<?php
/****c* PHP-API/androX4
*
* NAME
*   androX4
*
* FUNCTION
*   The PHP class androX4 is the base class for all Andromeda
*   pages.  It is used by the framework for "free" Table-Maintenance
*   screens and is also the basis of custom screens.
*
*   Making a subclass of androX4 begins with the Page-Name, and
*   is formed as "x4".Page-Name.  The file name is also 
*   "x4".Page-Name.".php".  
*
*   When calling a page, use the parameter x4Page, as in:
*
*          <a href="index.php?x4Page=example">
*
*   A subclass of androX4 can also contain routines that can be
*   called by Javascript code on the browser.  These can have
*   any name that is not reserved.  The reserved methods are:
*   * __construct
*   * main
*   * menuBar
*   * grid
*   * detailPane
*   * mover
*   * moverFetch
*   * moverSS
*   * browseFetch
*   * fetch
*   * fetchRow
*   * update
*   * delete
*
*   The following methods exist so that you can override them on
*   a page-by-page method:
*   * custom_construct - called at the end of __construct
*   * mainLayout - called to override default layout
*
* EXAMPLE
*   A basic custom page normally has three sections, and 
*   looks basically like this:
*
*     <?php
*     # this is file x4example.php
*     class x4example extends androX4 {
*         # The first major area is layout of the 
*         # page as it will be sent to the browser
*         function mainLayout($container) { 
*             $container->h('h1','This is H1 Title');
*             return;
*         }
*
*         # The second major area is extra Script to send
*         # to the browser
*         function extraScript() {
*              ?>
*              <script>
*              // javascript goes here
*              </script>
*              <?php
* 
*         # The third major area is routines that can be called
*         # from the browser
*         function routine1() { }
*         function routine2() { }
*     }
*     ?>
* 
******
*/
class androX4 {
    # ===================================================================
    #
    # Major Area 0: In the construction area, load up data dictionary 
    #
    # ===================================================================
    function __construct($x4Page) {
        $this->table_id = $x4Page;
        $this->dd       = &ddTable($this->table_id);
        $this->flat     = &$this->dd['flat'];
        $this->view_id  = a($this->dd,'viewname',$this->table_id);
        $this->tabindex=1000;
        
        $this->custom_construct();
    }
    #  Placeholders, to be overridden by subclasses
    function custom_construct() {
    }
    function extraScript() {
    }
    function browseFetchModify(&$answer) {
    }
    
    # ===================================================================
    #
    # Major Area 1: Describe and Return the Entire Layout
    #
    # ===================================================================
    /**
      * Generate a complete layout of all form elements and deliver
      * it to the browser
      *
      * @author: Kenneth Downs
      */
    function main() {
        # Write out the default help system
        $this->mainHelp();

        # KFD 6/18/08, replaced, see below        
        # If we see a "gp_pk" variable, they are requesting a certain
        # detail row.  Find out the skey and pass instructions on.
        # Notice the assumption of only a single column.
        $apre = aFromGP('pre_');
        if(count($apre)>0) {
            x4Data('init',$apre);
        }
        # And also add in the mode if it has been delivered, and focus
        if(gp('x4Mode')<>'') x4Data('x4Mode',gp('x4Mode'));
        if(gp('x4Focus')<>'')x4Data('x4Focus',gp('x4Focus'));
        
        # KFD 6/25/08, if there is extra script, run it
        ob_start();
        $this->extraScript();
        $extra = ob_get_clean();
        if($extra<>'') x4Script($extra);
        
        # KFD 8/7/08.  Grab any "hold" variables and
        # attach them to the current object.  This was
        # put in for the wholdist application to carry
        # context from screen to screen.
        #
        $this->hld = aFromGp('hld_');
        
        # All top-level elements will go inside of this div 
        $x4Top = html('div');
        $x4Top->hp['id']='x4Top';
        $this->mainLayout($x4Top);
        x4Html('*MAIN*',$x4Top->bufferedRender());
        x4Data('dd.'.$this->table_id,$this->dd);
        x4Data('returnto',gp('x4Return'));
        
        # Now send all hold variables out on the container
        foreach($this->hld as $key=>$value) {
            $x4Top->hidden($key,$value);
        }
        
        return;
    }
    
