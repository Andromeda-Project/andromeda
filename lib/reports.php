<?php
class reports extends x_table2 {
   // ---------------------------------------------------------------------
   // Basic overrides
   // ---------------------------------------------------------------------
   function custom_construct() {
      if(gp('gp_ajax')<>'') $this->flag_buffer=false;
      $this->Ajax_notice='';
   }

   function main() {
      if(gp('gp_process',0)>0) return $this->ehProcess();
      if(gp('gp_ajax')<>'') return $this->ehAjax();
      parent::main();
   }
  
   // ---------------------------------------------------------------------
   // ---------------------------------------------------------------------
   // ---------------------------------------------------------------------
   // MAJOR ROUTINE 1 OF 2: DISPLAY A REPORT FOR EDITING
   // Override method ehMain, the main output.  Hijack output for update
   // mode only
   // ---------------------------------------------------------------------
   // ---------------------------------------------------------------------
   // ---------------------------------------------------------------------
   function ehMain() {
      hidden('gp_page','reports');
      hidden('gp_skey',gp('gp_skey'));
      if($this->mode<>'upd') {
         parent::ehMain();
         return;
      }

      // Reset this, set it later      
      vgfSet('HTML_focus','');

      ?>
      <script>
      function TableAdd() {
         TableX=ob('sel_tablesavl').value
         if(TableX=='') {
            alert("Please choose a table from the list first");
         }
         else {
            sndReq('&gp_ajax=tableadd&gp_rtable='+TableX);
         }
      }
      function TableDel() {
         TableX=ob('sel_tablesrep').value
         if(TableX=='') {
            alert("Please choose a table from the list first");
         }
         else {
            sndReq('&gp_ajax=tabledel&gp_rtable='+TableX);
         }
      }
      function ColumnAdd() {
         ColumnX=ob('sel_columnsavl').value
         if(ColumnX=='') {
            alert("Please choose a column from the list first");
         }
         else {
            sndReq('&gp_ajax=columnadd&gp_rcolumn='+ColumnX);
         }
      }
      function ColumnSort() {
         ColumnX=ob('sel_columnsrep').value
         if(ColumnX=='') {
            alert("Please choose a column from the list first");
         }
         else {
            sndReq('&gp_ajax=columnsort&gp_rcolumn='+ColumnX);
         }
      }
      function ColumnUnSort() {
         ColumnX=ob('sel_colsort').value
         if(ColumnX=='') {
            alert("Please choose a column from the list first");
         }
         else {
            sndReq('&gp_ajax=columnunsort&gp_rcolumn='+ColumnX);
         }
      }
      
      
      </script>
      <h1><?php echo $this->PageSubtitle?></h1>
      <table cellpadding=0 cellspacing=0 width=100%  
             style="border-collapse:collapse">
        <tr>
          <td height="40" valign=top><?php echo $this->h['ButtonBar']?>
        </tr>
        <tr>
          <td>
            <hr/>
            <div class="x2menubar" id="x2tabbar" name="x2tabbar">
            <?php echo $this->ehTabbar(false);?>
            <hr/>
            </div>
          </td>
        </tr>
        <tr><td>&nbsp;
        <tr>
          <td class="x2_content" id="x2_content" name="x2_content">
          <?php echo $this->ehTab_Main()?>
          </td>
        </tr>
      </table>
      <?php
      return;
   }

