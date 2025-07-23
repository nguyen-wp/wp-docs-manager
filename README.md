# LIFT Docs System

A comprehensive document management system for WordPress with advanced features.

## Description

LIFT Docs System is a powerful WordPress plugin that allows you to manage, organize, and display documents on your website. It includes features like document categories, tags, analytics, search functionality, and much more.

## Features

- **Custom Post Type**: Dedicated document post type with full WordPress support
- **Categories & Tags**: Organize documents with hierarchical categories and tags
- **Analytics**: Track document views, downloads, and user engagement
- **Search Functionality**: Built-in search with AJAX support
- **Security**: Password protection and access control for documents
- **File Management**: Easy file uploads with size and type restrictions
- **Responsive Design**: Mobile-friendly templates and layouts
- **Admin Dashboard**: Comprehensive admin interface with statistics
- **Shortcodes**: Multiple shortcodes for displaying documents
- **Custom Templates**: Override templates in your theme

## Installation

1. Upload the plugin files to the `/wp-content/plugins/lift-docs-system/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to LIFT Docs > Settings to configure the plugin
4. Start adding documents through LIFT Docs > Add New

## Shortcodes

### [lift_documents]
Display a list of documents.

**Attributes:**
- `category` - Filter by category slug
- `tag` - Filter by tag slug
- `limit` - Number of documents to show (default: 10)
- `orderby` - Sort order (date, title, menu_order, comment_count)
- `order` - ASC or DESC (default: DESC)
- `show_excerpt` - Show excerpt (true/false, default: true)
- `show_meta` - Show metadata (true/false, default: true)

**Examples:**
```
[lift_documents limit="5" category="reports"]
[lift_documents orderby="title" order="ASC"]
[lift_documents tag="important" show_excerpt="false"]
```

### [lift_document_search]
Display a document search form.

**Attributes:**
- `placeholder` - Search input placeholder text

**Example:**
```
[lift_document_search placeholder="Search our documents..."]
```

### [lift_document_categories]
Display a list of document categories.

**Attributes:**
- `show_count` - Show document count (true/false, default: true)
- `hide_empty` - Hide empty categories (true/false, default: true)

**Example:**
```
[lift_document_categories show_count="false"]
```

## Template Override

You can override the plugin templates by copying them to your theme:

1. Copy `templates/archive-lift_document.php` to `your-theme/lift-docs/archive-lift_document.php`
2. Copy `templates/single-lift_document.php` to `your-theme/lift-docs/single-lift_document.php`

## Hooks and Filters

### Actions
- `lift_docs_document_viewed` - Fired when a document is viewed
- `lift_docs_document_downloaded` - Fired when a document is downloaded
- `lift_docs_before_content` - Before document content
- `lift_docs_after_content` - After document content

### Filters
- `lift_docs_document_content` - Filter document content
- `lift_docs_search_results` - Filter search results
- `lift_docs_meta_fields` - Filter meta fields
- `lift_docs_allowed_file_types` - Filter allowed file types

## Configuration

### General Settings
- Enable/disable analytics tracking
- Enable/disable comments on documents
- Enable/disable search functionality
- Enable/disable categories and tags

### Display Settings
- Documents per page
- Show document metadata
- Show download buttons
- Show view counts

### Security Settings
- Require login to view documents
- Require login to download documents
- Allowed file types
- Maximum file size

## Analytics

The plugin tracks various metrics:
- Document views
- Download counts
- Time spent reading
- User engagement
- Popular documents

Access analytics through LIFT Docs > Analytics in your admin panel.

## Database Tables

The plugin creates the following custom table:
- `wp_lift_docs_analytics` - Stores analytics data

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support and documentation, please visit [our website](https://example.com/support).

## Changelog

### 1.0.0
- Initial release
- Document management system
- Analytics tracking
- Search functionality
- Shortcode support
- Template system

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Your Name](https://example.com)

## Contributing

Contributions are welcome! Please submit pull requests to our GitHub repository.

## Security

If you discover any security vulnerabilities, please email us at security@example.com instead of using the issue tracker.
