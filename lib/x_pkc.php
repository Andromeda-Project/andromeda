<?php
class x_pkc extends x_table2 {
   function main(){
      // To submit or not submit
      ?>
      <div style="padding-left:50px; padding-top: 20px; font-family: Courier">
      <h1>Rapid Change</h1>
      <?php
      $gp_submit  = gp('gp_submit');
      if($gp_submit) $this->processSubmit();
      else           $this->prepareRow();
      echo "</div>";
   }
   
   // Prepare the row for submission
   function prepareRow(){
      // Prepare for the insert
      // Get all of the columns and the table that we are working with
      $this->columns = aFromGP('gp_upd_');
      $this->table   = gp('gp_table_upd');
      if(!$this->table){
         echo "No table supplied\n<br>";
         return;
      }
      
      hidden('gp_table_upd',$this->table);
      hidden('gp_submit',1);
      hidden('gp_page','x_pkc');
      
      // Get the flat table def
      $table_dd= dd_TableRef($this->table);
      $tabflat = ArraySafe($table_dd,'flat');
      //hprint_r($tabflat);
      $retval  = '';
      
      $retval .=  $this->dynamicVal(&$table_dd,$this->columns);
      echo $retval;
   }
   
   // Dynamically get values
   function dynamicVal(&$table_dd,&$columns){
      $tabflat=$table_dd['flat'];
      $name_prefix= 'gp_upd_';
      $opts = array(
                     'name_prefix'  => $name_prefix
                   );
      $hInputs = ahInputsComprehensive($table_dd,'ins',array(),'',$opts);
      //hprint_r($hInputs);
      $retval  = '<table>';
      //hprint_r($columns);
      foreach($tabflat as $column_id  => $details){
         $column_flat   = ArraySafe($tabflat,$column_id);
         //hprint_r($column_flat);
         $column_sent   = isset($columns[$column_id]);
         $column_supp   = ArraySafe($columns,$column_id,null);
         //hprint_r($column_supp);
         
         $pkey = ArraySafe($column_flat,'primary_key','');
         $desc = ArraySafe($column_flat,'description',null);
         
         // Get the value of any primary keys we don't have
         if((($pkey=='Y')&&($column_supp==null)) || ($column_sent && is_null($column_supp))){
            $retval .= '<tr>';
            $retval .= '<td>'.$desc;
            //$retval .= '<td>'."<input type=\"text\" name=\"$in_name\" id=\"$in_name\" value=\"$column_supp\">";
            $retval .= '<td>'.$hInputs[$column_id]['html'];
            $retval .= "</tr>";
         }
         // Create the hidden input for values we do have
         elseif($column_supp<>null){
            $in_name = $name_prefix.$column_id;
            $retval .= '<tr>';
            $retval .= '<td>'.$desc;
            $length  = (strlen($column_supp) * 10).'px';
            $retval .= '<td>'."<input type=\"text\" class=\"ro\" style=\"width: $length\" READONLY name=\"$in_name\" id=\"$in_name\" value=\"$column_supp\">";
            //$retval .= '<td>'.$hInputs[$column_id]['html'];
            $retval .= "</tr>";
         }
      }
      $retval .= "<tr><td colspan=\"99\"><input type=\"submit\" value=\"Submit\"></tr></table>";
      return $retval;
   }
   
   function processSubmit(){
      // Get the submitted data
      $table   = gp('gp_table_upd','');
      // Get the flat table def
      $table_dd= dd_TableRef($table);
      $tabflat = ArraySafe($table_dd,'flat');
      //hprint_r($table);
      $row     = aFromGP('gp_upd_');
      //hprint_r($row);
      //hprint_r($row);
      
      // Build a WHERE clause
      $where = array();
      foreach($row as $col=>$val){
         if(ArraySafe($tabflat[$col],'primary_key','N')<>'Y') continue;
         $where[] = $col." = '".$val."'";
      }
      //hprint_r($where);
      $where   = implode(' AND ',$where);
      
      // Build a SELECT
      $sql  = "SELECT skey
                 FROM ".ddTable_IDResolve($table)."
                WHERE ".$where;
      
      //hprint_r($sql);
      $records = SQL_AllRows($sql);
      if(count($records)<>1){
         echo "Invalid or non-unique key supplied\n<br>";
         return;
      }
      // Well, we haven't failed yet, let's add the skey before the update, just to be safe.
      
      $row['skey']   = $records[0]['skey'];
      $skey          = $records[0]['skey'];
      //hprint_r($row);
      SQLX_Update($table_dd,$row);
      
      if(Errors())   echo hErrors();
      else echo "Update Successful.  <a href=\"?gp_page=$table&gp_skey=$skey\">View Record</a> <br>\n";
   }
}
?>
