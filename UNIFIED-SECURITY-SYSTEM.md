# Unified Security System - View URL & Secure Download URL

## Mô tả
Đã cập nhật toàn bộ hệ thống để đảm bảo **TẤT CẢ** access points (View URL, Secure Download URL, Online View) đều tuân thủ cùng quy tắc bảo mật.

## Security Architecture

### 1. Unified Permission Methods
```php
// Frontend Class - Central Permission Hub
class LIFT_Docs_Frontend {
    private function can_user_view_document($post_id) {
        // Checks: login requirement, private docs, password protection
    }
    
    private function can_user_download_document($post_id) {
        // Checks: download login requirement, private docs, password protection
    }
}
```

### 2. All Access Points Secured

#### A. Admin Modal URLs
```php
// class-lift-docs-admin.php
private function render_document_details_button($post_id) {
    // Check permissions BEFORE generating URLs
    $can_view = $frontend->can_user_view_document($post_id);
    $can_download = $frontend->can_user_download_document($post_id);
    
    if ($can_view) {
        $view_url = generate_actual_view_url();
    } else {
        $view_url = wp_login_url(); // Login required URL
    }
    
    if ($can_download) {
        $download_url = generate_actual_download_url();
    } else {
        $download_url = wp_login_url(); // Login required URL
    }
}
```

#### B. Secure Links Handlers
```php
// class-lift-docs-secure-links.php
public function handle_secure_access() {
    // Token verification
    $verification = verify_secure_link($token);
    
    // Permission check using reflection
    $frontend = LIFT_Docs_Frontend::get_instance();
    $can_view = $frontend->can_user_view_document($document_id);
    
    if (!$can_view) {
        $this->show_access_denied();
        return;
    }
    // Continue with document display
}

public function handle_secure_download() {
    // Token verification
    $verification = verify_secure_link($token);
    
    // Permission check using reflection
    $frontend = LIFT_Docs_Frontend::get_instance();
    $can_download = $frontend->can_user_download_document($document_id);
    
    if (!$can_download) {
        status_header(403);
        die('Permission denied');
    }
    // Continue with file serving
}
```

#### C. Layout Handlers
```php
// class-lift-docs-layout.php
public function handle_secure_download() {
    // Token verification
    $verification = verify_secure_link($token);
    
    // Permission check using reflection
    $frontend = LIFT_Docs_Frontend::get_instance();
    $can_download = $frontend->can_user_download_document($document_id);
    
    if (!$can_download) {
        wp_die('Permission denied');
    }
    // Continue with file serving
}
```

#### D. Standard Download Handlers
```php
// class-lift-docs-frontend.php
public function handle_document_download() {
    // Nonce verification
    wp_verify_nonce();
    
    // Permission check
    if (!$this->can_user_download_document($document_id)) {
        wp_redirect(wp_login_url());
        exit;
    }
    // Continue with download
}

public function handle_document_view_online() {
    // Nonce verification  
    wp_verify_nonce();
    
    // Permission check (same as download)
    if (!$this->can_user_download_document($document_id)) {
        wp_redirect(wp_login_url());
        exit;
    }
    // Continue with online view
}
```

## Frontend JavaScript Integration

### Admin Modal Permission Handling
```javascript
// admin-modal.js
function populateModal(data) {
    // Check permission flags from PHP
    if (data.canView === 'true') {
        // Show actual view URL + preview button
        $('#lift-view-url').val(data.viewUrl);
        $('#lift-view-preview').show();
    } else {
        // Show login required message
        $('#lift-view-url').val('Login required');
        $('#lift-view-preview').hide();
    }
    
    if (data.canDownload === 'true') {
        // Show actual download URLs + online view button
        $('#lift-download-url').val(data.downloadUrl);
        $('#lift-online-view').show();
    } else {
        // Show login required message
        $('#lift-download-url').val('Login required to download');
        $('#lift-online-view').hide();
    }
}
```

## Permission Check Matrix

