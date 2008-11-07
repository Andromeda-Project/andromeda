<?php
class x6skinShow extends androX6 {
    function x6main() {
        ?>
        <h1>Skin Details</h1>
        
        <p><b>Current Skin:</b><?=arr($_COOKIE,'x6skin','win2k.gray.1024')?>
        </p>
        <?php
        if(isset($GLOBALS['AG']['x6skin'])) {
            hprint_r($GLOBALS['AG']['x6skin']);
        }
        else {
            echo "The skin was not loaded.";
        }
        
    }
}
?>
