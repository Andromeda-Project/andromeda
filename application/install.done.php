<?php
class install extends x_table2 {
   function custom_construct() {
      if(gp('gp_build')==1) $this->flag_buffer=false;   
   }

   function main() {
      // When building, just do this, then leave
      if(gp('gp_build')==1) {
         SessionSet('UID',SessionGet('xUID'));
         SessionSet('PWD',SessionGet('xPWD'));
         ob_start();
         $GLOBALS["parm"] = array(
            "DBSERVER_URL"=>"localhost"
            ,"UID"=>SessionGet('UID')
            ,"DIR_PUBLIC"=>realpath($GLOBALS['AG']['dirs']['root'].'..')
            ,"DIR_PUBLIC_APP"=>"andro"
            ,"APP"=>"andro"
            ,"APPDSC"=>"Andromeda Node Manager"
            ,"SPEC_BOOT"=>"AndroDBB"
            ,"SPEC_LIB"=>"andro_universal"
            ,"SPEC_LIST"=>"andro"
         );
         include("AndroBuild.php");
         SessionSet('UID','');
         SessionSet('PWD','');
         echo  ob_get_clean();
         return;
      }
	   
	   
      ob_start();
      ?>
      td.bad  { color: red ; font-weight: bolder; }
      td.good { color: green; }
      
      div.config {
         border: 1px solid gray;
         background-color: #C0E0FF; 
         margin: 5px 20px 5px 20px;
         padding: 5px;
         white-space: pre;
         font-family: monospace;
      }
      textarea.config {
         background-color: lightsteelblue; 
         margin: 5px 20px 5px 20px;
         padding: 5px;
         font-family: monospace;
      }         
      div.cli {
         margin: 1em 20px 1em 20px;
         padding: 5px;
         border: 1px solid gray;
         background-color: #404040;
         color: orange;
         white-space: pre;
         font-weight: bolder;
      }
      <?php
      elementAdd('styles',ob_get_clean());
  
      // Define the meta data for the steps
      $steps=array(
         array(false,'PostgreSQL support in PHP')
         ,array(false,'Connect as superuser to PostgreSQL')         
         ,array(false,'Confirm PostgreSQL >= 8.1')         
         ,array(false,'Create Node Manager Database')         
         ,array(false,'Initialize Node Manager Database')         
         ,array(false,'Final Notes')         
      );
      
      // Begin by assuming we must start here
      $current_step = 0;   
      
      // Call the routine that figures out what step we are on
      $current_step = $this->DoTests($steps);
        
      ?>
      <h1>New Install Detected</h1>
      <p>This looks like a new Andromeda node.  This wizard will
         help you to install Andromeda and get it running.

      <h2>Status Checklist</h2>
      
      <p>The installation process consists of completing each of the
         items here.  Some of the items can be checked automatically,
         and some will require your feedback.
      </p>
      
      <br>
      <table style='border: 1px solid black'>
         <tr>
           <td class="dhead">Complete
           <td class="dhead">Step
           <td class="dhead">Rollback
      <?php
      foreach($steps as $index=>$step) {
         echo "<tr><td class='".($step[0] ? 'good' : 'bad')."'>"
            .($step[0] ? 'YES' : 'NO')
            ."<td>".$step[1]
            ."<td>";
         if($index < $current_step) {
            echo "<a href='?stepreset=".$index."'>Rollback</a>";
         }
      }
      ?>
      </table>

      <br>
      <h2>Next Step: <?=$steps[$current_step][1]?></h2>
      <?php
      if($this->error) {
         echo "<div class='errorbox'>".$this->error."</div>";      
      }
      if(    !$steps[0][0]) $this->PGPhp();
      elseif(!$steps[1][0]) $this->PGConnect();
      elseif(!$steps[2][0]) $this->PG81();
      elseif(!$steps[3][0]) $this->NMCreate();
      elseif(!$steps[4][0]) $this->NMInit();
      elseif(!$steps[5][0]) $this->Final0();
      ?>
      <br><br>
      <hr>
      <h3>A Note About Install Mode</h3>
      <p>Install mode is triggered by the presence of the file 
         'install.php' in the application directory:
        
      <p><?=$GLOBALS['AG']['dirs']['application']?>
      
      <p>If you are
         an expert and wish to bypass this wizard, then simply remove
         or rename that file and Andromeda will attempt to operate
         normally.
      <?php
   }
      
