<?php
if ( !has_action( 'mo_abr_filter_login' ) ) {
    /**
        * Fires just before the user is logged in to WordPress via SSO. 
        * This allows you to perform actions before the user has logged in and also provides all the user attributes received from the IDP.
    
    */
    // add_action( 'mo_abr_filter_login', 'ronik_mo_abr_filter_login', 10, 3 );
    // // Define the callback function that will handle the action
    // function ronik_mo_abr_filter_login( $attrs, $nameId, $sessionIndex ) {
    //     // Example: Logging the data
    //     error_log( 'NameID: ' . $nameId );
    //     error_log( 'Session Index: ' . $sessionIndex );
    
    //     // If $attrs is an array, you can loop through it
    //     if ( is_array( $attrs ) ) {
    //         foreach ( $attrs as $key => $value ) {
    //             error_log( "Attribute $key: $value" );
    //         }
    //     }
    // }
}  


