<?php
if ( !has_action( 'mo_saml_attributes' ) ) {
    /**
        * Fires just before the user’s username is searched in WordPress for login. 
        * This allows you to modify the username (received from the SAML Response) before user login or register.
    
    */
    
    add_filter( 'pre_user_login', 'ronik_mo_pre_user_login', 10, 1 );
    
    // Define the callback function that will modify the sanitized username
    function ronik_mo_pre_user_login( $sanitized_userName ) {
        // Example: Convert the username to lowercase
        $sanitized_userName = strtolower( $sanitized_userName );
    
        // // Log the modified username for debugging
        // error_log( 'Modified Username: ' . $sanitized_userName );
    
        // Return the modified username
        return $sanitized_userName;
    }
} 