   // ---------------------------------------------------
   // Tell them to compile postgres support
   // ---------------------------------------------------
   function DoTests(&$steps) {
      $this->error='';
      
      // If they manually forced a rollback to an earlier step,
      //  we will catch it below
      $rb=gp('stepreset',-1);
   
      // look for flags that indicate manual approval of steps
      if(gp('pgconfig')==1) sessionSet('pgconfig',true);
      if(gp('pgsuper') ==1) sessionSet('pgsuper' ,true);
      
      // If they provided credentials, try to post them
      if(gpExists('loginUID')) {
         if(substr(strtolower(gp('loginUID')),0,5)=='andro') {
            ErrorAdd("Superuser account may not begin with 'andro'");
         }
         else {
            SessionSet('xUID',gp('loginUID'));
            SessionSet('xPWD',gp('loginPWD'));
         }
      }
   
      $finished=false;
      foreach($steps as $current_step=>$step) {
         switch($current_step) {
            case 0:
               if(!function_exists('pg_connect')) $finished=true;
               break;
            case 1:
               // If forcing rollback to here, clear user credentials
               if($rb==1) {
                  SessionUnset('xUID'); SessionUnSet('xPWD');
               }

               // Test if they gave us uid/pwd and if it works
               if(SessionGet('xUID')=='') $finished=true;
               else {
                  $cs=SQL_ConnString(
                     SessionGet('xUID')
                     ,SessionGet('xPWD')
                     ,'postgres'
                  );
                  $this->dbx=@pg_connect($cs);
                  if(!$this->dbx) { 
                     $this->error="Could Not Connect with that Username/Password"; 
                     $finished=true;
                  }
               }
               break;
            case 2:
               // Since we got a connection, try to get versions 
               $res=SQL2("Select version()",$this->dbx);
               $row= SQL_Fetch_Array($res);
               $x=explode(' ',$row['version']);
               $this->pgversion=$x[0].' '.$x[1];
               $vers=explode('.',$x[1]);
               $vers=$vers[0].'.'.$vers[1];
               if($vers < 8.1) $finished=true;
               break;
            case 3:
               if ($rb==3) { $this->andro=1;$finished=true; break; }
               $cs=SQL_ConnString(
                  SessionGet('xUID')
                  ,SessionGet('xPWD')
                  ,'andro'
               );
               $this->dba=@pg_connect($cs);
               if(!$this->dba) {
                  $finished=true;
                  $this->andro=0;               
               }
               else {
                  pg_close($this->dba);
                  $file=$GLOBALS['AG']['dirs']['generated'].'ddmodules.php';
                  if(!file_exists($file)) {
                     $finished=true;
                     $this->andro=1;
                  }
               }
               break;
            case 4:
               // Initialize the node manager
               SessionSet('UID',SessionGet('xUID'));
               SessionSet('PWD',SessionGet('xPWD'));
               scDBConn_Push();
               $dir_pub=realpath(dirname(__FILE__).'/../..');
               if(strpos(ArraySafe($_ENV,'OS',''),'indows')!==false) {
                  $dir_pub = str_replace("\\","\\\\",$dir_pub);
               }
               $row=array(
                  'webpath'=>'DEFAULT'
                  ,'dir_pub'=>$dir_pub
                  ,'description'=>'Default Web Path'
               );
               $table_dd=dd_TableRef('webpaths');
               SQLX_UpdateorInsert($table_dd,$row); 
               
               $table_dd=dd_TableRef('nodes');
               $row=array(
                  'node'=>'DHOST2'
                  ,'description'=>"Andromeda Master Node"
                  ,'node_url'=>'dhost2.secdat.com'
               );
               SQLX_UpdateorInsert($table_dd,$row); 
               $row=array(
                  'node'=>'LOCAL'
                  ,'description'=>"Local Node"
                  ,'node_url'=>'localhost'
               );
               SQLX_UpdateorInsert($table_dd,$row); 

               $table_dd=dd_TableRef('applications');
               $row=array(
                  'application'=>'andro'
                  ,'description'=>"Andromeda Node Manager"
                  ,'appspec'=>'andro'
                  ,'node'=>'LOCAL'
                  ,'webpath'=>'DEFAULT'
               );
               SQLX_UpdateorInsert($table_dd,$row); 

               
               scDBConn_Pop();
               SessionSet('UID','andro');
               SessionSet('PWD','andro');
               break;
            case 5:
               break;
            default:
               $finished=true;
         }

         // if we are clear, stop now
         if($finished) break;
      }
      
      for($x=0;$x<$current_step;$x++) {
         $steps[$x][0]=true;      
      }
      
      return $current_step;
   }      
   
