<?php
    Class AndroPluginManager {
        public $type;
        public $plugins;

        function __construct() {

        }

        function loadPlugin( $name ) {
            if ( file_exists( $GLOBALS['AG']['dirs']['lib'] .'plugins/' .$name .'.php' ) ) {
                include_once( $GLOBALS['AG']['dirs']['lib'] .'plugins/' .$name .'.php' );
            } elseif ( file_exists( $GLOBALS['AG']['application'] .'plugins/' .$name .'.php' ) ) {
                include_once( $GLOBALS['AG']['dirs']['application'] .'plugins/' .$name .'.php' );
            } else {
                trigger_error( "AndroPluginManager: " .$name ." not found", E_USER_ERROR );
                return false;
            }
            $objName = 'AndroPlugin_' .$name;
            $this->plugins[$name] = new $objName();
            return $this->plugins[$name];
        }
    }
?>