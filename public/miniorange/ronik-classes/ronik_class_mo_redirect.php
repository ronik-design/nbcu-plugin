<?php
class RonikMoHelperRedirect {
    /**
     * Handles user post-login redirection logic.
     * 
     * @param int $user_id The user ID.
     * @return string The final redirect URL.
     */

    public function handleUserPreLoginRedirect() {
        $mo_get_post_manager = new RonikMoHelperGetPostManager();
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
            $site_production_route_domain, 
            $site_staging_route_domain, 
            $site_local_route_domain
        ] = $mo_helper->siteAssigner();

        $default_redirect = $this->getDefaultRedirectUrl($blog_id_together, $blog_id_talent, $blog_id_request);
        // Process SSO GET params
        $mo_get_post_manager->processSsoPostConvertParams();
        // Handle environment and redirection
        return $this->handleRedirect($default_redirect, false, [
            'production' => ['request' => $site_production_request, 'talentroom' => $site_production_talentroom, 'together' => $site_production_together],
            'stage' => ['request' => $site_staging_request, 'talentroom' => $site_staging_talentroom, 'together' => $site_staging_together],
            'local' => ['request' => $site_local_request, 'talentroom' => $site_local_talentroom, 'together' => $site_local_together]
        ], 'pre');          
    }

    public function handleUserOverrideRedirect($old, $new){
        $current_url = $_SERVER['REQUEST_URI']; // Get current URL
        if($current_url == '/nbcuni-sso/login/' || $current_url == '/nbcuni-sso/login.php'){
            $new_url = str_replace($old, $new, $current_url); // Replace part of the URL
            // Perform the redirect
            wp_redirect( esc_url(home_url($new_url)) );
            exit();
        }
    }
    
    public function handleUserPostLoginRedirect($user_id) {
        $mo_get_post_manager = new RonikMoHelperGetPostManager();
        $mo_cookie_manager = new RonikMoHelperCookieManager();
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
            $site_production_route_domain, 
            $site_staging_route_domain, 
            $site_local_route_domain
        ] = $mo_helper->siteAssigner();
        $default_redirect = $this->getDefaultRedirectUrl($blog_id_together, $blog_id_talent, $blog_id_request);
        // Check redirect cookie or promotion-based redirects
        error_log(print_r( 'WARNING EXP reverse Fetch name cookie !!!!!' , true));
        $custom_redirect = $mo_cookie_manager->getRedirectCookies($default_redirect , 'pre');
        error_log(print_r( $custom_redirect , true));

        // error_log(print_r('handleUserPostLoginRedirect', true));
        if ($this->isTogetherBlog($blog_id_together)) {
            $promotion_redirect = $this->getPromotionRedirect($user_id);
            $custom_redirect = $promotion_redirect ?? $custom_redirect;
        }
        error_log(print_r( 'WARNING END' , true));
        error_log(print_r($custom_redirect, true));
        // Process SSO GET params
        $mo_get_post_manager->processSsoPostConvertParams();
        error_log(print_r( 'WARNING END 2' , true));
        error_log(print_r($custom_redirect, true));

        // Handle environment and redirection
        return $this->handleRedirect($custom_redirect, $user_id, [
            'production' => ['request' => $site_production_request, 'talentroom' => $site_production_talentroom, 'together' => $site_production_together],
            'stage' => ['request' => $site_staging_request, 'talentroom' => $site_staging_talentroom, 'together' => $site_staging_together],
            'local' => ['request' => $site_local_request, 'talentroom' => $site_local_talentroom, 'together' => $site_local_together]
        ], 'post');
    }

    private function getDefaultRedirectUrl($blog_id_together, $blog_id_talent, $blog_id_request) {
        $blog_id = get_current_blog_id();
        if ($blog_id == $blog_id_together) {
            return get_home_url() . '/home/';
        }
        return get_home_url() . '/';
    }

    private function isTogetherBlog($blog_id_together) {
        return get_current_blog_id() == $blog_id_together;
    }

    private function handleRedirect($default_redirect=false, $user_id=false, $site_mapping, $time_frame) {
        error_log(print_r( 'handleRedirect' , true));

        $mo_helper = new RonikMoHelper();
        $mo_get_post_manager = new RonikMoHelperGetPostManager();
        $mo_cookie_manager = new RonikMoHelperCookieManager();
        $mo_helper_cipher = new RonikMoHelperCipher();
        // Get the environment (local, staging, production) based on server name
        $environment = $mo_helper->getEnvironment($_SERVER['SERVER_NAME']);
        // Determine the current site based on the URL ('together' or 'talentroom')
        $current_site = str_contains(get_bloginfo('url'), 'together') ? 'together' : 'talentroom';
        if(isset($_GET['talent']) && $_GET['talent']){
            $current_site = 'talent';
        } else if( isset($_GET['request']) && $_GET['request']){
            $current_site = 'request';
        } 


        // Get the URL for the current site based on the environment
        if($default_redirect){
            $site_url = $site_mapping[$environment][$default_redirect];
        } else {
            $site_url = $site_mapping[$environment][$current_site];
        }
        error_log(print_r( 'handleRedirect default_redirect:' . $default_redirect   , true));
        error_log(print_r( 'handleRedirect current_site:' . $current_site   , true));
        error_log(print_r( 'handleRedirect site_url:' . $site_url   , true));


        // TEST
        $mo_get_post_manager->processSsoGet($user_id, $site_url, $site_mapping, $environment, $time_frame, $default_redirect);
        if($default_redirect == 'talentroom' || $default_redirect == 'request'){
            return $current_site;
        }
        // If neither 'talent' nor 'r/wl-register' parameters are present, return the default redirect
        return $default_redirect;
    }

    private function getPromotionRedirect($user_id) {
        $promotion_details = flag_promotion_success($user_id);
        $promotion_redirect = $promotion_details['first_login_redirect'] ?? null;
        if ($promotion_redirect && filter_var($promotion_redirect, FILTER_VALIDATE_URL)) {
            return filter_var($promotion_redirect, FILTER_SANITIZE_URL);
        }
        return null;
    }

}
