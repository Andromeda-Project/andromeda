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

    jsInclude('clib/jquery-1.8.3.min.js','JQuery is distributed under the GPL
          license, written by a team of programmers led by John Resig,
          more info at http://www.jquery.com'
          ,'Y'
    );








$xpath = arr($_COOKIE,'altjs','clib/');

$configJS = trim(configGet('js_css_debug' ,'N'));

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
