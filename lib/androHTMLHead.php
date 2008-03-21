<?php
echo "\n<title>".vgfGet('pageTitle')."</title>\n";

jsInclude('clib/jquery-1.2.3.js');


ob_start();
ElementOut('styles');
$styles = ob_get_clean();
if($styles<>'') {
    ?>
    <style type="text/css">
    <?=$styles?>
    </style>
    <?php
}
ob_start();
ElementOut('script');
$script = ob_get_clean();
if($script<>'') {
    ?>
    <script language="javascript" type="text/javascript">
    /* Script generated specifically for a page */
    <?=ElementOut("script");?>
    </script>
    <?php
}

// Standard Andromeda Libraries
jsInclude('clib/raxlib.js');

// DHTML Goodies calendar
if(vgfGet('suppress_goodies_calendar')!==true) { 
    cssInclude('clib/dhtmlgoodies_calendar.css');
    jsInclude('clib/dhtmlgoodies_calendar.js');
}

// Jquery Tooltip
jsInclude("clib/jquery.dimensions.js");
jsInclude("clib/jquery.tooltip.js");
cssInclude("clib/jquery.tooltip.css");
// DHTML Goodies tooltip
#if(vgfGet('suppress_goodies_tooltip')!==true) {
#    cssInclude('clib/dhtml-tt/css/form-field-tooltip.css');
#    jsInclude('clib/dhtml-tt/js/rounded-corners.js');
#    jsInclude('clib/dhtml-tt/js/form-field-tooltip.js');
#}

// Positioning styles
if(vgfGet('suppress_andromeda_css')!==true || vgfGet('x4')===true) {
    cssInclude("clib/andromeda.css");
}

if(! (vgfGet('x4')===true && LoggedIn()) ) { 
    cssInclude('templates/'.$mainframe->getTemplate().'/css/x2.css');
} 


// If debug mode is on, this will output all of the CSS files as one
cssOutput();
?>