   // ---------------------------------------------------------------------
   // ---------------------------------------------------------------------
   // Main ajax dispatcher
   // ---------------------------------------------------------------------
   // ---------------------------------------------------------------------
   /**
   function:ehAjax
   
   Universal dispatcher for ajax functions related to reports.  
   */
   function ehAjax() {
      switch(gp('gp_ajax')) {
         // All tabs covered in this routine
         case 'tab':           $this->ehAjax_tabs();     break;
         // Main tab: save report description
         case 'repdesc':       $this->Ajax_RepDesc();   break;
         // Tables tab: clicking a table shows related tables
         case 'tablesavl':     $this->ehTablesAvl();   break;
         case 'tabledel':      $this->TableDel();      break;
         case 'tableadd':      $this->TableAdd();      break;

         // Columns tab:  Adding a column
         case 'columnadd':     $this->Ajax_ColumnAdd(); break;
         case 'colup':
         case 'coldn':         $this->ehAjax_Colmove();  break;
         case 'coldl':         $this->ehAjax_ColDel();   break;
         case 'colsort':       $this->ehAjax_ColSort();  break;
         case 'colsave':       $this->Ajax_ColSave(); break;
         
         // Levels Change
         case 'levmod':
         case 'levadd':
         case 'levdel':        $this->ehAjax_Levels(); break;
         
         // Sorting tab: Remove or add a column
         case 'columnsort':
         case 'columnunsort':  $this->ehAjax_Sort();     break;
         
         // Filters
         case 'filtrep':
         case 'filtlev':        $this->ehTab_Filters(); break;
       
         
         case 'echo': 
            echo 'echo|--'.gp('gp_ajax_echo').'--'; break;
      }
   }

   // ---------------------------------------------------------------------
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // AJAX Processing for complete tabs
   // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
   // ---------------------------------------------------------------------
   function ehAjax_Tabs() {
      // In all cases generate the tabbar
      $this->ehTabBar();
      echo "|-|";
      echo "x2_content|";
      
      switch(gp('gp_ajax_tab')) {
         case 'Main':     $this->ehTab_Main();   break;
         case 'Tables':   $this->ehTab_Tables(); break;
         case 'Columns':  $this->ehTab_columns();break;
         //case 'Sorting':  $this->ehTab_Sort(); break; 
         case 'Levels':   $this->ehTab_Levels(false); break; 
         case 'Filters':  $this->ehTab_Filters(false);break;
      }
   }
   
   // ---------- TAB: MAIN -------------------------
   function ehTab_Main() {
      $skey=SQL_Format('numb',gp('gp_skey'));
      $this->row=SQL_OneRow("Select * from reports where skey=$skey");
      $desc=SQL_OneValue('description'
         ,"Select description from reports where skey=$skey"
      );
      $flnd=SQL_OneValue('flag_land'
         ,"Select flag_land from reports where skey=$skey"
      );
      $h1=hLinkpopup(
         ''
         ,'Run On-Screen Report'
         ,'gp_page=report_run&gp_process=1&gp_skey='.$skey
      );
      $h2=hLinkpopup(
         ''
         ,'Run Printable Report'
         ,'gp_page=report_run&gp_process=2&gp_skey='.$skey
      );
      $h3=hLinkpopup(
         ''
         ,'Print On-Screen Report'
         ,'gp_page=x_report&disp=HTML&report_id='.urlencode($this->row['report'])
      );
      $h4=hLinkpopup(
         ''
         ,'Print PDF Report'
         ,'gp_page=x_report&disp=PDF&report_id='.urlencode($this->row['report'])
      );
      $h4b=hLinkpopup(
         ''
         ,'Output CSV file'
         ,'gp_page=x_report&disp=CSV&report_id='.urlencode($this->row['report'])
      );
      $h4c=hLinkpopup(
         ''
         ,'View Generated SQL'
         ,'gp_page=x_report&disp=SQL&report_id='.urlencode($this->row['report'])
      );
      $h5="?gp_ajaxsql=update"
         ."&gp_table=reports"
         ."&txt_skey=".$skey
         ."&txt_flag_land=b:'+this.checked";
      ?>
      <table width=100%>
        <tr>
          <td>
          Report Description:
          <br>
          <input value="<?php echo $desc?>" name="ajx_description"
                 onblur="sndReq('&gp_ajax=repdesc&gp_new='+this.value)">
          <br/><br/>
          Landscape: <input type="checkbox" <?php echo ($flnd=='Y'?'CHECKED':'')?> 
                 name="ajax_checkbox"
                 onchange="andrax('<?php echo $h5?>)">
          </td>
          <td>
            <?php echo $h3?><br><br>
            <?php echo $h4?><br><br>
            <?php echo $h4b?><br><br>
            <?php echo $h4c?><br><br>
          </td>
        </tr>
      </table>         
      <?php
   }

