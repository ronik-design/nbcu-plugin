<?php

class RonikMoHelperCookieManager {
    // Set Redirect cookies
    public function setRedirectCookies($url, $origin = '') {
        $mo_helper = new RonikMoHelper();
        [
            $site_production_talentroom, 
            $site_staging_talentroom, 
            $site_local_talentroom, 
            $site_production_together, 
            $site_staging_together, 
            $site_local_together, 
            $blog_id_together, 
            $blog_id_talent, 
            $blog_id_request,
            $site_production_route_domain , 
            $site_staging_route_domain , 
            $site_local_route_domain
        ] = $mo_helper->siteAssigner();


        $origin = 'local'; // Example value; can be 'local', 'staging', or 'live'
        switch ($origin) {
            case 'local':
                $sub_domain = $site_local_together;
                $route_domain = $site_local_route_domain;
                error_log(print_r( "You are in the local environment." , true));
                break;

            case 'stage':
                $sub_domain = $site_staging_together;
                $route_domain = $site_production_route_domain;
                error_log(print_r( "You are in the staging environment." , true));
                break;

            case 'production':
                $sub_domain = $site_production_together;
                $route_domain = $site_production_route_domain;
                error_log(print_r( "You are in the live environment." , true));
                break;

            default:
                $sub_domain = '';
                $route_domain = '';
                error_log(print_r( "Unknown environment." , true));
                break;
        }

        error_log(print_r('setRedirectCookies', true));
        error_log(print_r($sub_domain, true));
        error_log(print_r($route_domain, true));
        error_log(print_r('setRedirectCookies', true));

        // Set the first cookie for the redirect URL
        if ($url == 'talent') {
            $url_set = $this->cookieProcessor( 'sso_pre_login' , 'talentroom' , $route_domain, $sub_domain);
        } else {
            $url_set = $this->cookieProcessor( 'sso_pre_login' , $url , $route_domain, $sub_domain);
        }
        // Set the second cookie for the origin
        if ($origin) {
            $this->cookieProcessor( 'sso_pre_origin' , $origin , $route_domain, $sub_domain);
        }

        return $url_set;
    }
    // Get Redirect Cookies
    public function getRedirectCookies($default_redirect) {
        error_log(print_r('getRedirectCookies', true));

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
    // Process the cookies
    private function cookieProcessor( $name, $data, $route_domain, $sub_domain){
        // $url_set = setcookie('sso_post_login_redirect', urlencode($url), time() + 3600, '/');
        // Log success or failure of setting the cookie
        $url_set = setcookie($name, urlencode($data), time() + 3600, '/', $route_domain);
        $url_set = setcookie($name, urlencode($data), time() + 3600, '/', $sub_domain, true, true);
        // Set cookie with SameSite=None for cross-site requests (important for cookies across subdomains)
        header('Set-Cookie: '.$name.'=' . urlencode($data) . '; path=/; domain='.$route_domain.'; max-age=3600; SameSite=None; Secure; HttpOnly');
        error_log('sso_post_login_redirect set: ' . ($url_set ? 'Success' : 'Failure'));
        
        return $url_set;
    }
}
