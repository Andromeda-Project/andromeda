<?php
/* ================================================================== *\
   (C) Copyright 2005 by Secure Data Software, Inc.
   This file is part of Andromeda

   Andromeda is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Andromeda is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Andromeda; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor,
   Boston, MA  02110-1301  USA 
   or visit http://www.gnu.org/licenses/gpl.html
\* ================================================================== */
/**
name:index_hidden.php

This is the hidden part of the two-file universal dispatch system.
The public file is index.php, which sets the path and immediately
passes execution to this file.

*/

// ==================================================================
// >>> 
// >>> Make INI file settings here
// >>> 
// ==================================================================
index_hidden_ini_set();
index_hidden_session_start();
index_hidden_appinfo();
index_hidden_getpost();
index_hidden_context();
index_hidden_includes();
SessionSet('count',SessionGet('count')+1);
vgfSet('cache_pkey',array('member_profiles'));

// Valid session with logout request logs out that session
if(gp('st2logout')<>'') {
   SessionReset();
}

if(gpExists('x_module')) {
   SessionSet('AGMENU_MODULE',gp('x_module'));
}
elseif(vgaGet('nomodule')<>'' && SessionGet('AGMENU_MODULE')=='') {
   SessionSet('AGMENU_MODULE',vgaGet('nomodule'));
}

index_hidden_redirections();
index_hidden_uid_determine();

// command line programs now exit, otherwise proceed
// to routing
if(!isset($_SERVER['HTTP_HOST'])) return;
if(isset($force_cli))   return;
if(isset($header_mode)) return;

index_hidden_routing();
// ==================================================================
// MAIN PROCESSOR ROUTINES
// ==================================================================
function index_hidden_ini_set() {
   ini_set("allow_url_open",false);
   ini_set("error_reporting",E_ALL);
   ini_set("display_errors",true);
   ini_set("log_errors",true);
   ini_set("short_tag_open",true);
}

function Index_Hidden_Session_Start() {
   session_start();
   header("Cache-control: private");  // added at advice of tutorial
}

function index_hidden_appinfo() {
   global $AG;
   @include("appinfo.php");
   if (!isset($AG['application'])) {
      $AG['application'] = 'andro';
      $AG['app_desc'] = 'Unknown';
   }
}

function index_hidden_getpost() {
   global $AG;
   $AG['clean']= array();
   $AG['clean']['gp_page'] = '';
   $AG['clean']['gp_action'] = '';

   
   Index_Hidden_RecurseGP($AG['clean'],$_POST);
   Index_Hidden_RecurseGP($AG['clean'],$_GET);
}

function index_hidden_recursegp(&$dest,&$src) {
   foreach($src as $key=>$value) {
      if(is_array($value)) {
         $dest[$key]=array();
         Index_Hidden_RecurseGP($dest[$key],$value);
      }
      else {
         $dest[$key] 
            =get_magic_quotes_gpc()==1
            ? stripslashes($value) 
            : $value;
      }
   }
}

function index_hidden_context() {
   global $AG;
   if(!isset($AG['clean']['gpContext'])) {
      $AG['clean']['gpContext']=array();
   }
   else {
      if(!isset($AG['clean']['gpContext'])) {
         $t2=array();
      }
      else {
         $t2 = base64_decode($AG['clean']['gpContext']);
         $t2 = gzuncompress($t2);
         $t2 = unserialize($t2);
      }
      $AG['clean']['gpContext'] = $t2;
   }
}

function index_hidden_redirections() {
   // The parameter 'gp_pageal' means page to go to after a login.
   // Save it now.  Used originally for project cme.
   if(gpExists('gp_pageal')) {
      SessionSet('clean',array('gp_page'=>gp('gp_pageal')));
   }
   
   // The parameters 'gp_aftersave' means go to a page after saving
   // information somewhere else.  The program processPost will look
   // for this after saving and do a gpSet() to this value.
   if(gpExists('gp_aftersave')) {
      SessionSet('gp_aftersave',gp('gp_aftersave'));
   }
}

function index_hidden_uid_determine() {
   global $AG;
   // This is the only place where we branch out of sequence to 
   // actual page processing, and it is only for logins.
   //
   // Begin with an anonymous connection if no identity.  An expired
   // session will create a new session and return no ID, same as
   // the first visit.
   //
   $uid = SessionGet('UID','');
   if($uid=='') {
      SessionReset();
      SessionSet('UID',$AG['application']);
      SessionSet('PWD',$AG['application']);
   }
   
   // A direct assignment of uid/pwd must still go through
   // the login system, but it does it in "quiet" mode, not bothering
   // to set up menus or anything like that.
   //
   $directlogin=false;
   if (gp('gp_uid')<>'') {
      $directlogin=true;
      $directclean = $AG['clean'];
      gpSet('loginUID',gp('gp_uid'));
      gpSet('loginPWD',gp('gp_pwd'));
      gpSet('gp_posted',1);
      gpSet('gp_page','x_login');
   }
   
   $gp_page = gp('gp_page');
   if (gp('gp_page') == 'x_login') {
      $obj_login = raxTableObject('x_login');
      $obj_login->directlogin = $directlogin;
      $obj_login->Login_Process();
      if(LoggedIn()) {
         // A direct login restores the "clean" array as it was
         if($directlogin) {
            unset($directclean['gp_uid']);
            unset($directclean['gp_pwd']);
            unset($directclean['loginUID']);
            unset($directclean['loginPWD']);
            $AG['clean']=$directclean;
         }
         elseif(count(SessionGet('clean',array()))<>0) {
            // These were a page attempt made w/o being logged in,
            // which is now being ok'd since the user is logged in.
            $GLOBALS['AG']['clean'] = SessionGet('clean');
            if(isset($GLOBALS['AG']['clean']['ajxBUFFER'])) {
               unset($GLOBALS['AG']['clean']['ajxBUFFER']);
            }
            SessionUnSet('clean');
            // In pos systems, save the page they are authenticated for
            if(vgaGET('POS_SECURITY',false)==true) {
               SessionSet('POS_PAGE',gp('gp_page'),'FW');
            }
         }
      }
   }

   // This is an after-the-fact check.  The login is never supposed
   // to allow logins to "postgres" or any user whose name begins 
   // with the application code.  If the login system let something
   // get by, then we trap it here.  We also set the user to anonymous
   //
   // Note however that an EXACT match of user_id to application code
   // is ok, that is the so-called "anonymous" account.  
   // 
   $uid=trim(SessionGet('UID'));
   $app=trim($AG['application']);
   $uidx=substr($uid,0,strlen($app));
   if(!trim($uid)==trim($app)) { 
      if($uid=='postgres' || $uidx == $app) {
         SessionReset();
         SessionSet('UID',$app);
         SessionSet('PWD',$app);
         fwLogEntry(1002,'Logged in as postgres or group role',$uid);      
      }
   }
}


function index_hidden_routing() {
   //include_once('raxlib-db.php');

   // If making an ajax call and session time out, send to logout
   if(Count(SessionGet('clean',array()))>0 && gpExists('ajxBUFFER')) {
      echo '_redirect|?st2logout=1';
      return;
   }
   
   // If the search flag is set, we need to know what class for this
   // application handles searchs
   if(gpExists('gp_search')) {
      gpSet('gp_page',vgaGet('SEARCH_CLASS'));
   }
   
   // If gp_echo, just echo back.  This is for debugging of course.
   if(gpExists('gp_echo')) {
      echo "echo|".gp('gp_echo');
      return;
   }
   
   // connect and dispatch the request
   scDBConn_Push();
      
   // KFD 6/7/07, make any calls just after db connection
   if(function_exists('app_after_db_connect')) {
      app_after_db_connect();
   }
   
   // This one is not exclusive.  Calling a command populates
   // gp variables to redirect the call
   if(gpExists('gp_command')) index_hidden_command();
   
   if(    gp('ajxFETCH')   =='1') index_hidden_ajxFETCH();
   // KFD 10/8/07, 2-column foreign key
   elseif(gp('ajxfSELECT')  <>'') index_hidden_ajxfSELECT();
   elseif(gp('ajxc1table')  <>'') index_hidden_ajx1ctable();
   elseif(gp('gp_function') <>'') index_hidden_function();
   elseif(gp('gp_dropdown') <>'') index_hidden_dropdown();
   elseif(gp('gp_fetchrow') <>'') index_hidden_fetchrow();
   elseif(gp('gp_sql')      <>'') index_hidden_sql();
   elseif(gp('gp_ajaxsql')  <>'') index_hidden_ajaxsql();
   else                           index_hidden_page();
   
   // All finished, disconnect and leave. 
   scDBConn_Pop();
   return;
}
// ------------------------------------------------------------------
// >> Ajax refresh a select that is 2nd column in foreign key
// ------------------------------------------------------------------
function index_hidden_ajxfSELECT() {
    // fetch parms
    $table   = gp('ajxfSELECT');
    $col1    = gp('col1');
    $col1val = gp('col1val');
    $col2    = gp('col2');
    $col2val = gp('col2val');
    $pfx     = gp('pfx');
    
    // Now do matches, make call
    $matches = array($col1=>$col1val);
    
    $html = hOptionsFromTable($table,$col2val,'',$matches,$col2);
    
    echo "$pfx$col2|".$html;
    
}

