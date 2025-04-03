<?php

// Template Name: sso_cookie_bridge

$mo_helper = new RonikMoHelper();
$mo_helper_site_processor = new RonikMoHelperSiteProcessor();
$mo_helper_cookie_processor = new RonikMoHelperCookieProcessor();
$mo_helper_demo_processor = new RonikMoHelperDemoProcessor();
$mo_helper_site_processor_is_local = $mo_helper_site_processor->siteMapping('together')['environment'];


if (isset($_COOKIE['sso_post_login_redirect_data']) && isset($_COOKIE['wl-register'])) {
    error_log('Bridge Test');
    if ($mo_helper_site_processor_is_local == 'local') {
        error_log('local');
        error_log('Available cookies at dummyUserFlow: ' . print_r($_COOKIE, true));
        $mo_helper_demo_processor->dummyUserFlow();
    } else {
        $redirect_url = 'home?option=saml_user_login';
        wp_redirect(esc_url(home_url($redirect_url)));
        exit;
    }
} else {
    error_log('Waiting loop: current cookies → ' . print_r($_COOKIE, true));
    echo "Waiting for cookies to set. Reloading...";
    echo '<script>setTimeout(() => location.reload(), 1000);</script>';
    exit;
}
