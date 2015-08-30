<?php
class x6phpinfo extends androX6
{
    public function x6main()
    {
        if (SessionGet('ROOT')) {
            phpinfo();
        }
    }
}
?>
    