   function Ajax_RepDesc() {
      $desc=SQL_Format('char',gp('gp_new'));
      $skey=SQL_Format('numb',gp('gp_skey'));
      $sq="UPDATE reports set description = $desc where skey=$skey";
      SQL($sq);
      //echo "echo|$sq";
   }

   // ---------- TAB: Table Selection -------------------------
   function ehTab_Tables() {
      $skey=SQL_Format('numb',gp('gp_skey'));
      
      ?>
      <table width=100%>
        <tr>
          <td >
            Tables Currently In this Report:
            <div id="ajx_tablesrep" name="ajx_tablesrep">
            <?php echo $this->ehTablesRep(false)?>
            </div>
          <td style="vertical-align: middle">
            <input type="button" value="&lt;&lt; Add"
                   onclick="TableAdd()";
                   style="font-family: fixed">
            <br>
            <br>
            <input type="button" value="Del &gt;&gt;"
                   onclick="TableDel()";
                   style="font-family: fixed">
          <td>
            Tables That Can Be Added To This Report
            <div id="ajx_tablesavl" name="ajx_tablesavl">
            <?php echo $this->ehTablesAvl(false)?>
        </tr>
      <?php
   }
   
   
   function ehTablesRep($ajax=true) {
      if($ajax) echo "ajx_tablesrep|";
      
      $gp_skey=SQL_Format('numb',gp('gp_skey'));
      // Now the tables
      $dr=SQL(
         "SELECT rt.table_id,tab.description
            From reporttables rt
            JOIN reports r ON r.report = rt.report
            JOIN tables tab ON rt.table_id = tab.table_id
           WHERE r.skey=$gp_skey"
      );
      $retval='';
      while ($row=SQL_Fetch_Array($dr)) {
         $retval .="\n<option value=\"".$row['table_id']."\">"
            .$row['description']
            ."</option>";
      }
      echo  
         '<select name="sel_tablesrep" id="sel_tablesrep"
            multiple size=7 style="width: 300px">' 
         .$retval
         ."\n</select>";
   }

   function ehTablesAvl($ajax=true) {
      $skey    =SQL_Format('numb',gp('gp_skey'));

      // Get the list of tables that are in the report,
      // get them into list version, like 'tablea','table2','tabled'...
      $tables=SQL_AllRows(
         "SELECT table_id 
            FROM reporttables rt
            JOIN reports r  ON r.report = rt.report
           WHERE r.skey = $skey"
      );
      $atables=array();
      foreach($tables as $table) {
         $atables[] = "'".trim($table['table_id'])."'";
      }
      $stables=implode(',',$atables);
      
      
      if($stables<>'') {
         // They requested a particular table
         $sq=
            "select distinct t.table_id,t.description
               FROM tables t
               JOIN (
                     SELECT table_id,table_id_par 
                       FROM tabfky
                      UNION ALL
                     SELECT table_id_par as table_id,table_id as table_id_par
                       FROM tabfky
                     ) fky
                  ON t.table_id = fky.table_id
               WHERE fky.table_id_par in ($stables)
                 AND t.table_id NOT IN ($stables) 
               ORDER BY t.description";
      } 
      else {
         $sq="select table_id,description
                  FROM tables 
                 order by description";
      }
      $dbres=SQL($sq);
      $retval='';
      while($row=SQL_Fetch_Array($dbres)) {
         $retval
            .="\n<option value=\"".$row['table_id']."\">"
            .$row['description']
            ."</option>";
      }

      // Echo the output
      //echo "echo|$sq";
      //return;
      if($ajax) echo "ajx_tablesavl|";
      echo 
         "<select multiple size=7 style=\"width: 300px\"
                  name=\"sel_tablesavl\" id=\"sel_tablesavl\"
                  >"
         .$retval
         ."</select>";
   }
   
