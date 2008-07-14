<?php
class x4configapp extends androX4 {
    function mainLayout($container) {
        # Erase default help message
        vgfSet('htmlHelp','');
        
        configLayoutX4($container,'Application');
    }
}
?>
