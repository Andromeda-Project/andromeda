<?php
class x4Menu extends androX4 {
    
    # ===================================================================
    #
    # Major Area 0: Put out the menu
    #
    # ===================================================================
    function mainLayout(&$container) {
        echo "in the override";
        ob_start();
        ?>
        <div id="x4Menu" class="x4Display">
        <center>
        <h1>Extended Desktop Menu</h1>
        <div style="text-align: left; width: 50%; font-size:1.2em">
        <p>
        This is the Extended Desktop version of the 
        application menu.  If you want to return to "classic" Andromeda,
        <a href="index.php">click here</a>.
        </p>
        <p>
        Extended Desktop is all about speed and responsiveness.
        Try out the arrow keys
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
                #$a->hp['href']="javascript:x4Menu.open('$page')";
                $a->hp['href']="?x4Return=menu&x4Page=$page";
                $a->setHTML($letters[$row].'. '.$info['description']);
                $a->hp['onkeydown']="x4Menu.click(event,$col,$row)";
                $a->hp['onmouseover']='this.focus()';
                $a->hp['onfocus'] = "this.className = 'x4MenuOn'";
                $a->hp['onblur']  = "this.className = ''";
                $grid[$col][$row] = 'x4menu_'.$page;
                $row++;

                // Add a BR
                html('br',$td);
            }
            $col++;
        }
        $table->render();
        echo "</div>";
        $container->setHTML(ob_get_clean());
        
        #
        # <---- End of HTML generation
        #
        #  ----> On to script generation
        ob_start()
        ?>
        <script>
window.x4Menu = {
    divId: '',
    jqid: '',
    lastFocusId: '',
    
    init: function() {
        // Record our div ID, and the jQuery version of it
        this.divId = 'x4Menu';
        this.jqid = '#' + this.divId;
        
        // Capture a public variable generated in PHP
        this.grid = $a.data.grid;
        
        // NOTE: we do not have to put event tracking
        //       onto the menu elements, they arrive from
        //       the web server with events already 
        //       pointing to the x4Menu object.

        // Turn on the focus tracking, then fade in and set focus
        $(this.jqid + " a").focusTrack();
        $(this.jqid).fadeIn(fadeSpeed,function() {
                if(x4Menu.lastFocusId!='') {
                    $('#'+x4Menu.lastFocusId).focus();
                }
                else {
                    $(x4Menu.jqid+" td a:first").focus();
                }
        });
        
        // Hijack the ctrl key for the entire page
        $(".wrapper").keydown(function(event) {
            // If ctrl, look for item with that id
            var letter = $a.charLetter(event.which);
            if(event.ctrlKey) {
                // Copy-n-paste operations are allowed
                if(letter=='x') return true;
                if(letter=='v') return true;
                if(letter=='c') return true;
                
                var x = $("[@accesskey="+letter+"]:visible");
                // count if there is a match
                if(x.length > 0) {
                    x.click();
                }
                event.stopPropagation();
                return false;
            }
        });
    },
    
    click: function(e,col,row) {
        var keyLabel = $a.keyLabel(e);
        if(keyLabel == 'Tab' || keyLabel == 'ShiftTab') {
            e.stopPropagation();
            return false;
        }
        var nextId = false;
        if(keyLabel == 'UpArrow') {
            if(row > 0) nextId = this.grid[col][row-1];
        }
        if(keyLabel == 'DownArrow') {
            var maxRow = this.grid[col].length - 1;
            if(row < maxRow) nextId = this.grid[col][row+1];
        }
        if(keyLabel == 'LeftArrow') {
            if(col > 0) {
                col--;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        if(keyLabel == 'RightArrow') {
            if(col < this.grid.length - 1) {
                col++;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        if(e.keyCode >= 49 && e.keyCode <= 57) {
            if ( (e.keyCode - 48) <= this.grid.length ) { 
                col = e.keyCode - 49;
                if( row > this.grid[col].length - 1) 
                    row = this.grid[col].length - 1;
                nextId = this.grid[col][row];
            }
        }
        if(e.keyCode >= 65 && e.keyCode <= 90) {
            if ( (e.keyCode - 65) <= (this.grid[col].length-1) ) {
                nextId = this.grid[col][e.keyCode - 65];
            }
        }

        if(nextId) {
            $('#'+nextId).focus();
            e.stopPropagation();
            return false;
        }
    },
    
    /*
    open: function(page) {
        // Make menu go away
        $.focusTrackBlur();
        $('#'+this.divId).css('display','none');
        
        // Make new layer
        var divObj = document.createElement('div');
        divObj.id = 'x4divLayer_2';
        divObj.style.height='100%';
        $a.byId('Form1').appendChild(divObj);        
        
        $a.json.init('x4Page',page);
        if($a.json.execute()) {
            $a.json.process(divObj.id);
            x4dd.dd = $a.json.data.dd;
            // Find, initialize and activate the first x4Display
            var rootObj = $(divObj).find(".x4Display")[0];
            this.initDisplay(rootObj,false);
            this.rootObj = rootObj;
            rootObj.activate();
        }
    },
    */
    
    // Called by a page that is ready to exit, says "please
    // get rid of me" and "make yourself displayed again"
    /*
    restore: function() {
        $('#x4divLayer_2').remove();
        $(this.jqid).fadeIn(fadeSpeed,function() {
            $.focusTrackRestore(); 
        });
    },
    */    
}
        </script>
        <?php
        x4Script(ob_get_clean());

        # put out the grid
        x4data('grid',$grid);
        
        # Set focus if requested
        if( ($focus=gp('x4Focus'))!='') {
            x4Script("x4Menu.lastFocusId = 'x4menu_$focus'");    
        }

        # Final miscellaneous commands
        vgfSet('suppress_hidden',true);
        vgfSet('show_menu',false);
        x4Script('x4Menu.init()');
        
    }
}
?>
