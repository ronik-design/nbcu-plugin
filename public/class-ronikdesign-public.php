<?php

use PragmaRX\Google2FA\Google2FA;
use Twilio\Rest\Client;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.ronikdesign.com/
 * @since      1.0.0
 *
 * @package    Ronikdesign
 * @subpackage Ronikdesign/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ronikdesign
 * @subpackage Ronikdesign/public
 * @author     Kevin Mancuso <kevin@ronikdesign.com>
 */
class Ronikdesign_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ronikdesign_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ronikdesign_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//  wp_enqueue_style($this->plugin_name, plugins_url() . '/ronikdesign/public/css/ronikdesign-public.css', array(), $this->version, 'all');
		//  wp_enqueue_style($this->plugin_name . '2', plugins_url() . '/ronikdesign/public/assets/dist/main.min.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ronikdesign-public.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name . '2', plugin_dir_url(__FILE__) . 'assets/dist/main.min.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ronikdesign_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ronikdesign_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( ! wp_script_is( 'jquery', 'enqueued' )) {
			wp_enqueue_script($this->plugin_name.'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js', array(), null, true);
			$scriptName = $this->plugin_name.'jquery';

			wp_enqueue_script($this->plugin_name.'-vimeo', 'https://player.vimeo.com/api/player.js', array($scriptName), $this->version, false);
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ronikdesign-public.js', array($scriptName), $this->version, false);
			wp_enqueue_script($this->plugin_name . '2', plugin_dir_url(__FILE__) . 'assets/dist/app.min.js', array($scriptName), $this->version, false);
		} else {
			wp_enqueue_script($this->plugin_name.'-vimeo', 'https://player.vimeo.com/api/player.js', array(), $this->version, false);
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ronikdesign-public.js', array(), $this->version, false);
			wp_enqueue_script($this->plugin_name . '2', plugin_dir_url(__FILE__) . 'assets/dist/app.min.js', array(), $this->version, false);
		}



		// Ajax & Nonce
		wp_localize_script($this->plugin_name, 'wpVars', array(
			'ajaxURL' => admin_url('admin-ajax.php'),
			'nonce'	  => wp_create_nonce('ajax-nonce')
		));
	}


	function ajax_do_verification()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
		$f_val_type = $_POST['validationType'];
		$f_value = $_POST['validationValue'];
		$f_strict = $_POST['validationStrict'];

		// $f_email = 'kevin@ronikdesign.com';
		// $f_number = '1-631-617-4271';
		$f_phone_api_key = get_field('abstract_api_phone_ronikdesign', 'option');
		$f_email_api_key = get_field('abstract_api_email_ronikdesign', 'option');

		// Header Manipulation && local needs to be set to false to work correctly.
		if (defined('SITE_ENV') && SITE_ENV == 'PRODUCTION') {
			$f_sslverify = true;
		} else {
			$f_sslverify = false;
		}
		$args = array(
			'headers'     => array(
				'Content-Type' => 'application/json',
				'User-Agent'    => 'PHP',
			),
			'blocking' => true,
			'sslverify' => $f_sslverify,
		);
		// Lets run phone validation.
		if ($f_val_type == 'phone') {
			// Check if phone number has a 1
			if (substr($f_value, 0, 1) !== '1') {
				$f_value = '1' . $f_value;
			}
			$f_url = 'https://phonevalidation.abstractapi.com/v1/?api_key=' . $f_phone_api_key . '&phone=' . $f_value . '';
			$response = wp_remote_get($f_url, $args);
			error_log(print_r($response, true));

			// error_log(print_r($response, true ));
			if ((!is_wp_error($response)) && (200 === wp_remote_retrieve_response_code($response))) {
				$responseBody = json_decode($response['body']);
				if (json_last_error() === JSON_ERROR_NONE) {
					if ($responseBody->valid == 1) {
						error_log(print_r('Phone Vetted', true));
						if($f_strict){
							error_log(print_r(CSP_NONCE, true));
							wp_send_json_success($responseBody->valid);
						} else {
							wp_send_json_success($responseBody->valid);
						}
					} else {
						wp_send_json_error('Error');
					}
				} else {
					wp_send_json_error('Error');
				}
			} else {
				wp_send_json_error('Error');
			}
		}
		// Lets run email validation.
		if ($f_val_type == 'email') {
			$f_url = 'https://emailvalidation.abstractapi.com/v1/?api_key=' . $f_email_api_key . '&email=' . $f_value . '';
			$response = wp_remote_get($f_url, $args);
			// error_log(print_r($response, true ));
			if ((!is_wp_error($response)) && (200 === wp_remote_retrieve_response_code($response))) {
				$responseBody = json_decode($response['body']);
				if (json_last_error() === JSON_ERROR_NONE) {
					if ($responseBody->is_valid_format->value == 1) {
						error_log(print_r('Email Vetted', true));
						if($f_strict){
							// error_log(print_r(CSP_NONCE, true));
							wp_send_json_success($responseBody->is_valid_format->value);
						} else {
							wp_send_json_success($responseBody->is_valid_format->value);
						}
					} else {
						wp_send_json_error('Error');
					}
				} else {
					wp_send_json_error('Error');
				}
			} else {
				wp_send_json_error('Error');
			}
		}
	}


	function my_body_classes($classes)
	{
		$f_custom_js_settings = get_field('custom_js_settings', 'options');

		if( !empty($f_custom_js_settings) ){
			if ($f_custom_js_settings['dynamic_image_attr']) {
				$classes[] = 'dyn-image-attr';
			}

			if ($f_custom_js_settings['dynamic_button_attr']) {
				$classes[] = 'dyn-button-attr';
			}

			if ($f_custom_js_settings['dynamic_external_link']) {
				$classes[] = 'dyn-external-link';
			}

			if ($f_custom_js_settings['smooth_scroll']) {
				$classes[] = 'smooth-scroll';
			}

			if ($f_custom_js_settings['dynamic_svg_migrations']) {
				$classes[] = 'dyn-svg-migrations';
			}

			if ($f_custom_js_settings['enable_serviceworker']) {
				$classes[] = 'enable-serviceworker';
			}

		}

		return $classes;
	}


	/**
	 * Icon Set
	*/
	function ajax_do_init_svg_migration_ronik() {
		if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
		// Check if user is logged in.
		if( !is_user_logged_in() ){
			return;
		}
		$f_icons = get_field('page_migrate_icons', 'options');
		if($f_icons){
			//The folder path for our file should.
			$directory = get_stylesheet_directory().'/roniksvg/migration/';

			// First lets loop through everything to see if any icons are assigned to posts..
			// The meta query will search for any value that has part of the beginning of the file name.
			$args_id = array(
				'fields' => 'ids',
				'post_type'  => 'any',
				'post_status'  => 'any',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
					'value' => 'ronik-migration-svg_',
					'compare' => 'LIKE',
					)
				),
			);
			$f_postsid = get_posts( $args_id );
			if($f_postsid){
				$f_array = array();
				// Loop through all found posts...
				foreach($f_postsid as $i => $postid){
					$metavalue = get_post_meta($postid);
					$count = -1;
					// Loop through all post meta for the current postid...
					foreach($metavalue as $a => $val){
						// We determine the meta value and explode and compare accordingly..
						$pieces = explode("migration-svg_", $val[0]);
						if( $pieces[0] == 'ronik-'){
							$count++;
							$f_filename = str_replace("ronik-migration-svg_","",  $val);
							$f_array[$count]['acf-key'] = $a;
							foreach($f_icons as $s => $icons){
								$f_filename_svg = str_replace(".svg","", $icons['svg']['filename']);
								if( $f_filename[0] == $f_filename_svg ){
									// Increase Index by 1 so we dont run into a false positive..
									$f_array[$count]['acf-index'] = $s+1;
								}
							}
						}
					}
					// This is critical we check the array count vs the
					if( $f_array ){
						$f_array_count = count($f_array);
						$f_valid = 0;
						foreach($f_array as $array){
							// Check if empty and if index is greater then 0.
							if( !empty($array['acf-index']) && ($array['acf-index'] > 0) ){
								error_log(print_r('valid' , true));
								$f_valid++;
							} else{
								error_log(print_r('unvalid' , true));
							}
						}
						if($f_array_count == $f_valid){
							error_log(print_r('it passed' , true));
							update_post_meta ( $postid, 'dynamic-icon_icon_select-history', $f_array  );
						}
					}
				}
			}
			sleep(.5);
			// Lets clean up all the icons within the folder
			$files = glob($directory.'*');
			foreach($files as $file) {
				if(is_file($file)){
					unlink($file);
				}
			}
			sleep(.5);
			// Next lets loop through all the options icons..
			foreach($f_icons as $key=> $icon2){
				// First lets copy the full image to the ronikdetached folder.
				$upload_dir   = wp_upload_dir();
				$link = wp_get_attachment_image_url( $icon2['svg']['id'], 'full' );
				$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $link);
				$file_name = explode('/', $link);
				//If the directory doesn't already exists.
				if(!is_dir($directory)){
					//Create our directory.
					mkdir($directory, 0777, true);
				}
				copy($file_path , $directory.'ronik-migration-svg_'.end($file_name));
			}
			sleep(.5);
			// Lastly lets loop through everything to see if we can reassign the icons
			foreach($f_icons as $key => $icon3){
				$args_id_3 = array(
					'fields' => 'ids',
					'post_type'  => 'any',
					'post_status'  => 'any',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'dynamic-icon_icon_select',
							'value' => str_replace(".svg","", $icon3['svg']['filename']),
							'compare' => '!='
						)
					),
				);
				$f_postsid = get_posts( $args_id_3 );
				if($f_postsid){
					foreach($f_postsid as $j => $postid){
						$f_history = get_post_meta( $f_postsid[$j], 'dynamic-icon_icon_select-history', true );

						if($f_history){
							foreach($f_history as $k => $history){
								error_log(print_r($history['acf-key'], true));
								error_log(print_r($history['acf-index']-1, true));
								$f_file = str_replace(".svg","", 'ronik-migration-svg_'.$f_icons[$history['acf-index']-1]['svg']['filename']);
								update_post_meta ( $postid, $history['acf-key'] , $f_file  );
							}
						}
					}
				}
			}
		} else {
			wp_send_json_error('No rows found!');
		}
		wp_send_json_success('Done');
	}

	// modify the path to the icons directory
	function acf_icon_path_suffix( $path_suffix ) {
		return $path_suffix;
		// return 'roniksvg/migration/';
	}
	// modify the path to the above prefix
	function acf_icon_path( $path_suffix ) {
		return $path_suffix;
		// return get_stylesheet_directory_uri();
	}
	// modify the URL to the icons directory to display on the page
	function acf_icon_url( $path_suffix ) {
		return $path_suffix;
		// return get_stylesheet_directory_uri();
	}

	function ronikdesigns_rest_api_init(){
		// Include the Spam Blocker.
		foreach (glob(dirname(__FILE__) . '/rest-api/*.php') as $file) {
			include $file;
		}
	}

	function ronikdesigns_acf_op_init_functions_public(){
				// Include the auth.
		foreach (glob(dirname(__FILE__) . '/authorization/*.php') as $file) {
			global $wpdb;

			// This piece of code is critical. It determines if the user should be allowed to bypass the authorization app.
			// We add the logic into the theme.
			// include $file;

			$f_bypasser = apply_filters( 'ronikdesign_auth_bypasser', false );
			if($f_bypasser){

				// The next part we find the user email.
				$user_id = get_current_user_id();
				$user_email = get_user_meta($user_id, "user_email", true);
				// If default user email is not found we look through a custom data path. do_users.
				if(!$user_email){
					$sql = "select * from do_users where ID = '$user_id'";
					$do_users = $wpdb->get_results($sql);
					if(empty($do_users)){
						if($do_users[0]->user_email){
							$user_email = $do_users[0]->user_email;
						} else {
							$user_email = 'No email found.';
						}
					} else {
						$user_email = 'No email found.';
					}
				}

				$user_confirmed = get_user_meta($user_id, "user_confirmed", true);
				$wp_3_access = get_user_meta($user_id, "wp_3_access", true);
				// Pretty much want to only trigger the MFA if the user is confirmed and is granted access.
				if($user_confirmed == 'Y' && $wp_3_access == 'Y'){
					// If no email we include the file.
					if($user_email == 'No email found.'){
						include $file;
					} else {
						$f_user_override = get_option('options_mfa_settings_user_override');
						// We remove all whitespace (including tabs and line ends)
						$f_user_override = preg_replace('/\s+/', '', $f_user_override);
						// Lets trim just incase as well.
						$f_user_override_array = explode(",", trim($f_user_override));
						// Detect if array is populated.
						if (!in_array($user_email, $f_user_override_array)) {
							include $file;
						}
					}
				}

			}
		}
		// Include the password reset.
		foreach (glob(dirname(__FILE__) . '/password-reset/*.php') as $file) {
			// This is critical without this we would get an infinite loop...
				// We check if the server REQUEST_URI contains the following "admin-post", "auth", "2fa", "mfa"
				if( !str_contains($_SERVER['REQUEST_URI'], 'admin-ajax') && !str_contains($_SERVER['REQUEST_URI'], 'admin-post') && !str_contains($_SERVER['REQUEST_URI'], 'auth') && !str_contains($_SERVER['REQUEST_URI'], '2fa') && !str_contains($_SERVER['REQUEST_URI'], 'mfa')  ){
					include $file;
				}
		}
	}



	function ronikdesigns_admin_password_reset() {
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}

		$f_value = array();
		if(!empty($_POST['password']) && !empty($_POST['retype_password'])){
			if($_POST['password'] === $_POST['retype_password']){
				// Lets get the current user information
				$curr_user = wp_get_current_user();

				// 	check if password already exists...
				if(wp_check_password($_POST['password'], $curr_user->user_pass, $curr_user->ID)){
					$f_value['pr-error'] = "alreadyexists";
				} else {
					if(strlen($_POST['password']) < 8){
						$f_value['pr-error'] = "weak";
					} else {
						if (!preg_match('/([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/', $_POST['password'])){
							// No special characters found in string!
							$f_value['pr-error'] = "no-special-characters";
						} else{
							if(!preg_match('/[A-Z]/', $_POST['password'])){
								// No uppercase found!
								$f_value['pr-error'] = "no-uppercase";
							} else {
								if(!preg_match('/[a-z]/', $_POST['password'])){
									// No lowercase found!
									$f_value['pr-error'] = "no-lowercase";
								} else{
									// Store the id.
									$curr_id = $curr_user->id;
									$current_date = strtotime((new DateTime())->format( 'd-m-Y' ));
									update_user_meta( $curr_id, 'wp_user-settings-time-password-reset', $current_date );
									// Get current logged-in user.
									$user = wp_get_current_user();
									// Send out an email notification.
									$to = $curr_user->user_email;
									$subject = 'Password Reset.';
									$body = 'Your password was successfully reset.';
									$headers = array('Content-Type: text/html; charset=UTF-8');
									wp_mail($to, $subject, $body, $headers);
									// Change password.
									wp_set_password( $_POST['password'], $user->ID);
									// Log-in again.
									wp_set_auth_cookie($user->ID);
									wp_set_current_user($user->ID);
									do_action('wp_login', $user->user_login, $user);

									$f_value['pr-success'] = "success";
								}
							}
						}
					}
				}

			} else {
				$f_value['pr-error'] = "nomatch";
			}
		} else{
			$f_value['pr-error'] = "missing";
		}
		$r_redirect = '/password-reset/?'.http_build_query($f_value, '', '&amp;');
		wp_redirect( esc_url(home_url($r_redirect)) );
		exit;
	}

	function ronikdesigns_admin_auth_verification() {
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			wp_send_json_success('noreload');
			return;
		}
		// Start the session
		// temp session_start();
		// session_start();
		$f_value = array();
		$f_auth = get_field('mfa_settings', 'options');

		$f_twilio_id = get_option('options_mfa_settings_twilio_id');
		$f_twilio_token = get_option('options_mfa_settings_twilio_token');
		$f_twilio_number = get_option('options_mfa_settings_twilio_number');

		// $f_auth_page_enabled = get_field('options_mfa_settings_auth_page_enabled', 'options');
		$f_auth_expiration_time = get_option('options_mfa_settings_auth_expiration_time');

		$mfa_status = get_user_meta(get_current_user_id(),'mfa_status', true);
		$mfa_validation = get_user_meta(get_current_user_id(),'mfa_validation', true);
		$sms_2fa_status = get_user_meta( get_current_user_id(),'sms_2fa_status', true );
		$get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
		$get_current_secret_2fa = get_user_meta(get_current_user_id(), 'sms_2fa_secret', true);

		$get_auth_lockout_counter = get_user_meta(get_current_user_id(), 'auth_lockout_counter', true);

		// Update the status with timestamp.
		// Keep in mind all timestamp are within the UTC timezone. For constant all around.
		// https://www.timestamp-converter.com/
		// Get the current time.
		$current_date = strtotime((new DateTime())->format( 'd-m-Y H:i:s' ));
		// Lets generate the sms_2fa_secret key.
		$sms_2fa_secret = wp_rand( 1000, 9999 );
		$sms_code_timestamp = get_user_meta(get_current_user_id(),'sms_code_timestamp', true);
		$f_expiration_time = get_option('options_mfa_settings_sms_expiration_time');
		$account_sid = $f_twilio_id;
		$auth_token = $f_twilio_token;
		// A Twilio number you own with SMS capabilities
		$twilio_number = $f_twilio_number;
		// Current user phone number.
		$to_number = '+1'.$get_phone_number;
		$client = new Client($account_sid, $auth_token);


		if(isset($_POST['re-auth']) && $_POST['re-auth']){
			if($_POST['re-auth'] == 'RESET'){
				update_user_meta(get_current_user_id(), 'auth_status', 'none');
				// We build a query and redirect back to auth route.
				$f_value['auth-select'] = "reset";
				$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
				wp_redirect( esc_url(home_url($r_redirect)) );
				exit;
			}
		}
		// AUTH Section:
			// First Check:
				// Lets get the auth-select value.
				if(isset($_POST['auth-select']) && $_POST['auth-select']){
					// Check if value is 2fa.
					if($_POST['auth-select'] == '2fa'){
						// Check if user has a phone number.
						if($get_phone_number){
							update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
							$f_value['auth-select'] = "2fa";
							$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
							// We build a query and redirect back to 2fa route.
							wp_redirect( esc_url(home_url($r_redirect)) );
							exit;
						} else {
							// If user has no phone number. We set the auth_status to auth_select_sms-missing.
							update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms-missing');
							$f_value['auth-select'] = "2fa";
							$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
							// We build a query and redirect back to 2fa route.
							wp_redirect( esc_url(home_url($r_redirect)) );
							exit;
						}
					// Check if value is mfa.
					} else {
						update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_mfa');
						$f_value['auth-select'] = "mfa";
						$r_redirect = '/mfa/?'.http_build_query($f_value, '', '&amp;');
						// We build a query and redirect back to 2fa route.
						wp_redirect( esc_url(home_url($r_redirect)) );
						exit;
					}
				}
			// Second Check:
				// Lets check the auth-phone_number.
				if(isset($_POST['auth-phone_number']) && $_POST['auth-phone_number']){
					//eliminate every char except 0-9
					$justNums = preg_replace("/[^0-9]/", '', $_POST['auth-phone_number']);
					// This is where api validation will be performed...
					update_user_meta(get_current_user_id(), 'sms_user_phone', $justNums);
					// End api validation
					update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
					// Update the status with sms_2fa_unverified
					update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');

					$f_value['auth-phone_number'] = "valid";
					$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
					// We build a query and redirect back to 2fa route.
					wp_redirect( esc_url(home_url($r_redirect)) );
					exit;
				}


		// SMS Section:
			// First Check:
				// Lets store the sms code and then we also send the sms code.
				if(isset($_POST['send-sms']) && $_POST['send-sms']){
					// // Lets store the sms_2fa_secret data inside the current usermeta.
					// update_user_meta(get_current_user_id(), 'sms_2fa_secret', $sms_2fa_secret);
					// Lets store the sms code timestamp in user meta.
					update_user_meta(get_current_user_id(), 'sms_code_timestamp', $current_date);
					// We generate a sms message and send it to the current user
					if( $f_twilio_id && $f_twilio_token && $f_twilio_number ){
						// We set sms to false until we are ready.
						$send_sms = true;
						if($send_sms){
							$service = $client->verify->v2->services->create("NBCU Together");
							$verification = $client->verify->v2->services($service->sid)->verifications->create($to_number, "sms");
							error_log(print_r($verification->status , true));
							// error_log(print_r($verification , true));
							// Lets store the sms_2fa_secret data inside the current usermeta.
							update_user_meta(get_current_user_id(), 'sms_2fa_secret', $service->sid);
							// $message = $client->messages->create(
							// 	// Where to send a text message (your cell phone?)
							// 	$to_number,
							// 	array(
							// 		'from' => $twilio_number,
							// 		'body' => 'Your verification code is '.$sms_2fa_secret
							// 	)
							// );
							// // Okay if the text message system is blocked, we will send sms code via email...
							// $send_email = false;
							// $message = $client->messages($message->sid)->fetch();
							// if(($message->status == 'undelivered') || ($message->error_code == '30034')){
							// 	$send_email = true;
							// }
						} else {
							$send_email = true;
						}
                        // For developers testing only...
						if($send_email){
							$sender_email = get_field("email_no_reply_sender", "options");
							$sender_name = get_field("email_no_reply_sender_name", "options");
							$sender = array(0=>$sender_email);
							if(!empty($sender_name)){
							  $sender[1] = $sender_name;
							}
							$curr_user = wp_get_current_user();
							$to = $curr_user->user_email;
							$subject = 'SMS Code.';
							$body = 'Your SMS Code: '.$sms_2fa_secret.' ';
							send_email($to, $subject, $body, $body, $sender, 'sms-code');
						}
					}
					$f_value['send-sms'] = "true";
					$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
					// We build a query and redirect back to 2fa route.
					wp_redirect( esc_url(home_url($r_redirect)) );
					exit;
				}
			// Second Check:
				// Lets validate-sms-code
				if(isset($_POST['validate-sms-code']) && $_POST['validate-sms-code']){

					if( strlen($_POST['validate-sms-code']) !== 6 ){
						$f_value['sms-error'] = "nomatch";
						if(isset($get_auth_lockout_counter) && ($get_auth_lockout_counter == 3)){
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $current_date);
						} else {
							if(!isset($get_auth_lockout_counter) || !$get_auth_lockout_counter){
								$get_auth_lockout_counter = 0;
							}
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $get_auth_lockout_counter+1);
						}
						$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
						// We build a query and redirect back to 2fa route.
						wp_redirect( esc_url(home_url($r_redirect)) );
						exit;
					}

					$verification_check = $client->verify->v2->services($get_current_secret_2fa)->verificationChecks->create([
						"to" => $to_number,
						"code" => $_POST['validate-sms-code']
					]);
					if($verification_check->status == 'approved'){
						update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_verified');
						$f_value['sms-success'] = "success";
						$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
						// We build a query and redirect back to 2fa route.
						wp_redirect( esc_url(home_url($r_redirect)) );
						exit;
					} else {
						$f_value['sms-error'] = "nomatch";
						if(isset($get_auth_lockout_counter) && ($get_auth_lockout_counter == 3)){
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $current_date);
						} else {
							if(!isset($get_auth_lockout_counter) || !$get_auth_lockout_counter){
								$get_auth_lockout_counter = 0;
							}
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $get_auth_lockout_counter+1);
						}
					}
					// if($get_current_secret_2fa == $_POST['validate-sms-code']){
					// 	update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_verified');
					// 	$f_value['sms-success'] = "success";
					// 	// $f_value['sms-valid'] = "true";
					// 	$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
					// 	// We build a query and redirect back to 2fa route.
					// 	wp_redirect( esc_url(home_url($r_redirect)) );
					// 	exit;
					// } else {
					// 	$f_value['sms-error'] = "nomatch";
					// }
					// $f_value['sms-valid'] = "false";
					$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
					// We build a query and redirect back to 2fa route.
					wp_redirect( esc_url(home_url($r_redirect)) );
					exit;
				}
			// Third Check:
				// Lets check to see if the user is idealing to long.
				if(isset($_POST['smsExpired']) && $_POST['smsExpired']){
                    if($f_expiration_time){
                        $f_sms_expiration_time = $f_expiration_time;
                    } else{
                        $f_sms_expiration_time = 10;
                    }
					$f_sms_expiration_time = 1;
                    $past_date = strtotime((new DateTime())->modify('-'.$f_sms_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
                    if( $past_date > $sms_code_timestamp ){
                        update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
                        update_user_meta(get_current_user_id(), 'sms_2fa_secret', 'invalid');

						if($get_auth_lockout_counter == 3){
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $current_date);
						} else {
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $get_auth_lockout_counter+1);
						}

						wp_send_json_success('reload');
                    }  else {
						wp_send_json_success('noreload');
					}
				}



		// MFA Section:
			// First Check:
				// Lets check the POST parameter to see if we want to send the MFA code.
				if(isset($_POST['google2fa_code']) && $_POST['google2fa_code']){
					$mfa_status = get_user_meta(get_current_user_id(),'mfa_status', true);
					$get_current_secret_mfa = get_user_meta(get_current_user_id(), 'google2fa_secret', true);
					$google2fa = new Google2FA();

					if ( $mfa_status == 'mfa_unverified' ) {
						// Lets save the google2fa_secret to the current user_meta.
						$code = $_POST["google2fa_code"];
						$valid = $google2fa->verifyKey($get_current_secret_mfa, $code);
						if ($valid) {
							// Lets store the mfa validation data inside the current usermeta.
							update_user_meta(get_current_user_id(), 'mfa_validation', 'valid');
							update_user_meta(get_current_user_id(), 'mfa_status', $current_date);
							$f_value['mfa-success'] = "success";
						} else {
							if($get_auth_lockout_counter == 3){
								update_user_meta(get_current_user_id(), 'auth_lockout_counter', $current_date);
							} else {
								update_user_meta(get_current_user_id(), 'auth_lockout_counter', $get_auth_lockout_counter+1);
							}

							$f_value['mfa-error'] = "nomatch";
						}
					}  else {
						$valid = false;
						// Lets store the mfa validation data inside the current usermeta.
						update_user_meta(get_current_user_id(), 'mfa_validation', 'invalid');

						if($get_auth_lockout_counter == 3){
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $current_date);
						} else {
							update_user_meta(get_current_user_id(), 'auth_lockout_counter', $get_auth_lockout_counter+1);
						}

						$f_value['mfa-error'] = "nomatch";
					}
					$r_redirect = '/mfa/?'.http_build_query($f_value, '', '&amp;');
					// We build a query and redirect back to 2fa route.
					wp_redirect( esc_url(home_url($r_redirect)) );
					exit;
				}



		// Global Validation Section:
			// Second Check:
				// Lets check to see if the user is idealing to long.
				if(isset($_POST['killValidation']) && ($_POST['killValidation'] == 'valid')){
					error_log(print_r('Kill Validation' , true));
					// Lets check if user is accessing a locked page.
					if($mfa_status !== 'mfa_unverified'){
						update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
						// update_user_meta(get_current_user_id(), 'mfa_validation', 'invalid');
						wp_send_json_success('reload');
					}
					if($sms_2fa_status !== 'sms_2fa_unverified'){
						update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
						update_user_meta(get_current_user_id(), 'sms_2fa_secret', 'invalid');
						wp_send_json_success('reload');
					}
					// Catch ALL
					wp_send_json_success('noreload');
				}
				// Lets check to see if the user is idealing to long.
				if(isset($_POST['timeChecker']) && ($_POST['timeChecker'] == 'valid')){
					error_log(print_r('Time Checker Validation' , true));
					if( isset($f_auth_expiration_time) && $f_auth_expiration_time ){
						$f_auth_expiration_time = $f_auth_expiration_time;
					} else {
						$f_auth_expiration_time = 30;
					}
                    $past_date = strtotime((new DateTime())->modify('-'.$f_auth_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
					if($mfa_status !== 'mfa_unverified'){
						if($past_date > $mfa_status ){
							update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
							wp_send_json_success('reload');
						}
					}
					if($sms_code_timestamp == 'invalid'){
						update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
						update_user_meta(get_current_user_id(), 'sms_2fa_secret', 'invalid');
						wp_send_json_success('reload');
					}
					if(isset($sms_2fa_status) && $sms_2fa_status && ($sms_2fa_status !== 'sms_2fa_unverified')){
						if($past_date > $sms_code_timestamp ){
							update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
							update_user_meta(get_current_user_id(), 'sms_2fa_secret', 'invalid');
							wp_send_json_success('reload');
						}
					}
					// Catch ALL
					wp_send_json_success('noreload');
				}
				// Lets check to see if the user is idealing to long.
				if(isset($_POST['timeLockoutChecker']) && ($_POST['timeLockoutChecker'] == 'valid')){
					error_log(print_r('Lockout Time Checker Validation' , true));
					if( isset($get_auth_lockout_counter) && (strlen($get_auth_lockout_counter) > 6) ){
						$f_expiration_time = 3;
						$past_date = strtotime((new DateTime())->modify('-'.$f_expiration_time.' minutes')->format( 'd-m-Y H:i:s' ));
						if( $past_date > $get_auth_lockout_counter ){
							delete_user_meta(get_current_user_id(), 'auth_lockout_counter');
							wp_send_json_success('reload');
						} else {
							// Catch ALL
							wp_send_json_success('noreload');
						}
					} else {
						// Catch ALL
						wp_send_json_success('noreload');
					}
					// Catch ALL
					wp_send_json_success('noreload');
				}
	}

}
