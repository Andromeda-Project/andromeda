<?php
class androX4 {
    # ===================================================================
    #
    # Major Area 0: In the construction area, load up data dictionary 
    #
    # ===================================================================
    function __construct() {
        $this->table_id = gp('x4Page');
        $this->dd       = ddTable($this->table_id);
        $this->flat     = $this->dd['flat'];
        $this->view_id  = $this->dd['viewname'];
        $this->tabindex=1000;
        
        $this->custom_construct();
    }
    #  Placeholder, to be overridden by subclasses
    function custom_construct() {
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
        # If we see a "gp_pk" variable, they are requesting a certain
        # detail row.  Find out the skey and pass instructions on.
        # Notice the assumption of only a single column.
        if( ($gpPk = gp('gp_pk')) <> '') {
            $pkc = $this->dd['pks'];
            $pkv = SQLFC($gpPk);
            $skey=SQL_OneValue('skey'
                ,"Select skey FROM $this->table_id WHERE $pkc = $pkv"
            );
            x4Data('init',$skey);
        }
        
        # All top-level elements will go inside of this div 
        $x4Top = html('div');
        $x4Top->hp['id']='x4Top';
        $this->mainLayout($x4Top);
        x4Html('*MAIN*',$x4Top->bufferedRender());
        x4Data('dd.'.$this->table_id,$this->dd);
        x4Data('returnto',gp('x4Return'));
        return;
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
        $detail = $this->detailPane($this->dd);
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
            if(a($info,'fkdisplay','') == 'none') continue;
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
            
            # Add into it the grid and the detail
            $tabx->addChild( $this->grid($ddChild,$this->table_id) );
            $tabx->addChild( $this->detailPane($ddChild,$this->table_id) );
            
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
        //$menuBar->hp['style']='float:left';
        $id = 'x4MenuBar';
        $menuBar->hp['id'] = $id;
        $menuBar->setAsParent();
        
        $mbTab=html('table',$menuBar);
        $mbTab->addClass('tab100');
        $mbr  =html('tr',$mbTab);
        $mbl  =html('td',$mbr);
        $mbr  =html('td',$mbr);
        $mbr->hp['style'] = 'text-align: right';
        //$mbr = &$menuBar;
        //$mbl = &$menuBar;
        
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
    function grid($dd,$tableIdPar = '') {
        $table_id = $dd['table_id'];
        
        # Generate default options
        #$inputs = a($options,'inputs','Y');
        $inputs='Y';
        
        # Everything goes into this table        
        $t =  html('table');
        $t->hp['id'] = 'grid_'.$table_id;
        $t->ap['xTableId'] = $table_id;
        $t->ap['xReturnAll'] = 'N';
        $t->addClass('x4Pane');
        $t->addClass('x4GridSearch');
        $t->setAsParent();
        $tb1 = html('tbody',$t);
        $th = html('tr',$tb1);
        if($inputs=='Y') {
            $ti = html('tr',$tb1);
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
            $hx->innerHtml = $colinfo['description'];
            
            if($inputs=='Y') {
                $inp= input($fakeCI,$tabLoop);
                $inp->hp['id'] = 'search_'.$table_id.'_'.$column;
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
    function detailPane($dd,$parentTable=null) {
        $div = html('div');
        $div->hp['id'] = 'detail_'.$dd['table_id'];
        $div->ap['xTableId'] = $dd['table_id'];
        $div->addClass('x4Pane');
        $div->addClass('x4Detail');
        $div->setAsParent();
        
        # create the table that will hold the inputs 
        $table = html('table',$div);
        $table->hp['class'] = 'x4detail';
        $tid   = $dd['table_id'];

        $trx   = html('tr',$table);
        $tdx   = html('td',$trx);
        $table = html('table',$tdx);
        $tabLoop = array();
        $colcount   = 0;
        $colbreak   = a($dd,'colbreak',17);
        $breakafter = a($dd,'breakafter',array());
        foreach($dd['flat'] as $column_id=>$colinfo) {
            if($colinfo['uino']=='Y'   ) continue;
            if($column_id=='skey'      ) continue;
            if($column_id=='_agg'      ) continue;
            if($column_id=='skey_quiet') continue;
        
            // The row and the caption
            $tr = html('tr',$table);
            $td = html('td',$tr);
            $td->setHtml($colinfo['description']);
            $td->hp['class'] = 'x4caption';
            
            // The input
            $td = html('td',$tr);
            $td->hp['class'] = 'x4input';
            $input = input($colinfo,$tabLoop
                ,array('parentTable'=>$parentTable)
            );
            $input->addClass('x4input');
            $input->ap['xParentId'] = $div->hp['id'];
            $td->addChild($input);

            # On twelfth column, break and make a new column of fields            
            $colcount++;
            if(count($breakafter)>0) {
                $break = in_array($column_id,$breakafter);
            }
            else {
                $break = $colcount == $colbreak ? true : false;
            }
            if($break) {
                $colcount=0;
                $tdx = html('td',$trx);
                $tdx->hp['style'] = 'width: 40px';
                $tdx = html('td',$trx);
                $table=html('table',$tdx);
            }
        }
        inputsTabLoop($tabLoop,array('xParentId'=>$div->hp['id']));
        return $div;
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
        # KFD 6/12/08, respect columns removed for security
        foreach($acols as $idx=>$value) {
            if(!isset($this->dd['flat'][$value])) unset($acols[$idx]);
        }


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
            if($type=='dtime' || $type=='date') {
                $tcv=dEnsureTS($tcv);
            }
            if ($tcv != "") {
                // trap for a % sign in non-string
                $awhere[]='('
                    .$this->searchBrowseOneCol($type,$column_id,$tcv,$exact)
                    .')';
            }
        }

        #  Build the Order by
        #        
        $ascDesc = gp('sortAD')=='ASC' ? ' ASC' : ' DESC';
        $aorder = array();
        $searchsort = trim(a($this->dd,'uisearchsort',''));
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
            $SQLOrder = " ORDER BY ".implode(',',$aorder)." Limit 20";
        }
        else {
            foreach($acols as $column_id) {
                // This causes the next column after ordered to be ordered also
                if(count($aorder)==1) {
                    $aorder[] = $column_id.$ascDesc;
                }
                if($column_id == gp('sortCol')) {
                    $aorder[] = $column_id.$ascDesc;
                }
            }
            if(count($aorder)==0) {
                $SQLOrder = " LIMIT 20";
                #$aorder[] = $acols[0];
            }
            else {
                $SQLOrder = " ORDER BY ".implode(',',$aorder)." Limit 20";
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
        $SLimit = ' LIMIT 20';
        $SWhere = ' WHERE '.implode(' AND ',$awhere);

        // Retrieve data
        $SQL ="SELECT skey,".implode(',',$acols)
             ."  FROM ".$this->view_id
             .$SWhere
             .$SQLOrder;
        $answer =SQL_AllRows($SQL);
        x4Debug($SQL);

        // Format as HTML
        ob_start();
        $skeys = array();
        foreach($answer as $idx=>$row) {
            $skey = $row['skey'];
            echo "<tr xIndex=\"$idx\" id='x4row_$skey' class='x4brrow'>";
            $skeys[] = $row['skey']; 
            foreach($row as $idx=>$value) {
                if($idx=='skey') continue;
                echo "<td>".$value;
            }
        }
        x4Html('*MAIN*',ob_get_clean());
        $skeys = array_flip($skeys);  // want to go by skey, not index
        x4Data('skeys',$skeys);
        x4Data('rowCount',count($skeys));
        
    }
    
    function searchBrowseOneCol($type,$colname,$tcv,$exact) {
        $values=explode(',',$tcv);
        $sql_new=array();
        foreach($values as $tcv) {
            if(trim($tcv)=='') continue;
            if($tcv=='*') $tcv='%';
            //$tcv = trim(strtoupper($tcv));
            $tcv = strtoupper($tcv);
            if(in_array($type,array('int','numb','date','time'))) {
                $tcv=preg_replace('/[^0-9]/','',$tcv);
            }
            
            // This is a greater than/less than situation,
            // we ignore anything else they may have done
            if(substr($tcv,0,1)=='>' || substr($tcv,0,1)=='<') {
                $new=$colname.substr($tcv,0,1).sql_Format($type,substr($tcv,1));
                $sql_new[]="($new)";
                continue;
            }
            
            if(strpos($tcv,'-')!==false  && $type<>'ph12' && $type<>'ssn') {
                list($beg,$end)=explode('-',$tcv);
                if(trim($end)=='') {
                    $new=" UPPER($colname) like '".strtoupper($beg)."%'";                
                }
                else {
                    $slbeg = strlen($beg);
                    $slend = strlen($end);
                    $new="SUBSTR($colname,1,$slbeg) >= ".sql_Format($type,$beg)
                        .' AND '
                        ."SUBSTR($colname,1,$slend) <= ".sql_Format($type,$end);
                }
                $sql_new[]="($new)";
                continue;
            }
    
            if($exact) {
                $tcsql = sql_Format($type,$tcv);
                $new=$colname."=".$tcsql;
            }
            else if(! isset($aStrings[$type]) && strpos($tcv,'%')!==false) {
                $new="cast($colname as varchar) like '$tcv'";
            }
            else {
                $tcsql = sql_Format($type,$tcv);
                if(substr($tcsql,0,1)!="'" || $type=='date' || $type=='dtime') {
                    $new=$colname."=".$tcsql;
                }
                else {
                    $tcsql = str_replace("'","''",$tcv); 
                    $new=" UPPER($colname) like '%".strtoupper($tcsql)."%'";
                }
            }
            $sql_new[]="($new)";
        }
        $retval = implode(" OR ",$sql_new);
        return $retval;
        
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
            $colcs[] = $info['column_id'];
            $colsp[] = $info['column_id_par'];
        }
        $type_id = $this->dd['flat'][$column_id]['type_id'];
        $value   = SQL_Format($type_id,gp('value'));
        $sql="SELECT ".implode(',',$colsp)
            ."  FROM ".ddTable_idResolve($table_id_fko)
            ." WHERE ".$this->dd['fk_parents'][$table_id_fko]['cols_par']."= $value";
        $answer = SQL_OneRow($sql);
        x4Data('row',$answer);
    }    
}
?>
