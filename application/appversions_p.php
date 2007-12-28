<?php
class appversions_p extends x_table2 {
   function custom_construct() {
      if(gp('gp_process')==1) $this->flag_buffer=false;
   }
   
   function main() {
      $app=gp('gp_app');
      // Today's date will become version.  Confirm that it is ok
      $v=date("Y.m.d",time());
      $mv=SQL_AllRows(
         "Select version from appversions 
           where version = '$v'
             AND application=".SQLFC($app) 
      );
      if(count($mv)>0) {
         ?>
         <div class="errorbox">There is already a version of the application
           stamped for today.  Cannot have two versions in the same day.
         </div>
         <?php
         return;
      }

      // Will need list of directories.
      if($app=='andro') {
         $dirs=SQL_AllRows("Select * from appdirs where flag_copy='Y'");
      }
      else {
         $dirs=SQL_AllRows(
            "Select * from appdirs where flag_copy='Y'
                AND flag_lib='N'"
         );
      }
      
      // Determine the app and root directory
      // Get the app
      $this->app=$app;
      $root=SQL_OneValue('dir_pub',
         "select wp.dir_pub 
           from webpaths     wp
           JOIN applications ap ON wp.webpath=ap.webpath
          WHERE ap.application = ".SQLFC($app)
      );
      $root=AddSlash($root).AddSlash($app);
      $this->root=$root;
      //x_echoFlush("Root directory will be: ".$root);
      
      // Either run the process....
      if(gp('gp_process')==1) {
         ob_start();
         $this->mainProcess($v,$dirs);
         echo ob_end_clean();
         return;
      }
      
      $link = '?gp_page=appversions_p&gp_process=1&gp_app='.gp('gp_app');
      // ...or ask them to click the button
      ?>
      <h1>Freeze Current Version</h1>
      <p>This program will create a new version of all application files
         for application <?=hSanitize(gp('gp_app'))?>.
         It will copy all disk files into the database.
         The version will be numbered as <?=$v?>.
      </p>
         
      <p>The top level directory to be scanned is: <?=$root?>.
      </p>
      
      <p>The subdirectories to be scanned are:
      </p>
      <ul>
      <?php
      foreach($dirs as $dir) {
         echo "<li>".$dir['dirname'];
      }
      
      ?>
      </ul>
         
      <p><a href="javascript:Popup('<?=$link?>')">Process Now</a>
      </p>
      
      <?php
   }
   
   function mainPRocess($v,$dirs) {
      // First create the version
      $table_dd=DD_TableRef('appversions');
      $row=array(
         'version'     =>$v
         ,'date'       =>time()
         ,'application'=>$this->app
      );
      SQLX_Insert($table_dd,$row);
      if(Errors()) {
         echo hErrors(); 
         return;
      }

      // We'll need this for every file we load
      $this->tlf=DD_TableRef('appfiles');
      
      // Create the application save directory
      $app = $this->app;
      $r2  = $GLOBALS['AG']['dirs']['root'];
      if(!file_exists($r2.'pkg-apps'))          mkdir($r2.'pkg-apps');
      if(!file_exists($r2."pkg-apps/$app-$v"))  mkdir($r2."pkg-apps/$app-$v");
      $r2 = $r2."pkg-apps/$app-$v";

      foreach($dirs as $dir) {
         if(!file_exists($this->root.$dir['dirname'])) {
            mkdir($this->root.$dir['dirname']);  
         }
         $this->mainPR_DirFiles($v,$r2,$this->root,$dir['dirname'],'');
      }
      $dpa=$GLOBALS['AG']['dirs']['root'].'pkg-apps/';
      chdir($GLOBALS['AG']['dirs']['root'].'pkg-apps/');
      $command="tar czvf $app-$v.tgz $app-$v";
      x_EchoFlush("");
      x_EchoFlush("Tarballing with this command: $command");
      `$command`;
      x_EchoFlush("");

      // Now create the install version.
      if($this->app=='andro') {
         x_EchoFlush("Renaming install.done.php to install.php");
         rename(
            "$dpa$app-$v/application/install.done.php"
            ,"$dpa$app-$v/application/install.php"
         );
         x_EchoFlush("Renaming directory to andro, copying index.php");
         rename("$dpa$app-$v",$dpa."andro");
         copy($dpa."andro/root/index.php",$dpa."andro/index.php");
         copy($dpa."andro/root/htaccess",$dpa."andro/.htaccess");
         // KFD 8/2/07, Sourceforge bug #1755244, for the node manager
         //             these are created by build but we need them for
         //             the build, so make them here.
         mkdir($dpa."andro/tmp");
         mkdir($dpa."andro/generated");
         // KFD 8/2/07, Sourceforge bug #1755244, END
         $command="tar czvf $app-$v-install.tgz andro index.php .htaccess";
         x_EchoFlush("");
         x_EchoFlush("Tarballing install version with this command: $command");
         `$command`;
         
         x_EchoFlush("Renaming directory back to $app-$v");
         unlink($dpa."andro/index.php");
         unlink($dpa."andro/.htaccess");
         rename($dpa."andro","$dpa$app-$v");
         
         
         x_EchoFlush("Process is complete!");
      }
   }
   
   
   function mainPR_DirFiles($v,$r2,$root,$dirname,$dirpath) {
      // Put together three segments of directories to begin looping
      $dir=AddSlash($root);
      $dir=AddSlash($dir.$dirname);
      $dir=AddSlash($dir.$dirpath);
      $d2 =AddSlash($r2);
      $d2 =AddSlash($d2.$dirname);
      $d2 =AddSlash($d2.$dirpath);
      if(!file_exists($d2)) mkdir($d2);
      
      x_EchoFlush("");
      x_EchoFlush("Begin scan of directory: $dir");
      
      $DIR=opendir($dir);
      while (false !== ($file = readdir($DIR))) {
         // no . or ..
         if($file=='.')  continue;
         if($file=='..') continue;
         
         // Get formatted name of dir + file;
         $fn=AddSlash($dirpath).$file;
         
         // This is a directory, maybe recurse
         if(is_dir($dir.$file)) {
            $this->mainPR_DirFiles($v,$r2,$root,$dirname,$fn);
         }
         else {
            $filecnts=file_get_contents($dir.$file);
            $row=array(
               'version'     =>$v
               ,'application'=>$this->app
               ,'filename'   =>$fn
               ,'dirname'    =>$dirname
               ,'filecnts'   =>''
               ,'filets'     =>filemtime($dir.$file)
               ,'filemd5'    =>md5($filecnts)
               ,'filesize'   =>strlen($filecnts)
            );
            x_EchoFlush("Loading file $fn, size: ".strlen($filecnts));
            //SQLX_Insert($this->tlf,$row);
            copy($dir.$file,$d2.$file);
         }
         
      }
      
      x_EchoFlush("");
      x_EchoFlush("End scan of directory: $dir");
   }
}
?>
