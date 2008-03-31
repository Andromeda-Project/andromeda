<?php
class androX4 {
    function __construct() {
        $this->table_id = gp('x4Page');
        $this->table = dd_tableRef($this->table_id);
        $this->flag_buffer = true;
        $this->PageSubtitle = '';
        
        if(isset($this->table['description'])) {
            $desc = $this->table['description'];
            if(substr($desc,-3)=='ies') {
                $sing = substr($desc,0,strlen($desc)-3).'y';
            }
            else {
                $sing = substr($desc,0,strlen($desc)-1);
            }
            $this->table['singular'] = $sing;
        }
    }
    
    
    # ===================================================================
    #
    # Major Area 0: Put out the menu, this is x_table2 methods 
    #
    # ===================================================================
    function main() {
        ?>
        <div id="x4divLayer_1" class="x4Layer">
        <center>
        <h1>X4 Menu</h1>
        <div style="text-align: left; width: 50%; font-size:1.2em">
        <p>
        This is the x4 version of the 
        application menu.  If you want to return to "classic" Andromeda,
        <a href="index.php">click here</a>.
        </p>
        <p>
        Andromeda x4 is all about the keyboard.  Try out the arrow keys
        to move around, hit ENTER to pick a menu item, and try out hitting
        some numbers and letters to see what happens!
        </p>
        </div>
        </center>
        <br/>
        <?php
        $table = html('table');
        $table->hp['class']='tab100';
        $tr    = html('tr',$table);
        $array=SessionGet('AGMENU');
        $first = true;
                
        $letters=array('a','b','c','d','e','f','g','h','i','j','k'
            ,'l','m','n','o','p','q','r','s','t','u','v'
            ,'w','x','y','z'
        );
        $col = 0;
        $grid = array();
        foreach($array as $module=>$modinfo) {
            if(!$first) {
                $td = html('td',$tr);
                $td->hp['style'] = 'width: 10px';
            }
            $first = false;
            $td = html('td',$tr);
            $td->hp['class'] = 'x4box';
            
            $h3 = html('h3',$td);
            $h3->setHtml(($col+1).'. '.$modinfo['description']);
            
            $row = 0;
            foreach($modinfo['items'] as $page=>$info) {
                // Add the link
                $a = html('a',$td);
                $a->hp['id']='x4menu_'. $page;
                $a->hp['href']="javascript:x4Menu.open('$page')";
                $a->setHTML($letters[$row].'. '.$info['description']);
                $a->hp['onkeydown']="x4Menu.click(event,$col,$row)";
                $a->hp['onmouseover']='this.focus()';
                $a->hp['onfocus'] = "this.className = 'x4MenuOn';x4Menu.lastFocusId=this.id";
                $a->hp['onblur']  = "this.className = ''";
                $grid[$col][$row] = 'x4menu_'.$page;
                $row++;

                // Add a BR
                html('br',$td);
            }
            $col++;
        }
        echo $table->render();
        ?>
        <script>
        var grid = <?=json_encode_safe($grid)?>;
        </script>
        </div>
        
        <div id="dialog" style="display:none">
        This is an alert dialog!
        </div>
        <?php
        
        // Command to suppress hidden variables
        vgfSet('suppress_hidden',true);
        elementAdd('jqueryDocumentReady','x4Menu.init()');
    }
    
