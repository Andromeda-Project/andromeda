<?php
# Deprecate files list:
#
# a_pullsvna.php, since 4/14/08, not necessary after we "rationalized"
#                  the downloads.


SessionSet('TEMPLATE','rt_pixel');
//vgaSet('MENU_TYPE','TABLE');
global $MPPages;
$MPPages=array('public_downloads'=>1);

vgfSet('ajaxTM',0);
vgfSet('loglogins',true);
vgfSet('buttons_in_commands',true);

function AppDir($app) {
   $app=trim($app);
   $wp=SQL_OneValue('dir_pub',
      "Select dir_pub
         from applications a
         JOIN webpaths     w ON a.webpath=w.webpath
        WHERE a.application='$app'"
   );
   return trim($wp)."/$app/";
}
function AppDirs($app) {
   if($app=='andro') {
      $sq="SELECT dirname FROM appdirs WHERE flag_copy='Y'";
   }
   else {
      $sq="SELECT dirname FROM appdirs 
            WHERE flag_copy='Y' and flag_lib='N'";
   }
   return array_Keys(SQL_allRows($sq,'dirname'));
}
function LatestAndro() {
   $dir = $GLOBALS['AG']['dirs']['root'].'/pkg-apps/';
   $files=scandir($dir);
   $maxtime=0;
   $maxfile='';
   foreach($files as $file) {
      // Only Andromeda non-install files please
      if(strpos($file,'andro')  ===false)  continue;
      if(strpos($file,'install')!==false)  continue;
      if(substr($file,-4)       !='.tgz' ) continue;
      
      // If later than current, save it as our candidate
      if(filemtime($dir.$file)>$maxtime) {
         $maxtime=filemtime($dir.$file);
         $maxfile=$file;
      }
   }
   
   return $maxfile;
}
function hLinkBuild($app,$caption) {
   return hLinkPopup(
      ''
      ,$caption
      ,array(
         "gp_page"=>"a_builder"
         ,"gp_out"=>"none"
         ,'txt_application'=>$app
      )
   );
}

/* KFD 4/15/08
function ExtractTGZ($filespec,$dir) {
   chdir($dir);
   require_once "Archive/Tar.php";
   $tar = new Archive_Tar($filespec,'gz');
   $tar->extract($dir);
}
*/
function sourceDeprecated() {
    ?>
    <div style="border: 3px solid #FF0700; padding: 5px; margin: 10px; 
    background-color: #FFFF00; font-weight: bolder">
    This feature has been deprecated and will be removed from Andromeda
    soon.<br/><br/>  We are now using Subversion (SVN) for Andromeda
    itself and we urge you to setup Subversion for your own applications.
    Subversion is much more powerful than Andromeda's source control
    operations, and it is well worth setting up.
    </div>
    <?php
}
/**
 * Pull all apps out of the server and then examine
 * the local station for latest versions
 *
 */
function svnVersions() {
    // Get a list of applications
    $sq="SELECT skey,application,description
               ,svn_url
               ,'  ' as local
               ,'  ' as latest
               ,svn_uid,svn_pwd,flag_svn
           FROM applications
          WHERE flag_svn = 'Y'
          ORDER by application";
    $rows = SQL_Allrows($sq,'application');
    
    // Get latest pkg-apps entries
    $dir=fsDirTop().'pkg-apps/';
    if(!file_exists($dir)) {
        mkdir($dir);
    }
    
    $vdirs=scandir($dir);
    foreach($vdirs as $vdir) {
        if($vdir=='.') continue;
        if($vdir=='..') continue;
        if(strpos($vdir,'-VER-')===false) continue;
        
        // split into app and version
        list($app,$vers) = explode('-VER-',$vdir);
        if(isset($rows[$app])) {
            $rows[$app]['local']=max($rows[$app]['local'],$vers);
        }
    }
    return $rows;
} 
/**
 * KFD 2/23/08, Put frequently used links over on the left
 * all of the time
 *
 */
function appModuleLeft() {
    if(!LoggedIn()) return false;

    ?>
    <div class="moduletable">
    <h3>Updates</h3>
    </div>
    <table>
      <tr><td class="leftcol"><a class="mainlevel" href="?gp_page=a_pullsvn"
        >Pull Code From Subversion</a>
    </table>
    <br/> 
    <?php

    // Display either applications or instances, depending upon 
    // which we have here
    # KFD 4/15/08, make this unconditional
    #$ds =OptionGet('DEV_STATION','Y');
    #$boa=OptionGet( 'BUILD_ALL_APPS','N');
    #if($ds=='Y' || $boa == 'Y') {
    if(True) {
        $apps=SQL_AllRows("Select * from applications order by application");
        ?>
        <div class="moduletable">
        <h3>Applications</h3>
        </div>
        <table style="width:100%">
        <?php foreach($apps as $app) { ?>
           <tr>
             <td align="left" class="leftcol">
               <a href="?gp_page=applications&gp_skey=<?=$app['skey']?>"
               ><?=$app['application']?></a>
             <td align="right"  class="leftcol">
               <?=hLinkBuild($app['application'],'Build')?>
        <?php }?>
        </table>
        <br/>
        <?php
    }
    
    $instances = SQL_ALLROWS("Select * from instances
        order by application,instance");
    if(count($instances)>0) {
        ?>
        <div class="moduletable">
        <h3>Instances</h3>
        </div>
        <table width="100%">
        <?php foreach($instances as $i) { ?>
            <tr>
            <td align="left">
              <a href="?gp_page=instances&bp_skey=<?=$i['skey']?>">
              <?=$i['application'].' / '.$i['instance']?>
              </a>
            <td align="right">
              <a href="?gp_page=instances_p&gp_app=<?=trim($i['application'])
                  ?>&gp_inst=<?=$i['instance']?>"
                  >Build/Upgrade</a>
        <?php } ?>
        </table>
        <?php
    }
    
    
    return false;
}

// DO 2-22-2008 Moved these out of AndroBuilder as they may be needed in other files 
function GetOS() {
   return $_ENV['OS'];
}

function isWindows() {
   $x=eregi('WIN',PHP_OS);
   return $x===false ? false : true;
}

?>