// ------------------------------------------------------------------
// >> Call from browser to server-side function
// ------------------------------------------------------------------
function index_hidden_function() {
   if(!function_exists('app_server_side_functions')) {
      echo '_script|function_return_value=false';
      echo '|-|echo|No server-side dispatcher on this application';
      return;
   }
   else {
      app_server_side_functions();
   }
}

// ------------------------------------------------------------------
// >> Execute a command.  Created 6/29/07 KFD originally for
// >>    medical program.  Only support in first version is 
// >>    to give a quick route to lookups
// ------------------------------------------------------------------
function index_hidden_command() {
   // Get command, strip out multiple spaces, split up and
   // grab the command separate from the arguments
   $commands = gp('gp_command');
   $commands = preg_replace('/\s{2,}/',' ',$commands);
   gpSet('gp_command',$commands);
   $args = explode(' ',$commands);
   
   $table_frag = array_shift($args);
   $table_frag = strtolower($table_frag);
   
   // If a special command was added, pull it out
   $dotcmd='';
   if(strpos($table_frag,'.')!==false) {
      list($table_frag,$dotcmd)=explode('.',$table_frag,2);
   }
   
   // Now run through the list of pages looking for the first match, but
   // look at the aliases first if they exist
   $aliases=ArraySafe($GLOBALS,'COMMAND_ALIASES',ARRAY());
   foreach($aliases as $alias=>$page) {
      if(substr(strtolower($alias),0,strlen($table_frag))==$table_frag) {
         $table_id = $page;
         break;
      }
   }
   $PAGES=array();
   include('ddpages.php');
   $pages = array_keys($PAGES);
   foreach($pages as $page) {
      if(substr(strtolower($page),0,strlen($table_frag))==$table_frag) {
         $table_id = $page;
         break;
      }
   }
   
   // Can't figure it, have to leave  
   if(!isset($table_id)) {
      vgfSet('command_error','Unknown: '.$table_frag);
      return;
   }

   // Now decide what to do.
   if($dotcmd=='new'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         ) {
      gpSet('gp_mode','ins');
      gpSet('gp_page',$table_id);
   }
   else if ($dotcmd<>'') {
      vgfSet('command_error','Unknown Command: '.$table_frag.'.'.$dotcmd);
      return;
   }
   else {
      // for now we assume everything else is a lookup
      if(count($args)==0) {
         // No arguments means go to lookup mode
         gpSet('gp_mode','search');
         gpSet('gp_page' ,$table_id);
      }
      else {
         // Clear prior search results
         processPost_TableSearchResultsClear($table_id);
         
         // Re-write the query into a search
         // by setting our values as the search values
         gpSet('gp_page' ,$table_id);
         gpSet('gpx_page',$table_id);
         gpSet('gp_mode' ,'browse');
         gpSet('gpx_mode','search');
         $dd=dd_tableRef($table_id);
         $cols = explode(',',$dd['projections']['_uisearch']);
         array_shift($cols);    // pop off the first one, assume it is the pk/code
         gpUnsetPrefix('x2t_'); // clear troublesome values 
         foreach($cols as $i=>$colname) {
            if(isset($args[$i])) {
               gpSet('x2t_'.$colname,$args[$i]);
            }
         }
      }
   }
}

// ------------------------------------------------------------------
// >> Return data to a call from dhtmlgoodies_ajax_list
//    Active 1/17/07, used by ajax-dyanmic-list
// ------------------------------------------------------------------
function index_hidden_dropdown() {
  
   // Get the target table that we need
   $table_id_fk=gp('gp_dropdown');
   
   // Strip a leading slash from the value
   $gpletters=trim(gp('gp_letters'));
   
   // Pull the rows from handy library routine.
   if(gp('gpv')=='2') {
       $rows=RowsForSelect($table_id_fk,$gpletters,array(),'',true);   
   }
   else {
       $rows=rowsForSelect($table_id_fk,$gpletters);
   }

   // KFD 11/4/07.  If "version 2", then turn into a table
   if(gp('gpv')=='2') {
       ob_start();
       echo "androSelect|";
       echo "<table>";
       foreach($rows as $idx=>$row) {
           $prev = $idx==0                ? '' : $rows[$idx-1]['skey'];
           $next = $idx==(count($rows)-1) ? '' : $rows[$idx+1]['skey'];
           $s = $row['skey'];
           $tds='';
           $x=-1;
           foreach($row as $colname=>$colvalue) {
               $x++;
               if($colname=='skey') continue;
               if($x==1) $value = $colvalue;
               $tds.="<td>$colvalue";
           }
           echo "<tr id='as$s' 
                     x_prev='$prev' x_next='$next' x_skey='$s'
                     x_value='$value'
                onmouseover='androSelect_mo(this,$s)'
                onclick=\"androSelect_click('$value')\";                
                >"
                .$tds;
       }
       echo ob_get_clean();
       return;                                               
   }
   
   
   // Echo out the return
   foreach($rows as $row) {
      echo $row['_value']."###".$row['_display']."<br>";
   }
   if(Errors()) {
      $he=hErrors();
      syslog(LOG_INFO,$he);
   }
}
// ------------------------------------------------------------------
// >> Return a single row from a table 
//    Created 4/18/07 originally for project "jewel"
// ------------------------------------------------------------------
function index_hidden_fetchrow() {
   // Get the target table that we need
   $table_id=gp('gp_fetchrow');
   
   // Load the data dictionary and format the pk value.  Notice 
   // that we assume a single-column pk, it will break otherwise.
   //
   $table_dd=dd_TableRef($table_id);
   $pkcol  =$table_dd['pks']; 
   $type_id=$table_dd['flat'][$pkcol]['type_id'];
   $pkval=SQL_Format($type_id,gp('gp_pk'));
   
   // Fetch the row
   $answers=SQL_AllRows(
      "SELECT * from $table_id WHERE $pkcol=$pkval"
   );
   if(count($answers)==0) {
      $row=array($pkcol=>$pkval);
   }
   else {
      $row = $answers[0];
   }
   
   // Collapse the values
   $returns=array();
   foreach($row as $colname=>$colvalue) {
      if(is_numeric($colname)) continue;
      $returns[]="_value|$table_id"."_$colname|".$colvalue;
   }
   echo implode("|-|",$returns);
   return;
}

