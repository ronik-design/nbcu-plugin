<?php
/**
 * Template Name: saml_user_login_custom
 *
 */

// Logout logic (force logout if already logged in)
if (is_user_logged_in()) {
    // Destroy the current user session
    wp_destroy_current_session();
    // Clear the authentication cookies
    wp_clear_auth_cookie();
    // Optionally clear user cache or any custom session data if necessary
    wp_cache_delete(get_current_user_id(), 'users');
    // Ideally replaced with client redirect or session check, not delay
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

// Set default siteTarget and override if query params exist
$siteTarget = 'together';
if (isset($_GET['talent']) && $_GET['talent']) {
    $siteTarget = 'talentroom';
} elseif (isset($_GET['request']) && $_GET['request']) {
    $siteTarget = 'request';
}

// Get site data based on target
$mo_helper_site_processor_data = $mo_helper_site_processor->siteMapping($siteTarget);
$route_domain = $mo_helper_site_processor_data['site_url_route_domain'];
$sso_post_login_redirect_site_origin = $mo_helper_site_processor_data['site_url'];
$mo_helper_site_processor_is_local = $mo_helper_site_processor_data['environment'];

// Detect environment and configure cookie flags
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$current_host = $_SERVER['HTTP_HOST'];
$is_local = str_contains($current_host, 'localhost') || str_contains($current_host, 'nbcudev.local');

        // Dynamically determine domain and secure flags
        $cookie_domain = $is_local ? '' : $route_domain; // Or pull from $route_domain if available
        $secure_flag = !$is_local; // Only true in staging/prod
        $httponly_flag = true;     // This can stay true

// Handle redirect path
$sso_post_login_redirect_cookie = null;
// if (isset($_GET['r']) && $_GET['r']) {
//     $sso_post_login_redirect_cookie = urldecode($_GET['r']);
//     // Remove full domain if present, keep path only
//     $sso_post_login_redirect_cookie = preg_replace('#^https?://[^/]+#', '', $sso_post_login_redirect_cookie);
// }


$sso_post_login_redirect_cookie = null;

if (isset($_GET['r']) && $_GET['r']) {
    $raw_redirect = urldecode($_GET['r']);
    error_log("Original GET r parameter: '$raw_redirect'");

    // Just use the raw value as-is (optional: strip protocol if present)
    $raw_redirect = preg_replace('#^https?://#', '', $raw_redirect); // optional cleanup

    // Strip trailing slashes or extra junk if needed (optional)
    $raw_redirect = trim($raw_redirect, '/');

    // Only set if it's still a valid string
    if (!empty($raw_redirect)) {
        $sso_post_login_redirect_cookie = $raw_redirect;
    }
}

error_log('sso_post_login_redirect_cookie');
error_log($sso_post_login_redirect_cookie ?? '[NULL]');





// Handle wl-register cookie logic
if (isset($_GET['wl-register']) && $_GET['wl-register']) {
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

// Log GET values
error_log(print_r($_GET, true));

// Generate cookies for SSO
$cookie_processor_progress = $mo_helper_cookie_processor->cookieSsoGenerator(
    $sso_post_login_redirect_site_origin,
    $sso_post_login_redirect_cookie,
    $route_domain,
    $mo_helper_site_processor_is_local
);

// Redirect or error
if ($cookie_processor_progress === 'cookieSsoGenerator valid') {
    error_log('Cookie processor progress: Valid');
    // Give browser a redirect to ensure cookie is fully available
    $bridge_url = home_url('sso_cookie_bridge');
    wp_redirect($bridge_url);
    exit;
} else {
    error_log(print_r('Cookie processor progress: Invalid', true));
    echo 'Cookie processor progress: Invalid';
}
?>
