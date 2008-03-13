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

INVENTORY OF framework gp variables:

* gp_page     means page processing, and is the default action
* st2logout   command to logout
* st2login    command to login

*/

// ==================================================================
// >>> 
// >>> Make INI file settings here
// >>> 
// ==================================================================
ini_set("allow_url_open",false);
ini_set("error_reporting",E_ALL);
ini_set("display_errors",true);
ini_set("log_errors",true);
ini_set("short_tag_open",true);

// ==================================================================
// >>> 
// >>> Start session
// >>> 
// ==================================================================
session_start();
header("Cache-control: private");  

// ==================================================================
// >>> 
// >>> Find file of application information
// >>> 
// ==================================================================
@include("appinfo.php");
if (!isset($AG['application'])) {
    $AG['application'] = 'andro';
    $AG['app_desc'] = 'Unknown';
}

// ==================================================================
// >>> 
// >>> Copy the $_POST array and overlay with $_GET
// >>> 
// ==================================================================
$AG['clean']= array();
$AG['clean']['gp_page'] = '';
$AG['clean']['gp_action'] = '';
$AG['clean'] = array_merge($AG['clean'],$_POST,$_GET); 

// >>> 
// >>> Restore the context (taken from g/p variables)
// >>> 
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

// ==================================================================
// >>> 
// >>> Load the libraries
// >>> 
// ==================================================================
include_once('androLib.php');
$x=fsDirTop().'application/applib.php';
if (file_exists($x)) {
  include_once($x);
}

// ==================================================================
// >>> 
// >>> Up the session hit counter
// >>> 
// ==================================================================
SessionSet('count',SessionGet('count')+1);

// QUESTIONABLE.  Added long time ago and not used by any
//                live systems.  commented kfd 3/6/08, delete
//                completely after july 08
//vgfSet('cache_pkey',array('member_profiles'));

// ==================================================================
// >>> 
// >>> Redirections.  QUESTIONABLE PLACEMENT AND APPROACH
// >>>                Almost definitely should be moved into
// >>>                index_hidden_page
// >>> 
// ==================================================================
// The parameter 'gp_pageal' means page to go to after a login.
// Save it now.  Used originally for project cme.
if(gpExists('gp_pageal')) {
  SessionSet('clean',array('gp_page'=>gp('gp_pageal')));
}
   
// The parameter 'gp_aftersave' means go to a page after saving
// information somewhere else.  The program processPost will look
// for this after saving and do a gpSet() to this value.
if(gpExists('gp_aftersave')) {
  SessionSet('gp_aftersave',gp('gp_aftersave'));
}

// ==================================================================
// >>> 
// >>> Determine the User ID, or UID
// >>> 
// ==================================================================

// A logout command comes first
if(gp('st2logout')<>'') {
   SessionReset();
}

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
// KFD 3/6/08 Changed login processing from page x_login to
//            the st2login command
if (gp('st2login')==1) {
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


// ==================================================================
// >>> 
// >>> Command line programs now exit after loading framework
// >>> 
// ==================================================================
if(!isset($_SERVER['HTTP_HOST'])) return;
if(isset($force_cli))   return;
if(isset($header_mode)) return;

// ==================================================================
// >>> 
// >>> Dispatching, pass execution to the relevant handler
// >>> 
// ==================================================================

// If making an ajax call and session time out, send to logout
if(Count(SessionGet('clean',array()))>0 && gpExists('ajxBUFFER')) {
    echo '_redirect|?st2logout=1';
    return;
}


// If gp_echo, just echo back.  This is for debugging of course.
if(gpExists('gp_echo')) {
    echo "echo|".gp('gp_echo');
    return;
}

// Everything after assumes we need a database connection
scDBConn_Push();
  
// KFD 6/7/07, make any calls just after db connection
if(function_exists('app_after_db_connect')) {
    app_after_db_connect();
}

// Only one path can be chosen, and each path is completely
// responsible for returning all headers, HTML or anything
// else it wants to send back.
if(gpExists('gp_command')) index_hidden_command();
if(gp('ajxFETCH')   =='1') index_hidden_ajxFETCH();
if(gp('ajxfSELECT')  <>'') index_hidden_ajxfSELECT();
if(gp('ajxc1table')  <>'') index_hidden_ajx1ctable();
if(gp('gp_function') <>'') index_hidden_function();
if(gp('gp_dropdown') <>'') index_hidden_dropdown();
if(gp('gp_fetchrow') <>'') index_hidden_fetchrow();
if(gp('gp_sql')      <>'') index_hidden_sql();
if(gp('gp_ajaxsql')  <>'') index_hidden_ajaxsql();
else                       index_hidden_page();


// All finished, disconnect and leave. 
scDBConn_Pop();
return;
// ==================================================================
// DISPATCH DESTINATIONS
// ==================================================================
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
   $sessok=!LoggedIn() ? false : true;

    // KFD 3/6/08, moved here from the main stream of index_hidden
    //             because these are relevant only to page processing
    if(gpExists('x_module')) {
       SessionSet('AGMENU_MODULE',gp('x_module'));
    }
    elseif(vgaGet('nomodule')<>'' && SessionGet('AGMENU_MODULE')=='') {
       SessionSet('AGMENU_MODULE',vgaGet('nomodule'));
    }
   
   
    // If the search flag is set, we need to know what class for this
    // application handles searchs
    if(gpExists('gp_search')) {
        gpSet('gp_page',vgaGet('SEARCH_CLASS'));
    }
   
   
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
       if ($obj_page->flag_buffer) { ob_start(); }
       $obj_page->main($gp_page);
       if ($obj_page->flag_buffer) {
               vgfSet("HTML",ob_get_clean());
               ob_end_clean();
       }
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
                   // DO 2-1-2008 Added to ignore SVN directory
                   if ($filename=='.svn') continue;
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

?>
