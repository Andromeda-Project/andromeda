<?php
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
    $count = 0;
    foreach($menuinfo['items'] as $page=>$pageinfo) {
        $count++;
        
        # Special hardcoded hack for x6 to remove some items
        if(vgfGet('x6')) {
            if($page == 'apppub') continue;
            if($page == 'userssimple') continue;
        }
        
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
            $href = "?x6page=$page&x6module=$menuid";
        }
        $li->hp['onclick'] = "window.location='$href'";
        if(arr($pageinfo,'spaceafter','N')=='Y') {
            if($count <> count($menuinfo['items'])) {
                $li=$ul->h('li');
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
