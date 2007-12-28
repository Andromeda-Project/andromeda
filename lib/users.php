<?php
class users extends x_table2 {
   function custom_construct() {
      if(ArraySafe($GLOBALS['AG'],'flag_pwmd5','N')=='Y') {
         unset($this->table['flat']['member_password']);
      }
      
   }
   
}
?>
