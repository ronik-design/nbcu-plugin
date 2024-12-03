<?php 
// http://together.nbcudev.local/home/?option=saml_user_login_custom&r=%2Faccount&wpc=1

function ronik_redirect_tracker(){
    $mo_helper_redirect = new RonikMoHelperRedirect();

    // Cancel log in request since user is logged in or no index found.
    if (is_user_logged_in()) {
        error_log(print_r( 'ronik_redirect_tracker: USER IS LOGGED IN', true));
        if (str_contains($_SERVER['REQUEST_URI'], '/login/')) {
            // Construct the base redirect URL
            $redirect_url = '/account';
            // Perform the redirect with the query parameters
            wp_redirect( esc_url(home_url($redirect_url)) );
            exit; // Always call exit after a redirect to prevent further execution
        }
        return false;
    }

    // Check if the request method is GET and if 'option=saml_user_login_custom' is in the URL
    // http://together.nbcudev.local/home?option=saml_user_login_custom
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['option']) && $_GET['option'] === 'saml_user_login_custom') {
        error_log(print_r( 'ronik_redirect_tracker: saml_user_login_custom', true));

        // Initialize an empty associative array to store sanitized GET data
        $get_data = array();
        // Check if 'talent' parameter exists in the URL and sanitize it
        if (isset($_GET['talent'])) {
            $get_data['talent'] = sanitize_text_field($_GET['talent']);
        }
        // Check if 'r' parameter exists in the URL and sanitize it
        if (isset($_GET['r'])) {
            $get_data['r'] = sanitize_text_field($_GET['r']);
        }
        // Check if 'wl-register' parameter exists in the URL and sanitize it
        if (isset($_GET['wl-register'])) {
            $get_data['wl-register'] = sanitize_text_field($_GET['wl-register']);
        }
        // Log the sanitized data for debugging
        error_log('Sanitized GET Data: ' . print_r($get_data, true));
        $post_login_redirect = $mo_helper_redirect->handleUserPreLoginRedirect();
        // Have to throttle the redirect
        sleep(5);
        // Construct the base redirect URL
        $redirect_url = 'home?option=saml_user_login';
        // Perform the redirect with the query parameters
        wp_redirect( esc_url(home_url($redirect_url)) );
        exit; // Always call exit after a redirect to prevent further execution
    } else {
        // http://together.nbcudev.local/home?option=saml_user_login
        error_log(print_r( 'ronik_redirect_tracker: NOT', true));

        $post_login_redirect = $mo_helper_redirect->handleUserPreLoginRedirect();
        $login_overrid_redirect = $mo_helper_redirect->handleUserOverrideRedirect('/nbcuni-sso/login/', '?option=saml_user_login');

        // Have to throttle the redirect
        sleep(5);
    }
}
add_action('template_redirect', 'ronik_redirect_tracker', 1);

