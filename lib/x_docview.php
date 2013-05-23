<?php
class x_docview extends x_table2 {
   /*
   function custom_construct() {
      $this->button_images=false;
   }
   function construct_custom() {
      $this->button_images=false;
   }
   */
   function custom_construct() {
      $this->image_directory="dbobj";
   }
   
   function main() {
      // Get top page
      $this->PageSubtitle="Documentation";
      //$sq="SELECT pagename FROM docpageshier WHERE pagehier=1";
      //$pageroot=SQL_OneValue('pagename',$sq);
      $pageroot='Data Dictionary';
      
      $pn=gp('gppn');
      $pn=($pn=='') ? $pageroot : $pn; 
      $sq="SELECT * from docpages 
            WHERE pagename = ".sql_format('char',$pn);
      $row=SQL_oneRow($sq);
      
      if($row===false) {
         echo "Page does not exist: ".$pn;
         return;
      }
      
      // Get one parent.  We used to get all of them, but now
      // we only want one
      $hmenu='';
      $attop = false;
      $parents=array();
      $peers=array();
      $kids=array();
      $pparent = $pprev = $pnext = '';
      $plast = $pn;
      while($plast<>$pageroot) {
         $sq="SELECT pagename_par FROM docpages 
               WHERE pagename = '$plast'";
         $rownew = SQL_AllRows($sq);
         $plast = $rownew[0]['pagename_par'];
         $parents[] = $plast;
         //if ($rownew[0]['pagename_par'] == $pageroot) {
         //   break;
         //}
      }
      if(count($parents)>0) { 
         $parents=array_reverse($parents); 
         //$hmenu=adocs_makemenu($parents,'Parent Topics');

         // Grab this page's peers
         $pparent = $parents[count($parents)-1];         
         $sq="SELECT pagename FROM docpages 
               WHERE pagename_par = '$pparent'
               ORDER BY sequence";
         $rs=SQL($sq);
         while ($rowx = SQL_Fetch_Array($rs)) {
            $peers[] = $rowx['pagename'];
         }
         $peersr = array_flip($peers);
         $pprev = $peersr[$pn] == 0 ? '' : $peers[$peersr[$pn]-1];
         $pnext = $peersr[$pn] == count($peers)-1 
            ? '' 
            : $peers[$peersr[$pn]+1];
      }  
      
      // Now pull out the kids
      $sq="SELECT pagename FROM docpages 
            WHERE pagename_par = '$pn'
            ORDER BY sequence";
      $rs=SQL($sq);
      while ($rowx = SQL_Fetch_Array($rs)) {
         $kids[] = $rowx['pagename'];
      }
      
      // Make and save a menu out of what we've discovered
      adocs_makemenu($pageroot,$pn,$parents,$peers);
      
      // Now format the page and save it.  No caching for now.      
      $html=$row['pagetext'];

      $html=$this->WikiProcess($html);
      
      /*
      // Remove carriage returns, makes things much easier
      $html=str_replace("\r",'',$html);

      // Convert newlines to double br's, but first don't do doubles
      // after headings
      $html=str_replace("=\n\n","=\n",$html);
      $html=preg_replace("/\n\s*\n/","\n<br><br>\n",$html);
     
      // Convert bold & italitcs 
      $html=preg_replace(
         "/'{4,}(.*)'{4,}/xmsU"
         ,'<b><i>$1</i></b>'
         ,$html);
      $html=preg_replace(
         "/'{3}(.*)'{3}/xmsU"
         ,'<i>$1</i>'
         ,$html);
      $html=preg_replace(
         "/\'{2}(.*)\'{2}/xmsU"
         ,'<b>$1</b>'
         ,$html);
     

      // Convert 6 levels of title
      $html=preg_replace(
         "/={6}(.*)={6}/xsU"
         ,'<head6>$1</head6>'
         ,$html);
      $html=preg_replace(
         "/={5}(.*)={5}/xsU"
         ,'<head5>$1</head5>'
         ,$html);
      $html=preg_replace(
         "/={4}(.*)={4}/xsU"
         ,'<head4>$1</head4>'
         ,$html);
      $html=preg_replace(
         "/={3}(.*)={3}/xsU"
         ,'<head3>$1</head3>'
         ,$html);
      $html=preg_replace(
         "/={2}(.*)={2}/xsU"
         ,'<head2>$1</head2>'
         ,$html);
         
      $html=preg_replace(
         '/^=(.*)=$/U'
         ,'<head1>$1</head1>'
         ,$html);

      // convert hyperlinks and images
      $matches=array();
      while(preg_match('/\[{2,}(.*)\]{2,}/xmsU',$html,$matches)>0) {
         $search=$matches[1];
         $asearch=explode(':',$search);
         if(count($asearch)==2) {
            $type=$asearch[0];
            $match=$asearch[1];
         }
         else {
            $type='ilink';
            $match=$search;
         }
         
         switch(strtolower($type)) {
            case 'ilink': $this->Linkilink($html,$match); break;
            case 'image': $this->LinkImage($html,$match,$type); break;
         }
         
         $matches=array();
      }
      */
      
      // Prepare a list of parents
      if(count($parents)==0) {
         $apars = array($pn);
      }
      else {
         $apars = $parents;
         $apars[] = $pn;
      }
      $hpars = '';
      foreach($apars as $apar) {
         $hpars.=($hpars=='' ? '' : ' &gt; ')
            .'<a href="?gp_page=x_docview&gppn='.urlencode($apar).'">'.$apar.'</a>';
      }
      
      // Prepare the prev, next stuff
      $hpn='';
      if($pprev.$pnext<>'') {
         $hp=$pprev=='' ? '' 
            : '<a href="?gp_page=x_docview&gppn='.urlencode($pprev).'">PREV: '.$pprev.'</a>';
         $hn=$pnext=='' ? '' 
            : '<a href="?gp_page=x_docview&gppn='.urlencode($pnext).'">NEXT: '.$pnext.'</a>';
         $hpn="
			<div class=\"row\">
			<div class=\"span9\">
				<div class=\"pull-left\">$hp</div>
				<div class=\"pull-right\">$hn</div>
			</div>
			</div>";
      }
      
      // Pull out and assemble the see-also groups
      $hsa='';
      /*
      $hsa='';
      $sq='SELECT DISTINCT seealso FROM seealsoxpages '
         ." WHERE pagename='$pn'";
      $sas=SQL_AllRows($sq);
      foreach($sas as $sa) {
         $hsa.="<hr>";
         $seealso=$sa['seealso'];
         $hsa.="<hr><h2>See Also ($seealso):</h2><p>";
         $sq="SELECT pagename FROM seealsoxpages "
            ." WHERE seealso = '$seealso' "
            ."  AND  pagename <> '$pn'"
            ." ORDER By pagename ";
         $sarows=SQL_AllRows($sq);
         foreach($sarows as $index=>$sarow) {
            $hsa.=($index==0 ? '' : ', ')
               .'<a href="?gppn='.urlencode($sarow['pagename']).'">'
               .$sarow['pagename'].'</a>';
         }
         $sarows.='</p>';
      }
      */
      
      
      // Now the actual output and formatting
      // 
      $this->PageSubtitle=$pn;
      echo "<div class=\"hero-unit\">Database Specification</div>";
      echo $hpars."<br><br>";
      echo $hpn;
      echo "\n<hr>";
      echo "\n<h2>".$pn."</h2>\n";
      echo $html;
      if (count($kids)>0 && $pn=='Data Dictionary') {
         echo "\n<hr>";
         echo "\n<head2>Child Topics</head2>";
         foreach($kids as $kid) {
            echo "\n<div><a href=\"?gp_page=x_docview&gppn=".urlencode($kid)."\">$kid</a></div>";
         }
      }
      echo $hsa;
      echo "<hr>";
      echo $hpn;
      ?>
      <hr>
      Page last modified <?php echo date('r',dEnsureTS($row['ts_upd']))?> by 
         <?php echo $row['uid_upd']?><br><br>
      <?php
   }
   
