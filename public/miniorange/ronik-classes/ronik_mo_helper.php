<?php
class RonikMoHelper{

    /**
     * Assigns user attributes after validating and handling cases where certain attributes might be missing.
     *
     * @param array $attributes The array of user attributes from an external source.
     * @return array Returns the user data and SSO ID as an array.
     */
    public function siteAssigner(){
        // Request Site: Production && Staging
        $site_production_request = 'https://requests.together.nbcuni.com/';
        $site_staging_request = 'https://stage.requests.together.nbcuni.com/';
        $site_local_request = 'https://requests.together.nbcudev.local/';
        // Talentroom Site: Production && Staging
        $site_production_talentroom = 'https://talentroom.nbcuni.com/';
        $site_staging_talentroom = 'https://stage.talentroom.nbcuni.com/';
        $site_local_talentroom = 'https://talentroom.nbcudev.local/';
        // Together Site: Production && Staging
        $site_production_together = 'https://together.nbcuni.com/';
        $site_staging_together = 'https://stage.together.nbcuni.com/';
        $site_local_together = 'https://together.nbcudev.local/';
        // Blog ID for each site.
        $blog_id_together = 3;
        $blog_id_talent = 7;
        $blog_id_request = 6;
        // Route Domain for each site.
        $site_production_route_domain = ".nbcuni.com";
        $site_staging_route_domain = ".nbcuni.com";
        $site_local_route_domain = ".nbcudev.local";
        return [ $site_production_request, $site_staging_request, $site_local_request, $site_production_talentroom , $site_staging_talentroom, $site_local_talentroom , $site_production_together, $site_staging_together, $site_local_together, $blog_id_together , $blog_id_talent , $blog_id_request , $site_production_route_domain , $site_staging_route_domain , $site_local_route_domain ]; // Return multiple variables as an array
    }

    public function getEnvironment($server_name) {
        if (stristr($server_name, 'local')) return 'local';
        if (stristr($server_name, 'stage')) return 'stage';
        return 'production';
    }

    /**
     * Assigns user attributes after validating and handling cases where certain attributes might be missing.
     *
     * @param array $attributes The array of user attributes from an external source.
     * @return array Returns the user data and SSO ID as an array.
     */
    public function attributesAssigner($attributes){
        if(empty($attributes)) {
            die("No attributes.");
        }
        // Check for email and other attributes, handling possible casing variations
        $email = !empty($attributes["email"][0]) ? $attributes["email"][0] : $attributes["Email"][0];
        $firstname = !empty($attributes["firstname"][0]) ? $attributes["firstname"][0] : $attributes["FirstName"][0];
        $lastname = !empty($attributes["lastname"][0]) ? $attributes["lastname"][0] : $attributes["LastName"][0];
        $account_status = $attributes["accountstatus"][0] ?? 'active'; // Fallback to empty if not provided
        $sso_id = !empty($attributes["uid"][0]) ? $attributes["uid"][0] : $attributes["UID"][0];
        $jobtitle = $attributes["jobtitle"][0] ?? '';
        $phone = $attributes["telephonenumber"][0] ?? '';
        // Create user data array
        $user_data = [
            "firstname" => $firstname,
            "lastname" => $lastname,
            "company" => "NBCUniversal", // Fixed company name
            "title" => $jobtitle,
            "email" => $email,
            "phone" => $phone,
            "suppress_email" => true,
            "account_status" => $account_status,
            'user_confirmed' => 'Y' // User confirmed flag
        ];
        return [ $user_data, $sso_id ]; // Return multiple variables as an array
    }

