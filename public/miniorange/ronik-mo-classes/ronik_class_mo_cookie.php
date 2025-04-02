<?php

class RonikMoHelperCookieProcessor {
    public function cookieSsoGenerator( $sso_post_login_redirect_site_origin , $sso_post_login_redirect_cookie, $route_domain , $is_local){
        $sso_post_login_redirect_data = [];
        // Log current site origin if set
        if ($sso_post_login_redirect_site_origin) {
            $sso_post_login_redirect_data['site_origin'] = $sso_post_login_redirect_site_origin;
            error_log('Current site origin: ' . $sso_post_login_redirect_site_origin);
        }
        // Log the redirect URL (cookie or query param) if set
        if ($sso_post_login_redirect_cookie) {
            $sso_post_login_redirect_data['redirect_url'] = $sso_post_login_redirect_cookie;
            error_log('Redirect URL: ' . $sso_post_login_redirect_cookie);
        }
        // Create redirect data array
        if(!empty($sso_post_login_redirect_data)){
            // Set the cookie with the redirect data
            if($is_local == 'local'){

                $url_set = setcookie('sso_post_login_redirect_data', urlencode(json_encode($sso_post_login_redirect_data)), time() + 3600, '/', $route_domain, false, false);
            } else {
                $url_set = setcookie('sso_post_login_redirect_data', urlencode(json_encode($sso_post_login_redirect_data)), time() + 3600, '/', $route_domain, true, true);
            }
            error_log(print_r($sso_post_login_redirect_data, true));
            error_log('setRedirectCookies: ' . $url_set);
            error_log('setRedirectCookies: ' . $is_local);
            error_log('setRedirectCookies: ' . $route_domain);

            return $url_set ? 'cookieSsoGenerator valid' : 'cookieSsoGenerator invalid';
        }
        return 'cookieSsoGenerator invalid';

    }

    public function cookieSsoFetcher($cookieName){
        // Check if the cookie exists and decode it
        if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName]) {
            $cookieData = urldecode($_COOKIE[$cookieName]);
            $res_cookie_data = json_decode($cookieData, true);
            error_log('Decoded cookie data: ' . print_r($res_cookie_data, true));
            return $res_cookie_data;
        }
        return 'cookieSsoFetcher invalid';

    }
}
