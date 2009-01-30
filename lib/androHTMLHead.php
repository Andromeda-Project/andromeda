<?php
$deprecated = configGet('deprecated','Y');
$x6         = vgfGet('x6',false);
# =====================================================================
# 
# Output the title
#
# =====================================================================
echo "\n<title>".vgfGet('pageTitle')."</title>\n";

# =====================================================================
# 
# The jQuery libraries always come first
#
#  NOTICE!  Andromeda has its own mini-fication system, 
#           so do not fear all of these individual files!
#
# =====================================================================
# The jQuery library always comes first
if(!$x6) {
    jsInclude('clib/jquery-1.2.6.pack.js','JQuery is distributed under the GPL
          license, written by a team of programmers led by John Resig,
          more info at http://www.jquery.com'
          ,'Y'
    );
}
else {
    jsInclude('clib/jquery-1.3.min.js','JQuery is distributed under the GPL
          license, written by a team of programmers led by John Resig,
          more info at http://www.jquery.com'
          ,'Y'
    );
}

# LOADS SPECIFIC TO x6
if($x6) {
    # NOPE.  Don't work anymore in jquery 1.2...
    #jsInclude('clib/jquery-fieldselection.js');
    
    # Nifty, lets us find out style sheet rules
    # Not so nifty, does not extract rules like 'padding-top',
    #   has no getter, only a setter.
    #jsInclude('clib/jquery.rule-1.0.1-min.js');
    
    cssInclude('clib/jquery.wysiwyg.css');
    jsInclude( 'clib/jquery.wysiwyg.pack.js' );
    jsInclude( 'clib/jquery.blockui.js' );
}

# EXPERIMENTAL: jquery ui full boat
#   This seems to be the annointed king of jQuery UI
#   facilities, so we may as well load the entire thing
#   and have it available for experiments
if(!$x6) {
    jsInclude('clib/jquery-ui-1.5.2.js');
}
else {
    # X6 will get only the jquery-ui elements we are actually
    # using, which at the moment is only tabs. 
    jsInclude('clib/jquery-ui-andro-1.6rc5.min.js');
    #jsInclude('clib/jquery-ui-core-1.5.3.min.js');
}
if(vgfGet('x6')) {
    #cssInclude('clib/jquery-ui-themeroller.css');
}

# 1.6b messes up drag-n-drop 
#sInclude('clib/jquery-ui-personalized-1.6b.packed.js');

# EXPERIMENTAL.  Added KFD 8/2/08 to scroll inside
#                of androSelect dropdown.
if(!$x6) {
    jsInclude('clib/jquery.scrollTo.js');
}

# JQUERY MODALS
# Status: must keep, at least one commercial customer
#         using it already.
if(!$x6) {
    jsInclude('clib/jqModal.js','jqModal was written by Brice Burgess
     and is distributed under the MIT license.  His website is
     http://dev.iceburg.net/jquery/jqModal/');
    cssInclude('clib/jqModal.css');
}


# SCROLLABLE TABLES
# Status: unknown.  It's kind of ok, but it seems that it might
#         be simpler just to create table bodies with fixed 
#         heights and overflow: scroll
if(!$x6) {
    if(gpExists('x4Page')) {
        jsInclude('clib/webtoolkit.jscrollable.js','Scrollable table is
            available at www.webtoolkit.info');
        jsInclude('clib/webtoolkit.scrollabletable.js','Scrollable table is
            available at www.webtoolkit.info');
    }
}

# Time Entry.  A very nifty plugin that makes time entry
#              inputs easy to work with
if(!$x6) {
    cssInclude('clib/jquery.timeentry.css');
    jsInclude('clib/jquery.timeentry.js');
}
// Date entry with their over-engineered downloads

# Date Manipulation.  This is a combo data input system
#      and library for date manipulation.
if(!$x6) {
    jsInclude('clib/jquery.date_input.js');
    cssInclude('clib/date_input.css');
}

# Another date library, extremely agile
# with date manipulation
# Strike 1: 8/21/08, This version cannot be trusted to give the
#                    correct day of the week.
if(!$x6) {
    jsInclude('clib/jquery.dates.js');
}


# Jquery Tooltip
#  Don't need dimensions, we have jquery 1.2.6 now
#jsInclude("clib/jquery.dimensions.js");
if(!$x6) {
    jsInclude("clib/jquery.tooltip.js");
    cssInclude("clib/jquery.tooltip.css");
}


# =====================================================================
#
# 
# The base and universal Andromeda files come second 
#
#
# =====================================================================
# KFD 12/23/08, see if they set a cookie for an
#               alternate location.
$xpath = arr($_COOKIE,'altjs','clib/');
if(!$x6) {
    jsInclude($xpath.'androLib.js');
}
if($deprecated && !$x6) {
    jsInclude('clib/androLibDeprecated.js');
}

// The x2 css file is loaded, unless there is an x4 page
if(gp('x4Page')=='' && gp('gp_page')<>'' && !$x6) {
    cssInclude('templates/'.$mainframe->getTemplate().'/css/x2.css');
}
// ..and this (misnamed) holds info for dynamic select
//cssInclude('clib/raxlib.css');
$configJS = trim(configGet('js_css_debug' ,'N'));
if ( $configJS == 'Y' ) {
    cssInclude( 'clib/debug.css' );
}
// DHTML Goodies calendar
if(vgfGet('suppress_goodies_calendar')!==true && !$x6) { 
    cssInclude('clib/dhtmlgoodies_calendar.css');
    jsInclude('clib/dhtmlgoodies_calendar.js');
}

// Positioning styles
if(vgfGet('suppress_andromeda_css')!==true || vgfGet('x4')===true) {
    if(!$x6) {
        cssInclude("clib/andromeda.css");
    }
}




# =====================================================================
#
# 
# Bringing up the rear are the x4 libraries 
#
#
# =====================================================================
if(gpExists('x4Page') && !vgfGet('x6')) {
    jsInclude('clib/androX4.js');
}

# =====================================================================
#
# 
# Output styles and script that must go at top 
#
# DEPRECATED.  The suppressDeprecated will remove this  
#
# =====================================================================
if($deprecated=='Y') {
    $styles = ElementImplode('styles');
    if($styles<>'') {
        ?>
        <style type="text/css">
        <?php echo $styles?>
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
        <?php echo ElementOut("script");?>
        </script>
        <?php
    }
}

# =====================================================================
#
# Thus it was written in the book of YSlow!
# Output your CSS in the header, so here it is
# Output your JS in the footer, so that is in androHTMLFoot.php 
#
#
# =====================================================================
cssOutput();
?>
