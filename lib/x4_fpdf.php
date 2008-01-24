<?php

require_once 'PEAR.php';
require_once 'fpdf153/fpdf.php';


/**
 *
 * Creates conventional business reports as PDF
 * files.  Is column-oriented, the programmer defines the
 * with and justification (right/left) of a series of 
 * columns, then queries a database and sends results to
 * various routines to output values. 
 *
 * Makes use of fpdf.php, by xxxx, http://www.fpdf.org
 *
 * @package Andro_Report
 * @author Kenneth Downs <ken@secdat.com>
 *
*/
class x4_fpdf extends fpdf {
    /**
     *  The last column that was written to
     *  @var lastCol
     *  @access private
     */
    var $lastCol = -1;

    /**
     *  An array of derived column information such
     *  as start position and width.
     *  @var cols
     *  @access private
     */
    var $cols = array();

    /**
     *  The gutter width in points
     *  @var cols
     *  @access private
     */
    var $gutter = 0;
    
    /**
     *  Constructor.  Clears all definitions and
     *  defaults to Landscape, points as unit of measure,
     *  and US Letter as page size.
     *
     *  @param string $ori   Orientation, "P" or "L"
     *  @param string $uom   Unit of measure
     *  @param string $paper See http://www.fpdf.org/manual/FPDF
     *
     *  @access public
     *  @since 0.1
     */
    function x4_fpdf($ori='l',$uom='pt',$paper='letter') {
        // Tab Stops
        $this->cols  = array();
        
        $this->FPDF($ori,$uom,$paper);
    }

    /**
     *  Output the report and then issue an "exit" to end all processing.
     *  Also issues an empty "Pragma" header which is apparently necessary
     *  on some IE systems to get a display, otherwise the user will 
     *  complain that it "doesn't work" and when you click on the link to
     *  generate the report nothing appears to happen.
     *
     *  @param string $name Name of report, defaults to 'report.pdf'
     *  @param string $nature "I" for inline (default) or "A" for attachment.
     *
     *  @since 12/16/07
     */
    function overAndOut($name="report.pdf",$nature='I') {
        header('Pragma:',true);
        $this->Output($name,$nature);
        exit;
    }
    
    
    /**
     *  Establish orientation, font name, size and line spacing
     *  
     *  This routine is usually called first, followed by 
     *  SetupColumns and then AddPage.  That 3-routine 
     *  sequence has you ready to start output.
     *
     *  @param string $ori        Orientation, "P" or "L"
     *  @param string $fontname   default "Times"
     *  @param string $fontsize   In points, default 12  
     *  @param string $lineheight default 1, single spacing
     */
    function setupReport($ori='p',$fontname='Times',$fontsize=12,$linespacing=1) {
        $this->reportSetup($ori,$fontname,$fontsize,$linespacing);
    }
    
    // Deprecated original name of rotuine, some code still
    // calls this
    function reportSetup($ori='p',$fontname='Times',$fontsize=12,$linespacing=1) {
        $this->margin_left = 36;
        $this->margin_top = 36;
        $this->SetMargins($this->margin_left,$this->margin_top);
        $this->SetTextColor(0,0,0);
        $this->SetFont($fontname);
        $this->SetFontSize($fontsize);
        $this->fontname=$fontname;
        $this->fontsize=$fontsize;
        $this->linespacing = $linespacing;
        $this->cpi = 120/$fontsize;
        $this->lineheight = $fontsize * $this->linespacing;
    }

    /**
     *  Accept column definitions, which are the basis of
     *  many of the features of this class.  Each column
     *  is given a width.  Columns by default are left-justified
     *  unless a ":R" is put after the width, in which case
     *  they are right-justified.
     *
     *  In normal coding you call this right after calling
     *  reportSetup and right before calling addPage.
     *
     *  @param float $gutter Width of gutter between columns 
     *                       in inches
     *  @param string $list  Comma-separated list of widths in inches
     *
     *  An example would be setupColumns(.1,'1,2,1:R,1:R'), which
     *  establishes a gutter of .1 inch, and four columns of widths
     *  1 inch, 2 inches, 1 inch and 1 inch respectively.  The 
     *  latter two columns will be right justified.
     */
    function setupColumns($gutter,$commalist) {
        $this->setupCols($gutter,$commalist);
    }
    function setupCols($gutter,$commalist) {
        $this->lastCol = -1;
        $this->cols = array();
        $this->gutter = $gutter * 72;
        if(is_array($commalist)) {
            $alist = $commalist;
        }
        else {
            $alist = explode(',',$commalist);
        }
        $posx = $this->margin_left;
        foreach($alist as $onestop) {
            $colstuff = explode(':',$onestop);
            $width = array_shift($colstuff);
            $align='L';
            if(count($colstuff)>0) {
                $align = array_shift($colstuff);
            }
            $this->cols[] = array(
                'xpos'=>$posx
                ,'width'=>$onestop * 72
                ,'align'=>$align
            );
            $posx += ($gutter+$onestop) * 72;
        }
    }

    /**
     * Outputs text alone on a line, centered.  Good for
     * page headers.
     *
     * @param string $text The text to output
     * @param string $style Can include "B" or "I" for 
     *                       bold and italic.  Defaults to blank
     *                      which is normal text size.
     * @param string $size Font size in points. Defaults to 
     *                     current font size.
     */
    function CenteredLine($text,$style='',$size=null) {
        if(is_null($size)) $size=$this->fontsize;
        $this->setFont($this->fontname,$style,$size);
        $this->Cell(0,0,$text,0,0,'C'); // DOC: 0 width=all
        $this->Ln($size * $this->linespacing);      
        $this->setFont($this->fontname,'',$this->fontsize);
    }

