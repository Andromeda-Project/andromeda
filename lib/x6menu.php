<?php
class x6menu extends androX6 {
    function x6main() {
        $top = html('div');
        $top->hp['style'] = 'text-align: center;';
        $top->addClass('fadein');
        $top->h('h1',configGet('x4menutitle'));
        
        # Get some basic dimensions we will use to build the
        # menu system.  Our basic metric is 10% of the 
        # screen width, and then 10% of that.
        $pad0   = x6CssDefine('pad0');
        $insidewidth = x6CssDefine('insidewidth');
        $width1 = intval($insidewidth / 10);
        $widthl = $width1 * 3;
        $widthr = $width1 * 3;
        $width1b= intval($width1 / 2);
        $width2 = intval($width1 / 10);
        
        $outer = $top->h('div');
        $outer->hp['id'] = 'x6menu_outer';
        $outer->hp['xWidth1']  = $width1;
        $outer->hp['xWidth1b'] = $width1b;
        $outer->hp['xWidth2']  = $width2;
        $outer->hp['style'] 
            = "padding: {$width1b}px {$width1}px;
               text-align: left;";

        # Calculate height of inner, this forces height of outer
        $iHeight = x6cssDefine('insideheight');
        $iHeight-= x6cssHeight('h1');
        $iHeight-= $width1b * 2;
        
        $inner=$outer->h('div');
        $inner->hp['id'] = 'x6menu_inner';
        $inner->hp['style'] 
            =" border: 1px solid ".x6cssDefine('bgcdark').";
               height: {$iHeight}px;
               padding:{$width2}px";
               
        # calculate height of left;
        $lHeight = $iHeight - $width2;
        $leftDiv = $inner->h('div');
        $leftDiv->hp['id']    = 'x6menu_left';
        $leftDiv->hp['style'] = 
            "background-color: ".x6cssDefine('bgcdark').";
             float: left;
             height: {$lHeight}px;
             width: {$widthl}px";
        $rightDiv = $inner->h('div');
        $rightDiv->hp['id']    = 'x6menu_right';
        $rightDiv->hp['style'] = 
            "position: relative; float: left; width: {$widthr}px;";
        
        $AGMENU = SessionGet('AGMENU');
        $countLeft = 1;
        foreach($AGMENU as $menuid=>$menuinfo) {
            if(count(arr($menuinfo,'items',array()))==0) continue;
            $h2 = $leftDiv->h('div',$countLeft.": ".$menuinfo['description']);
            $h2->hp['xKey'] = $countLeft;
            $countLeft++;
            $h2->hp['id'] = 'module_'.$menuid;
            $h2->hp['xMenuId'] = $menuid;
            $h2->hp['style']   = "padding: {$width2}px";
            $h2->hp['onclick'] =
                "x6.byId('x6menu_outer').clicked('module_$menuid');";
            $h2->hp['onmouseover'] = "this.style.textDecoration='underline'";
            $h2->hp['onmouseout']  = "this.style.textDecoration=''";
            
            $idLeft = $width2 * 2;
            $itemsDiv = $rightDiv->h('div');
            $itemsDiv->hp['id'] = 'items_'.$menuid;
            $itemsDiv->hp['style'] = 
            "position: absolute; top: 0; left: {$idLeft}px; display: none";
            $countRight = 65;
            foreach($menuinfo['items'] as $page=>$pageinfo) {
                # Special hardcoded hack for x6 to remove some items
                if($page == 'apppub') continue;
                if($page == 'userssimple') continue;
                
                $pd = strtolower(chr($countRight))
                    .': '.$pageinfo['description'];

                $a = $itemsDiv->h('div',$pd);
                $a->hp['xKey'] = strtolower(chr($countRight));
                $a->hp['xactive'] ='N';
                $countRight++;
                $a->hp['onmouseover'] = 
                    "$('#x6menu_right .hilight').removeClass('hilight');
                     $(this).addClass('hilight')";
                 
                # DUPLICATE CODE ALERT.  THIS CODE IS ALSO IN
                #   TEMPLATES/X6/X6MENUTOP.PHP
                # KFD 2/20/09 Sourceforge 2616802
                if($pageinfo['uix2'] == 'Y') {
                    $href = "?gp_page=$page&amp;x2=1";
                }
                else {
                    $href = "?x6page=$page&amp;x6module=$menuid";
                }
                $a->hp['onclick'] = "window.location='$href'";
                $a->hp['id'] = 'page_'.$page;
                if(arr($pageinfo,'spaceafter','N')=='Y') {
                    $itemsDiv->h('hr');
                }
            }
        }
        $top->render();
    }
    
