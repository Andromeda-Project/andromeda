<?php
// Output the command to load javascript files
jsOutput();

// Handle the first focus
//echo vgfGet('HTML_focus');
if(vgfGet('HTML_focus')<>'') {
    $f = vgfGet('HTML_focus');
    ElementAdd('jqueryDocumentReady','$("#'.$f.'").focus()');
}


// Output any JQuery document ready stuff
$jqueryDocumentReady = ElementImplode('jqueryDocumentReady');
if($jqueryDocumentReady<>'') {
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
    <?=$jqueryDocumentReady?>
    });
    </script>
    <?php
}

$scriptend = ElementImplode('scriptend');
if($scriptend<>'') {
    ?>
    <script type="text/javascript">
    <?=$scriptend?>
    </script>
    <?php
}

# -------------------------------------------------------------
# Put out some invisible divs that are used for modals, 
# popups and so forth
# -------------------------------------------------------------
?>
<div style="display:none" id="idiv1" class="idiv1" onclick="x4.helpClear()">
</div>
</div>         
<div  style="display:none" id="idiv2" class="idiv2"> 
  <table width="100%">
    <tr>
    <td align="left"><h1>Help System</h1></td>
    <td align="right" style="padding-right: 15px"><h3><a href="javascript:x4.helpClear()">Close</a></h3>
  </table>
  <br/>
  <div id="idiv2content" class="idiv2content" 
    style="margin: 10px; overflow-y: scroll;">
    <?=vgfGet('htmlHelp')?>
  </div>
</div>
           
