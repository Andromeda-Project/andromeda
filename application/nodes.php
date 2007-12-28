<?php
class nodes extends x_table2 {
   function aLinks_Extra($mode) {
      if($mode<>'upd') return;
      
      $link='http://'.AddSlash($this->row['node_url']).'andro';
      $hLink="<a href='$link' target='_BLANK'>$link</a>";
      return array($hLink);
   }
}
?>
