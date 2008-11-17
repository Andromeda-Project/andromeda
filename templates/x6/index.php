<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
# Load the style sheet that matches the cookie.
# If none, pick win2k.gray.1024
$cookie = arr($_COOKIE,'x6skin','win2k.gray.1024');
?>
<link    rel="stylesheet"
       x6skin="Y"
        href="clib/x6skin.<?=$cookie?>.css" />
        
<script type="text/javascript">
function x6ChangeSkin(select) {
    link = document.createElement('link');
    link.setAttribute('rel','stylesheet');
    link.setAttribute('type','text/css');
    link.setAttribute('href','clib/x6skin.'+select.value+'.css');
    link.setAttribute('x6skin','Y');
    link.setAttribute('xname',select.value);
    document.getElementsByTagName("head")[0].appendChild(link);
    $('link[x6skin=Y][xname!='+select.value+']').remove();
    
    // Set the cook
    document.cookie 
        = "x6skin="+select.value+"; expires=12/31/2049 00:00:00;";  
}
</script>
<?php include 'androHTMLHead.php' ?>
</head>
<body>
<div class="x6menu">
    <?php include 'x6menu.php' ?>
    <?php
    # Pull the serialized list of skins and display to user
    $file = fsDirTop().'generated/x6skins.ser.txt';
    $skins = unserialize(file_get_contents($file));
    $select = html('select');
    foreach($skins as $name=>$stats) {
        $option = $select->h('option',$name);
        $option->hp['value'] = $stats;
        # Note that $cookie was defined above 
        if($cookie==$stats) $option->hp['selected'] = 'selected';
    }
    $select->hp['onchange']='x6ChangeSkin(this)';
    ?>
    <span style="float: right"><?=$select->render()?></span>
</div>
<div style="clear: both"></div>
<div class="x6body">
    <!--
    <div class="x6commandBar">
    F2: command <input value="hi" />  
    <a href = "#">Calendar</a>
    <a href = "#">Today</a>
    </div>
    -->
    
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
