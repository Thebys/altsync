<?php
/**
 * Plugin Name:       AltSync
 * Description:       Synchronizes updated media library alt text into posts, replacing empty / stale alt attributes.
 * Version:           0.4.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Tomáš "Thebys" Biheler
 * Author URI:        https://biheler.eu
 * License:           No License
 * Text Domain:       altsync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
define( 'ALTSYNC_VERSION', '0.4.1' );
// @phpstan-ignore-next-line
define( 'ALTSYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// @phpstan-ignore-next-line
define( 'ALTSYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ALTSYNC_PLUGIN_DIR . 'includes/class-altsync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, 
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_altsync() {

	$plugin = new AltSync();
	$plugin->run();

}
run_altsync();
