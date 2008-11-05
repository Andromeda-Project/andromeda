<?php 
class x6pluginGrid extends androHTML {
    # ================================================================
    # 
    # Plugin main function, usually generates first version
    #  that goes to browser
    #
    # ================================================================
    function &main(&$area0,$dd,$skey=0,$height) {
        $table_id = $dd['table_id'];
        
        # Create a grid.
        $grid = $area0->addTabDiv($height);
        $grid->hp['id'] = 'tabDiv_'.$table_id;
        $grid->ap['x6plugIn'] = 'grid';
        $grid->hp['x6table']  = $table_id;
        
        # Add in the column headers
        $acols = explode(',',$dd['projections']['_uisearch']);
        foreach($acols as $acol) {
            $desc = $dd['flat'][$acol]['descshort'];
            if($desc=='') {
                $desc = $dd['flat'][$acol]['description'];
            }
            $desc = str_replace(' ','&nbsp;',$desc);
            $options=array(
                'column_id' =>  $acol
                ,'dispsize' =>  $dd['flat'][$acol]['dispsize']
                ,'type_id'  =>  $dd['flat'][$acol]['type_id']
                ,'description'=>$desc
                ,'sortable'   =>true
            );
            $grid->addColumn($options); 
        }
        
        # now make the columns sortable
        foreach($grid->headers as $idx=>$header) {
            $hdrhtml = $header->getHtml();
            $a = html('a-void');
            $a->setHtml('&uarr;&darr;');
            $col = $grid->columns[$idx]['column_id'];
            $args="{xChGroup:'$table_id', xColumn: '$col'}";
            $a->hp['onclick'] = "x6events.fireEvent('reqSort_$table_id',$args)";
            $a->hp['xChGroup'] = $table_id;
            $a->hp['xColumn']  = $col;
            $grid->headers[$idx]->setHtml(
                $hdrhtml.'&nbsp;&nbsp;'.$a->bufferedRender()
            );
        }
        
        $this->width = $grid->lastColumn();
        # Only add one new row.  It wraps automatically to
        # new rows as needed.
        $sortCol = gp('sortCol',$dd['pks']);
        $sortAsc = gp('sortAsc','true')=='true' ? 'ASC' : 'DESC';
        x4debug($sortCol);
        x4debug($sortAsc);
        $sWhere = $skey==0 ? '' : ' where skey = '.SQLFC($skey);
        $sq="Select * from ".$dd['viewname']." $sWhere
              order by $sortCol $sortAsc";
        x4debug($sq);
        $rows = SQL_AllRows($sq,'skey');
        foreach($rows as $user) {
            $tr = $grid->addRow($user['skey']);
            #$tr->hp['id'] = 'row_'.$user['skey'];
            # This code moved into tabdiv itself
            #$tr->hp['onmouseover']="$(this).addClass('hilight')";
            #$tr->hp['onmouseout'] ="$(this).removeClass('hilight')";
            #$tr->hp['onclick']    
            #    ="x6events.fireEvent('reqEditRow_$table_id',{$user['skey']});";
            foreach($acols as $col) {
                $grid->addCell($user[$col]);
            }
        }

        # This plugin will cache the rows
        jqDocReady("x6events.fireEvent('cacheRows_$table_id',"
            .json_encode($rows)
            .')'
        );

        return $grid;
    }
    

    # ================================================================
    # 
    # A loan refresh method usually just resends the data for
    # some reason.
    #
    # ================================================================
    function refresh($skey=0) {
        $table_id = gp('x6page');
        $dd = ddTable($table_id);
        $div = html('div');
        $grid = &$this->main($div,$dd,$skey);
        if($skey==0) {
            x4HTML('*MAIN*',$grid->dbody->bufferedRender());
        }
        else {
            x4HTML('*MAIN*',$grid->rows[0]->bufferedRender());
        }
    }
    # ================================================================
    # 
    # A onerow request wants the html for a single row
    #
    # ================================================================
    function oneRow() {
        $this->refresh(gp('skey'));
    }
    
}
?>