// ------------------------------------------------------------------
// >> Write a single column value to a single row, then return
//    values and assign them to controls.
//    Created 4/19/07 KFD for the POS screen of project 'jewel'
// ------------------------------------------------------------------
function index_hidden_ajx1ctable() {
   // First fetch the values
   $table_id = gp('ajxc1table');
   $colname  = gp('ajxcol');
   $colvalue = gp('ajxval');
   $skey     = gp('ajxskey');
   
   // Now prepare the data dictionary and issue the update
   $dd = dd_tableref($table_id);
   $colvalue = SQL_Format($dd['flat'][$colname]['type_id'],$colvalue);
   SQL("UPDATE $table_id SET $colname = $colvalue WHERE skey = $skey");

   // Errors will appear as a popup
   if(Errors()) {
      echo "echo|".hErrors();
      return;
   }
   
   // If they requested values back, provide those now
   if(gp('ajxlist')<>'' && gp('ajxlist')<>'X') {
      // Initialize the array of information about the queries we will make 
      $info=array(
         $table_id=>array('skey'=>$skey)
      );
      
      // Split the list and build the arrays of information we need to
      // run queries and generate return values for display
      $raw=explode(',',gp('ajxlist'));
      $returns=array();
      foreach($raw as $rawone) {
         // Parse each value into the three values it contains
         list($control,$table,$column)=explode(".",$rawone);
         if($control=='-skey-') {
            // this is magic value that tells us its the skey
            $info[$table]['skey']=$column; //column actually is skey here
         }
         else {
            // normal case, we've been told a control, table, and column.
            // If no table given, assume the table we updated
            $table=$table=='' ? $table_id : $table;
            $info[$table]['columns'][]=$column;
            $info[$table]['controls'][]=$control;
         }
      }
      
      // Now go through each table, pull the data, and build the returns
      foreach($info as $table=>$tabinfo) {
         if(!isset($tabinfo['skey'])) {
            $returns[]="echo|No skey passed for table $table";
         }
         else {
            $sk=$tabinfo['skey'];
            $cols=implode(',',$tabinfo['columns']);
            $row=SQL_OneRow(
               "SELECT $cols FROM $table WHERE skey = $sk"
            );
            foreach($tabinfo['columns'] as $index=>$colname) {
               $returns[]
                  ='_value'
                  .'|'.$tabinfo['controls'][$index]
                  .'|'.$row[$colname];
            }
         }
      }
      
      echo implode("|-|",$returns);
   }
}

// ------------------------------------------------------------------
// >> Fetch one or more columns from a table and return the
//    results as widget value assignments.
//    Created 5/21/07 KFD for the inventory receiving of 'jewel'
//    project, with general application eventually to any project.
// ------------------------------------------------------------------
function index_hidden_ajxFETCH() {
   $returns=array();

   // First fetch the values
   $table_id  = gp('ajxtable');
   $table_dd  = dd_Tableref($table_id);
   $colname   = gp('ajxcol');
   $colvalue  = gp('ajxval');
   $lcontrols = gp('ajxcontrols');
   $lcolumns  = gp('ajxcolumns');
   $acontrols = explode(',',$lcontrols);
   $acolumns  = explode(',',$lcolumns);
   
   // Since this is an ajax call, malformed requests exit with
   // no error or explanation
   if(count($acontrols)<>count($acolumns)) exit;
   if(count($acontrols)=='') exit;
   if($colvalue=='') exit;
   
   // Split column names and values and build a where clause
   // If any funny business, exit with no explanation
   $acols=explode(',',$colname);
   $avals=explode(',',$colvalue);
   $awhere=array();
   if(count($acols)<>count($avals)) exit;
   foreach($acols as $x=>$acol) {
      $awhere[]
         =str_replace("'","''",$acol)
         .' = '
         .SQLFC($avals[$x]);
   }
  
   // Build and execute some SQL
   $sq="SELECT ".str_replace("'","''",$lcolumns)
      ."  FROM ".str_replace("'","''",$table_id)
      ." WHERE ".implode(' AND ',$awhere);
   $row=SQL_OneRow($sq);
   
   // Any unusable results return with no error or complaint
   if(!is_array($row)) {
      return false;
   }
   if(count($row)==0) {
      return false;
   }
   
   //ob_start();
   //hprint_r($row);
   //$returns[]="echo|".ob_get_clean();
   //$returns[]='echo|'.$sq;
   
   // Build and return the controls
   foreach($acontrols as $x=>$acontrol) {
      //$returns[]='echo|'.$acontrol;
      //$returns[]='echo|'.$acolumns[$x];
      $cn=$acolumns[$x];
      if($table_dd['flat'][$cn]['type_id']=='date') {
         $row[$cn] = hFormat('date',$row[$cn]);
      }
      if(is_null($row[$cn])) {
         $row[$cn]='';
      }
      else {
         $row[$cn] = trim($row[$cn]);
      }
      $returns[]='_value|'.$acontrol.'|'.trim($row[$acolumns[$x]]);
   }
   echo implode('|-|',$returns);
}

// ------------------------------------------------------------------
// >> Simple SQL Query, return SQL_Allrows array
//    Active 1/17/07, known to be used by andro/a_pullcode.php
// ------------------------------------------------------------------
function index_hidden_sql() {
   $rows=SQL_AllRows(gp('gp_sql'),gp('gp_col'));
   echo "<html>".serialize($rows)."</html>";
}
// ------------------------------------------------------------------
// >> Simple SQL Statement
//    Active 1/17/07, known to be used by quicktime.php
// ------------------------------------------------------------------
function index_hidden_ajaxsql() {
   switch(gp('gp_ajaxsql')) {
      case 'update':
         $row=aFromgp('txt_');
         foreach($row as $key=>$value) {
            if($value=='b:true') $row[$key]='Y';
            if($value=='b:false')$row[$key]='N';
         }
         $table_id=gp('gp_table');
         SQLX_Update($table_id,$row);
         break;
      case 'insert':
         $row=aFromgp('txt_');
         $table_id=gp('gp_table');
         // XDB
         SQLX_Insert($table_id,$row);
         break;
   }
   if(Errors()) {
      echo 'echo|'.hErrors();
   }
}

// ------------------------------------------------------------------
// >> HTML, object pulls
// ------------------------------------------------------------------
function index_hidden_page_mime() {
    $x_mime  = CleanGet('x_mime');
    $x_table = CleanGet("gp_page");
    //$x_column= CleanGet("x_column");
    $x_skey  = CleanGet("x_skey");
    //$sql ="Select skey,$x_column FROM $x_table WHERE skey = $x_skey"; 
    //$row = SQL_OneRow($sql);
    
    $filename= "$x_table-$x_mime-$x_skey.$x_mime";
    $filepath=scAddSlash($GLOBALS['AG']['dirs']['root']).'files/'.$filename;
      
    //header('Content-type: audio/mpeg');
    // Kind of nifty, gives suggested filename to save
    header(
      'Content-disposition: attachment '
      .'; filename="'.$filename.'"'
    );
    echo file_get_contents($filepath);
}

