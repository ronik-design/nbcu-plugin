
<?php 
function ronik_redirect_tracker(){
    // Cancel log in request since user is logged in or no index found.
    if (is_user_logged_in()) {
        error_log(print_r( $_SERVER['REQUEST_URI'], true));

        if (str_contains($_SERVER['REQUEST_URI'], '/login/')) {
            // Construct the base redirect URL
            $redirect_url = '/account';
            // Perform the redirect with the query parameters
            wp_redirect( esc_url(home_url($redirect_url)) );
            exit; // Always call exit after a redirect to prevent further execution
        }
        return false;
    }
    $mo_helper_redirect = new RonikMoHelperRedirect();
    $post_login_redirect = $mo_helper_redirect->handleUserPreLoginRedirect();
    $login_overrid_redirect = $mo_helper_redirect->handleUserOverrideRedirect('/nbcuni-sso/login/', '?option=saml_user_login');
}
add_action('template_redirect', 'ronik_redirect_tracker', 1);



function process_saml_get_data_wp_parse_request($wp) {
    $mo_helper_redirect = new RonikMoHelperRedirect();

    // Check if the request method is GET and if 'option=saml_user_login_custom' is in the URL
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['option']) && $_GET['option'] === 'saml_user_login_custom') {
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
    }
}

add_action('template_redirect', 'process_saml_get_data_wp_parse_request', 10, 1);

