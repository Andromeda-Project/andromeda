<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>x4 loading, please wait...</title>
<link href="<?='/'.tmpPathInsert().'templates/x4/x4'.gp('gpt').'.css'?>" rel="stylesheet" type="text/css" />
<!-- ------------------------------------------------- --
     Any code embedded directly in this file is 
     specific to this template, not to the framework,
     so it does not belong in the x4.js file.
  -- ------------------------------------------------- -->
<script type="text/javascript">
function templateBodyResize() {
    if( typeof( window.innerWidth ) == 'number' ) {
        //Non-IE
        var myWidth = window.innerWidth;
        var myHeight = window.innerHeight;

    } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
        //IE 6+ in 'standards compliant mode'
        var myWidth = document.documentElement.clientWidth;
        var myHeight = document.documentElement.clientHeight;

    } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
        //IE 4 compatible
        var myWidth = document.body.clientWidth;
        var myHeight = document.body.clientHeight;
    }
    var x4mc = document.getElementById('x4MainContent');
    var x4vi = document.getElementById('x4VerticalInformation');
    x4mc.style.height = (myHeight - x4vi.offsetHeight) + 'px';
}
</script>
</head>
<body onresize="templateBodyResize()"
        onload="templateBodyResize();x4Boot()"
      >
<div id="x4VerticalInformation"></div>
<div id="x4CharacterInformation"></div>
<div id="x4ActionBar" class="scheme1">
    <span class="x4BarGroupLeft">
        <a    href="javascript:void(0)" 
           onclick="window.open('index.php?gp_page=x4init')"
           onmouseover="this.className='reverse'"
           onmouseout="this.className=''"
                id="object_for_f6">F6:New Window</a>
    </span>
    <span class="x4BarGroupRight">
        <a    href="javascript:void(0)" 
           onclick="window.location='?st2logout=1'"
           onmouseover="this.className='reverse'"
           onmouseout="this.className=''"
                id="object_for_f2">F2:Logout</a>&nbsp;&nbsp;
        <a    href="javascript:void(0)" 
           onclick="window.close()"
           onmouseover="this.className='reverse'"
           onmouseout="this.className=''"
                id="object_for_f4">F4:Close</a>
    </span>
</div>
<div id="x4MainContent" class="scheme0">
    <div id="andromeda_main_content">
    </div>
</div>
<div id="x4StatusBar"  class="scheme2">
    <span id="statusLeft" class="x4BarGroupLeft">
    </span>
    <?php $temp=gp('gpt')=='' ? 'gs' : '' ?>
    <span id="statusRight" class="x4BarGroupRight">
        <a href="?gp_page=x4init&gpt=<?=$temp?>">Switch Template</a>
    </span>
</div>
<?php
/* Watermark: figure out what to do */
if(!vgfGet('nowatermark')) {
    $AGdir = $GLOBALS['AG']['dirs']['root'];
    $file = '';
    if(file_exists("$AGdir/appclib/watermark.gif")) {
        $file = '/'.tmpPathInsert().'appclib/watermark.gif';
    }
    elseif (file_exists("$AGdir/templates/x4/watermark.gif")) {
        $file = '/'.tmpPathInsert().'templates/x4/watermark.gif';
    }
    if($file<>'') {
        ?>
        <script>
        var xyz = document.getElementById('x4MainContent');
        xyz.style.backgroundRepeat  = 'no-repeat';
        xyz.style.backgroundPosition= 'center';
        xyz.style.backgroundImage   = "url(<?=$file?>)";
        
        delete xyz;
        </script>
        <?php
    }
}
/* Watermark: end */
?>
<script type="text/javascript"  src="/<?=tmpPathInsert()?>clib/x4.js"> </script>
<script type="text/javascript"  src="/<?=tmpPathInsert()?>clib/json2.js"> </script>
</body>
</html>