    /**
     * Manages existing users' login process and updates their information.
     *
     * @param array $user_data User data to update.
     * @param string $sso_id The SSO ID for the user.
     * @return void
     */
    public function existingUserFlow($user_data, $sso_id) {
        $email     = $user_data['email'];
        $firstname = $user_data['firstname'];
        $lastname  = $user_data['lastname'];
        $jobtitle  = $user_data['title'];
        $phone     = $user_data['phone'];
        $user_manager = new UserManager();
        $existing_user = get_user_by('email', $email);
        if (!$existing_user) {
            return; // If no user found, stop the execution
        }
        $user_id = $existing_user->ID;
        // Log in user and redirect
        $user_manager->login($user_id, true, 'nbcuni-sso-existing');
        // Update user info if any data is different
        $needs_update = false;
        $user_update_data = [
            'ID'         => $user_id,
            'first_name' => $firstname,
            'last_name'  => $lastname
        ];
        if (strcasecmp($firstname, $existing_user->first_name) !== 0 ||
            strcasecmp($lastname, $existing_user->last_name) !== 0 ||
            strcasecmp($jobtitle, $existing_user->user_title) !== 0 ||
            strcasecmp($phone, $existing_user->user_phone) !== 0) {
            $needs_update = true;
        }
        if ($needs_update) {
            wp_update_user($user_update_data);
            if (!empty($jobtitle)) {
                update_user_meta($user_id, 'user_title', $jobtitle);
            }
            if (!empty($phone)) {
                update_user_meta($user_id, 'user_phone', $phone);
            }
        }
        // Update SSO ID if not already set
        if (empty(get_user_meta($user_id, 'nbcu_sso_id', true))) {
            update_user_meta($user_id, 'nbcu_sso_id', $sso_id);
        }
    }


    public function newUserFlow($user_data, $sso_id){
        $user_manager = new UserManager();
        $user_id = $user_manager->add_new_user($user_data);
        if(!empty($user_id)){
            update_user_meta($user_id, "user_company", $user_data["company"]);
            update_user_meta($user_id, "nbcu_sso_id", $sso_id);
            update_user_meta($user_id, "user_title", $user_data["title"]);
            update_user_meta($user_id, "user_phone", $user_data["phone"]);
        }
        return $user_id;
    }


    public function processWhitelist($user_id, $user_data) {
            // Check if the whitelist cookie is set
            if (isset($_COOKIE["wl-register"])) {
                $whitelist = $_COOKIE["wl-register"];
                $user_manager = new UserManager();
                $whitelist_props = $user_manager->get_restricted_whitelist_props('id', $whitelist);
                // Process whitelist properties if they exist
                if (!empty($whitelist_props)) {
                    // Save restricted whitelist to user meta
                    $existing_restricted_whitelists = get_user_meta($user_id, "restricted_whitelist", true);
                    if (empty($existing_restricted_whitelists) || is_string($existing_restricted_whitelists)) {
                        $existing_restricted_whitelists = [];
                    }
                    $existing_restricted_whitelists[] = $whitelist_props['unique_id'];
                    update_user_meta($user_id, 'restricted_whitelist', $existing_restricted_whitelists);
                    // Send approved email
                    if (!empty($whitelist_props['approved_email_template'])) {
                        $alt_email = $user_manager->build_alternate_email_path($whitelist_props['approved_email_template']);
                        update_user_meta($user_id, 'registration_sent_email', $alt_email);
                    }
                    $custom_subject = !empty($whitelist_props['approved_email_subject']) ? $whitelist_props['approved_email_subject'] : false;
                    $user_manager->log($user_id, "Together approval email sent");
                    // Save email to exclusive whitelist
                    if (!empty($whitelist_props['exclusive_whitelist'])) {
                        $user_manager->add_email_to_exclusive_whitelist($whitelist_props['exclusive_whitelist'], $user_data['email']);
                }
            }
        }
    }

    public function userFlowLogout($redirect_url){
        // Destroy the current user session
        wp_destroy_current_session();
        // Clear the authentication cookies
        wp_clear_auth_cookie();
        // Optionally clear user cache or any custom session data if necessary
        wp_cache_delete(get_current_user_id(), 'users');
        // Trigger any custom logout actions if needed (e.g., analytics, logging)
        do_action('wp_logout');
        // Safely redirect to the Single Sign-On logout URL
        wp_safe_redirect($redirect_url);
        // Ensure the script execution stops
        exit;
    }