   function TableDel() {
      $table_id=SQL_Format('char',gp('gp_rtable'));
      $skey=SQL_Format('numb',gp('gp_skey'));

      $r=SQL_Onevalue('report',"Select report from reports where skey=$skey");
      $sr=SQL_Format('char',$r);
      SQL("delete from reportcolumns where table_id=$table_id 
             and report = $sr"
      );
      SQL("delete from reporttables where table_id=$table_id 
             and report = $sr"
      );
      
      // Force a re-display of available tables, the one we were
      // looking at just got deleted.
      $this->ehTablesAvl();
      echo "|-|";
      $this->ehTablesRep();
   }
   
   function TableAdd() {
      $table_id=SQL_Format('char',gp('gp_rtable'));
      $skey=SQL_Format('numb',gp('gp_skey'));

      SQL(
         "INSERT INTO reporttables (report,table_id) 
          SELECT r.report,$table_id FROM reports r
          WHERE r.skey = $skey"
      );
      
      // Force a re-display only of tables in report
      $this->ehTablesAvl();
      echo "|-|";
      $this->ehTablesRep();
   }

   // ---------- TAB: COLUMNS -------------------------
   function ehTab_Columns() {
      $skey=SQL_Format('numb',gp('gp_skey'));
      $cols=SQL_AllRows(
         "SELECT  t.description    as tabdesc
                 ,flat.description as coldesc
                 ,t.table_id
                 ,flat.column_id
            FROM reports r
            JOIN reporttables  rt   ON r.report      = rt.report
            JOIN tables        t    ON rt.table_id   = t.table_id
            JOIN tabflat       flat ON rt.table_id   = flat.table_id
           WHERE r.skey=$skey
             AND flat.columN_id not in ('skey','skey_quiet','_agg')
           ORDER by t.description,flat.description" 
      );
      $hOpts='';
      //hprint_r($cols);
      foreach($cols as $col) {
         $hOpts
            .="<option value=\"".$col['table_id'].":".$col['column_id']."\">"
            ."(".$col['tabdesc'].") ".$col['coldesc']
            ."</option>";
      }
      
      ?>   
      <table width=100%>
      <tr>
        <td style="vertical-align: top">
           <br>
           <br>
           <div name="ajx_columnsrep" id="ajx_columnsrep">
           <?php echo $this->ehColumns(false)?>
           </div>
        </td>
        <td style="vertical-align: top; text-align: center; width: 80px">
            <br>
            <br>
            <br>
            <input type="button" value="&lt;&lt; Add"
                   onclick="ColumnAdd()";
                   style="font-family: fixed">
        <td style="width:224px" style="vertical-align: top">
           <br>Columns that can be added to the report:</b><br><br>
           <select size=20 style="width:220px"
                   name="sel_columnsavl" id="sel_columnsavl"
                  >
           <?php echo $hOpts?>
           </select>
        </td>
      </tr>
      </table>
      <?php
   }

