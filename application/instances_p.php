<?php
class instances_p extends x_table2 {
   function custom_construct() {
      if(gp('gp_posted')==1){
         $this->flag_buffer=false;
         $this->caption = "Run another build";
      }
   }
   
   function main() {
      $sApp =SQLFC(gp('gp_app'));
      $sInst=SQLFC(gp('gp_inst'));
      $hApp =hSanitize(gp('gp_app'));
      $hInst=hSanitize(gp('gp_inst'));
      $rows=SQL_AllRows(
         "SELECT * from instances 
           where application=$sApp AND instance=$sInst"
      );
      if(count($rows)<>1) {
         ?>
         <div class="errorbox">Incorrect call to instance processing.</div>
         <?php
         return;
      }
      $row=$rows[0];
      
      // Maybe we are on processing branch
      if(gp('gp_posted')==1) {
         $this->Process($rows[0]);
         return;
      }
      
      // Get the current version, and get the latest version available
      $hVer=hSanitize(trim($row['version']));
      $verx=SQL_OneValue("mv",
         "Select max(version) as mv from appversions
           WHERE application=$sApp"
      );
      if(is_null($verx)) $verx='';
      
      
      ?>
      <h1>Instance Upgrade</h1>
      <p>Application: <?=$hApp?>   </p>
      <p>Instance: <?=$hInst?>     </p>
      <p>Current Version: <?=($hVer=='' ? '-none-' : $hVer)?> </p>
      <p>Latest Version Available: <?=($verx=='' ? '-none-' : $verx)?> </p>
      <p>&nbsp;</p>
      <p>
      <?php
      if($verx=='') {
         ?>
         <b>No official versions are available.</b>  An instance can only
            be upgraded when an official version is available.  You may
            download release code for this application, or you may
            generate files out of your development code.
           </p>
         <?php
         return;
      }
      else {
         $caption=$hVer=='' ? 'Build as ' : 'Upgrade To';
         echo hLinkPopup(
            ''
            ,$caption.' version '.$verx
            ,array(
               'gp_app'=>gp('gp_app')
               ,'gp_inst'=>gp('gp_inst')
               ,'gp_posted'=>1
               ,'gp_page'=>'instances_p2'
               ,'gp_out'=>'none'
               ,'gp_ver'=>$verx
            )
         );
      }
   }
   
   // ---------------------------------------------------------------
   // Processing Functions Go Here
   // ---------------------------------------------------------------
   function Process($row) {
       ?>
       <h2>Coding Error</h2>
       
       <p>The instance build program is supposed to route through
          to the program instances_p2.  If you see this message
          then the HTTP POST did not go through correctly.
       
       <?php
       
   }
   /*
   function Process($row) {
      $sApp =SQLFC(gp('gp_app'));
      $sInst=SQLFC(gp('gp_inst'));
      $hApp =hSanitize(gp('gp_app'));
      $hInst=hSanitize(gp('gp_inst'));
      $sVer =SQLFC(gp('gp_ver'));
      $hVer =hSanitize(gp('gp_ver'));

      // Get information on latest version of Node Manager and
      // link to that
      $mv=SQL_OneValue("mv"
         ,"SELECT max(version) as mv 
             FROM appversions
            WHERE application='andro'"
      );
      $DIR_LINK_LIB=$GLOBALS['AG']['dirs']['root'].'/pkg-apps/andro-'.$mv;
      
      // Source of symlinks for app directories 
      $DIR_LINK_APP=$GLOBALS['AG']['dirs']['root']."/pkg-apps/$hApp-$hVer";
      
      
      // Get application information for the DO program
      $tsql = 
         'SELECT * from applications '
         .' WHERE application = '.$sApp;
      $row_a = SQL_OneRow($tsql);
      $tsql = 
         'SELECT * from webpaths '
         .' WHERE webpath = '.SQLFC($row_a['webpath']);
      $row_n = SQL_OneRow($tsql);
  
      $dirws = AddSlash(trim($row_n["dir_pub"]));
      //if (substr($dirws,-1,1)<>"/") $dirws.="/";
      //$row["webserver_dir_pub"] = $dirws;
      
      $string=
'<?php
// To run this program from the command line, you must
// be logged in as a user that has superuser priveleges, such
// as root or postgres.  When running from the web app,
// the current user\'s priveleges are used.

$GLOBALS["parm"] = array(
   "DBSERVER_URL"=>"localhost"
   ,"UID"=>"'.SessionGet('UID').'"
   ,"DIR_PUBLIC"=>"'.$dirws.'"
   ,"DIR_PUBLIC_APP"=>"'.$hApp.'_'.$hInst.'"
   ,"DIR_LINK_LIB"=>"'.$DIR_LINK_LIB.'"
   ,"DIR_LINK_APP"=>"'.$DIR_LINK_APP.'"
   ,"APP"=>"'.$hApp.'_'.$hInst.'"
   ,"INST"=>"'.$hInst.'"
   ,"TEMPLATE"=>"'.$row_a['template'].'"
   ,"XDIRS"=>"'.trim($row_a['xdirs']).'"
   ,"ROLE_LOGIN"=>"'.ArraySafe($row_a['flag_rolelogin'],'Y').'"
   ,"FLAG_PWMD5"=>"'.ArraySafe($row_a,'flag_pwmd5','N').'"
   ,"IVER"=>"'.$hVer.'"
   ,"APPDSC"=>"'.trim($row_a["description"]).'"
   ,"SPEC_BOOT"=>"'.trim($row_a["appspec_boot"]).'"
   ,"SPEC_LIB"=>"'.trim($row_a["appspec_lib"]).'"
   ,"SPEC_LIST"=>"'.trim($row_a["appspec"]).'"
);
   
include("AndroBuild.php");  
?>
   ';
      $t=pathinfo(__FILE__);
      $dircur = $t["dirname"];
      if (substr($dircur,-1,1)<>"/") $dircur.="/";
      $file = $dircur."do-$hApp-$hInst.php";
      $FILE = fopen($file,"w");
      fwrite($FILE,$string);
      fclose($FILE);
      //x_EchoFlush("");
      include($file);
      
      if(ArraySafe($GLOBALS,'retval',0)==1) {
         SQL("update instances set version=$sVer
               WHERE application = $sApp
                 AND instance    = $sInst"
         );
      }
      //chmod($file,0770);
      //$execstring = "php ".$dircur."do".$app.".php >$pLogPath 2>&1 &";
      //`$execstring`;
   } 
   */
}
?>
