<?php
class public_downloads extends x_table2 {
   function custom_construct() { 
      // Don't use the template, we will either pass back a plain-white
      // error or a file.
      $this->flag_buffer=false;
   }
   
   function main() {
      // Basically three items.  Either give them back the latest
      // filename, give them contents of latest, or give them
      // contents of a specific version of Andromeda
      if(gp('gp0')=='latest_filename.php') {
         echo LatestAndro();
      }
      else {
         $file=gp('gp0');
         if(strpos($file,'andro')  ===false) echo "File not found";
         else $this->GiveEmFile($file);
      }
      // Exit prevents any further extraneous output.
      exit;
   }
   
   function GiveEmFile($filename) {
      /*
      $row=array(
         'iphash'=>md5(ArraySafe($_SERVER,'REMOTE_ADDR','0.0.0.0'))
         ,'filename'=>$filename
      );
      SQLX_INSERT("downloads",$row);
      */

      // Test code, 
      //$filename="temp.txt";
      //$dir = $GLOBALS['AG']['dirs']['root'].'/';
      
      $dir = $GLOBALS['AG']['dirs']['root'].'/pkg-apps/';
      httpHeadersForDownload($dir.$filename,false);
      readfile($dir.$filename);
      exit;
   }
}
?>