   // ---------------------------------------------------
   // Tell them to compile postgres support
   // ---------------------------------------------------
   function PGPhp() {
      ?>
      <div class="errorbox">
      Support for PostgreSQL must be compiled into PHP.
      </div>
      <p>Andromeda requires PostgreSQL support to be compiled into
         PHP.  We detected that this is not the case, because the
         function "pg_connect" does not exist.   You can remedy this
         situation by doing these steps:
         
      <p><b>On Unbuntu Systems</b> install the package
          php5-pgsql (as of 4/4/07).
      </p>
      <p><b>On a Gentoo System</b> set the 'postgres' USE flag before
         compiling PHP, and be sure to restart apache afterwards.
         (as of 4/4/07).
      <p><b>On Windows with XAMPP</b> edit the file ...\xampp\apache\bin\php.ini
         and unrem the line that says "extension=php_pgsql.dll".  Be careful,
         though, there is a line for php_<b>pdo</b>_pgsql.dll, that is not
         the line we want. (as of 4/4/07).
      </p>
      <li><a href="index.php">Refresh This page</a>
      </ul>
      <?php
   }
   
   // ---------------------------------------------------
   // Tell them to make the config file changes
   // ---------------------------------------------------
   function PGConfig() {
      ?>
      <p>Andromeda requires a few changes to the default 
         PostgreSQL configuration files.  These changes
         are one-time only changes, once they are made you can
         completely manage the security of your databases
         without needing a PostgreSQl restart.
      
      <h3>Summary</h3>   
      <p>The summary of changes is here:
      <ul>
      <li>Changes to pg_hba.conf
      <li>Changes to pg_ident.conf
      <li>Changes to postgresql.conf
      </ul>
      
      <h3>Notes for Different Distributions</h3>
      <p><b>Gentoo</b>.  Gentoo has the advantage of keeping all of
         their configuration files in /var/lib/postgresql/, and all of
         these files will exist after you have completed the install.
         You can make the changes directly to the files in that
         directory.
      </p>
         
      <p><b>Debian/Ubuntu</b>.  Ubuntu 6.10 puts the configuration
         files into /etc/postgresql/8.1/main, you can make changes
         directly to the files in that directory.
      </p>
         
      <p><b>Windows</b>.  The default Postgres installer puts menu
         items onto the start menu that give you direct access to the
         configuration files.
      </p>
         
      <h3>Details</h3>
      The following lines should appear in your pg_hba.conf file:
      
      <textarea class='config' cols=70 rows=40># pg_hba.conf: Andromeda version
# --------------------------------------------------
# REM out all other lines in pg_hba.conf and replace
# them with these four lines.
#
# These comments modified 4/23/07 by KFD
# --------------------------------------------------
      
# LINUX ONLY.  This allows some accounts to use the
#              pg_ident.conf to gain trusted access
#
local   all         postgres    ident andromeda

# WINDOWS ONLY.  DEV MACHINES ONLY.  Do not put this
# line in on a production server, it lets any local
# user connect to the 
#
host    all         postgres  127.0.0.1/32  password
local   all         postgres                password


#  LINUX:   Put these two lines in on Linux systems
#  WINDOWS: Put these two lines in on Windows systems
#
#  Any member of role "root" can connect to any database, A "root"
#  user is an Andromeda superuser.
#
host    all         +root    127.0.0.1/32  password
local   all         +root                  password

#  LINUX:   Put these four lines in on Linux systems
#  WINDOWS: Put these four lines in on Windows systems
#
#  Normal user access.  Allows any user who is in the self-named group
#  connect to a database.  
#
hsot    samename    all  127.0.0.1/32  password
local   samename    all                password
host    samegroup   all  127.0.0.1/32  password
local   samegroup   all                password
</textarea>

      <br><br/>
      Changes to make to pg_ident.conf are listed here.  Do these only
      if you are running on Linux, they are not yet supported on Windows.
      <br><br/>
      
      <textarea class='config' cols=70 rows=11># pg_ident.conf: Andromeda version
# --------------------------------------------------
# These two lines allow the Unix root user and the 
# postgres user to connect in w/o password.
#
# LINUX ONLY!  You do not need this file in Windows.
#
# --------------------------------------------------
# MAPNAME     IDENT-USERNAME    PG-USERNAME
andromeda     postgres          postgres
andromeda     root              postgres</textarea>
      
      <br><br/>
      Changes to postgresql.conf are here:
      <br><br/>
      
      <textarea class='config' cols=70 rows=7># postgresql.conf: Andromeda Version
# -------------------------------------------------
# This allows apache to connect through TCP/IP
#
listen_addresses='localhost'
port = 5432
max_connections = 100</textarea>
      
      
      <h3>When You Have Finished</h3>
      <p>When you have finished making the changes, restart the
         PostgreSQL server.  After doing so, you can test your
         configuration to make sure you can connect.  On a linux system
         type in the following command (on Windows try connecting through
            pgAdmin3).
      <div class='cli'># psql -U postgres</div>

      <br>
      <p>If you can connect ok:
      <p><a href="?pgconfig=1">Click Here To Continue Install</a>.
      <?php      
   }