    function mainHelp() {
        if($this->table_id=='menu') { vgfSet('htmlHelp',false); return; }
        ob_start();
        ?>
        <div style="font-size:125%; line-height: 125%">
        <h2>Some basic ideas:</h2>
        <ul>
<li>When in doubt, keep hitting ESC to you get back to the menu
<li>The buttons are activated by CTRL- combinations, they have
underlined letters that show this, so:
    <ul><li>CTRL-A is equivalent to hitting [Add...]
        <li>CTRL-S is equivalent to hitting [SAVE]
        <li>etc.
    </ul>
<li>A button is grayed out when it cannot be used
</ul>

    <h2>The Search Screen</h2>
    <p>When you are in search mode, there are some advanced search
    abilities available:
    <ul><li>Just start typing in any box and see what comes up!
        <li>Whichever box you type in is automatically the sorted column
        <li>You can hit Shift-UpArrow or Shift-DownArrow to force sorting
            on a particular column
        <li>You can click on the column headers to force a search
        <li>Searches can contain lists, like "d,g,p" 
        <li>Searches can contain ranges, like "100--120"
        <li>Searches can contain comparisons, like "&gt;100" or "&lt;200"
        <li>Advanced search abilities can be combined, like "d,f--g,>x"
    </ul>
    </div>
        
        
        <?php
        vgfSet('htmlHelp',ob_get_clean());
    }
    
    function mainLayout(&$div) {
        # Add the top level item as an x4Pane so it will fade in        
        $x4Window = html('div',$div);
        $x4Window->addClass('x4Window');
        $x4Window->addClass('x4Pane');
        $x4Window->hp['id']='x4Window';
        $x4Window->setAsParent();
        
        # The first two are simple, a description 
        # and the menu bar.  These are permanent and will
        # be displayed for the entire time.
        $h1  = html('h1',$x4Window,$this->dd['description']);
        $h1->hp['id']='x4H1Top';

        # Add the menu bar
        $x4Window->addChild( $this->menuBar($this->dd) );
        
        #  This is the top level display item
        #
        $x4Display = html('div',$x4Window);
        $x4Display->addClass('x4Pane');
        $x4Display->addClass('x4TableTop');
        $x4Display->ap['xTableId'] = $this->table_id;
        $x4Display->hp['id'] = 'x4TableTop_'.$this->table_id;
        $x4Display->setAsParent();
        
        # Create a grid for the default display
        $grid = $this->grid($this->dd);
        $grid->addClass('x4VerticalScroll1');
        $x4Display->addChild( $grid );
        
        # Create a container and tab bar, 
        #
        $tabC = html('div',$x4Display);
        $tabC->addClass('x4Pane');
        $tabC->addClass('x4TabContainer');
        $tabCId = 'x4TabContainer_'.$this->table_id;
        $tabC->hp['id'] = $tabCId;
        $tabC->ap['xTableId'] = $this->table_id;
        $tabC->setAsParent();
        $tabB = html('div',$tabC);
        $tabB->addClass('x4TabBar');
        $tabB->addClass('x4Div');
        $tabBId = 'x4TabBar';
        $tabB->hp['id']=$tabBId;
        
        # For now we create only one detail pane, 
        # later on we want more
        #
        $tabid = 'tab_'.$this->dd['table_id'];
        $tabx = html('div',$tabC);
        $detail = $this->detailPane('');
        $detailId = $detail->hp['id'];
        $detail->addClass('x4VerticalScroll2');
        $tabC->addChild( $detail );
        $span = html('a-void',$tabB,'1: Detail');
        $span->hp['xAction'] = 'Ctrl1';
        $span->hp['id'] = 'tabFor_'.$detailId;
        $span->hp['onclick']="\$a.byId('$tabCId').dispatch('$detailId')";
        
        # Child table panes are added in a loop because there
        # may be more than one
        #
        $tabNumber = 2;
        foreach($this->dd['fk_children'] as $table_id=>$info) {
            if(trim(strtolower(a($info,'uidisplay',''))) == 'none') continue;
            $tabid = 'x4TableTop_'.$table_id;
            $ddChild = ddTable($table_id);
            x4Data('dd.'.$table_id,$ddChild);
            
            # Make a tableTop container
            $tabx = html('div',$tabC);
            $tabx->addClass('x4Pane');
            $tabx->addClass('x4TableTop');
            $tabx->hp['id'] = $tabid;
            $tabx->ap['xTableId'] = $table_id;
            $tabx->setAsParent();

            # Create an x4 object
            $chdobj = x4Object($table_id);

            # KFD 7/8/08, look for a "mover" box, and do an
            #             alternate setup
            $table_id = $this->dd['table_id'];
            $uidisplay = $ddChild['fk_parents'][$table_id]['uidisplay'];
            if(trim($uidisplay)=='mover') {
                $tabx->addChild($this->mover($ddChild,$table_id));
            }
            else {
                # Add into it the grid and the detail
                $tabx->addChild( $chdobj->grid($this->table_id) );
                $tabx->addChild( $chdobj->detailPane($this->table_id) );
            }
                
            # Make the tab entry
            $span=html('a-void',$tabB,$tabNumber.': '.$ddChild['description']);
            $span->hp['xAction']='Ctrl'.$tabNumber;
            $span->hp['id'] = 'tabFor_'.$tabid;
            $tabNumber++;
            $span->hp['onclick'] = "\$a.byId('$tabCId').dispatch('$tabid')";
        }
    }        
        
