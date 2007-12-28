<?php
class x_wiki {
   function _construct($table_id) {
      $this->page_id=$table_id;
      echo $table_id;
   }
   
   function hWikiFromTable($table_id,$pagename,$flag_title=true) {
      $tid=SQL_ESCAPE_STRING($table_id);
      $pn=SQLFC($pagename);
      $row=SQL_OneRow("SELECT pagewiki FROM $tid where pagename=$pn");
      
      $title=$flag_title ? $pagename : '';
      return $this->hWiki($table_id,$pagename,$row['pagewiki'],$title);
   }
   
   function hWikiFromText($input) {
      return $this->hWiki('','',$input,'');
   }

   
   function hWiki($table_id,$pagename,$input,$title='') {
      $this->table_id=$table_id;
      
      // Remove carriage returns, makes things much easier
      $html=str_replace("\r",'',$input);

      // Put in an "edit this page" if user is logged in
      if(LoggedIn() && $table_id<>'') {
         echo "<br><div>";
         echo '<a href="?gp_page=pages&gp_pk='.urlencode($pagename).'">EDIT THIS PAGE</a>';
         echo "</div>";
      }
      
      // If a title was provided, put it in h1 at top
      $retval=$title=='' ? '' : "<h1>".hSanitize($title)."</h1>";
      
      // Break into lines and begin outputting
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
            ,'<h6>$1</h6>'
            ,$retv);
         $retv=preg_replace(
            "/={5}(.*)={5}/xsU"
            ,'<h5>$1</h5>'
            ,$retv);
         $retv=preg_replace(
            "/={4}(.*)={4}/xsU"
            ,'<h4>$1</h4>'
            ,$retv);
         $retv=preg_replace(
            "/={3}(.*)={3}/xsU"
            ,'<h3>$1</h3>'
            ,$retv);
         $retv=preg_replace(
            '/==(.*)==/'
            ,'<h2>$1</h2>'
            ,$retv);
         $retv=preg_replace(
            '/=(.*)=/'
            ,'<h1>$1</h1>'
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
   
   function hFromWiki($wiki) {
      return $this->wikiProcess_NormalText($wiki);
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
            case 'ilink': $this->Linkilink($html,$match);       break;
            case 'image': $this->LinkImage($html,$match,$type); break;
            default     : $this->LinkUnknown($html,$search);    break;
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
      $tid=SQL_ESCAPE_STRING($this->table_id);
      $sq="SELECT pagename FROM $tid
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
      $newval='<img src="/'.tmpPathInsert().'apppub/'.$match.'">';
      $html=preg_replace('/\[\['.$type.":".$match.'\]\]/',$newval,$html);
   }

   function LinkUnknown(&$html,$match) {
      $newval = '<span class="nolink">UNSUPPORTED LINK: '.$match.'</span>';
      $html=str_replace('[['.$match.']]',$newval,$html);
   }   
}
?>
