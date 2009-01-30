<?php
class x_report extends x_table2 {
   // Information passed in from outside
   var $report_id = '';
   var $display   = '';
   
   
   function x_report($report_id='',$display='') {
      $this->report_id=$report_id;
      $this->display  =$display;
      
      $this->flag_buffer=false;
   }
   
   // -- this version called from dispatcher
   function main() {
      $this->report_id=gp('report_id');
      $this->display  =gp('disp');
      $this->ehMain();
   }
   
   function ehMain() {
      // This is the only validation.
      $allowed=array('HTML','PDF','SQL','CSV');
      if(!in_array($this->display,$allowed)) {
         echo "Error: Report format may be any of ".implode(',',$allowed);
         return;
      }
      $rep = $this->report_id;

      // Get resolved table names based on security     
      $tReports = DDTable_IDResolve('reports');
      
      // Get Report Information
      $report = SQL_OneRow(
         "Select * from reports where report=".SQLFC($rep)
      );
      if(!$report) {
         echo "Error: Report ".hFormat($rep)." not on file";
      }
      $this->row_rep=$report;
      $this->hTitle =$report['description'];
      
      // Either do custom or run standard report 
      //if($report['custom']<>'') 
      if(false) {
         include($rep.'_rep.php');
      }
      else {
         $this->RepStandard();
      }
   }
   
   // =================================================================
   // Standard Report Runs
   // =================================================================
   function RepStandard() {
      // First Generate the query
      $sq=$this->RepStandardQuery();
      
      if($this->display=='SQL') {
         echo "<h1>The Generated SQL</h1>";
         hprint_r($sq);
         return;
      }
      // Now execute the query and run the report
      if($this->display=='CSV') {
         echo "<pre>";
         echo implode(',',$this->Cols)."\n";
         $res=SQL($sq);
         while($row=pg_fetch_row($res)) {
            echo implode(',',$row)."\n";  
         }
         echo "</pre>";
         return;
      }

      
      // Pull the info on breaking, sums, etc.
      $srep=SQLFC($this->report_id);
      $s2="SELECT rcl.column_id,rcl.reportlevel,rcl.summact
             FROM reportcollevels rcl
             JOIN reportcolumns   rc  ON rcl.column_id = rc.column_id
            WHERE rc.report = $srep
            ORDER BY rcl.reportlevel,rc.uicolseq";
      $breaks=SQL_AllRows($s2);
      $abreaks=array();
      foreach($breaks as $break) {
         if($break['summact']=='BREAK') {
            $abreaks[$break['reportlevel']]['breaks'][$break['column_id']]='';
         }
         else {
            $abreaks[$break['reportlevel']]['data'][$break['column_id']]
               =array('summact'=>$break['summact'],'val'=>0,'cnt'=>0);
         }
      }
      
      // There is always some setup, for either PDF or HTML, so do that
      // here.
      $this->RepHeader();

      // Now execute the query and run the report 
      $res=SQL($sq);
      $firstrow=true;
      while ($row=SQL_Fetch_Array($res)) {
         if($firstrow) {
            $firstrow=false;
            $this->RepStandardBreakLevelsInit($abreaks,$row);
         }
         else {
            $this->RepStandardBreakLevels($abreaks,$row);
         }
         $xpos=0;
         foreach($this->rows_col as $column_id=>$colinfo) {
            $disp=substr($row[$column_id],0,$colinfo['dispsize']);
            $disp=STR_PAD($disp,$colinfo['dispsize'],' ');
            $this->PlaceCell($xpos,$disp);
            $xpos += (2 + $colinfo['dispsize']);
         }
         $this->ehFlushLine();
         $this->RepStandardRowsToLevels($abreaks,$row);
      }
      $this->RepStandardBreakLevels($abreaks,$row,true);
      
      // There is always some cleanup, either PDF or HTML
      $this->RepFooter();

   }

   function RepStandardBreakLevelsInit(&$abreaks,$row) {
      foreach($abreaks as $lev=>$info) {
         foreach($info['breaks'] as $colname=>$colvalue) {
            $abreaks[$lev]['breaks'][$colname]=$row[$colname];
         }
      }
   }
   
