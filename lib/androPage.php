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
                    'table'=>(isset( $this->yamlP2['table'] ) ? $this->yamlP2['table'] : '' )
                    ,'uifilter'=>(isset( $this->yamlP2['uifilter'] ) ? $this->yamlP2['uifilter'] : '' )
                )
            );
        }

        if (!isset($this->yamlP2['template'] ) ) {
                $this->yamlP2['template'] = '';
        }

        // Go through filters and make them all uniform
        $filters = ArraySafe($this->yamlP2,'uifilter',array());
        foreach($filters as $id=>$info) {
            // If a table is named, go for that
            if(isset($info['table'])) {
                $table_dd = dd_TableRef($info['table']);
                $column   = ArraySafe($info,'column',$id);
                $flat     = $table_dd['flat'][$column];
                $filter = &$this->yamlP2['uifilter'][$id];
                $filter['type_id'] =$flat['type_id'];
                $filter['colprec'] =$flat['colprec'];
                $filter['colscale']=$flat['colscale'];
                $filter['description']=
                    ArraySafe($filters[$id],'description',$flat['description']);
            }
        }
        if ( ArraySafe( $this->yamlP2['options'], 'buffer', 'Y' ) == 'N' ) {
                $this->flag_buffer = false;
        }

        // Check to see if nofilter option is set
        if ( ArraySafe( $this->yamlP2['options'], 'nofilter') != '' ) {
                $this->yamlP2['options']['nofilter'] = $this->yamlP2['options']['nofilter'];
        } else {
                $this->yamlP2['options']['nofilter'] = 'N';
        }

        //If nofilter option is set to Y then display without filter
        if ( $this->yamlP2['options']['nofilter'] == 'Y' ) {
                $this->PassPage();
        } else {
                if ( gp( 'gp_post' ) == '' ) {
                    $this->x3HTML();
                } else {
                    $this->PassPage();
                }
        }
    }

    /**
     * This function determines whether it should make the page a report or a Smarty template
     * @access private
     */
    private function PassPage() {
        $this->genSQL();
        if ( $this->yamlP2['template'] == '' ) {
                $this->pageReport();
        } else {
                $this->pageSmarty();
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
        $filters = ArraySafe($this->yamlP2,'uifilter',array());
        foreach($filters as $id=>$options) {
            $type_id = ArraySafe($options,'type_id','vchar');

            $tr = createElement('tr');
            $td  = createElement('td');
            $td->style['text-align'] = 'right';
            $td->innerHTML = $options['description'];
            $tr->appendChild($td);
            $td  = createElement('td');
            $td->style['text-align'] = 'left';
            $td->innerHTML = $this->h3Widget($id,$options);
            $tr->appendChild($td);
            $table->appendChild($tr);
        }
        hidden('gp_page',$this->page);
        if ( isset( $yamlP2['template'] ) ) {
                hidden('gp_post','smarty' );
        } else {
                hidden('gp_post','pdf');
        }
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

    function h3Widget($id,$options) {
        // Simple case is no lookup, use hWidget()
        if(ArraySafe($options,'lookup','N')=='N') {
            // hwidget uses length of value to be length of
            // box, so pass it suitably long string
            $value = '';
            if(ArraySafe($options,'colprec','')<>'') {
                $value = str_pad('',$options['colprec']);
            }
            return hWidget($options['type_id'],'ap_'.$id,$value,100);
        }
        else {
            // If doing a lookup we have to go to other code
            $tab = dd_tableRef($options['table']);
            $tab['flat'][$id]['table_id_fko'] = $options['table'];
            $tab['flat'][$id]['fkdisplay']='dynamic';
            $acols=aColsModeProj($tab,'upd');
            $acols[$id]['writable']=true;
            $ahcols=aHColsfromACols($acols);
            return WidgetFromAhCols(
                $ahcols,$id,'ap_','',0
            );
        }
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
            if(Errors()) {
                hprint_r($secinfo['sql']);
                echo hErrors();
            }

            // Now pass the SQL resource to the reporting engine
            $pdf->main($dbres,$this->yamlP2,$secinfo);
        }
    }

    /**
     *  Run the report on-screen as a smarty template
     *
     */
    private function pageSmarty() {
        if ( ArraySafe( $this->yamlP2['options']['noquery'],'N') == 'N' ) {
            // Execute SQL and return all rows for all sections
            $sections = $this->yamlP2['section'];
            foreach($sections as $secname=>$secinfo) {
                    $this->yamlP2['section'][$secname]['rows']
                            =SQL_AllRows($secinfo['sql']);
            }
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
        if ( ArraySafe( $this->yamlP2['options'], 'noquery','N') == 'N' ) {
            foreach($this->yamlP2['section'] as $secname=>$info) {
                $this->yamlP2['section'][$secname]['sql']
                 =$this->genSQLSection($secname);
            }
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

        // See if any of the columns have a GROUP setting,
        // if so, all others must get group: Y
        $yamlP2['groupby']=array();
        $group=false;
        foreach($yamlP2['table'] as $table_id=>$tabinfo) {
            foreach($tabinfo['column'] as $colname=>$colinfo) {
                if(ArraySafe($colinfo,'group','')<>'') {
                    $group=true;
                    break;
                }
            }
        }
        if($group) {
            foreach($yamlP2['table'] as $table_id=>$tabinfo) {
                foreach($tabinfo['column'] as $colname=>$colinfo) {
                    if(ArraySafe($colinfo,'group','')=='') {
                        if(ArraySafe($colinfo,'uino','N')=='N') {
                            $yamlP2['groupby'][]="$table_id.$colname";
                        }
                    }
                }
            }
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
                        $coldef="COALESCE($table.$colname,$z) as $colname";
                    }
                    else {
                        $coldef=$table.'.'.$colname;
                    }
                    if(ArraySafe($colinfo,'group','')<>'') {
                        $coldef = $colinfo['group'].'('.$coldef.") as $colname";
                    }
                    $SQL_COLSA[] = $coldef;
                }

                if(isset($colinfo['compare'])) {
                    $compare = "$table.$colname ".$colinfo['compare'];
                    foreach($uifilter as $filtername=>$info) {
                        if(strpos($compare,'@'.$filtername)!==false) {
                            $type_id = $table_dd['flat'][$colname]['type_id'];
                            $val = SQL_FORMAT($type_id,$info['value']);
                            $compare = str_replace(
                                '@'.$filtername,$val,$compare
                            );
                        }
                    }
                    $SQL_COLSWHA[] = $compare;
                }
            }
        }

        // Collapse the lists into strings
        $SQL_COLS=implode("\n       ,",$SQL_COLSA);
        $SQL_COLSOB='';
        if ( isset( $yamlP2['orderby'] ) ) {
                $SQL_COLSOB="\n ORDER BY " .$yamlP2['orderby'];
        } else {
                if(count($SQL_COLSOBA)>0) {
                    ksort($SQL_COLSOBA);
                    $SQL_COLSOB="\n ORDER BY ".implode(',',$SQL_COLSOBA);
                }
        }

        // For the UI Filter values, add in the values provided by the user
        $SQL_WHERE='';
        if(count($SQL_COLSWHA)>0) {
            $SQL_WHERE = "\n WHERE ".implode("\n   AND ",$SQL_COLSWHA);
        }

        // Collapse the group by
        $SQL_GROUPBY = '';
        if(count($yamlP2['groupby'])>0) {
            $SQL_GROUPBY = "\n GROUP BY ".implode(',',$yamlP2['groupby']);
        }

        // Now build the final SQL
        $SQ=" SELECT "
            .$SQL_COLS
            .$SQL_FROMJOINS
            .$SQL_WHERE
            .$SQL_GROUPBY
            .$SQL_COLSOB;
<<<<<<< .mine

=======
        
>>>>>>> .r218
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
            $table_par = ArraySafe(
                $yamlP2['table'][$table],'table_par',''
            );
            $table_chd = $table;
            $table_join= $table_par;
            if($table_par=='') {
                foreach($tables_done as $table_done) {
                    if(isset($dd1['fk_parents'][$table_done])) {
                        $table_par = $table_done;
                        $table_chd = $table;
                        break;
                    }
                    elseif(isset($dd1['fk_children'][$table_done])) {
                        // Cause the loop to tstop
                        $table_par = $table;
                        $table_chd = $table_done;
                        break;
                    }
                }
            }
            if($table_par=='') {
                $this->errorAdd("Table $table does not join to any "
                    ."previously listed table."
                );
            }
            else {
                $tables_done[] = $table;
                $dd=dd_TableRef($table_par);
                $apks=explode(',',$dd['pks']);
                $apks2=array();
                foreach($apks as $apk) {
                    $apks2[]="$table_chd.$apk = $table_par.$apk";
                }
                $SQL_Joins[$table] =array(
                    "expression"=>implode(' AND ',$apks2)
                    ,'left_join'=>ArraySafe(
                        $yamlP2['table'][$table]['left_join'],'N'
                     )
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