   /**
   function:hAjax_Columns
   returns:string AJAX_Element
   
   Returns a complete HTML table element listing all columns in a report.
   Each time this is called it completely replaces what came before.
   */
   function ehColumns($ajax=true) {
      if($ajax) echo "ajx_columnsrep|"; 
      $gp_skey=SQL_Format('numb',gp('gp_skey'));

      //$retval=$this->repColTitles();
      echo
         "<table width=100%>"
         .hTRFromArray('dhead'
            ,array('Delete','Up','Column','Down','Caption','Size','Sort')
         );
      
      $sq="SELECT rc.*
                  ,tab.description as tdesc
                  ,col.description as cdesc
             from reportcolumns rc
             JOIN reports   r ON r.report     = rc.report
             JOIN tables  tab ON rc.table_id  = tab.table_id
             JOIN tabflat col ON rc.table_id  = col.table_id
                                  AND rc.column_id = col.column_id
            WHERE r.skey=$gp_skey
            ORDER BY rc.uicolseq";
      $rows=SQL_AllRows($sq);
      $retval="";
      foreach($rows as $row) {
         $coldl="&gp_ajax=coldl&gp_skcol=".$row['skey'];
         $colup="&gp_ajax=colup"
            ."&gp_csorg=".trim($row['uicolseq'])."&gp_skorg=".$row['skey'];
         $coldn="&gp_ajax=coldn"
            ."&gp_csorg=".trim($row['uicolseq'])."&gp_skorg=".$row['skey'];
         $CHKD = $row['flag_sort']=='Y' ? 'CHECKED' : '';
         $colst="&gp_ajax=colsort&gp_skcol=".$row['skey'];

         /*
         // Make the drop-down for summary action
         $hsumm="<SELECT onchange=\"sndReq("
            ."'&gp_ajax=colsave&gp_ajxcol=summact"
            ."&gp_ajxval='+this.value+'&gp_ajxsky=".$row['skey']."')\">"
            ."<option value=\"-NA-\">&nbsp;</option>";
         $asumm=array('SUM','COUNT','MIN','MAX','AVG');
         foreach($asumm as $summ) {
            $sel=trim($row['summact'])==$summ ? ' SELECTED ' : '';
            $hsumm.="\n<OPTION $sel value=\"$summ\">$summ</OPTION>";
         }
         $hsumm.="</SELECT>";
         */
         
         $hCC="'&gp_ajax=colsave"
            ."&gp_colsk=".$row['skey']
            ."&gp_col=description"
            ."&gp_val='+this.value";
         $hDS="'&gp_ajax=colsave"
            ."&gp_colsk=".$row['skey']
            ."&gp_col=dispsize"
            ."&gp_val='+this.value";
         
         ?>
         <tr>
         <td><a href="javascript:sndReq('<?php echo $coldl?>')">delete</a>
         <td><a href="javascript:sndReq('<?php echo $colup?>')">up</a>&nbsp;&nbsp;
         <td><?php echo "(".$row['tdesc'].") ".$row['cdesc']?>
         <td><a href="javascript:sndReq('<?php echo $coldn?>')">down</a>
         <td><input value="<?php echo $row['description']?>" size=10
                    onblur="sndReq(<?php echo $hCC?>)">
         <td><input value="<?php echo $row['dispsize']?>" maxlength=3 size=3
                    onblur="sndReq(<?php echo $hDS?>)">
         <td><input type=checkbox <?php echo $CHKD?> value="Y"
               onchange="javascript:sndReq('<?php echo $colst?>')">
         <?php
      }
      echo "</table>";
   }
   
   function Ajax_ColumnAdd() {
      $gp=gp('gp_rcolumn');
      $skey=gp('gp_skey');
      list($table_id,$column_id) = explode(':',$gp);
      
      $maxui=SQL_OneValue('ui',
         "select max(uicolseq) as ui
            FROM reportcolumns"
      );
      $maxui=is_null($maxui) ? 1 : $maxui+1;
      $maxui=str_pad($maxui,4,STR_PAD_LEFT,'0');
      
      $sq="insert into reportcolumns
            (report,table_id,column_id,uicolseq,description)
           SELECT r.report,'$table_id','$column_id','$maxui',f.description
             FROM reports r,tabflat f
            WHERE r.skey=$skey
              AND f.table_id='$table_id'
              AND f.column_id='$column_id'";
      SQL($sq);
      //echo($sq);
      //echo hErrors();
      
      // Redraw the list of columns
      $this->ehColumns();
   }
   
