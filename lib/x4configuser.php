<?php
class x4configuser extends androX4 {
    # =================================================================
    # Area 1: 
    # =================================================================
    function mainLayout($container) {
        # Erase default help message
        vgfSet('htmlHelp','');

        $top = $container;
        
        # Pull the values
        # For a user, if there is no row, enter one and try again
        $dd = ddTable("configuser");
        $view = ddView('configuser');
        $row = SQL_AllRows("Select * from $view");
        if(count($row)==1) {
            $row = $row[0];
        }
        else {
            SQL("Insert into $view (skey_quiet) values ('N')");
            $row = SQL_OneRow("Select * from $view");
        }
        
        # Basic information at top
        html('h1',$top,'User Configuration');
        html('p',$top,'Any changes made here will take immediate 
            effect.');

        # Set up titles
        $table = html('table',$top);
        $thead = html('thead',$table);
        $tr    = html('tr',$thead);
        $tr->h('th','Setting'      , 'dark');
        $tr->h('th','Your Value'   , 'dark');
        $tr->h('th','Default Value', 'dark');
        $tr->h('th','&nbsp'        , 'dark');
        
        # Now put out inputs for each one
        $tbody = html('tbody',$table);
        $askip = array('recnum','_agg','skey_quiet','skey','uid_ins');
        foreach($this->flat as $column_id =>$colinfo) {
            if(in_array($column_id,$askip)) continue;
            $tr = html('tr',$tbody);
            $tr->hp['id'] = 'tr_'.$column_id;
            $tr->SetAsParent();
            $td = html('td',$tr,$colinfo['description']);
            
            # the input
            $input = input($colinfo);
            $input->hp['id'] = 'inp_'.$column_id;
            if($colinfo['type_id']=='text') 
                $input->setHTML($row[$column_id]);
            else {
                $input->hp['value'] = $row[$column_id];
                x4Script(
                    '$a.byId("'.$input->hp['id'].'").value="'
                    .$row[$column_id].'"'
                );
            }
            $input->hp['onchange'] = 'instaSave(this)';
            $input->ap['skey'] = $row['skey'];
            $td = html('td',$tr);
            $td->addChild($input);
            
            # The default value
            $td = html('td',$tr
                ,ConfigGet($column_id,'*null*',array('user'))
            );
            $td->hp['id']='def_'.$column_id;
            
            # The reset 
            $td = html('td',$tr);
            $button = html('a-void',$td,'Use Default');
            $button->hp['onclick'] = "makeDefault('$column_id')";
        }
    }
    
    
    # =================================================================
    # This class just contains skeleton code that calls
    # library routines.  The class x4configuser calls the
    # same routines
    # =================================================================
    function extraScript() {
        ?>
        <script>
        window.instaSave = function(obj) {
            $a.json.init('x4Page','configuser');
            $a.json.addParm('skey'  ,$a.p(obj,'skey'));
            $a.json.addParm('column',$a.p(obj,'xColumnId'));
            $a.json.addParm('value' ,encodeURIComponent(obj.value));
            $a.json.addParm('x4Action','instasave');
            
            if($a.json.execute()) {
                $a.json.process();
            }
        }
        
        window.makeDefault = function(id) {
            var obj = $a.byId('inp_'+id);
            obj.value = '';
            $a.json.init('x4Page','configuser');
            $a.json.addParm('skey'  ,$a.p(obj,'skey'));
            $a.json.addParm('column',$a.p(obj,'xColumnId'));
            $a.json.addParm('x4Action','makeDefault');
            
            if($a.json.execute()) {
                $a.json.process();
            }
        }
        </script>
        <?php
    }
    
    function instaSave() {
        $val = trim(urldecode(gp('value')));
        $row = array(
            'skey'=>gp('skey')
            ,gp('column')=>$val
        );
        SQLX_Update('configuser',$row);
        configWrite('user');        
    }

    function makeDefault() {
        $col = SQLFN(gp('column'));
        $skey= SQLFN(gp('skey'));
        SQL("update configuser set $col = null WHERE skey = $skey");
        configWrite('user');        
    }
}
?>
