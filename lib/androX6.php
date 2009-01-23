<?php
/****c* PHP-API/androX6
*
* NAME
*   androX6
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
    var $appTabs = array();
    # ===================================================================
    #
    # Major Area Pre-1: Constructor 
    #
    # ===================================================================
    function androX6() {
        # KFD 1/14/09. copied this from androX4
        # KFD 8/7/08.  Grab any "hold" variables and
        # attach them to the current object.  This was
        # put in for the wholdist application to carry
        # context from screen to screen.
        #
        $this->hld = aFromGp('hld_');
    }
    
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
        $this->profile_conventional();
        return;
    }
    
    function customButtons() {
        return false;
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
        $skey = SQLFN(gp('x6w_skey'));
        $row = SQL_OneRow("SELECT * FROM $view WHERE skey=$skey");
        x6Data('row',$row);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 3: Execute an skey-based update or insert 
    #
    # ===================================================================
    function save() {
        $table_id = gp('x6page');
        $dd = ddTable($table_id);
        
        $row0 = aFromGP('x6v_');
        $row1 = aFromgp('x6inp_'.$table_id.'_');
        $row = array_merge($row0,$row1);
        if(a($row,'skey',0)==0) unset($row['skey']);
        
        # KFD 12/20/08, prevent ui saves if dd does not allow them
        if(!isset($row['skey'])) {
            $perm = $this->uiPerm(gp('x6page'),'ins');
            if(!$perm) {
                x6Error("Inserts not allowed from this screen");
                return;
            }
        }
        else {
            $perm = $this->uiPerm(gp('x6page'),'upd');
            if(!$perm) {
                x6Error("Updates not allowed from this screen");
                return;
            }
        }            
        
        # KFD 12/8/08, More generalized code to allow for
        #              inserts before or after a row.
        #
        # an skeyAfter value means we must find the queuepos
        # column in this table, and save a value of that 
        # column equal to +1 of the value in row skeyAfter
        if(gp('queuepos','')<>'') {
            $queuepos = gp('queuepos');
            $skeyBefore=gp('skeyBefore');
            $skeyAfter =gp('skeyAfter');
            $skey      = 0;
            if($skeyBefore <> -1) $skey = $skeyBefore;
            if($skeyAfter  <> -1) $skey = $skeyAfter;
            if($skey==0) {
                $row[$queuepos] = 1;
            }
            else {
                $qpvalue = SQL_OneValue($queuepos,
                    "Select $queuepos from {$dd['viewname']}
                    where skey = ".sqlfc($skey)
                );
                if($skey==$skeyAfter) 
                    $qpvalue++;
                else
                    $qpvalue--;
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
                    x6Error($ermsg);
                    $errors = true;
                    continue;
                }
                if(strpos($value,'/')!==false) {
                    $parsed = explode('/',$value);
                    if(count($parsed)<>3) {
                        $errors = true;
                        x6Error($ermsg);
                        continue;
                    }
                    if(!checkdate($parsed[0],$parsed[1],$parsed[2])) {
                        x6Error($ermsg2);
                        $errors = true;
                        continue;
                    }
                }
                if(strpos($value,'-')!==false) {
                    $parsed = explode('-',$value);
                    if(count($parsed)<>3) {
                        $errors = true;
                        x6Error($ermsg);
                        continue;
                    }
                    if(!checkdate($parsed[1],$parsed[2],$parsed[0])) {
                        x6Error($ermsg2);
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
            x6Data('row',$row);
        }
        else {
            SQLX_Update($dd,$row);
            if(!errors()) {
                $skey = $row['skey'];
                $row=SQL_OneRow(
                    "Select * FROM {$dd['viewname']} WHERE skey = $skey"
                );
                x6Data('row',$row);
            }
        }
        
        if(vgfGet('x6')==true) {
            if($table_id == 'configinst') {
                configWrite('inst');
            }
            if($table_id == 'configapp') {
                configWrite('app');
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
        $perm = $this->uiPerm(gp('x6page'),'del');
        if(!$perm) {
            x6Error("Deletion not allowed from this screen");
            return;
        }
        
        $view = ddView(gp('x6page'));
        $skey = SQLFN(gp('skey'));
        $sq="Delete from $view where skey = $skey";
        if(Errors()) {
            x6Errors(hErrors());
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
        x6Data('fetch',$answer);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 6: fetch browse/grid values
    #
    # ===================================================================
    function browseFetch() {
        $mtime=microtime(true);
        $table_id = $this->dd['table_id'];
        $tabPar   = gp('tableIdPar');
        
        #  This is the list of columns to return.  Maybe override
        #  if there is something specific named for this table
        $acols = explode(',',$this->dd['projections']['_uisearch']);
        if($tabPar<>'') {
            if(isset($this->dd['projections']['child_'.$tabPar])) {
                $acols=explode(',',$this->dd['projections']['child_'.$tabPar]);
            }
        }

        #  By default the search criteria come from the 
        #  variables, unless it is a child table search
        $vals = aFromGP('x6w_');
        $awhere = array();
        $projSort= '';
        if($tabPar=='') {
            $vals2 = array();
        }
        else {
            $ddpar = ddTable(gp('tableIdPar'));
            $pks   = $ddpar['pks'];
            $stab  = ddView(gp('tableIdPar'));
            $skey  = SQLFN(gp('skeyPar'));
            $vals2 = SQL_OneRow("SELECT $pks FROM $stab WHERE skey = $skey");
            if(!$vals2) $vals2=array();
            $vals  = array_merge($vals,$vals2);
            
            # KFD 12/27/08, if the sortdesc flag has been set on any
            #               columns in the projection, those columns
            #               become the default sort.  Work it up here
            #               and set them aside.
            $proj = 'child_'.$tabPar;
            $aprojSort = array();
            if(isset($this->dd['projdetails'][$proj])) {
                foreach($this->dd['projdetails'][$proj] as $column=>$sortasc){
                    if($sortasc=='Y') {
                        $aprojSort[] = "+$column";
                    }
                    if($sortasc=='N') {
                        $aprojSort[] = "-$column";
                    }
                }
            }
            $projSort = implode(",",$aprojSort);
        }
        
        # Build the where clause        
        #
        $this->flat = $this->dd['flat'];
        foreach($vals as $column_id=>$colvalue) {
            if(!isset($this->flat[$column_id])) continue;
            if($colvalue=='*') {
                $awhere=array();
                gpSet('xReturnAll','Y');
                break;
            }
            
            $colinfo = $this->flat[$column_id];
            $exact = isset($vals2[$column_id]);
            $expre = gp('x6exactPre',0);
            
            //$tcv  = trim($colvalue);
            $tcv = $colvalue;
            $type = $colinfo['type_id'];
            if ($tcv != "") {
                if($exact) gpSet('x6exactPre',1);
                // trap for a % sign in non-string
                $xwhere = sqlFilter($this->flat[$column_id],$tcv);
                if($xwhere<>'') $awhere[] = "($xwhere)";
                if($exact && $expre==0) gpUnset('x6exactpre');
            }
        }
        
        # <----- RETURN (MAYBE)
        if(count($awhere) == 0 && gp('xReturnAll','N')=='N') return;
        
        # Generate the limit
        $SLimit = ' LIMIT 100';
        if($tabPar <> '') {
            if(a($this->dd['fk_parents'][$tabPar],'uiallrows','N')=='Y') {
                $SLimit = '';
            }
        }
        if(gp('xReturnAll','N')=='Y') $SLimit = '';
        

        #  Build the Order by
        #        
        $ascDesc = gp('sortAD')=='ASC' ? ' ASC' : ' DESC';
        $aorder = array();
        $searchsort = '';
        if(gpExists('sortAsc')) {
            x6Debug(gp('sortAsc'));            
            $ascDesc = gp('sortAsc')=='true' ? ' ASC' : ' DESC';
            $aorder[] = gp('sortCol').' '.gp('sortAD');
        }
        else {
            # KFD 12/27/08, Use the search sort that was 
            #               set aside above if it is there
            $searchsort = $projSort==''
              ? trim(arr($this->dd,'uisearchsort',''))
              : $projSort;
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
        if(count($awhere)==0) 
            $SWhere = '';
        else 
            $SWhere = ' WHERE '.implode(' AND ',$awhere);

        // Retrieve data
        #$SQL ="SELECT skey,".implode(',',$acols)
        # KFD 11/15/08.  We can actually select *, because the grid
        #                works out what columns it needs, and we
        #                don't want to accidentally reduce the column
        #                list and exclude something it needs.
        $SQL ="SELECT * "
             ."  FROM ".$this->dd['viewname']
             .$SWhere
             .$SQLOrder
             .$SLimit;
        $answer =SQL_AllRows($SQL);
        
        # These parameters have to be sent from the back.  They
        # figure everything out.
        $sortable  = gp('xSortable','N')=='Y';
        $gridHeight= gp('xGridHeight',500);
        $lookups   = gp('xLookups','N')=='Y';
        $edit      = 0;
        if(($tabPar != '') && ($this->dd['x6childwrites'] == 'Y')) $edit = 1;
        
        # Now make up the generic div and add all of the cells
        $bb = gp('xButtonBar','N')=='Y' || $edit;
        $grid = new androHTMLGrid(
            $gridHeight,$table_id,$lookups,$sortable,$bb,$edit
        );
        $this->gridGeneric($grid,$this->dd,$tabPar,$vals2);
        $grid->addData($answer);
        $grid->hp['x6profile'] = 'grid';
        
        # Put some important properties on the grid!
        $grid->ap['xGridHeight'] = $gridHeight;
        $grid->ap['xReturnAll']  = gp('xReturnAll','N');
        if($tabPar<>'') {
            $grid->ap['x6tablePar']=$tabPar;
        }
        
        # If they asked for the entire grid, send it back
        # as *MAIN* and let the browser put it where it belongs
        if(gp('sendGrid',0)==1) {
            if(count($answer)==0) {
                $grid->noResults();
            }
            x6html('*MAIN*',$grid->bufferedRender());
            return;
        }
        
        # ..otherwise just send the body back.  But kill
        #   any script they created.
        if(count($answer)==0) {
            $grid->noResults();
        }
        $mtimer = microtime(true);
        $grid->dbody->render();
        exit;
    }
    
    function timeDiff($mtime,$str) {
        FB::Send(number_format(microtime(true)-$mtime,4),$str);        
    }
    # ===================================================================
    #
    # SERVER FUNCTION 7: compose list of checkboxes for child x-ref
    #
    # ===================================================================
    function child_checkbox() {
        $table_id  = gp('x6page');
        $table_chd = gp('table_chd');
        $skey      = gp('skey');
        
        # Load up dd's, get pk of parent, etc.
        $dd       = ddTable($table_id);
        $dd_chd   = ddTable($table_chd);
        $view     = $dd['viewname'];
        $view_chd = $dd_chd['viewname'];
        $pk       = $dd['pks'];
        $pk_chd   = $dd_chd['pks'];
        $pkval    = SQL_OneValue($pk,
            "Select $pk from $view where skey = ".SQLFC($skey)
        );
        
        # Now work out the other parent table by assuming there
        # are only two parents.  Drop one and the other must be
        # our parent.
        $parents = array_keys($dd_chd['fk_parents']);
        unset($parents[ array_search($table_id,$parents) ]);
        $table_par = array_shift($parents);
        $dd_par  = ddTable($table_par);
        $pk_par  = $dd_par['pks'];
        $view_par= $dd_par['viewname'];
        
        # Now pull the list of descriptions from the other parent,
        # and the list of values from the x-ref.  Use this to 
        # build a set of checkboxes that are either checked or
        # not.
        $rows_par = SQL_AllRows(
            "Select $pk_par,description from $view_par order by description"
        );
        $rows     = SQL_AllRows(
            "Select $pk_par from $view_chd where $pk = ".SQLFC($pkval),$pk_par
        );
        
        # Finally we are ready to build the list of checkboxes
        # which are either checked or not
        $pad0 = x6CssDefine('pad0');
        $pad1 = $pad0 * 3;
        echo "<p style='padding: {$pad1}px'
          >When you check a box the change is saved immediately,
          you do not have to click on the [SAVE] button.</p>";
        foreach($rows_par as $idx=>$row_par) {
            $div = html('div');
            $div->hp['style'] = 'height: '.x6cssDefine('barheight').'px;'
                ."padding-left: {$pad0}px"; 
            $inp = $div->h('input');
            $inp->hp['style'] = "float: left;";
            $inp->hp['type'] = 'checkbox';
            $inp->hp['value']= $row_par[$pk_par];
            $strEvent = "xrefClick_$table_id";
            $strArgs  
                ="{inp:this,pkvalleft:'$pkval',pkl:'$pk',pkr:'$pk_par',"
                ."x6table:'$table_chd'}";
            $inp->hp['onclick'] = "x6events.fireEvent('$strEvent',$strArgs)";
            if(isset($rows[$row_par[$pk_par]])) {
                $inp->hp['checked'] = 'checked';
            }
            $div2 = $div->h('div',$row_par['description']);
            $div2->hp['style'] = "float: left; padding-top: 1px";
            $div->render();
        }
        
        
        #  NOTICE THE EXIT COMMAND.  This is because we are 
        #  streaming back literal html that will dropped directly
        #  into place.
        exit;
    }   
    
    function checkboxSave() {
        $row      = aFromGP('cbval_');
        x6Data('row',$row);
        $table_id = gp('x6table');
        if(gp('checked')=='true') {
            SQLX_Insert($table_id,$row);
        }
        else {
            SQLX_Delete($table_id,$row);
        }
    }
    
    # ===================================================================
    #
    # SERVER FUNCTION 8: Retrieve and display a CLOB field
    #
    # ===================================================================
    function viewClob() {
        $table_id = gp('x6page');
        $skey     = SQLFC(gp('skey'));
        $column   = gp('column');
        
        $sql='SELECT "'.$column.'" from "'.$table_id.'" where skey= '.$skey;
        echo "<pre>";
        $text= SQL_OneValue($column,$sql);
        echo $text;
        file_put_contents(fsDirTop().'tmp/viewClob.txt',$text);
        echo "</pre>";
        exit;
    }
    
    # ===================================================================
    # *******************************************************************
    #
    # Profile 0: "grid"  editable!
    #
    # *******************************************************************
    # ===================================================================
    function profile_grid() {
        # these were provided by the code that instantiated
        # and initialized the object.
        $dd       = $this->dd;
        $table_id = $this->dd['table_id'];
        
        # Scan through and look for a single queuepos column,
        # it changes things considerably: the queuepos column
        # is hidden and sorting is forced on that column.  It is
        # also assigned automatically as the user creates rows.
        $queuepos = '';
        foreach($dd['flat'] as $colname=>$colinfo) {
            if(strtolower($colinfo['automation_id'])=='queuepos') {
                $queuepos = $colname;
                jqDocReady("u.bb.vgfSet('queuepos_$table_id','$colname')");
                break;
            }
        }
        # Make sortable or not based on queuepos
        $sortable = $queuepos=='';
        
        # Create the top level div
        $top=new androHTMLTableController($table_id);
        $top->addClass('fadein');
        
        $hremain = x6cssDefine('insideheight');
        
        # Get us the basic title
        $top->h('h1',$dd['description']);
        $hremain -= x6cssHeight('h1');
        $hremain -= x6cssHeight('div.x6buttonBar a.button');
        $clearboth = $top->h('div');
        $clearboth->hp['style'] = 'clear: both';
        
        # Work out a height by finding out inside height
        # and subtracing line height a few times
        $gridHeight = $hremain - x6cssHeight('h1');
        $grid       = $top->addGrid(
            $gridHeight,$table_id,false,$sortable,true,true
        );
        $grid->hp['x6profile'] = 'grid';
        
        # More features specific to this profile, these will 
        # allow browseFetch not to have to figure this all out
        # all over again.
        $grid->ap['xGridHeight'] = $gridHeight;
        $grid->ap['xSortable']   = $sortable ? "Y" : "N";
        $grid->ap['xReturnAll']  = "Y";
        

        # If queuepos set, set this flag        
        if($queuepos) $grid->ap['xInsertAfter'] = 'Y';
        
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
        $this->hldOut($top);
        $top->render();
        
        # And a final doo-dad, broadcast a focus event
        # to turn on the grid
        jqDocReady("x6events.fireEvent('objectFocus','".$grid->hp['id']."');");
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
        
        # Get the standard padding, we are going to double it
        $pad0 = x6CSSDefine('pad0');
        $heightRemain = x6cssDefine('insideheight');
        
        # Now put in your basic title
        $div = new androHTMLTableController($table_id);
        $div->addClass('fadein');
        $div->ap['xCache']   = 'Y';  // results will be cached
        $div->h('h1','<center>'.$dd['description'].'</center>');
        $heightRemain -= x6cssHeight('h1');
        $heightRemain -= ($pad0*2);
        
        # Create a two-sided layout by creating two boxes
        # Left side is a grid with the entire table
        $area0 = $div->h('div');
        $area0->hp['style'] = "float: left; 
            padding-left: {$pad0}px;
            padding-right: {$pad0}px;";
        $x6grid = $area0->addGrid($heightRemain,$table_id,false,true,false);
        $x6grid->hp['x6profile'] = 'twosides';
        $this->gridGeneric($x6grid,$this->dd);
        
        # Now put the data over there
        $uisearch = $this->dd['projections']['_uisearch'];
        $ob       = $this->dd['pks'];
        # KFD 1/22/09, allow filtering on "pre" values
        $pre = aFromGp('pre_');
        $awhere = array();
        $where = '';
        foreach($pre as $colname=>$value) {
            $awhere[] = sqlfilter($this->dd['flat'][$colname],$value);
        }
        if(count($awhere)>0) $where = 'WHERE '.implode(' AND ',$awhere);
        $sql = "Select skey,$uisearch from $table_id $where ORDER BY $ob";
        $rows = SQL_AllRows($sql);
        $aColumns = explode(',',$uisearch);
        foreach($rows as $row) {
            $x6grid->addRow($row['skey']);
            foreach($aColumns as $column) {
                $x6grid->addCell($row[$column]);
            }
        }
        
        # Calculate how much width is left
        $wInner = x6CSSDefine('insidewidth');
        $wInner-=$x6grid->width;
        $wInner-=2;  // assume a border on the grid
        $wInner-=2;  // assume a border on the right-side
        $wInner-= ($pad0*6); // 3 times padding doubled
        
        $box2  = $div->h('div');
        $box2->hp['style'] = "float: left; width: {$wInner}px;";
        $detail = $box2->addDetail($table_id,true,$heightRemain-2);
        $detail->ap['x6profile'] = 'twosides';
        
        # Generate a list of child xrefs.  The idea here is to
        # work out the dimensions first, because we must tell the
        # class how high to make itself.  Then we generate the
        # list.  If it comes back empty, we forget about it, otherwise
        # we create a div of the correct position and put it into it.
        $xrtop = ($detail->hp['xInnerHeight']-$detail->hp['xInnerEmpty'])
            +(x6CssHeight('h1'));
        $xrhgt = $detail->hp['xInnerEmpty'] 
            - x6cssHeight('h1')
            - ($pad0 * 10) ;      // total hack, don't know why 7 works
        $xrwdth= $wInner - $detail->hp['xInnerWidthLost'];
        $xrefs = new androHTMLxrefs($table_id,$xrhgt);
        if($xrefs->hp['xCount']>0) {
            $xrefParent = $detail->innerDiv->h('div');
            $xrefParent->hp['style'] = 
               "position: absolute;
                width: {$xrwdth}px;
                top: {$xrtop}px;
                left: {$pad0}px;";
            $xrefParent->addChild($xrefs);
        }

        # tell the screen to start out by 
        # focusing on the browse
        jqDocReady("x6events.fireEvent('objectFocus','{$x6grid->hp['id']}')");
        
        # Render it!  That's it!
        $this->hldOut($div);
        $div->render();
    }
    
    # ===================================================================
    # *******************************************************************
    #
    # Profile 2: "conventional"
    #
    # *******************************************************************
    # ===================================================================
    function profile_conventional() {
        # Grab the data dictionary for this table
        $dd       = $this->dd;
        $table_id = $this->dd['table_id'];

        # Create the top level div as a table controller
        $top= new androHTMLTableController($table_id);
        $top->addClass('fadein');
        
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
        # pane, hardcoded at pad0.  This is where the child 
        # tables are initially displayed -- at the bottom of the
        # detail pane.  They are initially slid all of the way down,
        # that is why their height is only pad0.
        $hempty  = $pad0;
        
        # $hdetail is the height of the detail pane inside of the 
        # detail tab.  It is the $hpane1 less another tab (the 
        # nested one), and $hempty, and a double padding
        $hdetail = $hpane1 - $htabs - $hempty - ($pad0 * 2);
        
        # Begin with title and tabs
        $top->h('h1',$dd['description']);
        $options = array('x6profile'=>'conventional','x6table'=>$table_id);
        $tabs = $top->addTabs('tabs_'.$table_id,$hpane1,$options);
        $lookup = $tabs->addTab('Lookup');
        $detail = $tabs->addTab('Detail',true);

        # Make a generic grid, which will show all uisearch 
        # columns, and add a row of lookup inputs to it.  
        # Enclose it in a div that gives some padding on top
        # and bottom.  Divide up the left-right free space to
        # put 1/3 on the left and the remaining on the right.
        $divgrid = $lookup->h('div');
        $grid = $divgrid->addGrid($hpane1 - ($hlh*2),$table_id,true,true);
        $grid->hp['x6profile'] = 'conventional';
        $gridWidth = $this->gridGeneric($grid,$dd);
        # Work out the available free width after making the grid
        $wAvail = x6cssDefine('insidewidth')
            - 2 // hardcoded assumption of border of tab container
            - (x6cssDefine('pad0')*2)  // padding outside of tab container
            - $gridWidth;
        $divgrid->hp['style']=
            "padding-left: ".intval($wAvail/3)."px;
             padding-top: ".x6CSSDefine('lh0')."px;";
        # tell the browse tab object to focus when it is selected
        $lookup->hp['x6objectFocusId'] = $grid->hp['id'];
        
        # This is kind of a hack for forcing exact matches when
        # a maintenance screen is invoked with pre-set parameters
        if(gp('x6exactPre')==1) $grid->hp['x6exactPre'] = 1;

        # We are making
        # the assumption that there will *always* be child tables
        # because otherwise the programmer would have selected
        # a different profile.
        #
        $divDetail = $detail->addDetail($dd['table_id'],true,$hdetail);
        $divDetail->addCustomButtons($this->customButtons());
        $divDetail->ap['xTabSelector'] = $tabs->ul->hp['id'];
        $divDetail->ap['xTabIndex']    = 1;
        $divDetail->ap['x6profile'] = 'conventional';
        $detail->hp['x6objectFocusId'] = $divDetail->hp['id']; 
        
        # The div kids is a tabbar of child tables.  Notice that we
        # put nothing into them.  They are loaded dynamically when
        # the user picks them.
        $options=array('x6slideUp'=>$divDetail->hp['id']
            ,'x6slideUpInner'=>$divDetail->innerId
            ,'x6parentTable'=>$table_id
            ,'x6profile'=>'kids'
            ,'x6table'=>$table_id
            ,'styles'=>array('overflow'=>'hidden')
        );
        $tabKids = $detail->addTabs('kids_'.$table_id,$hempty,$options);
        $tabKids->ul->hp['xOffset'] = 2;
        $tab = $tabKids->addTab("Hide");
        foreach($dd['fk_children'] as $child=>$info) {
            # KFD 1/2/08.  If x6display is 'none', skip it
            if(trim(arr($info,'x6display',''))=='none') continue;
            
            $top->addTableController($child);
            $tab = $tabKids->addTab($info['description']);
            $tab->ap['x6tablePar'] = $table_id;
            $tab->ap['x6table'   ] = $child;
        }
        
        # And then loop through extra tabs
        foreach($this->appTabs as $child=>$caption) {
            $top->addTableController($child);
            $tab = $tabKids->addTab($caption);
            $tab->hp['x6tablePar'] = $table_id;
            $tab->hp['x6table'   ] = $child;
        }
        
        # tell the screen to start out by focusing on
        # the browse
        jqDocReady("x6events.fireEvent('objectFocus','{$grid->hp['id']}')");

        $this->hldOut($top);
        $top->render();
    }

    # ===================================================================
    # *******************************************************************
    #
    # Profile 3: "onerow"
    #
    # *******************************************************************
    # ===================================================================
    function profile_onerow() {
        
        # Get started with the top of the page and
        # a table controller
        $top = htmlMacroTop($this->x6page);
        $top->addTableController($this->x6page);
        
        # Make the tabs
        $height = x6CssHeightLessH1();
        $tabs = $top->addTabs($this->x6page,$height);
        
        $spacing = intval(x6CSSDefine('insidewidth')/25);
        
        # Get a list of projections, we will make a tab
        # for each one of them
        $projections = array_keys($this->dd['projections']);
        foreach($projections as $idx=>$projection) {
            if(substr($projection,0,1)=='_') unset($projections[$idx]);
        }
        
        # Say nothing to do if nothing to do
        if(count($projections)==0) {
            $top->h('p','Nothing to configure.');
        }
        
        # Get the row from the table so we can populate
        # the inputs
        $row = SQL_OneRow("Select * from ".$this->dd['viewname']);

        
        # Make a tab for each projection, and put the inputs in there!
        foreach($projections as $projection) {
            $description = $projection;
            $x = 'description_'.$projection;
            if(isset($this->dd['flat'][$x])) {
                $description=$this->dd['flat'][$x]['description'];
            }
            
            $tab = $tabs->addTab($description);
            $insidetab = $tab->h('div');
            $insidetab->hp['style']="padding: {$spacing}px";
            
            $table = $insidetab->h('table');
            $table->addClass('x6Detail');
            $columns = explode(',',$this->dd['projections'][$projection]);
            foreach($columns as $idx=>$column) {
                $tabLoop=array();
                
                $tr = $table->h('tr');
                $td = $tr->h('td',$this->dd['flat'][$column]['description']);
                $td->addClass('x6Caption');
                
                $input=input($this->dd['flat'][$column],$tabLoop);
                $input->hp['xSkey'] = $row['skey'];
                $input->hp['value'] = htmlentities($row[$column]);
                $td = $tr->h('td');
                $td->setHtml($input->bufferedRender());
                $td->addClass('x6Input');            
            }
        }
        $top->render();
        
        ob_start();
        ?>
        <script>
        $('input,textarea').each(
            function() {
                this.zOriginalValue = $(this).attr('value');
                this.afterBlur = function() {
                    if(this.zOriginalValue.trim()==this.value.trim()) return;
                    var json = new x6JSON('x6page',x6.p(this,'xTableId'));
                    json.addParm('x6action','save');
                    json.addParm('x6v_skey',x6.p(this,'xSkey'));
                    json.addParm(this.id,this.value);
                    json.execute(false,true);
                    this.zOriginalValue = this.value;
                    x6inputs.setClass(this);
                }
            }
        );
        <?php
        jqDocReady(ob_get_clean());
    }
    
    # ===================================================================
    # -------------------------------------------------------------------
    #
    # Simple Library routines
    #
    # -------------------------------------------------------------------
    # ===================================================================
    function uiPerm($table,$perm) {
        $dd = ddTable($table);
        return arr($dd,"ui$perm","Y") == 'N' ? false : true;
    }
    
    function hldOut(&$top) {
        if(isset($this->hld)) {
            foreach($this->hld as $name=>$value) {
                $inp = $top->h('input');
                $inp->hp['type'] = 'hidden';
                $inp->hp['name'] = $inp->hp['id'] = 'hld_'.$name;
                $inp->hp['value'] = $value;
            }
        }
    }
    
    # Makes a generic grid.  First created 11/3/08 so we can add
    # cells to it for a browseFetch and then pluck out the tbody html
    function gridGeneric(&$grid,$dd,$tabPar='',$vals2=array()) {
        $table_id = $dd['table_id'];
        
        # KFD 12/18/08.  If we have a tablePar and $vals2, we will
        #                put a set of options onto the grid that
        #                it will respect when building the inputs
        if($tabPar!='') {
            $options = array();
            foreach($vals2 as $colname=>$colvalue) {
                $options[$colname] = array(
                    'parentTable'=>$tabPar
                    ,'attributes'=>array(
                        'xDefault'=>$colvalue
                    )
                );
            }
            x6debug($options);
            $grid->setColumnOptions($options);
        }
        
        # KFD 11/15/08 
        # Nifty trick to allow different columns when viewed as 
        # child table.  If tabPar is passed in, we will pick
        # the projection named "child_{tabPar}" if it exists.
        # The first line will pull an empty result if this feature
        # has not been used by the programmer, which causes a fallback
        # to the normal uisearch approach.
        $uisearch = arr($dd['projections'],'child_'.$tabPar,'');
        if($uisearch=='') {
            $uisearch = $dd['projections']['_uisearch'];
        }
        $aColumns = explode(',',$uisearch);
        foreach($aColumns as $column) {
            $type_id = $dd['flat'][$column]['type_id'];
            $x6view  = arr($dd['flat'][$column],'x6view','');
            if($type_id=='text' && $x6view=='') {
                $dd['flat'][$column]['dispsize'] = 20;
            }
            $grid->addColumn($dd['flat'][$column]);
        }
        $gridWidth=$grid->lastColumn();
        return $gridWidth;
    }
    
}
?>
