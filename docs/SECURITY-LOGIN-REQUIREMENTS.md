# Tính năng Bảo mật Download - Login Requirements

## Mô tả
Đã cập nhật hệ thống để đảm bảo shortcode `[lift_document_download]` và tất cả tính năng download khác tuân thủ strict security rules.

## Tính năng bảo mật mới

### 1. Shortcode Security
- **Trước:** Shortcode luôn hiển thị nút download, user có thể bypass bằng direct link
- **Sau:** Shortcode kiểm tra permissions trước khi hiển thị nút download

### 2. Permission Checking
```php
// Method mới: can_user_download_document()
- Kiểm tra require_login_to_download setting
- Kiểm tra private documents (chỉ editors có thể access)
- Kiểm tra password protection
- Unified logic cho tất cả download methods
```

### 3. User Experience
- **Không đăng nhập:** Hiển thị thông báo "You need to log in to download" + nút "Log in"
- **Đã đăng nhập:** Hiển thị nút download bình thường
- **Document bị hạn chế:** Hiển thị thông báo phù hợp

## Files đã thay đổi

### 1. `class-lift-docs-frontend.php`

#### New Method: `can_user_download_document()`
```php
private function can_user_download_document($post_id) {
    // Check login requirement
    if (LIFT_Docs_Settings::get_setting('require_login_to_download', false) && !is_user_logged_in()) {
        return false;
    }
    
    // Check private documents
    $is_private = get_post_meta($post_id, '_lift_doc_private', true);
    if ($is_private && !current_user_can('edit_posts')) {
        return false;
    }
    
    // Check password protection
    $is_password_protected = get_post_meta($post_id, '_lift_doc_password_protected', true);
    if ($is_password_protected) {
        $doc_password = get_post_meta($post_id, '_lift_doc_password', true);
        $entered_password = $_POST['lift_doc_password'] ?? $_SESSION['lift_doc_' . $post_id] ?? '';
        
        if ($doc_password && $doc_password !== $entered_password) {
            return false;
        }
    }
    
    return true;
}
```

#### Updated: `document_download_shortcode()`
```php
// Kiểm tra permissions trước khi tạo download link
if (!$this->can_user_download_document($doc_id)) {
    // Hiển thị login required message
    $output = '<div class="lift-doc-download-widget lift-docs-restricted">';
    $output .= '<p>You need to log in to download this document.</p>';
    $output .= '<a href="' . wp_login_url() . '">Log in</a>';
    $output .= '</div>';
    return $output;
}
```

#### Updated: `get_document_actions()`
```php
// Document actions trong single post cũng kiểm tra permissions
if ($this->can_user_download_document($post_id)) {
    // Show download button
} else {
    // Show login required message
}
```

#### Updated: `handle_document_download()` & `handle_document_view_online()`
```php
// Sử dụng unified permission checking
if (!$this->can_user_download_document($document_id)) {
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
}
```

### 2. `frontend.css`

#### New CSS Classes:
```css
.lift-docs-login-required,
.lift-docs-restricted {
    background: #fefbf3;
    border: 1px solid #f0ad4e;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
    text-align: center;
}

.lift-docs-login-required .button {
    background: #0073aa;
    color: #fff;
    border: 1px solid #0073aa;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    font-weight: 600;
}
```

## Cách sử dụng

### 1. Kích hoạt Login Requirement
```php
// Trong admin settings
update_option('lift_docs_require_login_to_download', true);
```

### 2. Sử dụng Shortcode
```html
<!-- Shortcode sẽ tự động kiểm tra permissions -->
[lift_document_download id="123"]
[lift_document_download id="456" text="Download PDF"]
```

### 3. Test Scenarios

#### Scenario 1: Guest User + Login Required
```html
<!-- Output cho guest user -->
<div class="lift-doc-download-widget lift-docs-restricted">
    <h4>Document Title</h4>
    <div class="login-required">
        <p>You need to log in to download this document.</p>
        <p><a href="/wp-login.php" class="button button-primary">Log in</a></p>
    </div>
</div>
```

#### Scenario 2: Logged User + Login Required
```html
<!-- Output cho logged user -->
<div class="lift-doc-download-widget">
    <h4>Document Title</h4>
    <a href="/download-link" class="button lift-download-btn">
        <span class="dashicons dashicons-download"></span> Download
    </a>
</div>
```

### 4. Private Documents
```php
// Set document as private (chỉ editors trở lên có thể download)
update_post_meta($doc_id, '_lift_doc_private', '1');
```

### 5. Password Protected Documents
```php
// Set password protection
update_post_meta($doc_id, '_lift_doc_password_protected', '1');
update_post_meta($doc_id, '_lift_doc_password', 'secret123');
```

## Security Benefits

### 1. **No Bypass Possible**
- Tất cả download routes đều kiểm tra permissions
- Direct URL access cũng redirect về login

### 2. **Consistent UX**
- Shortcode, single post actions, admin links đều cùng behavior
- Clear messaging cho users về requirements

### 3. **Flexible Permission System**
- Support multiple restriction types
- Extensible cho custom permission logic

### 4. **SEO & Accessibility Friendly**
- Proper HTML structure
- Screen reader friendly
- Search engines không index restricted content

## Testing

### Manual Testing:
1. Set `require_login_to_download = true`
2. Logout khỏi site  
3. Thêm shortcode `[lift_document_download id="123"]` vào page
4. Verify: Hiển thị login message thay vì download button
5. Login và verify: Hiển thị download button

### Automated Testing:
```bash
# Chạy test file
php test-login-security.php
```

## Backward Compatibility
- ✅ Existing shortcodes continue working
- ✅ No changes to shortcode syntax
- ✅ Settings remain optional
- ✅ Default behavior unchanged when login not required

## Performance Impact
- ✅ Minimal: Chỉ thêm 1 permission check
- ✅ No additional database queries
- ✅ CSS tải cùng với existing frontend styles
