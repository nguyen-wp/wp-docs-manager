# LIFT Docs System - Troubleshooting Secure Links

## Vấn đề: Link secure redirect về `/documents/*/` thay vì hiển thị nội dung

### Giải pháp đã triển khai:

1. **Thay đổi xử lý secure access:**
   - Không còn redirect về permalink gốc
   - Hiển thị nội dung document trực tiếp tại URL `/lift-docs/secure/?lift_secure=*`

2. **Cập nhật post type settings:**
   - Khi secure links enabled: `rewrite => false` (tắt URL rewrite)
   - Khi secure links disabled: `rewrite => array('slug' => 'documents')`
   - `publicly_queryable => false` khi secure links enabled

3. **Auto flush rewrite rules:**
   - Khi thay đổi setting enable_secure_links
   - Tự động flush rewrite rules

### Cách test:

1. **Kích hoạt secure links:**
   ```
   WP Admin > LIFT Docs > Settings > Security Tab
   ✅ Enable Secure Links
   ✅ Hide from Sitemap
   Save Changes
   ```

2. **Tạo document test:**
   ```
   WP Admin > LIFT Docs > Add New
   - Tạo document với title và content
   - Publish document
   ```

3. **Generate secure link:**
   ```
   Edit document > Secure Links meta box
   - Click "Generate New Link" 
   - Copy URL dạng: /lift-docs/secure/?lift_secure=xyz123
   ```

4. **Test access:**
   ```
   ✅ Secure link: /lift-docs/secure/?lift_secure=xyz123
      → Hiển thị document content với security badge

   ❌ Direct link: /documents/document-name/
      → Show "Access Denied" (nếu không phải admin)
   ```

### Kiểm tra rewrite rules:

```php
// Thêm vào functions.php để debug
function debug_lift_rewrite_rules() {
    if (current_user_can('administrator')) {
        $rules = get_option('rewrite_rules');
        echo '<pre>';
        foreach ($rules as $pattern => $rewrite) {
            if (strpos($pattern, 'lift-docs') !== false || strpos($pattern, 'documents') !== false) {
                echo "$pattern => $rewrite\n";
            }
        }
        echo '</pre>';
    }
}
add_action('wp_footer', 'debug_lift_rewrite_rules');
```

### Nếu vẫn có vấn đề:

1. **Flush rewrite rules thủ công:**
   ```
   WP Admin > Settings > Permalinks > Save Changes
   ```

2. **Deactivate/Reactivate plugin:**
   ```
   WP Admin > Plugins > Deactivate LIFT Docs > Activate
   ```

3. **Kiểm tra .htaccess:**
   ```
   Đảm bảo file .htaccess có thể write
   Backup và regenerate permalinks
   ```

### URL Format mong đợi:

```
✅ Secure access: https://domain.com/lift-docs/secure/?lift_secure=abc123xyz
✅ Admin access: https://domain.com/wp-admin/post.php?post=123&action=edit
❌ Direct access: https://domain.com/documents/document-name/ (blocked)
```

### Security features hoạt động:

- ✅ AES-256-CBC encryption cho tokens
- ✅ Session-based access verification  
- ✅ Link expiry (configurable 1-365 days)
- ✅ Hidden from sitemaps
- ✅ Robots.txt blocking
- ✅ Meta noindex tags
- ✅ Direct access blocking
- ✅ Beautiful secure document display page

### Logs để debug:

Thêm vào wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Kiểm tra error logs tại `/wp-content/debug.log`
