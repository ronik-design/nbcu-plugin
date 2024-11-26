<?php
if ( !has_action( 'mo_abr_filter_login' ) ) {
    /**
        * Fires just before the user is logged in to WordPress via SSO.
        * This allows you to perform actions before the user has logged in and also provides all the user attributes received from the IDP.
    */
    // Register the action hook
    add_action( 'mo_abr_filter_login', 'ronik_mo_abr_filter_login', 10, 4 );

    // Define the callback function that will run when the action is triggered
    function ronik_mo_abr_filter_login( $attrs, $nameId = '', $sessionIndex = '') {
        $attributes = [
            "email" => isset($attrs['email'][0]) ? [$attrs['email'][0]] : [''],
            "Email" => isset($attrs['email'][0]) ? [$attrs['email'][0]] : [''], // Alternative casing
            "firstname" => isset($attrs['FirstName'][0]) ? [$attrs['FirstName'][0]] : [''],
            "FirstName" => isset($attrs['FirstName'][0]) ? [$attrs['FirstName'][0]] : [''], // Alternative casing
            "lastname" => isset($attrs['LastName'][0]) ? [$attrs['LastName'][0]] : [''],
            "LastName" => isset($attrs['LastName'][0]) ? [$attrs['LastName'][0]] : [''], // Alternative casing
            "accountstatus" => isset($attrs['accountstatus'][0]) && strtolower($attrs['accountstatus'][0]) == 'a' ? ['active'] : ['inactive'], // Could be "active" or "inactive"
            "uid" => isset($attrs['uid'][0]) ? [$attrs['uid'][0]] : [''],
            "UID" => isset($attrs['uid'][0]) ? [$attrs['uid'][0]] : [''], // Alternative casing
            "jobtitle" => [''], // Static value
            "telephonenumber" => [''], // Static value
        ]; 
        $mo_helper = new RonikMoHelper();
        // Assume $mo_helper is an object and userFlowProcessor() is a method that processes the user flow
        $post_login_redirect = $mo_helper->userFlowProcessor($attributes);
        sleep(1);
        // Check if the result is valid (non-empty, non-false) and redirect
        wp_redirect( !empty($post_login_redirect) ? $post_login_redirect : home_url() );
        exit(); // Always call exit() after wp_redirect() to stop further execution
    }
}
