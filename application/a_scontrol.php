<?php
class a_scontrol extends x_table2 {
   function custom_construct() {
      if(gp('gp_action')<>'') $this->flag_buffer=false;
   }
   
   function main() {
      switch(gp('gp_action')) {
         case 'pull':   $this->mainPull(); break;
         case 'over':   $this->mainOver(); break;
         case 'diff':   $this->mainDiff(); break;
      }
      if(gp('gp_action')<>'') return;
      
      if(gpExists('gp_clearremote')) {
         SessionSet('remoteUID','');
         SessionSet('remotePWD','');
         SessionSet('remoteNODE','');
      }
      
      // Need this basic stuff for everything
      $skey=gp('gp_skey');
      hidden('gp_skey',$skey);
      hidden('gp_page','a_scontrol');
      $row=SQL_OneRow("Select * from applications WHERE skey=$skey");
      $App =trim($row['application']);
      $sApp=SQLFC($App);
      $node=SQL_OneRow(
         "Select * from nodes WHERE node="
         .SQLFC($row['node'])
      );
      $this->row=$row;
      $this->node=$node;

      // Some file functions execute before showing the screen, because
      // they affect what is displayed on the screen
      switch(gp('gpfa')) {
         case 'patch':     $this->Patch();      break;
         case 'overlocal': $this->OverLocal();  break;
         case 'servsend':  $this->ServerSend(); break;
         case 'servdel':   $this->ServerDel();  break;
      }
           
      
      $h1="?gp_page=a_scontrol"
         ."&gp_action=pull&gp_url=".$node['node_url']
         ."&gp_app=".$App;
      $h2="?gp_page=a_scontrol&gp_action=over";
      $h3="?gp_page=a_scontrol&gp_skey=$skey";
      
      ?>
      <h1>Source Code Operations</h1>
      <p>For application 
         <a href="?gp_page=applications&gp_skey=<?php echo $skey?>"><?php echo $App?></a>.
      </p>
      <?php echo sourceDeprecated()?>
      <?php
      if(!$this->CheckRemoteUID($node['node'])) return;
      ?>

      <p><a href="javascript:formPostString('x=y')">Refresh This Page</a></p>
      <p>The Authoritative Node for this application is 
         <?php echo $node['node']?> at <?php echo $node['node_url']?>.
         You are using username <b><?php echo SessionGet('remoteUID')?></b> on
         the remote node.
         <a href="javascript:formPostString('gp_clearremote=1')">Connect as New User</a>.
      </p>
      <br>
      
      <style>table.sc td { padding: 3px; }</style>
      <table cellpadding=0 cellspacing=0 class="sc">
        <tr><td class="dhead" width=100>Function</td>
            <td class="dhead">Details</td>
        <tr><td>
            <a href="javascript:Popup('<?php echo $h1?>')">
               Update Reference</a>
            <td>Pulls the latest code from the Authoritative Node and puts
                it into the "ref" directory of the application.  Does not
                modify programs in 'application' or 'appclib' (or 'lib',
                'clib', 'root' and 'templates' for the node manager).
        <!--
        <tr><td>
            <a href="javascript:Popup('< ?=$h2? >')">
               Overwrite From Reference</a>
            <td><font color=red>Unconditionally destroys any code you have
                for this application</font> and replaces it with the
                reference code. Before being destroyed, the code is backed up into
                a directory named "ref-"+current timestamp, so that it can
                be recovered if necessary.  It is the programmer's
                responsibility to delete these backup directories as desired.
           -->
      </table>
      <?php  
      $hbase="?gp_page=a_scontrol&gp_app=$App&gp_skey=$skey";
      
      $dir=AppDir($App);
      $dirs=AppDirs($App);
      $filesL=array();
      $filesR=array();
      clearstatcache();  // need this before scanning dirs
      foreach($dirs as $onedir) {
         $this->WalkDir($filesL, $dir,        trim($onedir));
         $this->WalkDir($filesR, $dir."ref/", trim($onedir));
      }

      // Files they have and we don't. Pretty easy
      ?>
      <br><br>
      <h2>Server Files Not on Local Machine</h2>
      <table>
        <tr><td class="dhead">Filename
            <td class="dhead">&nbsp;
            <td class="dhead">&nbsp;
      <?php
      $count=0;
      foreach($filesR as $name=>$fileR) {
         if(!isset($filesL[$name])) {
            $hlinkC
               =$hbase
               .'&gpfa=overlocal'
               .'&gpfile='.urlencode($name);
            $hlinkD
               =$hbase
               .'&gpfa=servdel'
               .'&gpfile='.urlencode($name);
            $count++;
            $row=array(
               $name
               ,"&nbsp&nbsp;<a href='$hlinkC'>Overwrite Local</a>&nbsp&nbsp;"
               ,"&nbsp&nbsp;<a href='$hlinkD'>Delete From Server</a>&nbsp&nbsp;"
            );
            echo hTRFromArray('',$row);
         }
      }
      if($count==0) echo hTrFromArray('',array('none',''));
      echo "</table>";
      
      // Files we have and they dont
      ?>
      <br><br>
      <h2>Local Files Not on Server</h2>
      <table>
        <tr><td class="dhead">Name
            <td class="dhead">&nbsp;
      <?php
      $count=0;
      foreach($filesL as $name=>$fileL) {
         if(!isset($filesR[$name])) {
            $count++;
            $hlink
               =$hbase
               .'&gpfa=servsend'
               .'&gpfile='.urlencode($name);
            $row=array(
               $name
               ,"&nbsp&nbsp;<a href='$hlink'>Send To Server</a>&nbsp&nbsp;"
            );
            echo hTRFromArray('',$row);
         }
      }
      if($count==0) echo hTrFromArray('',array('none',''));
      echo "</table>";
      
      ?>
      <br><br>
      <h2>Files That Are Different</h2>
      <table>
        <tr><td class="dhead">Name
            <td class="dhead">Differences
            <td class="dhead">&nbsp;
            <td class="dhead">&nbsp;
            <td class="dhead">&nbsp;
      <?php
      foreach($filesR as $name=>$fileR) {
         if(!isset($filesL[$name])) continue;
         $fileL=$filesL[$name];
         $diffs=array();
         if($fileL['fsize']<>$fileR['fsize'])  $diffs[]='fsize';
         if($fileL['md5']  <>$fileR['md5'])    $diffs[]='md5';
         if(count($diffs)<>0) {
            $hlinkP
               =$hbase
               .'&gpfa=patch'
               .'&gpfile='.urlencode($name);
            $hlinkC
               =$hbase
               .'&gpfa=overlocal'
               .'&gpfile='.urlencode($name);
            $hlinkD
               ="javascript:Popup('$hbase"
               .'&gp_action=diff'
               .'&gpfile='.urlencode($name)
               ."')";
            $row=array(
               $name."&nbsp;&nbsp;"
               ,"&nbsp;&nbsp;".implode(' ',$diffs)."&nbsp;&nbsp;"
               ,"&nbsp;&nbsp;<a href='$hlinkP'>Patch To Server</a>&nbsp;&nbsp;"
               ,"&nbsp;&nbsp;<a href='$hlinkC'>Overwrite Local</a>&nbsp;&nbsp;"
               ,"&nbsp;&nbsp;<a href=\"$hlinkD\">View Diff</a>&nbsp;&nbsp;"
            );
            echo hTRFromArray('',$row);
         }
      }
      echo "</table>";
      
      //hprint_r($filesL);
      //hprint_r($filesR);

      /*
      <table cellpadding=0 cellspacing=0 class="sc">
        <tr><td class="dhead">File</td>
            <td class="dhead">Local</td>
            <td class="dhead">Reference</td>
            <td class="dhead">Patch to Server</td>
            <td class="dhead">New To Server</td>
            <td class="dhead">Ovewrite Local</td>
      */
   }

