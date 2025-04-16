<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://your-website.com/
 * @since      0.1.0
 *
 * @package    AltSync
 * @subpackage AltSync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    AltSync
 * @subpackage AltSync/admin
 * @author     Your Name <email@example.com>
 */
class AltSync_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        // Enqueue admin scripts and styles
        // Use the loader provided by the main class to add this action
        // Note: We'll add this hook registration in the main AltSync class's define_admin_hooks method.
        // add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

    /**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
     * @param    string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
        // Individual image sync functionality has been removed in favor of bulk sync
        // Only load scripts on our bulk sync admin page
        if ( 'media_page_altsync-bulk' === $hook_suffix ) {
            wp_enqueue_script( $this->plugin_name . '-bulk', ALTSYNC_PLUGIN_URL . 'admin/js/altsync-bulk.js', array( 'jquery' ), $this->version, true );
            
            // Pass data to JavaScript
            wp_localize_script( $this->plugin_name . '-bulk', 'altsync_bulk', array(
                'nonce'               => wp_create_nonce( 'altsync_bulk_nonce' ),
                'no_images_selected'  => __( 'Please select at least one image.', 'altsync' ),
                'error_text'          => __( 'An error occurred. Please try again.', 'altsync' ),
                'no_updates_needed'   => __( 'No empty alt text instances found in any posts.', 'altsync' ),
                'preview_summary'     => __( 'Found %d posts with empty alt text that can be updated.', 'altsync' ),
                'preview_summary_all' => __( 'Found %d posts where alt text will be replaced.', 'altsync' ),
                'sync_summary'        => __( 'Successfully updated alt text in %d posts.', 'altsync' ),
                'no_updates_performed' => __( 'No updates were performed.', 'altsync' ),
                'alt_text'            => __( 'Alt Text', 'altsync' ),
                'affected_posts'      => __( 'Affected Posts', 'altsync' ),
                'updated_posts'       => __( 'Updated Posts', 'altsync' ),
                'confirm_sync'        => __( 'Are you sure you want to update the EMPTY alt text in affected posts? This action cannot be undone.', 'altsync' ),
                'confirm_sync_all'    => __( '⚠️ WARNING: You are about to replace ALL alt text for selected images, even if they already have alt text set in posts. This is destructive and cannot be undone. Have you backed up your database? If yes, click OK to proceed.', 'altsync' ),
            ));
            
            wp_enqueue_style( $this->plugin_name . '-admin', ALTSYNC_PLUGIN_URL . 'admin/css/altsync-admin.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name . '-bulk', ALTSYNC_PLUGIN_URL . 'admin/css/altsync-bulk.css', array(), $this->version, 'all' );
        }
	}

    /**
     * Adds a custom field/button to the attachment edit fields (list and grid view).
     *
     * @since 0.1.0
     * @param array   $form_fields An array of attachment form fields.
     * @param WP_Post $post        The WP_Post object for the attachment.
     * @return array Modified form fields.
     */
    public function add_sync_alt_text_field( $form_fields, $post ) {
        // Only add this for images
        if ( strpos( $post->post_mime_type, 'image/' ) !== 0 ) {
             return $form_fields;
        }

        $alt_text = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
        $is_alt_text_set = ! empty( trim( $alt_text ) );

        $button_html = sprintf(
            '<button type="button" class="button button-secondary altsync-sync-button" data-attachment-id="%1$d" %2$s title="%3$s">%4$s</button>' .
            '<span class="altsync-status spinner" style="display: none; float: none; vertical-align: middle; margin-left: 5px; visibility: visible;"></span>' .
            '<p class="description altsync-message" style="margin-top: 5px;"></p>',
            esc_attr( $post->ID ),
            $is_alt_text_set ? '' : 'disabled="disabled"',
            $is_alt_text_set ? esc_attr__( 'Find posts using this image and update empty alt text.', 'altsync' ) : esc_attr__( 'Please set Alt Text before syncing.', 'altsync' ),
            esc_html__( 'Sync Alt Text to Posts', 'altsync' )
        );

        $form_fields['altsync_sync'] = array(
            'label' => __( 'Sync Alt Text', 'altsync' ),
            'input' => 'html',
            'html'  => $button_html,
            'helps' => __( 'Updates posts using this image where the alt text is currently empty.', 'altsync' ),
        );

        return $form_fields;
    }

    /**
     * Handles the AJAX request to sync alt text.
     *
     * @since 0.1.0
     */
    public function ajax_sync_alt_text() {
        // Verify nonce
        if ( ! check_ajax_referer( 'altsync_sync_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'altsync' ) ), 403 );
            return;
        }

        // Check permissions and attachment ID
        if ( ! isset( $_POST['attachment_id'] ) || ! current_user_can( 'edit_post', (int) $_POST['attachment_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied or invalid attachment ID.', 'altsync' ) ), 403 );
            return;
        }

        $attachment_id = (int) $_POST['attachment_id'];

        // Ensure it's an image
        $mime_type = get_post_mime_type( $attachment_id );
        if ( strpos( $mime_type, 'image/' ) !== 0 ) {
             wp_send_json_error( array( 'message' => __( 'Attachment is not an image.', 'altsync' ) ), 400 );
             return;
        }

        // Get the authoritative alt text from the media library
        $alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

        if ( $alt_text === false ) {
            wp_send_json_error( array( 'message' => __( 'Alt text not found for this attachment.', 'altsync' ) ) );
            return;
        }

        $alt_text_sync_value = trim( $alt_text );

        // Prevent syncing completely empty alt text
        if ( $alt_text_sync_value === '' ) {
             wp_send_json_error( array( 'message' => __( 'Cannot sync an empty alt text. Please set alt text in the media library first.', 'altsync' ) ) );
             return;
        }

        $updated_posts_count = 0;
        $processed_posts = array(); // Keep track of posts already processed to avoid duplicates if found by multiple methods
        $posts_using_image = $this->find_posts_using_image( $attachment_id );

        if ( empty( $posts_using_image ) ) {
             wp_send_json_success( array( 'message' => __( 'No posts found using this image.', 'altsync' ), 'count' => 0 ) );
             return;
        }

        $attachment_url = wp_get_attachment_url( $attachment_id );
        $image_basename = $attachment_url ? wp_basename( $attachment_url ) : null;

        foreach ( $posts_using_image as $post_id ) {
            // Skip if already processed
            if ( in_array( $post_id, $processed_posts ) ) {
                continue;
            }

            $post = get_post( $post_id );
            // Ensure post exists and user can edit
            if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
                 $processed_posts[] = $post_id;
                continue;
            }

            $content = $post->post_content;
            $updated_content = $this->update_alt_text_in_content( $content, $attachment_id, $attachment_url, $image_basename, $alt_text_sync_value );

            if ( $updated_content !== null && $updated_content !== $content ) {
                // Update the post without triggering infinite loops (if any hooks modify content on save)
                $result = wp_update_post( array(
                    'ID'           => $post_id,
                    'post_content' => $updated_content,
                ), true ); // Pass true to return WP_Error on failure

                if ( ! is_wp_error( $result ) ) {
                    $updated_posts_count++;
                } else {
                    // Optionally log the error: error_log('AltSync failed to update post ' . $post_id . ': ' . $result->get_error_message());
                }
            }
            $processed_posts[] = $post_id;
        }

        if ( $updated_posts_count > 0 ) {
            $message = sprintf(
                /* translators: %d: number of posts updated. */
                __( 'Alt text synced to %d posts.', 'altsync' ),
                $updated_posts_count
            );
             wp_send_json_success( array( 'message' => $message, 'count' => $updated_posts_count ) );
        } else {
             wp_send_json_success( array( 'message' => __( 'No posts required updates.', 'altsync' ), 'count' => 0 ) );
        }
    }

    /**
     * Find posts that use a specific attachment ID.
     *
     * Searches post_content for image class `wp-image-{id}` and image URLs.
     *
     * @since 0.1.0
     * @access public
     * @param int $attachment_id The ID of the attachment.
     * @return array An array of unique post IDs.
     */
    public function find_posts_using_image( $attachment_id ) {
        global $wpdb;
        $found_ids = array();

        // 1. Look for the `wp-image-{id}` class (common in Gutenberg and classic editor inserts)
        $class_like = '%wp-image-' . $attachment_id . '%';
        $class_query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_status IN ('publish', 'future', 'draft', 'pending', 'private')
             AND post_type NOT IN ('revision', 'nav_menu_item', 'attachment', 'wp_block')
             AND post_content LIKE %s",
            $class_like
        );
        $results_class = $wpdb->get_col( $class_query );
        if ( ! empty( $results_class ) ) {
            $found_ids = array_merge( $found_ids, $results_class );
        }

        // 2. Look for the attachment URL (more complex due to thumbnails and variations)
        $attachment_url = wp_get_attachment_url( $attachment_id );
        if ( $attachment_url ) {
            // Get metadata to find thumbnail URLs as well
            $metadata = wp_get_attachment_metadata( $attachment_id );
            $possible_urls = array( $attachment_url );

            if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
                $upload_dir = wp_get_upload_dir();
                foreach ( $metadata['sizes'] as $size => $size_data ) {
                    if ( isset( $size_data['file'] ) ) {
                        $possible_urls[] = trailingslashit( $upload_dir['baseurl'] ) . dirname( get_post_meta( $attachment_id, '_wp_attached_file', true ) ) . '/' . $size_data['file'];
                    }
                }
            }

            // Create LIKE clauses for all possible URLs
            $sql_like_parts = array();
            foreach ( $possible_urls as $url ) {
                 // Match the path part primarily, less dependent on scheme/domain changes
                 $url_path = preg_replace('/^https?:\/\/[^\/]+/i', '', $url);
                 if ($url_path) {
                     $sql_like_parts[] = $wpdb->prepare( "post_content LIKE %s", '%' . $wpdb->esc_like( $url_path ) . '%' );
                 }
                 // Fallback to full url match if path extraction fails or is empty
                 else {
                      $sql_like_parts[] = $wpdb->prepare( "post_content LIKE %s", '%' . $wpdb->esc_like( $url ) . '%' );
                 }
            }

            if ( ! empty( $sql_like_parts ) ) {
                $url_query = (
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE post_status IN ('publish', 'future', 'draft', 'pending', 'private')
                     AND post_type NOT IN ('revision', 'nav_menu_item', 'attachment', 'wp_block')
                     AND (" . implode( ' OR ', $sql_like_parts ) . ")"
                );
                $results_url = $wpdb->get_col( $url_query );
                 if ( ! empty( $results_url ) ) {
                    $found_ids = array_merge( $found_ids, $results_url );
                }
            }
        }

        // Return unique IDs
        return array_unique( array_map( 'intval', $found_ids ) );
    }

    /**
     * Parses HTML content and updates empty alt text for a specific image.
     *
     * Uses DOMDocument for safer HTML parsing.
     *
     * @since 0.1.0
     * @access private
     * @param string $content The HTML content (post_content).
     * @param int    $attachment_id The target attachment ID.
     * @param string|null $attachment_url The full URL of the target attachment (can be null).
     * @param string|null $image_basename The basename of the image file (can be null).
     * @param string $new_alt_text The alt text to set.
     * @return string|null The modified content, or null if no changes were made or parsing failed.
     */
    private function update_alt_text_in_content( $content, $attachment_id, $attachment_url, $image_basename, $new_alt_text ) {
        // Basic checks
        if ( empty( trim( $content ) ) || ! class_exists( 'DOMDocument' ) ) {
            return null;
        }

        $modified = false;
        $dom = new DOMDocument();

        // Suppress warnings during HTML parsing (libxml)
        libxml_use_internal_errors(true);

        // Load HTML. Must wrap in a container and provide encoding hint for best results.
        // Using mb_convert_encoding to ensure UTF-8 compatibility with loadHTML.
        if ( ! $dom->loadHTML( mb_convert_encoding( '<div id="altsync-wrapper">' . $content . '</div>', 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            // Log error if needed: error_log('AltSync DOMDocument loadHTML failed for content: ' . substr($content, 0, 100));
            libxml_clear_errors();
            return null; // Bail if parsing fails
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        // Find all images within the wrapper
        $images = $xpath->query('//div[@id="altsync-wrapper"]//img');

        if ( $images === false || $images->length === 0) {
            return null; // No images found
        }

        foreach ( $images as $img ) {
            $src = $img->getAttribute('src');
            $class = $img->getAttribute('class');
            $alt = $img->getAttribute('alt');

            $is_target_image = false;

            // --- Image Matching Logic --- 
            // 1. Check for `wp-image-{id}` class.
            if ( preg_match('/\bwp-image-' . $attachment_id . '\b/', $class) ) {
                $is_target_image = true;
            }
            // 2. Check if the src attribute ends with the image basename (more reliable than full URL match sometimes).
            // Ensure $image_basename is not null or empty before checking.
            else if ( $image_basename && $src && substr( $src, -strlen( $image_basename ) ) === $image_basename ) {
                 $is_target_image = true;
            }
            // 3. Check if src matches the full attachment URL (less reliable due to https/http, www, etc.)
            else if ( $attachment_url && $src === $attachment_url ) {
                 $is_target_image = true;
            }
             // Consider adding checks for data-id attributes if needed for specific themes/plugins in the future.

            // --- Alt Text Update Logic --- 
            if ( $is_target_image ) {
                // Check if alt text is *missing* or *empty* (covers alt="" and alt=" ")
                if ( ! $img->hasAttribute('alt') || trim( $alt ) === '' ) {
                    $img->setAttribute( 'alt', $new_alt_text );
                    $modified = true;
                }
            }
        }

        // --- Extract modified HTML --- 
        if ( $modified ) {
            // Get the wrapper div
            $wrapper = $xpath->query('//div[@id="altsync-wrapper"]')->item(0);
            $inner_html = '';
            if ($wrapper && $wrapper->hasChildNodes()) {
                // Iterate through children of the wrapper and save their HTML
                foreach ($wrapper->childNodes as $child) {
                    // Use saveHTML passing the node to get its outerHTML
                    $inner_html .= $dom->saveHTML($child);
                }
            }
             // Check if the extraction resulted in non-empty content
             if (empty(trim($inner_html))) {
                 // If extraction failed somehow, return original content to be safe
                 // Log error: error_log('AltSync DOMDocument saveHTML failed to extract content.');
                 return $content; 
             }
            return $inner_html;
        }

        return null; // Indicate no changes were made
    }

    /**
     * Register the admin menu page for bulk synchronization.
     * 
     * @since 0.2.0
     */
    public function add_admin_menu() {
        add_submenu_page(
            'upload.php',
            __('Bulk Alt Text Sync', 'altsync'),
            __('Bulk Alt Sync', 'altsync'),
            'upload_files',
            'altsync-bulk',
            array($this, 'display_bulk_sync_page')
        );
    }

    /**
     * Display the bulk synchronization admin page.
     * 
     * @since 0.2.0
     */
    public function display_bulk_sync_page() {
        // Get image attachments
        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        );
        $images = get_posts($args);
        
        // Count images with alt text
        $images_with_alt = 0;
        foreach ($images as $image) {
            $alt_text = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
            if (!empty(trim($alt_text))) {
                $images_with_alt++;
            }
        }
        
        include ALTSYNC_PLUGIN_DIR . 'admin/partials/altsync-bulk-page.php';
    }

    /**
     * Process the AJAX request for bulk alt text preview (dry run).
     * 
     * @since 0.2.0
     */
    public function ajax_bulk_preview() {
        // Verify nonce
        if (!check_ajax_referer('altsync_bulk_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Nonce verification failed.', 'altsync')), 403);
            return;
        }

        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'altsync')), 403);
            return;
        }

        // Get sync mode
        $sync_mode = isset($_POST['sync_mode']) && $_POST['sync_mode'] === 'all' ? 'all' : 'empty';
        
        // Process image IDs (either all or selected)
        $image_ids = array();
        if (isset($_POST['all_images']) && $_POST['all_images'] === 'true') {
            // Get all image attachments
            $args = array(
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            );
            $image_ids = get_posts($args);
        } elseif (isset($_POST['image_ids']) && is_array($_POST['image_ids'])) {
            $image_ids = array_map('intval', $_POST['image_ids']);
        }

        if (empty($image_ids)) {
            wp_send_json_error(array('message' => __('No images selected.', 'altsync')), 400);
            return;
        }

        $preview_results = array();
        $total_posts_to_update = 0;

        foreach ($image_ids as $attachment_id) {
            // Get the alt text
            $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (empty(trim($alt_text))) {
                continue; // Skip images without alt text
            }

            // Find posts using this image
            $posts = $this->find_posts_using_image($attachment_id);
            if (empty($posts)) {
                continue; // Skip if no posts use this image
            }

            // Get affected posts count based on sync mode
            $affected_posts = ($sync_mode === 'all') 
                ? $this->count_posts_with_any_alt($attachment_id, $posts)
                : $this->count_posts_with_empty_alt($attachment_id, $posts);
                
            if ($affected_posts > 0) {
                $attachment = get_post($attachment_id);
                $preview_results[] = array(
                    'id' => $attachment_id,
                    'title' => $attachment->post_title,
                    'alt_text' => $alt_text,
                    'affected_posts' => $affected_posts,
                    'thumbnail' => wp_get_attachment_image_src($attachment_id, 'thumbnail')[0],
                    'sync_mode' => $sync_mode
                );
                $total_posts_to_update += $affected_posts;
            }
        }

        wp_send_json_success(array(
            'preview' => $preview_results,
            'total_posts' => $total_posts_to_update,
            'sync_mode' => $sync_mode
        ));
    }

    /**
     * Process the AJAX request for bulk alt text synchronization.
     * 
     * @since 0.2.0
     */
    public function ajax_bulk_sync() {
        // Verify nonce
        if (!check_ajax_referer('altsync_bulk_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Nonce verification failed.', 'altsync')), 403);
            return;
        }

        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'altsync')), 403);
            return;
        }

        // Get sync mode
        $sync_mode = isset($_POST['sync_mode']) && $_POST['sync_mode'] === 'all' ? 'all' : 'empty';
        
        // Get image IDs from the request
        $image_ids = array();
        if (isset($_POST['image_ids']) && is_array($_POST['image_ids'])) {
            $image_ids = array_map('intval', $_POST['image_ids']);
        }

        if (empty($image_ids)) {
            wp_send_json_error(array('message' => __('No images selected.', 'altsync')), 400);
            return;
        }

        $results = array();
        $total_updated = 0;

        foreach ($image_ids as $attachment_id) {
            // Get the alt text
            $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (empty(trim($alt_text))) {
                continue; // Skip images without alt text
            }

            // Find posts using this image
            $posts_using_image = $this->find_posts_using_image($attachment_id);
            if (empty($posts_using_image)) {
                continue; // Skip if no posts use this image
            }

            // Update posts based on sync mode
            $updated_count = ($sync_mode === 'all')
                ? $this->update_posts_with_any_alt($attachment_id, $posts_using_image, $alt_text)
                : $this->update_posts_with_empty_alt($attachment_id, $posts_using_image, $alt_text);
                
            if ($updated_count > 0) {
                $attachment = get_post($attachment_id);
                $results[] = array(
                    'id' => $attachment_id,
                    'title' => $attachment->post_title,
                    'updated_posts' => $updated_count
                );
                $total_updated += $updated_count;
            }
        }

        wp_send_json_success(array(
            'results' => $results,
            'total_updated' => $total_updated,
            'sync_mode' => $sync_mode
        ));
    }

    /**
     * Count posts that have empty alt text for a specific image.
     * 
     * @since 0.2.0
     * @param int $attachment_id The attachment ID.
     * @param array $post_ids Array of post IDs to check.
     * @return int Number of posts with empty alt text for this image.
     */
    private function count_posts_with_empty_alt($attachment_id, $post_ids) {
        $count = 0;
        $attachment_url = wp_get_attachment_url($attachment_id);
        $image_basename = $attachment_url ? wp_basename($attachment_url) : null;
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || !current_user_can('edit_post', $post_id)) {
                continue;
            }

            $content = $post->post_content;
            // Use a modified version of update_alt_text_in_content that only counts
            $would_update = $this->would_update_alt_text($content, $attachment_id, $attachment_url, $image_basename, $alt_text);
            if ($would_update) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if the content would be updated with new alt text.
     * 
     * @since 0.2.0
     * @param string $content The post content.
     * @param int $attachment_id The attachment ID.
     * @param string|null $attachment_url The attachment URL.
     * @param string|null $image_basename The image basename.
     * @param string $new_alt_text The new alt text.
     * @return bool Whether the content would be updated.
     */
    private function would_update_alt_text($content, $attachment_id, $attachment_url, $image_basename, $new_alt_text) {
        // Basic checks
        if (empty(trim($content)) || !class_exists('DOMDocument')) {
            return false;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML(mb_convert_encoding('<div id="altsync-wrapper">' . $content . '</div>', 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return false;
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $images = $xpath->query('//div[@id="altsync-wrapper"]//img');

        if ($images === false || $images->length === 0) {
            return false;
        }

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $class = $img->getAttribute('class');
            $alt = $img->getAttribute('alt');

            $is_target_image = false;

            // Image matching logic - same as update_alt_text_in_content
            if (preg_match('/\bwp-image-' . $attachment_id . '\b/', $class)) {
                $is_target_image = true;
            } elseif ($image_basename && $src && substr($src, -strlen($image_basename)) === $image_basename) {
                $is_target_image = true;
            } elseif ($attachment_url && $src === $attachment_url) {
                $is_target_image = true;
            }

            if ($is_target_image) {
                // Check if alt text is missing or empty
                if (!$img->hasAttribute('alt') || trim($alt) === '') {
                    return true; // We would update this image
                }
            }
        }

        return false;
    }

    /**
     * Update posts with empty alt text for the given attachment.
     *
     * @since 0.3.0
     * @access public
     * @param int $attachment_id The attachment ID.
     * @param array $post_ids Array of post IDs to check.
     * @param string $alt_text The new alt text to set.
     * @return int Number of posts updated.
     */
    public function update_posts_with_empty_alt($attachment_id, $post_ids, $alt_text) {
        $updated_count = 0;
        $attachment_url = wp_get_attachment_url($attachment_id);
        $image_basename = $attachment_url ? wp_basename($attachment_url) : null;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || !current_user_can('edit_post', $post_id)) {
                continue;
            }

            $content = $post->post_content;
            $updated_content = $this->update_alt_text_in_content($content, $attachment_id, $attachment_url, $image_basename, $alt_text);

            if ($updated_content !== null && $updated_content !== $content) {
                $result = wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $updated_content,
                ), true);

                if (!is_wp_error($result)) {
                    $updated_count++;
                }
            }
        }

        return $updated_count;
    }

    /**
     * Count posts that have any alt text for a specific image to preview "replace all" mode.
     * 
     * @since 0.3.0
     * @param int $attachment_id The attachment ID.
     * @param array $post_ids Array of post IDs to check.
     * @return int Number of posts with the specified image.
     */
    private function count_posts_with_any_alt($attachment_id, $post_ids) {
        $count = 0;
        $attachment_url = wp_get_attachment_url($attachment_id);
        $image_basename = $attachment_url ? wp_basename($attachment_url) : null;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || !current_user_can('edit_post', $post_id)) {
                continue;
            }

            $content = $post->post_content;
            // Check if the image exists in this post
            $would_update = $this->would_update_any_alt_text($content, $attachment_id, $attachment_url, $image_basename);
            if ($would_update) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if the content has any instances of this image (for "replace all" mode).
     * 
     * @since 0.3.0
     * @param string $content The post content.
     * @param int $attachment_id The attachment ID.
     * @param string|null $attachment_url The attachment URL.
     * @param string|null $image_basename The image basename.
     * @return bool Whether the content contains the image.
     */
    private function would_update_any_alt_text($content, $attachment_id, $attachment_url, $image_basename) {
        // Basic checks
        if (empty(trim($content)) || !class_exists('DOMDocument')) {
            return false;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML(mb_convert_encoding('<div id="altsync-wrapper">' . $content . '</div>', 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return false;
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $images = $xpath->query('//div[@id="altsync-wrapper"]//img');

        if ($images === false || $images->length === 0) {
            return false;
        }

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $class = $img->getAttribute('class');

            $is_target_image = false;

            // Image matching logic - same as update_alt_text_in_content
            if (preg_match('/\bwp-image-' . $attachment_id . '\b/', $class)) {
                $is_target_image = true;
            } elseif ($image_basename && $src && substr($src, -strlen($image_basename)) === $image_basename) {
                $is_target_image = true;
            } elseif ($attachment_url && $src === $attachment_url) {
                $is_target_image = true;
            }

            if ($is_target_image) {
                return true; // We found an instance of this image
            }
        }

        return false;
    }

    /**
     * Update posts with any alt text (empty or not) for a specific image.
     * 
     * @since 0.3.0
     * @param int $attachment_id The attachment ID.
     * @param array $post_ids Array of post IDs to update.
     * @param string $alt_text The new alt text to set.
     * @return int Number of posts updated.
     */
    public function update_posts_with_any_alt($attachment_id, $post_ids, $alt_text) {
        $updated_count = 0;
        $attachment_url = wp_get_attachment_url($attachment_id);
        $image_basename = $attachment_url ? wp_basename($attachment_url) : null;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || !current_user_can('edit_post', $post_id)) {
                continue;
            }

            $content = $post->post_content;
            $updated_content = $this->update_all_alt_text_in_content($content, $attachment_id, $attachment_url, $image_basename, $alt_text);

            if ($updated_content !== null && $updated_content !== $content) {
                $result = wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $updated_content,
                ), true);

                if (!is_wp_error($result)) {
                    $updated_count++;
                }
            }
        }

        return $updated_count;
    }

    /**
     * Parses HTML content and updates ALL alt text for a specific image.
     * Similar to update_alt_text_in_content but replaces all alt text, not just empty ones.
     *
     * @since 0.3.0
     * @access private
     * @param string $content The HTML content (post_content).
     * @param int    $attachment_id The target attachment ID.
     * @param string|null $attachment_url The full URL of the target attachment (can be null).
     * @param string|null $image_basename The basename of the image file (can be null).
     * @param string $new_alt_text The alt text to set.
     * @return string|null The modified content, or null if no changes were made or parsing failed.
     */
    private function update_all_alt_text_in_content($content, $attachment_id, $attachment_url, $image_basename, $new_alt_text) {
        // Basic checks
        if (empty(trim($content)) || !class_exists('DOMDocument')) {
            return null;
        }

        $modified = false;
        $dom = new DOMDocument();

        // Suppress warnings during HTML parsing (libxml)
        libxml_use_internal_errors(true);

        // Load HTML. Must wrap in a container and provide encoding hint for best results.
        // Using mb_convert_encoding to ensure UTF-8 compatibility with loadHTML.
        if (!$dom->loadHTML(mb_convert_encoding('<div id="altsync-wrapper">' . $content . '</div>', 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return null; // Bail if parsing fails
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        // Find all images within the wrapper
        $images = $xpath->query('//div[@id="altsync-wrapper"]//img');

        if ($images === false || $images->length === 0) {
            return null; // No images found
        }

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $class = $img->getAttribute('class');
            $alt = $img->getAttribute('alt');

            $is_target_image = false;

            // --- Image Matching Logic --- 
            // 1. Check for `wp-image-{id}` class.
            if (preg_match('/\bwp-image-' . $attachment_id . '\b/', $class)) {
                $is_target_image = true;
            }
            // 2. Check if the src attribute ends with the image basename (more reliable than full URL match sometimes).
            // Ensure $image_basename is not null or empty before checking.
            else if ($image_basename && $src && substr($src, -strlen($image_basename)) === $image_basename) {
                 $is_target_image = true;
            }
            // 3. Check if src matches the full attachment URL (less reliable due to https/http, www, etc.)
            else if ($attachment_url && $src === $attachment_url) {
                 $is_target_image = true;
            }

            // --- Alt Text Update Logic --- 
            if ($is_target_image) {
                // Replace ALL alt text regardless of current value
                $img->setAttribute('alt', $new_alt_text);
                $modified = true;
            }
        }

        // --- Extract modified HTML --- 
        if ($modified) {
            // Get the wrapper div
            $wrapper = $xpath->query('//div[@id="altsync-wrapper"]')->item(0);
            $inner_html = '';
            if ($wrapper && $wrapper->hasChildNodes()) {
                // Iterate through children of the wrapper and save their HTML
                foreach ($wrapper->childNodes as $child) {
                    // Use saveHTML passing the node to get its outerHTML
                    $inner_html .= $dom->saveHTML($child);
                }
            }
             // Check if the extraction resulted in non-empty content
             if (empty(trim($inner_html))) {
                 // If extraction failed somehow, return original content to be safe
                 return $content; 
             }
            return $inner_html;
        }

        return null; // Indicate no changes were made
    }

} 