// ------------------------------------------------------------------
// >> HTML, conventional web page
// ------------------------------------------------------------------
function index_hidden_page() {
   global $AG;
   global $AGOBJ;
   $sessok=!LoggedIn() ? false : true;
   
   // Load up a list of pages that public users are allowed to see,
   // with home and password always there.
   global $MPPages; // allows it to be in applib 
   $MP     = array();
   //$MPPages= array();
   // This is the old method, load $MPPages from its own file
   if(file_exists_incpath('appPublicMenu.php')) {
      include_once('appPublicMenu.php');
   }
   if(!is_array($MPPages)) {
      $MPPages = array();
   }
   $MPPages['x_home']='Home Page';
   $MPPages['x_login']='Login';
   $MPPages['x_noauth']='Authorization Required';
   $MPPages['x_password']="Password";
   $MPPages['x_mpassword']="Member Password";
   $MPPages['x_paypalipn']='Paypal IPN';

   // If the install page exists, it will be used, no getting
   // around it.
   $install=$GLOBALS['AG']['dirs']['application'].'install.php';
   $instal2=$GLOBALS['AG']['dirs']['application'].'install.done.php';
   if(file_exists($install)) {
      if(gp('gp_install')=='finish') {
         rename($install,$instal2);
      }
      else {
         $MPPages['install']='install';
         gpSet('gp_page','install');
      }
   } 
   
   // First pass is to look for the "flaglogin" flag.  This says save all
   // current page settings and go to login screen.  They will be restored
   // on a successful login.  Very useful for links that say "Login to 
   // see nifty stuff..." 
   if (gp('gp_flaglogin')=='1') {
      gpSet('gp_flaglogin','');
      gpToSession();
      gpSet('gp_page','x_login');
   }
   
   // Second pass redirection, pick default page if there
   // is none, and verify public pages.
   //
   $gp_page = gp('gp_page');
   if ($gp_page=='') {
       if(vgfGet('LoginAttemptOK')===true && vgfGet('x4')===true) {
           $gp_page='x4init';
           gpSet('gp_page','x4init');
           SessionSet('TEMPLATE','x4');
       }
       else {
               
          if(function_exists('appNoPage')) {
             $gp_page=appNoPage();
          }
          else {
             if(!LoggedIn()) {
                $gp_page = FILE_EXISTS_INCPATH('x_home.php') ? 'x_home' : 'x_login';
             }
             else {
                // KFD 3/2/07, pull vga stuff to figure defaults
                if(vgaGet('nopage')<>'') {
                   $gp_page = vgaGet('nopage');
                }
                else {
                   $gp_page = 'x_welcome';
                }
                //$gp_page = $AGOBJ->WelcomePageName();
             }
          }
       }
   }
   // If they are trying to access a restricted page and are not 
   // logged in, cache their request and redirect to login page
   if(!$sessok && !isset($MPPages[$gp_page])) {
      if(vgfGet('loglogins',false)) {
         fwLogEntry('1014','Page access w/o login',$gp_page);
      }
      gpToSession();
      $gp_page='x_login';
   }
   // If pos is activated and the current requested page does not
   // match what they are cleared for, redirect to login
   if(vgaGet('POS_SECURITY',false)==true && SessionGet('ADMIN')==false) {
      if(SessionGet('POS_PAGE','','FW')<>$gp_page) {
         gpToSession();
         $gp_page='x_login';
      }
   }
   gpSet('gp_page',$gp_page);

   // Make any database saves.  Do this universally, even if save
   // was not selected.  If errors, reset to previous request.
   //if(gp('gp_save')=='1') processPost();
   processPost();
   if (Errors()) {
      gpSetFromArray('gp_',aFromGp('gpx_'));
   }
   
   
   // Put Userid where HTML forms can find it
   //vgfSet("UID",SessionGet("UID"));
   //if (vgfSet("UID")=="") { vgfSet("UID","Not Logged In"); }

   // THIS IS NEWER X_TABLE2 version of drilldown commands,
   // considerably simpler than the older ones. It makes use of 
   // three gp_dd variables.
   //
   // Notice how we process drillbacks FIRST, allowing a link 
   // to contain both drillback and drilldown, for the super-nifty
   // effect of a "drill-across"
   regHidden('gp_dd_page');
   regHidden('gp_dd_skey');
   regHidden('gp_dd_back');
   if (intval(gp('gp_dd_back'))>0 && $sessok) {
      // this is drillback
      $dd = ContextGet('drilldown',array());
      $back=intval(gp('gp_dd_back'));
      if (count($dd)>=$back) {
         $spot = count($dd)-$back;
         $aback=$dd[$spot];
         gpSet('gp_skey' ,$aback['skey']);
         gpSet('gp_page' ,$aback['page']);
         $gp_page=$aback['page'];
         gpSet('gpx_skey',$aback['skey']);
         gpSet('gpx_page',$aback['page']);
         gpSetFromArray('parent_',$aback['parent']);
         if(!gpExists('gp_mode')) gpSet('gp_mode','upd');
         $dd=($spot==0) ? array() : array_slice($dd,0,$spot);
         ContextSet('drilldown',$dd);
         ContextSet('drilldown_top',$aback['page']);
         //ContextSet('drilldown_level',count($dd));
      }
   }
   if(gp('gp_dd_page')<>'' && $sessok) {
      // this is drilldown...
      $matches = DrillDownMatches();
      $matches = array_merge($matches,aFromGP('parent_'));
      $dd = ContextGet('drilldown',array());
      $newdd = array(
         'matches'=>$matches
         ,'parent'=>aFromGP('parent_')
         ,'skey'=>gp('gpx_skey')
         ,'page'=>gp('gpx_page')
      );
      $dd[] = $newdd;
      ContextSet('drilldown',$dd);
      ContextSet('drilldown_top',gp('gp_dd_page'));
      //ContextSet('drilldown_level',count($dd));
      
      // having saved the stack, redirect to new page.
      $tnew = gp('gp_dd_page');
      $gp_page=$tnew;
      gpSet('gp_page',$tnew);
      if(gp('gp_dd_skey')<>'') {
         gpSet('gp_skey',gp('gp_dd_skey'));
         gpSet('gp_mode','upd');
      }
      
      // Clear search of new page, set filters to blank 
      processPost_TableSearchResultsClear($tnew);
      ConSet('table',$tnew,'search',array());
   }
   
   // If no drilldown commands were received, and we are not on
   // the page that is the top, user must have picked a new page
   // altogether, wipe out the drilldown stack
   if(gp('gp_page')<>ContextGet('drilldown_top','')) {
         ContextSet('drilldown',array());
         ContextSet('drilldown_top','');
   }
   
  
   // Must always have these on the user's form.  These can
   // be retired with x_Table, they are for old drilldown
   //
   regHidden("dd_page","");
   regHidden("dd_ddc","");
   regHidden("dd_ddv","");
   regHidden("dd_ddback","");
   regHidden("dd_action","searchexecute");
   regHidden("dd_skey","");
   
   // Load user preferences just before display
   UserPrefsLoad();
   
   $dir=$GLOBALS['AG']['dirs']['root'].'application/';
   if(file_exists($dir.$gp_page.".page.yaml")){
       include 'androPage.php';
       $obj_page = new androPage();
       ob_start();
       $obj_page->main($gp_page);
       vgfSet("HTML",ob_get_clean());
       vgfSet("PageSubtitle",$obj_page->PageSubtitle);
   }
   else {
       $obj_page = DispatchObject($gp_page);
       if ($obj_page->flag_buffer) { ob_start(); }
       $obj_page->main();
       if ($obj_page->flag_buffer && vgfGet('HTML')=='') {
          vgfSet("HTML",ob_get_contents());
          ob_end_clean();
       }	
       vgfSet("PageSubtitle",$obj_page->PageSubtitle);
   }
     
   // Save context onto the page.  Note that it is not really
   // protected by these methods, just compressed and obscured.
   //
   $t2 = serialize($GLOBALS['AG']['clean']['gpContext']);
   $t2 = gzcompress($t2);
   $t2 = base64_encode($t2);
   Hidden('gpContext',$t2);
   
   // KFD 3/7/07, give the app the final opportunity to process
   //             things before the display, while logged in.
   if(function_exists('appdisplaypre')) {
      appDisplayPre();  
   }
   
   // ...and write output and we are done.  Assume if there was
   // no buffering that the output is already done.
   if($obj_page->flag_buffer!=false) {
      // Work out what template we are using
      index_hidden_template();

      // KFD 5/30/07, send back only main content if asked 
      if(gp('ajxBUFFER')==1) {
         echo "andromeda_main_content|";
         ehStandardContent();
         echo "|-|_focus|".vgfGet('HTML_focus');
         $ajax=ElementReturn('ajax',array());
         echo '|-|'.implode('|-|',$ajax);
         echo '|-|_title|'.vgfGet('PageTitle');
      }
      elseif(defined('_VALID_MOS')) {
         // This is the default branch, using a Joomla template
         global $J;
         $mainframe               = $J['mainframe'];
         $my                      = $J['my'];
         $mosConfig_absolute_path = $J['mC_absolute_path'];
         $mosConfig_live_site     = $J['mC_live_site'];
         $template_color          = $J['template_color'];
         $template_color = 'red';
         $file
            =$GLOBALS['AG']['dirs']['root'].'/templates/'
            .$mainframe->GetTemplate()."/index.php";
         include($file);
      }
      elseif($obj_page->html_template!=='') {
         // This is newer style, let the class specify the template.
         include($obj_page->html_template.'.php');
      }
      else {
         // This is old style, defaults to "html_main.php", can be
         // set also by vgaSet() or by gp(gp_out)
         $html_main = vgaGet('html_main')==''? 'html_main' : vgaGet('html_main');
         switch (CleanGet("gp_out","",false)) {
            case "print": include("html_print.php"); break;
            case "info" : include("html_info.php");  break;
            case "":      include($html_main.".php");  break;
            default: 
         }
      }
   }
}





