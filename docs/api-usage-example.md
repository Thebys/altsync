# AltSync API Usage Guide

This document explains how to use the AltSync API with external applications, specifically the altgen browser extension.

## API Endpoints

The plugin provides REST API endpoints that allow external applications to interact with AltSync.

### Sync Image Endpoint

- **Endpoint URL**: `/wp-json/altsync/v1/sync-image`
- **Method**: POST
- **Authentication**: WordPress application passwords
- **Content-Type**: application/json

### Status Check Endpoint

- **Endpoint URL**: `/wp-json/altsync/v1/status`
- **Method**: GET
- **Authentication**: None (publicly accessible)
- **Content-Type**: application/json

## Parameters

### Sync Image Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attachment_id` | Integer | Yes | The ID of the image in the WordPress Media Library |
| `sync_mode` | String | Yes | Sync mode: 'empty' (update only empty alt text) or 'all' (update all instances) |

### Status Check Parameters

No parameters required.

## Authentication

The sync image API requires authentication using WordPress application passwords:

1. In WordPress admin, go to Users → Profile
2. Scroll down to "Application Passwords" section
3. Enter a name (e.g., "AltGen Extension") and click "Add New Application Password"
4. Save the generated password (it will only be shown once)

The status endpoint does not require authentication.

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

# Checking plugin status
curl -X GET \
  https://your-wordpress-site.com/wp-json/altsync/v1/status
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

// Function to check if AltSync plugin is active and ready
async function checkAltSyncStatus() {
  const statusUrl = 'https://your-wordpress-site.com/wp-json/altsync/v1/status';
  
  try {
    const response = await fetch(statusUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (response.ok && data.success) {
      console.log('AltSync plugin is active:', data.message);
      return data;
    } else {
      console.error('AltSync plugin is not available');
      throw new Error('AltSync plugin is not available');
    }
  } catch (error) {
    console.error('Error checking AltSync status:', error);
    throw error;
  }
}

// Example usage in altgen extension
// Check if plugin is active before attempting to sync
checkAltSyncStatus()
  .then(statusData => {
    // If active, proceed with sync
    return syncAltText(123, 'empty');
  })
  .then(syncData => {
    // Show success notification
    alert(`Alt text synced! ${syncData.message}`);
  })
  .catch(error => {
    // Show error notification
    alert(`Operation failed: ${error.message}`);
  });
```

## Response Format

### Sync Image Response (200 OK)

```json
{
  "success": true,
  "message": "Alt text synced to 5 posts.",
  "updated_count": 5
}
```

### Status Check Response (200 OK)

```json
{
  "success": true,
  "status": "active",
  "version": "0.4.1",
  "message": "AltSync plugin is active and ready to use."
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

1. First check if AltSync plugin is active using the status endpoint
2. After updating alt text with AI in the media library, call the sync image API
3. Store WordPress credentials securely in the extension settings
4. Offer options for sync mode (empty or all)
5. Provide clear feedback on the sync result

## Error Handling

Common error scenarios:

- **Authentication failure**: Check application password and username
- **Empty alt text**: Ensure alt text is set in the Media Library before syncing
- **Attachment not found**: Verify the attachment ID exists
- **Non-image attachment**: Ensure the attachment is an image
- **Plugin not active**: Check if the status endpoint returns successfully

## Best Practices

- Always check plugin status before attempting sync operations
- Only sync after confirming the alt text is high quality
- Start with 'empty' mode for safer updates
- Use 'all' mode with caution as it replaces existing alt text
- Consider implementing a confirmation dialog before sync
- Display clear success/error messages to users 