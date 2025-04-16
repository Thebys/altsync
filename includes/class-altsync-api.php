<?php

/**
 * The API functionality of the plugin.
 *
 * @link       https://your-website.com/
 * @since      0.4.0
 *
 * @package    AltSync
 * @subpackage AltSync/includes
 */

/**
 * The API functionality of the plugin.
 *
 * Defines the plugin API endpoints for remote synchronization.
 *
 * @package    AltSync
 * @subpackage AltSync/includes
 * @author     Tomáš "Thebys" Biheler
 */
class AltSync_API {

    /**
     * The ID of this plugin.
     *
     * @since    0.4.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    0.4.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The admin class instance to access sync functionality.
     *
     * @since    0.4.0
     * @access   private
     * @var      AltSync_Admin    $admin    The admin class instance.
     */
    private $admin;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.4.0
     * @param    string       $plugin_name    The name of the plugin.
     * @param    string       $version        The version of this plugin.
     * @param    AltSync_Admin $admin         The admin class instance.
     */
    public function __construct($plugin_name, $version, $admin) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->admin = $admin;
    }

    /**
     * Register the REST API endpoints.
     *
     * @since    0.4.0
     */
    public function register_endpoints() {
        register_rest_route('altsync/v1', '/sync-image', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_sync_image'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args'                => array(
                'attachment_id' => array(
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && intval($param) > 0;
                    },
                    'sanitize_callback' => 'absint',
                ),
                'sync_mode' => array(
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return in_array($param, array('empty', 'all'));
                    },
                ),
            ),
        ));

        // Add status endpoint to check if the plugin is active and ready
        register_rest_route('altsync/v1', '/status', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'handle_status_check'),
            'permission_callback' => '__return_true', // Publicly accessible
        ));
    }

    /**
     * Check if the request has valid application password authentication.
     *
     * @since    0.4.0
     * @param    WP_REST_Request $request The request object.
     * @return   bool Whether the request has valid auth.
     */
    public function check_api_permission($request) {
        // Check if the user is authenticated with application password
        return is_user_logged_in() && current_user_can('upload_files');
    }

    /**
     * Handle the sync image API request.
     *
     * @since    0.4.0
     * @param    WP_REST_Request $request The request object.
     * @return   WP_REST_Response The response object.
     */
    public function handle_sync_image($request) {
        $attachment_id = $request->get_param('attachment_id');
        $sync_mode = $request->get_param('sync_mode');
        
        // Ensure attachment exists and is an image
        $mime_type = get_post_mime_type($attachment_id);
        if (!$mime_type || strpos($mime_type, 'image/') !== 0) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Attachment is not an image or does not exist.', 'altsync'),
                ),
                400
            );
        }

        // Get the alt text from the media library
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (empty(trim($alt_text))) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Cannot sync empty alt text. Please set alt text in the media library first.', 'altsync'),
                ),
                400
            );
        }

        // Find posts using this image
        $posts_using_image = $this->admin->find_posts_using_image($attachment_id);
        if (empty($posts_using_image)) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __('No posts found using this image.', 'altsync'),
                    'updated_count' => 0,
                ),
                200
            );
        }

        // Perform sync based on mode
        if ($sync_mode === 'empty') {
            $result = $this->admin->update_posts_with_empty_alt($attachment_id, $posts_using_image, $alt_text);
        } else { // 'all' mode
            $result = $this->admin->update_posts_with_any_alt($attachment_id, $posts_using_image, $alt_text);
        }

        if ($result > 0) {
            $message = sprintf(
                /* translators: %d: number of posts updated. */
                __('Alt text synced to %d posts.', 'altsync'),
                $result
            );
        } else {
            $message = __('No posts required updates.', 'altsync');
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => $message,
                'updated_count' => $result,
            ),
            200
        );
    }

    /**
     * Handle the status check API request.
     *
     * @since    0.4.0
     * @return   WP_REST_Response The response object with plugin status.
     */
    public function handle_status_check() {
        return new WP_REST_Response(
            array(
                'success' => true,
                'status'  => 'active',
                'version' => $this->version,
                'message' => __('AltSync plugin is active and ready to use.', 'altsync'),
            ),
            200
        );
    }
} 