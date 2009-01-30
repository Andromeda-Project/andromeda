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
<?php echo implode("\n",$jqdr)?> 
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
    <?php echo $jqueryDocumentReady?>
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
        <?php echo $scriptend?>
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
<div  style="display:none" id="idiv2" class="idiv2"> 
  <table width="100%">
    <tr>
    <td align="left"><h1>Help System</h1></td>
    <td align="right" style="padding-right: 15px">
        <h3><a href="javascript:x4.helpClear()">Close</a></h3>
    </td>
    </tr>
  </table>
  <br/>
  <div id="idiv2content" class="idiv2content" 
    style="margin: 10px; overflow-y: scroll;">
    <?php echo vgfGet('htmlHelp')?>
  </div>
</div>
<div id="dialogoverlay" style="display:none"></div>
<div id="dialogbox"     style="display:none"></div>
<div    id = "x6modalblock"  
     style = "display:none;" 
     class = "x6modalblock"
   onclick = "return false">&nbsp;</div>
<div id="x6modal"       style="display:none;" class="x6modal">
<?php 
    $modals = arr($GLOBALS['AG'],'modals',array());
    foreach($modals as $modal) {
        $modal->render();
    }
?>    
</div>
<?php
    # KFD 12/31/08.  Restrict the query log to people who have
    #                set the cookie and are in the 'debuggers' group
    $debugging = inGroup('debugging');
    $cookie    = arr($_COOKIE,'log_Server',0);
    if($debugging && $cookie) {
        echo( '<br /><div class="androQueryLog">' );
        echo( '<div class="androQueryLogTitle">
            <div style="float:left;height:20px;">Query Log</div><div style="float:right;height:20px;cursor:pointer;" onclick="showHide(\'androQueryLogItems\');">Show/Hide</div></div>' );
        echo( '<div class="androQueryLogItems" id="androQueryLogItems">' );
        foreach ( $GLOBALS['AG']['dbg']['sql'] as $key=>$line ) {
            echo( '<div class="androQueryLogItem" style="width:auto;"><strong>Query:</strong> ' 
                .'<div><pre style="max-width:100%;">'.$line['sql'].'</pre></div>
                <div><strong>Execution time:</strong>' .number_format( $line['time'],4 ) .'</div>
                <div onclick="showHide(\'stack-' .$key .'\');" style="cursor:pointer;">More Details</div>' );
            echo( '<div style="display:none;" id="stack-' .$key .'">' );
            echo "<strong>Execution Stack:</strong><pre>"
                .$line['stack']."</pre></div>";     
            echo "</div>";
        }
        echo( '</div></div>' );
    }
?>
