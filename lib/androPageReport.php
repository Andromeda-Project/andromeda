<?php
require_once 'fpdf153/fpdf.php';
/**
 *
 * Outputs an Andromeda page definition in either PDF format
 *    or on-screen.
 *
 * Makes use of fpdf.php, by Olivier Plathey, http://www.fpdf.org
 *
 * @package androPage
 * @author Kenneth Downs <ken@secdat.com>
 *
*/
class androPageReport extends fpdf {
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
     *  An array of meta-data about columns.  
     *  @var cols
     *  @access private
     */
    var $colsmd = array();

    /**
     *  The gutter width in points
     *  @var cols
     *  @access private
     */
    var $gutter = 0;
    
    /**
     *  Page orientation.  
     *  @var orientation
     *  @access private
     */
    var $orientation = 0;

    /**
     *  Captions across top
     *  @var captions
     *  @access private
     */
    var $captions = array();

    /**
     *  Data dictionary information for each column
     *  @var ddcols
     *  @access private
     */
    var $ddcols = array();

    /**
     *  The format of the output.  Currently supported
     *  formats are pdf and onscreen.  
     *  @var format
     *  @access private
     */
    var $format = 'pdf';

    /**
     *  The rowcount of outputs.  Allows Extended Desktop code to
     *  use jQuery to deal with the rows.
     *
     *  @var rowNumber
     *  @access private
     */
    var $rowNumber = 0;
    
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
    function androPageReport($ori='l',$uom='pt',$paper='letter') {
        // Tab Stops
        $this->cols  = array();
        
        $this->FPDF($ori,$uom,$paper);
    }

    /**
     *  Main Entry point for execution.
     *
     *  @param string $yamlP2     
     *  @param string $fontname   default "Times"
     *  @since 12/16/07
     */
    function main($dbres,$yamlP2,$secinfo) {
        // Set most important flag first!
        $this->format = gp('gp_post');  // pdf or onscreen
        
        // Branch out to do setup...
        $this->mainSetup($yamlP2);
        
        // declare this to avoid jedit compiler warning
        $row=array();
        
        // Call the routine that sets up an array of
        // values to put into the bottom and break
        $bottom = $this->setupBottom($yamlP2);
        $break  = $this->setupBreak($yamlP2);
        
        // Begin by adding the first page
        if($this->format=='onscreen') {
            $this->headerOnScreen();
        }
        else {
            $this->addPage($this->orientation);
        }
        $row1 = false;
        while($row=SQL_Fetch_Array($dbres)) {
            if($row1) {
                if(!$this->compareBreak($yamlP2,$break,$row)) {
                    $this->linesForColumns();
                    $this->outFromArray($break);
                    $this->nextLine();
                    $this->nextLine();
                    $break = $this->SetupBreak($yamlP2);
                }
            }
            $row1=true;
            $this->outFromArray($row);
            if(count($break)>0) {
                $break = $this->processForBreak($yamlP2,$break,$row);
            }
            if(count($bottom)>0) {
                $bottom = $this->processForBottom($yamlP2,$bottom,$row);
            }
        }
        if(count($break)>0) {
            $this->linesForColumns();
            $this->outFromArray($break);
            $this->nextLine();
        }
        if(count($bottom)>0) {
            $this->linesForColumns();
            $this->outFromArray($bottom);
        }
        $this->overAndOut();
    }
    
