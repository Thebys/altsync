=== AltSync ===
Contributors: Tomáš "Thebys" Biheler
Donate link: https://biheler.eu
Tags: images, alt text, accessibility, media, sync, gutenberg, editor
Requires at least: 5.2
Tested up to: 6.8
Stable tag: 0.4.1
Requires PHP: 7.2
License: No License

Synchronizes updated media library alt text into posts, replacing empty or stale alt attributes.

== Description ==

When you update the Alt Text for an image in the WordPress Media Library, this change isn't automatically reflected in posts where the image is already used. AltSync solves this by providing a way to push the updated alt text from the Media Library to existing posts.

This helps maintain consistency and improves accessibility across your site while giving you control over what gets updated.

Intended to be used with altgen browser extension which uses AI assisted human controlled process to update alt text in the media library.

Features:

*   Bulk synchronization tool to update multiple images at once.
*   Two sync modes: update only empty alt text (safer) or update all alt text (replaces existing).
*   Dry run preview option to see changes before applying them.
*   Finds all posts/pages using specific images.
*   Strong warnings and confirmations to prevent accidental changes.
*   REST API endpoints for external applications to interact with the plugin.
*   Status check endpoint to verify plugin is active and ready to use.

== Installation ==

1.  Upload the `altsync` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to Media → Bulk Alt Sync to update multiple images at once.

== Frequently Asked Questions ==

= Does this overwrite custom alt text I set in the editor? =

By default, no. AltSync operates in "safer" mode that only updates images where the alt text is currently empty (`alt=""`).

However, there is also an "update all" mode that will replace ALL alt text for the selected images with the version from the media library, regardless of what was previously set in posts.

= Will this work with the Block Editor (Gutenberg)? =

Yes, it works by modifying the saved `post_content`, so it's compatible with both the Classic Editor and the Block Editor.

= What does the "Preview Changes (Dry Run)" option do? =

This option shows you which posts would be affected by the synchronization without actually making any changes. It allows you to review the potential impact before committing to the updates.

= Should I backup my database before using this plugin? =

Yes, especially when using the "Update ALL alt text" mode, which can make widespread changes to your content. Always backup your database before performing bulk operations.

= How can I use the API endpoints with external applications? =

The plugin provides REST API endpoints for integration with external applications:

1. **Sync endpoint**: `/wp-json/altsync/v1/sync-image` (POST) that can be called to synchronize alt text for a specific image. Requires authentication using WordPress application passwords.

2. **Status endpoint**: `/wp-json/altsync/v1/status` (GET) that allows checking if the plugin is active and ready to use. This endpoint is publicly accessible and doesn't require authentication.

Refer to the API documentation for more details.

== Screenshots ==

1. The bulk synchronization page with dry run preview.

== Changelog ==

= 0.4.1 =
*   Added status check endpoint at `/wp-json/altsync/v1/status` to allow external applications to verify plugin availability.
*   Updated API documentation with comprehensive examples and best practices.

= 0.4.0 =
*   Added REST API endpoint for external applications to trigger alt text synchronization.
*   API supports both empty alt text and full replacement sync modes.
*   Authentication required via WordPress application passwords.
*   Updated plugin architecture to accommodate API functionality.

= 0.3.0 =
*   Added new sync mode to replace ALL alt text (not just empty ones).
*   Added stronger warnings and confirmations for destructive operations.
*   Removed individual image sync button to focus on bulk synchronization.
*   Updated license information.
*   Tested compatibility with WordPress 6.8.

= 0.2.0 =
*   Added bulk synchronization feature.
*   Added dry run preview option to see changes before applying them.
*   Improved image detection in posts.

= 0.1.0 =
*   Initial plugin structure.

== Upgrade Notice ==

= 0.4.1 =
*   Added status check endpoint to allow external applications to verify plugin availability before making API calls.

= 0.4.0 =
*   Added REST API endpoint for external applications like altgen browser extension to trigger alt text synchronization.

= 0.3.0 =
*   Added new sync mode to replace ALL alt text (not just empty ones). Use with caution and backup your database first!

= 0.2.0 =
*   Added bulk synchronization with dry run preview option for reviewing changes before applying them.

= 0.1.0 =
*   Initial release. 