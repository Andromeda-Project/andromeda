<?php
    /*
     * Smarty Plugin
     * ---------------------------------------------------
     * File:        function.andropage.php
     * Type:        function
     * Name:        AndroPage
     * Purpose:     Allownesting of AndroPages
     * ---------------------------------------------------
     */
    
    function smarty_function_andropage( $params, &$smarty ) {
        if ( count( $params ) == 0 ) {
            $smarty->trigger_error( 'AndroPage: Missing Arguments' );
            return;
        }
        if ( !isset( $params['filters'] ) ) {
            $smarty->trigger_error('AndroPage: Missing filters argument');
            return;
        }
        $filters = array();
        $x1 = explode( "|", $params['filters'] );
        foreach( $x1 as $x ) {
            $x2 = explode( "=", $x );
            $filters[$x2[0]] = $x2[1];
        }
        $url = 'index.php?gp_page=' .$params['page'] .'&gp_post=smarty&gp_uid=' .sessionGet( 'UID' ) .'&st2login=1&gp_pwd=' .sessionGET( 'PWD' );


        foreach( $filters as $filterName=>$filterVal ) {
            $url .= '&ap_' .$filterName .'=' .$filterVal;
        }
        echo( file_get_contents( 'http://' .$_SERVER['SERVER_NAME'] .'/' .$GLOBALS['AG']['tmpPathInsert'] .$url ) );
    }
?>
