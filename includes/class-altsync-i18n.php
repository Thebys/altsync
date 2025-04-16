<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://your-website.com/
 * @since      0.1.0
 *
 * @package    AltSync
 * @subpackage AltSync/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.1.0
 * @package    AltSync
 * @subpackage AltSync/includes
 * @author     Tomáš "Thebys" Biheler
 */
class AltSync_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {

		// @phpstan-ignore-start
		load_plugin_textdomain(
			'altsync',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
		// @phpstan-ignore-end

	}

} 