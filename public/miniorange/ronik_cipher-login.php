<?php 
function ronik_decrypt_login_request(){
    // Cancel log in request since user is logged in or no index found.
    if (is_user_logged_in()) {
        // error_log(print_r('User is logged in. Kill function.', true));
        return false;
    }

    if (!isset($_GET["sso-rk-log"])) {
        // error_log(print_r('GET request not found. Kill function.', true));
        return false;
    }

    $mo_helper_cipher = new RonikMoHelperCipher();
    $redirect_url = $mo_helper_cipher->decryptLoginRequest('1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=');

    wp_redirect($redirect_url);
    exit;
}
add_action('template_redirect', 'ronik_decrypt_login_request');