    # ===================================================================
    #
    # Major Area 2: Generate the Universal Menu Bar 
    #
    # ===================================================================
    function menuBar($dd) {
        $menuBar = html('div');
        $menuBar->addClass('x4MenuBar');
        $menuBar->addClass('x4Div');
        $id = 'x4MenuBar';
        $menuBar->hp['id'] = $id;
        $menuBar->setAsParent();
        
        $mbl = $menuBar->h('div');
        $mbl->hp['style'] = 'float:left;';
        $mbr = $menuBar->h('div');
        $mbr->hp['style'] = 'float:right;';
        
        $a = html('a-void',$mbl,'<u>A</u>dd '.$dd['singular']);
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->hp['id']='button-new';
        $a->hp['xLabel']='CtrlA';
        $a->hp['xAction'] = 'newRow';
        
        $a = html('a-void',$mbl,'<u>D</u>elete');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->hp['id']='button-del';
        $a->hp['xLabel']='CtrlD';
        $a->hp['xAction'] = 'deleteRow';
        
        $a = html('a-void',$mbl,'Co<u>p</u>y');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->hp['id']='button-cpy';
        $a->hp['xLabel']='CtrlP';
        $a->hp['xAction'] = 'copyRow';
        
        $a = html('a-void',$mbl,'<u>S</u>ave');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->hp['id']='button-sav';
        $a->hp['xLabel']='CtrlS';
        $a->hp['xAction'] = 'saveRow';

        # Removed by KFD 5/29/08, We want copy to do the same thing
        #$a = html('a-void',$mbl,'Save &amp; <u>N</u>ew');
        #$a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        #$a->hp['id']='button-snw';
        #$a->hp['xLabel']='CtrlN';
        #$a->hp['xAction'] = 'saveRowAndNewRow';
        
        # Removed by KFD 5/29/08, Esc will do the same thing.
        #$a = html('a-void',$mbl,'Save &amp; E<u>x</u>it');
        #$a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        #$a->hp['id']='button-sxt';
        #$a->hp['xLabel']='CtrlX';
        #$a->hp['xAction'] = 'saveRowAndExit';
        
        
        $a = html('a-void',$mbl,"ESC: Quit");
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->hp['id']='button-esc';
        $a->hp['xLabel'] = 'Esc';
        $a->hp['xAction'] = 'Esc';
        
        $a = html('a-void',$mbr,'Top');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->ap['xLabel'] = 'CtrlPageUp';
        $a->hp['xAction'] = 'CtrlPageUp';

        $a = html('a-void',$mbr,'Previous');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->ap['xLabel'] = 'PageUp';
        $a->hp['xAction'] = 'PageUp';

        $span = html('span',$mbr,'No Records');
        $span->hp['id'] = 'x4RowInfoText';
        html('span',$mbr,'&nbsp;');

        $a = html('a-void',$mbr,'Next');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->ap['xLabel'] = 'PageDown';
        $a->hp['xAction'] = 'PageDown';

        $a  = html('a-void',$mbr,'Bottom');
        $a->hp['onclick']="x4.parent(this).keyPress(this.getAttribute('xAction'))";
        $a->ap['xLabel'] = 'CtrlPageDown';
        $a->hp['xAction'] = 'CtrlPageDown';
        return $menuBar;
    }        
            
