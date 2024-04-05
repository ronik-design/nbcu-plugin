<?php
// Shared Functionality between the two authentication.
# Include packages
require_once(dirname(__DIR__, 2) . '/vendor/autoload.php');
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;


$f_auth = get_field('mfa_settings', 'options');
$f_enable_mfa_settings = get_option('options_mfa_settings_enable_mfa_settings');
$f_enable_2fa_settings = get_option('options_mfa_settings_enable_2fa_settings');

// Frontend Creation of Authentication Pages.
    // This basically auto create the page it doesnt already exist. It will also auto assign the specific template.
    function ronikdesigns_add_custom_auth_page() {
        $f_enable_mfa_settings = get_option('options_mfa_settings_enable_mfa_settings');
        $f_enable_2fa_settings = get_option('options_mfa_settings_enable_2fa_settings');
        // Check if MFA && 2fa is enabled.
        if( isset($f_enable_mfa_settings) && isset($f_enable_2fa_settings) ){
            if($f_enable_mfa_settings && $f_enable_2fa_settings){
                if( !ronikdesigns_get_page_by_title('auth') ){
                    // Create post object
                    $my_post = array(
                        'post_title'    => wp_strip_all_tags( 'auth' ),
                        'post_content'  => 'auth',
                        'post_status'   => 'publish',
                        'post_author'   => 64902,
                        'post_type'     => 'page',
                        // Assign page template
                        'page_template'  => dirname( __FILE__ , 2).'/authorization/custom-templates/auth-template.php'
                    );
                    // Insert the post into the database
                    wp_insert_post( $my_post );
                }
            }
        }
        // Check if 2fa is enabled.
        if(isset($f_enable_2fa_settings) ){
            if($f_enable_2fa_settings){
                if( !ronikdesigns_get_page_by_title('2fa') ){
                    // Create post object
                    $my_post = array(
                        'post_title'    => wp_strip_all_tags( '2fa' ),
                        'post_content'  => '2fa',
                        'post_status'   => 'publish',
                        'post_author'   => 64902,
                        'post_type'     => 'page',
                        // Assign page template
                        'page_template'  => dirname( __FILE__ , 2).'/authorization/custom-templates/2fa-template.php'
                    );
                    // Insert the post into the database
                    wp_insert_post( $my_post );
                }
            }
        }
        // Check if MFA is enabled.
        if(isset($f_enable_mfa_settings)){
            if($f_enable_mfa_settings){
                if( !ronikdesigns_get_page_by_title('mfa') ){
                    // Create post object
                    $my_post = array(
                        'post_title'    => wp_strip_all_tags( 'mfa' ),
                        'post_content'  => '2fa',
                        'post_status'   => 'publish',
                        'post_author'   => 64902,
                        'post_type'     => 'page',
                        // Assign page template
                        'page_template'  => dirname( __FILE__ , 2).'/authorization/custom-templates/mfa-template.php'
                    );
                    // Insert the post into the database
                    wp_insert_post( $my_post );
                }
            }
        }
    }
    ronikdesigns_add_custom_auth_page();

    // If page template assignment fails we add a backup plan to auto assing the page template.
    function ronikdesigns_reserve_auth_page_template( $page_template ){
        // If the page is auth we add our custom ronik mfa-template to the page.
        if ( is_page( 'auth' ) ) {
            $page_template =  dirname( __FILE__ , 2).'/authorization/custom-templates/auth-template.php';
        }
        // If the page is 2fa we add our custom ronik 2fa-template to the page.
        if ( is_page( '2fa' ) ) {
            $page_template =  dirname( __FILE__ , 2).'/authorization/custom-templates/2fa-template.php';
        }
        // If the page is 2fa we add our custom ronik mfa-template to the page.
        if ( is_page( 'mfa' ) ) {
            $page_template =  dirname( __FILE__ , 2).'/authorization/custom-templates/mfa-template.php';
        }
        return $page_template;
    }
    add_filter( 'template_include', 'ronikdesigns_reserve_auth_page_template', 99 );


    // A custom function that will prevent infinite loops.
    // This is the brain of the entire application! Edit with care!
    function ronikRedirectLoopApproval($dataUrl, $cookieName){
        global $post;
        $f_auth = get_field('mfa_settings', 'options');
        $authChecker = new RonikAuthChecker;
        // Helper Guide
        $helper = new RonikHelper;

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
                                $helper->ronikdesigns_write_log_devmode('MFA Hit', 'low');
                                wp_redirect( esc_url(home_url()) );
                                exit;
                            }
                        }
                        if($get_auth_status == 'auth_select_mfa'){
                            if($post->post_title == '2fa'){
                                $helper->ronikdesigns_write_log_devmode('2fa Hit', 'low');
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
                                    ronikLooperDooper($dataUrl, $cookieName);
                                }
                                return false;
                            } else {
                                // This checks if the user is inside wp-admin
                                // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
                                if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin') && !str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                                    return false;
                                }
                            }
                        } else {
                            // This checks if the user is inside wp-admin
                            // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
                            if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin') && !str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                                return false;
                            }
                        }
                    }
                } else {
                    // CHECK for 404 if so return false.
                    if(is_404()){
                        return false;
                    }
                    // This checks if the user is inside wp-admin
                    // if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
                    if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin') && !str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                        $get_auth_status = get_user_meta(get_current_user_id(), 'auth_status', true);
                        if(($get_auth_status == 'none') || !isset($get_auth_status) || !$get_auth_status){
                            // First lets loop through all the provided urls.
                            ronikLooperDooper($dataUrl, $cookieName);
                        } else{

                            // Checks for the wpDashboard if so we redirect!!!!!
                            if( $authChecker->urlCheckWpDashboard($_SERVER['REQUEST_URI']) ){                    
                                if( $authChecker->urlCheckNoAuthPage($_SERVER['REQUEST_URI']) ){
                                    // PHP User Click Actions
                                    $user_id = get_current_user_id();
                                    $meta_key = 'user_tracker_actions';
                                    update_user_meta( $user_id, $meta_key, array(
                                        'timestamp' => time(),
                                        'url' => urlencode($_SERVER['REQUEST_URI'])
                                    ));
                                }
                                $helper->ronikdesigns_write_log_devmode( 'THIS IS A WP-ADMIN DASHBOARD' , 'low');
                                wp_redirect( esc_url(home_url($dataUrl['reDest'])) );
                                exit();
                            } 
                        }
                        return false;
                    }
                }
            }


            // First lets loop through all the provided urls.
            ronikLooperDooper($dataUrl, $cookieName);
        }
    }

    // Pretty much a cool function that helps with redirect without causing the crazy redirect loops.
    function ronikLooperDooper($dataUrl, $cookieName){
        $authChecker = new RonikAuthChecker;

        // First lets loop through all the provided urls.
        foreach ($dataUrl['reUrl'] as $value) {
            // If value matches with the request_url
            if (!str_contains($_SERVER['REQUEST_URI'], $value)) {

                // // This is the last check for if request_url is admin-ajax. WE DO NOT WANT TO REDIRECT IN ADMIN-AJAX
                // if (!str_contains( $_SERVER['REQUEST_URI']  , '/wp-admin/admin-ajax.php')) {
                //     return;
                // }

                if( $authChecker->urlCheckNoWpPage($_SERVER['REQUEST_URI']) ){
                    // Lastly we check if the requested matches the permalink to prevent looping issues.
                    if(get_permalink() !== home_url($dataUrl['reDest'])){
                        if( $authChecker->urlCheckNoAuthPage($_SERVER['REQUEST_URI']) ){
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

    function ronik_authorize_success_redirect_path(){
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



    class RonikAuthChecker{
        // Pretty much we check for the Authorization pages if provided URL is a Authorization page.
        public function urlCheckNoAuthPage($urlTarget) {
            // Lets check for the auth pages..
            if (!str_contains($urlTarget, 'ronikdesigns_admin_logout') && !str_contains($urlTarget, '/sw.js') && !str_contains($urlTarget, '/mfa') && !str_contains($urlTarget, '/2fa') && !str_contains($urlTarget, '/auth')) {
                return true;
            } else {
                return false;
            }
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

        public function userTimeValidationAjaxJS($templateType){
            // Helper Guide
            $helper = new RonikHelper;
            $get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);

            if( ($get_auth_lockout_counter) > 6){
                $f_expiration_time = 3;
                $past_date = strtotime((new DateTime())->modify('-'.$f_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                if( $past_date > $get_auth_lockout_counter ){
                    // delete_user_meta(get_current_user_id(), 'auth_lockout_counter');
                } else { ?>
                    <script>
                        timeLockoutValidationChecker();
                        // This is critical we basically re-run the timeValidationAjax function every 30 seconds
                        function timeLockoutValidationChecker() {
                            console.log('Lets check the timeout. <?= $templateType; ?>');
                            // Lets trigger the validation on page load.
                            timeValidationAjax('invalid', 'invalid', 'valid', true);
                        }
                        setInterval(timeLockoutValidationChecker, (60000/2));

                        function timeValidationAjax( killValidation, timeChecker, timeLockoutChecker, autoChecker ){
                            jQuery.ajax({
                                type: 'POST',
                                url: wpVars.ajaxURL,
                                data: {
                                    action: 'ronikdesigns_admin_auth_verification',
                                    killValidation: killValidation,
                                    timeChecker: timeChecker,
                                    timeLockoutChecker: timeLockoutChecker,
                                    nonce: wpVars.nonce,
                                    autoChecker: 'valid',
                                    crypt: '<?= $helper->ronik_encrypt_data_meta(get_current_user_id()); ?>'
                                },
                                success: data => {
                                    if(data.success){
                                        console.log(data);
                                        if(data.data == 'reload'){
                                            setTimeout(() => {
                                                window.location.reload(true);
                                            }, 50);
                                        }
                                    } else{
                                        console.log('error');
                                        console.log(data);
                                        console.log(data.data);
                                        // window.location.reload(true);
                                    }
                                    console.log(data);
                                },
                                error: err => {
                                    console.log(err);
                                    // window.location.reload(true);
                                }
                            });
                        }
                    </script>
                    <!-- <div id="countdown"></div> -->
                    <script>
                        var timeleft = 60*3;
                        var downloadTimer = setInterval(function(){
                            if(timeleft <= 0){
                                clearInterval(downloadTimer);
                                // document.getElementById("countdown").innerHTML = "Reloading";
                                setTimeout(() => {
                                    window.location = window.location.pathname + "?sms-success=success";
                                }, 1000);
                            } else {
                                // document.getElementById("countdown").innerHTML = "Page will reload in: " + timeleft + " seconds";
                            }
                            timeleft -= 1;
                        }, 1000);
                    </script>
            <?php }
            } else {
                if( $templateType == 'mfa' ){
                    do_action('mfa-registration-page');
                } else {
                    do_action('2fa-registration-page');
                }
            }
        }
    }
