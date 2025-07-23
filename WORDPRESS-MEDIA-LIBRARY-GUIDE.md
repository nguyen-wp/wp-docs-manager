# WordPress Media Library Integration Guide

## Overview
LIFT Docs System now integrates with WordPress Media Library for seamless file uploads. Users can now upload documents directly through the familiar WordPress media uploader interface.

## Features

### 🎯 WordPress Media Library Integration
- **Native WordPress Uploader**: Uses the standard WordPress media uploader interface
- **File Type Filtering**: Automatically filters to show document types (PDF, DOC, XLS, etc.)
- **Drag & Drop Support**: Inherits WordPress drag-and-drop upload functionality
- **Media Library Management**: Files are stored in WordPress media library for easy management

### 📁 Enhanced File Management
- **Visual File Icons**: Files display with appropriate document icons (📄)
- **File Size Display**: Shows file size and format information
- **Real-time Updates**: File information updates immediately after upload
- **Upload Status**: Visual feedback during upload process

### 🎨 Improved User Interface
- **Professional Styling**: Modern, WordPress-consistent design
- **Responsive Layout**: Works perfectly on desktop, tablet, and mobile
- **Icon-based Actions**: Clear visual buttons with emoji icons
- **Hover Effects**: Interactive feedback for better user experience

## How to Use

### 1. Adding Files via Media Library

#### Single File Upload:
1. Click the **📁 Upload** button next to any file input
2. WordPress Media Library opens
3. Either:
   - **Upload New**: Drag files or click "Upload files" tab
   - **Select Existing**: Choose from "Media Library" tab
4. Click **"Use This File"** to select
5. File URL and information automatically populate

#### Multiple Files Upload:
1. Use **➕ Add Another File** to create additional file inputs
2. Upload different files using the **📁 Upload** button for each row
3. Each file gets its own secure download link
4. Remove unwanted files with **✖ Remove** button

### 2. Manual URL Entry
- You can still manually enter file URLs if needed
- Useful for external files or direct links
- Combined with upload functionality for maximum flexibility

### 3. File Management
- **Clear All**: Use 🗑️ **Clear All** button to remove all files at once
- **Individual Removal**: Use **✖ Remove** on specific file rows
- **Rearrange**: Files maintain their order as entered

## Technical Implementation

### WordPress Media Library Integration

```javascript
// Create WordPress Media Uploader
mediaUploader = wp.media({
    title: 'Select Document File',
    button: {
        text: 'Use This File'
    },
    multiple: false,
    library: {
        type: ['application', 'text'] // Document types only
    }
});

// Handle file selection
mediaUploader.on('select', function() {
    const attachment = mediaUploader.state().get('selection').first().toJSON();
    // Set file URL and display information
});
```

### Enqueued Scripts

```php
// WordPress Media Library scripts
wp_enqueue_media();
wp_enqueue_script('media-upload');
wp_enqueue_script('thickbox');
wp_enqueue_style('thickbox');
```

### File Type Support

The media uploader automatically filters for document types:
- **PDF Files**: `.pdf`
- **Microsoft Office**: `.doc`, `.docx`, `.xls`, `.xlsx`, `.ppt`, `.pptx`
- **Text Files**: `.txt`, `.rtf`
- **Other Documents**: Based on MIME type `application/*` and `text/*`

## User Interface Elements

### File Input Row Structure
```html
<div class="file-input-row">
    <input type="url" class="file-url-input" placeholder="Enter file URL or click Upload" />
    <button class="upload-file-button">📁 Upload</button>
    <button class="remove-file-button">✖ Remove</button>
    <span class="file-size-display">📄 filename.pdf (2.5 MB)</span>
</div>
```

### Action Buttons
- **📁 Upload**: Opens WordPress Media Library
- **✖ Remove**: Removes specific file row
- **➕ Add Another File**: Creates new file input
- **🗑️ Clear All**: Removes all files with confirmation

### Visual Feedback
```css
/* Upload states */
.file-input-row.uploading { background: #e3f2fd; }
.file-input-row.uploaded { background: #e8f5e8; }

/* Hover effects */
.file-input-row:hover { border-color: #007cba; }
```

## Responsive Design

### Desktop (1200px+)
- Horizontal layout with all elements in a row
- Full-width file input with buttons on the right
- File information displayed inline

### Tablet (768px - 1199px)
- Maintained horizontal layout
- Slightly reduced button sizes
- Touch-friendly button spacing

### Mobile (< 768px)
- Vertical stacking of all elements
- Full-width buttons for easier touch interaction
- File input takes full width
- Buttons stack vertically below input

## Error Handling

### Fallback for Non-Media Library Environments
```javascript
if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
    // Use WordPress Media Library
} else {
    // Fallback to basic file input
    console.warn('WordPress Media Library not available. Using fallback.');
}
```

### Upload Error Handling
- Network errors: User-friendly error messages
- File type errors: Automatic filtering prevents wrong file types
- Size limits: Respects WordPress upload limits

## Security Features

### File Validation
- MIME type checking on upload
- File extension validation
- WordPress security filters applied
- Sanitized file URLs before storage

### Access Control
- Respects WordPress media library permissions
- Only users with upload privileges can use uploader
- Secure file URLs generated after upload

## Customization Options

### Custom File Type Filters
```javascript
// Customize allowed file types
library: {
    type: ['application/pdf', 'application/msword'] // PDF and Word only
}
```

### Custom Upload Button Text
```php
// Localization support
button: {
    text: <?php _e('Use This File', 'lift-docs-system'); ?>
}
```

### Styling Customization
```css
/* Custom upload button styling */
.upload-file-button {
    background: your-brand-color;
    border-radius: your-preferred-radius;
}
```

## Troubleshooting

### Common Issues

1. **Media Library Not Opening**
   - Check if `wp.media` is loaded
   - Ensure `wp_enqueue_media()` is called
   - Verify user permissions for media uploads

2. **File Not Appearing After Upload**
   - Check JavaScript console for errors
   - Verify AJAX responses
   - Ensure file URL is being set correctly

3. **Upload Button Not Working**
   - Check if jQuery is loaded
   - Verify media uploader initialization
   - Check for JavaScript conflicts

### Debug Mode
```php
// Enable debug logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    console.log('Media uploader initialized');
}
```

## Browser Compatibility

### Supported Browsers
- **Chrome**: Full support
- **Firefox**: Full support  
- **Safari**: Full support
- **Edge**: Full support
- **IE 11**: Basic support (fallback mode)

### Mobile Browsers
- **iOS Safari**: Full support
- **Chrome Mobile**: Full support
- **Samsung Internet**: Full support

## Best Practices

### For Users
1. **File Organization**: Keep files organized in WordPress media library
2. **File Naming**: Use descriptive filenames before upload
3. **File Sizes**: Consider file sizes for better performance
4. **File Formats**: Use widely supported document formats

### For Developers
1. **Error Handling**: Always provide fallback options
2. **Performance**: Load media scripts only when needed
3. **Accessibility**: Ensure keyboard navigation works
4. **Testing**: Test on various devices and browsers

## Updates and Migration

### From Previous Versions
- Existing manual URL entries remain unchanged
- New upload functionality added alongside existing features
- No data migration required
- Backward compatibility maintained

### Future Enhancements
- Bulk file upload support
- Drag-and-drop interface enhancement
- File preview capabilities
- Advanced file management features

---

**Ready to use!** The WordPress Media Library integration is now fully functional and provides a professional, user-friendly file upload experience. 🚀