    # ===================================================================
    #
    # Major Area 3: Generate a grid for a table 
    #
    # ===================================================================
    function grid($tableIdPar = '') {
        $dd = $this->dd;
        $table_id = $dd['table_id'];
        
        # Generate default options
        #$inputs = a($options,'inputs','Y');
        $inputs='Y';
        
        # Everything goes into this table
        $t = html('table');
        $t->hp['id'] = 'grid_'.$table_id;
        $t->ap['xTableId'] = $table_id;
        $t->ap['xReturnAll'] = 'N';
        $t->addClass('x4Pane');
        $t->addClass('x4GridSearch');
        $t->addClass('x4Grid');
        $t->setAsParent();
        $tb1 = html('thead',$t);
        $th = html('tr',$tb1);
        $th->addClass('header');
        if($inputs=='Y') {
            $ti = html('tr',$tb1);
            $ti->addClass('inputs');
        }
        $tbody = html('tbody',$t);
        $tbody->hp['id'] = 'grid_body_'.$table_id;
        $t->ap['xGridBodyId'] = $tbody->hp['id'];
        
        // Create the headers and inputs
        $cols = explode(',',$dd['projections']['_uisearch']);
        $tabLoop = array();
        $fakeCI = array('colprec'=>'10');
        foreach($cols as $column) {
            # KFD 6/12/08, respect columns removed for security
            if(!isset($dd['flat'][$column])) continue;
            $colinfo = $dd['flat'][$column];
            if($tableIdPar == $colinfo['table_id_fko'] && $tableIdPar<>'') {
                continue;
            }
            
            $column = trim($column);
            $hx = html('th',$th);
            #$hx->addClass('table-head');
            $hx->innerHtml = $colinfo['description'];
            $inpid = 'search_'.$table_id.'_'.$column;
            $hx->hp['onclick'] =
                "x4.parent(this).setOrderBy(\$a.byId('$inpid'))";
            
            if($inputs=='Y') {
                $inp= input($fakeCI,$tabLoop);
                $inp->hp['maxlength'] = 500;
                $inp->hp['id'] = $inpid;
                $inp->hp['autocomplete'] = 'off';
                $inp->ap['xValue']='';
                $inp->ap['xColumnId'] = $column;
                $inp->ap['xParentId'] = $t->hp['id'];
                $inp->ap['xNoEnter'] = 'Y';
                $td = html('td',$ti);
                $td->addChild($inp);
            }
        }
        inputsTabLoop($tabLoop,array('xParentId'=>$t->hp['id']));
        
        return $t;
    }
    
    
    # ===================================================================
    #
    # Major Area 4: Detail Panes 
    #
    # ===================================================================
    function detailPane($parentTable=null) {
        $dd  = $this->dd;
        $div = html('div');
        $div->hp['id'] = 'detail_'.$dd['table_id'];
        $div->ap['xTableId'] = $dd['table_id'];
        $div->addClass('x4Pane x4Detail');
        $div->setAsParent();
        
        # KFD 6/25/08, look for a method named after the table,
        #              if find that invoke that instead
        $method = $dd['table_id'].'_detail';
        if(method_exists($this,$method)) {
            $retval=$this->$method($dd,$div,$parentTable);
            if($retval!==false) return $retval;
        }
        
        # create the table that will hold the inputs 
        $table = html('table',$div);
        $table->hp['class'] = 'x4detail';
        $tid   = $dd['table_id'];

        $trx   = html('tr',$table);
        $tdx   = html('td',$trx);
        
        $tabLoop = array();
        $inputs = projection($this->dd,'',$tabLoop
            ,array('colbreak'=>a($dd,'colbreak',17)
                ,'breakafter'=>a($dd,'breakafter',array())
            )
        );
        $tdx->addChild($inputs);
        inputsTabLoop($tabLoop,array('xParentId'=>$div->hp['id']));
        
        return $div;
    }
    # ===================================================================
    #
    # Major Area 5: A mover box 
    #
    # ===================================================================
    function mover($dd,$parentTable) {
        $div = html('div');
        $div->addClass('x4Pane x4Mover');
        $div->hp['id'] = 'x4Mover_'.$dd['table_id'];
        $div->ap['xTableId'] = $dd['table_id'];
        $div->setAsParent();
        $div->br();
        $div->h('p','There is no need to click [SAVE] on this page'
            .', all changes take effect immediately.');

        # Make a note of the left-side parent table pk
        $col2 = $dd['fk_parents'][$parentTable]['cols_par'];
        $div->ap['xPk'] = $col2;
        
        
        # Locate the "other" parent, that is what
        # we are cross-referencing to
        $tables = array_keys($dd['fk_parents']);
        unset($tables[$parentTable]);
        $parentTable = array_pop($tables);
        
        # Retrieve the info
        $colPar = $dd['fk_parents'][$parentTable]['cols_par'];
        $div->ap['xRetCol'] = $colPar;
        $vpar   = ddView($parentTable);
        $sq     = "SELECT $colPar,description
                   FROM $vpar par
                  ORDER BY par.description";
        $rows   = SQL_AllRows($sq);
            
        # Build an HTML table that displays the security settings
        $table = $div->h('table');
        $thd = $table->h('thead');
        $tr  = $thd->h('tr');
        $tr->h('th','Assigned','dark');
        $tr->h('th','Description','dark');
        $tr->h('th','Code','dark');
        
        # now the body
        $tbody = $table->h('tbody');
        foreach($rows as $row) {
            $tr = $tbody->h('tr');
            $td = $tr->td();
            $input = html('input');
            $input->hp['type']    = 'checkbox';
            $input->hp['id'] = 'check_'.$row[$colPar];
            $input->hp['onclick']='x4.parent(this).clickCheck(this)';
            $input->hp['xcol1'] = $colPar;
            $input->hp['xvar1'] = $row[$colPar];
            $input->hp['xcol2'] = $col2;
            $input->hp['xTableId'] = $dd['table_id'];
            $td->addChild($input);
            $tr->h('td',$row['description']);
            $tr->h('td',$row[$colPar]);
        }
            
        return $div;
    }
    
