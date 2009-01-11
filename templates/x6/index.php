<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
# Load the style sheet that matches the cookie.
# If none, pick win2k.gray.1024
$cookie = arr($_COOKIE,'x6skin','Default.Gray.1024');
?>
<link    rel="stylesheet"
       x6skin="Y"
        href="templates/x6/skins/x6skin.<?=$cookie?>.css" />
<?php cssInclude('clib/jwysiwyg/jquery.wysiwyg.css'); ?>
<?php include 'androHTMLHead.php' ?>

<?php
# KFD 12/30/08, A cookie-controlled script to load firebug lite
$loadFBLite = arr($_COOKIE,'log_FBLite',0);
if($loadFBLite == 1) {
    ?>
    <script type='text/javascript' 
        src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'
           ></script>        
    <?php
}
?>
</head>
<body>
<div class="x6menu">
    <?php include 'x6menuTop.php' ?>
</div>
<div style="clear: both"></div>
<div class="x6body">
    <div class="x6main">
    <?=mosMainBody()?>
    </div>
</div>

<div class="x6footer">
  <span style="float: left">Produced by Secure Data Software</span>
  <span style="float: right">Server time: <?=date('h:i:s a',time())?></span>
</div>
<?php jsInclude( 'clib/jwysiwyg/jquery.wysiwyg.pack.js' ); ?>
<?php include 'androHTMLFoot.php' ?>
</body>
</html>
