<?php
require_once( 'smarty/libs/Smarty.class.php' );

/**
 *
 * Outputs an Andromeda page definition on-screen using a 
 *  smarty template.
 *
 * @package androPage
 * @author Donald Organ
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
        
        
        try {
                // Create new instance of smarty
                $smarty = new Smarty();
                $smarty->template_dir = $GLOBALS['AG']['dirs']['root'] .'application/templates/';
                $smarty->compile_dir = $GLOBALS['AG']['dirs']['root'] .'lib/smarty/compile/';
                $smarty->config_dir = $GLOBALS['AG']['dirs']['root'] .'lib/smarty/config/';
                $smarty->cache_dir = $GLOBALS['AG']['dirs']['root'] .'lib/smarty/cache/';
        
                $smarty->caching = false;
                foreach( $yamlP2['section'] as $section=>$props ) {
                        $smarty->assign( $section, $props['rows'] );
                }
                foreach( $yamlP2['options'] as $option=>$val ) {
                        $smarty->assign( $option, $val );
                }

                $smarty->display( $yamlP2['template'] );
        } catch ( Exception $e ) {
                echo( 'Unable to create Smarty Object for the following reason: ' .$e->getMessage() );       
        }
    }    
}
?>
