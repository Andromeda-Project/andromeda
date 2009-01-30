<?php
class x6useroptions extends androX6 {
    function x6main() {
        $top = html('div');
        $top->addClass('fadein');
        $top->h('h1','User Options');
        
        $height = x6cssdefine('insideheight')
            - (x6cssHeight('h1')*2);
        $pad1   = x6cssDefine('pad1');
            
        $tabs = $top->addTabs('useroptions',$height);
        
        # --------------------------------------------------------------
        # This is skin stuff
        # --------------------------------------------------------------
        $tab1 = $tabs->addTab('Appearance');
        
        $tab1->hp['style'] = "padding: {$pad1}px"; 
        
        $file = fsDirTop().'templates/x6/skinsphp/x6skins.ser.txt';
        $skins = unserialize(file_get_contents($file));
        $select = html('select');
        $cookie = arr($_COOKIE,'x6skin','Default.Gray.1024');
        foreach($skins as $name=>$stats) {
            $option = $select->h('option',$name);
            $option->hp['value'] = $stats;
            # Note that $cookie was defined above 
            if($cookie==$stats) $option->hp['selected'] = 'selected';
        }
        $select->hp['onchange']='x6ChangeSkin(this)';

        ob_start();
        ?>        
        <script>
        window.x6ChangeSkin = function(select) {
            document.cookie 
                = "x6skin="+select.value+"; expires=12/31/2049 00:00:00;";
            window.location.reload(true);
        }
        </script>
        <h2>Skin Selection</h2>
        Skin: <?php echo $select->render()?>
        <?php
        $tab1->setHtml(ob_get_clean());
        
        # <------- EARLY RETURN
        #
        if(!inGroup('debugging')) {
            $top->render();
            return;
        }
        
        # --------------------------------------------------------------
        # Now for javascript and logging
        # --------------------------------------------------------------
        $tab2 = $tabs->addTab('Javascript Development');
        $tab2->hp['style'] = "padding: {$pad1}px";
        
        $tab2->h('h2','Alternate Javascript Files');
        $tab2->h('p','You can use this feature to debug and enhance the
            Andromeda Javascript files without having a complete installation.
            Here is how it works:');
        $ul = $tab2->h('ul');
        $ul->h('li'
            ,'Use Firebug to make local copies of x6.js and androLib.js'
        );
        $ul->h('li'
            ,'Put these files somewhere you can edit them which is also
              on a <i>publicly visible website</i>.'
        );
        $ul->h('li'
            ,'Put the address of the public website here, including a
              trailing slash.'
        );
        $ul->h('li'
            ,'<span style="color:red">If you make a mistake and the files
              are not visible, this demo will stop working.  Close your
              browser and try again.</span>'
        );
        $tab2->h('span','Alternate Location:&nbsp;&nbsp;');
        $input = html('input');
        $input->hp['size'] = 70;
        $input->hp['id'] = 'altjs';
        $input->hp['value'] = arr($_COOKIE,'altjs','');
        $input->code['change'] = <<<JS
        function(input) {
            createCookie('altjs',input.value);
        }
JS;
        $tab2->addChild($input);
        
        $tab2->h('h2','Logging');
        $tab2->h('p','Logging is by default turned off.  Use the checkboxes
            below to turn on the various logging features.'
        );
        
        $a = $tab2->h('a-void','Detect console devices');
        $a->code['click'] = <<<JS
        function(input) {
            var msg = x6consoleActivate();
            if(msg==false) {
                alert("No console devices found, logging is disabled");
            }
            else {
                alert(msg);
            }
        }
JS;
        $tab2->br(2);
        
        $loptions = array(
            'Server'=>'Server-Side Query Log'
            ,'FBLite'=>'I am on IE, load Firebug Lite for me'
            ,'Group'=>'Javascript Log Outline'
            ,'Log'=>'Javascript Log Detail'
            ,'Warn'=>'Warnings'
            ,'Info'=>'Informational'
            ,'Error'=>'Errors'
            ,'Time'=>'Time start/end (requires firebug)'
        );
        foreach($loptions as $loption=>$description) {
            $input = html('input');
            $input->hp['type'] = 'checkbox';
            $input->hp['command']= $loption;
            $input->code['click'] = <<<JS
            function(input) {
                var command = x6.p(input,'command');
                var checked = input.checked;
                if(checked) {
                    x6.console['enable'+command] = true;
                    createCookie('log_'+command,1);
                }
                else {
                    x6.console['enable'+command] = false;
                    eraseCookie('log_'+command);
                }
            }
JS;
            if(arr($_COOKIE,'log_'.$loption,0)==1) {
                $input->hp['checked'] = 'checked';
            }
            $tab2->addChild($input);
            $tab2->h('span',$description);
            $tab2->br();
            if($loption=='Server') {
                $tab2->br();
            }
            
        }
        # --------------------------------------------------------------
        # End of the line
        # --------------------------------------------------------------
        $top->render();
    }
}
?>

