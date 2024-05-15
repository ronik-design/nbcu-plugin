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

