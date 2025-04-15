<?php
/**
 * Admin page for bulk alt text synchronization
 *
 * @link       https://your-website.com/
 * @since      0.2.0
 *
 * @package    AltSync
 * @subpackage AltSync/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="altsync-bulk-container">
        <div class="altsync-intro">
            <p><?php _e('This tool allows you to synchronize alt text from the Media Library to posts where the images are used with empty alt text.', 'altsync'); ?></p>
            <p><strong><?php _e('Note:', 'altsync'); ?></strong> <?php _e('This will only update images where the alt text is currently empty in posts.', 'altsync'); ?></p>
        </div>
        
        <div class="altsync-warning notice notice-error">
            <p><strong><?php _e('WARNING:', 'altsync'); ?></strong> <?php _e('This plugin makes direct changes to your post content. It is strongly recommended to backup your database before proceeding with any bulk operations.', 'altsync'); ?></p>
        </div>
        
        <div class="altsync-stats">
            <p>
                <?php printf(
                    __('Found %1$d images in your media library, %2$d with alt text.', 'altsync'),
                    count($images),
                    $images_with_alt
                ); ?>
            </p>
        </div>
        
        <?php if (count($images) > 0): ?>
            <div class="altsync-actions">
                <div class="altsync-selection">
                    <label>
                        <input type="radio" name="altsync-selection-type" value="all" checked>
                        <?php _e('Process all images with alt text', 'altsync'); ?>
                    </label>
                    <label>
                        <input type="radio" name="altsync-selection-type" value="selected">
                        <?php _e('Process selected images only', 'altsync'); ?>
                    </label>
                </div>
                
                <div class="altsync-sync-mode">
                    <h3><?php _e('Synchronization Mode', 'altsync'); ?></h3>
                    <label>
                        <input type="radio" name="altsync-sync-mode" value="empty" checked>
                        <?php _e('Update empty alt text only (safer)', 'altsync'); ?>
                        <p class="description"><?php _e('Only replaces alt text that is currently empty in posts.', 'altsync'); ?></p>
                    </label>
                    <label>
                        <input type="radio" name="altsync-sync-mode" value="all">
                        <?php _e('Update ALL alt text (destructive)', 'altsync'); ?>
                        <p class="description"><?php _e('Replaces ALL instances of alt text for the image, even if already set in posts.', 'altsync'); ?></p>
                    </label>
                </div>
                
                <div class="altsync-buttons">
                    <button type="button" id="altsync-preview" class="button button-primary">
                        <?php _e('Preview Changes (Dry Run)', 'altsync'); ?>
                    </button>
                    <button type="button" id="altsync-sync" class="button" disabled>
                        <?php _e('Synchronize Alt Text', 'altsync'); ?>
                    </button>
                    <span class="spinner" style="float: none; visibility: hidden;"></span>
                </div>
            </div>
            
            <div id="altsync-preview-results" class="altsync-results" style="display: none;">
                <h2><?php _e('Preview Results', 'altsync'); ?></h2>
                <div class="altsync-summary">
                    <p id="altsync-preview-summary"></p>
                </div>
                <div id="altsync-preview-list" class="altsync-list"></div>
            </div>
            
            <div id="altsync-sync-results" class="altsync-results" style="display: none;">
                <h2><?php _e('Synchronization Results', 'altsync'); ?></h2>
                <div class="altsync-summary">
                    <p id="altsync-sync-summary"></p>
                </div>
                <div id="altsync-sync-list" class="altsync-list"></div>
            </div>
            
            <div class="altsync-image-selection" style="display: none;">
                <h3><?php _e('Select Images to Process', 'altsync'); ?></h3>
                <p>
                    <button type="button" id="altsync-select-all" class="button"><?php _e('Select All', 'altsync'); ?></button>
                    <button type="button" id="altsync-select-none" class="button"><?php _e('Select None', 'altsync'); ?></button>
                </p>
                <div class="altsync-image-grid">
                    <?php foreach ($images as $image): 
                        $alt_text = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                        $has_alt = !empty(trim($alt_text));
                        $thumbnail = wp_get_attachment_image_src($image->ID, 'thumbnail');
                        $thumbnail_url = $thumbnail ? $thumbnail[0] : '';
                    ?>
                        <div class="altsync-image-item<?php echo $has_alt ? ' has-alt' : ' no-alt'; ?>">
                            <label>
                                <input type="checkbox" name="altsync-image[]" value="<?php echo esc_attr($image->ID); ?>" <?php echo $has_alt ? '' : 'disabled'; ?>>
                                <div class="altsync-image-preview">
                                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($image->post_title); ?>">
                                </div>
                                <div class="altsync-image-info">
                                    <div class="altsync-image-title"><?php echo esc_html($image->post_title); ?></div>
                                    <?php if ($has_alt): ?>
                                        <div class="altsync-image-alt"><?php echo esc_html($alt_text); ?></div>
                                    <?php else: ?>
                                        <div class="altsync-image-alt altsync-no-alt"><?php _e('No alt text', 'altsync'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="notice notice-warning">
                <p><?php _e('No images found in the media library.', 'altsync'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div> 