   function wikiProcess($input) {
      // Remove carriage returns, makes things much easier
      $html=str_replace("\r",'',$input);
      
      // Break into lines and begin outputting
      $retval='';
      $ahtml = explode("\n",$html);
      $mode  = '';
      foreach($ahtml as $oneline) {
         switch($mode) {
            case 'html':
               $f4=substr($oneline,0,5);
               if($f4=="</div" || $f4=="</pre") {
                  $mode='';
                  $new =$oneline."\n";
               }
               else {
                  $new =($oneline)."\n";
               }
               break;
            case 'para':
               list($mode,$new)=$this->wikiProcessModePara($oneline);
               $new.="\n";
               break;
            case 'list':
               list($mode,$new)=$this->wikiProcessModeList($oneline);
               $new.="\n";
               break;
            default:
               list($mode,$new)=$this->wikiProcessModeBlank($oneline);
               break;
         
         }
         $retval.=$new;
      }
      return $retval;
   }
   
   function wikiProcessModeBlank($oneline) {
      // easy one, an empty line
      $oneline=trim($oneline);
      if (strlen($oneline)==0) return array('','');
      
      // if a title, no change in mode
      $mode='';
      $retv=$oneline;
      if(substr($oneline,0,1)=="=" && substr($oneline,-1,1)=="=") {
         $retv=preg_replace(
            "/={6}(.*)={6}/xsU"
            ,'<head6>$1</head6>'
            ,$retv);
         $retv=preg_replace(
            "/={5}(.*)={5}/xsU"
            ,'<head5>$1</head5>'
            ,$retv);
         $retv=preg_replace(
            "/={4}(.*)={4}/xsU"
            ,'<head4>$1</head4>'
            ,$retv);
         $retv=preg_replace(
            "/={3}(.*)={3}/xsU"
            ,'<head3>$1</head3>'
            ,$retv);
         $retv=preg_replace(
            '/==(.*)==/'
            ,'<head2>$1</head2>'
            ,$retv);
         $retv=preg_replace(
            '/=(.*)=/'
            ,'<head1>$1</head1>'
            ,$retv);
      }
      elseif (substr($oneline,0,4)=="<div" ) {
         $mode='html';
         $retv=$oneline;
      }  
      elseif (substr($oneline,0,4)=="<pre" ) {
         $mode='html';
         $retv=$oneline;
      }
      elseif (substr($oneline,0,1)=="*" ) {
         $mode='list';
         $retv='<ul><li>'.substr($oneline,1);
      }
      else {
         // assume some kind of text, open a paragraph
         $mode="para";
         $retv="<p>".$this->wikiProcess_NormalText($oneline);
      }
      if ($mode<>'html') {
         $retv.="\n";
      }
      return array($mode,$retv); 
   }
   