function index_hidden_template() {
   global $AG;
   // First conditional fix contributed by Don Organ 9/07, $AG['template']
   // was getting lost on passes 2+
   if(ArraySafe($AG,'template')<>'') {
       SessionSet('TEMPLATE',$AG['template']); 
   }
   else {
       if(SessionGet("TEMPLATE")=='') {
          if(!file_exists(fsDirTop().'templates')) {
             // There is no templates directory, so stop looking
             SessionSet('TEMPLATE','*');
          }
          else {
             if(ArraySafe($AG,'template')<>'') {
                // if the app or instance specified a template at build time,
                // use that.
                SessionSet('TEMPLATE',$AG['template']);
             }
             else {
                // At this point nobody has told us what to do, pick the
                // first template we can find.
                $dir = $AG['dirs']['root'].'templates/';
                $DIR = opendir($dir);
                while (false!==($filename = readdir($DIR))) {
                   if ($filename=='.')  continue;
                   if ($filename=='..') continue;
                   if ($filename=='andro_classic') continue;
                   if (is_dir($dir.$filename)) {
                      SessionSet('TEMPLATE',scFileName($filename));
                      break;
                   }
                }
                closedir($DIR);
             }
          }
       }
   }
   // Now if a template was identified
   if(SessionGet("TEMPLATE")<>'*') {
      // Notify any code that may need to know that we are in a hybrid
      // Andromeda-joomla situation.  This is for both template code and
      // Andromeda code.  We define both variables in case people forget
      // which one we defined.
      define("_ANDROMEDA_JOOMLA",1); 
      define("_JOOMLA_ANDROMEDA",1); 
      
      // Activate the template by creating public $J and calling funcs
      global $J;
      $J['TEMPLATE']=SessionGet('TEMPLATE');
      JoomlaCompatibility($J['TEMPLATE']);
      $aphp=$AG['dirs']['root'].'/templates/'.$J['TEMPLATE'].'/andromeda.php';
      if(file_exists($aphp)) {
         include($aphp);
      }
   }
   
}

function index_hidden_includes() {
   include_once('raxlib.php');
   include_once('raxlib-db.php');
   // CODE PURGE 7/5/07 KFD
   //if (substr(phpversion(),0,1)=='4') { 
   //   include_once('raxlib-php4.php'); 
   //}
   $x=fsDirTop().'application/applib.php';
   if (file_exists($x)) {
      include_once($x);
   }
}

// ==================================================================
// END OF LIVE CODE, beginning of library
// ==================================================================
/**
name:hprint_r
parm:any Input
returns:string HTML_Fragment

Invokes the PHP print_r command, but wraps it in HTML PRE tags so
it is readable in a browser.
*/
function hprint_r($anyvalue) {
	echo "<pre>\n";
   print_r($anyvalue);
	echo "</pre>";
}

// ------------------------------------------------------------------
/**
name:Session Variables
parent:Framework API Reference

Andromeda provides wrappers for accessing session variables.  The
PHP superglobal $_SESSION should not be directly accessed, instead
an Andromeda program should use [[SessionGet]] and [[SessionSet]]. 

Do not use session variables for storing information across different
requests, such as storing user replies going page-to-page through
a wizard.  Use [[Context Functions]] or [[Hidden Variables]]
for these instead, they are much more flexible and robust.

It may happen that you have multiple Andromeda applications on a server,
and that a browser is connected to more than one of them in multiple
tabls.  This would result in a collision if you were access $_SESSION
directly, because each app would overwrite the variables of the others.
Andromeda prevents these collisions automatically whenever 
[[SessionGet]] and [[SessionSet]] are used.

Andromeda also prevents collissions between session variables used by
the framework and those you may put into your application.  All of the
Session variables accept an optional last parameter (not documented in
the individual functions)  The default value of this parameter is
'app', but some framework functions call it with a value of 'fw' to keep
these variables separate from application variables.  It should be noted
that sometimes the framework uses application session variables, so that
the application can find them if necessary.  Examples of this are session
variables UID (current user_id) and PWD (password of current user).

*/

/**
name:SessionGet
parm:string Var_Name
parm:any Default_Value
returns:any

This program returns a session variable.  The second parameter 
is a [[Standard Default Value]] and will be returned if the 
Session variable Var_Name does not exist.

The framework itself tracks only 2 session variables.  These are UID, which
is user_id, and PWD, which is user password.  An application must be
careful not to overwrite those values, as the framework will make no
provision to prevent such an accident.
*/
function SessionGet($key,$default="",$sfx='app') {
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	if (isset($_SESSION[$xkey])) {
		return $_SESSION[$xkey];
	}
	else return $default;
}

/**
name:SessionSet
parm:string Var_Name
parm:any Var_Value
returns:any

This program sets a session variable.  The variable will exist
as long as the PHP session is alive.

The framework tracks only 2 session variables.  These are UID, which
is user_id, and PWD, which is user password.  An application must be
careful not to overwrite those values, as the framework will make no
provision to prevent such an accident.
*/
function SessionSet($key,$value,$sfx='app') {
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	$_SESSION[$xkey] = $value;
}

/**
name:SessionUnSet
parm:string Var_Name
returns:void

Destroys the named session variable.

The framework tracks only 2 session variables.  These are UID, which
is user_id, and PWD, which is user password.  An application should
never call SessionUnSet on these variables. 
*/
function SessionUnSet($key,$context='app',$sfx='app') {
   $x=$context;
   $xkey=$GLOBALS["AG"]["application"]."_".$sfx."_".$key;
	unset($_SESSION[$xkey]);
}

/**
name:SessionReset
returns:void

Destroys all session variables for the current application.  We use this
instead of PHP session_destroy because it allows a user to be logged in
to several apps at once, because the framework makes effective sessions
for each separate application.

Note that this function destroys both application and framework session
variables, there is more information on what these are on the
[[Session Variables]] page.
*/
function SessionReset() {
   global $AG;
   foreach($_SESSION as $key=>$value) {
      $app = $AG['application'].'_';
      if (substr($key,0,strlen($app))==$app) {
         unset($_SESSION[$key]);
      }
   }
}

/* DEPRECATED */
function SessionUnSet_Prefix($prefix) {
	$prefix = $GLOBALS["AG"]["application"]."_".$prefix;
	foreach ($_SESSION as $key=>$value) {
		if (substr($key,0,strlen($prefix))==$prefix) {
			unset($_SESSION[$key]);
		}
	}
}
// ==================================================================
// ==================================================================
// Global Variables
// ==================================================================
// ==================================================================
/**
name:_default_
parent:Global Variables
*/
// ------------------------------------------------------------------
/**
name:Global Variables
parent:Framework API Reference

Andromeda provides some wrapper functions for getting and setting
framework variables.  The main purpose of these is to allow your
application to create variables without worrying about name collisions
with framework global variables.

A global variable is one that exists from the beginning to the end of
a server request.  Once the HTML has been delivered to the browser, the
globals are all gone.  If you need to store variables that are
persistent from request to request, consider using [[Context Variables]].

You can set a Global Variable with [[vgaSet]] and
retrieve it with [[vgaGet]].

These values can be any valid PHP type.

The framework uses the corresponding functions [[vgfSet]] and
[[vgfGet]].
*/
/* DEPRECATED */
function ValueSet($key,$value) {
   echo "Calling Valueset $key <br/>";
   vgfSet($key,$value);
   if(!isset($GLOBALS['AG']['values'])) $GLOBALS['AG']['values']=array();
	$GLOBALS["AG"]["values"][$key] = $value;
}
/* DEPRECATED */
function ValueGet($key) {
   if(!isset($GLOBALS['AG']['values'])) $GLOBALS['AG']['values']=array();
	if (isset($GLOBALS["AG"]["values"][$key])) 
      return $GLOBALS["AG"]["values"][$key];
	else 
   	return "";
}
/* DEPRECATED */
function V($key,$value=null) {
	if (is_null($value)) { return ValueGet($key); }
	else ValueSet($key,$value);
}

