<?php
class x_import extends x_table2 {
   function main() {
      $this->PageSubtitle="Imports";
      // Process uploaded files if there are any
      if(!gpExists('gp_xajax')) {
         foreach($_FILES as $onefile) {
            if(!$onefile['error']) {
               // Generate a previously unused name, give up after 20 tries
               $dir=$GLOBALS['AG']['dirs']['root'].'tmp/';
               $count=0;
               while(true) {
                  $fn=$onefile['name'].'.'.(rand(1000,9999));
                  $fs=$dir.$fn;
                  if(!file_exists($fs)) break;
                  $count++;
                  if($count>20) { $fn=''; break; }
               }
               if($fn=='') continue;  // skip this file, we couldn't name it
               
               // If we got to here, then we have a good name to use, lets copy
               // the file over.
               move_uploaded_file($onefile['tmp_name'],$fs);
               $newfile=array(
                  'name'=>$onefile['name']
                  ,'uname'=>$fs
                  ,'type'=>$onefile['type']
                  ,'error'=>$onefile['error']
                  ,'size'=>$onefile['size']
               );
               
               vgfSet('files',array($newfile));
               break;  // only do one file!
            }
         }
      }

      // Routing
      if(gp('gp_fbproc')=='1') return $this->fbProc();
      if(gpExists('gp_xajax')) return $this->xAjax();
      
      // Obtain the basic parameters we need on this page, and then assign
      // them to hidden variables so that FORM POSTs will return here.
      $tid=gp('gp_table_id');
      $t=DD_TableRef(gp('gp_table_id'));
      hidden('gp_page','x_import');
      hidden('gp_nofile','');
      hidden('gp_table_id',gp('gp_table_id'));

      //  Process requests.  If they uploaded a file, save the
      //  info to the session.  If they requested file destroy,
      //  throw it away.
      $files=vgfGet('files',array());
      $fi=null;
      if(isset($files[0])) {
         $fi=$files[0];
         SessionSet('importfile',$fi);
      }
      if(gp('gp_nofile')==1) {
         SessionUnSet('importfile');
         $fi=null;
      }
      
      // If a file is uploaded we just output the div and then call
      // back for the content
      // On this branch we       
      // - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
      // BEGIN HTML
      // - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
      
      ?>
      <h1>Table Import</h1>
      <p><b>Destination Table:</b><?=hLinkPage('',$tid)?>
      </p>
      <p><b>Table Name in Database:</b> <?=$tid?>.
      </p>
      <p>This is standardized import program that can accept file 
         uploads in the popular "CSV" format.  The first row is expected
         to name columns, and all subsequent rows are expected to 
         contain comma-separated values.
      </p>
      <p>This routine will match known column names and display the 
         columns that will be imported.
      </p>
      <hr />
      <?php      
      if($fi) {
         ?>
         <div id="xajax"></div>
         <div id="xajaxm"></div>
         <script type="text/javascript">
         andrax('?gp_page=x_import&gp_table_id=<?=gp("gp_table_id")?>&gp_xajax=1');
         </script>
         <?php
         return;
      }
      else {
         ?>
         <h2>File Upload</h2>
         <p>Please upload a file to process.
         <p><?=hFileUpload();?>
         <?php
      }
   }
   