   function RepStandardBreakLevels(&$abreaks,$row,$force=false) {
      $levels=array_keys($abreaks);
      foreach($levels as $lev) {
         // See if any values were changed, flip flag if true
         $tforce=$force;
         if(!$tforce) {
            foreach($abreaks[$lev]['breaks'] as $colname=>$colvalue) {
               if($colvalue<>$row[$colname]) {
                  //echo '-'.$colvalue.'-*-'.$row[$colname].'-'."\n";
                  $tforce=true;
                  break;
                  exit;
               }
            }
         }
         // Is flag now flipped?
         if($tforce) {
            $this->RepStandardBreak($abreaks,$lev,$row);
         }
      }
   }
   
   function RepStandardBreak(&$abreaks,$lev,$row) { 
      // Push out a row of dashes
      $this->PlaceCell(0,$this->hDashes);
      $this->ehFlushLine();
      $xpos=0;
      // For each column, put out either value or calculated value  
      foreach($this->rows_col as $colinfo) {
         $colname=$colinfo['column_id'];
         $ds=$colinfo['dispsize'];
         if(isset($abreaks[$lev]['breaks'][$colname])) {
            $this->PlaceCell($xpos,$abreaks[$lev]['breaks'][$colname]);
            $abreaks[$lev]['breaks'][$colname]=$row[$colname];
         }
         else {
            $val=$abreaks[$lev]['data'][$colname]['val'];
            $cnt=$abreaks[$lev]['data'][$colname]['cnt'];
            //echo $colname.'='.$abreaks[$lev]['data'][$colname]['summact'];
            switch($abreaks[$lev]['data'][$colname]['summact']) {
               case '-NA-': 
                  $d=str_repeat(' ',$ds); 
                  break; 
               case 'AVG': 
                  $d=$cnt==0 ? 0 : $val/$cnt;
                  $d=str_pad($d,$ds,' ',STR_PAD_LEFT);
                  break;
               case 'SUM':  
               case 'COUNT':
               case 'MIN':  
               case 'MAX':  
                  $d=str_pad($val,$ds,' ',STR_PAD_LEFT);
                  break;
               default:
                  $d=str_repeat(' ',$ds); 
            }
            $this->PlaceCell($xpos,$d);
            $xpos += (2 + $ds);
            $abreaks[$lev]['data'][$colname]['val']=0;
            $abreaks[$lev]['data'][$colname]['cnt']=0;
         }
      }
      $this->ehFlushLine();
      $this->ehFlushLine();   // A blank line after a summary line
   }


   
   function RepStandardRowsToLevels(&$abreaks,$row) {
      foreach($abreaks as $level=>$abreak) {
         foreach($abreak['data'] as $colname=>$colinfo) {
            $val=$colinfo['val'];
            $cnt=$colinfo['cnt'];
            $rwv=$row[$colname];
            
            switch($colinfo['summact']) {
               case '-NA-': break;
               case 'AVG':  $cnt++;
               case 'SUM':  $val+=$row[$colname];          break;
               case 'COUNT':$val++;                        break;
               case 'MIN':  $val=max($val,$row[$colname]); break;
               case 'MAX':  $val=min($val,$row[$colname]); break;
            }
            $abreaks[$level]['data'][$colname]['val']=$val;
            $abreaks[$level]['data'][$colname]['cnt']=$cnt;
         }
      }
   }
   
   

