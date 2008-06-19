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
<div id="idiv1" 
onclick="x4.helpClear()";
  style="position: absolute;
              top: 0px;
             left: 0px;
           height: 100%;
            width: 100%;
          opacity: 0;
 background-color: black;
          z-index: 9000;
          display: none;">
</div>         
<div id="idiv2" 
  style="position: absolute;
              top: 90px;
             left: 100px;
           height: 475px;
            width: 880px;
 background-color: white;
           border: 3px solid blue;
          z-index: 9001;
          display: none;">
  <table width="100%">
    <tr>
    <td align="left"><h1>Help System</h1></td>
    <td align="right" style="padding-right: 15px"><h3><a href="javascript:x4.helpClear()">Close</a></h3>
  </table>
  <br/>
  <div style="margin: 10px;
          overflow-y: scroll;"><?=vgfGet('htmlHelp')?>
  </div>
</div>
           