/**
name:vgaGet
parm:string var_name
parm:string Default_Value
returns:any
group:

This function returns a [[Global Variable]].  The second parameter
names a [[Standard Default Value]] that will be returned if the
requested variable does not eixst.

You can use [[vgaGet]] and [[vgaSet]] to store and retrieve global
variables without worrying about naming collisions with the framework.
*/
function vgaGet($key,$default='') {
   return isset($GLOBALS['appdata'][$key])
      ? $GLOBALS['appdata'][$key]
      : $default;
}
/**
name:vgaSet
parm:string var_name
parm:any var_value
returns:void

This function sets the value of a global variable.
The variable will exist during the current request and can be 
accessed from any scope with the [[vgaGet]] function.

You can use [[vgaGet]] and [[vgaSet]] to store and retrieve global
variables without worrying about naming collisions with the framework.
*/
function vgaSet($key,$value='') {
   $GLOBALS['appdata'][$key]=$value;
}


/**
name:vgfGet
parm:string var_name
parm:string Default_Value
returns:any
group:

This function returns a [[Global Variable]].  The second parameter
names a [[Standard Default Value]] that will be returned if the
requested variable does not eixst.

The framework uses [[vgfGet]] and [[vgfSet]] to store and retrieve global
variables without worrying about naming collisions with an application.
*/
function vgfGet($key,$default='') {
   // hardcopy routines.  Some framework variables are actually
   // constructed from other things
   $hc=array('PageTitle');
   if(in_array($key,$hc)) return vgfGetHC($key,$default);
   
   if(isset($GLOBALS['fwdata'][$key])) {
      return $GLOBALS['fwdata'][$key];
   }
   elseif(isset($GLOBALS['AG'][$key])) {
      return $GLOBALS['AG'][$key];
   }
   else {
      return $default;
   }
}


function vgfGetHC($key,$default='') {
   switch($key) {
      case 'PageTitle':
         if(vgfGet('UseSubtitle',false)) {
            return vgfGet('PageSubtitle');
            break;
         }
         else {
            $repl=Optionget('SITETITLE');
            $base=$repl=='' ? ValueGet('PageTitle') : $repl;
            if(vgaGet('PageTitleSuffix')<>'') {
               $base=trim($base).": ".trim(vgaGet('PageTitleSuffix'));
            }
            return $base;
            break;
         }
      default:
         return $default;
   }
}

/**
name:vgfSet
parm:string var_name
parm:any var_value
returns:void

This function sets the value of a global variable.
The variable will exist during the current request and can be 
accessed from any scope with the [[vgfGet]] function.

The framework uses [[vgfGet]] and [[vgfSet]] to store and retrieve global
variables without worrying about naming collisions with the framework.
*/
function vgfSet($key,$value='') {
    //echo $key." - ".$value;
    //$a=xdebug_get_function_stack();
    //hprint_r($a);
    //echo $key." - ".$value."<br/><br/>";
   $GLOBALS['fwdata'][$key]=$value;
}
// ==================================================================
// ==================================================================
// Library Routines: Post/Get processing
// ==================================================================
// ==================================================================
/**
name:_default_
parent:GET-POST Variables
*/
// ------------------------------------------------------------------
/**
name:GET-POST Variables
parent:Framework API Reference

=Accessing POST and GET Variables=

Andromeda combines the PHP superglobals <span class="syntax10">$_GET</span>
and <span class="syntax10">$_POST</span> into one array.  The POST variables
are processed first, and then the GET variables are processed,
so that a GET will override a POST of the same name.  This feature
is entirely for the convenience of the programmer so that you do not
have to distinguish between these two sources.

Unlike many systems, Andromeda does ''not want to sanitize''
or in any way modify the data that comes in through POST/GET.  
There are two reasons for this:
   
*The sanitation process is different for a browser or a database,
      and sanitizing for one corrupts for the other.  Therfore we
      <a href="coding.html#5">Sanitize when Sending</a>.
*You may need to handle the raw data. 

The "no-sanitization" policy runs counter to the default installation
   of PHP5.  By default PHP5 has a setting turned on called
   <a class="phpfunc" href="http://www.php.net/manual/en/ref.info.php#ini.magic-quotes-gpc">magic-quotes-gpc</a> which modifies data.  During the
   processing of GET-POST Variables, Andromeda detects this setting.
   If the setting is turned on, Andromeda will pass the data through
   <a class="phpfunc" href="http://www.php.net/manual/en/function.stripslashes.php">stripslashes()</a> to return it to its original state.
   However, there is a small chance that this process will not return
   the exact value originally posted, so if you have a server used
   exclusively for Andromeda, you should turn off
   <a class="phpfunc" href="http://www.php.net/manual/en/ref.info.php#ini.magic_quotes_gpc">magic_quotes_gpc</a> in PHP.INI.

=Reading Variables From A Request=

You can pull any value from the current request with the [[gp]] function, 
which takes as its arguments the variable name.

You can find out if a variable was posted in by passing the variable name to
the [[gpExists]] function, which returns true or false.

You can capture a family of variables into a [[row array]] with the 
function [[roowFromGP]], which takes as its single argument a string prefix.
All variables whose name begins with that prefix will be put into the array 
that is returned.  The key names will have the prefix itself stripped off.

You can set the value of a posted variable, to make it look to later code as 
if it came from the browser, with [[gpSet]].  A variable set 
this way does not go out to the browser, it appears as if it came in on the
current request.  You can set the value of hidden variables that will go
back to the browser with the [[Hidden]] function.

=Writing Variables=

You can set the value of a hidden variable that will go out to the browser with
[[Hidden]] which takes as its arguments a name and a value.  This is not the
same as using [[gpSet]], because the former puts a value onto the form that
will be sent to the browser, and therefore returned on the next request, while the
latter "fakes" the appearance of a variable coming in on the current request.

=Framework Conventions=

The framework generates a lot of its own variables, which follow certain conventions. 
The framework uses prefixes to group variables together for similar treatment.
The special prefix for application-specific variables is "ga_", the framework will 
absolutely never create a form variable with that name prefix.

The conventions in use by the framework are:

*prefix: gp_, control parameters for a page request, such as a table name, 
     a flag to go to the next page, and so forth.  Never contains user data.
*subset: gp_dd_, used by the framework to specify drilldown and drillback commands.
8prefix: gpx_, These appear in every page sent to the browser, and contain 
     the parameters used to process and generate this HTML.  The gp_* variables
     that are read and processed at the beginning of a page request are written
     out at the end of the page request to generate these values.
*prefix: ga_, <b>reserved for application use</b>.  The framework will never produce
    variables with this name prefix.
*prefix: array_, visible user input controls such as HTML INPUT
        and TEXTAREA controls.
*prefix: parent_, hidden controls that contain the values of the primary key 
    of the current row of the current table.
*variable: gpContext, contains the entire [[window context]].  Serialized and base64'd.
*variable: gpControls, contains information about the array_* controls
    Serialized and base64'd.

The following are [[deprecated]] form variable conventions:
                                           
*prefix: txt_, deprecated.  Class x_table used these for user input controls.
*prefix: dd_, deprecated.  Class x_table used these 
  for drilldown information.


*/


/**
function:gp
parm:string GP_Name
parm:any GP_Default (optional)
returns: string

Returns the value of a [[GET-POST Variable]].  

If the variable was not received on the current request, and there is
no second parameter, gp returns an empty string.

If the variable was not received on the current request, and there is
 a second parameter, gp returns that value.  This makes for convenient
 coding of default values.

<pre class="code">
$value=gp('user_id','anonymous');
$value=SQLFC($value);
$sq="Select option from member_profiles WHERE user_id=$value";
</pre>
   
*/
function gp($key,$vardefault='') {
	$post=$GLOBALS["AG"]["clean"];
	if (!isset($post[$key])) return $vardefault;
	else return $post[$key];
}

/**
name:gpExists
parm:string GP_Name
returns:bool

Returns true if the named [[GET-POST Variable]] was sent by the browser
in the current request.
*/
function gpExists($key) {
	return isset($GLOBALS["AG"]["clean"][$key]);   
}

/**
function:hgp
parm:string GP_Name
parm:any GP_Default (optional)
returns:string

Returns the value of a [[GET-POST Variable]], having first sanitized
it for the browser.

Equivalent to calling [[gp]] and then passing it through
[[php:htmlentities]].
*/
function hgp($key,$default='') {
   $temp=gp($key,$default);
   return htmlentities($temp);
}


