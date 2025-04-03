<?php
class RonikAuthProcessor{
    // Check for the Authorization pages if provided URL is a Authorization page.
    public function urlCheckNoAuthPage($urlTarget) {
        // Lets check for the auth pages..
        if (!str_contains($urlTarget, 'ronikdesigns_admin_logout')
            && !str_contains($urlTarget, '/sw.js')
            && !str_contains($urlTarget, '/mfa')
            && !str_contains($urlTarget, '/2fa')
            && !str_contains($urlTarget, '/auth')
        ) {
            return true;
        } else {
            return false;
        }
    }


    public function urlAuthPageEnabled($urlTarget) {
        $f_auth = get_field('mfa_settings', 'options');
        $f_validation = array();
        if($f_auth['auth_page_enabled']){
            $f_slug_array = array('2fa','auth','mfa');
            foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
                $postId = $auth_page_enabled['page_selection'][0];
                $slug = basename(get_permalink($postId));
                $f_slug_array[] = $slug;
            }
            if( count($f_slug_array) !== 0  ){
                foreach($f_slug_array as $f_slug){
                    // Loose check...
                    if (str_contains($f_slug, trim( $urlTarget, "/" ) )) {
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


    public function urlCheckNoWpPage($urlTarget, $customUrlArray=false) {
        $f_redirect_wp_slugs = array(
            '/favicon.ico',
            '/wp-content/',
            '/wp-admin/admin-post.php',
            '/wp-admin/admin-ajax.php',
            '/wp-admin/admin-ajax.php?action=ronikdesigns_admin_logout',
        );
        if( $customUrlArray ){
            $f_redirect_wp_slugs = array_merge($f_redirect_wp_slugs, $customUrlArray);;
        }
        if( !in_array($urlTarget, $f_redirect_wp_slugs) ){
            return true;
        } else {
            return false;
        }
    }


    public function urlCheckWpDashboard($urlTarget, $customUrlArray=false) {
        $f_auth = get_field('mfa_settings', 'options');

        if($f_auth['auth_page_enabled_wpadmin']){
            $f_redirect_wp_slugs = array(
                '/wp-admin/import.php',
                '/wp-admin/export.php'.
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
            $f_redirect_wp_slugs = array(
            );
        }
        if( $customUrlArray ){
            $f_redirect_wp_slugs = array_merge($f_redirect_wp_slugs, $customUrlArray);;
        }
        foreach($f_redirect_wp_slugs as $wp_slugs){
            if(str_contains( $urlTarget, $wp_slugs )){
                return true;
            }
        }
        return false;
    }



    public function userTrackerActions($urlTarget){
        // PHP User Click Actions
        $user_id = get_current_user_id();
        $meta_key = 'user_tracker_actions';
        update_user_meta( $user_id, $meta_key, array(
            'timestamp' => time(),
            'url' => urlencode($urlTarget)
        ));
    }




    public function ronik_authorize_success_redirect_path(){
        $user_id = get_current_user_id();
        $meta_key = 'user_tracker_actions';
        $userclick_actions = get_user_meta($user_id, $meta_key, true);

        if($userclick_actions){
            if( isset($userclick_actions['url']) ){
                wp_redirect( esc_url((urldecode($userclick_actions['url']))) );
                exit;
            } else {
                wp_redirect( esc_url(home_url()) );
                exit;
            }
        } else {
            wp_redirect( esc_url(home_url()) );
            exit;
        }
    }








    // A custom function that will prevent infinite loops.
    // This is the brain of the entire application! Edit with care!
    public function ronikRedirectLoopApproval($dataUrl, $cookieName){
        global $post;
        $f_auth = get_field('mfa_settings', 'options');
        // Helper Guide
        $helper = new RonikHelper;
        $authProcessor = new RonikAuthProcessor;

        // If user is not logged in we continue to redirect to home page.
        if(!is_user_logged_in()){
            // Prevent an infinite loop.
            foreach ($dataUrl['reUrl'] as $value) {
                if (str_contains($_SERVER['REQUEST_URI'], $value)) {
                    wp_redirect( esc_url(home_url()) );
                    exit;
                }
            }
        } else {
            // We add the slash to the reUrl to prevent infinite loops.
            if($f_auth['auth_page_enabled']){
                $f_id_array = array();
                foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
                    $postId = $auth_page_enabled['page_selection'][0];
                    $slug = basename(get_permalink($postId));
                    $f_id_array[] = $postId;
                    if(strstr($slug, '/')){
                        $f_url = strstr($slug, '/');
                    } else {
                        $f_url = '/';
                    }
                    array_push($dataUrl['reUrl'], $f_url);
                }
                // First lets check if the the property_exists.
                if( $post && property_exists($post, 'post_title') ){
                    // Lets get the current post title. & Check if the post title are NOT EQUAL to mfa || 2fa.
                    if($post->post_title == 'mfa' || $post->post_title == '2fa'){
                        $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                        if($get_auth_status == 'auth_select_sms'){
                            if($post->post_title == 'mfa'){
                                $helper->ronikdesigns_write_log_devmode('MFA Hit', 'low', 'auth_mfa');
                                wp_redirect( esc_url(home_url()) );
                                exit;
                            }
                        }
                        if($get_auth_status == 'auth_select_mfa'){
                            if($post->post_title == '2fa'){
                                $helper->ronikdesigns_write_log_devmode('2fa Hit', 'low', 'auth_2fa');
                                wp_redirect( esc_url(home_url()) );
                                exit;
                            }
                        }
                    } else {
                        // The magic part we check if any id are within the
                        // Lets check the id both ids dont match we kill the redirect.
                        if( $post && property_exists($post, 'ID') ){

                            if( !in_array($post->ID, $f_id_array) ){


                                $get_current_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                                if(empty($get_current_auth_status) || $get_current_auth_status == 'none'){
                                    // By adding the looperdooper we basically invoke the loop to the auth page.
                                    $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                                }
                                $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                                if( $get_auth_status == 'auth_select_sms-missing' ){
                                    $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                                } elseif( $get_auth_status == 'auth_select_mfa'){
                                    $mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);
                                    if( !$mfa_validation || ($mfa_validation == 'not_registered' )  ){
                                        $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                                    }
                                }
                                return false;
                            } else {
                                // This checks if the user is inside wp-admin
                                if( !$authProcessor->urlCheckNoWpPage($_SERVER['REQUEST_URI']) ){
                                    return false;
                                }
                                // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin') && !str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                                //     return false;
                                // }
                            }
                        } else {
                            // This checks if the user is inside wp-admin
                            if( !$authProcessor->urlCheckNoWpPage($_SERVER['REQUEST_URI']) ){
                                return false;
                            }
                            // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin') && !str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                            //     return false;
                            // }
                        }
                    }
                } else {
                    // CHECK for 404 if so return false.
                    if(is_404()){
                        return false;
                    }
                    // This checks if the user is inside wp-admin
                    // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
                    if( $authProcessor->urlCheckNoWpPage($_SERVER['REQUEST_URI']) ){
                    // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin') && !str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                        $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                        if(($get_auth_status == 'none') || !isset($get_auth_status) || !$get_auth_status){
                            // First lets loop through all the provided urls.
                            $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
                        } else{
                            // Checks for the wpDashboard if so we redirect!!!!!
                            if( $authProcessor->urlCheckWpDashboard($_SERVER['REQUEST_URI']) ){
                                if( $authProcessor->urlCheckNoAuthPage($_SERVER['REQUEST_URI']) ){
                                    // PHP User Click Actions
                                    $user_id = get_current_user_id();
                                    $meta_key = 'user_tracker_actions';
                                    update_user_meta( $user_id, $meta_key, array(
                                        'timestamp' => time(),
                                        'url' => urlencode($_SERVER['REQUEST_URI'])
                                    ));
                                }
                                $helper->ronikdesigns_write_log_devmode( 'THIS IS A WP-ADMIN DASHBOARD' , 'low', 'auth');
                                wp_redirect( esc_url(home_url($dataUrl['reDest'])) );
                                exit();
                            }
                        }
                        return false;
                    }
                }
            }
            // First lets loop through all the provided urls.
            $authProcessor->ronikLooperDooper($dataUrl, $cookieName);
        }
    }

    // Pretty much a cool function that helps with redirect without causing the crazy redirect loops.
    public function ronikLooperDooper($dataUrl, $cookieName){
        $authProcessor = new RonikAuthProcessor;

        // First lets loop through all the provided urls.
        foreach ($dataUrl['reUrl'] as $value) {
            // If value matches with the request_url
            if (!str_contains($_SERVER['REQUEST_URI'], $value)) {
                if( $authProcessor->urlCheckNoWpPage($_SERVER['REQUEST_URI']) ){
                    // Lastly we check if the requested matches the permalink to prevent looping issues.
                    if(get_permalink() !== home_url($dataUrl['reDest'])){
                        if( $authProcessor->urlCheckNoAuthPage($_SERVER['REQUEST_URI']) ){
                            // PHP User Click Actions
                            $user_id = get_current_user_id();
                            $meta_key = 'user_tracker_actions';
                            update_user_meta( $user_id, $meta_key, array(
                                'timestamp' => time(),
                                'url' => urlencode($_SERVER['REQUEST_URI'])
                            ));
                        }
                        // Pause server.
                        sleep(.5);
                        wp_redirect( esc_url(home_url($dataUrl['reDest'])) );
                        exit();
                    }
                }
            }
        }
    }















     // This function block is responsible for detecting the time expiration of the 2fa on page specific pages.
     public function ronikdesigns_redirect_registered_2fa() {
        // Helper Guide
        $helper = new RonikHelper;
        $authProcessor = new RonikAuthProcessor;

        $get_registration_status = get_user_meta(get_current_user_id(),'sms_2fa_status', true);
        $sms_code_timestamp = get_user_meta(get_current_user_id(),'sms_code_timestamp', true);
        $f_mfa_settings = get_field('mfa_settings', 'options');

        $get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
        if(!$get_phone_number){
            update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms-missing');
            $f_value['auth-select'] = "2fa";
            // Redirect Magic, custom function to prevent an infinite loop.
            $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/auth/?auth-select=2fa');
            $dataUrl['reDest'] = '/auth/?auth-select=2fa';
            $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
        }

        if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
            $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
        } else {
            $f_auth_expiration_time = 30;
        }
        // Redirect Magic, custom function to prevent an infinite loop.
        $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/');
        $dataUrl['reDest'] = '/2fa/';

        // We check the current page id and also the page title of the 2fa.
        if (!str_contains($_SERVER['REQUEST_URI'], '2fa') && !str_contains($_SERVER['REQUEST_URI'], 'mfa')) {
            // Check if user has sms_2fa_status if not add secret.
            if (!$get_registration_status) {
                update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
            }
            // Check if sms_2fa_status is not equal to unverified.
            if (($get_registration_status !== 'sms_2fa_unverified')) {
                $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                // If past date is greater than current date. We reset to unverified & start the process all over again.
                if($past_date > $sms_code_timestamp ){
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
        }else {
            // Lets block the user from accessing the 2fa if already authenticated.
            $dataUrl['reUrl'] = array('/');
            $dataUrl['reDest'] = '/';
            $authProcessor->ronikRedirectLoopApproval($dataUrl, "ronik-auth-reset-redirect");
        }
    }












    // This function block is responsible for detecting the time expiration of the MFA on page specific pages.
    public function ronikdesigns_redirect_non_registered_mfa() {
        $helper = new RonikHelper;
        $authProcessor = new RonikAuthProcessor;

        $mfa_status = get_user_meta(get_current_user_id(), $key = 'mfa_status', true);
        $mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);

        $f_mfa_settings = get_field('mfa_settings', 'options');
        if( isset($f_mfa_settings['auth_expiration_time']) || $f_mfa_settings['auth_expiration_time'] ){
            $f_auth_expiration_time = $f_mfa_settings['auth_expiration_time'];
        } else {
            $f_auth_expiration_time = 30;
        }
        // Redirect Magic, custom function to prevent an infinite loop.
        $dataUrl['reUrl'] = array('/wp-admin/admin-ajax.php', '/2fa/', '/mfa/');
        $dataUrl['reDest'] = '/mfa/';

        if(ronikdesigns_get_page_by_title('mfa')){
            // Check if user has mfa_status if not add secret.
            if (!$mfa_status) {
                // add_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
                update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');

            }
            // Check if mfa_status is not equal to unverified.
            if (($mfa_status !== 'mfa_unverified')) {
                $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                // If past date is greater than current date. We reset to unverified & start the process all over again.
                if($past_date > $mfa_status ){
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

                        if($mfa_validation !== 'valid' ){
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
