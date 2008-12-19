<?php
class x6useroptions extends androX6 {
    function x6main() {
        $file = fsDirTop().'generated/x6skins.ser.txt';
        $skins = unserialize(file_get_contents($file));
        $select = html('select');
        #hprint_r($GLOBALS);
        $cookie = $_COOKIE['x6skin'];
        foreach($skins as $name=>$stats) {
            $option = $select->h('option',$name);
            $option->hp['value'] = $stats;
            # Note that $cookie was defined above 
            if($cookie==$stats) $option->hp['selected'] = 'selected';
        }
        $select->hp['onchange']='x6ChangeSkin(this)';

        ?>        
        <script>
        window.x6ChangeSkin = function(select) {
            document.cookie 
                = "x6skin="+select.value+"; expires=12/31/2049 00:00:00;";
            window.location.reload(true);
        }
        </script>
        <h1>User Options</h1>
        Skin: <?=$select->render()?>
        <?php
    }
}
?>

