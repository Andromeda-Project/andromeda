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
        href="clib/x6skin.<?=$cookie?>.css" />
        
<?php include 'androHTMLHead.php' ?>
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
<?php include 'androHTMLFoot.php' ?>
</body>
</html>