    public function userFlowProcessor($attributes) {
        $mo_helper = new RonikMoHelper();
        $mo_helper_redirect = new RonikMoHelperRedirect();
        $user_manager = new UserManager();

        // error_log(print_r($attributes, true));

        // Assign attributes
        list($user_data, $sso_id) = $mo_helper->attributesAssigner($attributes);
        // Check if user exists
        $user_exists = get_user_by("email", $user_data['email']);
        if ($user_exists) {
            $user_id = $user_exists->ID;
            $mo_helper->existingUserFlow($user_data, $sso_id);
            // error_log(print_r('user exists' , true));
        } else {
            $user_id = $mo_helper->newUserFlow($user_data, $sso_id);
            // error_log(print_r('new user' , true));
        }
        // error_log(print_r('User ID: '.$user_id , true));
        // Handle post-login redirect
        // $post_login_redirect = $mo_helper_redirect->handleUserPostLoginRedirect($user_id);
        $post_login_redirect = $this->loginDetection($user_id);

        error_log(print_r('Post Login Redirect: '. $post_login_redirect , true));
        // Process whitelist
        $mo_helper->processWhitelist($user_id, $user_data);
        // error_log(print_r(' processWhitelist'  , true));
        // Confirm user and log in
        $user_manager->confirm_user($user_id);
        // error_log(print_r(' confirm_user'  , true));
        $user_manager->login($user_id, true, 'nbcuni-sso-new');
        // error_log(print_r('login '  , true));
        // error_log(print_r($user_id  , true));
        // Validate redirect URL before redirecting
        if (filter_var($post_login_redirect, FILTER_VALIDATE_URL) || strpos($post_login_redirect, '/') === 0) {
            // error_log(print_r( $post_login_redirect  , true));
            return $post_login_redirect;
        } else {
            // Additional check and sanitization
            if (is_string($post_login_redirect)) {
                // Sanitize the string as plain text
                $sanitized_redirect = htmlspecialchars($post_login_redirect, ENT_QUOTES, 'UTF-8');
                return esc_url(home_url($sanitized_redirect)); // Return the sanitized text
            }

            return 'invalid-redirect'; // If not a string, return 'invalid-redirect'
        }
    }



    public function loginDetection($user_id){
        // Helper Guide
        $helper = new RonikHelper;
        $mo_helper_cookie_processor = new RonikMoHelperCookieProcessor();

        if($user_id){
            $res_sso_post_login_redirect_data = $mo_helper_cookie_processor->cookieSsoFetcher('sso_post_login_redirect_data');

            error_log(print_r('loginDetection', true));
            error_log(print_r($res_sso_post_login_redirect_data, true));


            if ($res_sso_post_login_redirect_data !== 'cookieSsoFetcher invalid') {
                sleep(5);
                // Construct the base redirect URL
                if (!empty($res_sso_post_login_redirect_data['site_origin'])) {
                    // Get the redirect URL from the data
                    $redirect_url = esc_url_raw(
                        $res_sso_post_login_redirect_data['site_origin'] .
                        ($this->removeLeadingSlash($res_sso_post_login_redirect_data['redirect_url']) ?? '') // Append redirect_url if it exists
                    );
                    // Check if the redirect_url already contains a query string
                    $separator = (strpos($redirect_url, '?') === false) ? '?' : '&';
                    // Construct the login URL with the sso-rk-log parameter
                    $login_url = $redirect_url . $separator . "sso-rk-log=" . $helper->ronik_encrypt_data_meta($user_id);
                    error_log(print_r('processSsoGet time_frame POST', true));
                    return $login_url;
                }
            }
        }
    }



    private function removeLeadingSlash($url) {
        return ltrim($url, '/');
    }
}
