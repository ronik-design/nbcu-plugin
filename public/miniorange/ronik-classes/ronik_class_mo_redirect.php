<?php
// http://together.nbcudevelopment.local/home/?talent=valid
// http://together.nbcudevelopment.local/login/?r=%2Faccount%2F&wpc=1

class RonikMoHelperRedirect {

    /**
     * Handles user post-login redirection logic.
     * 
     * @param int $user_id The user ID.
     * @return string The final redirect URL.
     */
    public function handleUserPostLoginRedirect($user_id) {
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

        // Check custom or promotion-based redirects
        $custom_redirect = $this->getCustomRedirect($default_redirect);
        if ($this->isTogetherBlog($blog_id_together)) {
            $promotion_redirect = $this->getPromotionRedirect($user_id);
            $custom_redirect = $promotion_redirect ?? $custom_redirect;
        }

        // Process SSO GET params
        $this->processSsoGetParams();

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

    private function processSsoGetParams() {
        if (!empty($_POST['auth-sso-get'])) {
            $sanitized_get_params = json_decode(stripslashes($_POST['auth-sso-get']), true);
            if (is_array($sanitized_get_params)) {
                foreach ($sanitized_get_params as $key => $value) {
                    $_GET[$key] = $value;
                }
                error_log(print_r($sanitized_get_params, true)); // Log decoded params
            } else {
                error_log("Error decoding GET parameters.");
            }
        }
    }

    private function handleRedirect($default_redirect, $user_id, $site_mapping) {
        $mo_helper = new RonikMoHelper();
        $mo_helper_cipher = new RonikMoHelperCipher();

        // Get the environment (local, staging, production) based on server name
        $environment = $mo_helper->getEnvironment($_SERVER['SERVER_NAME']);

        // Determine the current site based on the URL ('together' or 'talentroom')
        $current_site = str_contains(get_bloginfo('url'), 'together') ? 'together' : 'talentroom';
    
        // Get the URL for the current site based on the environment
        $site_url = $site_mapping[$environment][$current_site];
        $redirect = '';
    
        // Check if the 'talent' parameter exists in the URL query string
        if (!empty($_GET['talent'])) {
            // For Talent Room redirection
            // Create the login URL for Talent Room, encrypting the login request
            $talent_site_url = $site_mapping[$environment]['talentroom']; // Use 'talentroom' URL
            // $login_url = $mo_helper_cipher->encryptLoginRequest('1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=', $talent_site_url . "wp-admin/admin-ajax.php?action=ronikdesign_miniorange_ajax&sso-rk-log=", $user_id);
            $login_url = $mo_helper_cipher->encryptLoginRequest('1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=', $talent_site_url . "?sso-rk-log=", $user_id);

            // Set the redirect cookies and return the login URL
            $this->setRedirectCookies($login_url, "talent-room_$environment");
            return $login_url;
    
        } elseif (!empty($_GET['r']) || !empty($_GET['wl-register'])) {
            // For Together site redirection
            // Get the 'r' or 'wl-register' parameter for redirection
            $redirect = $this->removeLeadingSlash($_GET['r'] ?? $_GET['wl-register']);
            
            // Set the redirect cookies and return the together URL with the redirect path
            $this->setRedirectCookies($site_url . $redirect, "together_$environment");
            return $site_url . $redirect;
        }
    
        // If neither 'talent' nor 'r/wl-register' parameters are present, return the default redirect
        return $default_redirect;
    }
    
    private function setRedirectCookies($url, $origin = '') {
        setcookie('sso_post_login_redirect', $url, time() + 3600, '/');
        if ($origin) {
            setcookie('sso_point_of_origin', $origin, time() + 3600, '/');
        }
    }

    private function getCustomRedirect($default_redirect) {
        $custom_redirect = $_COOKIE['sso_post_login_redirect'] ?? null;
        if ($custom_redirect && filter_var($custom_redirect, FILTER_VALIDATE_URL)) {
            return filter_var($custom_redirect, FILTER_SANITIZE_URL);
        }
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

    private function removeLeadingSlash($url) {
        return ltrim($url, '/');
    }
}
