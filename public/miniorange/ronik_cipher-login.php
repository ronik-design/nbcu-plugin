<?php 
function ronik_decrypt_login_request(){
    // Cancel log in request since user is logged in or no index found.
    if (is_user_logged_in()) {
        return false;
    }
    if (!isset($_GET["sso-rk-log"])) {
        return false;
    }
    $mo_helper_cipher = new RonikMoHelperCipher();
    $redirect_url = $mo_helper_cipher->decryptLoginRequest('1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=');
    if( $redirect_url ){
        wp_redirect($redirect_url);
        exit;
    } else {
        return false;
    }
}
add_action('template_redirect', 'ronik_decrypt_login_request');