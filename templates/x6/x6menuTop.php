<?php
# ==================================================================
# DUPLICATE CODE ALERT
#
# THE BASIC LINK GENERATION CODE IN HERE IS ALSO IN X6MENU.PHP
# ==================================================================


# ==================================================================
# DO NOT ALLOW DIRECT ACCESS
# ==================================================================
defined( '_VALID_MOS' ) 
    or die( 'Direct Access to this location is not allowed.' );

# ==================================================================
# AREA 1: Render the menx6.  Note we directly output it
# ==================================================================
$menus=SessionGET("AGMENU",array());
#if(count($menus)==0) return;
$ulpads = html('ul');
$first = 0;
foreach($menus as $menuid=>$menuinfo) {
    if(count($menuinfo['items'])==0) continue;
    
    $pad = $ulpads->h('li');
    $pad->hp['id'] = 'x6menupad_'.$menuid;
    #$pad->addClass('x6menupad');
    
    # IE 6
    $a = $pad->h('a',$menuinfo['description']);
    $a->hp['href'] = "javascript:x6menuclick('$menuid')";
    #$span= $pad->h('span',$menuinfo['description']);
    #$span->hp['onmouseover'] = "x6menumouseover('$menuid')";
    #$span->hp['onclick']     = "x6menuclick('$menuid')";
    $a->hp['onmouseover'] = "x6menumouseover('$menuid')";
    #$a->hp['onclick']     = "x6menuclick('$menuid')";
    #$span->addClass('x6menuspan'); // bogus, just for tracking

    $ul  = $pad->h('ul');
    $ul->hp['id'] = 'x6menu_'.$menuid;
    $ul->addClass('dropdown');
    $ul->hp['style'] = 'display:none;';
    $count = 0;
    foreach($menuinfo['items'] as $page=>$pageinfo) {
        $count++;
        
        # Special hardcoded hack for x6 to remove some items
        if(vgfGet('x6')) {
            if($page == 'apppub') continue;
            if($page == 'userssimple') continue;
        }
        
        
        $pd = $pageinfo['description'];
        $li = $ul->h('li');
        $a = $li->h('a',$pd);
        #$li->hp['onmouseover']="$(this).addClass('selected')";
        #$li->hp['onmouseout'] ="$(this).removeClass('selected')";
        
        if($pageinfo['uix2'] == 'Y') {
            #$a->hp['href'] = "?gp_page=$page&x2=1";
            $href = "?gp_page=$page&amp;x2=1";
        }
        else {
            #$a->hp['href'] = "?x6page=$page";
            $href = "?x6page=$page&x6module=$menuid";
        }
        #$li->hp['onclick'] = "window.location='$href'";
        $a->hp['href'] = "$href";
        if(arr($pageinfo,'spaceafter','N')=='Y') {
            if($count <> count($menuinfo['items'])) {
                $li=$ul->h('li');
                $li->addClass('dropdown');
                $li->hp['style'] = 'cursor: auto';
                $li->hr();
            }
        }
    }
}
$ulpads->render();
if(LoggedIn()) {
    ?>
    <div style="float: right">
      <a href="?x6page=menu">Menu</a>
      &nbsp;&nbsp;
      <a href="?x6page=useroptions">Options</a>
      &nbsp;&nbsp;
      <a href="?st2logout=1">Logout</a>
    </div>
    <?php
}

# ==================================================================
# AREA 2: Some script
# ==================================================================
?>
<script type="text/javascript">
/*  <![CDATA[  */
window.x6menu = false;
// If menu is activated, pick this one and turn off other,
// but if menu is not active do nothing
function x6menumouseover(menuid) {
    // KFD 03/07/09 Sourceforge 2643036
    //              If x6 is not loaded yet, don't do anything
    //              Prevents errors from mouse doodling during
    //              page load
    if(typeof(x6)=='undefined') return;
    if(!window.x6menu) return;
    if(window.x6menu == menuid) return;

    // Turn off the old one
    //var pad = x6.byId('x6menu_'+window.x6menu);
    //pad.style.display = 'none';
    $("#x6menu_"+window.x6menu).css('display','none');
    $('#x6menupad_'+window.x6menu).removeClass('selected');
    
    // Turn on the new one
    window.x6menu = menuid;
    //var pad = x6.byId('x6menu_'+menuid);
    //pad.style.display = '';
    $("#x6menu_"+window.x6menu).css('display','');
    $("#x6menu_"+window.x6menu).css('z-index','1000');
    $('#x6menupad_'+menuid).addClass('selected');
    
    
}

// Clicking a menu pad activates the menu and causes
// mousing-over of other pads to activate those.
function x6menuclick(menuid) {
	// KFD 03/07/09 Sourceforge 2643036
	//              If x6 is not loaded yet, don't do anything
	//              Prevents errors from mouse doodling during
	//              page load
	if(typeof(x6)=='undefined') return;
    // if an old one exists, clear it out
    if(window.x6menu) {
        var pad = x6.byId('x6menu_'+window.x6menu);
        pad.style.display = 'none';
        $('#x6menupad_'+window.x6menu).removeClass('selected');
    }

    // Turn on the new one
    window.x6menu = menuid;
    var pad = x6.byId('x6menu_'+menuid);
    pad.style.display = '';
    $('#x6menupad_'+menuid).addClass('selected');
}

// If the menu is active and we click on anywhere else on the page, we want to deactivate it. 
document.onclick = function(e) {
    // Internet explorer does not pass a parameter to the onClick event. 
    var tg = e ? e.target : window.event.srcElement;
    if(tg != null && !$(tg).hasClass('dropdown') && !$(tg).hasClass('x6menuspan')){
        if(window.x6menu) {
            var pad = x6.byId('x6menu_'+window.x6menu);
            pad.style.display = 'none';
            $('#x6menupad_'+window.x6menu).removeClass('selected')
            window.x6menu = false;
        }
    }
};
/* ]]> */
</script>