/**
name:rowFromGP
parm:string GP_Prefix
returns:array Row

Returns a [[Row Array]] taken from a subset of the GET-POST Variables
sent by the browser.  Only variables that begin with GP_Prefix will
be returned, and the GP_Prefix will be stripped off of the key.

!>example:Using rowFromGP
!>php:If an HTML Form contains these controls:
<input name='txt_control1' value='Foo'>
<input name='txt_control2' value='bar'>
!<
!>php:When the user submits the form, we use rowFromGP
<?php
$row=rowFromGP('txt_');
print_r($row)
?>
!<
!>output:Which will output the following
control1:control2;Foo:bar
!<
!<

*/
function rowFromgp($prefix) {
   return aFromgp($prefix);  
}


function removetrailingnewlines($input) {
   while(substr($input,-1,1)=="\n") {
      $input=substr($input,0,strlen($input)-1);
   }
   return $input;
}

/* DEPRECATED  (it was named wrong, should have been rowFromGP */
function aFromgp($prefix) {
	$strlen = strlen($prefix);
	$row = array();
	foreach ($GLOBALS["AG"]["clean"] as $colname=>$colvar) {
		if (substr($colname,0,$strlen)==$prefix) {
         $row[substr($colname,$strlen)] = $colvar;
		}
	}
	return $row;
}


/**
name:gpSet
parm:string GP_Name
parm:any NewValue

Sets the value of a GET-POST Variable so that later code sees it
as if it were passed from the browser.

The return value is not defined.

*/
function gpSet($key,$value='') {
	$GLOBALS["AG"]["clean"][$key] = $value;
}

/**
name:gpSetFromArray
parm:string GP_prefix
parm:array Row

Sets the value of a one or more GET-POST Variables so that later code sees 
them as if they were passed from the browser. 

One GET-POST Variable will be created for each element in the [[Row array]].
The name of the variable will be the string concatenation of GP_Prefix
and the array element's index.  The value will come from the array 
element's value.

*/
function gpSetFromArray($prefix,$array) {
   foreach($array as $key=>$value) {
      gpSet($prefix.$key,$value);
   }
}

/**
name:gpUnset
parm:string GP_Name

Destroys a GET-POST Variable so that later code cannot see it, simulating
the situation where the browser did not send the variable.
*/
function gpUnSet($key) {
	if (isset($GLOBALS["AG"]["clean"][$key])) {
      unset($GLOBALS["AG"]["clean"][$key]);
   }
}

/**
name:gpUnsetPrefix
parm:string GP_Prefix

Destroys all GET-POST Variables whose names begin with GP_Prefix,
so that later code cannot see them, simulating
the situation where the browser did not send the variables.
*/
function gpUnsetPrefix($prefix) {
   foreach($GLOBALS['AG']['clean'] as $key=>$value) {
      if(substr($key,0,strlen($prefix))==$prefix) {
         gpUnset($key);
      }
   }
}



/**
name:gpcontrols
returns:Array Special

Returns an array of information about the user input controls that
were sent out to the browser and returned by the current request. 
The structure of the array is:

Array(
  [0] => Array(
     't'=> table_id
     'c'=> column_id
     'v'=> column_value
     's'=> skey value
  ),
  [1] => ....
)
 
*/
function gpControls() {
   return unserialize(base64_decode(gp('gpControls')));
}


/* DEPRECATED */
function rowFromgpInputs() {
   return afromgp('txt_');  
}

/* DEPRECATED */
/*
function rowFromgp($table_id) {
   // First look for gp_skey
   $skey=CleanGet('gp_skey','',false);
   $skey=$skey<>'' ? $skey : Cleanget('txt_skey','',false);
   if($skey<>'') {
      $sq="SELECT * FROM ".$table_id." WHERE skey=".SQL_Format('numb',$skey);
      return SQL_OneRow($sq);
   }
   
   // no skey?  Look for the primary key, assume one column
   $table=DD_TableRef($table_id);
   $pkcol=$table['pks'];
   $pkval = CleanGet('gp_'.$pkcol,'',false);
   $pkval = $pkval<>'' ? $pkval : CleanGet('txt_'.$pkcol,'',false);
   if($pkval=='') {
      return false;
   }
   $sq="SELECT * FROM ".$table_id." WHERE $pkcol=".SQL_Format('char',$pkval);
   return SQL_OneRow($sq);
}
*/

/**
name:gpToSession
flag:framework

This function saves all [[GET-POST Variables]] to the session for
later retrieval.  It saves them to the session variable "clean".
They can be retrieved by calling SessionGet('clean').

This function is used by the framework when a user calls for a page
that requires a login.  This function caches the request until after
the user has logged in.

There is no stack of user requests.  If this function is called twice
without retrieving the values, then the second call overwrites the
first.

There is no function to retrieve the variables.  The framework 
pulls them directly by calling SessionGet('clean').
*/
function gpToSession() {
   SessionSet('clean',$GLOBALS['AG']['clean']);
}

// ------------------------------------------------------------------
// Named stack functions
// ------------------------------------------------------------------
/** (SYSTEM) Initialize a Stack
  *
  * Initializes a stack for {@link scStackPush} and {@link scStackPop}
  *
  * @param $stackname string 
  * @category miscellaneous utility
  */
function _scStackInit($stackname) {
   if (!isset($GLOBALS['STACK'])) {
      $GLOBALS['STACK']=array();
   }
   if (!isset($GLOBALS['STACK'][$stackname])) {
      $GLOBALS['STACK'][$stackname]=array();
   }
}
/** Push a value to a named stack
  * 
  * Pushes $value to the stack named by $stackname.  The value
  * can be retrieved with scStackPop.
  */
function scStackPush($stackname,$value) {
   _scStackInit($stackname);
   $GLOBALS['STACK'][$stackname][] = $value;
}
/** Pops a value from a named stack
  *
  * Pops the last-added value from a named stack.  Returns
  * null if the stack is empty, an empty stack does not
  * throw an error.
  *
  */
function scStackPop($stackname) {
   _scStackInit($stackname);
   return array_pop($GLOBALS['STACK'][$stackname]);
}
// ------------------------------------------------------------------
// Routines to assemble return values
// ------------------------------------------------------------------
function return_value_add($element,$value) {
   global $AG;
   $retvals=ArraySafe($AG,'retvals',array());
   $retvals[$element]=$value;
   $GLOBALS['AG']['retvals']=$retvals;
}
function retCmd($command,$element,$value) {
   return_command_add($command,$element,$value);
}
function return_command_add($command,$element,$value) {
   global $AG;
   $retcommands=ArraySafe($AG,'retcommands',array());
   $retcommands[$command][$element]=$value;
   $GLOBALS['AG']['retcommands']=$retcommands;
}

function returns_as_ajax() {
   global $AG;
   $retvals=ArraySafe($AG,'retvals',array());
   $rv2=array();
   foreach($retvals as $element=>$value) {
      $rv2[]=$element.'|'.$value;
   }
   $retcommands=ArraySafe($AG,'retcommands',array());
   foreach($retcommands as $cmd=>$info) {
      foreach($info as $element=>$value) {
         $rv2[] = $cmd.'|'.$element.'|'.$value;
      }
   }
   echo implode("|-|",$rv2);
}



// ------------------------------------------------------------------
// Data Dictionary Routines
// ------------------------------------------------------------------
function DD_EnsureREf(&$unknown) {
   if(is_array($unknown)) return $unknown;
   else return dd_TableRef($unknown);
}
function DD_Table($table_id) {
	include_once("ddtable_".$table_id.".php");
	return $GLOBALS["AG"]["tables"][$table_id];
}

