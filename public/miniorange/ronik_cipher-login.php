<?php
function ronik_decrypt_login_request(){
    $mo_helper_cipher = new RonikMoHelperCipher();
    // Cancel log in request since user is logged in or no index found.
    if (is_user_logged_in()) {
        return false;
    }
    // Log all cookies
    if (isset($_COOKIE['sso_post_login']) && $_COOKIE['sso_post_login']) {
        error_log('sso_post_login cookie found: ' . $_COOKIE['sso_post_login']);
        $custom_redirect = urldecode($_COOKIE['sso_post_login']);
        $split = explode("sso-rk-log=",$custom_redirect);
        if(isset($split[1]) && $split[1]){
            $mo_helper_cipher->decryptLoginRequest($split[1]);
            // Perform the redirect with the query parameters
            wp_redirect( esc_url($split[0]) );
            exit; // Always call exit after a redirect to prevent further execution
        }
    } else {
        // error_log('sso_post_login cookie NOT found, fallback to url login');
        if (!isset($_GET["sso-rk-log"])) {
            return false;
        } else {
            error_log(print_r( $_GET["sso-rk-log"], true));

            $mo_helper_cipher->decryptLoginRequest($_GET["sso-rk-log"]);
        }
    }
}
add_action('init', 'ronik_decrypt_login_request');
