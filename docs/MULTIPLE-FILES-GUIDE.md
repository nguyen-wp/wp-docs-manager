# Multiple Files Support Guide

## Overview

The LIFT Docs System now supports uploading and managing multiple files per document. Each file can have its own secure download link with individual access control.

## Features

### 1. Multiple File Upload
- Add unlimited number of files to a single document
- Each file has its own URL input field
- Dynamic add/remove interface with smooth animations
- Support for WordPress Media Library integration

### 2. Individual Secure Links
- Each file generates its own unique secure download link
- Individual access control per file
- Separate encryption tokens for enhanced security
- File index tracking for proper download routing

### 3. Responsive Interface
- Mobile-friendly file management interface
- Touch-optimized buttons for mobile devices
- Collapsible file sections for better organization
- Real-time file counter display

## How to Use

### Adding Multiple Files

1. **Edit or Create a Document**
   - Go to `Documents` → `Add New` or edit existing document
   - Scroll to "Document Details & Secure Links" metabox

2. **Add Files**
   - Enter first file URL in the initial input field
   - Click `+ Add Another File` button to add more files
   - Use `Upload` button to select files from Media Library
   - Use `Remove` button to delete unwanted file entries

3. **Save Document**
   - Click `Update` or `Publish` to save all file URLs
   - Secure links will be automatically generated for each file

### Managing Download Links

1. **View Generated Links**
   - After saving, secure download links appear below file inputs
   - Each file has its own section with unique download URL
   - Links are read-only and can be copied using the copy button

2. **Copy Links**
   - Use the `📋 Copy Link` button to copy individual download URLs
   - Links can be shared directly or embedded in other content
   - Each link includes the file index for proper routing

### Frontend Display

1. **Single File (Legacy)**
   - Documents with single file continue to work as before
   - Automatic fallback to single file display

2. **Multiple Files**
   - Files are displayed as numbered list
   - Each file has its own download button
   - File names extracted from URLs when possible

## Technical Details

### Database Storage

```php
// Single file (backward compatibility)
update_post_meta($post_id, '_lift_doc_file_url', $single_url);

// Multiple files (new format)
update_post_meta($post_id, '_lift_doc_file_urls', array(
    'https://example.com/file1.pdf',
    'https://example.com/file2.docx',
    'https://example.com/file3.xlsx'
));
```

### Secure Link Generation

```php
// Generate link for specific file
$settings = new Lift_Docs_Settings();
$secure_url = $settings->generate_secure_download_link($post_id, $file_index);

// Example output
// https://yoursite.com/lift-docs/download/?token=abc123&file=1
```

### URL Structure

```
Base URL: /lift-docs/download/
Parameters:
- token: Encrypted access token
- file: File index (0, 1, 2, etc.)
- expires: Expiration timestamp (optional)
```

## Migration from Single Files

### Automatic Migration

The system automatically handles documents with single files:

1. **Reading Files**
   - Checks for `_lift_doc_file_urls` (multiple files)
   - Falls back to `_lift_doc_file_url` (single file)
   - Converts single file to array format when needed

2. **Saving Files**
   - Always saves as `_lift_doc_file_urls` array
   - Maintains `_lift_doc_file_url` for backward compatibility
   - No data loss during migration

### Manual Migration

To manually migrate existing documents:

```php
// Get all documents with single file only
$posts = get_posts(array(
    'post_type' => 'lift_document',
    'meta_query' => array(
        array(
            'key' => '_lift_doc_file_url',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => '_lift_doc_file_urls',
            'compare' => 'NOT EXISTS'
        )
    )
));

// Convert to multiple files format
foreach ($posts as $post) {
    $single_url = get_post_meta($post->ID, '_lift_doc_file_url', true);
    if ($single_url) {
        update_post_meta($post->ID, '_lift_doc_file_urls', array($single_url));
    }
}
```

## Security Features

### Individual File Protection

- Each file has its own encrypted token
- File index prevents unauthorized access to other files
- Download permissions checked per file request

### Access Control

```php
// Check access for specific file
$can_access = apply_filters('lift_docs_can_access_file', true, $post_id, $file_index, $current_user_id);

// Log download attempts
do_action('lift_docs_file_downloaded', $post_id, $file_index, $current_user_id, $download_time);
```

### Token Security

- Unique tokens per file and user session
- Expiration time support
- Rate limiting capabilities
- Download attempt logging

## CSS Customization

### Custom Styling

```css
/* Customize file input rows */
.file-input-row {
    background: your-color;
    border: your-border;
}

/* Customize download links */
.download-link-item {
    background: your-background;
    border-radius: your-radius;
}

/* Customize add button */
#add_file_button {
    background: your-button-color;
    color: your-text-color;
}
```

### Responsive Breakpoints

- Desktop: Full horizontal layout
- Tablet (768px): Vertical stacking with full-width buttons
- Mobile (480px): Compact layout with touch-friendly controls

## JavaScript Hooks

### Custom Events

```javascript
// Listen for file added
document.addEventListener('lift_docs_file_added', function(event) {
    console.log('File added:', event.detail.fileIndex);
});

// Listen for file removed
document.addEventListener('lift_docs_file_removed', function(event) {
    console.log('File removed:', event.detail.fileIndex);
});
```

### Custom Functions

```javascript
// Add file programmatically
LiftDocs.addFileInput('https://example.com/file.pdf');

// Remove file by index
LiftDocs.removeFileInput(2);

// Get all file URLs
var urls = LiftDocs.getAllFileUrls();
```

## Testing

### Test Multiple Files

1. **Enable Test Mode**
   ```php
   // Add to wp-config.php
   define('LIFT_DOCS_TEST_MODE', true);
   ```

2. **Create Test Document**
   - Use the test button in admin bar (🧪 Test Multi Files)
   - Creates document with sample multiple files
   - Generates secure links for testing

3. **Verify Functionality**
   - Check file input interface
   - Test add/remove buttons
   - Verify secure link generation
   - Test download functionality

### Debug Mode

```php
// Enable debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check logs for multiple files activity
tail -f /wp-content/debug.log | grep "LIFT Docs Multi-File"
```

## Troubleshooting

### Common Issues

1. **Files Not Saving**
   - Check JavaScript console for errors
   - Verify metabox permissions
   - Ensure proper nonce validation

2. **Secure Links Not Generated**
   - Check encryption settings
   - Verify post save hooks
   - Ensure settings class is loaded

3. **Download Issues**
   - Check file URLs are accessible
   - Verify token generation
   - Check download handler routing

### Error Messages

```php
// File access denied
"Access denied for file index {$file_index} in document {$post_id}"

// Invalid token
"Invalid or expired download token for document {$post_id}"

// File not found
"File {$file_index} not found in document {$post_id}"
```

## Support

For technical support or feature requests related to multiple files functionality:

1. Check the debug logs for detailed error information
2. Verify WordPress and plugin versions compatibility
3. Test with default theme to rule out theme conflicts
4. Review server error logs for PHP errors

## Changelog

### Version 1.5.0
- ✅ Added multiple files support
- ✅ Individual secure links per file
- ✅ Responsive file management interface
- ✅ Backward compatibility with single files
- ✅ Enhanced security with file index tokens
- ✅ Mobile-optimized UI
- ✅ Test functionality and debugging tools