   // ============================================================
   // Check remote UID   
   // ============================================================
   function CheckRemoteUID($node) {
      // If the node we were last on is not the current one, wipe
      // out any memory of previous UID and PWD
      if(SessionGet('remoteNODE')<>$node) {
         SessionSet('remoteUID','');
         SessionSet('remotePWD','');
         SessionSet('remoteNODE',$node);
      }
      
      // Already a user?  Fine, go back.
      if(SessionGet('remoteUID')<>'') {
         return true;
      }
      
      // If they posted a uid/pw, assume it is ok and go back
      // and tell them to continue.  Notice we don't check or anything,
      // to do that we'd have to have a function on the remote server
      // that told us if a login was ok, and we kind of don't really 
      // want that, it could be abused.
      if(gp('remoteUID')<>'') {
         SessionSet('remoteUID',gp('remoteUID'));
         SessionSet('remotePWD',gp('remotePWD'));
         return true;
      }

      ?>
      <p>
      The authoritative node for this application is <b><?php echo $node?></b>.
      Please provide your user id and password for that node.
      </p>
      <table>
      <tr><td style="text-align: right">Remote user id
          <td style="text-align: left"><input name="remoteUID" id="remoteUID" 
                                        tabindex="<?php echo hpTabIndexNext(100)?>">
      <tr><td style="text-align: right">Remote password
          <td style="text-align: left"><input name="remotePWD" type="password"
                     tabindex="<?php echo hpTabIndexNext(100)?>">
      </table>
      <br/>
      <input type="submit" value="Save" tabindex="<?php echo hpTabIndexNext(100)?>">
      
      <?php
      vgfSet('HTML_focus','remoteUID');
      
      
      // Since we don't have a remote uid/pwd anymore, return false
      // so they don't display the rest of the page
      return false;
      
   }
   
