<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.ronikdesign.com/
 * @since      1.0.0
 *
 * @package    Ronikdesign
 * @subpackage Ronikdesign/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ronikdesign
 * @subpackage Ronikdesign/includes
 * @author     Kevin Mancuso <kevin@ronikdesign.com>
 */
class Ronikdesign
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Ronikdesign_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('RONIKDESIGN_VERSION')) {
			$this->version = RONIKDESIGN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'ronikdesign';

		$this->load_dependencies();
		$this->set_locale();

		/* Checks to see if "is_plugin_active" function exists and if not load the php file that includes that function */
		if (!function_exists('is_plugin_active')) {
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		//plugin is activated ACF,
		if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
			$this->define_admin_hooks();
			$this->define_public_hooks();
		} else {
			if(!empty(get_mu_plugins()['acf.php'])){
				$this->define_admin_hooks();
				$this->define_public_hooks();
			}
		}

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ronikdesign_Loader. Orchestrates the hooks of the plugin.
	 * - Ronikdesign_i18n. Defines internationalization functionality.
	 * - Ronikdesign_Admin. Defines all hooks for the admin area.
	 * - Ronikdesign_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ronikdesign-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ronikdesign-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ronikdesign-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-ronikdesign-public.php';

		$this->loader = new Ronikdesign_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ronikdesign_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Ronikdesign_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Ronikdesign_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('acf/input/admin_enqueue_scripts', $plugin_admin, 'acf_enqueue_scripts', 99);


		// Hooking up our function to theme setup
		$this->loader->add_action('acf/init', $plugin_admin, 'ronikdesigns_acf_op_init');
		$this->loader->add_action('acf/init', $plugin_admin, 'ronikdesigns_acf_op_init_fields', 10);
		$this->loader->add_action('acf/init', $plugin_admin, 'ronikdesigns_acf_op_init_functions', 20);

		// $this->loader->add_action('admin_menu', $plugin_admin, 'remove_menus', 99);
		$this->loader->add_filter('upload_mimes', $plugin_admin, 'roniks_add_svg_mime_types', 99);

		// $this->loader->add_action( 'admin_init', $plugin_admin, 'remove_acf_options_page', 99);

		// Add admin posts
		$this->loader->add_action('admin_post_nopriv_ronikdesigns_admin_password_reset', $plugin_admin, 'ronikdesigns_admin_password_reset');
		$this->loader->add_action('admin_post_ronikdesigns_admin_password_reset', $plugin_admin, 'ronikdesigns_admin_password_reset');

		$this->loader->add_action('admin_post_nopriv_ronikdesigns_admin_auth_verification', $plugin_admin, 'ronikdesigns_admin_auth_verification');
		$this->loader->add_action('admin_post_ronikdesigns_admin_auth_verification', $plugin_admin, 'ronikdesigns_admin_auth_verification');

		// Add Ajax
		$this->loader->add_action('wp_ajax_nopriv_do_init_page_migration', $plugin_admin, 'ajax_do_init_page_migration');
		$this->loader->add_action('wp_ajax_do_init_page_migration', $plugin_admin, 'ajax_do_init_page_migration');

		$this->loader->add_action('wp_ajax_nopriv_do_init_unused_media_migration', $plugin_admin, 'ajax_do_init_unused_media_migration');
		$this->loader->add_action('wp_ajax_do_init_unused_media_migration', $plugin_admin, 'ajax_do_init_unused_media_migration');

		$this->loader->add_action('wp_ajax_nopriv_do_init_remove_unused_media', $plugin_admin, 'ajax_do_init_remove_unused_media');
		$this->loader->add_action('wp_ajax_do_init_remove_unused_media', $plugin_admin, 'ajax_do_init_remove_unused_media');

		$this->loader->add_action('wp_ajax_nopriv_do_init_analytics', $plugin_admin, 'ajax_do_init_analytics');
		$this->loader->add_action('wp_ajax_do_init_analytics', $plugin_admin, 'ajax_do_init_analytics');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Ronikdesign_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// This will add styles to the admin dashboard side
		// $this->loader->add_action('admin_enqueue_scripts', $plugin_public, 'enqueue_styles');

		// Verification API AJAX.
		$this->loader->add_action('wp_ajax_nopriv_do_verification', $plugin_public, 'ajax_do_verification');
		$this->loader->add_action('wp_ajax_do_verification', $plugin_public, 'ajax_do_verification');

		// SVG API AJAX.
		$this->loader->add_action('wp_ajax_nopriv_do_init_svg_migration_ronik', $plugin_public, 'ajax_do_init_svg_migration_ronik');
		$this->loader->add_action('wp_ajax_do_init_svg_migration_ronik', $plugin_public, 'ajax_do_init_svg_migration_ronik');

		// SVG Add Custom Route Path.
		$this->loader->add_filter('acf_icon_path_suffix', $plugin_public, 'acf_icon_path_suffix');
		$this->loader->add_filter('acf_icon_path', $plugin_public, 'acf_icon_path');
		$this->loader->add_filter('acf_icon_url', $plugin_public, 'acf_icon_url');

		// Lets add body class. This will allow us to hook into some powerful JS functions.
		$this->loader->add_action('body_class', $plugin_public, 'my_body_classes');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Ronikdesign_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
