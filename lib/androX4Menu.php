<?php
class x4Menu extends androX4 {
    
    # ===================================================================
    #
    # Major Area 0: Put out the menu
    #
    # ===================================================================
    function mainLayout(&$container) {
        # load the javascript        
        jsInclude("clib/androX4Menu.js");

        # Set focus if requested
        if( ($focus=gp('x4Focus'))!='') {
            x4Script("$('#x4Menu')[0].lastFocusId = 'x4menu_$focus'");    
        }

        # Other miscellaneous commands
        vgfSet('suppress_hidden',true);
        //vgfSet('show_menu',false);
        
        # Fetch some text
        $h1 = configGet("x4menutitle","Extended Desktop Menu");
        $text = configGet("x4menutext","");
        
        ob_start();
        ?>
        <div id="x4Menu" class="x4Pane x4Menu">
        <center>
        <h1><?php echo $h1?></h1>
        <?php echo $text?>
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
            $td->hp['class'] = 'style1';
            
            $h3 = html('h3',$td);
            $h3->setHtml(($letters[$col]).') '.$modinfo['description']);
            
            $row = 0;
            foreach($modinfo['items'] as $page=>$info) {
                // Add the link
                $a = html('a',$td);
                $a->hp['id']='x4menu_'. $page;
                $a->hp['href']="?x4Return=menu&x4Page=$page";
                $a->setHTML($row.') '.$info['description']);
                $a->hp['onkeyup']  = "return \$a.byId('x4Menu').x4KeyUp(event,$col,$row)";
                $a->hp['onkeydown']  = "return false;";
                $a->hp['onmouseover']= '$(this).focus()';
                $a->hp['onfocus']    = "this.className = 'x4MenuOn'";
                $a->hp['onblur']     = "this.className = ''";
                $grid[$col][$row]    = 'x4menu_'.$page;
                $row++;

                // Add a BR
                html('br',$td);
            }
            $col++;
        }
        $table->render();
        echo "</div>";
        
        # put out the grid and set the HTML
        x4data('grid',$grid);
        $container->setHTML(ob_get_clean());
    }
}
?>