    # ----------------------------------
    # Server-side companions
    # ----------------------------------
    function moverFetch() {
        $table=$this->dd['table_id'];
        $pkcol = gp('pkcol');
        $pkval = gp('pkval');
        # We're doing this here instead of using SQLFC, because
        # SQLFC will go to upper-case if the ALLCAPS option is
        # on, and we don't have a mechanism to stop that for
        # user_id values.
        $pkval = str_replace("'","''",$pkval);
        $retcol= gp('retcol');
        $sq = "select $retcol as x from $table where $pkcol = '$pkval'";
        $rows = sql_allRows($sq);
        $moverFetch = array();
        foreach($rows as $row) {
            $moverFetch[] = $row['x'];
        }
        x4Data('moverFetch',$moverFetch);
    }
    function moverSS() {
        $checked = gp('checked')=='Y' ? true : false;
        $col1 = gp('col1');
        $var1 = gp('var1');
        $col2 = gp('col2');
        $var2 = gp('var2');
        $table= $this->dd['table_id'];
        
        # Either a delete or an insert
        $row = array($col1=>$var1,$col2=>$var2);
        if($checked) {
            SQLX_Insert($table,$row);
        }
        else {
            SQLX_Delete($table,$row);
        }
    }
    
    
    # ===================================================================
    #
    # SERVER FUNCTION 1: Retrieve search results for a grid 
    #
    # ===================================================================
    /**
      * Generate search results for an x4browse/search
      *
      * @author: Kenneth Downs
      */
    function browseFetch() {
        #  This is the list of columns to return 
        $acols = explode(',',$this->dd['projections']['_uisearch']);

        #  By default the search criteria come from the 
        #  variables, unless it is a child table search
        $vals = aFromGP('x4w_');
        $awhere = array();
        $tabPar = gp('tableIdPar');
        if($tabPar<>'') {
            $ddpar = ddTable(gp('tableIdPar'));
            $pks   = $ddpar['pks'];
            $stab  = SQLFN(gp('tableIdPar'));
            $skey  = SQLFN(gp('skeyPar'));
            $vals2 = SQL_OneRow("SELECT $pks FROM $stab WHERE skey = $skey");
            $vals  = array_merge($vals,$vals2);
        }
        
        # Build the where clause        
        #
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
             ."  FROM ".$this->view_id
             .$SWhere
             .$SQLOrder
             .$SLimit;
        $answer =SQL_AllRows($SQL);
        
        $this->browseFetchModify($answer);
        
        x4Data('browseFetch',$answer);
        return;

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
        $skey = SQLFN(gp('x4w_skey'));
        $row = SQL_OneRow("SELECT * FROM ".$this->view_id." WHERE skey=$skey");
        x4Data('row',$row);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 3: Execute an skey-based update 
    #
    # ===================================================================
    /**
      * Execute an skey-based update
      *
      */
    function update() {
        $row = aFromGP('x4v_');
        $skey= 0;
        $table_id = $this->dd['table_id'];

        # KFD 6/12/08, allow functions to modify or prevent a write
        $tbefore = $table_id."_writeBefore";
        $tafter  = $table_id."_writeAfter";
        if(function_exists($tbefore)) {
            $message = $tbefore($row);
            if($message<>'') {
                x4Error($message);
                return;
            }
        }
        
        # KFD 6/28/08, a non-empty date must be valid
        $errors = false;
        foreach($row as $col => $value) {
            if(!isset($this->dd['flat'][$col])) {
                unset($row[$col]);
                continue;
            }
            $ermsg = "Invalid date format for "
                .$this->dd['flat'][$col]['description'];
            $ermsg2 = "Invalid date value for "
                .$this->dd['flat'][$col]['description'];
            if($this->dd['flat'][$col]['type_id'] == 'date') {
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
        
        if($row['skey']==0 || !isset($row['skey'])) {
            unset($row['skey']);
            $skey = SQLX_Insert($this->dd,$row);
            if(!errors()) {
                $row=SQL_OneRow(
                    "Select * FROM {$this->view_id} WHERE skey = $skey"
                );
            }
            x4Data('row',$row);
        }
        else {
            SQLX_Update($this->dd,$row);
            if(!errors()) {
                $skey = $row['skey'];
                $row=SQL_OneRow(
                    "Select * FROM {$this->view_id} WHERE skey = $skey"
                );
                x4Data('row',$row);
            }
        }
        
        # KFD 6/12/08, allow functions to modify or prevent a write
        if(Errors()) return;
        if(function_exists($tafter)) {
            $message = $tafter($row);
            if($message<>'') {
                x4Error($message);
                return;
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
        $skey = SQLFN(gp('skey'));
        $sq="Delete from ".$this->view_id." where skey = $skey";
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
}
?>
