<?php
class phpinfo extends x_table2 {
   function main() {
      If(SessionGet('ADMIN',false)==false) {
         echo "Sorry, admins only";
      }
      else {
         hprint_r($_SERVER);
         phpinfo();
      }
      
   }
   
}
?>
