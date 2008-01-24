<?php
/**
 *
 * Parses .page.yaml files and generates various
 * outputs.  The two main stages are 1) generating forms
 * requesting user input and 2) displaying results.
 * 
 * Special thanks go to Donald Organ for inspiring what is
 * probably the most important strategic aspect of this 
 * class.  This class allows the Andromeda programmer to 
 * continue to use YAML in application development, and further
 * strengthens our strategy of putting application assets
 * into data files instead of code.
 *
 * @package androPage
 * @author Kenneth Downs <ken@secdat.com>
 *
 *
 * 
*/

class androPage {
    /**
     *  Included for compatibility with index_hidden.php. 
     *  Currently hardcoded to true.
     *  @var flag_buffer
     *  @access public
     */
    var $flag_buffer = true;

    /**
     *  The larger framework wants to see this, it uses it
     *  to assign the HTML TITLE.  Taken from the YAML element
     *  options:title
     *
     *  @var PageSubtitle
     *  @access public
     */
    var $PageSubtitle = '-Please Set PageSubtitle- or options:title';

    /**
     *  The page being processed.
     *  @var page
     *  @access public
     */
    var $page = '';
    
    /**
     *  Entry point for all processing.  Assumes a file
     *  named "$page.page.yaml" is in the application
     *  directory.
     *
     *  @param string $page   Name of page. 
     *  @access public
     */
    function main($page) {
        // Store the name of the page
        $this->page = $page;
        
        $filename
            =$GLOBALS['AG']['dirs']['root']
            ."application/$page.page.yaml";
        
        include_once("spyc.php");
        $yamlRaw=Spyc::YAMLLoad($filename);
        $this->yamlP2=$this->YAMLPass2($yamlRaw);
        
        // If there are no sections, take content and make a section,
        // so that downstream code can unconditionally work with sections
        if(!isset($this->yamlP2['section'])) {
            $this->yamlP2['section'] = array(
                'default'=>array(
                    'table'=>$this->yamlP2['table']
                    ,'uifilter'=>$this->yamlP2['uifilter']
                )
            );
        }

        switch(gp('gp_post')) {
        case '':
            $this->x3HTML();
            break;
        case 'pdf':
            $this->genSQL();
            $this->pageReport();
            break;
        case 'smarty':
            $this->genSQL();
            $this->pageSmarty();
            break;
        }
    }   
    
    /**
     *  Part of the YAML Processing arrangement. 
     *  Andromeda maintains a set of conventions for using YAML,
     *  which requires that we make a second pass of the associative
     *  array to re-shuffle it a bit.
     *
     *  This routine calls itself recursively on sub arrays.
     *
     *  @param string $array  The raw result of processing by Spyc. 
     *  @access private
     */
    private function YamlPass2($array) {
        if(!is_array($array)) return $array;
        
        $retval = array();
        foreach($array as $index=>$subarr) {
            $aIdx = explode(" ",$index);
            if(count($aIdx)==1) {
                $retval[$index] = $this->yamlPass2($subarr);
            }
            else {
                $retval[$aIdx[0]][$aIdx[1]] = $this->yamlPass2($subarr);
            }
        }
        return $retval;
    }
    
    /**
     *  Generates an x3 HTML page requesting input from the
     *  user.  No parameters, accesses the object's yamlP2 property. 
     *
     *  @access private
     */
    private function x3HTML() {
        $yamlP2 = $this->yamlP2;
        if(isset($yamlP2['options']['title'])) {
            $this->PageSubtitle = $yamlP2['options']['title'];
        }
        
        $table = createElement('table');
        $filters = ArraySafe($yamlP2,'uifilter',array());
        foreach($filters as $id=>$options) {
            $type_id = ArraySafe($options,'type_id','vchar');

            $tr = createElement('tr');
            $td  = createElement('td');
            $td->style['text-align'] = 'right';
            $td->innerHTML = $options['description'];
            $tr->appendChild($td);
            $td  = createElement('td');
            $td->style['text-align'] = 'left';
            $td->innerHTML = hWidget($type_id,'ap_'.$id);
            $tr->appendChild($td);
            $table->appendChild($tr);
        }
        hidden('gp_page',$this->page);
        hidden('gp_post','pdf');
        ?>
        <!-- x3HTML Render -->
        <br/><h1><?=$this->PageSubtitle?></h1>
        <?=$table->render()?>
        <br/>
        <input type="submit" value="F10: Print Now" id="object_for_f10"
            onclick="javascript:formPost()"
        ></input>
        <?php
        echo "\n<!-- x3HTML Render (END) -->\n";
    }    

    /**
     *  Run the report based on options provided by user.
     *
     *  @param string $yamlP2  A processed YAML page description 
     *  @access private
     */
    private function pageReport() {
        // Create the PDF object
        require_once('androPagePDF.php');
        $pdf = new androPagePDF();
        
        // For each section, run the output
        foreach($this->yamlP2['section'] as $secname=>$secinfo) {
            $dbres = SQL($secinfo['sql']);
        
            // Now pass the SQL resource to the reporting engine
            $pdf->main($dbres,$this->yamlP2,$secinfo);
        }
    }

    /**
     *  Run the report on-screen as a smarty template
     *
     */
    private function pageSmarty() {
        // Execute SQL and return all rows for all sections
        $sections = $this->yamlP2['section'];
        foreach($sections as $secname=>$secinfo) {
            $this->yamlP2['section'][$secname]['rows']
                =SQL_AllRows($secinfo['sql']);
        }
        
        // Create the Smarty handler and call out to that
        require_once('androPageSmarty.php');
        $smarty = new androPageSmarty();
        
        // Now pass the whole ball of wax to the smarty handler
        $smarty->main($this->yamlP2,$this->page);        
    }
    
