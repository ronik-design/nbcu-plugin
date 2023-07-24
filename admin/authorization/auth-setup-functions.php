<?php 
// Shared Functionality between the two authentication.
# Include packages
require_once(dirname(__DIR__, 2) . '/vendor/autoload.php');
use PragmaRX\Google2FA\Google2FA;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twilio\Rest\Client;


$f_auth = get_field('mfa_settings', 'options');
// Frontend Creation of Authentication Pages.
    // This basically auto create the page it doesnt already exist. It will also auto assign the specific template.
    function ronikdesigns_add_custom_auth_page($f_auth) {
        // Check if MFA && 2fa is enabled.
        if((isset($f_auth['enable_mfa_settings']) || $f_auth['enable_mfa_settings']) && (isset($f_auth['enable_2fa_settings']) || $f_auth['enable_2fa_settings'])){
            if( !ronikdesigns_get_page_by_title('auth') ){
                // Create post object
                $my_post = array(
                    'post_title'    => wp_strip_all_tags( 'auth' ),
                    'post_content'  => 'auth',
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'post_type'     => 'page',
                    // Assign page template
                    'page_template'  => dirname( __FILE__ , 2).'/authorization/custom-templates/auth-template.php'
                );
                // Insert the post into the database
                wp_insert_post( $my_post );
            }
        }

        // Check if 2fa is enabled.
        if(isset($f_auth['enable_2fa_settings']) || $f_auth['enable_2fa_settings']){
            if( !ronikdesigns_get_page_by_title('2fa') ){
                // Create post object
                $my_post = array(
                    'post_title'    => wp_strip_all_tags( '2fa' ),
                    'post_content'  => '2fa',
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'post_type'     => 'page',
                    // Assign page template
                    'page_template'  => dirname( __FILE__ , 2).'/authorization/custom-templates/2fa-template.php'
                );
                // Insert the post into the database
                wp_insert_post( $my_post );
            }
        }

        // Check if MFA is enabled.
        if(isset($f_auth['enable_mfa_settings']) || $f_auth['enable_mfa_settings']){
            if( !ronikdesigns_get_page_by_title('mfa') ){
                // Create post object
                $my_post = array(
                    'post_title'    => wp_strip_all_tags( 'mfa' ),
                    'post_content'  => '2fa',
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'post_type'     => 'page',
                    // Assign page template
                    'page_template'  => dirname( __FILE__ , 2).'/authorization/custom-templates/mfa-template.php'
                );
                // Insert the post into the database
                wp_insert_post( $my_post );
            }
        }
    }
    ronikdesigns_add_custom_auth_page($f_auth);

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
    function ronikRedirectLoopApproval($dataUrl, $cookieName){
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
                foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
                    $postId = $auth_page_enabled['page_selection'][0];
                    $slug = basename(get_permalink($postId));

                    if(strstr($slug, '/')){
                        $f_url = strstr($slug, '/');
                    } else {
                        $f_url = '/';
                    }
                    array_push($dataUrl['reUrl'], $f_url);
                }
            }
            // First lets loop through all the provided urls.
            foreach ($dataUrl['reUrl'] as $value) {
                // If value matches with the request_url
                if (!str_contains($_SERVER['REQUEST_URI'], $value)) {
                    // We dont want to redirect on the '/wp-admin/admin-post.php'
                    if (!str_contains($_SERVER['REQUEST_URI'], '/wp-admin/admin-post.php')) {
                        // Lastly we check if the requested matches the permalink to prevent looping issues.
                        if(get_permalink() !== home_url($dataUrl['reDest'])){
                            if($_SERVER['REQUEST_URI'] !== '/favicon.ico'){
                                $cookie_value = urlencode($_SERVER['REQUEST_URI']);
                            } else {
                                $cookie_value =  '/';
                            }
                            // if(!$cookie_value){ $cookie_value =  '/'; }
                            // Lets expire the cookie after 1 day.
                            setcookie($cookieName, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day
                            // Pause server.
                            sleep(.5);
                            wp_redirect( esc_url(home_url($dataUrl['reDest'])) );
                            exit;
                        }
                    }
                }
            }








            // $condition = '';
            // foreach ($dataUrl['reUrl'] as $value) {
            //     if($_SERVER['REQUEST_URI'] !== $value){
            //         $condition .= ' valid ';
            //     } else {
            //         $condition .= ' invalid ';
            //     }
            // }

        
            // if (!str_contains($condition, 'invalid')) {

            //     if($_SERVER['REQUEST_URI'] !== '/favicon.ico'){
            //         $cookie_value = urlencode($_SERVER['REQUEST_URI']);
            //     } else {
            //         $cookie_value =  '/';
            //     }
            //     // if(!$cookie_value){ $cookie_value =  '/'; }
            //     // Lets expire the cookie after 1 day.
            //     setcookie($cookieName, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day
            //     // Pause server.
            //     sleep(.5);
         
            //     if(get_permalink() !== home_url($dataUrl['reDest'])){

            //         wp_redirect( esc_url(home_url($dataUrl['reDest'])) );
            //         exit;
            //     }
            // }   
            
            





        }
    }
