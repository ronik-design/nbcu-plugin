<?php
/**
 * Template Name: saml_user_login_custom
 *
*/

if (is_user_logged_in()) {
    // Destroy the current user session
    wp_destroy_current_session();
    // Clear the authentication cookies
    wp_clear_auth_cookie();
    // Optionally clear user cache or any custom session data if necessary
    wp_cache_delete(get_current_user_id(), 'users');
    sleep(1);
}


// http://together.nbcudev.local/saml_user_login_custom?talent=1&r=/view-projects/%3Ftype=project
// https://stage.together.nbcuni.com/saml_user_login_custom?together=1&r=%2Faccount%2F&wpc=1
// https://stage.together.nbcuni.com/saml_user_login_custom?talent=1&r=%2Faccount%2F&wpc=1
// https://stage.together.nbcuni.com/saml_user_login_custom?talent=1&r=%2Ftalent

$mo_helper = new RonikMoHelper();
$mo_helper_site_processor = new RonikMoHelperSiteProcessor();
$mo_helper_cookie_processor = new RonikMoHelperCookieProcessor();
$mo_helper_demo_processor = new RonikMoHelperDemoProcessor();
$mo_helper_site_processor_is_local = $mo_helper_site_processor->siteMapping('together')['environment'];
$sso_post_login_redirect_site_origin = null;
$sso_post_login_redirect_cookie = null;

$siteTarget = 'together';
$mo_helper_site_processor_data = $mo_helper_site_processor->siteMapping($siteTarget);
$sso_post_login_redirect_site_origin = $mo_helper_site_processor_data['site_url'];
$route_domain = $mo_helper_site_processor_data['site_url_route_domain'];

if($_GET){
    if(isset($_GET['talent']) && $_GET['talent']){
        $siteTarget = 'talentroom';
    }
    if(isset($_GET['request']) && $_GET['request']){
        $siteTarget = 'request';
    }
    if(isset($_GET['r']) && $_GET['r']){
        $sso_post_login_redirect_cookie = $_GET['r'];
        // Remove the 'http://' or 'https://' from the beginning of the URL
        $sso_post_login_redirect_cookie = preg_replace('#^https?://#', '', $sso_post_login_redirect_cookie);
        // Now $sso_post_login_redirect_cookie will contain only the path part of the URL
    }
    if(isset($_GET['wl-register']) && $_GET['wl-register']){
        // $sso_post_login_redirect_cookie = $_GET['wl-register'];
        // error_log(print_r('wl-register COOKIE SET', true));
        // error_log(print_r($sso_post_login_redirect_cookie, true));
        // error_log(print_r($route_domain, true));
        // error_log(print_r(str_replace('/', '', $_COOKIE["wl-register"]), true));

        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
        $current_host = $_SERVER['HTTP_HOST'];
        $is_local = str_contains($current_host, 'localhost') || str_contains($current_host, 'nbcudev.local');
        
        // Dynamically determine domain and secure flags
        $cookie_domain = $is_local ? '' : $route_domai; // Or pull from $route_domain if available
        $secure_flag = !$is_local; // Only true in staging/prod
        $httponly_flag = true;     // This can stay true
        
        setcookie(
            'wl-register',
            str_replace('/', '', $_GET['wl-register']),
            time() + 3600,
            '/',
            $cookie_domain,
            $secure_flag,
            $httponly_flag
        );
    } else {
        setcookie(
            'wl-register',
            'sso-temp-logger',
            time() + 3600,
            '/',
            $cookie_domain,
            $secure_flag,
            $httponly_flag
        );
    }

    error_log(print_r($_GET , true));
}

$cookie_processor_progress = $mo_helper_cookie_processor->cookieSsoGenerator( $sso_post_login_redirect_site_origin , $sso_post_login_redirect_cookie, $route_domain , $mo_helper_site_processor_is_local);

if ($cookie_processor_progress == 'cookieSsoGenerator valid') {
    error_log('Cookie processor progress: Valid');
    // Give browser a redirect to ensure cookie is fully available
    $bridge_url = home_url('sso_cookie_bridge');
    wp_redirect($bridge_url);
    exit;
} else {
    error_log(print_r('Cookie processor progress: Invalid', true));
    echo 'Cookie processor progress: Invalid';
}






// if($cookie_processor_progress == 'cookieSsoGenerator valid'){
//     error_log(print_r('Cookie processor progress: Valid', true));

//     // Have to throttle the redirect
//     sleep(3);

//     if($mo_helper_site_processor_is_local == 'local'){
//         error_log(print_r('local', true));
//         $mo_helper_demo_processor->dummyUserFlow();
//     }



//     // Construct the base redirect URL
//     $redirect_url = 'home?option=saml_user_login';
//     // Perform the redirect with the query parameters
//     wp_redirect( esc_url(home_url($redirect_url)) );
//     exit; // Always call exit after a redirect to prevent further execution
// } else {
//     error_log(print_r('Cookie processor progress: Invalid', true));
//     echo 'Cookie processor progress: Invalid';
// }
?>
