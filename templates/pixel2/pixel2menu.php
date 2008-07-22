<?php
# ==================================================================
# DO NOT ALLOW DIRECT ACCESS
# ==================================================================
defined( '_VALID_MOS' ) 
    or die( 'Direct Access to this location is not allowed.' );

# ==================================================================
# AREA 1: Render the menu.  Note we directly output it
# ==================================================================
$menus=SessionGET("AGMENU");
$ulpads = html('ul');
$first = 0;
foreach($menus as $menuid=>$menuinfo) {
    $pad = $ulpads->h('li');
    $span= $pad->h('span',$menuinfo['description']);
    $span->hp['onmouseover'] = "pixel2ShowMenu('$menuid')";
    $span->hp['onmouseout']  = "window.pixel2pad=false;pixel2HideMenu('$menuid')";
    $span->hp['id'] = 'pixel2_pad_'.$menuid;

    $ul  = $pad->h('ul');
    $ul->hp['id'] = 'pixel2_menu_'.$menuid;
    $ul->addClass('dropdown');
    $ul->hp['style'] = 'display:none;';
    $ul->hp['onmouseover'] = "pixel2ShowMenu('$menuid')";
    $ul->hp['onmouseout']  = "window.pixel2pad=false;pixel2HideMenu('$menuid')";
    foreach($menuinfo['items'] as $page=>$pageinfo) {
        $pd = $pageinfo['description'];
        $li = $ul->h('li');
        $a  = html('a',$li,$pd);
        if($pageinfo['uix2'] == 'Y') {
            $a->hp['href'] = "?gp_page=$page&x2=1";
        }
        else {
            $a->hp['href'] = "?x4Page=$page";
        }
    }
}

#$li = $ulpads->html('li');
#$a  = $li->a('Logout','?st2logout=1');
#$a->hp['style'] = 'color: white; border-width: 0';

$ulpads->render();

# ==================================================================
# AREA 2: Some script
# ==================================================================
?>
<script>
pixel2pad = false;
function pixel2ShowMenu(menuid) {
    var obj = $a.byId('pixel2_menu_'+menuid);
    $('#pixel2_pad_'+menuid).addClass('selected');
    if($a.p(obj,'zHeight','')=='') {
        obj.style.opacity=0;
        obj.style.display='block';
        obj.zHeight = $(obj).height();
        obj.style.height = '0px';
        obj.style.opacity = 1;
    }
    if(window.pixel2pad!=menuid) {
        // mark this as current and slide it down
        window.pixel2pad = menuid;
        $(obj)
            .stop()
            .css('opacity',1)
            .animate({height: obj.zHeight},pixel2Speed(obj,true));
    }
}

function pixel2HideMenu(menuid) {
    setTimeout(function() {
        if(window.pixel2pad != menuid) {
            $('#pixel2_pad_'+menuid).removeClass('selected');
            var obj = $a.byId('pixel2_menu_'+menuid);
            $(obj).stop().animate(
                {height: 0}
                ,pixel2Speed(obj,false)
                ,null
                ,function() {this.style.opacity=0}
             );
        }
    },300);
}

function pixel2Speed(obj,goDown) {
    if(goDown) 
        var distance = obj.zHeight - $(obj).height();
    else
        var distance = $(obj).height();
        
    return distance;        
}
</script>
