<?php
include_once('fpdf153/fpdf.php');
class x_fpdf extends fpdf {
   var $repType='none';
   var $fontname='Courier';
   var $fontsize=10;
   var $zeronumber=false;
   
   var $lineheight=2.125;
   
   // -----------------------------------------------------
   // FPDF Overrides
   // -----------------------------------------------------
   function x_fpdf($ori='p') {
      $this->FPDF($ori,'mm','letter');
   }
   
   function header() {
      if($this->repType=='Type1') {
         $this->Type1Header();
      }
      else {
         $this->trackback->header($this);
      }
   }
   function Footer() {
      if($this->repType=='Type1') {
         $this->Type1Footer();
      }
      else {
         $this->trackback->footer($this);
      }
   }

   // -----------------------------------------------------
   // Startup
   // -----------------------------------------------------
   function StandardSetup($ori='p') {
      $this->SetMargins(10,10);
      $this->SetTextColor(0,0,0);
      $this->SetFont('Courier');
      $this->SetFontSize(12);
      $this->SetAutoPageBreak(true,20); // sets 1 in bottom margin
      $this->AddPage($ori);
      $this->repType='none';
   }
   // -----------------------------------------------------
   // Andromeda core routines
   // -----------------------------------------------------
   function BlankLine() {
      $this->ClearLine();
      $this->OneCell(' ','char');
      $this->Outputline();
   }
   
   function SImpleLine($text) {
      $this->ClearLine();
      $this->OneCell($text,'char');
      $this->Outputline();
   }
   
   function ClearLine() {
      $this->CurrentLine='';
   }
   function OnePair($caption,$value,$type_id='char',$ds=0,$cs=0) {
      $this->SetFont($this->fontname,'B',$this->fontsize);
      // Set the first one in bold, add a colon
      $this->OneCell($caption.": ",'char');
      $this->SetFont($this->fontname,'U',$this->fontsize);
      $this->OneCell($value,$type_id,$ds,$cs);
      $this->SetFont($this->fontname,'',$this->fontsize);
      $this->OneCell('  ','char');
   }
   
   function OneCell($text,$type_id='char',$dispsize=0,$colscale=0) {
      if($dispsize==0) $dispsize=strlen($text);
      $align="L";
      switch($type_id) {
         case 'char':
         case 'vchar':
            $output = str_pad(substr($text,0,$dispsize),$dispsize).' ';
            break;
         case 'date':
            if(!$text) $output = '           ';
            else $output = date('m/d/Y',dEnsureTS($text)).' ';
            break;
         case 'numb':
            if($this->zeronumber && $text==0) {
               $output = '';
            }
            else {
               $output=number_format((float)$text,$colscale);
            }
            $output = ' '.str_pad($output,$dispsize,' ',STR_PAD_LEFT);
            $align="R";
            break;
         case 'time':
            $output = ' '.str_pad(trim(hTime($text)),8,' ',STR_PAD_LEFT);
            $align="R";
            break;
         default:
            $output = $text;
      }
      $width=$this->GetStringWidth($output);
      $this->Cell($width,0,$output,0,0,$align);
      //$this->CurrentLine.=$output.' ';
   }
   function OutputLine() {
      $this->Cell(0,$this->lineheight,$this->CurrentLine,0,1);
      $this->Ln($this->lineheight);
   }
   
   function OutDashes($aLengths,$char='=') {
      $this->ClearLine();
      foreach($aLengths as $Length) {
         $this->OneCell( str_repeat($char,$Length),'char');
      }
      $this->OutputLine();
   }
   // -----------------------------------------------------
   // Andromeda handy routines
   // -----------------------------------------------------
   function PageNumberRight() {
      $this->Cell(0,0,$this->PageNo(),0,0,'R');
   }
   function DateAndPage($lh) {
      $this->Cell(0,$lh,date('m/d/Y',time()),0,1,'L');
      $this->Cell(0,$lh,'Page '.$this->PageNo(),0,0,'R');
      $this->Ln();
   }

   // -----------------------------------------------------
   // Type 1 Routines.  These are routines used for
   // Normal Type 1 reports.  
   // -----------------------------------------------------
   function Type1Setup($t1ColInfo,$t1Title,$t1Orient='p',$titles=array(),$fontsize=12) {
      //$this->FPDF($t1Orient,'mm','letter');
      $this->t1ColInfo=$t1ColInfo;
      $this->t1Title  =$t1Title;
      $this->repType  ='Type1';
      $this->Type1Headers=$titles;
      $this->SetMargins(10,10);
      $this->SetTextColor(0,0,0);
      $this->SetFont('Courier');
      $this->SetFontSize($fontsize);
      $this->SetAutoPageBreak(true,20); // sets 1 in bottom margin
      $this->AddPage($t1Orient);
   }

   function Type1Header() {
      $this->ClearLine();
      $this->OneCell($this->t1Title,'char');
      $this->OutputLine();
      if(isset($this->oRep)) {
         if(method_exists($this->oRep,'Type1_header_extra')) {
            $this->ClearLine();
            $this->oRep->Type1_header_extra();
            $this->OutputLine();
         }
      }
      $this->DateAndPage(2);
      $this->ClearLine();
      $this->OutputLine();
      $this->BlankLine();

      //hprint_r($this->Type1Headers);      
      foreach($this->Type1Headers as $oneline) {
         $this->ClearLine();
         $this->OneCell($oneline,'char');
         $this->OutputLine();
      }

      $this->ClearLine();
      foreach($this->t1ColInfo as $colname=>$info) {
         // Get column size
         $size=$this->Type1ColSize($info);
         
         // Figure out the padding
         $pad=$info['type_id']=='numb' ? STR_PAD_LEFT : STR_PAD_RIGHT;
         $caption=str_pad($info['caption'],$size,' ',$pad);

         $this->OneCell($caption,'char',$size);  
      }
      $this->OutputLine();
      $this->Type1Dashes("=");
      $this->Ln();
      
   }
   
   function Type1Dashes($char='=') {
      $dashes=array();
      foreach($this->t1ColInfo as $colname=>$info) {
         $dashes[] = $this->Type1ColSize($info);
      }
      $this->OutDashes($dashes,$char);
   }
   
   function Type1Footer() {
      $this->ClearLine();
      $this->OutputLine();
      $this->ClearLine();
      $this->OutputLine();
      $this->ClearLine();
      $this->OutputLine();
   }
   
   function Type1Row($row) {
      $this->ClearLine();
      foreach($this->t1ColInfo as $colname=>$colinfo) {
         $value=ArraySafe($row,$colname);
         $type_id=$colinfo['type_id'];
         $size=$this->Type1ColSize($colinfo);
         $colscale=ArraySafe($colinfo,'colscale',0);
         //echo "Calling $value  $type_id  $size  $colscale<br/>";
         $this->OneCell($value,$type_id,$size,$colscale);
      }
      $this->OutputLine();
   }

   function Type1ColSize($info) {   
      if(isset($info['colprec'])) {           
         $size=$info['colprec'];
      }
      else {
         if($info['type_id']=='date') $size=10;
         if($info['type_id']=='time') $size=8;
      }
      return $size;
   }
   
   function Type1Percent($top,$bottom) {
      if($bottom==0) return 0;
      else return (($top*100)/$bottom);
   }
}
?>
