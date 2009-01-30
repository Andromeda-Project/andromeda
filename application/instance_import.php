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

class x_table_instance_import extends x_table {
	function x_table_instance_import() {
		$this->flag_buffer = false;   // don't buffer output
		$this->caption = "Repeat the Process";
	}
	
	function main() {
		$this->main_pr();
	}
	
	function main_pr_html() {
		
		?>
		<h1>Upload New Program Files</h1>

		<p>On this page you can send a containing images, library code,
		and application code.  The images should be in a 'img'
		subdirectory, application code in 'app' and library code in 'lib'.
		
		<p>Any files submitted on this page will overwrite all existing
		files for application<b><?php echo CleanGet("application")?></b>.</p>
		
		<p>The maximum file size in megabytes is: <?php echo $this->maxf?>.</p>
		
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max?>000000">
		<input type="FILE" name="f_single" id="f_single">
		
		<br><br>
		<?php
	}
	function main_pr_execute() {
		
		// Important to allow long-running process
		echo "<h1>File Submission Processing</h1>";
		$this->application = CleanGet("application");
		
		if ($_FILES["f_single"]["size"]==0) {
			x_EchoFlush("The file was not received OK.");
		}
		else {
			x_EchoFlush("File received OK, wiping out current files");
			SQL("delete from appfiles where application = '".CleanGet("application")."'");

			$f_org = $_FILES["f_single"]["name"];
			$f_new = $_FILES["f_single"]["tmp_name"];
			x_EchoFlush("Original file received: ".$f_org);
			
			
			$x = pathinfo($f_new);
			$xf= $x["basename"];
			if (isset($x["extension"])) $xf.=".".$x["extension"];
			x_EchoFlush("File received as: ".$xf);
			x_EchoFlush("In directory    : ".$x["dirname"]);
		
			$this->ProcessWalk(
				$x["dirname"]."/",
				$xf,
				$f_org = $_FILES["f_single"]["name"]);
		}
			
		?>	
		<br>
		<p><b>ALL PROCESSING IS NOW COMPLETE.</B></p>
		<?php
		x_EchoFlush("");
	}
	
	// Recursive, will be used to walk through a zip, a tarball,
	// or can handle an individual file
	//
	function ProcessWalk($dir,$f_tmp,$f_org,$stack=array()) {
		x_EchoFlush("Processing: ".$dir.$f_tmp);
		
		// If we've been handed a directory, recurse the directory
		// Note there is an early return here, if we recurse we don't
		// do the rest of the code in this routine.
		//
		if (is_dir($dir.$f_tmp)) {
			$stack[] = $f_tmp;
			x_EchoFlush("This is a directory, will walk through it.");
			$DIR = opendir($dir.$f_tmp);
			while (false !== ($file=readdir($DIR))) {
				if ($file=="." || $file=="..") continue;
				$this->ProcessWalk($dir.$f_tmp."/",$file,$file,$stack);
				rmdir($dir.$f_tmp);
			}
			return;
		}
		
		if (count($stack)==0) $dird=""; else $dird=$stack[count($stack)-1];
		
		// If it's not a dir, check for mime-type that we recognize
		$mime = trim(mime_content_type($dir.$f_tmp));
		switch($mime) {
			case "application/x-gzip":
				x_EchoFlush("Recognized mime type: $mime");
				$f_new = $f_tmp.".unzipped";
				x_EchoFlush("Will unzip to file ".$f_new);
				$cmd = "gunzip -c ".$dir.$f_tmp." > ".$dir.$f_new;
				x_EchoFlush("Command is: $cmd");
				`$cmd`;
				x_EchoFlush("Will now attempt to process the unzipped file");
				$this->ProcessWalk($dir,$f_new,$f_org);
				break;
			case "application/x-tar":
				x_EchoFlush("Recognized mime type: $mime");
				$f_new = $f_tmp.".dir/";
				mkdir($dir.$f_new);
				$cmd = "tar xf ".$dir.$f_tmp ." --directory=".$dir.$f_new;
				x_EchoFlush("The untar command is: ".$cmd);
				`$cmd`;
				$this->ProcessWalk($dir,$f_new,$f_org);
				break;
			default:
				$this->LoadFile($dir,$f_tmp,$dird);
		}
		
		unlink($dir.$f_tmp);
	}
		
	
	// -------------------------------------------------------
	// -------------------------------------------------------	
	function LoadFile($dir,$f_tmp,$dird) {
		$application = CleanGet("application");
		switch ($dird) { 
			case "img":
			case "app":
			case "lib":
				$filetext = base64_encode(file_get_contents($dir.$f_tmp));
				//$filetext = 'hiken';
				$filetype = strtoupper($dird);
				$sql = "insert into appfiles ".
					"(application,filename,filetype,filetext) ".
					" VALUES ".
					"('$application','$f_tmp','".strtoupper($dird)."','$filetext')";
				SQL($sql);
		}
	}
	
}
?>
