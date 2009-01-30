<?php defined( '_VALID_MOS' ) 
    or die( 'Direct Access to this location is not allowed.' );
?>
<?php 
# ========================================================
# The templating system expects these functions
# ========================================================
function tmpCountModules($module) { return true; }
function tmpLoadModules($module) {
    if($module=='footer') {
        echo configGet('notesfooter');
    }
}
function loadMenu() {
    if (LoggedIn()) {
        $dir = dirname(__FILE__);
        include $dir."/pixel2menu.php";        
    }
}
# ========================================================
# Get some cookie stuff taken care of before header
# ========================================================
$template = $mainframe->getTemplate();

$app   = $GLOBALS['AG']['application'];
if(gpExists('p2c')) {
    $color = gp('p2c');
    setCookie($app."_color",hx($color),strtotime("+5 years",time()));
}
else {
    $color = a($_REQUEST,$app."_color",'blue');
}
if(gpExists('p2s')) {
    $size  = gp('p2s');
    setCookie($app."_size",hx($size),strtotime("+5 years",time()));
}
else {
    $size  = a($_REQUEST,$app."_size",'1024');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php

cssInclude("templates/$template/css/pixel2.css");
cssInclude("templates/$template/css/pixel2-$size.css");
cssInclude("templates/$template/css/pixel2-$color.css");
include('androHTMLHead.php');
?>
</head>
<body>
<center>
    <div class="template" style="text-align: left">
        <?php if (mosCountModules('header')) { ?>
            <div class="templateheader"><?php echo mosLoadModules('header')?>
            <div class="templateheader-top">
                <img src="templates/<?php echo $template?>/images/logo.gif">
                <div class='sizepicker'>
                <a href="?p2s=800">800</a>&nbsp;
                <a href="?p2s=1024">1024</a>&nbsp;
                <a href="?p2s=1400">1400</a>&nbsp;
                </div>
                <div class='colorpicker'>
                <a href="?p2c=blue"   class="cp_blue">&nbsp;&nbsp;</a>&nbsp;&nbsp;
                <a href="?p2c=orange" class="cp_orange">&nbsp;&nbsp;</a>&nbsp;&nbsp;
                <a href="?p2c=green"  class="cp_green">&nbsp;&nbsp;</a>&nbsp;&nbsp;
                <a href="?p2c=red"    class="cp_red">&nbsp;&nbsp;</a>&nbsp;&nbsp;
                <a href="?p2c=gs"   class="cp_gs">&nbsp;&nbsp;</a>&nbsp;&nbsp;
                </div>
            </div>
            </div>
        <?php } ?>
        <?php if (mosCountModules('menu')) { ?>
            <div class="templatemenu"><?php echo loadMenu()?>
                <?php echo fwModuleMenuRight()?>
            </div>
            <div style="clear: both"></div>
        <?php } ?>
        <?php if (mosCountModules('shortcuts')) { ?>
            <div class="templateshortcuts"><?php echo mosLoadModules('shortcuts')?>
            </div>
        <?php } ?>
    
        <!-- the explicit cellspacing is required for IE -->
        <table class="template" cellspacing=0 cellpadding=0>
            <tr>
                <?php if (mosCountModules('left')) { ?>
                    <td class="auxiliary templateleft">
                        <?php echo mosLoadModules('left')?>
                    </td>
                <?php } ?>
            
                <td class="templatemain">
                <?php echo mosMainBody()?>
                </td>
            </tr>
        </table>        
    
        <?php if (mosCountModules('footer')) { ?>
            <div class="templatefooter"><?php echo mosLoadModules('footer')?>
            </div>
        <?php } ?>
    </div>
</center>
<?php include('androHTMLFoot.php'); ?>
</body>
</html>
