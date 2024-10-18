<?php
if ( !has_action( 'mo_saml_attributes' ) ) {
    /**
        * Fires after a user is created/updated in WordPress for SSO. 
        * This allows you to perform actions after the user has logged in and also provides the basic user attributes received from the IDP.
    
    */
    

    // Temporary diagnostic function to see what's being passed
function diagnostic_mo_saml_attributes() {
    $args = func_get_args(); // Gets all passed arguments
    
    error_log('Number of arguments passed: ' . count($args)); // Log number of arguments passed
    
    foreach ($args as $index => $arg) {
        error_log("Argument $index: " . (is_array($arg) ? json_encode($arg) : var_export($arg, true)));
    }
}

// Hook the diagnostic function to log arguments passed by 'mo_saml_attributes'
add_action('mo_saml_attributes', 'diagnostic_mo_saml_attributes', 10);




    // add_action( 'mo_saml_attributes', 'ronik_mo_saml_attributes', 10, 8 );

    // // Define the callback function that will run when the action is triggered
    // function ronik_mo_saml_attributes( $user_email, $firstName, $lastName, $userName, $groupName, $identity_provider, $relayState, $attrs ) {
    //     // Log the values of each argument to check if they are passed
    //     error_log("Checking passed arguments:");
    //     error_log( "User Email: " . (isset($user_email) ? $user_email : 'NOT PASSED') );
    //     error_log( "First Name: " . (isset($firstName) ? $firstName : 'NOT PASSED') );
    //     error_log( "Last Name: " . (isset($lastName) ? $lastName : 'NOT PASSED') );
    //     error_log( "Username: " . (isset($userName) ? $userName : 'NOT PASSED') );
    //     error_log( "Group: " . (isset($groupName) ? $groupName : 'NOT PASSED') );
    //     error_log( "Identity Provider: " . (isset($identity_provider) ? $identity_provider : 'NOT PASSED') );
    //     error_log( "Relay State: " . (isset($relayState) ? $relayState : 'NOT PASSED') );
        
    //     // Log the attributes array
    //     if (isset($attrs) && is_array($attrs)) {
    //         foreach ( $attrs as $key => $value ) {
    //             error_log( "Attribute $key: $value" );
    //         }
    //     } else {
    //         error_log( "Attributes: NOT PASSED or EMPTY" );
    //     }
    
    //     // Perform any additional tasks such as updating user metadata, redirecting, etc.
    // }
    

}     

