<?php
include('androHTMLFoot.php');
return;
// Script goes out at absolute end, after <html> element is closed
if (vgfGet("HTML_focus")=="") {
   // This is used only in the default Andromeda template, you need to
   // change your menu generation program to create it.  See
   // rt_pixel/rt_suckerfish
   vgfSet('HTML_focus','FIRSTMENU');  
}
if (vgfGet("HTML_focus")<>"") {
   ?>
   <script type="text/javascript">
<!--//--><![CDATA[//><!--
theFocus=function() {
   if ( ob('<?=vgfGet("HTML_focus")?>') != null ) {
   ob('<?=vgfGet("HTML_focus")?>').focus();
   }
}


if (window.attachEvent) window.attachEvent("onload", theFocus);
else {
   if(ob('<?=vgfGet("HTML_focus")?>')) {
      ob('<?=vgfGet("HTML_focus")?>').focus();
   }
}

//--><!]]>
   </script>
   <?php if(vgfGet('suppress_goodies_tooltip')!==true) { ?>
   <script type="text/javascript">
   var tooltipObj = new DHTMLgoodies_formTooltip();
   tooltipObj.setTooltipPosition('right');
   tooltipObj.setPageBgColor('#EEEEEE');
   tooltipObj.setTooltipCornerSize(15);
   tooltipObj.initFormFieldTooltip();
   </script>
   <?php } ?>
   <script type="text/javascript">
   /* output of ElementOut('scriptend') */
   <?=ElementOUt('scriptend',false)?>
   </script>
   
   <?php
}
?>
