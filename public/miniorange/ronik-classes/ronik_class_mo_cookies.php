<?php

class RonikMoHelperCookieManager {
    // Set Redirect cookies
    public function setRedirectCookies($url, $origin = '' , $time_frame) {
        error_log(print_r( 'setRedirectCookies' , true));
        error_log(print_r( $time_frame , true));

        $mo_helper = new RonikMoHelper();
        [
            $site_production_request, 
            $site_staging_request, 
            $site_local_request, 
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

        // $origin = 'local'; // Example value; can be 'local', 'staging', or 'live'
        switch ($origin) {
            case 'local':
                $sub_domain = $site_local_together;
                $route_domain = $site_local_route_domain;
                // error_log(print_r( "You are in the local environment." , true));
                break;
            case 'stage':
                $sub_domain = $site_staging_together;
                $route_domain = $site_production_route_domain;
                // error_log(print_r( "You are in the staging environment." , true));
                break;
            case 'production':
                $sub_domain = $site_production_together;
                $route_domain = $site_production_route_domain;
                // error_log(print_r( "You are in the live environment." , true));
                break;
            default:
                $sub_domain = '';
                $route_domain = '';
                // error_log(print_r( "Unknown environment." , true));
                break;
        }
        if($time_frame == 'pre'){
            $cookie_name = 'sso_pre_login';
        } else {
            $cookie_name = 'sso_post_login';
        }

        error_log(print_r( $cookie_name , true));
        error_log(print_r( $route_domain , true));
        error_log(print_r( $sub_domain , true));

        // Set the first cookie for the redirect URL
        if ($url == 'talent') {
            error_log(print_r( 'setRedirectCookies: talent' , true));
            $url_set = $this->cookieProcessor( $cookie_name , 'talentroom' , $route_domain, $sub_domain);
        } elseif($url == 'request') {
            error_log(print_r( 'setRedirectCookies: request' , true));

            $url_set = $this->cookieProcessor( $cookie_name , 'request' , $route_domain, $sub_domain);
        } else {
            error_log(print_r( 'setRedirectCookies: misc' , true));
            if($time_frame == 'pre'){
                $url_set = $this->cookieProcessor( $cookie_name , $url , $route_domain, $sub_domain);
            }
        }
        return $url_set;
    }
    // Get Redirect Cookies
    public function getRedirectCookies($default_redirect, $time_frame) {
        if($time_frame == 'pre'){
            $cookie_name = 'sso_pre_login';
        } else {
            $cookie_name = 'sso_post_login';
        }
        if( isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name]){
            $custom_redirect = urldecode($_COOKIE[$cookie_name]);
            return $custom_redirect;
        } else {
            $custom_redirect = null;
        }        
        if($custom_redirect == 'talentroom' || $custom_redirect == 'request'){
            return $custom_redirect;
        }
        return $default_redirect;
    }
    // Process the cookies
    private function cookieProcessor( $name, $data, $route_domain, $sub_domain){
        // Log success or failure of setting the cookie
        $url_set = setcookie($name, urlencode($data), time() + 3600, '/', $route_domain);
        $url_set = setcookie($name, urlencode($data), time() + 3600, '/', $sub_domain, true, true);
        // Set cookie with SameSite=None for cross-site requests (important for cookies across subdomains)
        header('Set-Cookie: '.$name.'=' . urlencode($data) . '; path=/; domain='.$route_domain.'; max-age=3600; SameSite=None; Secure; HttpOnly');
        return $url_set;
    }
}