   // ---------------------------------------------------
   // Make a superuser
   // ---------------------------------------------------
   function PGSuper() {
      ?>
      <h3>Create Your Superuser</h3>
      <p>In this step you must create a database superuser and
         create an empty 'andro' database.
      
      <p>Go into pgadmin3 (or pgsql at the linux CLI) and enter
      these SQL commands.  Remember to substitute actual values for
      USER_NAME and PASSWORD:
      </p>
      
      <textarea cols=70 rows=4 class='config'>create role root nologin;
create user USER_NAME superuser password 'PASSWORD';
grant root to USER_NAME;
create database andro;</textarea>

      <h3>File Permissions (Linux Only)</h3>
      
      <p><b>For Linux Only.</b>  Change the ownership and priveleges of the 
         Andromeda files so that they are writable by the apache 
         process.  On gentoo apache runs as "apache", on Ubuntu it
         runs by default as "www-data", and on Fedora Core 6 it runs
         as "daemon".
<div class='cli'># chown root:APACHE /path/to/andro -R
# chmod g+rw /path/to/andro -R
</div>

      <br>
      
      <h3>Moving On...</h3>
      <p>Once these steps are complete:
      <p><a href="?pgsuper=1">Click Here To Continue Install</a>.
      <?php      
   }
   // ---------------------------------------------------
   // Get superuser credentials
   // ---------------------------------------------------
   function PGConnect() {
      ?>
      <p>In this step we need to know the username and password
         of the superuser that you just created.
      <p><b>Please Note: the superuser ID may not begin with 'andro'.</b></p>
      <form action="index.php" method='post'>
      <table>
         <tr><td>Superuser Account:
            <td><input name="loginUID">
         <tr><td>Password
            <td><input type="password" name="loginPWD">
      </table>
      <br>
      <input type="submit" value="Attempt Connection">
      </form>
      <?php      
   }   

   function PG81()      {
      ?>
      <div class="errorbox">PostgreSQL must be version 8.1 or better</div>
      
      <p>The install program has detected <?=$this->pgversion?>, the minimum
         version required to run Andromeda is 8.1.  Please upgrade to version
         8.1 or better and then rerun the installation.
      <?php
   }
   function NMCreate()  { 
      if($this->andro==0) {
         ?>
         <p>The 'andro' database does not exist.  Please 
            <a href="?stepreset=2">RollBack to Superuser/andro Instructions</a>
            and follow the instructions for creating the Node Manager
            database.
         <?php      
      }
      else {
         ?>
         <p>A complete Node Manager has not yet been built.  Please click on
         the link below to build the complete Node Manager.
         
         <p>If you have just installed these files, you will need to 
            change the ownership of the 'andro' directory to the apache
            user, which is 'apache' on gentoo and 'www-data' on Ubuntu.
            
         <p>If after you have changed the ownership, and the program 
            errors out complaining about file permissions, then go root
            and execute the program /tmp/andro_fix_perms.sh.
         <?php
      }
      ?>
      <br>
      <br>
      <a href="javascript:Popup('?gp_page=install&gp_build=1')">
      Build Database Now</a>
      <br>
      <br>
      After the build has finished successfully
      <a href="index.php">Refresh This Page.</a>
      <?php
   }
   
   function NMInit()    { 
      // There is really nothing to tell them here, this just happens
   }
   
   function Final0()     { 
      ?>
      <p>Congratulations, Andromeda is now installed.  For any additional
      information, visit 
      <a href="http://www.secdat.com">http://www.secdat.com</a> and
      follow the links to the Andromeda site.
      
      <p>To continue, <a href="?gp_install=finish">Click Here</a> to rename
         the file 'install.php' to 'install.done.php' and go to the
         Node Manager login screen.
      <?php
   }
}
?>
