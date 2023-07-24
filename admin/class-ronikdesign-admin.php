<?php

use PragmaRX\Google2FA\Google2FA;
use Twilio\Rest\Client;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.ronikdesign.com/
 * @since      1.0.0
 *
 * @package    Ronikdesign
 * @subpackage Ronikdesign/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ronikdesign
 * @subpackage Ronikdesign/admin
 * @author     Kevin Mancuso <kevin@ronikdesign.com>
 */
class Ronikdesign_Admin
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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ronikdesign-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
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
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ronikdesign-admin.js', array($scriptName), $this->version, false);
		} else {
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ronikdesign-admin.js', array(), $this->version, false);
		}

		// Ajax & Nonce
		wp_localize_script($this->plugin_name, 'wpVars', array(
			'ajaxURL' => admin_url('admin-ajax.php'),
			'nonce'	  => wp_create_nonce('ajax-nonce')
		));
	}
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function acf_enqueue_scripts()
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
		
		// Detect if jQuery is included if not lets modernize with the latest stable version.
		if ( ! wp_script_is( 'jquery', 'enqueued' )) {
			wp_enqueue_script($this->plugin_name.'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js', array(), null, true);
			$scriptName = $this->plugin_name.'jquery';
			wp_enqueue_script($this->plugin_name . '-acf', plugin_dir_url(__FILE__) . 'js/acf/admin.js', array($scriptName), $this->version, false);
		} else {
			wp_enqueue_script($this->plugin_name . '-acf', plugin_dir_url(__FILE__) . 'js/acf/admin.js', array(), $this->version, false);
		}
	}


	// This will setup all options pages.
	function ronikdesigns_acf_op_init()
	{
		// Check function exists.
		if (function_exists('acf_add_options_page')) {
			// Add parent.
			$parent = acf_add_options_page(array(
				'page_title'  => __('Developer General Settings'),
				'menu_title'  => __('Developer Settings'),
				'menu_slug'     => 'developer-settings',
				// 'parent_slug' => $parent['menu_slug'],
				'redirect'    => false,
			));
			// Add sub page.
			$child = acf_add_options_page(array(
				'page_title'  => __('Code Template'),
				'menu_title'  => __('Code Template'),
				'menu_slug'     => 'code-template',
				'parent_slug' => $parent['menu_slug'],
			));
		}
	}

	// This will setup all custom fields via php scripts.
	function ronikdesigns_acf_op_init_fields()
	{
		// Include the ACF Fields
		foreach (glob(dirname(__FILE__) . '/acf-fields/*.php') as $file) {
			include $file;
		}
	}


	function ronikdesigns_acf_op_init_functions()
	{
		// acf-icon-picker-master
		include dirname(__FILE__) . '/acf-icon-picker-master/acf-icon-picker.php';

		// Include the Script Optimizer.
		foreach (glob(dirname(__FILE__) . '/script-optimizer/*.php') as $file) {
			include $file;
		}
		// Include the Spam Blocker.
		foreach (glob(dirname(__FILE__) . '/spam-blocker/*.php') as $file) {
			include $file;
		}
		// Include the Wp Cleaner.
		foreach (glob(dirname(__FILE__) . '/wp-cleaner/*.php') as $file) {
			include $file;
		}
		// Include the Wp Functions.
		foreach (glob(dirname(__FILE__) . '/wp-functions/*.php') as $file) {
			include $file;
		}
		// Include the auth.
		foreach (glob(dirname(__FILE__) . '/authorization/*.php') as $file) {
			include $file;
		}
		// Include the password reset.
		foreach (glob(dirname(__FILE__) . '/password-reset/*.php') as $file) {
			// This is critical without this we would get an infinite loop...
				// SMS Checkpoint
				$get_current_sms_secret = get_user_meta(get_current_user_id(), 'sms_2fa_secret', true);
				$get_registration_sms_status = get_user_meta(get_current_user_id(), 'sms_2fa_status', true);
				// MFA Checkpoint
				$get_current_secret = get_user_meta(get_current_user_id(), 'google2fa_secret', true);
				$get_registration_status = get_user_meta(get_current_user_id(), 'mfa_status', true);
				// If all fields are not empty we include the password reset file.
				if( (empty($get_current_sms_secret) || $get_registration_sms_status == 'sms_2fa_unverified') || (empty($get_current_secret) || $get_registration_status == 'mfa_unverified')){} else {
					// include $file;
				}
		}
		// Include the manifest.
		foreach (glob(dirname(__FILE__) . '/manifest/*.php') as $file) {
			include $file;
		}
		// Include the Service Worker.
		foreach (glob(dirname(__FILE__) . '/service-worker/*.php') as $file) {
			include $file;
		}
		// // Include the analytics.
		// foreach (glob(dirname(__FILE__) . '/analytics/*.php') as $file) {
		// 	include $file;
		// }
	}


	/**
	 * Enable SVG as a mime type for uploads.
	 * @param array $mimes
	 * @return string
	 */
	function roniks_add_svg_mime_types($mimes): array
	{
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Remove menus from clients.
	 */
	function remove_menus()
	{
		$curr_user = wp_get_current_user();
		$curr_id = 'user_' . $curr_user->id;
		$curr_experience = get_field('global_user_experience', $curr_id);

		// We check user roles.
		$allowed_roles = array('administrator');
		if (!array_intersect($allowed_roles, $curr_user->roles)) {
			// Code here for allowed roles
			remove_menu_page('acf-options-developer-settings');
		}
		if ($curr_experience !== 'advanced') {
			remove_menu_page('index.php'); //Dashboard  
			remove_menu_page('options-general.php'); //Settings  
			remove_menu_page('tools.php'); //Tools  
			remove_menu_page('edit.php?post_type=acf-field-group');  //Hide ACF Field Groups  
			remove_menu_page('themes.php'); //Appearance  
			remove_menu_page('plugins.php'); //Plugins
			remove_menu_page('acf-options-developer-settings');
		}
	}

	function ronikdesigns_admin_auth_verification() {
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}
		// Start the session
		session_start();
		$f_value = array();

		$f_auth = get_field('mfa_settings', 'options');
		$mfa_status = get_user_meta(get_current_user_id(),'mfa_status', true);
		$sms_2fa_status = get_user_meta( get_current_user_id(),'sms_2fa_status', true );
		$get_phone_number = get_user_meta(get_current_user_id(), 'sms_user_phone', true);
		$get_current_secret = get_user_meta(get_current_user_id(), 'sms_2fa_secret', true);
		// Update the status with timestamp.
		// Keep in mind all timestamp are within the UTC timezone. For constant all around.
		// https://www.timestamp-converter.com/
		// Get the current time.
		$current_date = strtotime((new DateTime())->format( 'd-m-Y H:i:s' ));
		// Lets generate the sms_2fa_secret key.
		$sms_2fa_secret = wp_rand( 1000000, 9999999 );



		// Lets check to see if the user is idealing to long.
		if(isset($_POST['smsExpired']) && $_POST['smsExpired']){
			error_log(print_r( 'Expired', true));
			update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
			//Lets wipe out the session for sms_2fa_status
			unset($_SESSION['send-sms']);
			unset($_SESSION['sms-valid']);
			wp_send_json_success('reload');
		}



		// Lets check to see if the user is idealing to long.
		if(isset($_POST['timeChecker']) && $_POST['timeChecker']){
			// Lets check if user is accessing a locked page.
			if($f_auth['auth_page_enabled']){
				foreach($f_auth['auth_page_enabled'] as $auth_page_enabled){
					// We check the current page id and also the page title of the 2fa.
					if(($auth_page_enabled['page_selection'][0] == get_the_ID()) || ronikdesigns_get_page_by_title('2fa') || ronikdesigns_get_page_by_title('mfa')){
						if($mfa_status !== 'mfa_unverified'){
							update_user_meta(get_current_user_id(), 'mfa_status', 'mfa_unverified');
							//Lets wipe out the session for sms_2fa_status
							unset($_SESSION['send-mfa']);
							wp_send_json_success('reload');
						}
						if($sms_2fa_status !== 'sms_2fa_unverified'){
							update_user_meta(get_current_user_id(), 'sms_2fa_status', 'sms_2fa_unverified');
							//Lets wipe out the session for sms_2fa_status
							unset($_SESSION['send-sms']);
							unset($_SESSION['sms-valid']);
							wp_send_json_success('reload');
						}
					} else {
						wp_send_json_success('noreload');
					}
				}
			}
			// Catch ALL 
			wp_send_json_success('noreload');
		}




		// Lets get the auth-select value.
		if(isset($_POST['auth-select']) && $_POST['auth-select']){
			if($_POST['auth-select'] == '2fa'){
                if($get_phone_number){ 
					update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
					$f_value['auth-select'] = "2fa";
					$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
					// We build a query and redirect back to 2fa route.
					wp_redirect( esc_url(home_url($r_redirect)) );
					exit;
				} else {	
					// Set session variables
					$_SESSION["auth-select"] = "2fa";
					$f_value['auth-select'] = "2fa";
					$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
					// We build a query and redirect back to 2fa route.
					wp_redirect( esc_url(home_url($r_redirect)) );
					exit;
				}
			} else {
				update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_mfa');
				// Set session variables
				$_SESSION["auth-select"] = "mfa";
				$f_value['auth-select'] = "mfa";
				$r_redirect = '/mfa/?'.http_build_query($f_value, '', '&amp;');
				// We build a query and redirect back to 2fa route.
				wp_redirect( esc_url(home_url($r_redirect)) );
				exit;	
			}
		}







		if(isset($_POST['auth-phone_number']) && $_POST['auth-phone_number']){
			// This is where api validation will be performed...
			update_user_meta(get_current_user_id(), 'sms_user_phone', $_POST['auth-phone_number']);
			// End api validation
			update_user_meta(get_current_user_id(), 'auth_status', 'auth_select_sms');
			// Update the status with timestamp.
			// Keep in mind all timestamp are within the UTC timezone. For constant all around.
			// https://www.timestamp-converter.com/
			// Get the current time.
			update_user_meta(get_current_user_id(), 'sms_2fa_status', $current_date);
			// Set session variables
			// $_SESSION["auth-phone_number"] = "valid";
			$f_value['auth-phone_number'] = "valid";
			$r_redirect = '/auth/?'.http_build_query($f_value, '', '&amp;');
			// We build a query and redirect back to 2fa route.
			wp_redirect( esc_url(home_url($r_redirect)) );
			exit;
		}
		









		if(isset($_POST['send-sms-code']) && $_POST['send-sms-code']){
			if($get_current_secret == $_POST['send-sms-code']){
				update_user_meta(get_current_user_id(), 'sms_2fa_status', $current_date);
				$f_value['sms-success'] = "success";
				// Set session variables
				$_SESSION["sms-valid"] = "valid";
				// $f_value['sms-valid'] = "true";
				$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');

				// We build a query and redirect back to 2fa route.
				wp_redirect( esc_url(home_url($r_redirect)) );
				exit;
			} else {
				$f_value['sms-error'] = "nomatch";
			}
			// Set session variables
			$_SESSION["send-sms"] = "invalid";
			$_SESSION["sms-valid"] = "invalid";
			$f_value['sms-valid'] = "false";
			$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
			// We build a query and redirect back to 2fa route.
			wp_redirect( esc_url(home_url($r_redirect)) );
			exit;
		}




		// Lets check the POST parameter to see if we want to send the MFA code.
		if(isset($_POST['google2fa_code']) && $_POST['google2fa_code']){
			$mfa_status = get_user_meta(get_current_user_id(),'mfa_status', true);
			$get_current_secret = get_user_meta(get_current_user_id(), 'google2fa_secret', true);
			$google2fa = new Google2FA();

			error_log(print_r( 'abc', true));

			if ( $mfa_status == 'mfa_unverified' ) {
                // Lets save the google2fa_secret to the current user_meta.
                $code = $_POST["google2fa_code"];
                $valid = $google2fa->verifyKey($get_current_secret, $code);
                if ($valid) {
					update_user_meta(get_current_user_id(), 'mfa_validation', 'valid');
                    update_user_meta(get_current_user_id(), 'mfa_status', $current_date);
					// Set session variables
					$_SESSION["send-mfa"] = "valid";
					// $f_value['send-mfa'] = "true";
                }
            }  else {
                $valid = false;
				// Set session variables
				$_SESSION["send-mfa"] = "invalid";
				// $f_value['send-mfa'] = "false";
            }

			$r_redirect = '/mfa/?'.http_build_query($f_value, '', '&amp;');
			// We build a query and redirect back to 2fa route.
			wp_redirect( esc_url(home_url($r_redirect)) );
			exit;
		}




		// Lets check the POST parameter to see if we want to send the sms code.
		if(isset($_POST['send-sms']) && $_POST['send-sms']){
			// Lets store the sms_2fa_secret data inside the current usermeta. 
			if(get_user_meta(get_current_user_id(),'sms_2fa_secret', true)){
				update_user_meta(get_current_user_id(), 'sms_2fa_secret', $sms_2fa_secret);
			} else {
				add_user_meta(get_current_user_id(), 'sms_2fa_secret', $sms_2fa_secret);
			}

			// The phone stuff.. We generate a sms message and send it to the current user
			if( $f_auth['twilio_id'] && $f_auth['twilio_token'] && $f_auth['twilio_number'] ){
				$account_sid = $f_auth['twilio_id'];
				$auth_token = $f_auth['twilio_token'];
				// A Twilio number you own with SMS capabilities
				$twilio_number = $f_auth['twilio_number'];
				// Current user phone number.
				$to_number = '6316174271';
				$client = new Client($account_sid, $auth_token);
				$client->messages->create(
					// Where to send a text message (your cell phone?)
					$to_number,
					array(
						'from' => $twilio_number,
						'body' => 'Your verification code is '.$sms_2fa_secret
					)
				);
			}

			// Set session variables
			$_SESSION["send-sms"] = "valid";
			$f_value['send-sms'] = "true";
			$r_redirect = '/2fa/?'.http_build_query($f_value, '', '&amp;');
			// We build a query and redirect back to 2fa route.
			wp_redirect( esc_url(home_url($r_redirect)) );
			exit;
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

	

	/**
	 * Init Page Migration, Basically swap out the original link with the new link.
	 */
	function ajax_do_init_page_migration()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}
		$f_url_migration = get_field('page_url_migration', 'options');
		if ($f_url_migration) {
			foreach ($f_url_migration as $key => $url_migration) {
				// CHECK if both fields are populated.
				if ($url_migration['original_link'] && $url_migration['new_link']) {
					// Lets convert the given url to post ids.
					$original_link = url_to_postid($url_migration['original_link']['url']);
					$new_link = url_to_postid($url_migration['new_link']['url']);
					// Check if 0 is present in the both variables. url_to_postid return Post ID, or 0 on failure.
					if ($original_link !== 0 && $new_link !== 0) {
						$original_post_slug = get_post_field('post_name', $original_link);
						// First we have to draft the orginal link and change the post_name.
						wp_update_post(array(
							'ID'    =>  $original_link,
							'post_name' => $original_post_slug . '-drafted',
							'post_status' => array('draft'),
						));
						// Second we have to take the $original_post_slug and remove the -drafted string
						$modified_original_post_slug = str_ireplace('-drafted', '', $original_post_slug);
						wp_update_post(array(
							'ID'    =>  $new_link,
							'post_name' => $modified_original_post_slug,
							'post_status' => 'publish', // Change to publish status.
							'post_password' => '' // This is critical we empty the password value for it to become no longer private.
						));
						// This will be our way for logging post migration status.
						update_option('options_page_url_migration_' . $key . '_migration-status', 'Success: Before rerunning please remove any successful rows!');
					} else {
						update_option('options_page_url_migration_' . $key . '_migration-status', 'Failure: Please check the provided url!');
					}
				} else {
					update_option('options_page_url_migration_' . $key . '_migration-status', 'Failure: Please check the provided url!');
				}
			}
		} else {
			// If no rows are found send the error message!
			wp_send_json_error('No rows found!');
		}
		// Send sucess message!
		wp_send_json_success('Done');
	}






	/**
	 * Init Unused Media Migration.
	 */
	function ajax_do_init_unused_media_migration()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}
		function ronikdesigns_timeout_extend( $time ){
			// Default timeout is 5
			return 20;
		}
		add_filter( 'http_request_timeout', 'ronikdesigns_timeout_extend' );

		function recursive_delete($number){
			$post_type = get_field('page_media_cleaner_post_type_field_ronikdesign', 'options');	
			$select_post_type = $post_type;
			$post_mime_type = get_field('page_media_cleaner_post_mime_type_field_ronikdesign', 'options');			
			// https://developer.wordpress.org/reference/hooks/mime_types/
			if($post_mime_type){
				$select_attachment_type = array();
				foreach($post_mime_type as $type){
					if($type == 'jpg'){
						$select_attachment_type['jpg'] = "image/jpg";
						$select_attachment_type['jpeg'] = "image/jpeg";
						$select_attachment_type['jpe'] = "image/jpe";			
					} else if($type == 'gif'){
						$select_attachment_type['gif'] = "image/gif";	
					} else if($type == 'png'){
						$select_attachment_type['png'] = "image/png";	
					} else if($type == 'pdf'){
						$select_attachment_type['pdf'] = "application/pdf";	
					} else if($type == 'video'){
						$select_attachment_type['asf|asx'] = "video/x-ms-asf";	
						$select_attachment_type['wmv'] = "video/x-ms-wmv";	
						$select_attachment_type['wmx'] = "video/x-ms-wmx";	
						$select_attachment_type['wm'] = "video/x-ms-wm";	
						$select_attachment_type['avi'] = "video/avi";	
						$select_attachment_type['divx'] = "video/divx";	
						$select_attachment_type['flv'] = "video/x-flv";	
						$select_attachment_type['mov|qt'] = "video/quicktime";	
						$select_attachment_type['mpeg|mpg|mpe'] = "video/mpeg";	
						$select_attachment_type['mp4|m4v'] = "video/mp4";	
						$select_attachment_type['ogv'] = "video/ogg";	
						$select_attachment_type['webm'] = "video/webm";	
						$select_attachment_type['mkv'] = "video/x-matroska";
					} else if($type == 'misc'){
						$select_attachment_type['js'] = "application/javascript";	
						$select_attachment_type['pdf'] = "application/pdf";	
						$select_attachment_type['tar'] = "application/x-tar";	
						$select_attachment_type['zip'] = "application/zip";	
						$select_attachment_type['gz|gzip'] = "application/x-gzip";	
						$select_attachment_type['rar'] = "application/rar";	
						$select_attachment_type['txt|asc|c|cc|h|srt'] = "text/plain";	
						$select_attachment_type['csv'] = "text/csv";	
					}
				}
			}
			$select_numberposts = get_field('page_media_cleaner_numberposts_field_ronikdesign', 'options');
			$offsetValue = $number * $select_numberposts;
			$select_post_status = array('publish', 'pending', 'draft', 'private', 'future');

			// Lets gather all the image id of the entire application.
				// We receive all the image id.
				error_log(print_r('Gather All Image ID of the entire website.' , true));
				$allimagesid = get_posts( array(
					'post_type' => 'attachment',
					// 'posts_per_page' => 2,
					'offset' => $offsetValue,
					// 'numberposts' => 20, // Do not add more then 150.
					// 'numberposts' => -1, // Do not add more then 150.
					'numberposts' => $select_numberposts,
					'fields' => 'ids',
					'post_mime_type' => $select_attachment_type,
					'orderby' => 'date', 
					'order'  => 'DESC',
				));
				// This will allow us to collect all the image ids.
				$main_image_ids = array();
				if ($allimagesid) {
					foreach ($allimagesid as $imageID){
						$main_image_ids[] = $imageID;
					}
				}

			// CHECKPOINT 1
				error_log(print_r('Get all image ids: '.count($main_image_ids) , true));
				error_log(print_r('CHECKPOINT 1' , true));
				// sleep(1);
			// Lets get all of the pages, posts and custom post types of the entire application. Thumbnail.
				$get_all_post_pages = get_posts( array(
					'post_type' => $select_post_type,
					'numberposts' => -1,
					'fields' => 'ids',
					'post_status'  => $select_post_status,
					'orderby' => 'date', 
					'order'  => 'DESC',
				));				
				$all_post_thumbnail_ids = array();
				$all_image_attachement_ids = array();
				if ($get_all_post_pages) {
					foreach ($get_all_post_pages as $pageID){
						$attachments = get_posts( array(
							'post_type' => 'attachment',
							'numberposts' => -1,
							'fields' => 'ids',
							'post_parent' => $pageID,
							'post_mime_type' => $select_attachment_type,
							'orderby' => 'date', 
							'order'  => 'DESC',
						));
						if ($attachments) {
							foreach ($attachments as $attachmentID){
								$all_image_attachement_ids[] = $attachmentID;
							}
						}
						if( get_post_thumbnail_id( $pageID ) ){
							$all_post_thumbnail_ids[] = get_post_thumbnail_id( $pageID );
						}
					}
				}
	
			// Lets remove any duplicated matches & set to new array.
				// First let remove the thumbnail id from the bulk main id array
				if($all_post_thumbnail_ids){
					$arr_checkpoint_1a = array_values(array_diff($main_image_ids, $all_post_thumbnail_ids) );
				} else{
					$arr_checkpoint_1a = $main_image_ids;
				}
				// Second let remove any image id that has a image attachment associated with it id.
				if($all_image_attachement_ids){
					$arr_checkpoint_1b = array_values(array_diff($arr_checkpoint_1a, $all_image_attachement_ids) );
				} else {
					$arr_checkpoint_1b = $arr_checkpoint_1a;
				}

			// CHECKPOINT COMPLETE
				error_log(print_r('Remove thumbnail id from bulk main id array: '.count($arr_checkpoint_1a) , true));
				error_log(print_r('Remove images attachment from bulk main id array: '.count($arr_checkpoint_1b) , true));
				error_log(print_r('CHECKPOINT 1 COMPLETE' , true));
				// sleep(1);

			// CHECKPOINT 2
				error_log(print_r('CHECKPOINT 2' , true));

				$wp_postsid_gutenberg_image_array = array();
				$wp_posts_gutenberg_image_array = array();
				if($arr_checkpoint_1b){
					foreach($arr_checkpoint_1b as $a => $image_id){
						// This will search for the image id within the posts. This is primarily for Gutenberg Block Editor. The image id is stored within the post content...
						$f_postsid = get_posts(
							array(
								'post_status'  => $select_post_status,
								'post_type' => $select_post_type,
								'fields' => 'ids',		
								'posts_per_page' => -1,
								's'  => ':'.$image_id,
								'orderby' => 'date', 
								'order'  => 'DESC',
							),
						);
						if($f_postsid){
							foreach($f_postsid as $b => $postsid){
								$wp_postsid_gutenberg_image_array[] = $image_id;
							}
						}

						// lets get the attached file name. Search through the wp_posts data table. This is not ideal, but is the only good way to search for imageid for gutenberg blocks. Plus any images that are inserted into posts manually.
						$f_attached_file = get_attached_file( $image_id );
						$pieces = explode('/', $f_attached_file ) ;
						$f_postsattached = get_posts( array(
							'post_status'  => $select_post_status,
							'post_type' => $select_post_type,
							'fields' => 'ids',		
							'posts_per_page' => -1,
							's'  => end($pieces),
							'orderby' => 'date', 
							'order'  => 'DESC',
						) );
						if($f_postsattached){
							foreach($f_postsattached as $key => $posts){
								if($posts->ID){
									$wp_posts_gutenberg_image_array[] = $image_id;
								}
							}
						}
					}
				}
				
			// Lets remove any duplicated matches & set to new array.
				// First let remove the Gutenberg id from the bulk main id array
				if($wp_postsid_gutenberg_image_array){
					$arr_checkpoint_2a = array_values(array_diff($arr_checkpoint_1b, $wp_postsid_gutenberg_image_array) );
				} else{
					$arr_checkpoint_2a = $arr_checkpoint_1b;
				}
				if($wp_posts_gutenberg_image_array){
					$arr_checkpoint_2b = array_values(array_diff($arr_checkpoint_2a, $wp_posts_gutenberg_image_array) );
				} else {
					$arr_checkpoint_2b = $arr_checkpoint_2a;
				}
				// 'reindex' array to cleanup...
				$arr_checkpoint_2c = array_values(array_filter($arr_checkpoint_2b)); 

			// CHECKPOINT COMPLETE
				error_log(print_r('Remove Gutenberg id from bulk main id array: '.count($arr_checkpoint_2a) , true));
				error_log(print_r('Remove Gutenberg Image from bulk main id array: '.count($arr_checkpoint_2b) , true));
				error_log(print_r('Reindex Array: '.count($arr_checkpoint_2c) , true));
				error_log(print_r('CHECKPOINT 2 COMPLETE' , true));
				// sleep(1);

			// CHECKPOINT 3
				error_log(print_r('CHECKPOINT 3' , true));
			
				$wp_postsmeta_acf_repeater_image_array = array();
				$wp_postsmeta_acf_repeater_image_url_array = array();
				if($arr_checkpoint_2c){
					foreach($arr_checkpoint_2c as $image_id){

						// This part is critical we check all the postmeta for any image ids in the acf serialized array. AKA any repeater fields or gallery fields.
						$f_posts = get_posts( array(
							'fields' => 'ids',
							'post_type' => $select_post_type,
							'post_status'  => $select_post_status,
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
								'value' => sprintf(':"%s";', $image_id),
								'compare' => 'LIKE',
								)
							),
							'orderby' => 'date', 
							'order'  => 'DESC',
						) );
						if($f_posts){
							foreach($f_posts as $key => $posts){
								if($posts){
									$wp_postsmeta_acf_repeater_image_array[] = $image_id;
								}
							}
						}

						// This part is critical we check all the postmeta for any image ids in the acf serialized array. AKA any repeater fields or gallery fields.		
						$f_posts_2 = get_posts( array(
							'fields' => 'ids',
							'post_type' => $select_post_type,
							'post_status'  => $select_post_status,
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
									'value' => sprintf(':"%s";', get_attached_file($image_id)),
									'compare' => 'LIKE',
								)
							),
							'orderby' => 'date', 
							'order'  => 'DESC',
						));
						if($f_posts_2){
							foreach($f_posts_2 as $key => $posts){
								if($posts){
									$wp_postsmeta_acf_repeater_image_url_array[] = $image_id;
								}
							}
						}
					}	
				}	


			// Lets remove any duplicated matches & set to new array.
				// First let remove the Gutenberg id from the bulk main id array
				if($wp_postsmeta_acf_repeater_image_array){
					$arr_checkpoint_3a = array_values(array_diff($arr_checkpoint_2c, $wp_postsmeta_acf_repeater_image_array) );
				} else{
					$arr_checkpoint_3a = $arr_checkpoint_2c;
				}
				if($wp_postsmeta_acf_repeater_image_url_array){
					$arr_checkpoint_3b = array_values(array_diff($arr_checkpoint_3a, $wp_postsmeta_acf_repeater_image_url_array) );
				} else{
					$arr_checkpoint_3b = $arr_checkpoint_3a;
				}

			// CHECKPOINT COMPLETE
				error_log(print_r('Postmeta for any image ids in the acf serialized array: '.count($arr_checkpoint_3a) , true));
				error_log(print_r('Postmeta for any image url in the acf serialized array: '.count($arr_checkpoint_3b) , true));
				error_log(print_r('CHECKPOINT 3 COMPLETE' , true));
				// sleep(1);

			// CHECKPOINT 4
			error_log(print_r('CHECKPOINT 4' , true));

				$wp_postsmeta_acf_array = array();
				if($arr_checkpoint_3b){
					foreach($arr_checkpoint_3b as $image_id){

						$f_posts = get_posts( array(
							'fields' => 'ids',
							'post_type' => $select_post_type,
							'post_status'  => $select_post_status,
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
								'value' => $image_id,
								'compare' => '==',
								)
							),
							'orderby' => 'date', 
							'order'  => 'DESC',
						));
						if($f_posts){
							foreach($f_posts as $key => $posts){
								if($posts){
									$wp_postsmeta_acf_array[] = $image_id;
								}
							}
						}		
					}	
				}	

			// This part is critical we check all the postmeta for any image ids in the meta value
				if($wp_postsmeta_acf_array){
					$arr_checkpoint_4a = array_values(array_diff($arr_checkpoint_3b, $wp_postsmeta_acf_array) );
				} else{
					$arr_checkpoint_4a = $arr_checkpoint_3b;
				}
				$arr_checkpoint_4b = array_values(array_filter($arr_checkpoint_4a)); // 'reindex' array to cleanup...


			// CHECKPOINT COMPLETE
			error_log(print_r('Postmeta for any image ids in the acf array: '.count($arr_checkpoint_4a) , true));
			error_log(print_r('Reindex: '.count($arr_checkpoint_4b) , true));
			error_log(print_r('CHECKPOINT 4 COMPLETE' , true));
			// sleep(1);

			// CHECKPOINT 5
				error_log(print_r('CHECKPOINT 5' , true));
										
				$wp_infiles_array = array();
				if($arr_checkpoint_4b){
					foreach($arr_checkpoint_4b as $image_id){
						$wp_infiles_array[] = ronikdesigns_receiveAllFiles_ronikdesigns($image_id);						
					}	
				}	

			// This part is critical we check all the php files within the active theme directory.
				if($wp_infiles_array){
					$arr_checkpoint_5a = array_values(array_diff($arr_checkpoint_4b, $wp_infiles_array) );
				} else{
					$arr_checkpoint_5a = $arr_checkpoint_4b;
				}

			// CHECKPOINT COMPLETE
				error_log(print_r('Check all the php files within the active theme directory: '.count($arr_checkpoint_5a) , true));
				error_log(print_r('CHECKPOINT 5 COMPLETE' , true));
				// sleep(1);

			return array_values(array_filter($arr_checkpoint_5a)); // 'reindex' array to cleanup...
		}
		// Warning this script will slow down the entire server. Use only a small amount at a time.
		$f_offset_value_end = get_field('page_media_cleaner_offset_field_ronikdesign', 'options');
		$f_offset_value_start = $f_offset_value_end - 5;
		$image_array = array();
		foreach ( range( $f_offset_value_start, $f_offset_value_end ) as $number) {
			$image_array[] = recursive_delete($number);
		}
		// remove empty and re-arrange image array
		$image_array = array_values(array_filter($image_array));
		$image_array = array_unique(array_merge(...$image_array));


		error_log(print_r(memory_get_usage(true), true));
		error_log(print_r(memory_get_usage(), true));

		error_log(print_r('Final Results', true));
		error_log(print_r($image_array, true));
		if($image_array){
			// Get the array count..
			update_option( 'options_page_media_cleaner_field' , count($image_array) );
			foreach( $image_array as $key => $f_result ){
				update_option('options_page_media_cleaner_field_' . $key . '_file_size', ((filesize(get_attached_file($f_result)))/1000)/1000);
				update_option('options_page_media_cleaner_field_' . $key . '_image_id', $f_result);
				update_option('options_page_media_cleaner_field_' . $key . '_image_url', get_attached_file($f_result) );
				update_option('options_page_media_cleaner_field_' . $key . '_thumbnail_preview', $f_result);

				if( $f_result == end($image_array) ){
					// Sleep for 2 seconds...
					// sleep(1);
					// Send sucess message!
					wp_send_json_success('Done');
				}
			}
		} else {
			// If no rows are found send the error message!
			wp_send_json_error('No rows found!');
		}
	}



	/**
	 * Init Remove Unused Media .
	*/
	function ajax_do_init_remove_unused_media()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}

		$f_media_cleaner = get_field('page_media_cleaner_field', 'options');
		if($f_media_cleaner){
			foreach($f_media_cleaner as $key => $media_cleaner){

				// First lets copy the full image to the ronikdetached folder.
				$upload_dir   = wp_upload_dir();
				$link = wp_get_attachment_image_url( $media_cleaner['image_id'], 'full' );
				$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $link);
				$file_name = explode('/', $link);	
				
				//Year in YYYY format.
				$year = date("Y");
				//Month in mm format, with leading zeros.
				$month = date("m");
				//Day in dd format, with leading zeros.
				$day = date("d");
				//The folder path for our file should be YYYY/MM/DD
				$directory = dirname(__FILE__, 2).'/ronikdetached/'."$year/$month/$day/";
				//If the directory doesn't already exists.
				if(!is_dir($directory)){
					//Create our directory.
					mkdir($directory, 0777, true);
				}
				copy($file_path , $directory.end($file_name));

				// Delete attachment from database only, not file
				$delete_attachment = wp_delete_attachment( $media_cleaner['image_id'] , true);
				if($delete_attachment){
					//Delete attachment file from disk
					unlink( get_attached_file( $media_cleaner['image_id'] ) );
					error_log(print_r('File Deleted', true));
					update_option('options_page_media_cleaner_field_' . $key . '_file_size',  '');
					update_option('options_page_media_cleaner_field_' . $key . '_image_id',  '');
					update_option('options_page_media_cleaner_field_' . $key . '_image_url', '' );
					update_option('options_page_media_cleaner_field_' . $key . '_thumbnail_preview', '');
				}

				if( $media_cleaner == end($f_media_cleaner) ){
					// Get the array count..
					update_option( 'options_page_media_cleaner_field' , '' );
					// sleep(1);				
					// Send sucess message!
					wp_send_json_success('Done');
				}

			}
		} else {
			// If no rows are found send the error message!
			wp_send_json_error('No rows found!');
		}
		// Send sucess message!
		wp_send_json_success('Done');
	}




	function ajax_do_init_sms_verification() {
		// // Check if user is logged in.
		// if (!is_user_logged_in()) {
		// 	return;
		// }
		// $user_id = get_current_user_id();
		error_log(print_r( 'e', true));
		// $meta_key = 'user_click_actions';

		// $current_data = get_user_meta( $user_id, $meta_key, true );

		// if($current_data){
		// 	// error_log(print_r($current_data, true));
		// 	$current_data[] = array(
		// 		'action' => $_POST['click_action'],
		// 		'timestamp' => time(),
		// 		'url' => $_POST['point_origin']
		// 	);
		// 	update_user_meta( $user_id, $meta_key, $current_data );
		// } else {
		// 	$current_data = array(
		// 		'action' => $_POST['click_action'],
		// 		'timestamp' => time(),
		// 		'url' => $_POST['point_origin']
		// 	);
		// 	update_user_meta( $user_id, $meta_key, $current_data );

		// }
	}
	


	function ajax_do_init_analytics() {
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}
		$user_id = get_current_user_id();
		$meta_key = 'user_click_actions';

		$current_data = get_user_meta( $user_id, $meta_key, true );

		if($current_data){
			// error_log(print_r($current_data, true));
			$current_data[] = array(
				'action' => $_POST['click_action'],
				'timestamp' => time(),
				'url' => $_POST['point_origin']
			);
			update_user_meta( $user_id, $meta_key, $current_data );
		} else {
			$current_data = array(
				'action' => $_POST['click_action'],
				'timestamp' => time(),
				'url' => $_POST['point_origin']
			);
			update_user_meta( $user_id, $meta_key, $current_data );

		}
	}
}