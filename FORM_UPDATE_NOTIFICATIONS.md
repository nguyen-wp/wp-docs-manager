# Thông báo Email khi Cập nhật Form

## Tổng quan
Hệ thống đã được cập nhật để gửi email thông báo cho admin mỗi khi user cập nhật form trong document.

## Tính năng mới

### 1. Email Notification khi Form Update
- Tự động gửi email cho admin khi user cập nhật form
- Bao gồm thông tin chi tiết về form và document liên quan
- Hiển thị thông tin user thực hiện cập nhật
- Có link trực tiếp đến trang quản lý forms

### 2. Cài đặt Email Notifications
- Tab mới "Email Notifications" trong Settings
- Bật/tắt thông báo email form updates
- Cấu hình nhiều email recipients (phân cách bằng dấu phẩy)
- Tính năng gửi test email để kiểm tra

### 3. Validation và Security
- Validate email addresses khi nhập
- Fallback về admin email nếu không có email hợp lệ
- Nonce security cho AJAX requests
- Permission checks cho admin functions

## Files đã được sửa đổi

### 1. `includes/class-lift-forms.php`
- Thêm logic gửi email notification sau khi update form
- Kiểm tra settings để chỉ gửi khi được enabled
- Tìm document liên quan đến form để include trong email

### 2. `includes/class-lift-docs-admin.php`
- Thêm method `send_form_update_notification()`
- Support gửi email đến nhiều recipients
- Detailed logging cho debug

### 3. `includes/class-lift-docs-settings.php`
- Thêm tab "Email Notifications" mới
- UI để cấu hình notification settings
- Test email functionality với AJAX
- Validation methods cho email list và checkbox

## Cách sử dụng

### Cấu hình Notifications
1. Vào **LIFT Docs > Settings**
2. Click tab **"Email Notifications"**
3. Bật **"Enable Form Update Notifications"**
4. Nhập email addresses (cách nhau bởi dấu phẩy)
5. Click **"Send Test Email"** để kiểm tra
6. Click **"Save Changes"**

### Email sẽ được gửi khi:
- User cập nhật form trong document (không phải tạo mới)
- Form update notifications được enabled trong settings
- Có ít nhất một email address hợp lệ được cấu hình

### Thông tin trong Email:
- Tên form và ID
- Document chứa form (nếu có)
- User thực hiện cập nhật
- Ngày và giờ cập nhật
- Link đến trang quản lý forms

## Technical Details

### Database Options
- `lift_docs_form_update_notifications`: boolean (enable/disable)
- `lift_docs_form_notification_recipients`: string (comma-separated emails)

### AJAX Actions
- `lift_docs_send_test_email`: Gửi test email

### WordPress Hooks
- Sử dụng `wp_mail()` function
- Integrate với WordPress settings API
- Validation callbacks cho form data

## Security
- Nonce verification cho AJAX requests
- Email validation và sanitization
- Permission checks (manage_options capability)
- XSS prevention trong form outputs

## Logging
- Debug logs khi WP_DEBUG enabled
- Track email send success/failure
- Include recipient count và form details
