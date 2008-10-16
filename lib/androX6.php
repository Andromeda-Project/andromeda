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
    /****m* androX6/html
    *
    * NAME
    *   androX6/html
    *
    * PURPOSE
    *   If you want to make a purely custom web page then 
    *   use the html() method to generate and display your html.
    *
    ******
    */
    function html() { }
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
        SQL("set datestyle to US");
        SQL("set datestyle to SQL");
        $skey = SQLFN(gp('x4w_skey'));
        $row = SQL_OneRow("SELECT * FROM ".$this->view_id." WHERE skey=$skey");
        x4Data('row',$row);
    }

    # ===================================================================
    #
    # SERVER FUNCTION 3: Execute an skey-based update or insert 
    #
    # ===================================================================
    function save() {
        $table_id = gp('x6Page');
        $dd = ddTable($table_id);
        
        $row0 = aFromGP('x4v_');
        $row1 = aFromgp('x4inp_'.$table_id.'_');
        $row = array_merge($row0,$row1);
        if(a($row,'skey',0)==0) unset($row['skey']);

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
        $view = ddView(gp('x6Page'));
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
    # *******************************************************************
    #
    # Profile 1: "twosides"
    #
    # *******************************************************************
    # ===================================================================
    function profile_twosides() {
        # always include the styles and javascript.
        cssinclude('clib/x6.css');
        jsInclude('clib/x6.js');
        
        # Do this first, so all objects are initialized
        # and ready to receive events that may be fired
        # from downstream jqDocReady() calls.
        jqDocReady('x6.init()');
        
        # Begin with the data dictionaries and db pulls
        $dd       = $this->dd;
        $table_id = $this->dd['table_id'];
        
        # Now put in your basic title
        $div = html('div');
        $div->addClass('fadein');
        $div->h('h1','<center>'.$dd['description'].'</center>');
        
        # Make the div into a table controller.  There always
        # has to be a table controller somewhere.
        $div->hp['id'] = 'tc_'.$table_id;
        $div->ap['x6plugIn'] = 'tableController';
        $div->ap['x6table']  = $table_id;
        
        # Create a two-sided layout by creating two boxes
        # Left side is a grid plugin
        $box1  = $div->h('div');
        $box1->addClass('box');
        include 'x6plugInGrid.php';
        $x6grid = new x6plugInGrid;
        $x6grid->main($box1,$dd);
        
        $box2  = $div->h('div');
        $box2->addClass('box');
        include 'x6plugInDetail.php';
        $x6detail = new x6plugInDetail;
        $x6detail->main($box2,$dd);
        
        # Render it!  That's it!
        $div->render();
    }
}
?>