   function RepStandardQuery() {
      $sreport=SQLFC($this->report_id);
      
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
      $this->rows_col = $rows_col;
      foreach($this->rows_col as $key=>$colinfo) {
         $table_dd =dd_Tableref($colinfo['table_id']);
         $column_id=$colinfo['column_id'];
         if(intval($colinfo['dispsize'])==0) {
            $this->rows_col[$key]['dispsize']
               =$table_dd['flat'][$column_id]['dispsize'];
         }
      }
      
      // Make two header lines out of column information
      $aTemp=array();      
      foreach($this->rows_col as $colinfo) {
         $x = substr($colinfo['description'],0,$colinfo['dispsize']);
         $aTemp[]=str_pad(
            $x
            ,$colinfo['dispsize']
            ,' ',STR_PAD_RIGHT
         );
      }
      $this->hTitles = implode('  ',$aTemp);
      $aTemp=array();
      foreach($this->rows_col as $colinfo) {
         $aTemp[]=str_repeat('=',$colinfo['dispsize']);
      }
      $this->hDashes = implode('  ',$aTemp);
      $this->hWidth  = strlen($this->hDashes);
      $this->hTitle
         =str_repeat(' ',intval( ($this->hWidth-strlen($this->hTitle)) / 2))
         .$this->hTitle;
      
      // Go get the joins
      $SQL_FROMJOINS=$this->ehProcessFromJoins(array_keys($rows_tab));
      
      // Build a list of columns, and order-by columns, and filters'
      $this->Cols=array();
      $SQL_COLSA=array();
      $SQL_COLSOBA=array();
      $SQL_COLSWHA=array();
      foreach($rows_col as $row_col) {
         $this->Cols[]=$row_col['column_id'];
         $SQL_COLSA[]=$row_col['table_id'].'.'.$row_col['column_id'];
         if($row_col['flag_sort']=='Y') {
            //$SQL_COLSOBA[$row_col['uisort']]
            //   =$row_col['table_id'].'.'.$row_col['column_id'];
            $SQL_COLSOBA[]
               =$row_col['table_id'].'.'.$row_col['column_id'];
         }
         
         //if($row_col['compoper']<>'' && $row_col['compval']<>'') {
         //   $table_dd=DD_TableRef($row_col['table_id']);
         //   $ddcol=&$table_dd['flat'][$row_col['column_id']];
         //   $colval=SQL_Format($ddcol['type_id'],$row_col['compval']);
         //   $SQL_COLSWHA[]
         //      =$row_col['table_id'].'.'.$row_col['column_id']
         //      .$row_col['compoper']
         //      .$colval;
         //}
      }
      
      // Collapse the lists into strings
      $SQL_COLS=implode("\n       ,",$SQL_COLSA);
      $SQL_COLSOB='';
      if(count($SQL_COLSOBA)>0) {
         ksort($SQL_COLSOBA);
         $SQL_COLSOB="\n ORDER BY ".implode(',',$SQL_COLSOBA);
      }
      
      
      $SQL_WHERE='';
      if($this->row_rep['repfilters']<>'') {
         $SQL_WHERE="\n WHERE ".$this->row_rep['repfilters'];         
      }
      //if(count($SQL_COLSWHA)>0) {
      //   $SQL_WHERE="\n WHERE ".implode("\n       ",$SQL_COLSWHA);
      //}
      
      // Now build the final SQL
      $SQ=" SELECT "
         .$SQL_COLS
         .$SQL_FROMJOINS
         .$SQL_WHERE
         .$SQL_COLSOB;
         
      //echo $SQ;
      return $SQ;
   }
   
