# AltSync API Usage Guide

This document explains how to use the AltSync API with external applications, specifically the altgen browser extension.

## API Endpoint

The plugin provides a REST API endpoint that allows external applications to trigger alt text synchronization for specific images.

- **Endpoint URL**: `/wp-json/altsync/v1/sync-image`
- **Method**: POST
- **Authentication**: WordPress application passwords
- **Content-Type**: application/json

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attachment_id` | Integer | Yes | The ID of the image in the WordPress Media Library |
| `sync_mode` | String | Yes | Sync mode: 'empty' (update only empty alt text) or 'all' (update all instances) |

## Authentication

The API requires authentication using WordPress application passwords:

1. In WordPress admin, go to Users → Profile
2. Scroll down to "Application Passwords" section
3. Enter a name (e.g., "AltGen Extension") and click "Add New Application Password"
4. Save the generated password (it will only be shown once)

## Example Requests

### Using cURL

```bash
# Updating only empty alt text
curl -X POST \
  https://your-wordpress-site.com/wp-json/altsync/v1/sync-image \
  -u "username:application_password" \
  -H "Content-Type: application/json" \
  -d '{"attachment_id": 123, "sync_mode": "empty"}'

# Updating all instances regardless of current alt text
curl -X POST \
  https://your-wordpress-site.com/wp-json/altsync/v1/sync-image \
  -u "username:application_password" \
  -H "Content-Type: application/json" \
  -d '{"attachment_id": 123, "sync_mode": "all"}'
```

### Using JavaScript (for browser extensions)

```javascript
// Function to sync alt text after updating it in the Media Library
async function syncAltText(attachmentId, mode = 'empty') {
  const wpApiUrl = 'https://your-wordpress-site.com/wp-json/altsync/v1/sync-image';
  const username = 'your_username';
  const appPassword = 'your_app_password';
  
  // Basic authentication header
  const auth = btoa(`${username}:${appPassword}`);
  
  try {
    const response = await fetch(wpApiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Basic ${auth}`
      },
      body: JSON.stringify({
        attachment_id: attachmentId,
        sync_mode: mode
      })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      console.log('Alt text synced successfully:', data.message);
      return data;
    } else {
      console.error('Failed to sync alt text:', data.message);
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Error syncing alt text:', error);
    throw error;
  }
}

// Example usage in altgen extension
// After updating alt text in Media Library:
syncAltText(123, 'empty')
  .then(data => {
    // Show success notification
    alert(`Alt text synced! ${data.message}`);
  })
  .catch(error => {
    // Show error notification
    alert(`Sync failed: ${error.message}`);
  });
```

## Response Format

### Successful Response (200 OK)

```json
{
  "success": true,
  "message": "Alt text synced to 5 posts.",
  "updated_count": 5
}
```

### Error Response (400 Bad Request)

```json
{
  "success": false,
  "message": "Cannot sync empty alt text. Please set alt text in the media library first."
}
```

## Integration with altgen Browser Extension

To integrate with the altgen browser extension:

1. After updating alt text with AI in the media library, call the AltSync API
2. Store WordPress credentials securely in the extension settings
3. Offer options for sync mode (empty or all)
4. Provide clear feedback on the sync result

## Error Handling

Common error scenarios:

- **Authentication failure**: Check application password and username
- **Empty alt text**: Ensure alt text is set in the Media Library before syncing
- **Attachment not found**: Verify the attachment ID exists
- **Non-image attachment**: Ensure the attachment is an image

## Best Practices

- Only sync after confirming the alt text is high quality
- Start with 'empty' mode for safer updates
- Use 'all' mode with caution as it replaces existing alt text
- Consider implementing a confirmation dialog before sync
- Display clear success/error messages to users 