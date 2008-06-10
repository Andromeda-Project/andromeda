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
//
// PURPOSE: Special page that just handles logins
//
class x_login extends x_table2 {
	var $PageSubtitle = "Some kind of login title";
	var $pmenu = array();
   
	function main() {
        $this->PageSubtitle = "Please Login";
        
        // KFD 3/6/08, changed login processing to st2login=1,
        //             its not a page anymore.
        hidden('st2login',1);          
        hidden('gp_page','');
        hidden('gp_posted','1');
        
        // Send these out so they are available after successful login
        $gpz=aFromGp('gpz_');
        foreach($gpz as $var=>$val) {
            hidden('gpz_'.$var,$val);
        }
      
		$loginUID = CleanGet("loginUID","",false);
		vgfSet("HTML_focus","loginUID");
      
        // EXPERIMENTAL.  DOING THIS FOR ONLY ONE CLIENT RIGHT NOW
        $hForgot=(vgaget('hfmode',false)==true)
             ? 'x_password.phtml'
             : 'index.php?gp_page=x_password';
      
        /**
        name:Replace Login Form
        
        You can replace the default login form by putting a file named
        "x_login_form.inc.html" into the [[Application Directory]].  
        */
        if(File_exists_incpath('x_login_form.inc.html')) {
            if (vgaGet('html_main')<>('html_skin_tc')) {
                include('x_login_form.inc.html');
                return;
            }
        }
      
		?>	

<!-- LOGIN HTML (BEGIN) -->
<br>
<br>
<table align="CENTER">
	<tr>
		<td class="logincaption">Login Name:</td>
		<td class="logininput">
			<input class="login" id="loginUID" name="loginUID" max_length="10" size="10" value="<?=$loginUID?>">
			</input></td>
	</tr>
	<tr>
		<td class="logincaption">Password:</td>
		<td class="logininput">
			<input class="login"  name="loginPWD" type="password" max_length="10" size="10">
			</input></td>
	</tr>
	<tr><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td class="logincaption">&nbsp;</td>
		<td class="logininput"><input class="login" type="submit" name="pushSave" value="Login" /></td>
	</tr>
</table>
<br>
<br>
<center>
<a href="<?=$hForgot?>">Forgotten Password and Change Password</a></center>
<!-- LOGIN HTML (END) -->		
		<?php
	}

    // ------------------------------------------------------------------
    // ------------------------------------------------------------------
    // Various helper functions
    function _MenuX($content,$newline,$class,$id='') {
        //$id=hTagParm('id',$id);
        if ($this->pmenu['MENU_TYPE']<>'TABLE') {
            return $newline.'<div class="'.$class.'">'.$content.'</div>';
        }
        else {
            return "$newline<tr><td id=\"$id\" class=\"$class\">"
                .$content."</td></tr>";
        }
    }

    function _MenuModule($content) {
        if(vgaGet('MENU_TAG_MODL','')<>'') {
            $mt=vgaGet('MENU_TAG_MODL','');
            return "<$mt>$content</$mt>"; 
        }
        else {
            return $this->_MenuX(
                $content,"\n\n",$this->pmenu['MENU_CLASS_MODL']
            );
        }
    }

    // Various helper functions
    function _MenuItem($content,$dest,$ins=false,$extra=array()) {
      // The parms are the table plus maybe the insert mode
		$parms = array('gp_page'=>$dest);
      $id='menu_'.$dest;
      if ($ins) {
         $parms['gp_mode']='ins';
      }
      else {
         $parms['gp_mode']='';
         $parms['gp_skey']='';
      }
      $parms=array_merge($parms,$extra);

      // Get the hyperlink with preceeding tick mark      
      $class = $this->pmenu['MENU_CLASS_ITEM'];
      $href 
         =$this->pmenu['MENU_TICK']
         .hLinkPostFromArray($class,$content,$parms);
      
      return $this->_MenuX($href,"\n",$this->pmenu['MENU_CLASS_ITEM'],$id);
    }
   