   function wikiProcessModePara($html) {
      // Early exit;
      $html=trim($html);
      if(strlen($html)=='') {
         return array('',"</p>");
      }
      
      $retv=$this->wikiProcess_NormalText($html);
      return array('para',$retv);
   }

   function wikiProcessModelist($html) {
      // Early exit;
      $html=trim($html);
      if(strlen($html)=='') {
         return array('',"</ul>");
      }
      $prefix="";
      if(substr($html,0,1)=='*') {
         $prefix="<li>";
         $html  = substr($html,1);
      }
      
      $retv=$prefix.$this->wikiProcess_NormalText($html);
      return array('list',$retv);
   }
   
   
   function wikiProcess_NormalText($html) {      
      // Convert bold & italitcs 
      $html=preg_replace(
         "/'{4,}(.*)'{4,}/xmsU"
         ,'<b><i>$1</i></b>'
         ,$html);
      $html=preg_replace(
         "/'{3}(.*)'{3}/xmsU"
         ,'<i>$1</i>'
         ,$html);
      $html=preg_replace(
         "/\'{2}(.*)\'{2}/xmsU"
         ,'<b>$1</b>'
         ,$html);
     
      // convert hyperlinks and images
      $matches=array();
      while(preg_match('/\[{2,}(.*)\]{2,}/xmsU',$html,$matches)>0) {
         $search=$matches[1];
         $asearch=explode(':',$search);
         if(count($asearch)==2) {
            $type=$asearch[0];
            $match=$asearch[1];
         }
         else {
            $type='ilink';
            $match=$search;
         }
         
         switch(strtolower($type)) {
            case 'ilink': $this->Linkilink($html,$match); break;
            case 'image': $this->LinkImage($html,$match,$type); break;
         }
         
         $matches=array();
      }
      return $html;
   }

   // ------------------------------------------------------------
   // Process various kinds of links
   // ------------------------------------------------------------
   // Internal links
   function Linkilink(&$html,$match) {
      $sq="SELECT pagename FROM docpages 
            WHERE pagename = '$match'";
      $rs=SQL_OneValue('pagename',$sq);
      if(!$rs) {
         $newval='<span class="nolink">'.$match.'</span>';
      }
      else {
         $newval='<a href="?gp_page=x_docview&gppn='.urlencode($rs).'">'.$rs.'</a>';
      }
      $html=str_replace('[['.$match.']]',$newval,$html);
   }

   // Images
   function LinkImage(&$html,$match,$type) {
      $sq="SELECT filename,description FROM media 
            WHERE filename = '$match'";
      $rs=SQL_OneValue('filename',$sq);
      if(!$rs) {
         $newval='<span class="nolink">MISSING IMAGE: '.$match.'</span>';
      }
      else {
         $newval='<img src="'.$this->image_directory.'/'.$match.'">';
      }
      $html=preg_replace('/\[\['.$type.":".$match.'\]\]/',$newval,$html);
   }
   
}
?>
