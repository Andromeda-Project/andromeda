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

?>
