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
class a_pullcode extends x_table2 {
	function custom_construct() {
		if (gpExists('gp_posted')) $this->flag_buffer=false;
	}
	  
   function main() {
      if(gp('gp_posted')==1) return $this->PullCode();
      if(gp('gp_posted')==2) return $this->PullAndro();

      sourceDeprecated();
      ?>
      <h1>Upgrade Node Manager Only</h1>

      <p>This program below will download the latest available code
         for the node manager only.
      </p>
      
      <p style="color:red">Warning!  This program will overwrite the live
      running code of the Node Manager.  Any programs directly linked to the
      node manager will be instantly updated (this does not apply to 
         instances).  Remember to always keep production databases running
      as instances so that they are unaffected by this action.
      </p>
      
      <a href="javascript:Popup('?gp_page=a_pullcode&gp_posted=2')">Step 1: Get Code</a>
      <br/>
      <br/>
      <?php echo hLinkBuild('andro','Step 2: Rebuild Node Manager')?>
     
      <h1>Download Latest Software Updates</h1>      

      <p>This program downloads the latest available version
         of each program listed in the table of applications.
         The code is pulled from the 'Authoritative Node' for 
         that application (applications without an authoritative
         node are not reviewed).
      </p>
      
      <p>This program does not actually run updates to any running
         instances, each instance is upgraded manually at the 
         discretion of the administrator.
      </p>
      
      <br>
      <p>
      <a href="javascript:Popup('?gp_page=a_pullcode&gp_posted=1')">Pull Now</a>

      <?php
   }
    
   
   function pullAndro() {
      ob_start();
      x_EchoFlush("Obtaining information about Node Manager");
      $info=SQL_AllRows(
         "Select n.node_url
            from applications a
            join nodes        n ON a.node = n.node
           where a.application='andro'"
      );
      if(count($info)==0) {
         x_EchoFlush("ERROR!  There is no entry for the node manager!");
         return;
      }
      
      // Pull the two pieces we need to know
      $URL =$info[0]['node_url'];
      x_EchoFlush("We will pull latest code from:");
      x_EchoFlush("  $URL");
      $URL ="http://".$URL."/andro/pages/public_downloads/";
      x_EchoFlush("Complete URL will be:");
      x_EchoFlush("  $URL");
      
      x_EchoFlush("Requesting name of latest file from remote URL");
      $URL_req=$URL."latest_filename.php";
      x_EchoFlush(" Complete request string is ".$URL_req);
      $filename=file_get_contents($URL_req);
      x_EchoFLush("Remote URL reports latest file is: $filename");


        
      x_EchoFlush("Beginning download of $filename");
      $file=file_get_contents($URL.$filename);      
      x_EchoFlush("Download complete. Filesize is ".strlen($file).' bytes');
      $fileapps=$GLOBALS['AG']['dirs']['root']."pkg-apps";
      $filespec=$GLOBALS['AG']['dirs']['root']."pkg-apps/$filename";
      // KFD 7/25/07, this does not appear on windows systems
      if(!file_exists($fileapps)) { mkdir($fileapps); }
      x_EchoFlush("Saving as $filespec");
      x_EchoFlush("Bytes written: ".file_put_contents($filespec,$file));
      

      // Here is the extraction code
      x_EchoFlush("Extracting locally");
      require_once "Archive/Tar.php";
      $tar = new Archive_Tar($filespec,'gz');
      $tar->extract($fileapps);

      // Protect flag
      $protect=strtolower(Option_Get("NM_PROTECT"));
      if(in_array($protect,array('yes','true','y'))) {
         x_EchoFlush("");
         x_EchoFlush("ERROR!");
         x_EchoFlush("The system variable NM_PROTECT has value -$protect-");
         x_EchoFlush("This prevents any overwriting of node manager code");
         x_EchoFlush("On this node.  This is an undocumented option and");
         x_EchoFlush("would not normally be set by accident.");
         return;
      }
      
      // If we didn't abort because we're Ken's laptop, expand
      // directly over the currently running node manager.
      //
      x_echoFlush("Unpacking with PEAR Archive_Tar library."); 
      $filedest=$GLOBALS['AG']['dirs']['root'];
      $remove_path=basename($filespec,".tgz");
      $tar->extractModify($filedest,$remove_path);
      x_EchoFlush("Done");
      echo ob_get_clean();
   }
   
