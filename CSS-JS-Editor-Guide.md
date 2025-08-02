# LIFT Docs System - CSS/JS Editor Guide

## Overview
The CSS/JS Editor allows you to add custom styling and functionality to your LIFT Docs System without modifying core plugin files.

## CSS Editor

### Getting Started
1. Navigate to **LIFT Docs > CSS Editor** in your WordPress admin
2. Write your custom CSS in the editor
3. Click **Save CSS** to apply your changes

### Useful CSS Classes
The LIFT Docs System uses these CSS classes that you can target:

#### Document Lists
- `.lift-docs-list` - Container for document lists
- `.lift-doc-item` - Individual document items
- `.lift-doc-title` - Document title
- `.lift-doc-meta` - Document metadata
- `.lift-doc-status` - Status badge

#### Forms
- `.lift-forms-container` - Form container
- `.lift-form-field` - Individual form fields
- `.lift-btn` - Buttons
- `.lift-login-form` - Login form container

#### Status Classes
- `.lift-doc-status.pending` - Pending documents
- `.lift-doc-status.completed` - Completed documents
- `.lift-doc-status.in-progress` - In-progress documents

### CSS Tips
1. Use specific selectors to avoid conflicts
2. Test your CSS on different screen sizes
3. Use the browser's developer tools for debugging
4. Consider dark mode users with `@media (prefers-color-scheme: dark)`

### Example CSS
```css
/* Custom document item styling */
.lift-doc-item {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.lift-doc-item:hover {
    transform: translateY(-5px);
}

/* Custom status colors */
.lift-doc-status.custom-status {
    background: #e74c3c;
    color: white;
}
```

## JavaScript Editor

### Getting Started
1. Navigate to **LIFT Docs > JS Editor** in your WordPress admin
2. Write your custom JavaScript in the editor
3. Click **Save JavaScript** to apply your changes

### Available Variables
The LIFT Docs System provides these JavaScript variables:

- `lift_docs_ajax.ajax_url` - WordPress AJAX URL
- `lift_docs_ajax.nonce` - Security nonce
- `jQuery` or `$` - jQuery library

### Global Functions
The system exposes these functions via `window.LiftDocs`:

- `LiftDocs.showNotification(message, type)` - Show notifications
- `LiftDocs.showLoader()` - Show loading spinner
- `LiftDocs.hideLoader()` - Hide loading spinner
- `LiftDocs.updateDocumentStatus(docId, status)` - Update document status
- `LiftDocs.validateForm(form)` - Validate form inputs

### JavaScript Tips
1. Wrap your code in `jQuery(document).ready()` for DOM manipulation
2. Use `'use strict';` at the beginning of your code
3. Handle errors gracefully with try-catch blocks
4. Use console.log() for debugging

### Example JavaScript
```javascript
jQuery(document).ready(function($) {
    'use strict';
    
    // Custom document click handler
    $('.lift-doc-item').on('click', function() {
        var docId = $(this).data('doc-id');
        console.log('Document clicked:', docId);
    });
    
    // Custom form validation
    $('.my-custom-form').on('submit', function(e) {
        var isValid = true;
        
        $(this).find('input[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            LiftDocs.showNotification('Please fill all required fields', 'error');
        }
    });
    
    // AJAX example
    $('#my-button').on('click', function() {
        $.ajax({
            url: lift_docs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'my_custom_action',
                nonce: lift_docs_ajax.nonce,
                data: 'some_data'
            },
            success: function(response) {
                if (response.success) {
                    LiftDocs.showNotification('Success!', 'success');
                }
            },
            error: function() {
                LiftDocs.showNotification('Error occurred', 'error');
            }
        });
    });
});
```

## Security Considerations

### CSS Security
- CSS is automatically sanitized to remove potentially harmful code
- PHP tags and script tags are automatically removed
- Avoid using `@import` statements with external URLs

### JavaScript Security
- JavaScript is sanitized to remove PHP and HTML tags
- Always validate and sanitize user inputs
- Use WordPress nonces for AJAX requests
- Avoid using `eval()` or similar dangerous functions

## Best Practices

### Performance
1. Minimize your CSS and JavaScript for production
2. Avoid complex selectors that could slow down rendering
3. Use efficient JavaScript loops and avoid memory leaks
4. Test on different devices and browsers

### Maintenance
1. Comment your code for future reference
2. Keep backups of your custom code
3. Test changes on a staging site first
4. Document any custom functionality

### Compatibility
1. Use modern CSS features with fallbacks
2. Test JavaScript in different browsers
3. Consider mobile users and touch interfaces
4. Follow WordPress coding standards

## Troubleshooting

### CSS Not Applying
1. Check for syntax errors in your CSS
2. Verify selectors are targeting the correct elements
3. Check if other CSS is overriding your styles
4. Clear any caching plugins

### JavaScript Not Working
1. Check the browser console for errors
2. Verify jQuery is available
3. Make sure DOM elements exist before targeting them
4. Check AJAX responses for errors

### Common Issues
- **Caching**: Clear browser and plugin caches after making changes
- **Conflicts**: Other plugins may interfere with your custom code
- **Updates**: Your custom code persists through plugin updates
- **Permissions**: Ensure you have admin privileges to save changes

## Support

If you encounter issues with the CSS/JS Editor:

1. Check the browser console for error messages
2. Validate your CSS and JavaScript syntax
3. Test with other themes and plugins disabled
4. Contact plugin support with specific error details

Remember to always test your changes thoroughly before deploying to a live site!
