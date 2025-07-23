# Tính năng "View Online" - Xem tài liệu trực tuyến

## Mô tả
Đã thêm tính năng cho phép xem tài liệu trực tuyến (online view) thay vì chỉ có thể download.

## Tính năng mới

### 1. Nút "View Online" trong Admin
- Trong danh sách tài liệu admin, click "View Details" 
- Modal hiện ra với section "Download URL" 
- Có thêm nút "View Online" màu xanh bên cạnh nút "Copy"

### 2. Xử lý file types
- **PDF, TXT, HTML, HTM, JPG, JPEG, PNG, GIF, SVG**: Mở trực tiếp trong browser
- **Các file khác**: Tự động chuyển về chế độ download

### 3. Bảo mật
- Sử dụng nonce verification
- Kiểm tra quyền truy cập (nếu có setting require login)
- Tracking riêng cho online view

### 4. Analytics
- Track action "view_online" riêng biệt với "download"
- Không tăng download counter khi view online

## Files đã thay đổi

### 1. `includes/class-lift-docs-admin.php`
```php
// Thêm data attribute cho online view URL
data-online-view-url="<?php echo esc_attr($online_view_url); ?>"

// Thêm nút View Online trong modal HTML
<a href="#" id="lift-online-view" class="button" target="_blank">
    <?php _e('View Online', 'lift-docs-system'); ?>
</a>
```

### 2. `includes/class-lift-docs-frontend.php`
```php
// Thêm handler mới
add_action('init', array($this, 'handle_document_view_online'));

// Method xử lý view online
public function handle_document_view_online() {
    // Kiểm tra nonce và permissions
    // Phân biệt file types
    // Redirect phù hợp
}
```

### 3. `assets/js/admin-modal.js`
```javascript
// Cập nhật populate modal function
$('#lift-online-view').attr('href', data.onlineViewUrl || '#');
if (!data.onlineViewUrl) {
    $('#lift-online-view').hide();
} else {
    $('#lift-online-view').show();
}
```

### 4. `assets/css/admin-modal.css`
```css
/* Styling cho nút View Online */
#lift-online-view {
    background: #0073aa !important;
    color: #fff !important;
    border-color: #0073aa !important;
}
```

## Cách sử dụng

1. **Trong Admin:**
   - Vào "LIFT Docs" > "All Documents"
   - Click "View Details" trên bất kỳ document nào
   - Click nút "View Online" màu xanh

2. **URL Pattern:**
   ```
   https://yoursite.com/?lift_view_online=123&nonce=abc123
   ```

3. **Kiểm tra:**
   - Chạy file `test-online-view.php` để verify implementation

## Benefits

1. **User Experience tốt hơn:**
   - Xem PDF trực tiếp không cần download
   - Xem ảnh/text files trong browser
   - Giữ nguyên download option cho những ai muốn

2. **Quản lý tốt hơn:**
   - Admin có thể preview documents dễ dàng
   - Phân biệt được view vs download analytics

3. **Bảo mật:**
   - Vẫn giữ nguyên security layer
   - Nonce verification cho mọi request

## Tương thích
- Hoạt động với existing secure links
- Không ảnh hưởng đến download functionality hiện tại
- Backward compatible với shortcodes và widgets

## Test
Chạy `test-online-view.php` để kiểm tra implementation.
