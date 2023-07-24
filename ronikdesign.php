<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.ronikdesign.com/
 * @since             1.0.6.4
 * @package           Ronikdesign
 *
 * @wordpress-plugin
 * Plugin Name:       Ronik Designs Developer Toolbox
 * Plugin URI:        https://github.com/ronik-design/ronikdesign
 * Description:       Theme Code Optimizer. ACF is necessary for plugin to work correctly.
 * Version:           1.0.6.4
 * Author:            Kevin Mancuso
 * Author URI:        https://www.ronikdesign.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ronikdesign
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RONIKDESIGN_VERSION', '1.0.6.4' );



// $myUpdateChecker = PucFactory::buildUpdateChecker(
// 	'https://kmancuso.com/plugin.json',
// 	__FILE__, //Full path to the main plugin file or functions.php.
// 	'ronikdesign'
// );

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/ronik-design/ronikdesign',
	__FILE__,
	'ronikdesignPlugin'
);
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');
//Optional: If you're using a private repository, specify the access token like this:
// $myUpdateChecker->setAuthentication('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');




/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ronikdesign-activator.php
 */
function activate_ronikdesign() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ronikdesign-activator.php';
	Ronikdesign_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ronikdesign-deactivator.php
 */
function deactivate_ronikdesign() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ronikdesign-deactivator.php';
	Ronikdesign_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ronikdesign' );
register_deactivation_hook( __FILE__, 'deactivate_ronikdesign' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

// We need to make sure ACF is installed. Otherwise site may crash!
if(class_exists('ACF') ){
	require plugin_dir_path( __FILE__ ) . 'includes/class-ronikdesign.php';
} else {
	return;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ronikdesign() {

	$plugin = new Ronikdesign();
	$plugin->run();
}
run_ronikdesign();
