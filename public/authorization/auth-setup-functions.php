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
                                error_log(print_r('MFA Hit' , true));
                                wp_redirect( esc_url(home_url()) );
                                exit;
                            }
                        }
                        if($get_auth_status == 'auth_select_mfa'){
                            if($post->post_title == '2fa'){
                                error_log(print_r('2fa Hit' , true));
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
                                if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
                                    return false;
                                }
                            }
                        } else {
                            // This checks if the user is inside wp-admin
                            if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
                                return false;
                            }
                        }
                    }
                } else {
                    // This checks if the user is inside wp-admin
                    if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin')) {
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
        $f_redirect_wp_slugs = array(
            '/favicon.ico', 
            '/wp-admin/admin-post.php', 
            '/wp-admin/admin-ajax.php',
        );
        // First lets loop through all the provided urls.
        foreach ($dataUrl['reUrl'] as $value) {
            // If value matches with the request_url
            if (!str_contains($_SERVER['REQUEST_URI'], $value)) {
                // We dont want to redirect on the '/wp-admin/admin-post.php' or /wp-content/
                if (!str_contains($_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php') && !str_contains($_SERVER['REQUEST_URI'], '/wp-content/')) {
                    // Lastly we check if the requested matches the permalink to prevent looping issues.
                    if(get_permalink() !== home_url($dataUrl['reDest'])){
                        if( !in_array($_SERVER['REQUEST_URI'], $f_redirect_wp_slugs) ){
                            if( !str_contains($_SERVER['REQUEST_URI'], '2fa') && !str_contains($_SERVER['REQUEST_URI'], 'mfa') && !str_contains($_SERVER['REQUEST_URI'], 'auth')  ){
                                $cookie_value = urlencode($_SERVER['REQUEST_URI']);
                                // Lets expire the cookie after 30 days.
                                setcookie($cookieName, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day
                            }
                        }
                        // Pause server.
                        sleep(.5);
                        wp_redirect( esc_url(home_url($dataUrl['reDest'])) );
                        exit;
                    }
                }
            }
        }
    }

    function ronik_authorize_success_redirect_path(){
        $user_id = get_current_user_id();
		$meta_key = 'user_click_actions';
        $userclick_actions = get_user_meta($user_id, $meta_key, true);

        if($userclick_actions){
            if( isset($userclick_actions['url']) ){
                wp_redirect( esc_url(home_url(urldecode($userclick_actions['url']))) );
                exit;
            } else {                
                wp_redirect( esc_url(home_url()) );
                exit;
            }
        } else {
            wp_redirect( esc_url(home_url()) );
            exit;  
        }
        // // We disable cookie tracking.
        // $cookie_name = "ronik-auth-reset-redirect";
        // if(isset($_COOKIE[$cookie_name])) {
        //     wp_redirect( esc_url(home_url(urldecode($_COOKIE[$cookie_name]))) );
        //     exit;
        // } else {
        //     wp_redirect( esc_url(home_url()) );
        //     exit;
        // }
    }