/**
name:ddUserPerm
parm:string Table_ID
parm:string Perm_ID
returns:boolean

This function will tell you if the user is granted a particular permission
on a particular table.  

The permissions you can request are:
* sel: May the user select?
* ins: May the user Insert?
* upd: May the user Update?
* del: May the user Delete?
* menu: Does this person see this on the menu?  To return a true for this
  permission, the user must have menu permission and SELECT permission. 
*/
function ddUserPerm($table_id,$perm_id) {
   // Menu is done a little differently than the rest
   if($perm_id=='menu') {
      // KFD 7/19/07.  This code assumes that tablepermsmenu lists
      //               both the base table and derived column-security table,
      //               while tablepermssel lists only the views, go figure.
      $view_id=DDTable_idresolve($table_id);
      $pm = in_array($table_id,SessionGet('TABLEPERMSMENU',array()));
      $ps = in_array($view_id,SessionGet('TABLEPERMSSEL',array()));
      return $pm && ($ps || SessionGet("ROOT"));
   }
   
   // These are pretty simple
   $perm_id=strtoupper($perm_id);
   
   //$prms=SessionGET('TABLEPERMS'.$perm_id);
   
   return in_array($table_id,SessionGET('TABLEPERMS'.$perm_id));
}

//function D*D_arrBrowseColumns(&$table) {
//   $table=DD_EnsureRef($table);
//   $retval=array();
//   foreach($table['flat'] as $colname=>$colinfo) {
//      if(DD_ColumnBrowse($colinfo,$table)) {
//         $retval[$colname]=$colinfo['description'];
//      }
//   }
//   return $retval;
//}
function DD_ColumnBrowse(&$col,&$table)
{
	if ($col["column_id"]=="skey") return false;
	if ($col["uino"]=="Y")        return false;
	if ($col["uisearch"]=="Y")    return true;
	if ($table["risimple"]=="Y")  return true;
   return false;
}
function DD_TableProperty($table_id,$property) {
	$table = DD_Tableref($table_id);
	return $table[$property];	
}
function DD_TableDropdown($table_id) {
	// Get reference to table's data dictionary
	$table = DD_TableRef($table_id);
	
	// Look for a projection called "dropdown".  If
	// not found, use the list "pks"
	if (isset($table["projections"]["dropdown"])) {
		$ret = $table["projections"]["dropdown"];
	}
	else {
		$ret = $table["pks"];
	}
	return explode(",",$ret);	
}

/**
name:DDTable_IDResolve
parm:string $table_id
returns:string $view_id

Accepts the name of a table and returns the appropriate view to
access based on the user's effective group.

The name of a view is only returned if there is some reason to redirect
the user to a view.  In very many cases, oftentimes in all cases, the
function returns the base table name itself, such as:

* If no column or row security is on the table
* If the user is a root user
* If the user is the anonymous (login) user

*/
function DDTable_IDResolve($table_id) {
    // Both super user and nobody get original table
    if(SessionGet("ROOT")) {
      return $table_id;
    }
    // KFD 1/23/08.  This probably should never have been here,
    //     since it would always return an unusable answer.
    //
    //if(!LoggedIn()) {
    //   return $table_id;
    //}
    
    $ddTable=dd_TableRef($table_id);
    // This is case of nonsense table, give them back original table
    if(count($ddTable)==0) return $table_id;
    
    //echo "permspec is: ".$ddTable['permspec'];
    $views=ArraySafe($ddTable,'tableresolve',array());
    if(count($views)==0) 
        return $table_id;
    else
        // KFD 1/23/08.  This code takes advantage of the fact that
        //   the public user by itself is always the very last
        //   effective group.  Therefore, if a user is not logged
        //   in, we will take the very last entry, assuming that it
        //   gives the answer for somebody who is only in one group.
        //
        if(LoggedIn()) {
           return $views[SessionGet('GROUP_ID_EFF')];
        }
        else {
           return array_pop($views);   
        }
}

/**
name:DD_ColInsertsOK
parm:$colinfo
parm:$mode='html'
returns:bool

Accepts an array of dictionary information about a column and
then works out if inserts are allowed to that column.  Useful for
disabling HTML controls.

The optional 2nd parameter defaults to "html" but can also be "db".
If it is "html" it tells you if the user should be allowed to 
specify a value, while the value of "db" determines if a SQL Insert
should be allowed to specify a value for this column.

*/
function DD_ColInsertsOK(&$colinfo,$mode='html') {
   // If in a drilldown, any parent column is read-only
   if(DrillDownLevel()>0 & $mode=='html') {
      $dd=DrillDownTop();
      if(isset($dd['parent'][$colinfo['column_id']])) return false;
   }
	$aid = strtolower(trim($colinfo["automation_id"]));
	return in_array($aid,
      array('seqdefault','fetchdef','default'
          ,'blank','none','','synch'
          ,'queuepos','dominant'
      )
   );
}

function DD_ColUpdatesOK(&$colinfo) {
    // KFD 10/22/07, allow changes to primary key
    if($colinfo['primary_key']=='Y') {
        if(ArraySafe($colinfo,'pk_change','N')=='Y')
            return true;
        else 
            return false;
    }
    if(DrillDownLevel()>0) {
        $dd=DrillDownTop();
        if(isset($dd['parent'][$colinfo['column_id']])) return false;
    }
    $aid = strtolower(trim($colinfo["automation_id"]));
    if($aid=='') return true;
    $automations=array('seqdefault','fetchdef','default'
        ,'blank','none','','synch'
        ,'queuepos','dominant'
    );
    return in_array($aid,$automations);
}

function DDColumnWritable(&$colinfo,$gpmode,$value) {
   $NEVERUSED=$value;
   // If neither update or ins we don't know, just say ok
   if($gpmode <> 'ins' && $gpmode <> 'upd') return true;
      
   // Look for explicit settings in the dd arrays
   if(ArraySafe($colinfo,'upd','')=='N' && $gpmode=='upd') return false;
   if(ArraySafe($colinfo,'ins','')=='N' && $gpmode=='ins') return false;
   
   // so much for the exceptions, now just go for normal answer
   if ($gpmode=='ins') return DD_ColInsertsOK($colinfo);
   else return DD_ColUpdatesOK($colinfo);
}

/**
name:dd_tableref
parm:string table_id
returns:array Table_data_dictionary

Loads the data dictionary for a given table and returns a reference.
*/
function DD_TableRef($table_id) {
	if (!isset($GLOBALS["AG"]["tables"][$table_id])) {
      $file=fsDirTop()."generated/ddtable_".$table_id.".php";
      if(!file_exists($file)) {
         return array(); 
      }
      else {
         include($file);
      }
   }
   $retval=&$GLOBALS["AG"]["tables"][$table_id];
   return $retval;
}

// ------------------------------------------------------------------
// File system functions
// ------------------------------------------------------------------
/**
name:fsDirTop
returns:string Directory Path

This function returns the path to the application's
[[top directory]].  All other directories, such as the
[[lib directory]] and the [[application directory]] are all
directly below the [[top directory]].

The return value already contains a trailing slash.
*/
function fsDirTop() {
   return $GLOBALS['AG']['dirs']['root'];  
}

// ------------------------------------------------------------------
// Generic Language Extensions
// ------------------------------------------------------------------
/**
name:ArraySafe
parm:Array Candidate_Array
parm:string Array_Key
parm:any Default_value

Allows you to safely retrieve the value of an array by index value,
returning a [[Standard Default Value]] if the key does not exist.
*/
function ArraySafe(&$arr,$key,$value="") {
	if(isset($arr[$key])) return $arr[$key]; else return $value; 
}

// ------------------------------------------------------------------
// PHP functions that mimic Javascript DOM functions
// ------------------------------------------------------------------
/**
name:createElement
parm:type

Allows you to safely retrieve the value of an array by index value,
returning a [[Standard Default Value]] if the key does not exist.
*/
function createElement($type) {
	 return new androHElement($type);
}

class androHElement {
    function androHElement($type) {
        $this->type = $type;
        $this->children = array();
        $this->atts = array();
        $this->innerHTML = '';
    }
    
    function appendChild($object) {
        $this->children[] = $object;
    }
    
    function render($indent=0) {
        $hIndent = str_pad('',$indent*3);
        
        $retval="\n$hIndent<".$this->type;
        foreach($this->atts as $name=>$value) {
            $retval.=" $name = \"$value\"";
        }
        $retval.=">";
        foreach($this->children as $onechild) {
            $retval.=$onechild->render($indent+1);
        }
        $retval.=$this->innerHTML;
        $retval.="\n$hIndent</".$this->type.">";
        return $retval;
    }
}



?>