   function WalkDir(&$files, $base, $path ) {
      if(!is_dir("$base/$path")) return;
      if(substr($path,-4)=='.svn') return;
      
      $DIR=opendir("$base/$path");
      while (false !== ($file = readdir($DIR))) {
         if($file=='.' || $file=='..') continue;
         
         $fn="$base/$path/$file";
         if(is_link($fn)) {
            // Do nothing, we ignore links
         }
         elseif(is_dir($fn)) {
            $this->WalkDir($files, $base, "$path/$file");
         }
         else {
            $files["$path/$file"]=array(
               'fsize'=>filesize($fn)
               ,'md5'=>md5(file_get_contents($fn))
            );
         }
      }
      closedir($DIR);
   }
      
   // --------------------------------------------------------------
   // MAJOR FUNCTION: PULL REFERENCE CODE FROM SERVER
   // --------------------------------------------------------------
   function OverLocal() {
      // Overwrite a local file with the reference file
      $gpfile=gp('gpfile');
      $app   =gp('gp_app');
      $dir=AppDir($app);
      //echo "Would copy  $dir/ref/$gpfile to $dir/$gpfile";
      copy("$dir/ref/$gpfile","$dir/$gpfile");
   }

   function Patch() {
      $diff=$this->mainDiff(true); // request the diff
      $file=gp('gpfile');
      $dir =AppDir($this->row['application'])."/tmp/";
      $fn  =$dir.scFileName($file).'.diff';
      file_put_contents($fn,$diff);
      $parms
         ="?gp_uid=".SessionGet('remoteUID')
         ."&gp_pwd=".SessionGet('remotePWD')
         ."&gp_page=a_codexfer"
         ."&gp_app=".trim($this->row['application'])
         ."&gp_action=patch"
         ."&gpfile=".urlencode($file);
         
      // Setup the file transfer
      $postData = array();
      //simulates <input type="file" name="file_name">
      $dir=$GLOBALS['AG']['dirs']['root'];
      $postData[ 'file_name' ] = "@".$fn;
      $postData[ 'submit' ] = "UPLOAD";
            
      $url=$this->node['node_url'];
      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://$url/andro/$parms");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_POST  , 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData );
      $retval = curl_exec($ch);
      curl_close($ch);
      if(substr($retval,0,3)<>'OK:') {
         ErrorAdd($retval);
      }
      else {
         file_put_contents(
            AppDir($this->row['application'])."/ref/$file"
            ,substr($retval,3)
         );
      }
   }
   
