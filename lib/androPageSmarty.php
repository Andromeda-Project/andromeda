<?php
/**
 *
 * Outputs an Andromeda page definition on-screen using a 
 *  smarty template.
 *
 * @package androPage
 * @author Donald Organ????
 *
*/
class androPageSmarty {
    /**
     *  A placeholder property to demonstrate PHP Doc conventions
     *  @var placeholder
     *  @access private
     */
    var $placeholder = array();
    
    /**
     *  Constructor.  Not sure if we need a constructor
     *
     *  @access public
     *  @since 0.1
     */
    function androPageSmarty() {
    }

    /**
     *  Main Entry point for execution.
     *
     *  @param string $rows       query results     
     *  @param string $yamlP2     The processed YAML page description
     *  @param string $page       The name of the page we are working on
     */
    function main($yamlP2,$page) {
        // The application directory.
        $appdir=$GLOBALS['AG']['dirs']['root']."/application/";
        
        
        // What needs to happen here is you must invoke smarty
        // and make all of the assignments
    }    
}
?>
