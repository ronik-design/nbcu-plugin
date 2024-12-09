<?php
if ( !has_action( 'mo_saml_attributes' ) ) {
    /**
        * Fires after a user is created/updated in WordPress for SSO.
        * This allows you to perform actions after the user has logged in and also provides the basic user attributes received from the IDP.
    */
    // Register the action hook
    add_action( 'mo_saml_attributes', 'ronik_mo_saml_attributes', 10, 5 );
    // Define the callback function that will run when the action is triggered
    function ronik_mo_saml_attributes( $user_name, $user_email, $first_name, $last_name, $group_name ) {
        // Log some basic info for debugging purposes
        // Perform any additional tasks such as updating user metadata, redirecting, etc.
    }
}