   function xajax() {
      if(gp('gp_xajax')<>'1') return $this->xAjaxColSave();
      
      // No conditionals here, this is always ajax
      echo "xajax|";
      
      $tid=gp('gp_table_id');
      $parms['gp_table_id']=$tid;
      $parms['gp_page']='x_import';
      $parms['gp_xajax']='1';
      $parms['gp_map']=gp('gp_map','');
      
      //$a1=aFromgp('gp_');
      //hprint_r($a1);
      //$a2=aFromgp('txt_');
      //hprint_r($a2);
      
      // Look for a map delete command
      if(gpExists('gp_del')) {
         SQL("Delete from importmaps where importmap=".SQLFC(gp('gp_del')));
         if(gp('gp_del')==gp('gp_map')) {
            gpSet('gp_map','');
            $parms['gp_map']='';
         }
      }
      
      // Look for a map insert command. If found and works, automatically
      // select this as the map we want.
      $row=aFromGP('txt_');
      if(count($row)>0 && gpExists('gp_new')) {
         $dd=DD_TableRef('importmaps');
         $row['table_id']=gp('gp_table_id');
         SQLX_Insert($dd,$row);
         if(!Errors()) {
            gpSet('gp_map',$row['importmap']);
            $parms['gp_map']=gp('gp_map');
         }
      }
      
      
      // Display a list of maps we may use
      $maps=SQL_AllRows(
         "Select importmap,name_prefix from importmaps
           where table_id=".SQLFC($tid)
         ,'importmap'
      );
      //hprint_r($maps);
      ?>

      <h2>Map Selection</h2>
      
      <p>Please choose a map to use.  If no map exists, please create
         a new one.  After a map is chosen you can map individual columns.
      </p>
      
      <table id="x2data1">
        <thead>
          <tr><th>Map Name
              <th>Select
              <th>Delete
        </thead>
        <tbody>
      <?php
      foreach($maps as $map) {
         $px=$parms;
         $px['gp_map']=$map['importmap'];
         $hp1=http_build_query($px);
         $px['gp_del']=$map['importmap'];
         $hp2=http_build_query($px);
         echo $map['importmap']==$parms['gp_map'] 
            ? '<tr class="hilite">'
            : '<tr>';
         ?>
         <td><?=$map['importmap']?>
         <td><a href="javascript:andrax('?<?=$hp1?>')">Select</a>
         <td><a href="javascript:andrax('?<?=$hp2?>')">Delete</a>
         <?php
      }
      // Now the row for a new entry
      $px=$parms;
      $px['gp_new']=1;
      $hp="'?".http_build_query($px);
      $hp.="&txt_importmap='+ob('txt_importmap').value";
      ?>
         <tr><td><input name="txt_importmap"   id="txt_importmap">
             <td>&nbsp;
             <td><a href="javascript:andrax(<?=$hp?>)">Create</a>
      </table>
      <?php
             
      // If they have not picked a map, we are done.  If we continue
      // we will let them pick individual columns.
      if($parms['gp_map']=='') return;
      
      // Get column listing from dictionary
      $dd=DD_TableRef(gp('gp_table_id'));
      $cols=array_keys($dd['flat']);

      // Get cols available from import
      $fi=SessionGet('importfile');
      $FILE=fopen($fi['uname'],'r');
      $sline=fsGets($FILE);
      $aline=explode(',',$sline);
      array_unshift($aline,'');
      $aline=array_combine($aline,$aline);  // make keys and values the same
      fclose($FILE);
      
      // Get current map
      $mapcols=SQL_AllRows(
         "Select column_id,column_id_src FROM importmapcolumns
           WHERE importmap=".SQLFC(gp('gp_map'))."
             AND table_id =".SQLFC(gp('gp_table_id'))
         ,'column_id'
      );
          
      ?>
      <hr />
      <h2>Individual Column Mappings</h2>
      <table id="x2data1">
        <thead><tr><th>Destination Column</td>
                   <th>Caption</td>
                   <th>Source Column</td>
        </thead>
        <tbody>
      <?php
      foreach($cols as $col) {
         $value=ArraySafe($mapcols,$col,array());
         $value=ArraySafe($value,'column_id_src','');
         $px=$parms;
         $px['gp_xajax']=$col;
         $andrax="?".http_build_query($px);
         $extra="onchange=\"andrax('$andrax&gp_xval='+this.value)\"";
         $hSelect=hSelectFromAA($aline,'anycol',$value,$extra);
         if($dd['flat'][$col]['uino']<>'Y') {
            ?>
            <tr><td><?=$col?>
                <td><?=$dd['flat'][$col]['description']?>
                <td><?=$hSelect?>
            <?php
         }
      }
      ?>
        </tbody>
      </table>
      <?php
      
      $href='?gp_page=x_import&gp_table_id='
         .$tid.'&gp_fbproc=1'
         .'&gp_map='.$parms['gp_map'];
      ?>
      <hr />
      <h2>File Process</h2>
      
      <p>The file <?=$fi['name']?> was uploaded, size 
         <?=number_format($fi['size'])?> bytes.
      </p>
      
      <p><a href="javascript:SetAndPost('gp_nofile',1)">
         Upload A Different File
         </a>
      </p>  
         
      <p><a href="javascript:Popup('<?=$href?>')">Process Now</a>
      </p>
      
      <?php
   }
   