   function ServerSend() {
      $file=gp('gpfile');
      $parms
         ="?gp_uid=".SessionGet('remoteUID')
         ."&gp_pwd=".SessionGet('remotePWD')
         ."&gp_page=a_codexfer"
         ."&gp_app=".trim($this->row['application'])
         ."&gp_action=servsend"
         ."&gpfile=".urlencode($file);
         
      // Setup the file transfer
      $postData = array();
      //simulates <input type="file" name="file_name">
      $dir=appDir($this->row['application']);
      $postData[ 'file_name' ] = "@".$dir.$file;
      $postData[ 'submit' ] = "UPLOAD";
            
      $url=$this->node['node_url'];
      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://$url/andro/$parms");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_POST  , 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData );
      $retval = curl_exec($ch);
      curl_close($ch);

      // Process Results
      if($retval=='1') {
         $d1=AppDir($this->row['application']);
         $d2=$d1.'/ref/';
         fsMakeDirNested($d2,dirname($file));
         copy($d1.$file,$d2.$file);
      }
      else {
         ErrorAdd($retval);
      }
   }

   function ServerDel() {
      $file=gp('gpfile');
      $parms
         ="?gp_uid=".SessionGet('remoteUID')
         ."&gp_pwd=".SessionGet('remotePWD')
         ."&gp_page=a_codexfer"
         ."&gp_app=".trim($this->row['application'])
         ."&gp_action=servdel"
         ."&gpfile=".urlencode($file);
      $url=$this->node['node_url'];
      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://$url/andro/$parms");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $retval = curl_exec($ch);
      curl_close($ch);
      if($retval<>1) {
         ErrorAdd($retval);
      }
      else {
         $d1=AppDir($this->row['application']).'/ref/';
         unlink($d1.$file);
      }
   }
  
   // --------------------------------------------------------------
   // MAJOR FUNCTION: PULL REFERENCE CODE FROM SERVER
   // --------------------------------------------------------------
   function mainPull() {
      // Pull the tarball down
      $url=gp('gp_url');
      $app=gp('gp_app');
      x_EchoFlush("Pulling Reference Code for $app From $url");

      $wp=AppDir($app);
      $wp="$wp/ref";
      if(!is_dir($wp)) mkdir($wp);
      x_echoFLush(" -> Purging out $wp");
      $cmd="rm -r $wp/*";
      x_echoFLush(" -> Command is $cmd");
      `$cmd`;
      
      x_EchoFlush(" -> Waiting for file...");
      // Set up the parms of the array
      $parms
         ="?gp_uid=".SessionGet('remoteUID')
         ."&gp_pwd=".SessionGet('remotePWD')
         ."&gp_page=a_codexfer"
         ."&gp_app=".$app
         ."&gp_action=devpull";
      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://$url/andro/$parms");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $retval = curl_exec($ch);
      //echo $retval;
      curl_close($ch);
      
      x_EchoFlush("");
      x_EchoFlush("Size of returned file was: ".hnumber(strlen($retval),0)." bytes");
      
      if(strlen($retval)<5000) {
         x_EchoFlush("File contents were: ");
         hprint_r($retval);  
      }
      else {

         // Save it and expand it
         file_put_contents($wp."/devcode.tgz",$retval);
         x_EchoFlush(" -> Saving to $wp/devcode.tgz");
         //$command="tar xpzvf devcode.tgz";
         //chdir($wp);
         //x_EchoFlush(" -> Unpacking with this command: $command");
         //`$command`;
         x_EchoFlush(" -> Expanding file now");
         ExtractTGZ($wp."/devcode.tgz",$wp);
         unlink($wp."/devcode.tgz");
         x_EchoFlush(" -> Deleted devcode.tgz, all done");
      }
   }
   
   function mainDiff($quiet=false) {
      $app=gp('gp_app');
      $file=gp('gpfile');
      $d1=AppDir($app);
      $f1=$d1.$file;
      $f2=$d1.'/ref/'.$file;
      $diff=shell_exec("diff -u $f2 $f1");
      if($quiet) return $diff;
      
      // If not in quiet mode, they want to view the diff
      x_EchoFlush("<h1>File Diff</h1>");
      x_EchoFlush("Application $app");
      x_EchoFlush("Local file is: $f1");
      x_EchoFlush("Reference file is: $f2");
      echo "<pre>";
      echo htmlentities($diff);
      echo "</pre>";
      
   }
   
}
?>
