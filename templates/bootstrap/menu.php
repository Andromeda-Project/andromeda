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
$ulpads->hp['class'] = 'nav';
$first = 0;
$menuLvl = 0;
foreach($menus as $menuid=>$menuinfo) {
    $pad = $ulpads->h('li');
    $pad->hp['class'] = 'dropdown';
    $a = $pad->h('a',$menuinfo['description']);
    $a->hp['class'] = 'dropdown-toggle';
    $a->hp['data-toggle'] = 'dropdown';
    $a->hp['href'] = '#';
    

    $ul  = $pad->h('ul');
    $ul->hp['class'] = 'dropdown-menu';
    if (!empty($menuinfo['items'])) {
		$c = $a->h('b');
		$c->hp['class'] = 'caret';
	}
    foreach($menuinfo['items'] as $page=>$pageinfo) {
        $pd = $pageinfo['description'];
        $li = $ul->h('li');
        $a2  = html('a',$li,$pd);
        $a2->hp['href'] = "?gp_page=$page&x2=1";
    }
    $menuLvl++;
}

#$li = $ulpads->html('li');
//$a  = $li->a('Logout','?st2logout=1');
#$a->hp['style'] = 'color: white; border-width: 0';

$ulpads->render();

?>
