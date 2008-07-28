<?php
# =====================================================================
#
# Spit out previously registered JS files.
# The jsOutput() command decides whether to send a 
# single minified file or to send them individually
#
# =====================================================================
jsOutput();

# =====================================================================
#
# Old-fashioned pre-x4 pre-jquery way to set focus.
# Don't ever use this!  It will be deprecated
# sooner or later.
#
# =====================================================================
if(vgfGet('HTML_focus')<>'') {
    $f = vgfGet('HTML_focus');
    jqDocReady('$("#'.$f.'").focus()');
}


# =====================================================================
#
# Old-fashioned pre-x4 pre-jquery way to set focus.
# Don't ever use this!  It will be deprecated
# sooner or later.
#
# =====================================================================
$jqdr = vgfGet('jqDocReady',array());
if(count($jqdr)>0) {
    ?>
    <script type="text/javascript">
    $(document).ready(function() {
<?=implode("\n",$jqdr)?> 
    });
    </script>
    <?php
}
/*  Original way pre 7/25/08, before we deprecated
             elementadd()
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
*/

if(configGet('deprecated','Y')=='Y') {
    $scriptend = ElementImplode('scriptend');
    if($scriptend<>'') {
        ?>
        <script type="text/javascript">
        <?=$scriptend?>
        </script>
        <?php
    }
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
<div id="dialogoverlay" style="display:none"></div>
<div id="dialogbox"     style="display:none"></div>
