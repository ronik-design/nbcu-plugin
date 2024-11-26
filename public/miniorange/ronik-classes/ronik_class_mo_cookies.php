<?php

class RonikMoHelperCookieManager {
    // Set Redirect cookies
    public function setRedirectCookies($url, $origin = '') {
        // Log the inputs to ensure they're correct
        error_log('Setting cookies - URL: ' . $url . ', Origin: ' . $origin);
        // Set the first cookie for the redirect URL
        if ($url) {
            // $url_set = setcookie('sso_post_login_redirect', urlencode($url), time() + 3600, '/');
            // Log success or failure of setting the cookie
            $url_set = setcookie('sso_pre_login', urlencode($url), time() + 3600, '/');
            error_log('sso_post_login_redirect set: ' . ($url_set ? 'Success' : 'Failure'));
        }    
        // Set the second cookie for the origin
        if ($origin) {
            $origin_set = setcookie('sso_pre_origin', $origin, time() + 3600, '/');
            // Log success or failure of setting the cookie
            error_log('sso_pre_origin set: ' . ($origin_set ? 'Success' : 'Failure'));
        }
    }
    // Get Redirect Cookies
    public function getRedirectCookies($default_redirect) {
        if( isset($_COOKIE['sso_pre_login']) && $_COOKIE['sso_pre_login']){
            $custom_redirect = urldecode($_COOKIE['sso_pre_login']);
            error_log(print_r( 'getRedirectCookies', true));
            error_log(print_r( $custom_redirect, true));
        } else {
            $custom_redirect = null;
        }        
        if ($custom_redirect && filter_var($custom_redirect, FILTER_VALIDATE_URL)) {
            return filter_var($custom_redirect, FILTER_SANITIZE_URL);
        }
        return $default_redirect;
    }
}
