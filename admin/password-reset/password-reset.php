<?php

$f_password_reset = get_field('password_reset_settings', 'options');
// Validate password reset is enabled if not just return false.
if(!isset($f_password_reset['enable_pr_settings']) || !$f_password_reset['enable_pr_settings']){
    return false;
}

// This basically creates the password reset page.
function ronikdesigns_add_custom_reset_page() {
    if( !ronikdesigns_get_page_by_title('Password Reset') ){
        // Create post object
        $my_post = array(
            'post_title'    => wp_strip_all_tags( 'Password Reset' ),
            'post_content'  => 'password-reset',
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            // Assign page template
            'page_template'  => dirname( __FILE__ , 2).'/custom-templates/password_reset-template.php'
        );
        // Insert the post into the database
        wp_insert_post( $my_post );
    }
}
ronikdesigns_add_custom_reset_page();

// Lets add the password reset template.
function ronikdesigns_reserve_page_template_reset( $page_template ){
    // If the page is password reset we add our custom ronik 2fa-template to the page.
    if ( is_page( 'Password Reset' ) ) {
        $page_template =  dirname( __FILE__ , 2).'/custom-templates/password_reset-template.php';
    }
    return $page_template;
}
add_filter( 'template_include', 'ronikdesigns_reserve_page_template_reset', 99 );

// The real code.
function password_reset_ronikdesigns(){
    // If user is not logged in we cancel the function.
	if(!is_user_logged_in()){
		return false;
	}
    function ronikdesigns_reserve_passwordreset_page_template( $page_template ){
        // If the page is 2fa we add our custom ronik 2fa-template to the page.
        if ( is_page( 'password-reset' ) ) {
            $page_template =  dirname( __FILE__ , 2).'/custom-templates/password_reset-template.php';
        }
        return $page_template;
    }
    add_filter( 'template_include', 'ronikdesigns_reserve_passwordreset_page_template', 99 );

    $f_password_reset = get_field('password_reset_settings', 'options');
    // Lets get the current user information
    $curr_user = wp_get_current_user();
    // Store the id.
    $curr_id = $curr_user->id;
    // Keep in mind all timestamp are within the UTC timezone. For constant all around.
    // https://www.timestamp-converter.com/
    // Get the current time.
    $current_date = strtotime((new DateTime())->format( 'd-m-Y' ));
    // Go back in time. Lets say 30 days.
    $past_date = strtotime((new DateTime())->modify('-'.$f_password_reset['pr_days'].' day')->format( 'd-m-Y' ));

    // Lets check if the usermeta exist if it doesnt we create it.
    if( get_user_meta( $curr_id, 'wp_user-settings-time-password-reset', true) == '' ){
        // Store the current date.
        update_user_meta( $curr_id, 'wp_user-settings-time-password-reset', $current_date );
    } else {
        // Lets store the user meta for time comparison
        $current_user_reset_time_stamp = get_user_meta( $curr_id, 'wp_user-settings-time-password-reset', true);

        // Comment this out for live site..
		// This basically overrides the time.
        // $past_date_older = strtotime((new DateTime())->modify('-30 day')->format( 'd-m-Y' ));
		// error_log(print_r( $past_date_older, true));
        // $current_user_reset_time_stamp = $past_date_older;
        // End of Comment this out.

        // If past date is greater then current time stamp. We redirect to the reset page.
        if( $current_user_reset_time_stamp <= $past_date ){

            // Lets setup the cookie for redirect purposes.
            if(($_SERVER['REQUEST_URI'] !== '/wp-admin/admin-post.php') && ($_SERVER['REQUEST_URI'] !== '/password-reset/')){
                if( ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-success=success') && ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-error=alreadyexists') && ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-error=nomatch') && ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-error=missing') ){
                    $cookie_name = "ronik-password-reset-redirect";
                    $cookie_value = urlencode($_SERVER['REQUEST_URI']);
                    // Lets expire the cookie after 1 day.
                    setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day
                    // Pause server.
                    sleep(.5);
                }
            }
        
                // Due to redirect loop issues we need to check the following parameters to avoid a redirect loop.
                    // Check if the $_SERVER is available via isset. WPE will default to else.
                    if(isset($_SERVER['REDIRECT_URL'])){
                        // Lets check if the $_SERVER['REDIRECT_URL'] is equal to admin-post.php or password-reset.
                        // This prevent redirect loop issues
                        if(($_SERVER['REQUEST_URI'] !== '/wp-admin/admin-post.php') && ($_SERVER['REDIRECT_URL'] !== '/password-reset/')){
                            // Because we are using GET we have to check each query
                            if( ($_SERVER['QUERY_STRING'] !== 'pr-success=success') || ($_SERVER['QUERY_STRING'] !== 'pr-error=alreadyexists') || ($_SERVER['QUERY_STRING'] !== 'pr-error=nomatch') || ($_SERVER['QUERY_STRING'] !== 'pr-error=missing') ){
                                wp_redirect( esc_url(home_url('/password-reset/')) );
                                exit;
                            }
                        }
                    } else {
                        if(($_SERVER['REQUEST_URI'] !== '/wp-admin/admin-post.php') && ($_SERVER['REQUEST_URI'] !== '/password-reset/')){
                            if( ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-success=success') && ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-error=alreadyexists')&& ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-error=nomatch') && ($_SERVER['REQUEST_URI'] !== '/password-reset/?pr-error=missing') ){
                                wp_redirect( esc_url(home_url('/password-reset/')) );
                                exit;
                            }
                        }
                    }
 

        }
    }
}
add_action( 'admin_init', 'password_reset_ronikdesigns' );
add_action( 'template_redirect', 'password_reset_ronikdesigns' );


// add_filter( 'wp_mail_from', 'my_mail_from' );
// function my_mail_from( $email ) {
//     $config_wp_mail_from = 'kevin.m.mancuso@gmail.com';
//     // $config_wp_mail_from = get_field( 'config_wp_mail_from', 'options');
//     if($config_wp_mail_from){
//         return $config_wp_mail_from;
//     } else {
//         return $email;
//     }
// }
// add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
// function my_mail_from_name( $name ) {
//     $config_wp_mail_from_name = 'kevin';
//     // $config_wp_mail_from_name = get_field( 'config_wp_mail_from_name', 'options');
//     if($config_wp_mail_from_name){
//         return $config_wp_mail_from_name;
//     } else {
//         return $name;
//     }
// }
