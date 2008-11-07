<?php
/****c* PHP-API/androX6
*
* NAME
*   androX4
*
* FUNCTION
*   The PHP class androX6 is the base class for all Andromeda
*   pages.  It is used by the framework for "free" Table-Maintenance
*   screens and is also the basis of custom screens.
*
*   Making a subclass of androX6 begins with the Page-Name, and
*   is formed as "x6".Page-Name.  The file name is also 
*   "x6".Page-Name.".php".  
*
*   When calling a page, use the parameter x6Page, as in:
*
*          <a href="index.php?x6Page=example">
*
*
* 
******
*/
class androX6 {
    # ===================================================================
    #
    # Major Area 0: User overridable functions 
    #
    # ===================================================================
    /****m* androX6/x6main
    *
    * NAME
    *   androX6/x6main
    *
    * PURPOSE
    *   If you want to make a purely custom web page then 
    *   use the x6main() method to generate and display your html.
    *
    ******
    */
    function x6main() { 
        ?>
        <h1>Unprogrammed page</h1>
        
        <p>The programmer has made a call to a page that has no
           profile and no custom code.  
        </p>
        <?php
    }
    # ===================================================================
    #
    # SERVER FUNCTION 2: Return a single row when asked 
    #
    # ===================================================================
    /**
      * Return a single row for a table
      *
      */
    function fetchRow() {
        $table_id = gp('x6page');
        $dd = ddTable($table_id);
        $view = $dd['viewname'];

        SQL("set datestyle to US");
        SQL("set datestyle to SQL");
        $skey = SQLFN(gp('x4w_skey'));
        $row = SQL_OneRow("SELECT * FROM $view WHERE skey=$skey");
        x4Data('row',$row);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 3: Execute an skey-based update or insert 
    #
    # ===================================================================
    function save() {
        $table_id = gp('x6page');
        $dd = ddTable($table_id);
        
        $row0 = aFromGP('x4v_');
        $row1 = aFromgp('x4inp_'.$table_id.'_');
        $row = array_merge($row0,$row1);
        if(a($row,'skey',0)==0) unset($row['skey']);
        
        # an skeyAfter value means we must find the queuepos
        # column in this table, and save a value of that 
        # column equal to +1 of the value in row skeyAfter
        if(gp('skeyAfter',false)!==false) {
            foreach($dd['flat'] as $colname=>$colinfo) {
                if(strtolower($colinfo['automation_id'])=='queuepos') {
                    $queuepos = $colname;
                    break;
                }
            }

            if(gp('skeyAfter')==0) {
                $row[$queuepos] = 1;
            }
            else {
                $qpvalue = SQL_OneValue($queuepos,
                    "Select $queuepos from {$dd['viewname']}
                    where skey = ".sqlfc(gp('skeyAfter'))
                );
                $qpvalue++;
                $row[$queuepos] = $qpvalue;
            }
        }
        

        # KFD 6/28/08, a non-empty date must be valid
        $errors = false;
        foreach($row as $col => $value) {
            if(!isset($dd['flat'][$col])) {
                unset($row[$col]);
                continue;
            }
            $ermsg = "Invalid date format for "
                .$dd['flat'][$col]['description'];
            $ermsg2 = "Invalid date value for "
                .$dd['flat'][$col]['description'];
            if($dd['flat'][$col]['type_id'] == 'date') {
                if(trim($value)=='') continue;
                
                if(strpos($value,'/')===false && strpos($value,'-')===false) {
                    x4Error($ermsg);
                    $errors = true;
                    continue;
                }
                if(strpos($value,'/')!==false) {
                    $parsed = explode('/',$value);
                    if(count($parsed)<>3) {
                        $errors = true;
                        x4Error($ermsg);
                        continue;
                    }
                    if(!checkdate($parsed[0],$parsed[1],$parsed[2])) {
                        x4Error($ermsg2);
                        $errors = true;
                        continue;
                    }
                }
                if(strpos($value,'-')!==false) {
                    $parsed = explode('-',$value);
                    if(count($parsed)<>3) {
                        $errors = true;
                        x4Error($ermsg);
                        continue;
                    }
                    if(!checkdate($parsed[1],$parsed[2],$parsed[0])) {
                        x4Error($ermsg2);
                        $errors = true;
                        continue;
                    }
                }
            }
        }
        if($errors) return;
        
        if(!isset($row['skey'])) {
            $skey = SQLX_Insert($dd,$row);
            if(!errors()) {
                $row=SQL_OneRow(
                    "Select * FROM {$dd['viewname']} WHERE skey = $skey"
                );
            }
            x4Data('row',$row);
        }
        else {
            SQLX_Update($dd,$row);
            if(!errors()) {
                $skey = $row['skey'];
                $row=SQL_OneRow(
                    "Select * FROM {$dd['viewname']} WHERE skey = $skey"
                );
                x4Debug($row);
                x4Data('row',$row);
            }
        }
    }
    
    # ===================================================================
    #
    # SERVER FUNCTION 4: Execute an skey-based delete 
    #
    # ===================================================================
    /**
      * Execute an skey-based delete
      *
      */
    function delete() {
        $view = ddView(gp('x6page'));
        $skey = SQLFN(gp('skey'));
        $sq="Delete from $view where skey = $skey";
        if(Errors()) {
            x4Errors(hErrors());
        }
        SQL($sq);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 5: Fetch values from other tables based on an FK
    #
    # ===================================================================
    /**
      * Go get FETCH values from other tables
      *
      */
    function fetch() {
        // Get the list of columns from the dd
        $column_id    = gp('column');
        $table_id     = $this->dd['table_id'];
        $table_id_fko = $this->dd['flat'][$column_id]['table_id_fko'];
        $match = $table_id.'_'.$table_id_fko.'_';
        $collist = $this->dd['FETCHDIST'][$match];
        
        // Build the SQL to fetch the row
        $colsc= array();
        $colsp= array();
        foreach($collist as $idx=>$info) {
            $colsp[] = $info['column_id_par'].' as '.$info['column_id'];
        }
        $type_id = $this->dd['flat'][$column_id]['type_id'];
        $value   = SQL_Format($type_id,gp('value'));
        $sql="SELECT ".implode(',',$colsp)
            ."  FROM ".ddTable_idResolve($table_id_fko)
            ." WHERE ".$this->dd['fk_parents'][$table_id_fko]['cols_par']."= $value";
        $answer = SQL_OneRow($sql);
        x4Data('fetch',$answer);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 6: fetch browse/grid values
    #
    # ===================================================================
    function browseFetch() {
        #  This is the list of columns to return 
        $acols = explode(',',$this->dd['projections']['_uisearch']);

        #  By default the search criteria come from the 
        #  variables, unless it is a child table search
        $vals = aFromGP('x6w_');
        $awhere = array();
        $tabPar = gp('tableIdPar');
        if($tabPar<>'') {
            $ddpar = ddTable(gp('tableIdPar'));
            $pks   = $ddpar['pks'];
            $stab  = ddView(gp('tableIdPar'));
            $skey  = SQLFN(gp('skeyPar'));
            $vals2 = SQL_OneRow("SELECT $pks FROM $stab WHERE skey = $skey");
            if(!$vals2) $vals2=array();
            $vals  = array_merge($vals,$vals2);
        }
        
        # Build the where clause        
        #
        $this->flat = $this->dd['flat'];
        foreach($vals as $column_id=>$colvalue) {
            if(!isset($this->flat[$column_id])) continue;
            $colinfo = $this->flat[$column_id];
            $exact = isset($vals2[$column_id]);
            
            //$tcv  = trim($colvalue);
            $tcv = $colvalue;
            $type = $colinfo['type_id'];
            if ($tcv != "") {
                // trap for a % sign in non-string
                $xwhere = sqlFilter($this->flat[$column_id],$tcv);
                if($xwhere<>'') $awhere[] = "($xwhere)";
            }
        }
        
        # <----- RETURN
        if(count($awhere) == 0) { x4Debug("returning"); return; }
        
        # Generate the limit
        $SLimit = ' LIMIT 100';
        if($tabPar <> '') {
            if(a($this->dd['fk_parents'][$tabPar],'uiallrows','N')=='Y') {
                $SLimit = '';
            }
        }
        

        #  Build the Order by
        #        
        $ascDesc = gp('sortAD')=='ASC' ? ' ASC' : ' DESC';
        $aorder = array();
        $searchsort = trim(a($this->dd,'uisearchsort',''));
        if(gpExists('sortAD')) {
            $aorder[] = gp('sortCol').' '.gp('sortAD');
        }
        if($searchsort <> '') {
            $aocols = explode(",",$searchsort);
            foreach($aocols as $pmcol) {
                $char1 = substr($pmcol,0,1);
                $column_id = substr($pmcol,1);
                if($char1 == '+') {
                    $aorder[] = $column_id.' ASC';
                }
                else {
                    $aorder[] = $column_id.' DESC';
                }
            }
            $SQLOrder = " ORDER BY ".implode(',',$aorder);
        }
        else {
            # KFD 6/18/08, new routine that works out sort 
            $aorder = sqlOrderBy($vals);
            if(count($aorder)==0) {
                $SQLOrder = '';
            }
            else {
                $SQLOrder = " ORDER BY ".implode(',',$aorder);
            }
        }
        
        # just before building the query, drop out
        # any columns that have a table_id_fko to the parent
        foreach($acols as $idx=>$column_id) {
            if($this->flat[$column_id]['table_id_fko'] == $tabPar
                && $tabPar <> '') {
                unset($acols[$idx]);
            }
        }
        
        // Build the where and limit
        $SWhere = ' WHERE '.implode(' AND ',$awhere);

        // Retrieve data
        $SQL ="SELECT skey,".implode(',',$acols)
             ."  FROM ".$this->dd['viewname']
             .$SWhere
             .$SQLOrder
             .$SLimit;
        $answer =SQL_AllRows($SQL);
        
        # Now make up the generic div and add all of the cells
        $grid = new androHTMLTabDiv(500);
        $this->tabDivGeneric($grid,$this->dd);
        $grid->addData($answer);
        ob_start();
        foreach($grid->dbody->children as $child) {
            $child->render();
        }
        x4Html('browseFetchHtml',ob_get_clean());
        return;
    }
    
    # ===================================================================
    # *******************************************************************
    #
    # Profile 0: "conventional"
    #
    # *******************************************************************
    # ===================================================================
    function profile_conventional() {
        # Grab the data dictionary for this table
        $dd       = $this->dd;
        $table_id = $this->dd['table_id'];

        # Create the top level div as a table controller
        $top=html('div');
        $top->addClass('fadein');
        $top->hp['x6plugin'] = 'tableController';
        $top->hp['x6table']  = $table_id;
        $top->hp['id']       = 'tc_'.$table_id;
        
        # Various heights.  Assume all borders are the same and get
        # only one.
        $hinside = x6cssDefine('insideheight');
        $hh1     = x6cssHeight('h1');
        $htabs   = x6cssHeight('.ui-tabs-nav li');
        $hborder = x6cssRuleSize('ui-tabs-panel','border-top');
        $pad0    = x6cssDefine('pad0');
        $hlh     = x6CssDefine('lh0');
        
        # $hpane1 is the outer, it is what is left after removing
        # h1, one row of tabs, and padding at bottom.
        $hpane1  = $hinside - $hh1 - $htabs - ($pad0 * 2);
        
        # $hchild is the height of the empty nested tab
        # pane, hardcoded at pad0
        $hempty  = $pad0;
        
        # $hdetail is the height of the detail pane inside of the 
        # detail tab.  It is the $hpane1 less another tab (the 
        # nested one), and $hempty, and a double padding
        $hdetail = $hpane1 - $htabs - $hempty - ($pad0 * 2);
        
        #echo "<br/>inside height available ".x6cssDefine('insideheight');
        #echo "<br/>Height of tabs main: $height";
        #echo "<br/>Total height kids tabs and panes: $kidsheight";
        #echo "<br/>Pane height kids: $kidpaneheight";
        
        # Begin with title and tabs
        $top->h('h1',$dd['description']);
        $tabs = $top->addTabs('tabs_'.$table_id,$hpane1);
        $lookup = $tabs->addTab('Lookup');
        $detail = $tabs->addTab('Detail',true);

        # Make a generic tabDiv, which will show all uisearch 
        # columns, and add a row of lookup inputs to it.  
        # Enclose it in a div that gives some padding on top
        # and bottom.  Divide up the left-right free space to
        # put 1/3 on the left and the remaining on the right.
        $divgrid = $lookup->h('div');
        $grid = $divgrid->addTabDiv($hpane1 - ($hlh*2),true);
        $gridWidth = $this->tabDivGeneric($grid,$dd);
        # Work out the available free width after making the grid
        $wAvail = x6cssDefine('insidewidth')
            - 2 // hardcoded assumption of border of tab container
            - (x6cssDefine('pad0')*2)  // padding outside of tab container
            - $gridWidth;
        $divgrid->hp['style']=
            "padding-left: ".intval($wAvail/3)."px;
             padding-top: ".x6CSSDefine('lh0')."px;";

        # Find out how many child tables there are.  We are making
        # the assumption that there will *always* be child tables
        # because otherwise the programmer would have selected
        # a different profile.
        #
        $divDetail = $detail->h('div');
        include 'x6plugindetailDisplay.php';
        $x6detail = new x6plugindetailDisplay;
        $x6detail->main($divDetail,$dd,$hdetail);
        
        
        # The div kids is a tabbar of 
        #$divKids = $detail->h('div');
        #$divKids->hp['style'] = "height: {$bheight}px;"; 
        #$divKids->addClass('x6childtabs');
        $tabKids = $detail->addTabs('kids_'.$table_id,$hempty);
        $tab = $tabKids->addTab("Hide");
        foreach($dd['fk_children'] as $child=>$info) {
            $tab = $tabKids->addTab($info['description']);
            #$tab->hp['style'] 
            #    ="height: {$kidpaneheight}px; overflow-y: scroll";
            /*
            $grid = $tab->addTabDiv($kidpane);
            $grid->hp['x6table']   = $child;
            $grid->hp['id']        = 'tabDiv_'.$child;
            $grid->hp['xSortable'] = 'Y';
            $ddChild = ddTable($child);
            $aColumns= explode(',',$ddChild['projections']['_uisearch']);            
            foreach($aColumns as $column) {
                $grid->addColumn($ddChild['flat'][$column]);
            }
            */
        }
        
        $top->render();
    }

    # ===================================================================
    # *******************************************************************
    #
    # Profile 1: "twosides"
    #
    # *******************************************************************
    # ===================================================================
    function profile_twosides() {
        # Grab the data dictionary for this table
        $dd       = $this->dd;
        $table_id = $this->dd['table_id'];
        
        # Notice we are NOT sending the data dictionary up.
        # We are hoping to avoid that by setting properties
        # directly onto controls and plugins.
        #jqDocReady("x6dd.tables.$table_id = ".json_encode($dd));


        # Get the standard padding, we are going to double it
        $pad0 = x6CSSDefine('pad0');
        $heightRemain = x6cssDefine('insideheight');
        
        # Now put in your basic title
        $div = html('div');
        $div->addClass('fadein');
        $div->h('h1','<center>'.$dd['description'].'</center>');
        $heightRemain -= x6cssHeight('h1');
        $heightRemain -= ($pad0*2);
        
        # Make the div into a table controller.  There always
        # has to be a table controller somewhere.
        $div->hp['id'] = 'tc_'.$table_id;
        $div->ap['x6plugin'] = 'tableController';
        $div->ap['x6table']  = $table_id;
        $div->ap['xCache']   = 'Y';  // results will be cached
        
        # Create a two-sided layout by creating two boxes
        # Left side is a grid plugin
        $area0 = $div->h('div');
        $area0->hp['style'] = "float: left; 
            padding-left: {$pad0}px;
            padding-right: {$pad0}px;";
        include 'x6plugingrid.php';
        $x6grid = new x6plugInGrid;
        $x6grid->main($area0,$dd,0,$heightRemain);
        
        # Calculate how much width is left
        $wInner = x6CSSDefine('insidewidth');
        $wInner-=$x6grid->width;
        $wInner-=2;  // assume a border on the grid
        $wInner-=2;  // assume a border on the right-side
        $wInner-= ($pad0*6); // 3 times padding doubled
        
        $box2  = $div->h('div');
        $box2->hp['style'] = "float: left; 
            width: {$wInner}px;
            padding-left: {$pad0}px;
            padding-right: {$pad0}px;";
#            height: {$heightRemain}px;
        $box2->hp['onkeydown'] = 'x6inputs.keyDown(event,this)';
        include 'x6plugindetailDisplay.php';
        $x6detail = new x6plugindetailDisplay;
        $x6detail->main($box2,$dd,$heightRemain-2);
        
        # Render it!  That's it!
        $div->render();
    }
    # ===================================================================
    # *******************************************************************
    #
    # Profile 2: "tabDiv"  editable!
    #
    # *******************************************************************
    # ===================================================================
    function profile_tabDiv() {
        # these were provided by the code that instantiated
        # and initialized the object.
        $dd       = $this->dd;
        $table_id = $this->dd['table_id'];
        
        # Scan through and look for a single queuepos column,
        # it changes things considerably
        $queuepos = '';
        foreach($dd['flat'] as $colname=>$colinfo) {
            if(strtolower($colinfo['automation_id'])=='queuepos') {
                $queuepos = $colname;
                break;
            }
        }
        
        # Create the top level div
        $top=html('div');
        $top->addClass('fadein');
        $top->hp['x6plugin'] = 'tableController';
        $top->hp['x6table']  = $table_id;
        
        # Create a hidden object that contains inputs we will
        # clone over to the grid on demand
        $top->hiddenInputs($dd);
        
        $hremain = x6cssDefine('insideheight');
        
        # Get us the basic title
        $top->h('h1',$dd['description']);
        $hremain -= x6cssHeight('h1');
        $bb = $top->addButtonBar($table_id);
        $hremain -= x6cssHeight('div.x6buttonBar a.button');
        $clearboth = $top->h('div');
        $clearboth->hp['style'] = 'clear: both';
        
        # Work out a height by finding out inside height
        # and subtracing line height a few times
        $grid = $top->addTabDiv($hremain - x6cssHeight('h1'));
        $grid->hp['x6table']      = $table_id;
        $grid->hp['id']           = "tabDiv_$table_id";
        $grid->hp['uiNewRow' ] = 'inline'; // vs. nothing
        $grid->hp['uiEditRow'] = 'inline'; // vs. nothing
        
        # Now obtain the _uisearch columns and make one column each
        $uisearch = $dd['projections']['_uisearch'];
        $aColumns = explode(',',$uisearch);
        foreach($aColumns as $idx=>$column) {
            if($column==$queuepos) unset($aColumns[$idx]);
        }
        foreach($aColumns as $column) {
            $desc = $dd['flat'][$column]['descshort'];
            if($desc=='') {
                $desc = $dd['flat'][$column]['description'];
            }
            $desc = str_replace(' ','&nbsp;',$desc);
            $options=array(
                'column_id' =>  $column
                ,'dispsize' =>  $dd['flat'][$column]['dispsize']
                ,'type_id'  =>  $dd['flat'][$column]['type_id']
                ,'description'=>$desc
                ,'sortable'   =>true
            );
            #$grid->addColumn($dd['flat'][$column]);
            $grid->addColumn($options);
        }
        $gridWidth=$grid->lastColumn();
        
        # Make sortable
        if($queuepos=='') {
            $grid->makeSortable();
        }
        else {
            $grid->ap['xInsertAfter'] = 'Y';
        }
        
        
        # Now set the left-padding to be 1/3 of remaining space
        $remain = x6cssDefine('insidewidth') - $gridWidth;
        $remain = intval($remain/3);
        $top->hp['style'] = "padding-left: {$remain}px";
        
        # Also, let's set the width of the button bar
        # now that we know what it is
        $bb->hp['style'] = "width: {$gridWidth}px";
        
        # The data...
        $ob='';
        if($queuepos) $ob = " order by $queuepos"; 
        $rows = SQL_AllRows("Select skey,$uisearch from $table_id $ob");
        foreach($rows as $row) {
            $grid->addRow($row['skey']);
            foreach($aColumns as $column) {
                $grid->addCell($row[$column]);
            }
        }
        
        # always at the end, render it
        $top->render();
    }    
    # ===================================================================
    # -------------------------------------------------------------------
    #
    # Simple Library routines
    #
    # -------------------------------------------------------------------
    # ===================================================================
    # Makes a generic tabdiv.  First created 11/3/08 so we can add
    # cells to it for a browseFetch and then pluck out the tbody html
    function tabDivGeneric(&$grid,$dd) {
        $table_id = $dd['table_id'];
        $grid->hp['x6table']      = $table_id;
        $grid->hp['id']           = "tabDiv_$table_id";
        $grid->hp['xSortable']    = 'Y';
        $uisearch = $dd['projections']['_uisearch'];
        $aColumns = explode(',',$uisearch);
        foreach($aColumns as $column) {
            $grid->addColumn($dd['flat'][$column]);
        }
        $gridWidth=$grid->lastColumn();
        return $gridWidth;
    }
}
?>
