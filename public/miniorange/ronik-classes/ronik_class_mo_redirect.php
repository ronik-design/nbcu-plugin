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
            $site_production_talentroom, 
            $site_staging_talentroom, 
            $site_local_talentroom, 
            $site_production_together, 
            $site_staging_together, 
            $site_local_together, 
            $blog_id_together, 
            $blog_id_talent, 
            $blog_id_request
        ] = $mo_helper->siteAssigner();

        $default_redirect = $this->getDefaultRedirectUrl($blog_id_together, $blog_id_talent, $blog_id_request);
        // Process SSO GET params
        $mo_get_post_manager->processSsoPostConvertParams();


        // Handle environment and redirection
        return $this->handleRedirect($default_redirect, false, [
            'production' => ['talentroom' => $site_production_talentroom, 'together' => $site_production_together],
            'stage' => ['talentroom' => $site_staging_talentroom, 'together' => $site_staging_together],
            'local' => ['talentroom' => $site_local_talentroom, 'together' => $site_local_together]
        ]);          
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
            $site_production_talentroom, 
            $site_staging_talentroom, 
            $site_local_talentroom, 
            $site_production_together, 
            $site_staging_together, 
            $site_local_together, 
            $blog_id_together, 
            $blog_id_talent, 
            $blog_id_request
        ] = $mo_helper->siteAssigner();

        $default_redirect = $this->getDefaultRedirectUrl($blog_id_together, $blog_id_talent, $blog_id_request);

        // Check redirect cookie or promotion-based redirects
        $custom_redirect = $mo_cookie_manager->getRedirectCookies($default_redirect);
        error_log(print_r('handleUserPostLoginRedirect', true));
        error_log(print_r($custom_redirect, true));

        if ($this->isTogetherBlog($blog_id_together)) {
            $promotion_redirect = $this->getPromotionRedirect($user_id);
            $custom_redirect = $promotion_redirect ?? $custom_redirect;
        }

        // Process SSO GET params
        $mo_get_post_manager->processSsoPostConvertParams();

        // Handle environment and redirection
        return $this->handleRedirect($custom_redirect, $user_id, [
            'production' => ['talentroom' => $site_production_talentroom, 'together' => $site_production_together],
            'stage' => ['talentroom' => $site_staging_talentroom, 'together' => $site_staging_together],
            'local' => ['talentroom' => $site_local_talentroom, 'together' => $site_local_together]
        ]);
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

    private function handleRedirect($default_redirect=false, $user_id=false, $site_mapping) {
        $mo_helper = new RonikMoHelper();
        $mo_get_post_manager = new RonikMoHelperGetPostManager();
        $mo_cookie_manager = new RonikMoHelperCookieManager();
        $mo_helper_cipher = new RonikMoHelperCipher();

        // Get the environment (local, staging, production) based on server name
        $environment = $mo_helper->getEnvironment($_SERVER['SERVER_NAME']);

        // Determine the current site based on the URL ('together' or 'talentroom')
        $current_site = str_contains(get_bloginfo('url'), 'together') ? 'together' : 'talentroom';
    
        // Get the URL for the current site based on the environment
        $site_url = $site_mapping[$environment][$current_site];
        $redirect = '';
    

        // TEST
        $mo_get_post_manager->processSsoGet($user_id, $site_url, $site_mapping, $environment);
    
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
