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

$mo_helper_site_processor = new RonikMoHelperSiteProcessor();
$mo_helper_cookie_processor = new RonikMoHelperCookieProcessor();
$mo_helper_demo_processor = new RonikMoHelperDemoProcessor();
$mo_helper_site_processor_is_local = $mo_helper_site_processor->siteMapping('together')['environment'];
$sso_post_login_redirect_site_origin = null; 
$sso_post_login_redirect_cookie = null; 

$siteTarget = 'together';
if($_GET){
    if(isset($_GET['talent']) && $_GET['talent']){
        $siteTarget = 'talentroom';
    }
    if(isset($_GET['request']) && $_GET['request']){
        $siteTarget = 'request';
    }
    if(isset($_GET['r']) && $_GET['r']){
        $sso_post_login_redirect_cookie = $_GET['r'];
    }
    if(isset($_GET['wl-register']) && $_GET['wl-register']){
        $sso_post_login_redirect_cookie = $_GET['wl-register'];
    }
}
$mo_helper_site_processor_data = $mo_helper_site_processor->siteMapping($siteTarget);
$sso_post_login_redirect_site_origin = $mo_helper_site_processor_data['site_url'];
$route_domain = $mo_helper_site_processor_data['site_url_route_domain'];

$cookie_processor_progress = $mo_helper_cookie_processor->cookieSsoGenerator( $sso_post_login_redirect_site_origin , $sso_post_login_redirect_cookie, $route_domain , $mo_helper_site_processor_is_local);

if($cookie_processor_progress == 'cookieSsoGenerator valid'){
    error_log(print_r('Cookie processor progress: Valid', true));

    // Have to throttle the redirect
    sleep(5);

    if($mo_helper_site_processor_is_local == 'local'){
        error_log(print_r('local', true));
        $mo_helper_demo_processor->dummyUserFlow();
    }



    // Construct the base redirect URL
    $redirect_url = 'home?option=saml_user_login';
    // Perform the redirect with the query parameters
    wp_redirect( esc_url(home_url($redirect_url)) );
    exit; // Always call exit after a redirect to prevent further execution
} else {
    error_log(print_r('Cookie processor progress: Invalid', true));
    echo 'Cookie processor progress: Invalid';
}
?>
