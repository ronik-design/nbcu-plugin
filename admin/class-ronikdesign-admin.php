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
				'capability' => 'manage_network_users',
				'page_title'  => __('Developer General Settings'),
				'menu_title'  => __('Developer Settings'),
				'menu_slug'     => 'developer-settings',
				// 'parent_slug' => $parent['menu_slug'],
				'redirect'    => false,
			));
			// Add sub page.
			$child = acf_add_options_page(array(
				'capability' => 'manage_network_users',
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

	//* delete transient
	function ronikdesigns_delete_custom_csp_transient(){
		delete_transient('csp_allow_fonts_scripts_santized');
		delete_transient('csp_allow_scripts_santized');
	}

	function ronikdesigns_acf_op_init_functions()
	{
		// Include the Wp Functions.
		foreach (glob(dirname(__FILE__) . '/wp-functions/*.php') as $file) {
			include $file;
		}
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


	// https://together.nbcudev.local/wp-admin/admin-ajax.php?action=ronikdesigns_admin_user_email_swap&search=hard&type=soft
	// https://together.nbcudev.local/wp-admin/admin-ajax.php?action=ronikdesigns_admin_user_email_swap&search=soft&type=medium
	function ronikdesigns_admin_user_email_swap(){
		// Check if user is logged in.
		if (!is_user_logged_in()) {
			return;
		}
		// Check if user is a super admin.
		if (!is_super_admin(get_current_user_id())) {
			return;
		}
		$helper = new RonikHelper;

		// Template should be csv file two columns first column old email and second column new email.
		$target_files = glob(dirname(__FILE__) . '/email-swapper/email-swapper.csv');
		if($target_files){
			$user_array = array();
			foreach($target_files as $i => $target_file){
				$file = fopen($target_file, 'r');
				while ( ($line = fgetcsv($file)) !== FALSE) {
					// We validate the email address before storing into the array.
					if (filter_var($line[0], FILTER_VALIDATE_EMAIL) && filter_var($line[1], FILTER_VALIDATE_EMAIL)) {
						$user_array[] = $line;
					}
				}
				fclose($file);
			}


			if( $user_array ){
				global $wpdb;
				$success_user_list = array();
				$search_level = isset($_GET['search']) && $_GET['search'] ? $_GET['search'] : "soft";
				$swap_level = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : "soft";

				foreach($user_array as $j => $user){
					// We get the first value from the array.
					$f_get_user_data = get_user_by( 'email', $user[0] );

					// We check if the f_get_user_data is not empty!
					if ( ! empty( $f_get_user_data ) ) {
						// Old Email
						$f_old_user_email = $user[0];
						// New Email
						$f_new_user_email = $user[1];
						// Store the target id.
						$f_user_id = $f_get_user_data->data->ID;

						//
						if( $search_level == 'hard' ){
							// Update the wp_3_postmeta
							$meta_key_target_0 = $helper->ronik_database_update('wp_3_postmeta', 'meta_key', 'meta_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, true, $swap_level);
                            $meta_key_target_0b = $helper->ronik_database_update('wp_6_postmeta', 'meta_key', 'meta_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, true, $swap_level);
                        } else {
							$meta_key_target_0 = ' Database Table wp_3_postmeta: Not Synced!';
                            $meta_key_target_0b = ' Database Table wp_6_postmeta: Not Synced!';
						}

						if($swap_level == 'hard'){
							$meta_key_target_0 = $helper->ronik_database_update('wp_3_postmeta', 'meta_key', 'meta_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, true, $swap_level);
                            $meta_key_target_0b = $helper->ronik_database_update('wp_6_postmeta', 'meta_key', 'meta_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, true, $swap_level);
                        }

						// Update the wp_usermeta
						$meta_key_target_1 = $helper->ronik_database_update('wp_usermeta', 'meta_key', 'meta_value' , 'user_id', $f_user_id, $f_old_user_email, $f_new_user_email, false, $swap_level);

						// Update the wp_sitemeta
						$meta_key_target_2 = $helper->ronik_database_update('wp_sitemeta', 'meta_key', 'meta_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, false, $swap_level);

						// Update the wp_3_options
						$meta_key_target_3 = $helper->ronik_database_update('wp_3_options', 'option_name', 'option_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, false, $swap_level);

						// Update the wp_6_options
						$meta_key_target_4 = $helper->ronik_database_update('wp_6_options', 'option_name', 'option_value' , false, $f_user_id, $f_old_user_email, $f_new_user_email, false, $swap_level);

                        if($swap_level == 'hard' || $swap_level == 'medium'){
							// Target the do_users table!
							// We have to target this way since its a custom table
							$wpdb->query(
								$wpdb->prepare(
									"UPDATE do_users SET user_login = %s ,user_email = %s WHERE ID = %d", $f_new_user_email, $f_new_user_email, $f_user_id
								)
							);
                            $wp_meta_datas = $wpdb->get_results("select * from do_users where user_account_updates LIKE '%$f_old_user_email%'");
                            if($wp_meta_datas){
                                // Loop through all the rows.
                                foreach($wp_meta_datas  as $key =>  $wp_meta_data){
                                    $f_meta_value = $wp_meta_data->user_account_updates;
                                    if($helper->ronik_compare_like_compare($f_meta_value , $f_old_user_email)){
                                        if (str_contains($f_meta_value, ';s:')) {
                                            $f_meta_value_mod = str_replace( 's:'.strlen($f_old_user_email).':"'.$f_old_user_email.'"', 's:'.strlen($f_new_user_email).':"'.$f_new_user_email.'"', $f_meta_value);
                                        } else{
                                            $f_meta_value_mod = str_replace( $f_old_user_email, $f_new_user_email, $f_meta_value);
                                        }

                                        $wpdb->query(
                                            $wpdb->prepare(
                                                "UPDATE do_users SET user_account_updates = %s WHERE ID = %d", $f_meta_value_mod, $f_user_id
                                            )
                                        );
                                    }
                                }
                            }
							// Update the
							$wpdb->query(
								$wpdb->prepare(
									"UPDATE wp_users SET user_login = %s ,user_email = %s WHERE ID = %d", $f_new_user_email, $f_new_user_email, $f_user_id
								)
							);
						}
						$success_user_list[] = $f_old_user_email . ' ' . $f_new_user_email . ' ' . $f_user_id . $meta_key_target_0 . $meta_key_target_0b . $meta_key_target_1 . $meta_key_target_2 . $meta_key_target_3 . $meta_key_target_4;
					}
				}

				if( !empty($success_user_list) ){
					wp_send_json_success( array(
						'response_counter' => count($success_user_list),
						'response' => $success_user_list,
					), 200 );
				} else {
					wp_send_json_success( array(
						'response' => 'No users were updated!',
					), 200 );
				}
			} else {
				wp_send_json_error('CSV File could not be parsed', '400');
			}
		} else {
			wp_send_json_error('CSV File Not Found', '400');
		}
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

}
