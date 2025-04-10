<?php
class RonikAuthProcessor
{
    // Check for the Authorization pages if provided URL is a Authorization page.
    public function urlCheckNoAuthPage($urlTarget)
    {
        // Lets check for the auth pages..
        if (
            !str_contains($urlTarget, 'ronikdesigns_admin_logout')
            && !str_contains($urlTarget, '/sw.js')
            && !str_contains($urlTarget, '/mfa')
            && !str_contains($urlTarget, '/2fa')
            && !str_contains($urlTarget, '/auth')
            && !str_contains($urlTarget, '/password')
        ) {
            return true;
        } else {
            return false;
        }
    }


    public function urlAuthPageEnabled($urlTarget)
    {
        $f_auth = get_field('mfa_settings', 'options');
        $f_validation = array();
        if ($f_auth['auth_page_enabled']) {
            $f_slug_array = array('2fa', 'auth', 'mfa');
            foreach ($f_auth['auth_page_enabled'] as $auth_page_enabled) {
                $postId = $auth_page_enabled['page_selection'][0];
                $slug = basename(get_permalink($postId));
                $f_slug_array[] = $slug;
            }
            if (count($f_slug_array) !== 0) {
                foreach ($f_slug_array as $f_slug) {
                    // Loose check...
                    if (str_contains($f_slug, trim($urlTarget, "/"))) {
                        // return true;
                        $f_validation[] = 'valid';
                    } else {
                        // return false;
                        $f_validation[] = 'false';
                    }
                }
            } else {
                // return false;
                $f_validation[] = 'false';
            }
        } else {
            // return false;
            $f_validation[] = 'false';
        }
        if (in_array("valid", $f_validation)) {
            return 'VALID';
        }
        return false;
    }


    public function urlCheckNoWpPage($urlTarget, $customUrlArray = false)
    {
        $f_redirect_wp_slugs = array(
            '/favicon.ico',
            '/wp-content/',
            '/wp-admin/admin-post.php',
            '/wp-admin/admin-ajax.php',
            '/wp-admin/admin-ajax.php?action=ronikdesigns_admin_logout',
        );
        if ($customUrlArray) {
            $f_redirect_wp_slugs = array_merge($f_redirect_wp_slugs, $customUrlArray);;
        }
        if (!in_array($urlTarget, $f_redirect_wp_slugs)) {
            return true;
        } else {
            return false;
        }
    }


    public function urlCheckWpDashboard($urlTarget, $customUrlArray = false)
    {
        $f_auth = get_field('mfa_settings', 'options');

        if ($f_auth['auth_page_enabled_wpadmin']) {
            $f_redirect_wp_slugs = array(
                '/wp-admin/import.php',
                '/wp-admin/export.php' .
                    '/wp-admin/site-health.php',
                '/wp-admin/export-personal-data.php',
                '/wp-admin/erase-personal-data.php',
                '/wp-admin/ms-delete-site.php',
                '/wp-admin/options-',
                '/wp-admin/admin.php?page=',
                '/wp-admin/upload.php',
                '/wp-admin/tools.php',
                '/wp-admin/plugins.php',
                '/wp-admin/themes.php',
                '/wp-admin/my-sites.php',
                '/wp-admin/index.php',
                '/wp-admin/edit.php?post_type=',
                '/wp-admin/post.php?post=',
                '/wp-admin/users.php',
                '/wp-admin/user-new.php',
            );
        } else {
            $f_redirect_wp_slugs = array();
        }
        if ($customUrlArray) {
            $f_redirect_wp_slugs = array_merge($f_redirect_wp_slugs, $customUrlArray);;
        }
        foreach ($f_redirect_wp_slugs as $wp_slugs) {
            if (str_contains($urlTarget, $wp_slugs)) {
                return true;
            }
        }
        return false;
    }



    public function userTrackerActions($urlTarget)
    {
        // Critical We do not want to log password-reset otherwise loop error will occur,
        if (strpos($urlTarget, '/password-reset') !== false) {
            return;
        }

        // PHP User Click Actions
        $user_id = get_current_user_id();
        $meta_key = 'user_tracker_actions';
        update_user_meta($user_id, $meta_key, array(
            'timestamp' => time(),
            'url' => urlencode($urlTarget)
        ));
    }




