# AltSync

![Version](https://img.shields.io/badge/version-0.4.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.2+-green)
![PHP](https://img.shields.io/badge/PHP-7.2+-purple)

## The original problem with Wordpress image alt texts…

1. Ain't nobody got time for that.
2. It does not exactly work in a very user friendly way, because when you do have time for it, you either:
   1. Update the alt text on image placing, overriding the default preventing alt text reuse.
   2. Update it in Media Library and it does not get propagated through half the places.

### Here comes AltSync, [brother of AltGen](https://github.com/thebys/altgen)

AltSync is a WordPress plugin that synchronizes (freshly updated) media library alt text into posts, replacing empty or stale alt attributes. It works in tandem with the [AltGen](https://github.com/thebys/altgen) browser extension to create a powerful accessibility workflow.

## 🔄 The Alt Text Workflow

1. **[AltGen](https://github.com/thebys/altgen)**
   - AI-assisted browser extension that helps generate high-quality alt text for images
   - It can update alt text of user selected image in Wordpress Media Library
   - It can call AltSync API to propagate alt text changes from Media Library to posts.
2. **AltSync**: This WordPress plugin - it ensures the fresh updated alt text in Media Library are applied everywhere the image is used

This combination creates a seamless workflow for maintaining accessibility standards across your WordPress site.

## ✨ Features

- **Bulk Synchronization**: Update alt text across multiple images at once
- **Smart Sync Modes**: 
  - "Empty" mode (safer): only updates images with empty alt text
  - "All" mode: replaces all instances of alt text for selected images
- **Preview Before Committing**: Dry run feature to see potential changes before applying
- **API Integration**: REST endpoints for external applications like [AltGen](https://github.com/thebys/altgen)
- **Status Check**: Public endpoint to verify plugin availability

## 📦 Installation

1. Upload the `AltSync` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Media → Bulk Alt Sync to update multiple images at once

## 🚀 Usage

### Manual / bulk sync options
Navigate to Media → Bulk Alt Sync in the WordPress admin where you can:
- Select all images to sync (not thoroughly tested, dangerous and slow, make sure to have DB backup!)
- Select multiple images to sync (great for semi-automated HITL workflows)
- Choose between updating only empty alt text or all instances (honor your overrides.. or don't!)
- Preview changes before committing (not very detailed)

### Automated Sync with [AltGen](https://github.com/thebys/altgen)
For the best experience, use AltSync together with the [AltGen](https://github.com/thebys/altgen) browser extension:

1. Install the [AltGen](https://github.com/thebys/altgen) browser extension (available for ~~Chrome~~/Firefox)
2. Configure [AltGen](https://github.com/thebys/altgen) to connect to your WordPress site
3. Use [AltGen](https://github.com/thebys/altgen) to generate AI-assisted alt text for your images
4. [AltGen](https://github.com/thebys/altgen) will automatically call AltSync's API to propagate the alt text changes

## 🔌 API Documentation

AltSync provides two REST API endpoints  (used by guess what - ... AltGen):

### Status Endpoint
```
GET /wp-json/AltSync/v1/status
```
- Publicly accessible
- Checks if the plugin is active and ready to use
- No authentication required

### Sync Endpoint
```
POST /wp-json/AltSync/v1/sync-image
```
- Requires WordPress application password authentication
- Parameters:
  - `attachment_id`: The ID of the image to update
  - `sync_mode`: Either 'empty' (safer) or 'all' (replace all alt texts of this image site-wide)

For detailed API documentation, see [docs/api-usage-example.md](docs/api-usage-example.md).

## ❓ FAQ

### Does this overwrite custom alt text I set in the editor?

By default, no. AltSync operates in "safer" mode that only updates images where the alt text is currently empty (`alt=""`).

However, there is also an "update all" mode that will replace ALL alt text for the selected images with the version from the media library, regardless of what was previously set in posts.

### Will this work with the Block Editor (Gutenberg)?

Yes, it works by modifying the saved post content, so it's compatible with both the Classic Editor and the Block Editor.

### Do I need this if I include ALL images directly via Elementor?

Actually maybe not, it seems Elementor handles updating / propagating ALT text rather good without even needing to resave anything a simple reload / cache bust seems to do it in my env. However you may still greatly benefit from HITL [AltGen](https://github.com/thebys/altgen) browser extension to improve accessibility and SEO workflow.
### Should I backup my database before using this plugin?

Yes, especially when using the "Update ALL alt text" mode, which can make widespread changes to your content. Always backup your database before performing bulk operations.

## 📝 Changelog

### 0.4.1
- Added status check endpoint at `/wp-json/AltSync/v1/status` for external applications
- Updated API documentation with comprehensive examples

### 0.4.0
- Added REST API endpoint for external applications like [AltGen](https://github.com/thebys/altgen)
- API supports both empty alt text and full replacement sync modes
- Authentication via WordPress application passwords

### 0.3.0
- Added new sync mode to replace ALL alt text (not just empty ones)
- Added stronger warnings and confirmations for destructive operations
- Removed individual image sync button to focus on bulk synchronization

### 0.2.0
- Added bulk synchronization feature
- Added dry run preview option
- Improved image detection in posts

### 0.1.0
- Initial plugin structure

## 👨‍💻 Author

Tomáš "Thebys" Biheler 