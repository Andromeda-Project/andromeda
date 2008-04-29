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
        # All top-level elements will go inside of this div 
        $div = html('div');
        $div->hp['id']='x4Top';
        $this->mainLayout($div);
        x4Html('*MAIN*',$div->bufferedRender());
        x4Data('dd.'.$this->table_id,$this->dd);
        x4Data('return',gp('x4Return'));
        return;
    }
    
    function mainLayout(&$div) {
        # The first two are simple, a description 
        # and the menu bar.  These are permanent and will
        # be displayed for the entire time.
        $h1  = html('h1',$div,$this->dd['description']);
        $h1->hp['id']='x4H1Top';
        
        # The window controller is very top object, contains menu
        # bar and any details
        #
        $x4Window = html('div',$div);
        $x4Window->addClass('x4Pane');
        $x4Window->addClass('x4Window');
        $x4Window->addChild( $this->menuBar($this->dd) );
        
        #  This is the top level display item
        #
        $x4Display = html('div',$x4Window);
        $x4Display->addClass('x4Pane');
        $x4Display->addClass('x4TableTop');
        $x4Display->ap['xTableId'] = $this->table_id;
        $x4Display->hp['id'] = 'x4TableTop_'.$this->table_id;
        
        # Create a grid for the default display
        $grid = $this->grid($this->dd);
        $grid->addClass('x4VerticalScroll1');
        $x4Display->addChild( $grid );
        
        # Create a container and tab bar, 
        # with the row-display inside of it
        #
        $tabC = html('div',$x4Display);
        $tabC->addChild($this->rowDisplay());
        $tabC->addClass('x4Pane');
        $tabC->addClass('x4TabContainer');
        $tabC->hp['id'] = 'x4TabContainer_'.$this->table_id;
        $tabC->ap['xTableId'] = $this->table_id;
        $tabB = html('div',$tabC);
        $tabB->addClass('x4TabBar');
        $tabB->hp['id']='x4TabBar';
        
        # For now we create only one detail pane, 
        # later on we want more
        #
        $tabid = 'tab_'.$this->dd['table_id'];
        $span = html('a-void',$tabB,'Detail');
        $tabx = html('div',$tabC);
        $detail = $this->detailPane($this->dd);
        $detailId = $detail->hp['id'];
        $detail->addClass('x4VerticalScroll2');
        $span->hp['onclick']="x4MenuBar.tabEvent(this,'$detailId')";
        $tabC->addChild( $detail );
        
        # Child table panes are added in a loop because there
        # may be more than one
        #
        foreach($this->dd['fk_children'] as $table_id=>$info) {
            $tabid = 'xTableTop_'.$table_id;
            $ddChild = ddTable($table_id);
            x4Data('dd.'.$table_id,$ddChild);
            $span=html('a-void',$tabB,$ddChild['description']);
            $span->hp['onclick'] = "x4MenuBar.tabEvent(this,'$tabid')";
            
            # Make a tableTop container
            $tabx = html('div',$tabC);
            $tabx->addClass('x4Pane');
            $tabx->addClass('x4TableTop');
            $tabx->hp['id'] = $tabid;
            $tabx->ap['xTableId'] = $table_id;
            
            # Add into it the grid and the detail
            $options = array('inputs'=>'N');
            $tabx->addChild( $this->grid($ddChild,$options) );
            $tabx->addChild( $this->detailPane($ddChild) );
        }
    }        
        
    # ===================================================================
    #
    # Major Area 2: Generate the Universal Menu Bar 
    #
    # ===================================================================
    function menuBar($dd) {
        $menubar = html('div');
        $menubar->hp['class'] = 'x4MenuBar';
        
        $a = html('a-void',$menubar,'<b><u>N</u></b>ew '
            .$dd['singular']
        );
        $a->hp['onclick']="x4MenuBar.eventHandler('newRow')";
        $a->hp['id']='button-new';
        $a->hp['accesskey']='n';
        
        $a = html('a-void',$menubar,'<b><u>D</u></b>elete');
        $a->hp['id']='button-del';
        $a->hp['onclick']="x4MenuBar.eventHandler('deleteRow')";
        $a->hp['accesskey']='d';
        
        $a = html('a-void',$menubar,'Co<b><u>p</u></b>y');
        $a->hp['id']='button-cpy';
        $a->hp['onclick']="x4MenuBar.eventHandler('copyRow')";
        $a->hp['accesskey']='p';
        
        $a = html('a-void',$menubar,'<b><u>S</u></b>ave');
        $a->hp['id']='button-sav';
        $a->hp['onclick']="x4MenuBar.eventHandler('saveRow')";
        $a->hp['accesskey']='s';
        
        $a = html('a-void',$menubar,'S<b><u>a</u></b>ve &amp; New');
        $a->hp['id']='button-snw';
        $a->hp['onclick']="x4MenuBar.eventHandler('saveRowAndNewRow')";
        $a->hp['accesskey']='a';
        
        $a = html('a-void',$menubar,'Save &amp; E<b><u>x</u></b>it');
        $a->hp['id']='button-sxt';
        $a->hp['onclick']="x4MenuBar.eventHandler('saveRowAndExit')";
        $a->hp['accesskey']='x';
        
        $a = html('a-void',$menubar,"ESC: Quit");
        $a->hp['id']='button-esc';
        $a->hp['onclick']="x4MenuBar.eventHandler('onEscape')";
        
        $a = html('a',$menubar,"Imports");
        $a->hp['href'] = "?gp_page=x_imports&gp_table_id=".$dd['table_id'];
        
        return $menubar;
    }        
        
    function rowDisplay() {
        $div = html('div');
        $div->hp['id'] = 'x4RowInfo';
        $aTop  = html('a-void',$div,'Top');
        $aTop->hp['onclick'] = "x4Detail.move('CtrlPageUp')";
        $aPrev = html('a-void',$div,'Previous');
        $aPrev->hp['onclick'] = "x4Detail.move('PageUp')";
        $span = html('span',$div,'Row x of y');
        $span->hp['id'] = 'x4RowInfoText';
        $aNext = html('a-void',$div,'Next');
        $aNext->hp['onclick'] = "x4Detail.move('PageDown')";
        $aBot  = html('a-void',$div,'Bottom');
        $aBot->hp['onclick'] = "x4Detail.move('CtrlPageDown')";
        return $div;
    }
            
    # ===================================================================
    #
    # Major Area 3: Generate a grid for a table 
    #
    # ===================================================================
    function grid($dd,$options = array()) {
        $table_id = $dd['table_id'];
        
        # Generate default options
        $inputs = a($options,'inputs','Y');
        
        # Everything goes into this table        
        $t =  html('table');
        $t->hp['id'] = 'grid_'.$table_id;
        $t->ap['xTableId'] = $table_id;
        $t->ap['xReturnAll'] = 'N';
        $t->addClass('x4Pane');
        $t->addClass('x4Grid');
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
        foreach($cols as $column) {
            $colinfo = $dd['flat'][$column];
            
            $column = trim($column);
            $hx = html('th',$th);
            $hx->innerHtml = $colinfo['description'];
            
            if($inputs=='Y') {
                $inp= html('input');
                $inp->hp['tabindex']  = ++$this->tabindex;
                $inp->hp['id'] = 'search_'.$table_id.'_'.$column;
                $inp->hp['autocomplete'] = 'off';
                $inp->ap['xTabindex']  = $this->tabindex;
                $inp->ap['xValue']='';
                $inp->ap['xColumnId'] = $column;
                $inp->ap['oHTMLId'] = $t->hp['id'];
                $td = html('td',$ti);
                $td->addChild($inp);
            }
        }
        return $t;
    }
    
    
    # ===================================================================
    #
    # Major Area 4: Detail Panes 
    #
    # ===================================================================
    function detailPane($dd,&$tabs=null,&$tabbar=null) {
        $div = html('div',$tabs);
        $div->hp['id'] = 'detail_'.$dd['table_id'];
        $div->ap['xTableId'] = $dd['table_id'];
        $div->addClass('x4Pane');
        $div->addClass('x4Detail');
        
        # create the table that will hold the inputs 
        $table = html('table',$div);
        
        $table->hp['class'] = 'x4detail';
        $tid   = $dd['table_id'];
        foreach($dd['flat'] as $column_id=>$colinfo) {
            // Early exits
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
            $input = input($colinfo);
            $input->addClass('x4input');
            $input->ap['oHTMLId'] = $div->hp['id'];
            $input->hp['tabindex'] = ++$this->tabindex;
            $td->addChild($input);
        }
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

        #  By default the search criteria come from the 
        #  variables, unless it is a child table search
        $vals = aFromGP('x4w_');
        if(gp('tableIdPar')<>'') {
            $ddpar = ddTable(gp('tableIdPar'));
            $pks   = $ddpar['pks'];
            $stab  = SQLFN(gp('tableIdPar'));
            $skey  = SQLFN(gp('skeyPar'));
            $vals  = SQL_OneRow("SELECT $pks FROM $stab WHERE skey = $skey");
        }
        
        # Build the where clause        
        #
        $awhere = array();
        foreach($vals as $column_id=>$colvalue) {
            if(!isset($this->flat[$column_id])) continue;
            $colinfo = $this->flat[$column_id];
            
            $tcv  = trim($colvalue);
            $type = $colinfo['type_id'];
            if($type=='dtime' || $type=='date') {
                $tcv=dEnsureTS($tcv);
            }
            if ($tcv != "") {
                // trap for a % sign in non-string
                $awhere[]
                    ='('.$this->searchBrowseOneCol($type,$column_id,$tcv).')';
            }
        }

        #  Build the Order by
        #        
        $ascDesc = gp('sortAD')=='ASC' ? ' ASC' : ' DESC';
        $aorder = array();
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
            $aorder[] = $acols[0];
        }
        
        
        // Build the query.  For a "returnall" table we actually
        // ignore the WHERE clause completely
        $SWhere = '';
        $SLimit = ' LIMIT 20';
        if($this->dd['returnall'] <> 'Y') {
            $SWhere = ' WHERE '.implode(' AND ',$awhere);
            $SLimit = ' LIMIT 20';
        }
        // Retrieve data
        $SQL ="SELECT skey,".implode(',',$acols)
             ."  FROM ".$this->view_id
             .$SWhere
             ." ORDER BY ".implode(',',$aorder).$SLimit;
        $answer =SQL_AllRows($SQL);
        x4Debug($SQL);

        // Format as HTML
        ob_start();
        $skeys = array();
        foreach($answer as $row) {
            echo "<tr id='x4row_{$row['skey']}' class='x4brrow'>";
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
    
    function searchBrowseOneCol($type,$colname,$tcv) {
        $values=explode(',',$tcv);
        $sql_new=array();
        foreach($values as $tcv) {
            if(trim($tcv)=='') continue;
            if($tcv=='*') $tcv='%';
            $tcv = trim(strtoupper($tcv));
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
    
            if(! isset($aStrings[$type]) && strpos($tcv,'%')!==false) {
                $new="cast($colname as varchar) like '$tcv'";
            }
            else {
                $tcsql = sql_Format($type,$tcv);
                if(substr($tcsql,0,1)!="'" || $type=='date' || $type=='dtime') {
                    $new=$colname."=".$tcsql;
                }
                else {
                    $tcsql = str_replace("'","''",$tcv); 
                    $new=" UPPER($colname) like '".strtoupper($tcsql)."%'";
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
        $row = SQL_OneRow("SELECT * FROM ".$this->table_id." WHERE skey=$skey");
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
        if($row['skey']==0 || !isset($row['skey'])) {
            unset($row['skey']);
            $skey = SQLX_Insert($this->dd,$row);
            if(!errors()) {
                $row=SQL_OneRow(
                    "Select * FROM $table_id WHERE skey = $skey"
                );
            }
            x4Data('row',$row);
        }
        else {
            SQLX_Update($this->dd,$row);
            if(!errors()) {
                $skey = $row['skey'];
                $row=SQL_OneRow(
                    "Select * FROM $table_id WHERE skey = $skey"
                );
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
      * Execute an skey-based update
      *
      */
    function delete() {
        $skey = SQLFN(gp('skey'));
        $sq="Delete from ".$this->table_id." where skey = $skey";
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