   // ------------------------------------------------------------------
   // LOGIN PROCESS
   // ------------------------------------------------------------------
	function Login_Process() {
      $arg2=$this->directlogin==true ? 'direct'  : '';
      
      // only process if user hit "post"
  		if (CleanGet('gp_posted','',false)=='') return;
      vgfSet('LoginAttemptOK',false);

      // Error title
      vgfSet('ERROR_TITLE','*');
      
		// If the user supplied a loginUID, this is a post and we
		// must process the request.
      $ale=vgaGet('login_errors',array());
      $app=$GLOBALS['AG']['application'];
      $em000=
         isset($ale['000']) ? $ale['000']
         : "That username/password combination did not work.  Please try again.";
      $em001=
         isset($ale['001']) ? $ale['001']
         : "That username/password combination did not work.  Please try again.";
      $em002=
         isset($ale['002']) ? $ale['002']
         : "That username/password combination did not work.  Please try again.";
      $em099=
         isset($ale['099']) ? $ale['099']
         : "That username/password combination did not work.  Please try again.";
		$terror = "";
		$uid = gp('loginUID');
      $uid = MakeUserID($uid);
      //$uid = str_replace('@','_',$uid);
      //$uid = str_replace('.','_',$uid);
		$pwd = CleanGet("loginPWD","",false);
		
		// First check, never allow the database server's superuser
		// account
		//
		if ($uid=="postgres") { 
			ErrorAdd($em000);
         if(vgfGet('loglogins',false)) {
            sysLog(LOG_WARNING,"Andromeda:$app:Bad login attempt as postgres");
            fwLogEntry('1011','Attempt login as postgres','',$arg2);
         }
			return;
		}
      $app=$GLOBALS['AG']['application'];
      if (substr($uid,0,strlen($app))==$app) {
			ErrorAdd($em001);
         if(vgfGet('loglogins',false)) {
            sysLog(LOG_WARNING,"Andromeda:$app:Bad login attempt as group role");
            fwLogEntry('1012','Attempt login as group role',$uid,$arg2);
         }
			return;
      }

		// Begin with a connection attempt.  
		// on fail, otherwise continue
		$tcs = @SQL_CONN($uid,$pwd);
		if ($tcs===false) {
			ErrorAdd($em099);
         if(vgfGet('loglogins',false)) {
            sysLog(LOG_NOTICE,"Andromeda:$app:Bad login attempt server rejected");
            fwLogEntry('1013','Server rejected username/password',$uid,$arg2);
         }
			return;
		}
		else {
			SQL_CONNCLOSE($tcs);
		}

		// The rest of this routine uses an admin connection.  If we
      // have an error, we must close the connection before returning!
      //    ...yes, yes, that's bad form, all complaints to /dev/null
		//
      if(vgfGet('loglogins',false)) fwLogEntry('1010','Login OK',$uid,$arg2);
      scDBConn_Push();
      

      // See if they are a root user.  If not, do they have an
      //  active account?
      $root = false;
      $admin= false;
      $group_id_eff='';
      $results=SQL("
         Select oid
           FROM pg_roles   
          WHERE rolname = CAST('$uid' as name)
            AND rolsuper= true"
      );
      $cr=SQL_NUMROWS($results);
      if($cr<>0) {
         $root=true;
      }
      else {
         $results = SQL(
            "Select * from users WHERE LOWER(user_id)='$uid'" 
            ."AND (user_disabled<>'Y' or user_disabled IS NULL)"
         );
         $cr = SQL_NUMROWS($results);
         if ($cr==0) {
            scDBConn_Pop();
            ErrorAdd($em002);
            sysLog(LOG_WARNING,"Andromeda:$app:Bad login attempt code 002");
            return;
         }
         else {
            $userinfo=SQL_Fetch_Array($results);
            $group_id_eff=$userinfo['group_id_eff'];
         }
      }

		// Flag if the user is an administrator
      if($root==true) {
         $admin=true;
      }
      else {
         $results = SQL(
            "select count(*) as admin from usersxgroups "
            ."where user_id='$uid' and group_id ='$app"."_admin'"
         );
         $row = SQL_FETCH_ARRAY($results);
         $admin = intval($row["admin"])>0 ? true : false;
      }
		
		// Get the users' groups
		$groups="";
      if ($root) {
         $results=SQL("
            select group_id 
              from zdd.groups 
             where COALESCE(grouplist,'')=''"
         );
      }
      else {
         $results=SQL("select group_id from usersxgroups WHERE LOWER(user_id)='$uid'");
      }
		while ($row = SQL_FETCH_ARRAY($results)) {
			$groups.=ListDelim($groups)."'".trim($row["group_id"])."'";
		}
		//scDBConn_Pop();
		
      // We have a successful login.  If somebody else was already
      // logged in, we need to wipe out that person's session.  But
      // don't do this if there was an anonymous login.
      if(LoggedIn()) {
         $uid_previous=SessionGet('UID');
         if($uid<>$uid_previous) {
            //Session_Destroy();
            SessionReset();
            //Index_Hidden_Session_Start(false);
         }
      }

      // We know who they are and that they can connect, 
      // see if there is any app-specific confirmation required
      //
      if(function_exists('app_login_process')) {
         //echo "Calling the process now";
         if(!app_login_process($uid,$pwd,$admin,$groups))
         return;
      }

      // Protect the session from hijacking, generate a new ID
      Session_regenerate_id();

		// We now have a successful connection, set some
		// flags and lets go
		//
      vgfSet('LoginAttemptOK',true);
		SessionSet("UID",$uid);
		SessionSet("PWD",$pwd);
		SessionSet("ADMIN",$admin);
      SessionSet("ROOT",$root);
      SessionSet("GROUP_ID_EFF",$group_id_eff);
		SessionSet("groups",$groups);
      if(gp('gpz_page')=='') {
         gpSet('gp_page','');
      }
      $GLOBALS['session_st'] = 'N';   // for "N"ormal
		
		// -------------------------------------------------------------------
		// We are about to make the menu.  Before doing so, see if there 
      // are any variables set for the menu layout.  Set defaults and then
      // load from database.
		//
      $this->pmenu = array(
         'MENU_TYPE'=>vgaGet('MENU_TYPE','div')
         ,'MENU_CLASS_MODL'=>vgaGet('MENU_CLASS_MODL','modulename')
         ,'MENU_CLASS_ITEM'=>vgaGet('MENU_CLASS_ITEM','menuentry')
         ,'MENU_TICK'=>vgaGET('MENU_TICK',' - ')
      );
      //$sql = "SELECT * from variables WHERE variable like 'MENU%'";
      //$dbres = SQL($sql);
      //while ($row = SQL_FETCH_ARRAY($dbres)) {
      //   $this->pmenu[trim($row['variable'])]=trim($row['variable_value']);
      //}

		// -------------------------------------------------------------------
      // KFD 10/28/06, Modified to examine "nomenu" instead of permsel
      //   pulls all tables user has nomenu='N'.  The basic idea is
      //   to remove from $AGMENU the stuff they don't see
		//
      // GET AGMENU
		$AGMENU=array();  // avoid compiler warning, populated next line
		include("ddmodules.php");
      
      // Pull distinct modules person has any menu options in.
      $sq="SELECT DISTINCT module
             FROM zdd.perm_tabs 
            WHERE nomenu='N'
              AND group_id iN ($groups)";
      $modules=SQL_AllRows($sq,'module');
      $AGkeys=array_keys($AGMENU);
      foreach($AGkeys as $AGkey) {
         if(!isset($modules[$AGkey])) {
            unset($AGMENU[$AGkey]);
         }
      }
      
      // Now recurse the remaining modules and do the same trick
      // for each one, removing the tables that don't exist
      foreach($AGMENU as $module=>$moduleinfo) {
         $sq="SELECT DISTINCT table_id
                FROM zdd.perm_tabs 
               WHERE nomenu='N'
                 AND module = '$module'
                 AND group_id iN ($groups)";
         $tables=SQL_AllRows($sq,'table_id');
         $tkeys =array_keys($moduleinfo['items']);
         foreach($tkeys as $tkey) {
            if(!isset($tables[$tkey])) {
               unset($AGMENU[$module]['items'][$tkey]);
            }
         }
      }
      
      // KFD 12/18/06.  Put all table permissions into session
      $table_perms=SQL_AllRows(
         "Select distinct table_id FROM zdd.perm_tabs
           WHERE group_id IN ($groups)
             AND nomenu='N'"
         ,'table_id'
             
      );
      SessionSet('TABLEPERMSMENU',array_keys($table_perms));
      
      $table_perms=SQL_AllRows(
         "Select distinct table_id FROM zdd.perm_tabs
           WHERE group_id IN ($groups)
             AND permsel='Y'"
         ,'table_id'
             
      );
      SessionSet('TABLEPERMSSEL',array_keys($table_perms));
      
      $table_perms=SQL_AllRows(
         "Select distinct table_id FROM zdd.perm_tabs
           WHERE group_id IN ($groups)
             AND permins='Y'"
         ,'table_id'
             
      );
      SessionSet('TABLEPERMSINS',array_keys($table_perms));
      
      $table_perms=SQL_AllRows(
         "Select distinct table_id FROM zdd.perm_tabs
           WHERE group_id IN ($groups)
             AND permupd='Y'"
         ,'table_id'
             
      );
      SessionSet('TABLEPERMSUPD',array_keys($table_perms));
      
      $table_perms=SQL_AllRows(
         "Select distinct table_id FROM zdd.perm_tabs
           WHERE group_id IN ($groups)
             AND permdel='Y'"
         ,'table_id'
             
      );
      SessionSet('TABLEPERMSDEL',array_keys($table_perms));
      //echo "<div style='background-color:white'>";
      //echo "$uid $groups $group_id_eff";
      //hprint_r(SessionGet('TABLEPERMSMENU'));
      //hprint_r(SessionGet('TABLEPERMSSEL'));
      //echo "</div>";
      
      
      // KFD 7/9/07, we always use joomla templates now, don't need
      // options to turn them off
      //if(defined('_ANDROMEDA_JOOMLA')) {
         // In a hybrid situation, put the menu into the session
         SessionSet('AGMENU',$AGMENU);
      //}
      $HTML_Menu="";
      $WML_Menu="";
      foreach ($AGMENU as $key=>$module) {
         //if($key=="datadict") continue;
         //if($key=="sysref")   continue;
         $HTML_Module="";
         $WML_Module="";
         foreach($module["items"] as $itemname=>$item) {
            if (!isset($item["mode"])) { $item["mode"]="normal"; }
            switch ($item["mode"]) {
               case "normal":
                  $ins=false;
                  $extra=array();
                  if($item['menu_parms']<>'') {
                     $aextras=explode('&',$item['menu_parms']);
                     foreach($aextras as $aextra) {
                        list($var,$value)=explode("=",$aextra);
                        $extra[$var]=$value;
                     }
                  }
                  $HTML_Module.=$this->_MenuItem(
                     $item['description'],$itemname,$ins,$extra
                  );
                  $WML_Module.="<div>";
                  $WML_Module.=hLink(
                     '',$item['description'],'?gp_page='.$itemname
                  );
                  $WML_Module.="</div>";
                  break;
               case "ins":
                  //if ($admin || isset($tables_ins[$item["name"]]))  {
                     $HTML_Module.=$this->_MenuItem(
                        $item['description'],$itemname,true
                     );
                  //}
                  break;
                     /*
                     $HTML_Module.=
                        "\n<font class=\"tablename\">- <a href=\"index.php?gp_page=".$itemname."\">".
                        $item["description"]."</a></font><br />";
                     */
               }
         }
         
         // the module is defined AFTER its contents so it can be
         // left off if it has no entries
         if ($HTML_Module!="") {
            $HTML_Menu.=$this->_MenuModule($module['description']);
            $HTML_Menu.=$HTML_Module;
         }
         if ($WML_Module!="") {
            $WML_Menu.="<div><b>".$module['description']."</b></div>";
            $WML_Menu.=$WML_Module;
         }
      }
      
      DynamicSave("menu_".$uid.".php",$HTML_Menu);
      DynamicSave("menu_wml_".$uid.".php",$WML_Menu);
      

		// -------------------------------------------------------------------
      // Fetch and cache user preferences
      if(vgaGet('member_profiles')) {
         cacheMember_Profiles();
      }
		
		// -------------------------------------------------------------------
		// Now find the user's table permissions more precisely table by table
      $sql=
			"select p.table_id,
				max(case when p.permins='Y' then 1 else 0 end) as permins,
				max(case when p.permupd='Y' then 1 else 0 end) as permupd,
				max(case when p.permdel='Y' then 1 else 0 end) as permdel,
				max(case when p.permsel='Y' then 1 else 0 end) as permsel
				from zdd.perm_tabs  P
				WHERE group_id in ($groups)
				GROUP BY p.table_id";
      //echo $sql;
		$results = SQL($sql);
			
		$HTML_Perms="<?php\n\$table_perms = array();\n";
		while ($row = SQL_FETCH_ARRAY($results)) {
			$tn = $row["table_id"];
			$ti = $row["permins"];
			$tu = $row["permupd"];
			$td = $row["permdel"];
			$ts = $row["permsel"];
			$HTML_Perms .= "\$table_perms[\"$tn\"]=array(\"ins\"=>$ti,\"upd\"=>$tu,\"del\"=>$td,\"sel\"=>$ts);\n";
		}
		$HTML_Perms.="?>\n";
		DynamicSave("perms_".$uid.".php",$HTML_Perms);

      /* October 28, 2006, KFD.  Rem'd this all out, column and row security
         made this irrelevant
		// -------------------------------------------------------------------
		// Find out if this user has any UID Columns, columns that create
		// filters on the user's UID
		$sql = "Select column_id FROM groupuids WHERE group_id IN ($groups)";
		//echo $sql;
		$results = SQL($sql);
		$groupuids = array();
		while ($row = SQL_FETCH_ARRAY($results)) {
			//echo "Found this one".$row["column_id"];
			$groupuids[$row["column_id"]] = $row["column_id"];
		}
		SessionSet("groupuids",$groupuids);
      */
		
		scDBConn_Pop();
		return;
	}
   

   function GenerateMenu($AGMENU,$admin,$tables_sel,$tables_ins) {   
   }

}
?>