| Access Method | Permission Check | Fallback Action |
|---------------|------------------|-----------------|
| View URL (Normal) | `can_user_view_document()` | Login URL |
| Secure View URL | `can_user_view_document()` | Access Denied Page |
| Download URL (Normal) | `can_user_download_document()` | Login Redirect |
| Secure Download URL | `can_user_download_document()` | 403 Error |
| Online View URL | `can_user_download_document()` | Login Redirect |
| Shortcode Download | `can_user_download_document()` | Login Message |

## Security Rules Applied

### 1. Login Requirements
```php
// Settings
'require_login_to_view' => true/false
'require_login_to_download' => true/false

// Applied to ALL access methods
if (require_login && !is_user_logged_in()) {
    return false; // Deny access
}
```

### 2. Private Documents
```php
// Meta: _lift_doc_private = '1'
$is_private = get_post_meta($post_id, '_lift_doc_private', true);
if ($is_private && !current_user_can('edit_posts')) {
    return false; // Only editors can access
}
```

### 3. Password Protection
```php
// Meta: _lift_doc_password_protected = '1'
// Meta: _lift_doc_password = 'secret123'
$is_password_protected = get_post_meta($post_id, '_lift_doc_password_protected', true);
if ($is_password_protected) {
    $doc_password = get_post_meta($post_id, '_lift_doc_password', true);
    $entered_password = $_SESSION['lift_doc_' . $post_id] ?? '';
    
    if ($doc_password !== $entered_password) {
        return false; // Wrong password
    }
}
```

## User Experience Flow

### 1. Admin Views Document Details
```
1. Click "View Details" button
2. PHP checks permissions for current user
3. If permitted: Shows actual URLs
4. If not permitted: Shows login URLs
5. JavaScript shows/hides buttons accordingly
```

### 2. User Accesses URLs Directly
```
1. User clicks/visits any document URL
2. Handler checks token/nonce validity
3. Handler checks user permissions
4. If permitted: Serves content
5. If not permitted: Redirects to login or shows error
```

### 3. Shortcode Usage
```
1. [lift_document_download id="123"] rendered
2. PHP checks permissions before HTML generation  
3. If permitted: Shows download button
4. If not permitted: Shows login required message
```

## Testing Scenarios

### Scenario 1: Guest User + Login Required
- **View URL**: Returns login URL
- **Secure View**: Shows access denied  
- **Download URL**: Returns login URL
- **Secure Download**: Returns 403 error
- **Shortcode**: Shows login message

### Scenario 2: Logged User + Private Document + Not Editor
- **All URLs**: Denied access
- **Message**: "You do not have permission"

### Scenario 3: Password Protected + Wrong Password
- **All URLs**: Denied access  
- **Action**: Prompt for password

### Scenario 4: Valid User + All Permissions
- **All URLs**: Work normally
- **Experience**: Seamless access

## Benefits

### 1. **Complete Security Coverage**
- No bypass possible through any URL
- Consistent permission checking
- Same rules applied everywhere

### 2. **Better User Experience**  
- Clear messaging about requirements
- Appropriate redirects and errors
- No broken links or confusing states

### 3. **Admin Clarity**
- Shows actual state in modal
- No false URLs when no permission
- Clear indication of access requirements

### 4. **Maintainability**
- Centralized permission logic
- Easy to modify rules in one place
- Consistent behavior across features

## Implementation Files Changed

1. `class-lift-docs-admin.php` - Admin modal permission checking
2. `class-lift-docs-frontend.php` - Core permission methods
3. `class-lift-docs-secure-links.php` - Secure handlers protection  
4. `class-lift-docs-layout.php` - Layout handler protection
5. `admin-modal.js` - JavaScript permission handling
6. `frontend.css` - Login required styling

## Testing
```bash
# Run comprehensive test
php test-view-url-security.php
```

Bây giờ **TẤT CẢ** View URL và Secure Download URL đều tuân thủ cùng quy tắc bảo mật như shortcode download!
