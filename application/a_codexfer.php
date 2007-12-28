<?php
class a_codexfer extends x_table2 {
   function custom_construct() {
      $this->flag_buffer=false;
   }
   
   function main() {
      // basically, if they could not connect to the db 
      // server, this cannot be right, just forget it
      if(!$GLOBALS['dbconn']) return;
      
      // The function names below are the viewpoint of the client machine,
      // not this server machine.  So "Pull" means somebody is pulling
      // from here, "ServSend" means somebody is sending to here, etc.
      switch(gp('gp_action')) {
         case 'pull'   :  $this->Pull();     break;
         case 'devpull':  $this->DevPull();  break;
         case 'servsend': $this->ServSend(); break;
         case 'servdel':  $this->ServDel();  break;
	 case 'patch':    $this->Patch();    break;
      }
      return '';
   }
   
   // ---------------------------------------------------------------
   // Patch a file received from client
   // ---------------------------------------------------------------
   function Patch() {
      if(count($_FILES)==0) {
         echo "ERROR: No file was received";
         return;
      }
      // Get dir information and so forth
      $dir=AppDir(gp('gp_app'));
      $filepath=$dir.'/'.gp('gpfile');
      $patchfn=$_FILES['file_name']['tmp_name'];

      // Save file and attempt patch
      $fcnow=file_get_contents($filepath);
      $retval=shell_exec("patch -s $filepath $patchfn");
      if($retval=='') {
      	// An empty return, it all went well, return "OK"
         echo "OK:";
         readfile($filepath);
         return;
      }
      else {
         echo "ERROR:$retval";
         echo "<br><pre>";
         readfile($filepath.".rej");
         echo "</pre>";
         unlink($filepath.".rej");
      }
   }


   // ---------------------------------------------------------------
   // Pull a published version
   // ---------------------------------------------------------------
   function Pull() {
      $filename=gp('gp_file');
      $filename
         =$GLOBALS['AG']['dirs']['root']."pkg-apps/"
         .$filename;
      readfile($filename);
   }
   
   // ---------------------------------------------------------------
   // Pull a published version
   // ---------------------------------------------------------------
   function DevPull() {
      // Get the app and its local directory
      $app=gp('gp_app');
      $wp=AppDir($app);
      
      // Pull list of directories
      $dirs=AppDirs($app);
      $hdirs=implode(' ',$dirs);
      
      // Go to directory, rm devcode.tgz, build it again
      if(file_exists($wp.'/devcode.tgz')) unlink($wp.'/devcode.tgz');
      chdir($wp);
      $cmd='tar czvf devcode.tgz '.$hdirs;
      `$cmd`;
      readfile($wp.'/devcode.tgz');
   }
   // ---------------------------------------------------------------
   // Receive a file sent as a new file
   // ---------------------------------------------------------------
   function ServSend() {
      if(count($_FILES) ==0) {
         echo "Server reports: No file was received, unknown error.";
         return;
      }
      $dir=AppDir(gp('gp_app'));
      $gpfile=gp('gpfile');
      if(file_exists($dir.$gpfile)) {
         echo "File $dir$gpfile already exists on server, please refresh your reference files";
         return;
      }
      fsMakeDirNested($dir,dirname($gpfile));
      ob_start();
      $noecho=move_uploaded_file($_FILES['file_name']['tmp_name'],$dir.$gpfile);
      return '';
   }

   function ServDel() {
      $dir=AppDir(gp('gp_app'));
      $gpfile=gp('gpfile');
      if(!file_exists($dir.$gpfile)) {
         echo "File $dir$gpfile does not exist on server, please refresh your reference files";
	 return;
      }
      unlink($dir.$gpfile);
   }
}
?>
