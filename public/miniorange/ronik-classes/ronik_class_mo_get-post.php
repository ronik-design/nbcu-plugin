<?php
class RonikMoHelperGetPostManager {
    public function processSsoGet($user_id, $site_url, $site_mapping, $environment, $time_frame, $default_redirect){
        error_log(print_r( 'processSsoGet' , true));
        error_log(print_r( $time_frame , true));


        // Helper Guide
        $helper = new RonikHelper;
        $mo_helper = new RonikMoHelper();
        $mo_cookie_manager = new RonikMoHelperCookieManager();
        $mo_helper_cipher = new RonikMoHelperCipher();
        if($time_frame == 'post'){
            if($user_id){
                // For Talent Room redirection
                if($default_redirect == 'request' || $default_redirect == 'talentroom'){
                    error_log(print_r( 'TEST !!!!!' , true));

                    // Create the login URL for Talent Room, encrypting the login request
                    $dynamic_site_url = $site_mapping[$environment][$default_redirect]; // Use 'talentroom' URL
                    $login_url = $dynamic_site_url . "?sso-rk-log=". $helper->ronik_encrypt_data_meta($user_id);
                } else {
                    error_log(print_r( 'TEST @@@@@@' , true));

                    $login_url = $default_redirect;
                }
                // Set the redirect cookies and return the login URL
                $url_set = $mo_cookie_manager->setRedirectCookies($login_url , $environment , $time_frame);




                
                error_log(print_r( 'processSsoGet time_frame POST' , true));
                error_log(print_r( 'setRedirectCookies: '. $url_set , true));
                error_log(print_r( 'default_redirect: '. $default_redirect , true));
                error_log(print_r( 'site_url: '. $site_url , true));
                error_log(print_r('environment: '. $environment , true));
                error_log(print_r( 'login_url: '. $login_url , true));
                error_log(print_r( 'processSsoGet' , true));



                wp_redirect( esc_url($login_url) );
                exit; // Always call exit after a redirect to prevent further execution
            }
        }
        if(!empty($_GET['option']) || !empty($_GET['talent']) || !empty($_GET['request']) || !empty($_GET['r']) || !empty($_GET['wl-register'])){
            // Check if the 'talent' parameter exists in the URL query string
            if (!empty($_GET['talent'])) {
                if($time_frame == 'pre'){
                    error_log(print_r( 'processSsoGet: setRedirectCookies talent' , true));
                    $mo_cookie_manager->setRedirectCookies('talent', $environment , $time_frame);
                }
            } elseif (!empty($_GET['request'])) {
                if($time_frame == 'pre'){
                    error_log(print_r( 'processSsoGet: setRedirectCookies request' , true));
                    $mo_cookie_manager->setRedirectCookies('request', $environment , $time_frame);
                }
            } elseif (!empty($_GET['r']) || !empty($_GET['wl-register'])) {
                error_log(print_r( 'processSsoGet: setRedirectCookies r' , true));
                // For Together site redirection
                // Get the 'r' or 'wl-register' parameter for redirection
                $redirect = $this->removeLeadingSlash($_GET['r'] ?? $_GET['wl-register']);
                // Set the redirect cookies and return the together URL with the redirect path
                $mo_cookie_manager->setRedirectCookies($site_url . $redirect, $environment, $time_frame);
                return $site_url . $redirect;
            }
        }
    }
    // This is more for demo debuging purposes. Or if client wants to not depend on cookies. Proof of concept!
    // public function processSsoPostConvertParams() {
    //     if (!empty($_POST['auth-sso-get'])) {
    //         $sanitized_get_params = json_decode(stripslashes($_POST['auth-sso-get']), true);
    //         if (is_array($sanitized_get_params)) {
    //             foreach ($sanitized_get_params as $key => $value) {
    //                 $_GET[$key] = $value;
    //             }
    //         } else {
    //             // error_log("Error decoding GET parameters.");
    //         }
    //     }
    // }


    public function processCookies($site_url, $site_mapping, $environment, $time_frame ){
        $mo_helper = new RonikMoHelper();
        $mo_cookie_manager = new RonikMoHelperCookieManager();

        if(!empty($_GET['r']) || !empty($_GET['wl-register'])){
            error_log(print_r('ss', true));
            // Check if the 'talent' parameter exists in the URL query string
            if (!empty($_GET['talent'])) {
                if($time_frame == 'pre'){
                    error_log(print_r( 'processSsoGet: setRedirectCookies talent' , true));
                    $mo_cookie_manager->setRedirectCookies('talent', $environment , $time_frame);
                }
            } elseif (!empty($_GET['request'])) {
                if($time_frame == 'pre'){
                    error_log(print_r( 'processSsoGet: setRedirectCookies request' , true));
                    $mo_cookie_manager->setRedirectCookies('request', $environment , $time_frame);
                }
            } elseif (!empty($_GET['r']) || !empty($_GET['wl-register'])) {
                error_log(print_r( 'processSsoGet: setRedirectCookies r' , true));
                // For Together site redirection
                // Get the 'r' or 'wl-register' parameter for redirection
                $redirect = $this->removeLeadingSlash($_GET['r'] ?? $_GET['wl-register']);
                // Set the redirect cookies and return the together URL with the redirect path
                $mo_cookie_manager->setRedirectCookies($site_url . $redirect, $environment, $time_frame);
                return $site_url . $redirect;
            }
        }
    }


    private function removeLeadingSlash($url) {
        return ltrim($url, '/');
    }
}
