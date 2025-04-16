<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://your-website.com/
 * @since      0.1.0
 *
 * @package    AltSync
 * @subpackage AltSync/includes
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
 * @since      0.1.0
 * @package    AltSync
 * @subpackage AltSync/includes
 * @author     Tomáš "Thebys" Biheler
 */
class AltSync {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      AltSync_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.1.0
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
	 * @since    0.1.0
	 */
	public function __construct() {

		if ( defined( 'ALTSYNC_VERSION' ) ) {
			$this->version = ALTSYNC_VERSION;
		} else {
			$this->version = '0.1.0';
		}
		$this->plugin_name = 'altsync';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_api_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - AltSync_Loader. Orchestrates the hooks of the plugin.
	 * - AltSync_i18n. Defines internationalization functionality.
	 * - AltSync_Admin. Defines all hooks for the admin area.
	 * - AltSync_Public. Defines all hooks for the public side of the site.
	 * - AltSync_API. Defines API functionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once ALTSYNC_PLUGIN_DIR . 'includes/class-altsync-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once ALTSYNC_PLUGIN_DIR . 'includes/class-altsync-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once ALTSYNC_PLUGIN_DIR . 'admin/class-altsync-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		// require_once ALTSYNC_PLUGIN_DIR . 'public/class-altsync-public.php'; // Not needed for MVP

		/**
		 * The class responsible for defining all API functionality.
		 */
		require_once ALTSYNC_PLUGIN_DIR . 'includes/class-altsync-api.php';

		$this->loader = new AltSync_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the AltSync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new AltSync_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new AltSync_Admin( $this->get_plugin_name(), $this->get_version() );

		// Hook for enqueuing admin scripts and styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Removed individual image sync button in favor of bulk sync
		// $this->loader->add_filter( 'attachment_fields_to_edit', $plugin_admin, 'add_sync_alt_text_field', 10, 2 );

		// Add hook for handling the sync action via AJAX (kept for backward compatibility)
		$this->loader->add_action( 'wp_ajax_altsync_sync_alt_text', $plugin_admin, 'ajax_sync_alt_text' );
		
		// Add hooks for bulk sync functionality
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'wp_ajax_altsync_bulk_preview', $plugin_admin, 'ajax_bulk_preview' );
		$this->loader->add_action( 'wp_ajax_altsync_bulk_sync', $plugin_admin, 'ajax_bulk_sync' );

	}

	/**
	 * Register all of the hooks related to the API functionality
	 * of the plugin.
	 *
	 * @since    0.4.0
	 * @access   private
	 */
	private function define_api_hooks() {

		$plugin_admin = new AltSync_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_api = new AltSync_API( $this->get_plugin_name(), $this->get_version(), $plugin_admin );

		// Register the REST API endpoints when WordPress REST API is initialized
		$this->loader->add_action( 'rest_api_init', $plugin_api, 'register_endpoints' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function define_public_hooks() {

		// $plugin_public = new AltSync_Public( $this->get_plugin_name(), $this->get_version() );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.1.0
	 * @return    AltSync_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

} 