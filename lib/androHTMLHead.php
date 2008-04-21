<?php
echo "\n<title>".vgfGet('pageTitle')."</title>\n";

// We now include JQuery all of the time
jsInclude('clib/jquery-1.2.3.js','JQuery is distributed under the GPL
      license, written by a team of programmers, more info at
      http://www.jquery.com'
);

// This is the old CSS library for x2
cssInclude('templates/'.$mainframe->getTemplate().'/css/x2.css');
// ..and this (misnamed) holds info for dynamic select
cssInclude('clib/raxlib.css');


// The new css library for x4 and the new js library for x4
if(gpExists('x4Page')) {
    cssInclude('clib/x4.css');
    jsInclude('clib/androX4.js');
}

// Try out the ui.datepicker for JQuery
cssInclude('clib/ui.datepicker.css');
jsInclude('clib/ui.datepicker.js');

// Another jquery add-on: key  navigation 
//jsInclude('clib/keynav.js');
// Yet another jquery add-on: blocking the UI, useful when calling an alert
jsInclude('clib/jqModal.js','jqModal was written by Brice Burgess
     and is distributed under the MIT license.  His website is
     http://dev.iceburg.net/jquery/jqModal/');
cssInclude('clib/jqModal.css');


$styles = ElementImplode('styles');
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
jsInclude('clib/androLib.js');

// DHTML Goodies calendar
if(vgfGet('suppress_goodies_calendar')!==true) { 
    cssInclude('clib/dhtmlgoodies_calendar.css');
    jsInclude('clib/dhtmlgoodies_calendar.js');
}

// Jquery Tooltip
jsInclude("clib/jquery.dimensions.js");
jsInclude("clib/jquery.tooltip.js");
cssInclude("clib/jquery.tooltip.css");

// Positioning styles
if(vgfGet('suppress_andromeda_css')!==true || vgfGet('x4')===true) {
    cssInclude("clib/andromeda.css");
}



// If debug mode is off, this will output all of the CSS files as one
cssOutput();
?>