    function x6Script() {
        ?>
        <script>
        var id   = 'x6menu_outer';
        var self = x6.byId(id);
        
        self.clicked = function(id) {
            // First work out if we are changing, otherwise
            // nothing to do
            var moduleNow = $("#x6menu_left div.hilight");
            var idNow = moduleNow.length > 0 ? moduleNow[0].id : '';
            if(idNow == id) {
                return;
            }
            
            // Very first thing, deactivate right-side so it
            // won't get keystrokes.
            $('#x6menu_right div[xActive=Y]').attr('xActive','N');
            
            // Notice we do the fade out and then put the fadein
            // below.  This causes them to overlap each other,
            // which I kind of like.
            if(moduleNow.length > 0) {
                var moduleId = x6.p(moduleNow[0],'xMenuId');
                $(moduleNow).removeClass('hilight');
                $('#items_'+moduleId).fadeOut(300);
            }
            var moduleId = x6.p(x6.byId(id),'xMenuId');
            $('#'+id).addClass('hilight');
            var crString = '#items_'+moduleId+" div:first";
            this.clickedRight($(crString)[0].id);
            $('#items_'+moduleId).fadeIn(300,
                function() {
                    $('#items_'+moduleId+' > div').attr('xActive','Y');
                }
            );
        }
        self.clickedRight = function(id) {
            $('#x6menu_right div div.hilight').removeClass('hilight');
            $('#x6menu_right div div#'+id).addClass('hilight');
            //$('#x6menu_right div div.hilight(+id+' a').attr('xactive','Y');
        }
        
        x6events.subscribeToEvent('key_ShiftDownArrow',id);
        self.receiveEvent_key_ShiftDownArrow = function() {
            var moduleNext = $("#x6menu_left div.hilight").next();
            if(moduleNext.length == 1) {
                this.clicked(moduleNext[0].id);
            }
            return false;
        }
        x6events.subscribeToEvent('key_ShiftUpArrow',id);
        self.receiveEvent_key_ShiftUpArrow = function() {
            var modulePrev = $("#x6menu_left div.hilight").prev();
            if(modulePrev.length == 1) {
                this.clicked(modulePrev[0].id);
            }
            return false;
        }
        x6events.subscribeToEvent('key_DownArrow',id);
        self.receiveEvent_key_DownArrow = function() {
            var menuNext = $("#x6menu_right div div.hilight").nextAll();
            for(var x = 0;x < menuNext.length; x++) {
                if(menuNext[x].tagName == 'DIV') {
                    this.clickedRight(menuNext[x].id);
                    break;
                }
            }
            return false;
        }
        x6events.subscribeToEvent('key_UpArrow',id);
        self.receiveEvent_key_UpArrow = function() {
            var menuPrev = $("#x6menu_right div div.hilight").prevAll();
            for(var x = menuPrev.length-1;x >= 0; x--) {
                if(menuPrev[x].tagName == 'DIV') {
                    this.clickedRight(menuPrev[x].id);
                }
            }
            return false;
        }
        x6events.subscribeToEvent('key_Enter',id);
        self.receiveEvent_key_Enter = function() {
            var menuPrev = $("#x6menu_right div div.hilight").click();
        }
        
        // set up all of the numbers
        for(var x=1;x<=9;x++) {
            x6events.subscribeToEvent('key_'+x,id);
            self['receiveEvent_key_'+x] = function(keyLabel) {
                $('#x6menu_left div[xkey='+keyLabel+']').click();
            }
        }
        
        // set up all of the letters
        for(var x=65;x<=90;x++) {
            var letter = String.fromCharCode(x).toLowerCase();
            x6events.subscribeToEvent('key_'+letter,id);
            self['receiveEvent_key_'+letter] = function(letter) {
                letter = letter.toLowerCase();
                $('#x6menu_right div[xActive=Y][xkey='+letter+']').click();
            }
        }
        
        // Initialize the first one
        var x6page_prior = '<?php echo gp('x6page_prior')?>';
        var x6mod_prior  = '<?php echo gp('x6mod_prior')?>';
        if(x6mod_prior=='') {
            $("#x6menu_left div:first").click();
            //self.clicked($("#x6menu_left div:first")[0].id);
        }
        else {
            $('#module_'+x6mod_prior).click();
            $('#page_'+x6page_prior).mouseover();
        }
        
        
        
        </script>
        <?php
    }
}
?>