   /**
   function:hAjax_Colmove
   returns:string AJAX_Element
   
   Moves a column up or down by altering its uicolseq
   */
   function ehAjax_ColMove() {
      $gp_skey = gp('gp_skey');
      $cs_org  = gp('gp_csorg');
      //$cs_org  = str_pad($cs_org,4,STR_PAD_LEFT,'0');
      $sk_org  = gp('gp_skorg');
      if(gp('gp_ajax')=='coldn') {
         $sq="select rc.skey,rc.uicolseq 
               from reportcolumns rc 
               JOIN reports r  ON r.report = rc.report
              WHERE r.skey = $gp_skey
                AND rc.uicolseq > '$cs_org'
              ORDER BY rc.uicolseq ASC LIMIT 1";
      }
      else {
         $sq="select rc.skey,rc.uicolseq 
               from reportcolumns rc 
               JOIN reports r  ON r.report = rc.report
              WHERE r.skey = $gp_skey
                AND rc.uicolseq < '$cs_org'
              ORDER BY rc.uicolseq DESC LIMIT 1";
      }
      $row=SQL_OneRow($sq);
      $swap_sk=$row['skey'];
      $swap_cs=$row['uicolseq'];

      $sq="update reportcolumns set uicolseq='$cs_org'  where skey=".$swap_sk;
      SQL($sq);
      $sq="update reportcolumns set uicolseq='$swap_cs' where skey=".$sk_org;
      SQL($sq);

      $this->ehColumns();      
   }

   
   /**
   function:ehAjax_Coldel
   returns:string AJAX_Element
   
   Delete a column from a report
   */
   function ehAjax_ColDel() {
      $sk_org  = SQL_Format('numb',gp('gp_skcol'));
      SQL("DELETE FROM reportcolumns where skey=$sk_org");
      echo $this->ehColumns();      
   }
   
