<?php
class yamlview extends x_table2 {
   function main() {
      $file=$GLOBALS['AG']['dirs']['root'].'application/'.gp('file');
      include_once("spyc.php");
      echo "\n<h2>Examine YAML file $file</h2>";
      echo "\n<h3>HERE IS THE SOURCE FILE:</h3>";
      echo "\n<pre>";
      readfile($file);
      echo "\n</pre>";
      $temparray=Spyc::YAMLLoad($file);
      echo "<h3>HERE IT IS PARSED:</h3>";
      hprint_r($temparray);
   }
}
?>
