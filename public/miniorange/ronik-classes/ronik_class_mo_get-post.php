<?php
class RonikMoHelperGetPostManager {
    public function processSsoGet($user_id, $site_url, $site_mapping, $environment){
        $mo_helper = new RonikMoHelper();
        $mo_cookie_manager = new RonikMoHelperCookieManager();
        $mo_helper_cipher = new RonikMoHelperCipher();

        // Check if the 'talent' parameter exists in the URL query string
        if (!empty($_GET['talent'])) {
            if($user_id){
                // For Talent Room redirection
                // Create the login URL for Talent Room, encrypting the login request
                $talent_site_url = $site_mapping[$environment]['talentroom']; // Use 'talentroom' URL
                // $login_url = $mo_helper_cipher->encryptLoginRequest('1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=', $talent_site_url . "wp-admin/admin-ajax.php?action=ronikdesign_miniorange_ajax&sso-rk-log=", $user_id);
                $login_url = $mo_helper_cipher->encryptLoginRequest('1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=', $talent_site_url . "?sso-rk-log=", $user_id);
    
                // Set the redirect cookies and return the login URL
                $mo_cookie_manager->setRedirectCookies($login_url, "talent-room_$environment");
                return $login_url;
            } else {

            }
        } elseif (!empty($_GET['r']) || !empty($_GET['wl-register'])) {
            // For Together site redirection
            // Get the 'r' or 'wl-register' parameter for redirection
            $redirect = $this->removeLeadingSlash($_GET['r'] ?? $_GET['wl-register']);
            // Set the redirect cookies and return the together URL with the redirect path
            $mo_cookie_manager->setRedirectCookies($site_url . $redirect, "together_$environment");
            return $site_url . $redirect;
        }
    }

    // This is more for demo debuging purposes. Or if client wants to not depend on cookies. Proof of concept!
    public function processSsoPostConvertParams() {
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

    private function removeLeadingSlash($url) {
        return ltrim($url, '/');
    }
}