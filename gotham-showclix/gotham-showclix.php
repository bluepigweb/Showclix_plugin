<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       Gotham Showclix
 * Description:       Sync and display Leap Events natively via custom DB structures, dynamic shortcodes, and calendars.
 * Version:           1.0.1
 * Author:            Jero
 * Text Domain:       gotham-showclix
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'GOTHAM_SHOWCLIX_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gotham-showclix-activator.php
 */
function activate_gotham_showclix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gotham-showclix-activator.php';
	Gotham_Showclix_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gotham-showclix-deactivator.php
 */
function deactivate_gotham_showclix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gotham-showclix-deactivator.php';
	Gotham_Showclix_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gotham_showclix' );
register_deactivation_hook( __FILE__, 'deactivate_gotham_showclix' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gotham-showclix.php';

/**
 * Begins execution of the plugin.
 */
function run_gotham_showclix() {

	$plugin = new Gotham_Showclix();
	$plugin->run();

}
run_gotham_showclix();
