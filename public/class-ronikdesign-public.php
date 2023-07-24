<?php

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
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ronikdesign-public.js', array($scriptName), $this->version, false);
			wp_enqueue_script($this->plugin_name . '2', plugin_dir_url(__FILE__) . 'assets/dist/app.min.js', array($scriptName), $this->version, false);
		} else {
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

}