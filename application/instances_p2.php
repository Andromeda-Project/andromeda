<?php
class instances_p2 extends x_table2 {
   function custom_construct() {
      $this->flag_buffer=false;
      $this->caption = "Run another build";
   }
   
   function main() {
       gpSet('gp_posted','1');
       $this->main_pr_execute();
   }
   
   
   function main_pr_execute() {
       ob_start();
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
      
      $sVer =SQLFC(gp('gp_ver'));
      $hVer =hSanitize(gp('gp_ver'));
      
      // KFD 2/4/08, If this is a subversion-enabled server, 
      //     get version information from there
      if(OptionGet('DEV_STATION','')<>'') {
          $aversions = svnVersions();
          $mv = '-VER-'.$aversions['andro']['local'];
      }
      else {
          // Get information on latest version of Node Manager and
          // link to that
          $mv=SQL_OneValue("mv"
             ,"SELECT max(version) as mv 
                 FROM appversions
                WHERE application='andro'"
          );
      }
      $DIR_LINK_LIB=$GLOBALS['AG']['dirs']['root'].'/pkg-apps/andro'.$mv;
      
      // Source of symlinks for app directories 
      $DIR_LINK_APP=$GLOBALS['AG']['dirs']['root']."/pkg-apps/$hApp-VER-$hVer";
      
      
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
   ,"IVER"=>"'.$hVer.'"
   ,"XDIRS"=>"'.trim($row_a['xdirs']).'"
   ,"FLAG_PWMD5"=>"'.ArraySafe($row_a,'flag_pwmd5','N').'"
   ,"ROLE_LOGIN"=>"'.ArraySafe($row_a,'flag_rolelogin','Y').'"
   ,"TEMPLATE"=>"'.$row['template'].'"
   ,"APPDSC"=>"'.trim($row_a["description"]).'"
   ,"SPEC_BOOT"=>"'.trim($row_a["appspec_boot"]).'"
   ,"SPEC_LIB"=>"'.trim($row_a["appspec_lib"]).'"
   ,"SPEC_LIST"=>"'.trim($row_a["appspec"]).'"
);
   
include("androBuild.php");  
?>
   ';
   
   
   
      $t=pathinfo(__FILE__);
      $dircur = AddSlash($t["dirname"])."../tmp/";   
      //$dircur = $t["dirname"];
      if (substr($dircur,-1)<>"/") $dircur.="/";
      $file = $dircur."do-$hApp-$hInst.php";
      $FILE = fopen($file,"w");
      fwrite($FILE,$string);
      fclose($FILE);
      include($file);
      
      if(ArraySafe($GLOBALS,'retval',0)==1) {
         SQL("update instances set version=$sVer
               WHERE application = $sApp
                 AND instance    = $sInst"
         );
      }
      echo ob_get_clean();

   }      
}
?>
