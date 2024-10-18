<?php
if ( !has_action( 'mo_saml_attributes' ) ) {
    /**
        * Fires after a user is created/updated in WordPress for SSO. 
        * This allows you to perform actions after the user has logged in and also provides the basic user attributes received from the IDP.
    
    */
    
    add_action( 'mo_saml_attributes', 'ronik_mo_saml_attributes', 10, 8 );

    // Define the callback function that will run when the action is triggered
    function ronik_mo_saml_attributes( $user_email, $firstName, $lastName, $userName, $groupName, $identity_provider, $relayState, $attrs ) {
        // Log some basic info for debugging purposes
        error_log( "User Email: " . $user_email );
        error_log( "First Name: " . $firstName );
        error_log( "Last Name: " . $lastName );
        error_log( "Username: " . $userName );
        error_log( "Group: " . $groupName );
        error_log( "Identity Provider: " . $identity_provider );
        error_log( "Relay State: " . $relayState );
    
        // If $attrs is an array of attributes, you can loop through it:
        foreach ( $attrs as $key => $value ) {
            error_log( "Attribute $key: $value" );
        }
    
        // Perform any additional tasks such as updating user metadata, redirecting, etc.
    }
    
    // Use ReflectionFunction to check the number of arguments expected by the function
    $reflection = new ReflectionFunction('ronik_mo_saml_attributes');
    
    // Log the number of required parameters and total parameters
    error_log('Number of required parameters: ' . $reflection->getNumberOfRequiredParameters());
    error_log('Total number of parameters: ' . $reflection->getNumberOfParameters());
    

}     

