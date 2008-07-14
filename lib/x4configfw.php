<?php
class x4configfw extends androX4 {
    function mainLayout($container) {
        # Erase default help message
        vgfSet('htmlHelp','');

        configLayoutX4($container,'Framework');
    }
}
?>
