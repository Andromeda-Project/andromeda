<?php
class x6phpinfo extends androX6 {
    function x6main() {
        if(SessionGet('ROOT')) {
            phpinfo();
        }
    }
}
?>
    
