<?php
class report_run extends x_table2 {
   // ---------------------------------------------------------------------
   // MAIN
   // ---------------------------------------------------------------------
   function main() {
      // First pull the report
      $row_rep=SQL_OneRow("SELECT * from reports where skey=".gp('gp_skey'));
      $sreport=SQL_Format('char',$row_rep['report']);
      
      // Now the tables
      $rows_tab=SQL_AllRows(
         "SELECT * From reporttables WHERE report=$sreport"
      );
      $rows_tab=KeyRowsFromRows($rows_tab,'table_id');
    
      // Now all columns
      $rows_col=SQL_AllRows(
         "SELECT * From reportcolumns WHERE report=$sreport
           ORDER BY uicolseq "
      );
      
      // Go get the joins
      $SQL_FROMJOINS=$this->ehProcessFromJoins(array_keys($rows_tab));
      
      // Build a list of columns, and order-by columns, and filters
      $SQL_COLSA=array();
      $SQL_COLSOBA=array();
      $SQL_COLSWHA=array();
      foreach($rows_col as $row_col) {
         $SQL_COLSA[]=$row_col['table_id'].'.'.$row_col['column_id'];
         if($row_col['uisort']<>0) {
            $SQL_COLSOBA[$row_col['uisort']]
               =$row_col['table_id'].'.'.$row_col['column_id'];
         }
         
         if($row_col['compoper']<>'' && $row_col['compval']<>'') {
            $table_dd=DD_TableRef($row_col['table_id']);
            $ddcol=&$table_dd['flat'][$row_col['column_id']];
            $colval=SQL_Format($ddcol['type_id'],$row_col['compval']);
            $SQL_COLSWHA[]
               =$row_col['table_id'].'.'.$row_col['column_id']
               .$row_col['compoper']
               .$colval;
         }
      }
      
      // Collapse the lists into strings
      $SQL_COLS=implode("\n       ,",$SQL_COLSA);
      $SQL_COLSOB='';
      if(count($SQL_COLSOBA)>0) {
         ksort($SQL_COLSOBA);
         $SQL_COLSOB="\n ORDER BY ".implode(',',$SQL_COLSOBA);
      }
      
      $SQL_WHERE='';
      if(count($SQL_COLSWHA)>0) {
         $SQL_WHERE="\n WHERE ".implode("\n       ",$SQL_COLSWHA);
      }
      
      // Now build the final SQL
      $SQ=" SELECT "
         .$SQL_COLS
         .$SQL_FROMJOINS
         .$SQL_WHERE
         .$SQL_COLSOB;
         
      //echo $SQ;

      // Display
      $this->ehProcessDisplay($SQ,$rows_col,$row_rep);
   }
   
   
   // ---------------------------------------------------------------------
   // Examine the tables involved in the query and find join conditions
   // to put them together
   // ---------------------------------------------------------------------
   function ehProcessFromJoins($tables) {
      // pop off the first element, make it the "FROM"
      $SQL_from = array_pop($tables);
      $SQL_Joins= array();
      $tables_done=array($SQL_from);
      
      // Continue as long as we have more tables, or until
      // the joining fails (an error)
      $tabcount=count($tables);
      while(count($tables) > 0) {
         // try to match each remaining table to any table we've got
         // so far
         foreach($tables as $table) {
            $table_par='';
            foreach($tables_done as $table_done) {
               // if we got a match, take care of business
               $table_par=$this->ehProcessFromJoins_Match($table,$table_done);
               if($table_par<>''){
                  $table_chd=$table_par==$table_done ? $table : $table_done;
                  $dd=dd_tableref($table_par);
                  $apks=explode(',',$dd['pks']);
                  $apks2=array();
                  foreach($apks as $apk) {
                     $apks2[]="$table_chd.$apk = $table_par.$apk";
                  }
                  $SQL_Joins[$table] = implode(' AND ',$apks2); 
                  
                  // Move over to the "done" pile
                  $tables_done[] = $table;
                  unset($tables[$table]);
                  break;
               }
            }
            if($table_par<>'') break;
         }
         
         // This means we failed to join any of the tables to any
         // other table, so something has gone wrong 
         if($tabcount==count($tables)) {
            break;
         }
      }
      
      // Now join them all up and return
      $retval = "\n  FROM $SQL_from ";
      foreach($SQL_Joins as $table_id=>$SQL_Join) {
         $retval.="\n  JOIN $table_id ON $SQL_Join";
      }
      return $retval;
   }
   
   
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // Examine the tables involved in the query and find join conditions
   // to put them together
   function ehProcessFromJoins_Match($table,$table_done) {
      // See if these two tables join as either parent or child
      $sq="SELECT COUNT(*) as cnt FROM zdd.tabfky_c 
            WHERE (    table_id    ='$table' 
                   AND table_id_par='$table_done'
                   AND suffix='' AND prefix=''
                  )";
      $matched=SQL_oneValue('cnt',$sq);
      if($matched>0) {
         return $table_done;
      }

      $sq="SELECT COUNT(*) as cnt FROM zdd.tabfky_c 
            WHERE (    table_id_par='$table' 
                   AND table_id    ='$table_done'
                   AND suffix='' AND prefix=''
                  )";
      $matched=SQL_oneValue('cnt',$sq);
      if($matched>0) {
         return $table;
      }
      
      return '';
   }
   
   
   // ---------------------------------------------------------------------
   // DISPLAY OUTPUT
   // The big question is, should online and printed be two separate
   // routines?  We'd say yes because the header/footer are so different
   // for each, but we'd say no because the processing/breaking is so much
   // the same for each.  So we will try as one routine for now and see
   // how it works out
   //
   // ---------------------------------------------------------------------
   function ehProcessDisplay($SQ,$rows_col,$row_rep) {
      $gp_process=gp('gp_process');
      $this->dispmode=$gp_process==1 ? 'screen' : 'print';
      
      // Set the template
      $html_main
         = gp('gp_process')==1 
         ? 'html_skin_tc_prscreen'
         : 'html_print';
      vgaSet('html_main',$html_main);
         
      // Execute the query, but do not retrieve 
      $result=SQL($SQ);
      
      // Two counters:  PageNum, and RowNum, the row number
      // inside of a page.
      $this->PageNum = 1;
      $this->RowNum = 1;
      $this->RowsPerPage=66;
      
      // In all cases, begin with a header
      $this->ehPDInit();
      if($this->PageNum==1) {
         $this->ehPDHeader($row_rep,$rows_col);
      }
      
      //  This is the basic output loop
      while ($row=SQL_Fetch_Array($result)) {
         arrayStripNumericIndexes($row);
         $this->ehPDRow($row);
         
         // Always increase row count.  For PDF, look for reset
         $RowNum++;
         if ($dispmode=='print') {
            if($RowNum>$RowsPerPage) {
               $this->ehPDFooter();
               $this->RowNum = 1;
               $this->PageNum++;
               $this->ehPDHeader($PageNum);
            }
         }
      }
      
      // In all Cases, end with a footer
      $this->ehPDFooter();
      $this->ehPDClose();
   }
   

   function ehPDInit() {
       echo "<pre>";
   }
   
   function ehPDClose() {
       echo "</pre>";
   }

   function ehPDHeader($row_rep,$rows_col) {
      //hprint_r($rows_col);
      echo "<h1>".$row_rep['description']."</h1>";
      $hcols=array();
      $line1 = $line2 = "";
      foreach($rows_col as $row_col) {
         $ds=$row_col['dispsize'];
         $line1.=str_pad($row_col['description'],$ds,' ').' ';
         $line2.=str_pad('',$row_col['dispsize'],'=')." ";
      }
      echo $line1."\n";
      echo $line2."\n";
   }
   
   function ehPDFooter() {
      echo "\nEnd of Report\n";
      
   }
   
   function ehPDRow($row) {
      foreach ($row as $colname=>$colvalue) {
         echo $colvalue." ";
      }
      echo "\n";
   }
}
?>
