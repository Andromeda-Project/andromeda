<?php
class instances extends x_table2 {
   function aLinks_Extra($mode) {
		if ($mode <> 'upd') { return array(); }
      
      $caption
         = !is_null($this->row['version']) && trim($this->row['version'])<>''
         ? 'Upgrade This Instance'
         : 'Build This Instance';
   
      return array(
         hLink(
            ''
            ,$caption
            ,"?gp_page=instances_p"
            ."&gp_app=".urlencode(trim($this->row['application']))
            .'&gp_inst='.urlencode(trim($this->row['instance']))
         )
      );
   }
}
?>