   /**
   function:ehAjax_ColSort
   
   Flips the sort flag (flag_sort) for a given skey
   */
   function ehAjax_ColSort() {
      $skey=SQLFN(gp('gp_skcol'));
      SQL("update reportcolumns set flag_sort =
             CASE WHEN COALESCE(flag_sort,'N')='N' THEN 'Y' ELSE 'N' END
            WHERE skey = $skey");
      $this->ehColumns();      
   }

   
   /**
   function:Ajax_ColSave
   
   saves a single column of a single row.  Does no refresh
   */
   function Ajax_ColSave() {
      $cskey=gp('gp_colsk');
      $colnm=gp('gp_col');
      $colvl=gp('gp_val');
      SQL("update reportcolumns set $colnm='$colvl' where skey=$cskey");
   }
   
   
   // ---------- TAB: Levels/Breaking Definitions
   function ehTab_Levels($ajax=true) {
      if($ajax) echo "x2_content|"; 
      
      // First get basic rows, and start an array that contains level info
      $skey=SQLFN(gp('gp_skey'));
      $sq="SELECT rc.* 
             FROM reportcolumns rc
             JOIN reports       r  ON rc.report = r.report
            WHERE r.skey = $skey
            ORDER BY rc.uicolseq";
      $rows=SQL_AllRows($sq,'column_id');
      $hCols=array();
      foreach($rows as $row) {
         $hCols[$row['column_id']]=array($row['description']);
      }
      
      // Now get the previously defined levels and slot them out
      // into the array for display
      $hTitles=array('Column');
      $sq="SELECT cl.* 
             FROM reportcollevels cl 
             JOIN reports         r  ON cl.report = r.report
            WHERE r.skey = $skey
            ORDER BY cl.reportlevel";
      $levels=SQL_AllRows($sq);
      foreach($levels as $lev) {
         $js="javascript:sndReq('"
            ."&gp_ajax=levmod"
            ."&gp_skeycl=".$lev['skey']
            ."&gp_value='+this.value"
            .")";
         $hx="<Select onchange=\"$js\">"
            .hOptionsFromTable(
               'summacts'
               ,$lev['summact']
            )
            ."</select>";
         $hCols[$lev['column_id']][$lev['reportlevel']]=$hx;

         $hTitles[$lev['reportlevel']]='Level '.$lev['reportlevel'];
      }
      
      ?>
      <br>
      <a href="javascript:sndReq('&gp_ajax=levadd')">Add Level</a>
      &nbsp;&nbsp;&nbsp;
      <a href="javascript:sndReq('&gp_ajax=levdel')">Remove Level</a>
      <br><br>
      <table>
        <tr>
        <?php
         foreach($hTitles as $hTitle) {
            echo "<td class='dhead' style='width:10em'>$hTitle</td>";
         }
         echo "</tr>";
         foreach($hCols as $hCol) {
            echo hTRFromArray('',$hCol);
         }
         ?>
      </table>
      <?php
   }

   function ehAjax_Levels() {
      $skey=SQLFN(gp('gp_skey'));
      $report=SQL_OneValue('report'
         ,"Select report from reports where skey=$skey"
      );
      $levaction=gp('gp_ajax');
      $sq="SELECT max(reportlevel) as maxlev 
            FROM reportcollevels WHERE report = '$report'";
      $maxlev=SQL_AllRows($sq);
      $maxlev=count($maxlev)==0 ? 1 : $maxlev[0]['maxlev'];
      if($levaction=='levadd') {
         //if(is_null($maxlev)) $maxlev=1; else $maxlev++;
         $maxlev++;
         SQL("INSERT INTO reportcollevels 
                     (report,table_id,column_id,reportlevel,summact)
              SELECT rc.report,rc.table_id,rc.column_id,$maxlev,'-NA-'
                FROM reportcolumns rc
               WHERE report='$report'"
         );
         echo hErrors();
      }
      elseif($levaction=='levdel') {
         //echo "hi".$maxlev.'hi';
         $sq="Delete from reportcollevels
               where report='$report'
                 AND reportlevel = $maxlev";
         SQL($sq);
         //echo $sq;
        
         //if(Errors()) echo hErrors();
      }
      elseif($levaction=='levmod') {
         $skeycl=SQLFN(gp('gp_skeycl'));
         $newval=SQLFC(gp('gp_value'));
         SQL(
            "UPDATE reportcollevels 
                SET summact = $newval
              WHERE skey=$skeycl"
         );
      }
      $this->ehTab_Levels();
   }


   // ---------- TAB: FILTERS ---------------------
   // DEFUNCT, use new code now
   function ehTab_Filters($ajax=true) {
      if($ajax) echo "x2_content|"; 

      $skey=SQLFN(gp('gp_skey'));
      $report=SQL_OneValue('report'
         ,"Select report from reports where skey=$skey"
      );
      
      // Do any processing that may have come through
      if(gp('gp_ajax')=='filtrep') {
         $repfilters=SQLFC(gp('gp_val'));
         //echo "we are setting $repfilters";
         SQL("UPDATE reports SET repfilters=$repfilters WHERE skey=$skey");
      }
      if(gp('gp_ajax')=='filtlev') {
         $repfilters=SQLFC(gp('gp_val'));
         $skeylev=SQLFN(gp('gp_skeylev'));
         SQL("UPDATE reportlevels 
                 SET repfilters=$repfilters WHERE skey=$skeylev"
         );
      }
      

      // Retrieve and display
      $repfilter=SQL_OneValue('repfilters'
         ,"Select skey,repfilters from reports where skey=$skey"
      );
      $levs=SQL_AllRows(
         "Select rl.skey,rl.reportlevel,rl.repfilters 
           from reportlevels    rl
          WHERE exists ( SELECT * from reportcollevels
                          WHERE report = '$report'
                            AND reportlevel = rl.reportlevel)
            AND report='$report'"
      );
      //echo hErrors();
      
      // Now for each level list some filters
      ?>
      <table>
        <tr>
          <td class="dhead" style="width: 10em">Level</td>
          <td class="dhead">SQL Filters</td>
          <td class="dhead">Save</td>
        </tr>
        <tr>
          <td valign=top>Base</td>
          <td><textarea id='lev0' name="lev0"
                   cols=60
                   rows=5 
                  style="border:1px solid gray"
                ><?php echo $repfilter?></textarea></td>
           <td><a href="javascript:sndReq('&gp_ajax=filtrep&gp_val='+encodeURIComponent(ob('lev0').value))">Save</a>

        </tr>
      <?php
      foreach($levs as $lev) {
         $js='&gp_ajax=filtlev&gp_skeylev='.$lev['skey'];
         ?>
         <tr>
           <td valign=top><?php echo $lev['reportlevel']?></td>
           <td><textarea 
                    cols=60 
                    rows=5 
                   style="border:1px solid gray"
               onchange="sndReq('<?php echo $js?>&gp_val='+this.value)"
                 ><?php echo $lev['repfilters']?></textarea></td>
         </tr>
         <?php
      }
      ?>
      </table>
      <br/>
      <h3>Columns in this report</h3>
      <table style="width:50%">
        <tr>
          <td class="dhead">Description</td>
          <td class="dhead">Table</td>
          <td class="dhead">Column</td>
        </tr>
        <?php
         // Columns in this report
         $sql="SELECT description,table_id,column_id from reportcolumns
                WHERE report = '$report'
                ORDER BY description";
         $cols=SQL_Allrows($sql);
         foreach($cols as $col) {
            ?>
            <tr><td><?php echo $col['description']?>
                <td><?php echo $col['table_id']?>
                <td><?php echo $col['column_id']?>
            <?php
         }
         ?>
      </table>
      <?php  
   }
   
   // ---------- TAB: FILTERS ---------------------
   // DEFUNCT, use new code now
   function ehTab_Filters_OLD() {
      $skey=SQL_Format('numb',gp('gp_skey'));
      $sq=
         "SELECT  rc.table_id,rc.column_id
                 ,rc.compoper,rc.compval
                 ,t.description as tdesc
                 ,f.description as cdesc
                 ,rc.skey
            FROM reportcolumns rc 
            JOIN reports       r  ON r.report = rc.report
            JOIN tables  t  ON rc.table_id = t.table_id
            JOIN tabflat f  ON rc.table_id = f.table_id
                                 AND rc.column_id= f.column_id
           WHERE r.skey=$skey
           ORDER BY rc.uicolseq";
      $rows=SQL_AllRows($sq);
      $opts=array('','=','!=','>','<','>=','<=');
      ob_start();
      foreach($rows as $row) {
         $row['compoper']=trim($row['compoper']);
         $hSelBase
            ="'&gp_ajax=colsave&gp_colsk=".$row['skey']
            ."&gp_val='+this.value+'&gp_col=";
         $hSel1=$hSelBase."compoper'";
         $hSel2=$hSelBase."compval'";
         $hOpts='';
         foreach($opts as $opt) {
            $sel=$row['compoper']==$opt ? ' SELECTED ' : '';
            $hOpts.="\n<option $sel value=\"$opt\">"
               .htmlentities($opt)."</option>";
         }
         ?>
         <tr>
         <td><?php echo '('.$row['tdesc'].') '.$row['cdesc']?>
         <td><select onchange="sndReq(<?php echo $hSel1?>)" 
                     value="<?php echo $row['compoper']?>">
             <?php echo $hOpts?>
             </select>
         <td><input onblur="sndReq(<?php echo $hSel2?>)"
                value="<?php echo $row['compval']?>"
                size=70 maxlength=100>
         </tr>   
         <?php
      }
      $hHTML=ob_get_clean();
           
           
      ?>
      <table width=100%>
         <tr>
           <td class="dhead" style="width:25%">Column
           <td class="dhead" style="width:10%">Operator
           <td class="dhead" style="width:65%">Value
         </tr>
         <?php echo $hHTML?>
      </table>
      <?php
   }
   
   // - - - - - - - - - - - - - - - - - - 
   // HELPER: Just the tabbar
   // - - - - - - - - - - - - - - - - - -
   function ehTabBar($ajax=true) {
      // If in ajax mode, put out id, else set for first tab
      if($ajax) echo "x2tabbar|";
      
      // fixed values for the tabs
      //$tabs=array(
      //   'Main','Tables','Columns','Levels','Filters'
      //);
      $tabs=array(
         'Main','Tables','Columns','Filters'
      );
      
      // Now generate the tab bar
      $ctab=gp('gp_ajax_tab','Main');
      foreach($tabs as $tab) {
         $hlink="sndReq('&gp_ajax=tab&gp_ajax_tab=".$tab."')";
         if($ctab==$tab) {
            echo "<span>$tab</span>&nbsp;&nbsp;";
         }
         else {
            echo "<a href=\"javascript:$hlink\">$tab</a>&nbsp;&nbsp;</a>";
         }
      }
   }   
}
?>