    /**
     *  Automated report setup.  Establishes all hardcoded
     *  defaults, all overrides from YAML file, captions, column
     *  sizes, page orientation, and so forth and so on.
     *
     *  This class can output to PDF or onscreen (and later to
     *  csv), but by far the most complex option is the 
     *  PDF version.  We do not wrap the PDF stuff in conditionals
     *  except where necessary, so as to prevent loading up the
     *  class with lots of unnecessary conditionals.  This means
     *  a few properties get set that end up being ignored, but
     *  that is no big deal.
     *
     *  @param string $yamlP2  The processed page definition     
     *  @since 12/16/07
     */
    function mainSetup($yamlP2) {
        // Default UOM is points, so these are 1/2 inch margins
        $this->margin_left = 36;
        $this->margin_top = 36;
        $this->SetMargins($this->margin_left,$this->margin_top);

        // Set defaults.  Font name and size can be pulled
        // from the YAML definition, the rest are hardcoded 
        // or derived.
        $this->fontname = a($yamlP2['options'],'fontname','Arial');
        $this->fontsize = a($yamlP2['options'],'fontsize',12);
        $this->linespacing = 1;
        $this->cpi = 120/$this->fontsize;
        $this->lineheight = $this->fontsize * $this->linespacing;
        
        // Pull options from Yamlp2
        $this->title1 = $yamlP2['options']['title'];
 
        // Determine if there are filters to list:
        $uifilters = ArraySafe($yamlP2,'uifilter',array());
        $atitle2 = array();
        foreach($uifilters as $name=>$info) {
            $atitle2[] = $info['description'].':'.trim(gp('ap_'.$name));
        }
        $this->title2 = count($atitle2)==0 ? '' : implode(", ",$atitle2);
        
        // Tell the fpdf parent class about our setting choices
        $this->SetTextColor(0,0,0);
        $this->SetFont($this->fontname);
        $this->SetFontSize($this->fontsize);
        
        // Set up titles and columns by looping through and figuring
        // out justification and size
        $setupArr = array();
        $width = 0;
        foreach($yamlP2['table'] as $table=>$columns) {
            $dd = ddTable($table);
            foreach($columns['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'uino','N')=='Y') continue;
                $md = array('linkskey'=>a($colinfo,'linkskey','N'));
                
                // Use type to figure out if right or left
                // KFD 4/29/08, constants will not exist, use char
                if(!isset($dd['flat'][$colname]['type_id'])) {
                    $type_id = 'char';
                }
                else {
                    $type_id = $dd['flat'][$colname]['type_id'];
                }
                $suffix = '';
                if(in_array(trim($type_id),array('money','numb','int'))) {
                    $suffix .= ':R';
                    switch($type_id) {
                    case 'money': $scale=2; break;
                    case 'numb':$scale=$dd['flat'][$colname]['colscale'];break;
                    case 'int': $scale = 0;
                    }
                    $suffix .= ':'.$scale;
                }
                if(trim($type_id)=='money') {
                    $suffix .= ':M';
                }
                if(ArraySafe($colinfo,'dispsize','')<>'') {
                    $suffix .= ':C'.$colinfo['dispsize'];
                }
                
                // Work out size using cpi setting
                $dispsize = ArraySafe($colinfo,'dispsize','');
                $dispsize = $dispsize <> '' 
                    ? $dispsize
                    : $dd['flat'][$colname]['dispsize'];
                $setupArr[] = round(($dispsize/$this->cpi),1).$suffix;
                $width += ($dispsize*$this->cpi)+.1; 
                
                // Save the captions for displaying in header
                $desc1=a($dd['flat'][$colname],'descshort','');
                $desc2=a($dd['flat'][$colname],'description','');
                $ddcaption = $desc1 <> '' ? $desc1 : $desc2;
                $caption = ArraySafe($colinfo,'description','');
                $caption = $caption <> '' ? $caption : $ddcaption; 
                $this->captions[] = $caption;
                
                # Work out if this guy is a foreign key.  If they
                # have given a page then link unconditionally
                if( ($tlink= a($colinfo,'linkpage','')) <> '') {
                    $md['linkpage']=$tlink;
                    $md['linkcolumn']=$colname;
                }
                elseif( $dd['flat'][$colname]['primary_key']=='Y') {
                    if( $colname == $dd['pks'] ) {
                        $md['linkpage']=$table;
                        $md['linkcolumn']=$colname;
                    }
                }
                else {
                    $tfko = a($dd['flat'][$colname],'table_id_fko','');
                    if($tfko<>'') {
                        $ddpar = ddTable($tfko);
                        if($tfko = $ddpar['pks']) {
                            $md['linkpage']=$tfko;
                            $md['linkcolumn']=$colname;
                        }
                    }
                }
                $this->md[]=$md;
            }
        }
        
        $this->setupColumns(.1,implode(',',$setupArr));
        
        // Finally, establish orientation by looking at size of report
        if(($width/72) < 7.5) {
            $this->orientation = 'P';
        }
        else {
            $this->orientation = 'L';
        }
    }
    

    /**
     *  Automated report setup.  Setup an array of bottom
     *  values if any of the columns actually have
     *  sums, counts, or anything.
     *
     *  @param string $yamlP2  The processed page definition     
     *  @since 1/31/08
     */
    function setupBottom($yamlP2) {
        $retval = array();
        $retcount= 0;
        
        foreach($yamlP2['table'] as $table=>$tabinfo) {
            foreach($tabinfo['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'uino','N')=='Y') continue;
                $bot=ArraySafe($colinfo,'bottom');
                if(strtolower($bot)=='sum' || strtolower($bot)=='count') {
                    $retval[$colname] = 0;
                    $retcount++;
                }
                else {
                    $retval[$colname] = $bot=='' ? '_' : $bot;
                }
            }
        }
        
        // The idea is to return an empty array if there is
        // nothing to do.
        if($retcount==0) return array();
        return $retval;
    }

    /**
     *  Automated report setup.  Setup an array of break
     *  values if any of the columns actually have
     *  sums, counts, or anything.
     *
     *  @param string $yamlP2  The processed page definition     
     *  @since 4/3/08
     */
    function setupBreak($yamlP2) {
        $retval = array();
        $retcount= 0;
        
        foreach($yamlP2['table'] as $table=>$tabinfo) {
            foreach($tabinfo['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'uino','N')=='Y') continue;
                $bot=strtolower(ArraySafe($colinfo,'break'));
                if($bot=='sum' || $bot=='count') {
                    # this is the value that will be summed or counted
                    $retval[$colname] = 0;
                    $retcount++;
                }
                else {
                    # A magic value 
                    $retval[$colname] = '_';
                }
            }
        }
        
        // The idea is to return an empty array if there is
        // nothing to do.
        if($retcount==0) return array();
        return $retval;
    }
    
    /**
     *  Processes the current row for report end totals
     *  by counting or summing values
     *
     *  @param array $bottom  the current values for bottom
     *  @param array $row     this particular row     
     *  @since 1/31/08
     */
    function processForBottom($yamlP2,$bottom,$row) {
        $x=0;
        $keys=array_keys($row);
        foreach($yamlP2['table'] as $table=>$tabinfo) {
            foreach($tabinfo['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'uino','N')=='Y') continue;
                $bot=strtoupper(ArraySafe($colinfo,'bottom'));
                $val=array_shift($row);
                if($bot=='COUNT') $bottom[$keys[$x]]++;
                if($bot=='SUM')   $bottom[$keys[$x]]+=$val;
                $x++;
            }
        }
        return $bottom;
    }

    /**
     *  Processes the current row for breaking totals
     *
     *  @param array $break   the current values for bottom
     *  @param array $row     this particular row     
     *  @since 4/3/08
     */
    function processForBreak($yamlP2,$bottom,$row) {
        $x=0;
        $keys=array_keys($row);
        foreach($yamlP2['table'] as $table=>$tabinfo) {
            foreach($tabinfo['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'uino','N')=='Y') continue;
                $bot=strtoupper(ArraySafe($colinfo,'break'));
                $val=array_shift($row);
                if($bot=='COUNT') $bottom[$keys[$x]]++;
                if($bot=='SUM')   $bottom[$keys[$x]]+=$val;
                if($bot=='Y')     $bottom[$keys[$x]]=$val;
                $x++;
            }
        }
        return $bottom;
    }
    
    /**
     *  Compares breaking variables to see if we broke
     *
     *  @param array $yamlP2  the processed YAML array
     *  @param array $break   the current values for break
     *  @param array $row     this particular row   
     *  @since 4/3/08
     */
    function compareBreak($yamlP2,$bottom,$row) {
        foreach($yamlP2['table'] as $table=>$tabinfo) {
            foreach($tabinfo['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'break')<>'Y') continue;
                if($bottom[$colname]<>$row[$colname]) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *  The FPDF library that we use (which I love by the way)
     *  requires the functions header() and footer() to be
     *  defined.  Our version of footer() is empty, but header()
     *  uses the YAML page definition to generate a header
     *
     */
    function footer() { }
    function header() {
        $this->CenteredLine($this->title1,'B',$this->fontsize*1.2);
        if($this->title2<>'') {
            $this->CenteredLine($this->title2,'B');
        }
        $this->DateAndPage();
        $this->nextLine();
        $this->outFromArray($this->captions,true);
        $this->LinesForColumns();
    }
    
    /**
     *  Put out a table row containing the headers
     *
     */
    function headerOnScreen() {
        if(!gpExists('x4Page')) {
            echo "<br/><hr/><br/>";
        }
        echo "\n<table id=\"x2data1\"> <!-- generated by androPageReport -->";
        echo "\n<thead><tr class=\"dark\">";
        $this->outFromArray($this->captions,true);
        echo "\n</tr></thead><tbody><tr id='row_0'>";
    }


    /**
     *  Accept column definitions, which are the basis of
     *  many of the features of this class.  Each column
     *  is given a width.  Columns by default are left-justified
     *  unless a ":R" is put after the width, in which case
     *  they are right-justified.  Columns with a ":C" put after
     *  the width will be clipped down to the calculated
     *  width of the column.
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
            $clip=0;
            $money=false;  // WARNING: No longer used as of 4/9/08 KFD
            $colscale = 0;
            foreach($colstuff as $onesetting) {
                if($onesetting=='R') $align='R';
                if(substr($onesetting,0,1)=='C') {
                    $clip=substr($onesetting,1);
                }
                
                //if($onesetting=='M') $money=true;
                if($onesetting>=1 && $onesetting<=9) {
                    $colscale = $onesetting;
                }
            }
            //if(count($colstuff)>0) {
            //    $align = array_shift($colstuff);
            //}
            $this->cols[] = array(
                'xpos'=>$posx
                ,'width'=>$onestop * 72
                ,'align'=>$align
                ,'clip'=>$clip
                ,'colscale'=>$colscale
                ,'money'=>$money
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
     * so that "AtNextCol" will begin at the left.
     */
    function nextLine($titles=false) {
        $this->Ln($this->lineheight);
        $this->lastCol=-1;
        if($this->format=='onscreen') {
            if(!$titles) {
                $this->rowNumber++;
                echo "\n<tr id='row_$this->rowNumber'>";
            }
        }
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
   function outFromArray($alist,$titles=false) {
       $this->source = a($alist,'_source','');
       $this->skey   = a($alist,'skey','');
       if(isset($alist['_source'])) unset($alist['_source']);
       if(isset($alist['skey'])) unset($alist['skey']);
       
       while($value = array_shift($alist)) {
           if($value=='_') $value='';
           $last = count($alist)==0 ? true : false;
           $this->atNextCol($value,$titles,$last);
       }
       $this->nextLine($titles);
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
     * @param string $titles This is true when a line of titles
     *                       is being written.  Suppresses conversion
     *                       of values like numbers and dates.
     */
   function AtCol($col,$text,$titles=false,$last=false) {
       if(!isset($this->cols[$col])) {
           echo "<b>ERROR: Output past last column, did you 
            forget a \$this->nextLine()?";
           exit;
       }
       
       // work out the x position in points, don't mess with y
       $xposition = $this->cols[$col]['xpos'];
       $width     = $this->cols[$col]['width'];
       $align     = $this->cols[$col]['align'];
       if($this->cols[$col]['clip']<>0) {
           //$max = intval($this->cols[$col]['clip']/$this->cpi);
           $text = substr($text,0,$this->cols[$col]['clip']);
       }
       
       // if a money column, reformat value, unless it
       // looks like a 
       //if($this->cols[$col]['money'] && !$titles) {
       //     $text = number_format($text);
       //}
       if($this->cols[$col]['colscale'] > 0 && !$titles) {
            $colscale = $this->cols[$col]['colscale'];
            $text = number_format($text,$colscale);
            
       }
       
       # Onscreen version: just output a table cell
       # PDF version: output a cell and advance column counter
       if($this->format=='onscreen') {
           $class='';
           if($last) $text.="&nbsp;&nbsp;&nbsp;";
           if(a($this->md[$col],'linkpage')<>'' && !$titles) {
               $class='class="link"';
               $link = $this->md[$col]['linkpage'];
               $lsky = a($this->md[$col],'linkskey','N');
               if($lsky=='Y') {
                   $href = '&gp_skey='.$this->skey;
               }
               else {
                   $href = '&pre_'.$this->md[$col]['linkcolumn'].'='.$text;
               }
               if($this->source <>'') {
                   $href.='&gp_source='.$this->source;
               }
               if(gpExists('x4Page')) {
                   $ahref = "?x4Page=$link$href&x4Return=exit";
                   $text="<a href='javascript:\$a.openWindow(\"$ahref\")'
                        >$text</a>";
               }
               else {
                   $text="<a href='?gp_page=$link$href'>$text</a>";
               }
           }
           $align=($align=='R') ? 'right' : 'left';
           echo "<td $class style=\"text-align: $align\">$text</td>";
       }
       else {
           $this->Setx($xposition);
           $this->Cell($width,0,$text,0,0,$align);
       }
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
   function AtNextCol($text,$titles=false,$last=false) {
       $this->AtCol($this->lastCol+1,$text,$titles,$last);
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

    /**
     *  In onscreen mode, end the HTML table.
     *
     *  In PDF mode, output the report and then issue an 
     *  "exit" to end all processing.
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
        if($this->format=='onscreen') {
            echo "\n</table> <!-- androPageReport end -->";
            return;
        }
        header('Pragma:',true);
        $this->Output($name,$nature);
        exit;
    }   
}
?>