    public function ronik_authorize_success_redirect_path()
    {
        $user_id = get_current_user_id();
        $meta_key = 'user_tracker_actions';
        $userclick_actions = get_user_meta($user_id, $meta_key, true);

        // ✅ If a saved redirect path exists, go there
        if ($userclick_actions && isset($userclick_actions['url'])) {
            wp_redirect(esc_url(urldecode($userclick_actions['url'])));
            exit;
        }

        // 🏠 Fallback to homepage
        wp_redirect(esc_url(home_url()));
        exit;
    }








    // A custom function that will prevent infinite loops.
    // This is the brain of the entire application! Edit with care!
    public function ronikRedirectLoopApproval($dataUrl, $cookieName)
    {
        global $post;
        $f_auth = get_field('mfa_settings', 'options'); // ✅ Get ACF config for MFA/2FA
        $helper = new RonikHelper;
        $authProcessor = new RonikAuthProcessor;

        // 🔐 If user is NOT logged in, we redirect to home unless URL is already in approved reUrl
        if (!is_user_logged_in()) {
            foreach ($dataUrl['reUrl'] as $value) {
                if (str_contains($_SERVER['REQUEST_URI'], $value)) {
                    wp_redirect(esc_url(home_url()));
                    exit;
                }
            }
        } else {
            // ✅ Add slugs of all valid auth pages to prevent looping
            if ($f_auth['auth_page_enabled']) {
                $f_id_array = array();
                foreach ($f_auth['auth_page_enabled'] as $auth_page_enabled) {
                    $postId = $auth_page_enabled['page_selection'][0];
                    $slug = basename(get_permalink($postId));
                    $f_id_array[] = $postId;

                    // 🧼 Normalize slugs to begin with `/` if needed
                    $f_url = strstr($slug, '/') ? strstr($slug, '/') : '/';
                    array_push($dataUrl['reUrl'], $f_url);
                }

                // ✅ Validate the current post/page ID against the list of auth pages
                if ($post && property_exists($post, 'post_title')) {
                    if ($post->post_title === 'mfa' || $post->post_title === '2fa') {
                        // 🔄 Redirect if user chose SMS but is on MFA page (or vice versa)
                        $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                        if ($get_auth_status === 'auth_select_sms' && $post->post_title === 'mfa') {
                            $helper->ronikdesigns_write_log_devmode('MFA Hit', 'low', 'auth_mfa');
                            wp_redirect(esc_url(home_url()));
                            exit;
                        }
                        if ($get_auth_status === 'auth_select_mfa' && $post->post_title === '2fa') {
                            $helper->ronikdesigns_write_log_devmode('2fa Hit', 'low', 'auth_2fa');
                            wp_redirect(esc_url(home_url()));
                            exit;
                        }
                    } else {
                        // 🚫 Not on a valid auth page: check if page ID is not in approved list
                        if ($post && property_exists($post, 'ID') && !in_array($post->ID, $f_id_array)) {
                            $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);

                            // 👤 If no auth has been selected, force redirect to auth page
                            if (empty($get_auth_status) || $get_auth_status === 'none') {
                                $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                            }

                            // 📱 If SMS was selected but is missing config, redirect
                            if ($get_auth_status === 'auth_select_sms-missing') {
                                $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                            }

                            // 🔐 If MFA is selected but unregistered, redirect
                            if ($get_auth_status === 'auth_select_mfa') {
                                $mfa_validation = get_user_meta(get_current_user_id(), 'mfa_validation', true);
                                if (!$mfa_validation || $mfa_validation === 'not_registered') {
                                    $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                                }
                            }

                            return false;
                        }
                    }
                } else {
                    // 🛑 404 page or unknown post context
                    if (is_404()) return false;

                    if ($authProcessor->urlCheckNoWpPage($_SERVER['REQUEST_URI'])) {
                        $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                        if (empty($get_auth_status) || $get_auth_status === 'none') {
                            $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                        } else {
                            // 🧠 If dashboard access is blocked, force redirect to auth page
                            if ($authProcessor->urlCheckWpDashboard($_SERVER['REQUEST_URI'])) {
                                if ($authProcessor->urlCheckNoAuthPage($_SERVER['REQUEST_URI'])) {
                                    // 👣 Track where user came from
                                    $user_id = get_current_user_id();
                                    update_user_meta($user_id, 'user_tracker_actions', array(
                                        'timestamp' => time(),
                                        'url' => urlencode($_SERVER['REQUEST_URI'])
                                    ));
                                }
                                $helper->ronikdesigns_write_log_devmode('WP-ADMIN BLOCKED: redirecting to home', 'low', 'auth');
                                wp_redirect(esc_url(home_url($dataUrl['reDest'])));
                                exit;
                            }
                        }
                        return false;
                    }
                }
            }

            // 🌀 If no other condition matches, initiate redirect loop fallback
            $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
        }
    }


    // Pretty much a cool function that helps with redirect without causing the crazy redirect loops.
    public function ronikLooperDooper($dataUrl, $cookieName)
    {
        $authProcessor = new RonikAuthProcessor;

        // 🔁 Loop through all redirect URLs and check if current request is *not* already on one of them
        foreach ($dataUrl['reUrl'] as $value) {
            if (!str_contains($_SERVER['REQUEST_URI'], $value)) {
                // 🧼 Confirm it's not a WP page (e.g., /wp-admin/admin-ajax.php)
                if ($authProcessor->urlCheckNoWpPage($_SERVER['REQUEST_URI'])) {
                    // 🚫 Prevent redirecting to the same page again (avoids infinite loop)
                    if (get_permalink() !== home_url($dataUrl['reDest'])) {
                        // ✅ Confirm we’re not redirecting to an auth page itself
                        if ($authProcessor->urlCheckNoAuthPage($_SERVER['REQUEST_URI'])) {
                            // 🧠 Save the user's point of origin so they can return here after auth
                            $user_id = get_current_user_id();
                            update_user_meta($user_id, 'user_tracker_actions', array(
                                'timestamp' => time(),
                                'url' => urlencode($_SERVER['REQUEST_URI'])
                            ));
                        }

                        // ⏳ Short delay to stabilize redirect timing
                        sleep(.5);

                        // 🔁 Finally, redirect to desired destination
                        wp_redirect(esc_url(home_url($dataUrl['reDest'])));
                        exit();
                    }
                }
            }
        }
    }
















    // This function block is responsible for detecting the time expiration of the 2fa on page specific pages.
    public function ronikdesigns_redirect_registered_2fa()
    {
        // Helper Guide
        $helper = new RonikHelper;
        $authProcessor = new RonikAuthProcessor;

        $get_registration_status = get_user_meta(get_current_user_id(), 'sms_2fa_status', true);
        $sms_code_timestamp = get_user_meta(get_current_user_id(), 'sms_code_timestamp', true);
        $f_mfa_settings = get_field('mfa_settings', 'options');

        $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
        if (!$get_phone_number) {
            update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms-missing');
            $f_value['auth-select'] = "2fa";
            // Redirect Magic, custom function to prevent an infinite loop.
            $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/auth/?auth-select=2fa');
            $dataUrl['reDest'] = '/auth/?auth-select=2fa';
            $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
        }

        if (isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time']) {
            $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
        } else {
            $f_auth_expiration_time = 30;
        }
        // Redirect Magic, custom function to prevent an infinite loop.
        $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/');
        $dataUrl['reDest'] = '/2fa/';



        $current_uri = $_SERVER['REQUEST_URI'];
        $is_on_2fa_page = str_contains($current_uri, '/2fa');
        $is_on_mfa_page = str_contains($current_uri, '/mfa');

        if (!$is_on_2fa_page && !$is_on_mfa_page) {
            // Normal redirect logic

            // We check the current page id and also the page title of the 2fa.
            if (!str_contains($_SERVER['REQUEST_URI'], '2fa') && !str_contains($_SERVER['REQUEST_URI'], 'mfa')) {
                // Check if user has sms_2fa_status if not add secret.
                if (!$get_registration_status) {
                    update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                }
                // Check if sms_2fa_status is not equal to unverified.
                if (($get_registration_status !== 'sms_2fa_unverified')) {
                    $past_date = strtotime((new DateTime())->modify('-' . $f_auth_expiration_time . ' minutes')->format('d-m-Y H:i:s'));
                    // If past date is greater than current date. We reset to unverified & start the process all over again.
                    if ($past_date > $sms_code_timestamp) {
                        update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                        update_user_meta(get_current_user_id(), 'sms_2fa_secret', 'invalid');
                        update_user_meta(get_current_user_id(), 'sms_code_timestamp', 0);
                        $helper->ronikdesigns_write_log_devmode('Forcing user to restart 2fa due to expiration', 'low', 'auth_2fa');
                        if (!str_contains($_SERVER['REQUEST_URI'], '/wp-admin/')) {
                            $helper->ronikdesigns_write_log_devmode('RONIK NEXT FIX 1! KM might be fixed by ignoring the wp-admin request uri', 'low', 'auth_2fa');
                            // update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                            // Takes care of the redirection logic
                            $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                        }
                    } else {
                        if (str_contains($_SERVER['REQUEST_URI'], '/2fa/')) {
                            // Lets block the user from accessing the 2fa if already authenticated.
                            $dataUrl['reUrl'] = array('/');
                            $dataUrl['reDest'] = '/';
                            $helper->ronikdesigns_write_log_devmode('RONIK NEXT FIX!', 'low', 'auth_2fa');
                            $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                        }
                    }
                } else {
                    $helper->ronikdesigns_write_log_devmode('RONIK NEXT FIX 2!', 'low', 'auth_2fa');
                    // update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                    // Takes care of the redirection logic
                    $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                }
            } else {
                // Lets block the user from accessing the 2fa if already authenticated.
                $dataUrl['reUrl'] = array('/');
                $dataUrl['reDest'] = '/';
                $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
            }
        } elseif ($is_on_2fa_page && $get_registration_status === 'sms_2fa_unverified') {
            // 🛑 STOP redirect if already on 2fa page and user is unverified
            return;
        }
    }



    // This function block is responsible for detecting the time expiration of the MFA on page specific pages.
    public function ronikdesigns_redirect_non_registered_mfa()
    {
        $helper = new RonikHelper;
        $authProcessor = new RonikAuthProcessor;

        $mfa_status = get_user_meta(get_current_user_id(), $key = 'mfa_status', true);
        $mfa_validation = get_user_meta(get_current_user_id(), 'mfa_validation', true);

        $f_mfa_settings = get_field('mfa_settings', 'options');
        if (isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time']) {
            $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
        } else {
            $f_auth_expiration_time = 30;
        }
        // Redirect Magic, custom function to prevent an infinite loop.
        $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/', '/mfa/');
        $dataUrl['reDest'] = '/mfa/';

        if (ronikdesigns_get_page_by_title('mfa')) {
            // Check if user has mfa_status if not add secret.
            if (!$mfa_status) {
                // add_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
            }
            // Check if mfa_status is not equal to unverified.
            if (($mfa_status !== 'mfa_unverified')) {
                $past_date = strtotime((new DateTime())->modify('-' . $f_auth_expiration_time . ' minutes')->format('d-m-Y H:i:s'));
                // If past date is greater than current date. We reset to unverified & start the process all over again.
                if ($past_date > $mfa_status) {
                    // if (!str_contains($_SERVER['REQUEST_URI'], '/wp-admin/')) {
                    //     error_log(print_r( 'RONIK NEXT FIX 0! KM might be fixed by ignoring the wp-admin request uri', true));
                    //     // Takes care of the redirection logic
                    //     // ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                    // }
                } else {
                    if (str_contains($_SERVER['REQUEST_URI'], '/mfa/')) {
                        // if($_SERVER['REQUEST_URI'] == '/mfa/'){
                        // Lets block the user from accessing the 2fa if already authenticated.
                        $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/', '/mfa/');
                        $dataUrl['reDest'] = '/';

                        if ($mfa_validation !== 'valid') {
                            $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
                        }
                    }
                }
            } else {
                // update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                // Takes care of the redirection logic
                // Redirect Magic, custom function to prevent an infinite loop.
                $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/');
                $dataUrl['reDest'] = '/mfa/';
                $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
            }
        }
    }
}