    # ===================================================================
    #
    # Major Area 1: Create a browse/search box
    #
    # ===================================================================
    /**
      * Generate an x4 browse/search box and add it to the
      * stack of stuff to return via JSON.
      *
      * @author: Kenneth Downs
      */
    function browse() {
        $div = html('div');
        
        $help = html('div',$div);
        $help->hp['class'] = 'x4box';
        $help->hp['id'] = 'help';
        $help->hp['style'] = 'float: right; width: 35%; display:none;';
        $help->setHTML(
            "<h3>Helpful Hints</h3>
            Start typing in any box to see search results.
            <br/>
            <br/>
            A maximum of 20 search results are displayed.
            <br/>
            <br/>
            Up and down arrows move through search results.  PageUp
            and PageDown goes to top and bottom.
            <br/>
            <br/>
            Hit ENTER to pick a row and edit it.
            <br/>
            <br/>
            Hit SHIFT-UpArrow or SHIFT-DownArrow to sort the results.
            <br/>
            <br/>
            CTL-N does a new row.
            <br/>
            CTL-D deletes the current row.
            <br/>
            CTL-H turns this HELP on and off.
            <br/>
            <b>When in doubt, hit ESC</b>");
            
        $h1  = html('h1',$div,$this->table['description']);
        
        $menubar = html('div',$div);
        $menubar->hp['class'] = 'x2menubar';
        $menubar->hp['style'] = 'margin-bottom: 8px; margin-top: 6px';
        $a = html('a-void',$menubar,'<b><u>N</u></b>ew '.$this->table['singular']);
        $a->hp['onclick']='javascript:x4Browse.new()';
        $a->hp['accesskey']='n';
        $a->hp['style']="margin-right: 15px";
        $a = html('a-void',$menubar,'<b><u>D</u></b>elete');
        $a->hp['onclick']='javascript:x4Browse.delete()';
        $a->hp['accesskey']='d';
        $a->hp['style']="margin-right: 15px";
        $a = html('a-void',$menubar,"<u><b>I</b></u>mports");
        $a->hp['onclick']='?gp_page=x_import&gp_table_id='.$this->table_id;
        $a->hp['accesskey']='i';
        $a->hp['style']="margin-right: 15px";
        $a = html('a-void',$menubar,"<u><b>H</b></u>elp");
        $a->hp['onclick']='javascript:x4Browse.help()';
        $a->hp['accesskey']='h';
        $a->hp['style']="margin-right: 15px";
        $a = html('a-void',$menubar,"ESC: Back to Menu");
        $a->hp['onclick']='javascript:x4Layers.pop()';
        $a->hp['style']="margin-right: 15px";
        
        $t =  html('table',$div);
        $t->hp['style']='z-index:50';
        $th = html('tr',$t);
        $ti = html('tr',$t);
        $tbody = html('tbody',$t);
        $tbody->hp['id'] = 'x4browsetbody';
        
        $inputs = array();
        $details= array(
            'table_id'=>gp('x4Page')
            ,'returnAll'=>$this->table['returnall']
        );

        // Create the headers and inputs        
        $tabindex = 1000;
        foreach($this->table['flat'] as $column=>$colinfo) {
            if(arraySafe($colinfo,'uisearch','N')<>'Y') continue;
            
            $column = trim($column);
            $hx = html('th',$th);
            $hx->innerHtml = $colinfo['description'];
            
            $td = html('td',$ti);
            $inp= html('input',$td);
            $inp->hp['class']     = 'x4browseinput x4input'; 
            $inp->hp['tabindex']  = ++$tabindex;
            $inp->ap['x_tabindex']  = $tabindex;
            $inp->hp['id'] = 'search_'.$column;
            
            // andromeda Properties
            $inp->ap['x_column_id'] = $column;
            
            // List of inputs for use by Javascript
            $inputs[] = $inp->hp['id'];
        }
        
        // Now put out the HTML...
        jsonHtml('*MAIN*',$div->bufferedRender());
        // ...and then put out the data
        jsonData('inputs',$inputs);
        jsonData('details',$details);
        jsonData('dd',$this->table);
    }
    
    # ===================================================================
    #
    # Major Area 2: Retrieve search results from server 
    #
    # ===================================================================
    /**
      * Generate search results for an x4browse/search
      *
      * @author: Kenneth Downs
      */
    function browseFetch() {
        // We will take the search criteria as the list of columns
        $cols = aFromGP('x4w_');
        
        // Build a where clause and a column list, and an order by clause
        // Order by is first column specified plus next in line
        $awhere = array();
        $acols  = array();
        $aorder = array();
        // Work out the ascending/descending thing
        $ascDesc = gp('sortAD')=='ASC' ? ' ASC' : ' DESC';
        foreach($cols as $column_id=>$colvalue) {
            if(!isset($this->table['flat'][$column_id])) continue;
            $colinfo = $this->table['flat'][$column_id];
            
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
            $acols[] = $column_id;
            
            // This causes the next column after ordered to be ordered also
            if(count($aorder)==1) {
                $aorder[] = $column_id.$ascDesc;
            }
            if($column_id == gp('sortCol')) {
                $aorder[] = $column_id.$ascDesc;
            }
        }
        
        
        // Build the query.  For a "returnall" table we actually
        // ignore the WHERE clause completely
        $SWhere = '';
        $SLimit = ' LIMIT 20';
        if($this->table['returnall'] <> 'Y') {
            $SWhere = ' WHERE '.implode(' AND ',$awhere);
            $SLimit = ' LIMIT 20';
        }
        // Retrieve data
        $SQL ="SELECT skey,".implode(',',$acols)
             ."  FROM ".$this->table_id
             .$SWhere
             ." ORDER BY ".implode(',',$aorder).$SLimit;
        $answer =SQL_AllRows($SQL);
        jsonDebug($SQL);

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
        jsonHtml('x4browsetbody',ob_get_clean());
        jsonData('skeys',$skeys);
        
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
    # Major Area 3: Return a basic details screen 
    #
    # ===================================================================
    /**
      * Generate search results for an x4browse/search
      *
      * @author: Kenneth Downs
      */
    function detail() {
        $skey = SQLFN(gp('x4skey'));
        $row = SQL_ONEROW("Select * from ".$this->table_id." where skey=$skey");
        
        $tabindex = 2000;

        $div = html('div');
        $h1 = html('h1',$div,$this->table['singular']);
        
        $menubar = html('div',$div);
        $menubar->hp['class'] = 'x2menubar';
        $menubar->hp['style'] = 'margin-bottom: 8px; margin-top: 6px';
        $a = html('a-void',$menubar,'<b><u>N</u></b>ew '.$this->table['singular']);
        $a->hp['onclick']='javascript:x4Detail.new()';
        $a->hp['accesskey']='n';
        $a->hp['style']="margin-right: 15px";
        $a = html('a-void',$menubar,'<b><u>D</u></b>elete');
        $a->hp['onclick']='javascript:x4Detail.delete()';
        $a->hp['accesskey']='d';
        $a->hp['style']="margin-right: 15px";
        $a = html('a-void',$menubar,"ESC: Back to Search");
        $a->hp['onclick']='javascript:x4Detail.tryToLeave();';
        $a->hp['style']="margin-right: 15px";
        
        
        // Generate a set of widgets
        $table = html('table',$div);
        $h1->hp['id'] = 'x4h1';
        $h1->ap['base'] = $this->table['singular'];
        
        $table->hp['class'] = 'x4detail';
        $tid   = $this->table_id;
        $tabdd = $this->table;
        $inputs = array();
        foreach($tabdd['flat'] as $column_id=>$colinfo) {
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
            $input = input($colinfo);
            $inputs[] = $input->hp['id'];
            $input->hp['tabindex'] = $tabindex++;
            $td->setHtml($input->bufferedRender());
            $td->hp['class'] = 'x4input';
        }
        jsonHTML('*MAIN*',$div->bufferedRender());
        jsonData('inputs',$inputs);
    }
    
    # ===================================================================
    #
    # Major Area 4: Return a single row when asked 
    #
    # ===================================================================
    /**
      * Return a single row for a table
      *
      */
    function fetchRow() {
        $skey = SQLFN(gp('x4w_skey'));
        $row = SQL_OneRow("SELECT * FROM ".$this->table_id." WHERE skey=$skey");
        jsonData('row',$row);
    }

    # ===================================================================
    #
    # Major Area 5: Execute an skey-based update 
    #
    # ===================================================================
    /**
      * Execute an skey-based update
      *
      */
    function update() {
        $row = aFromGP('x4v_');
        if($row['skey']==0 || !isset($row['skey'])) {
            unset($row['skey']);
            SQLX_Insert($this->table,$row);
        }
        else {
            SQLX_Update($this->table,$row);
        }
    }
    
    # ===================================================================
    #
    # Major Area 6: Execute an skey-based delete 
    #
    # ===================================================================
    /**
      * Execute an skey-based update
      *
      */
    function delete() {
        $skey = SQLFN(gp('skey'));
        $sq="Delete from ".$this->table_id." where skey = $skey";
        echo $sq;
        SQL($sq);
    }

}
?>
