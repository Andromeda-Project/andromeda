<?php 

// file example 1: read a text file into a string with fgets 

$filename="templateDetails.xml"; 
$output=""; 
$file = fopen($filename, "r"); 
while(!feof($file)) $output.= fgets($file, 4096); 
fclose ($file);
 
//echo $output; 

$filesstart = "<files>";
$filesend = "</files>";

$ignores = array("/._", "CVS/", ".DS_Store", ".bak");


$xmlstart = (substr($output,0, strpos($output,$filesstart)+strlen($filesstart)+1));

$xmlend = (substr($output,strpos($output, $filesend)));

$newxmlfile = $xmlstart . ls ("./") . $xmlend;


// Let's make sure the file exists and is writable first.
if (is_writable($filename)) {

   // In our example we're opening $filename in append mode.
   // The file pointer is at the bottom of the file hence
   // that's where $somecontent will go when we fwrite() it.
   //	rename($filename, $filename . ".bak");
   if (!$handle = fopen($filename, 'a')) {
         echo "Cannot open file ($filename)\n";
         exit;
   }

   // Write $somecontent to our opened file.
   if (fwrite($handle, $newxmlfile) === FALSE) {
       echo "Cannot write to file ($filename)\n";
       exit;
   }
  
   echo "Success, wrote data to file ($filename)\n";
  
   fclose($handle);

} else {
   echo "The file $filename is not writable\n";
}



function ls ($curpath) {
   $lsoutput = "";
   $dir = dir($curpath);
   while ($file = $dir->read()) {

       if($file != "." && $file != "..") {
           if (is_dir($curpath.$file)) {
                 $lsoutput .= ls($curpath.$file."/");
             } else {
				if (notIgnore($curpath . $file)) {
                 $lsoutput .= "      <filename>". substr($curpath . $file,2) . "</filename>\n";
				}
             }
       }
   }
   $dir->close();
   return $lsoutput;
}


function notIgnore($element) {
	global $ignores;
	foreach ($ignores as $ignore) {
		if (strpos($element,$ignore)) return false;
	}
	return true;
}




?>