   function xAjaxColSave() {
      echo "xajaxm|";
      $row=array(
         'table_id'=>gp('gp_table_id')
         ,'importmap'=>gp('gp_map')
         ,'column_id'=>gp('gp_xajax')
         ,'column_id_src'=>gp('gp_xval')
      );
      $dd=dd_tableref('importmapcolumns');
      SQLX_UpdateOrInsert($dd,$row);
   }
   
   
   /** **********************************************************
   name:fbProc
   returns:echos HTML
   
   Does actual processing.  
   */
   function fbProc() {
      ob_start();
      $tid=gp('gp_table_id');
      $fi = SessionGet('importfile',array());
      $t=DD_TableRef($tid);
      
      if(!isset($t['description']) || count($fi)==0) {
         echo "Problem with uploads";
         return;
      }
      ?>
      <h1>Table Import Processing</h1>
      <p>For Table: <?=$t['description']?>
      <p>Name in database: <?=$tid?>
      <p>File to process: <?=$fi['name']?>
      <p>File upload size: <?=number_format($fi['size'])?>
      <hr>
      <pre>
      <?php
      list($linenum,$linesok)=$this->fbProcInner($fi,$t);
      echo "</pre>";
      echo "<hr>";
      echo "Processed $linesok of $linenum lines without errors";
      echo ob_get_clean();
   }
   
   function fbProcInner($fi,$t) {
      x_EchoFlush("BEGIN FILE PROCESSING");
      $FILE=fopen($fi['uname'],'r');
      if(!$FILE) {
         x_EchoFlush("Trouble opening local uploaded file.");
         x_EchoFlush("ABORT WITH ERROR");
         return 0;
      }
      
      // Make sure first line is ok
      $line1=fsGets($FILE);
      if(strlen($line1)==0) {
         x_EchoFlush("Failed reading first line, file is empty?");
         x_EchoFlush("ABORT WITH ERROR");
         return 0;
      }
      if(strlen($line1)>4998) {
         x_EchoFlush("First line is &gt; 4998 bytes, this cannot be right.");
         x_EchoFlush("ABORT WITH ERROR");
         return 0;
      }
         
      // Now convert the first line into the list of columns
      $acols=explode(',',$line1);
      x_echoFlush("COLUMNS IN FILE:");
      foreach($acols as $acol) {
         x_EchoFlush($acol);
      }

      // Retrieve maps
      $mapcols=SQL_AllRows(
         "SELECT column_id,COALESCE(column_id_src,'') as src
            FROM importmapcolumns
           WHERE table_id=".SQLFC($t['table_id'])."
             AND importmap=".SQLFC(gp('gp_map'))
         ,'column_id'
      );
      echo "<hr>";
      echo "<h2>Map is as follows: ".gp('gp_map')."</h2>";
      hprint_r($mapcols);
      echo "<hr>";

      
      // Now convert each line as we go
      $linenum=0;
      $linesok=0;
      while( ($oneline=fsGets($FILE))!==false ) {
         $linenum++;
         // Give the user something to believe in
         if($linenum %100 ==0) {
            x_EchoFlush("Line: $linenum processing");
         }
         // Pull the line
         $data=explode(',',$oneline);
         // Maybe a problem?
         if(count($data)<>count($acols)) {
            x_EchoFlush("ERROR LINE $linenum");
            x_EchoFlush("Too many or too few values");
            hprint_r($data);
            continue;
         }
         // No problem yet, attempt the insert
         ErrorsClear();
         // Assign first-row column names to incoming data
         $row=array_combine($acols,$data);
         // Match the values from the map
         $rowi=array();
         foreach($mapcols as $mapcol=>$info) {
            if($info['src']<>'') {
               if(isset($row[$info['src']])) {
                  $rowi[$mapcol]=$row[$info['src']];
               }
            }
         }
         $mixed=array($t['table_id']=>array($rowi));
         SQLX_Cleanup($mixed);
         SQLX_insert($t,$mixed[$t['table_id']][0]);
         
         // Complaints?  Problems? Report them!
         if(Errors()) {
            x_EchoFlush('------------------------------------------------');
            x_EchoFlush("ERROR LINE $linenum when attempting to insert");
            x_EchoFlush(hErrors());
            x_EchoFlush('------------------------------------------------');
            continue;
         }
         $linesok++;
      }
      return array($linenum,$linesok);
   }
}
?>
