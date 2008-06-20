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

        # Route out to generate help text
        $this->mainHelp();
        
        // If there are no sections, take content and make a section,
        // so that downstream code can unconditionally work with sections

        if(!isset($this->yamlP2['section'])) {
            $this->yamlP2['section'] = array(
                'default'=>array(
                    'table'=>a($this->yamlP2,'table',array())
                    ,'union'=>a($this->yamlP2,'union',array())
                    ,'uifilter'=>a($this->yamlP2,'uifilter',array())
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

        
        // KFD 4/21/08, determine when to display HTML and
        // when to run page
        $runHTML = true;
        if( $this->yamlP2['options']['nofilter'] == 'Y' )   $runHTML = false;
        if( gp('gp_post')<>'' && gp('gp_post')<>'onscreen') $runHTML = false;
        if( gp('gp_post')=='onscreen' && gpExists('x4Page'))$runHTML = false;
        $runPage = true;
        if(gp('gp_post')=='') $runPage = false;
        
        if($runHTML) {
            $this->x3HTML();
        }
        if($runPage) {
            $this->PassPage();
        }        
    }
    
    function mainHelp() {
        ob_start();
        ?>
        This is the <?=$this->yamlP2['options']['title']?> inquiry screen
        <br/><br/>
        The input boxes accept a very flexible set of values,
        you can enter ranges like a-e or 100-200, you can enter
        comparisons like &gt;x or &lt;500, and you can put multiple
        criteria separated by commas, like &lt;b,d,g-k,$gt;x
        <br/><br/>
        Hit CTRL-P to get a printable PDF report, or hit 
        CTRL-O to see the results displayed onscreen.
        <br/><br/>
        When results are displayed onscreen, use the up and down
        arrow keys to navigate, or the pageUp and pageDown keys.
        <br/><br/>
        Sometimes the onscreen results will show hyperlinks to 
        other pages.  Hit rightArrow to jump to the link.
        <br/><br/>
        Hit ESC to clear results, and ESC to return to menu
        <br/><br/>
        <?php
        vgfSet('htmlHelp',ob_get_clean());
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
            # This is for classic x2 displays
            $this->PageSubtitle = $yamlP2['options']['title'];
        }
        
        # There are few tweaks based on x4/x_table2 version
        $x4 = gp('x4Page')=='' ? false : true;
        
        # Hidden variables so posts will come back here
        if($x4) {
            x4Data('return','menu');
            hidden('x4Page',$this->page);
        }
        else {
            hidden('gp_page',$this->page);
        }

        # List of ids for buttons below
        $ids=array('pdf'=>'printNow','onscreen'=>'showOnScreen'
            ,'showSql'=>'showSql'
        );


        # Create top-level div, x4 library is looking for this
        # and x2 library will ignore it.
        $top = html('div');
        $top->hp['id'] = 'x4Top';
        $top->autoFormat(true);
        $x4D = html('div',$top);
        if($x4) $x4D->addClass('x4Pane');
        $x4D->addClass('x4AndroPage'); # Triggers all browser-side x4 stuff
        $x4D->hp['id'] = 'x4AndroPage';
        $x4D->ap['defaultOutput'] = a($ids, a($yamlP2['options'],'default')); 

        # Put out the title and the help link
        $tabx  = html('table',$x4D);
        $tabx->addClass('tab100');
        $tabxtr= html('tr',$tabx);
        $td    = html('td',$tabxtr);
        $td->hp['style'] = "text-align: left; vertical-align: top";
        $h1=html('h1',$td,$this->PageSubtitle);
        $h1->hp['id'] = 'x4H1Top';
        $td    = html('td',$tabxtr);
        $td->hp['style'] = "text-align: right; vertical-align: top;
        padding-top: 8px; font-size: 120%";
        #if($x4) {
            #$a = html('a-void',$td,"F1:Help");
            #$a->hp['onclick'] = "$('#x4AndroPage')[0].help()";
            #$a->addClass('button');
        #}
        
        # Make top level container
        $tabtop = html('table',$x4D);
        $tr = html('tr',$tabtop);
        $td1 = html('td',$tr);
        $td2 = html('td',$tr);
        
        # Do right-hand side first actually, the on-screen display area 
        $div = html('div',$td2);
        $div->hp['id']='divOnScreen';

        # Put out the inputs
        $table = html('table',$td1);
        $filters = ArraySafe($this->yamlP2,'uifilter',array());
        foreach($filters as $id=>$options) {
            if(isset($options['table'])) {
                $dd = ddTable($options['table']);
                $opt2 = $dd['flat'][$options['column']];
                $options = array_merge($opt2,$options);
            }
            else {
                $options['inputId']='ap_'.$id;
            }
            $options['value'] = gp('ap_'.$id);
            $type_id = a($options,'cotype_id','vchar');

            $tr = html('tr',$table);
            $td = html('td',$tr);
            $td->hp['style']="text-align: right";
            $td->setHTML($options['description']);
            $td = html('td',$tr);
            $td->hp['style']="text-align: left";
            $input = input($options);
            $input->hp['autocomplete'] = 'off';
            $td->setHTML($input->bufferedRender());
        }
        if ( isset( $yamlP2['template'] ) ) {
                hidden('gp_post','smarty' );
        } else {
                hidden('gp_post','pdf');
        }
        
        $td1->br();
        
        # First button: print
        $inp = html('a-void',$td1,'<u>P</u>rint Now');
        $inp->ap['xLabel'] = 'CtrlP';
        $inp->hp['id'] = $ids['pdf'];
        $inp->addClass('button');
        if(gpExists('x4Page')) {
            $inp->hp['onclick'] = "\$a.byId('x4AndroPage').printNow()";
        }
        else {
            $inp->hp['onclick'] = 'formSubmit();';
        }
        $td1->br(2);
        
        # Second button: show onscreen
        $inp = html('a-void',$td1,'Show <u>O</u>nscreen');
        $inp->hp['id'] = $ids['onscreen'];
        $inp->ap['xLabel'] = 'CtrlO';
        $inp->addClass('button');
        if(gpExists('x4Page')) {
            $inp->hp['onclick'] = "\$a.byId('x4AndroPage').showOnScreen()";
        }
        else {
            $inp->hp['onclick'] = "SetAndPost('gp_post','onscreen')";
        }
        $td1->br(2);
        
        if(SessionGet('ADMIN')==true && $x4) {
            $x4D->nbsp(2);
            $inp = html('a-void',$td1,'Show S<u>Q</u>L');
            $inp->ap['xLabel'] = 'CtrlQ';
            $inp->hp['id'] = $ids['showSql'];
            $inp->hp['name'] = 'showsql';  // For x2
            $inp->addClass('button');
            $inp->hp['onclick'] = "\$a.byId('x4AndroPage').showSql()";
        }
        
        # Put in the spot where we display the SQL
        $td1->br(2);
        $showSql = html('div',$td1);
        $showSql->hp['id'] = 'divShowSql';
        
        echo $top->render();
    }

    /**
     *  Run the report based on options provided by user.
     *
     *  @param string $yamlP2  A processed YAML page description
     *  @access private
     */
    private function pageReport() {
        // Create the PDF object
        require_once('androPageReport.php');
        $pdf = new androPageReport();
        
        // For each section, run the output
        foreach($this->yamlP2['section'] as $secname=>$secinfo) {
            $dbres = SQL($secinfo['sql']);
            if(gpExists("showsql")) {
                hprint_r($secinfo['sql']);
            }
            if(Errors()) {
                hprint_r($secinfo['sql']);
                echo hErrors();
            }
            if(gpExists("showsql")) {
                return;
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
        if ( ArraySafe( $this->yamlP2['options'], 'noquery', 'N') == 'N' ) {
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
            // Get the values from the UI Filter fields into a temp array,
            // for use below in the column list building
            $uifilter=ArraySafe($this->yamlP2,'uifilter',array());
            foreach($uifilter as $colname=>$info) {
                if(gpExists('ap_'.$colname)) {
                    $this->yamlP2['uifilter'][$colname]['value'] 
                        = gp('ap_'.$colname);
                }
                elseif(isset($info['table'])) {
                    $gp = 'x4inp_'.$info['table'].'_'.$info['column'];
                    $this->yamlP2['uifilter'][$colname]['value'] = gp($gp); 
                }
            }

            foreach($this->yamlP2['section'] as $secname=>$info) {
                $this->yamlP2['section'][$secname]['sql']
                    =$this->genSQLSection($secname);
            }
        }
    }

    /**
     *  Generate the SQL expression for a particular named
     *  section by examining the table/column details in
     *  the processed yaml.  A section can contain:
     *  
     *  a) A UNION block.  The list of tables inside of here
     *       will be unioned together.
     *  b) A list of tables.  These will all be joined together
     *
     *  This routine does not aim to reproduce the entire SQL
     *  SELECT statement.  Mostly it is all about automating
     *  straightforward SQL SELECT statements.
     *
     *  @access private
     */
    function genSQLSection($secname) {
        $yamlP2 = &$this->yamlP2['section'][$secname];
        if(count(ArraySafe( $yamlP2, 'union',array() ) )>0) {
            list($table,$cols) = each($yamlP2['union']['table']);
            $this->yamlP2['table']=array($table=>$cols);
            return $this->genSQLSectionUnion($yamlP2['union']['table']);
        }
        else {
            return $this->genSQLSectionJoin($yamlP2);
        }
        
   }
   
    /**
     *  Takes a list of tables and UNIONs them together
     *  and builds the complete query for them.  Supports
     *  only very limited abilities at this time, basically
     *  assuming the queries are defined correctly and
     *  doing a UNION ALL.
     *
     *  Returns: A SQL SELECT statement
     */
    function genSQLSectionUnion($yamlP2) {
        $page = $this->page;
        $uifilter = $this->page['uifilter'];
        
        $sql=array();
        
        foreach($yamlP2 as $table_id=>$tabinfo) {
            $SQL_COLSWHA = array();
            
            $collist = array("'$table_id' as _source");
            $collist[]='skey';
            foreach($tabinfo['column'] as $column_id=>$colinfo) {
                if(a($colinfo,'constant','')<>'') {
                    $collist[]=SQLFC($colinfo['constant'])." as $column_id";
                }
                else {
                    $collist[]="$table_id.$column_id";
                    # KFD 6/18/08, reroute to new SQLFilter
                    #$compare = sqlFilter($colinfo,
                    $compare=$this->SQLCompare($table_id,$column_id,$colinfo);
                    if($compare<>'') {
                        $SQL_COLSWHA[] = $compare;
                    }
                }
            }
            $sq = "SELECT ".implode("\n      ,",$collist)
                ." FROM $table_id ";
            if(count($SQL_COLSWHA)>0) {
                $sq.="\n WHERE ".implode("\n   AND ",$SQL_COLSWHA);
            }  
            $sql[] = $sq;
        }

        # Build the sql
        $sql = implode("\nUNION ALL\n",$sql);        
        if(gp('gp_post')=='onscreen') $sql.= " LIMIT 300";
        return $sql;
    }   
   
    /**
     *  Takes a list of tables and JOINs them together
     *  and builds the complete query for them.
     *
     *  Returns: A SQL SELECT statement
     */
    function genSQLSectionJoin($yamlP2) {
        $page = $this->page;
        $uifilter = $this->yamlP2['uifilter'];

        // Go get the joins
        $SQL_FROMJOINS=$this->genSQLFromJoins($yamlP2);
        
        $SQL_COLSWHA=array();

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
                        //if(ArraySafe($colinfo,'uino','N')=='N') {
                            $yamlP2['groupby'][]="$table_id.$colname";
                        //}
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
                // order by
                if(ArraySafe($colinfo,'order','N')=='Y') {
                    $SQL_COLSOBA[] = "$table.$colname";
                }
                
                // comparison
                if(isset($colinfo['compare'])) {
                    $compare=$this->SQLCompare($table,$colname,$colinfo);
                    if($compare<>'') {
                        $SQL_COLSWHA[] = $compare;
                    }
                }

                // group by 
                if(a($colinfo,'group','')<>'') {
                    //$coldef = str_replace("as $colname","",$coldef);
                    $coldef = $colinfo['group']."($table.$colname)";
                }
                else {
                    $coldef = "$table.$colname";
                }

                // If not in output, stop now
                // KFD 5/31/08, no, keep going and filter out at 
                //     output.  We need all columns in case they
                //     are orderby columns
                if(a($colinfo,'uino','N')=='Y') continue;
                
                // if a constant, add the constant and skip the rest
                $constant = a($colinfo,'constant','');
                if(ArraySafe($table_info,'left_join','N')=='Y') {
                    $z = SQL_Format(
                        $table_dd['flat'][$colname]['type_id']
                        ,''
                    );
                    $cval = $constant=='' ? $coldef : "'$constant'";
                    $coldef="COALESCE($cval,$z) as $colname";
                }
                else {
                    $coldef = $constant=='' 
                        ? "$coldef as $colname"
                        : "'$constant' as $colname";
                }
                $SQL_COLSA[] = $coldef;
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
        if(gp('gp_post')=='onscreen') $SQ.= " LIMIT 300";
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
            $dd1 = ddTable($table);
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
                $dd=ddTable($table_par);
                $apks=explode(',',$dd['pks']);
                $apks2=array();
                foreach($apks as $apk) {
                    $apks2[]="$table_chd.$apk = $table_par.$apk";
                }
                $SQL_Joins[$table] =array(
                    "expression"=>implode(' AND ',$apks2)
                    ,'view'=>$dd['viewname']
                    ,'left_join'=>ArraySafe(
                        $yamlP2['table'][$table]['left_join'],'N'
                     )
                );
            }
        }

        // Now join them all up and return
        $view_id = ddView($SQL_from);
        $retval = "\n  FROM $view_id $SQL_from ";
        foreach($SQL_Joins as $table_id=>$SQL_Join) {
            $view_id = $SQL_Join['viewname'];
            $expr = $SQL_Join['expression'];
            $left = $SQL_Join['left_join']=='Y' ? 'LEFT ' : '';
            $retval.="\n  {$left}JOIN $view_id $table_id ON $expr";
        }
        return $retval;
    }

   /**
     *  Generate a comparison expression for a column
     *
     *  @param string $table_id the table
     *  @param string $colname  the column
     *  @param string $colinfo  other column information
     *  @access private
     */
    function SQLCompare($table,$colname,$colinfo) {
        // Early return and alternate branch
        if(a($colinfo,'compare','')=='') return;
        if(substr($colinfo['compare'],0,1)=='*') return $this->SQLCompareStar(
            $table,$colname,$colinfo
        );
            
        $noempty = ArraySafe($colinfo,'no_empty_compare','Y');
        $table_dd = ddTable($table);
        $uifilter = $this->yamlP2['uifilter'];     
        $compare = "$table.$colname ".a($colinfo,'compare','');
        foreach($uifilter as $filtername=>$info) {
            if(strpos($compare,'@'.$filtername)!==false) {
                $type_id = $table_dd['flat'][$colname]['type_id'];
                if(a($info,'value','')<>'') {
                    $val = SQL_FORMAT($type_id,$info['value']);
                    if($noempty=='Y' && trim($info['value'])=='') {
                        $compare='';
                        break;
                    }
                    $compare = str_replace(
                        '@'.$filtername,$val,$compare
                    );
                }
            }
        }
        return $compare;
    }

    
   /**
     *  Generate a comparison expression using dashes, commas etc.
     *
     *  @param string $table_id the table
     *  @param string $colname  the column
     *  @param string $colinfo  other column information
     *  @access private
     */
    function SQLCompareStar($table,$colname,$colinfo) {
        // Get the uifilter being used and its value
        // skip the asterisk and the @sign
        $uif_name = substr($colinfo['compare'],2);
        x4Debug($this->yamlP2['uifilter']);
        $uiv_val  = a($this->yamlP2['uifilter'][$uif_name],'value');
        if($uiv_val=='') return '';
        
        // Get data dictionary
        $dd = ddTable($table);
        
        # KFD 6/18/08, route out to the new universal sqlFilter()
        $rv = sqlFilter($dd['flat'][$colname],$uiv_val,$dd['table_id']);
        x4Debug($colname);
        x4Debug($uiv_val);
        x4Debug($rv);
        if($rv<>'') return "(".$rv.")";
        return '';
        #return "(".rff_OneCol($dd['flat'][$colname],$colname,$uiv_val).")";
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
