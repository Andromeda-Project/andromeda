<?php
class x4configconfirm extends androX4 {
    function mainLayout($container) {
        # Erase default help message
        vgfSet('htmlHelp','');

        html('h1',$container,'Configuration Review');
        
        $table = html('table',$container);
        $table->hp['id'] = 'x2data1';
        $thead = html('thead',$table);
        $tr    = html('tr',$thead);
        $tr->h('th','Setting'     ,'dark');
        $tr->h('th','Framework'   ,'dark');
        $tr->h('th','Application' ,'dark');
        $tr->h('th','Instance'    ,'dark');
        $tr->h('th','User'        ,'dark');
        $tr->h('th','Your Setting','dark');

        # Include any of the files that exist        
        $dir = fsDirTop()."/dynamic/table_config";
        $configfw = $configapp = $configinst = $configuser = array();
        if(file_exists($dir.'fw.php')) {
            include($dir.'fw.php');
        }
        if(file_exists($dir.'app.php')) {
            include($dir.'app.php');
        }
        if(file_exists($dir.'inst.php')) {
            include($dir.'inst.php');
        }
        if(file_exists($dir.'user_'.SessionGet('UID').'.php')) {
            include($dir.'user_'.SessionGet('UID').'.php');
        }
        
        $dd = ddTable('configapp');
        $askip = array('recnum','skey','skey_quiet','_agg','uid_ins');
        $tbody = html('tbody',$table);
        $flipper = 0;
        foreach($dd['flat'] as $column_id=>$colinfo) {
            if(in_array($column_id,$askip)) continue;
            
            $tr = html('tr',$tbody);
            if($flipper>2) {
                $tr->addClass('lightgray');
            }
            else {
                if($flipper<>2) {
                    $tr->addClass('lightgraybottom');
                }
            }
            $flipper+=1;
            if($flipper==6) $flipper=0;
            html('td',$tr,$colinfo['description']);
            
            # The four levels from files
            html('td',$tr,a($configfw  ,$column_id));
            html('td',$tr,a($configapp ,$column_id));
            html('td',$tr,a($configinst,$column_id));
            if(a($colinfo,'flagcarry','N') == 'Y') {
                html('td',$tr,a($configuser,$column_id));
            }
            else {
                html('td',$tr,'n/a');
            }
            
            # The final resolved value
            html('td',$tr,ConfigGet($column_id));
        }
    
    }
}
?>