    /**
     * Outputs A line containing the date on left and the
     * page number on the right.  No options or parameters.
     *
     * Good for use in headers.
     */
   function DateAndPage() {
      $this->Cell(0,0,date('m/d/Y',time()),0,1,'L');
      $this->Cell(0,0,'Page '.$this->PageNo(),0,0,'R');
      $this->Ln($this->lineheight);
   }

    /**
     * Goes to the next line.  Also resets the column position
     * so that "AtNextCol" will begin at the let.
     */
    function nextLine($color=false) {
        $this->Ln($this->lineheight);
        $this->lastCol=-1;
    }
       
    /**
     * Accepts a comma-separated list, splits it up
     * and puts one value in each column, beginning with
     * the current column position.
     *
     * @param string $values Comma-separated list of values
     *
     * You can output titles on two rows of a page header
     * using this routine like so:
     *
     * function header() {
     *   $this->CenteredLine("Report Header");
     *   $this->DateAndPage();
     *   $this->outFromList('First,Last,Quarterly,Annual');
     *   $this->outFromList('Name,Name,Income,Income');
     *   $this->linesForColumns();
     * }
     *
     *
     */
   function outFromList($commalist) {
       $this->lineFromArray(explode(',',$commalist));
   }
   
    /**
     * Accepts an array of values and puts each value
     * into a column, beginning with
     * the current column position.
     *
     * @param array $values Array of values
     *
     * Useful in the main body of your report, after executing
     * a query:
     *
     * foreach($rows as $row) {
     *   $this->outFromArray($row);
     * }
     *
     */
   function outFromArray($alist) {
       while($value = array_shift($alist)) {
           $this->atNextCol($value);
       }
       $this->nextLine();
   }
   
    /**
     * Puts a value into a specific column.
     *
     * @param int $col  Column position, zero indexed.  Columns
     *                  must be defined already by a call to
     *                  setupColumns().
     * @param string $text Value to write.  Numerics and dates
     *                     must be formatted into the string
     *                     representation you want.
     * @param string $ori Orientation (optional)  Overrides default 
     *                    orientation as established by 
     *                    prior call to setupColumns()
     *
     */
   function AtCol($col,$text,$flush='L') {
       if(!isset($this->cols[$col])) {
           echo "<b>ERROR: Output past last column, did you 
            forget a \$this->nextLine()?";
           exit;
       }
       
       // work out the x position in points, don't mess with y
       $xposition = $this->cols[$col]['xpos'];
       $width     = $this->cols[$col]['width'];
       $align     = $this->cols[$col]['align'];
       $this->Setx($xposition);
       $this->Cell($width,0,$text,0,0,$align);
       $this->lastCol = $col;
   }

    /**
     * Puts a value into the next column. 
     * Calling nextLine() resets the column to zero.
     * Calling atNextCol() or AtCol() advances the column
     * position.
     *
     * @param string $text Value to write.  Numerics and dates
     *                     must be formatted into the string
     *                     representation you want.
     * @param string $ori Orientation (optional)  Overrides default 
     *                    orientation as established by 
     *                    prior call to setupColumns()
     *
     */
   function AtNextCol($text,$flush='L') {
       $this->AtCol($this->lastCol+1,$text,$flush);
   }
   
    /**
     * Fills a line with horizontal lines across the
     * width of each column.  Often used as the last call
     * in a page header.
     *
     */
    function LinesForColumns() {
       $posy = $this->getY();
       foreach($this->cols as $onecol=>$colinfo) {
           $this->Line(
               $colinfo['xpos'],$posy
               ,$colinfo['xpos']+$colinfo['width'],$posy
           );
       }
       $this->nextLine();       
    }
   
    /**
     * Draws a horizontal line across one or more columns.
     * Useful for instance just before writing out a total
     * that will span four columns.
     *
     * @param int $start First column (zero indexed)
     * @param int $end   Last Column (zero indexed)
     */
   function lineAcrossColumns($start,$end) {
       $posx1 = $this->cols[$start]['xpos'];
       $posx2 = $this->cols[$start]['xpos'];
       for($x = $start;$x<=$end;$x++) {
           $posx2 += $this->cols[$x]['width'];
       }
       $posx2 += $this->gutter * ($end - $start);
       $posy = $this->getY();
       $this->Line($posx1,$posy,$posx2,$posy);
   }
   
    /**
     * Writes a value across two or more columns.  Usually
     * used in page headers.
     *
     * @param int $start First column (zero indexed)
     * @param int $end   Last Column (zero indexed)
     * @param string $text Value to display
     * @param string $align Can by "L"eft, "R"ight or "C"enter
     *                      defaults to centered.
     *
     * This example shows a page header for a financial report
     *
     * $this->ValueAcrossColumns(2,4,'Income');
     * $this->ValueAcrossColumns(5,6,'Expense');
     * $this->newLine()
     * $this->lineAcrossColumns(2,4);
     * $this->lineAcrossColumns(5,6);
     * $this->outFromlist(
     *    'Name,DOB,Salary,Dividends,Royalties,Personal,Business'
     * );
     * $this->LinesAcrossColumns();
     */
   function valueAcrossColumns($start,$end,$text,$align='C') {
       $posx1 = $this->cols[$start]['xpos'];
       $width = 0;
       for($x = $start;$x<=$end;$x++) {
           $width += $this->cols[$x]['width'];
       }
       $width += $this->gutter * ($end - $start);
       $this->Setx($posx1);
       $this->Cell($width,0,$text,0,0,$align);
   }
   
}
?>
