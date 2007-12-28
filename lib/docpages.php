<?php
class docpages extends x_table2 {

   // Overrideable.  Override this method for your own layout
   function ehBoxesHTML($mode) {
      // Obtain the HTML for the inputs and output as table
      if(isset($this->row['pagename'])) {
         echo "<a href=\"?gppn=".urlencode($this->row['pagename'])."\">"
            ."VIEW THIS PAGE</a><br><br>";
      }
      $ahc= ahInputsComprehensive($this->table,$mode,$this->row);
      echo "\n<center>".hTable(100);
      $this->ehBoxesFromAHComprehensive($ahc);      
      echo "\n</table></center>";
   }
   
   // Force no extras on left, we'll do it ourselves
   function ehLinks($mode) {
      $x=$mode; echo ' '; 
   }
}
?>
