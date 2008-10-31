<?php
# ==================================================================
# DO NOT ALLOW DIRECT ACCESS
# ==================================================================
defined( '_VALID_MOS' ) 
    or die( 'Direct Access to this location is not allowed.' );

# ==================================================================
# AREA 1: Render the menu.  Note we directly output it
# ==================================================================
$menus=SessionGET("AGMENU",array());
#if(count($menus)==0) return;
$ulpads = html('ul');
$first = 0;
foreach($menus as $menuid=>$menuinfo) {
    $pad = $ulpads->h('li');
    $pad->hp['id'] = 'x6menupad_'.$menuid;
    $span= $pad->h('span',$menuinfo['description']);
    $span->hp['onmouseover'] = "x6menumouseover('$menuid')";
    $span->hp['onclick']     = "x6menuclick('$menuid')";
    $span->addClass('x6menuspan'); // bogus, just for tracking

    $ul  = $pad->h('ul');
    $ul->hp['id'] = 'x6menu_'.$menuid;
    $ul->addClass('dropdown');
    $ul->hp['style'] = 'display:none;';
    foreach($menuinfo['items'] as $page=>$pageinfo) {
        $pd = $pageinfo['description'];
        $li = $ul->h('li',$pd);
        $li->hp['onmouseover']="this.className='selected'";
        $li->hp['onmouseout'] ="this.className=''";
        #$a  = html('a',$li,$pd);
        #$ul->br();
        if($pageinfo['uix2'] == 'Y') {
            #$a->hp['href'] = "?gp_page=$page&x2=1";
            $href = "?gp_page=$page&amp;x2=1";
        }
        else {
            #$a->hp['href'] = "?x6page=$page";
            $href = "?x6page=$page";
        }
        $li->hp['onclick'] = "window.location='$href'";
    }
}
$ulpads->render();

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
    if(!window.x6menu) return;
    if(window.x6menu == menuid) return;

    // Turn off the old one
    var pad = u.byId('x6menu_'+window.x6menu);
    pad.style.display = 'none';
    $('#x6menupad_'+window.x6menu).removeClass('selected');
    
    // Turn on the new one
    window.x6menu = menuid;
    var pad = u.byId('x6menu_'+menuid);
    pad.style.display = '';
    $('#x6menupad_'+menuid).addClass('selected');
}

// Clicking a menu pad activates the menu and causes
// mousing-over of other pads to activate those.
function x6menuclick(menuid) {
    // if an old one exists, clear it out
    if(window.x6menu) {
        var pad = u.byId('x6menu_'+window.x6menu);
        pad.style.display = 'none';
        $('#x6menupad_'+window.x6menu).removeClass('selected');
    }

    // Turn on the new one
    window.x6menu = menuid;
    var pad = u.byId('x6menu_'+menuid);
    pad.style.display = '';
    $('#x6menupad_'+menuid).addClass('selected');
}

document.onclick = function(e) {
    e = (e) ? e : ((window.event) ? window.event : "");
    var tg = e.target;
    if(!$(tg).hasClass('dropdown') && !$(tg).hasClass('x6menuspan')){    
        if(window.x6menu) {
            var pad = u.byId('x6menu_'+window.x6menu);
            pad.style.display = 'none';
            $('#x6menupad_'+window.x6menu).removeClass('selected')
            window.x6menu = false;
        }
    }
};
/* ]]> */
</script>
