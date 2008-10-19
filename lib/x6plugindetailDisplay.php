<?php 
class x6plugindetailDisplay {
    # ================================================================
    # 
    # Plugin main function, usually generates first version
    #  that goes to browser
    #
    # ================================================================
    function &main(&$area1,$dd) {
        $table_id = $dd['table_id'];
        
        # Now for the display
        # Put some buttons on users
        $bb = $area1->h('div');
        $bb->hp['style'] = 'text-align: left';
        $a=$area1->h('a-void','New');
        $a->addClass('button');
        $a->hp['style'] = 'margin-left: 0px';
        $a->hp['x6table']  = $table_id;
        $a->hp['x6plugIn'] = 'buttonNew';
        $a->hp['style']    = 'float: left';
        $a=$area1->h('a-void','Duplicate');
        $a->addClass('button');
        $a->hp['x6table']  = $table_id;
        $a->hp['x6plugIn'] = 'buttonDuplicate';
        $a->hp['style']    = 'float: left';
        $a=$area1->h('a-void','Save');
        $a->addClass('button');
        $a->hp['x6table']  = $table_id;
        $a->hp['x6plugIn'] = 'buttonSave';
        $a->hp['style']    = 'float: left';
        $a=$area1->h('a-void','Remove');
        $a->addClass('button');
        $a->hp['x6table']  = $table_id;
        $a->hp['x6plugIn'] = 'buttonRemove';
        $a->hp['style']    = 'float: right';
        $a=$area1->h('a-void','Abandon Changes');
        $a->addClass('button');
        $a->hp['x6table']  = $table_id;
        $a->hp['x6plugIn'] = 'buttonAbandon';
        $a->hp['style']    = 'float: right';
        
        $area1->br(2);
        
        # generate a detail pane of inputs and assign
        # the standard keyup to all of them.
        #
        # Note: tabLoop is a requirement for x4 that is easier
        # to just define and pass in than it is to try to make
        # it go away.  
        $tabLoop = array();
        $options = array(
            'onkeyup'=>'x6inputs.keyUp(event,this)'
            ,'onkeydown'=>'x6inputs.keyDown(event,this)'
            ,'onfocus'=>'x6inputs.focus(this)'
            ,'onblur'=>'x6inputs.blur(this)'
            ,'attributes'=>array(
                'xClassRow'=>1
                ,'disabled'=>true
            )
            ,'classes'=>array('readOnly')
            ,'tabIndex'=>true
            
        );
        $projection = projection($dd,'',$tabLoop,$options);
        foreach($projection->inputs as $idx=>$input) {
            /*
            $projection->inputs[$idx]->hp['onkeyup']
                ='x6inputs.keyUp(event,this)';
            $projection->inputs[$idx]->hp['onkeypress']
                ='x6inputs.keyDown(event,this)';
            $projection->inputs[$idx]->hp['onfocus']
                ='x6inputs.focus(this)';
            $projection->inputs[$idx]->hp['onblur']
                ='x6inputs.blur(this)';
            $projection->inputs[$idx]->hp['xClassRow'] = '1';
            $projection->inputs[$idx]->hp['disabled'] = 'true';
            */
            //$projection->inputs[$idx]->addClass('readOnly');
            $projection->inputs[$idx]->tabIndex();
        }
        
        
        $area1->hp['x6plugin'] = 'detailDisplay';
        $area1->hp['x6table']  = $table_id;
        $area1->hp['id'] = 'ddisp_'.$table_id;
        $area1->addChild($projection);
        
        $area1->br(5);
        $sb = $area1->h('div','status message here');
        $sb->addClass('statusBar');
        
        $disables=<<<JS
            x6events.fireEvent('disable_duplicate');
            x6events.fireEvent('disable_save');
            x6events.fireEvent('disable_abandon');
            x6events.fireEvent('disable_remove');
JS;
        jqDocReady($disables);

        return $area1;
    }
}
?>