   function pullCode() {
      echo "<pre>";
      x_EchoFlush("Pulling a list of applications and their nodes...");
      $sq="SELECT a.application,n.node,n.node_url
                 ,COALESCE(av.version,'') as version
             FROM applications     a
             JOIN nodes            n  ON a.node = n.node
             LEFT JOIN (SELECT application,max(version) as version
                          FROM appversions
                         GROUP BY application) av
               ON a.application = av.application";
      $rows=SQL_AllRows($sq);
      if (count($rows)==0) {
         x_EchoFlush("");
         x_EchoFlush("There are no applications listing Authoritative Nodes");
         x_EchoFlush("Nothing to do!");
      }
      foreach($rows as $row) {
         $this->PullCodeApp($row);
      }
      x_EchoFlush("");
      x_EchoFlush(" ---------------- ");
      x_EchoFlush("All Processing Finished.");
   }
   
   function PullCodeApp($row) {
      $app=trim($row['application']);
      $sApp=SQLFC($app);
      $ver=$row['version'];
      $sVer=SQLFC($ver);
      x_echoFlush("");
      x_EchoFlush("*** Application: ".$row['application']);
      x_EchoFlush("  Authoritative Node is: ".$row['node']);
      x_EchoFlush("  Current Local Version: ".$ver);
      
      // Get version from remote node
      $remote = $this->CURL($row['node_url']
         ,"select max(version) as version
             FROM appversions
            WHERE application=$sApp"
      );
      if(count($remote)==0) {
         x_EchoFlush("The remote server says it's got nothing for us!");
      }
      else {
         $vremote=$remote[0]['version'];
         x_EchoFlush("  Remote server has version: $vremote"); 
         if($vremote==$ver) {
            x_EchoFlush("Local version is up-to-date, nothing to do here.");
         }
         elseif($vremote<$ver) {
            x_EchoFlush("Local version is more recent.");
            x_EchoFlush("  --> this should not normally happen, perhaps");
            x_EchoFlush("      somebody published code on this machine?");
            
         }
         else {
            x_EchoFlush("Local version out of date, pulling latest");
            $this->VersionPull($app,$vremote,$row);
            $table_dd=DD_TableRef('appversions');
            $row=array('application'=>$app,'version'=>$vremote);
            SQLX_Insert($table_dd,$row);
            echo hERrors();
         }
      }
   }
   
   function VersionPull($app,$vremote,$appinfo) {
      $url=$appinfo['node_url'];
      $filename=$app.'-'.$vremote.'.tgz';
      
      // Set up the parms of the array
      $parms
         ="?gp_uid=".SessionGet('UID')
         ."&gp_pwd=".SessionGet('PWD')
         ."&gp_page=a_codexfer"
         ."&gp_action=pull"
         ."&gp_file=".$filename;

      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://$url/andro/$parms");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $retval = curl_exec($ch);
      curl_close($ch);
      //echo $retval;
      
      file_put_contents(
         $GLOBALS['AG']['dirs']['root']."pkg-apps/$filename"
         ,$retval
      );
      chdir($GLOBALS['AG']['dirs']['root']."pkg-apps/");
      $command="tar xzvf ".$filename;
      x_EchoFlush("Unpacking with this command: $command");
      `$command`;
   }

   
   // ----------------------------------------------------------------
   // Simple Helper routines
   // ----------------------------------------------------------------
   function Curl($url,$sql,$col='') {
      // Set up the parms of the array
      $parms
         ="?gp_uid=".SessionGet('UID')
         ."&gp_pwd=".SessionGet('PWD')
         ."&gp_col=".$col
         .'&gp_sql='.urlencode($sql);

      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, "http://$url/andro/$parms");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $retval = curl_exec($ch);
      curl_close($ch);
      $retval = substr($retval,0,strlen($retval)-7);
      $retval = substr($retval,6);
      $retval = unserialize($retval);
      return $retval;
   }
}
?>