    /**
     *  Generate the SQL expression for each section by 
     *  examining the table/column information.  This routine
     *  actually recurses the sections and invokes genSQLSection
     *  for each one.
     *  
     *  @access private
     */
   function genSQL() {
       foreach($this->yamlP2['section'] as $secname=>$info) {
           $this->yamlP2['section'][$secname]['sql']
            =$this->genSQLSection($secname);
       }
   }
   
    /**
     *  Generate the SQL expression for a particular named
     *  section by examining the table/column details in
     *  the processed yaml.
     *  
     *  @access private
     */
   function genSQLSection($secname) {
        $yamlP2 = $this->yamlP2['section'][$secname];
        $page = $this->page;
    
        // Go get the joins
        $SQL_FROMJOINS=$this->genSQLFromJoins($yamlP2);

        // Get the values from the UI Filter fields into a temp array,
        // for use below in the column list building
        $SQL_COLSWHA=array();
        $uifilter=ArraySafe($this->yamlP2,'uifilter',array());
        foreach($uifilter as $colname=>$info) {
            $uifilter[$colname]['value'] = gp('ap_'.$colname);
        }

        // Build various lists of columns
        $SQL_COLSA=array();
        $SQL_COLSOBA=array();
        foreach($yamlP2['table'] as $table=>$table_info) {
            $table_dd = dd_TableRef($table);
            foreach($table_info['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'uino','N')<>'Y') {
                    if(ArraySafe($table_info,'left_join','N')=='Y') {
                        $z = SQL_Format(
                            $table_dd['flat'][$colname]['type_id']
                            ,''
                        );
                        $SQL_COLSA[]="COALESCE($table.$colname,$z) as $colname";
                    }
                    else {
                        $SQL_COLSA[]=$table.'.'.$colname;
                    }
                }
                
                if(isset($colinfo['compare'])) {
                    $compare = $colname.' '.$colinfo['compare'];
                    foreach($uifilter as $filtername=>$info) {
                        $type_id = $table_dd['flat'][$colname]['type_id'];
                        $val = SQL_FORMAT($type_id,$info['value']);
                        $compare = str_replace('@'.$filtername,$val,$compare);
                    }
                    $SQL_COLSWHA[] = $compare;
                }
            }
        }
        
        // Collapse the lists into strings
        $SQL_COLS=implode("\n       ,",$SQL_COLSA);
        $SQL_COLSOB='';
        if(count($SQL_COLSOBA)>0) {
            ksort($SQL_COLSOBA);
            $SQL_COLSOB="\n ORDER BY ".implode(',',$SQL_COLSOBA);
        }
        
        // For the UI Filter values, add in the values provided by the user
        $SQL_WHERE='';
        if(count($SQL_COLSWHA)>0) {
            $SQL_WHERE = "\n WHERE ".implode("\n   AND ",$SQL_COLSWHA);
        }
        
        // Now build the final SQL
        $SQ=" SELECT "
            .$SQL_COLS
            .$SQL_FROMJOINS
            .$SQL_WHERE
            .$SQL_COLSOB;
         
        return $SQ;
    }
    
    /**
     *  Generate a list of FROM and JOIN commands out of the
     *  processed YAML page description.  Assume the first entry
     *  is the FROM table and that all entries will join to 
     *  something above them.
     *
     *  @param array $yamlP2 The processed page description 
     *  @access private
     */
    function genSQLFromJoins($yamlP2) {
        // Get the list of tables and pop off the first one
        $tables = array_keys($yamlP2['table']);
        $SQL_from = array_shift($tables);
        $SQL_Joins = array();
        $tables_done=array($SQL_from);

        // Loop through children looking for which of the
        // parents they can join to
        foreach($tables as $table) {
            $dd1 = dd_TableRef($table);
            $table_par = '';
            foreach($tables_done as $table_done) {
                if(isset($dd1['fk_parents'][$table_done])) {
                    // Make the assignments
                    $table_par = $table_done;
                    // Grab the pk and build an expression
                    $dd=dd_TableRef($table_par);
                    $apks=explode(',',$dd['pks']);
                    $apks2=array();
                    foreach($apks as $apk) {
                        $apks2[]="$table.$apk = $table_par.$apk";
                    }
                    $SQL_Joins[$table] =array(
                        "expression"=>implode(' AND ',$apks2)
                        ,'left_join'=>ArraySafe(
                            $yamlP2['table'][$table]['left_join'],'N'
                         )
                    );
                    break;
                }
            }
            if($table_par == '') {
                $this->errorAdd("Table $table does not join to any "
                    ."previously listed table."
                );
            }
        }
        
        // Now join them all up and return
        $retval = "\n  FROM $SQL_from ";
        foreach($SQL_Joins as $table_id=>$SQL_Join) {
            $view_id = ddTable_idResolve($table_id);
            $expr = $SQL_Join['expression'];
            $left = $SQL_Join['left_join']=='Y' ? 'LEFT ' : '';
            $retval.="\n  {$left}JOIN $table_id ON $expr";
        }
        return $retval;
    }

    /**
     *  Placeholder error function since our current error system
     *  may not deal with processing errors that well.
     *
     *  @param string $message The error message 
     *  @access private
     */
    function errorAdd($message) {
        ErrorAdd($message);
    }
}
?>