   function ehProcessFromJoins($tables) {
      // pop off the first element, make it the "FROM"
      $SQL_from = array_pop($tables);
      $SQL_Joins= array();
      $tables_done=array($SQL_from);
      
      // Continue as long as we have more tables, or until
      // the joining fails (an error)
      $tabcount=count($tables);
      while(count($tables) > 0) {
         //echo "<br/>Looping";
         //hprint_r($tables);
         // try to match each remaining table to any table we've got
         // so far
         foreach($tables as $table_index=>$table) {
            //echo "<br/>  Table $table";
            $table_par='';
            foreach($tables_done as $table_done) {
               //echo "<br/>  Table $table to $table_done?";
               // if we got a match, take care of business
               $table_par=$this->ehProcessFromJoins_Match($table,$table_done);
               //echo "<br/>  Got this as table_par: $table_par";
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
                  unset($tables[$table_index]);
                  break;
               }
            }
            if($table_par<>'') break;
         }
         
         // This means we failed to join any of the tables to any
         // other table, so something has gone wrong 
         //echo "<br>We are comparing $tabcount to ".count($tables);
         //hprint_r($tables);
         //hprint_r($tables_done);
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
      $sq="SELECT COUNT(*) as cnt FROM tabfky 
            WHERE (    table_id    ='$table' 
                   AND table_id_par='$table_done'
                   AND suffix='' AND prefix=''
                  )";
      $matched=SQL_oneValue('cnt',$sq);
      if($matched>0) {
         return $table_done;
      }

      $sq="SELECT COUNT(*) as cnt FROM tabfky 
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


   // =================================================================
   // Format-Neutral Output routines
   // =================================================================
   function ehFlushLine($increment=true) {
      if ($this->display=='HTML') {
         $line = implode('&nbsp;&nbsp;',$this->aLine);
         echo "$line\n";
      }
      else {
         $line = implode('  ',$this->aLine);
         $this->pdf->Cell(0,12,$line,0,1,"L");
      }
      
      // Clear out the line
      $this->aLine=array();
      $increment=$increment;
   }
   
   function PlaceCell($xpos,$value,$class='') {
      $xpos='';$class='';
      $this->aLine[]=$value;
      return;
   }
   
   function RepHeader() {
      if($this->display=='HTML') $this->RepHeaderHTML();
      else $this->RepHeaderPDF();
   }
   function RepFooter() {
      if($this->display=='HTML') $this->RepFooterHTML();
      else $this->RepFooterPDF();
   }

   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
   // HTML Functions
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   function RepHeaderHTML() {
      ?>
<?php echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml">
      <head>
      <style>
      div  { font: 10pt courier; text-align: left; }
      pre  { font: 10pt courier; text-align: left; }
      body { font: 10pt courier; text-align: left; }
      </style>
      </head>
      <body>
      <br>
      <?php
      echo "<pre>";
      echo "<b>".$this->hTitle."</b>\n\n";
      echo $this->hTitles."\n";
      echo $this->hDashes;
      echo "<div style=\"height: 35em; overflow: auto;\">";
   }

   function RepFooterHTML() {
      ?>
      </div><?php echo $this->hDashes?></pre>
      </body>
      </html>
      <?php
   }
   
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
   // PDF Functions
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   function RepHeaderPDF() {
      $orient=$this->row_rep['flag_land']=='Y' ? 'L' : 'P';
      $this->pdf = new Andro_PDF($orient,'pt','letter');
      $this->pdf->SetFont('Courier','',12);
      $this->pdf->SetMargins(36,36,36,36); 
      $title=$this->row_rep['description'];
      $this->pdf->hTitle =$this->hTitle;
      $this->pdf->hTitles=$this->hTitles;
      $this->pdf->hDashes=$this->hDashes;
      $this->pdf->hWidth =$this->hWidth;
      
      // This is weird.  If you don't do this, it adds pages just fine,
      // but skips the first page!
      $this->pdf->AddPage($orient);
   }

   function RepFooterPDF() {
      // 3/26/07, put in explicit options 
      $this->pdf->Output("report.pdf","I");
      //$this->pdf->Output();
   }
}

// ====================================================================
// ====================================================================
// EXTENSION OF FPDF.PHP for header and footer mostly
// ====================================================================
// ====================================================================
include_once('fpdf153/fpdf.php');
class Andro_PDF extends FPDF {
   function Header() {
      //header('Content-type: application/pdf');
      //header('Content-disposition: inline');
      $this->Cell(0,12,$this->hTitle,0,1,'L');
      $this->Ln();
      $line2
         ='Page: '.str_pad($this->PageNo(),5,' ',STR_PAD_LEFT)
         .str_repeat(' ',$this->hWidth-31)
         .date('D m/d/Y G:i',time());
      $this->Cell(0,12,$line2,0,1,"L");
      $this->Ln();
      $this->Cell(0,12,$this->hTitles,0,1,'L');
      $this->Cell(0,12,$this->hDashes,0,1,'L');
   }
   
   function Footer() {
      $this->Cell(0,12,$this->hDashes,0,1,'L');
   }
}
?>
