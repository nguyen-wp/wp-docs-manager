# Download Links Debug Guide

## Vấn đề hiện tại
Khi nhấp vào download link báo lỗi "Access denied: Invalid or expired token"

## Nguyên nhân đã khắc phục

### 1. Token Verification không nhất quán
- **Vấn đề**: `verify_secure_link()` trả về array nhưng code expect document ID
- **Khắc phục**: Cập nhật `handle_secure_access()` và `handle_secure_download()` để xử lý đúng array

### 2. File storage không nhất quán  
- **Vấn đề**: Plugin sử dụng cả `_lift_file_id` và `_lift_doc_file_url`
- **Khắc phục**: Chuẩn hóa tất cả sử dụng `_lift_doc_file_url`

## Steps để Debug

### Step 1: Tạo test data
```bash
# Truy cập URL này để tạo test document với file URL
https://yoursite.com/wp-content/plugins/wp-docs-manager/create-test-data.php
```

### Step 2: Debug token verification
```bash
# Kiểm tra token generation và verification
https://yoursite.com/wp-content/plugins/wp-docs-manager/debug-token-verification.php
```

### Step 3: Test download functionality
```bash
# Test comprehensive download links
https://yoursite.com/wp-content/plugins/wp-docs-manager/test-download-links.php
```

### Step 4: Quick command line test
```bash
cd /path/to/plugin
php quick-download-test.php
```

## Files đã được cập nhật

### class-lift-docs-secure-links.php
- `handle_secure_access()`: Fixed verification array handling
- Uses `_lift_doc_file_url` consistently

### class-lift-docs-layout.php  
- `handle_secure_download()`: Fixed verification array handling
- Updated to use `_lift_doc_file_url` instead of `_lift_file_id`
- Added `serve_local_file()` method

## Checklist để verify fix

✅ **Token Generation**
- [ ] `generate_secure_link()` tạo token với `document_id`
- [ ] `generate_secure_download_link()` tạo token với `document_id`
- [ ] Cả hai sử dụng cùng encryption method

✅ **Token Verification**
- [ ] `verify_secure_link()` trả về array chứa `document_id`
- [ ] Tất cả handlers xử lý đúng array format
- [ ] Không còn sử dụng `verify_secure_token`

✅ **File Handling**
- [ ] Tất cả chỗ sử dụng `_lift_doc_file_url`
- [ ] Không còn reference đến `_lift_file_id`
- [ ] Handle cả local và external files

✅ **Settings**
- [ ] `enable_secure_links` = true
- [ ] `encryption_key` được set
- [ ] Document có `_lift_doc_file_url` meta

## Debugging Commands

### Check document meta
```php
$doc_id = 123; // Replace with actual document ID
echo get_post_meta($doc_id, '_lift_doc_file_url', true);
```

### Check settings
```php
echo LIFT_Docs_Settings::get_setting('enable_secure_links', false) ? 'Enabled' : 'Disabled';
echo strlen(LIFT_Docs_Settings::get_encryption_key()) . ' char key';
```

### Test token manually
```php
$doc_id = 123;
$token = LIFT_Docs_Settings::generate_secure_download_link($doc_id, 1);
$verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
print_r($verification);
```

## Expected Results

Sau khi fix:
- Download links sẽ hoạt động từ metabox
- Download links sẽ hoạt động từ secure views
- Tất cả sử dụng cùng verification method
- File downloads sẽ trigger proper headers và content
