<?php
if ( !has_action( 'mo_abr_filter_login' ) ) {
    /**
        * Fires just before the user is logged in to WordPress via SSO.
        * This allows you to perform actions before the user has logged in and also provides all the user attributes received from the IDP.
    */
    // Register the action hook
    add_action( 'mo_abr_filter_login', 'ronik_mo_abr_filter_login', 10, 3 );

    // Define the callback function that will run when the action is triggered
    function ronik_mo_abr_filter_login( $attrs, $nameId = '', $sessionIndex = '' ) {
        // Log some basic info for debugging purposes
        error_log( "NameID: " . (isset($nameId) && !empty($nameId) ? $nameId : 'No NameID Provided') );
        error_log( "Session Index: " . (isset($sessionIndex) && !empty($sessionIndex) ? $sessionIndex : 'No SessionIndex Provided') );


        error_log(print_r($attrs, true));
        

        // If $attrs is an array, log the attributes received
        if (is_array($attrs)) {
            foreach ($attrs as $key => $value) {
                
                error_log( "Attribute $key: $value" );
            }
        }
    }
}


