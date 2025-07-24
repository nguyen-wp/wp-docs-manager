# LIFT Docs - Permanent Token System

## 🔄 System Changes

Chúng ta đã loại bỏ hoàn toàn hệ thống **encryption key** và thay thế bằng **permanent token system** sử dụng hash-based tokens.

## ✨ Tính Năng Mới

### 1. **Permanent View URLs**
- Mỗi document có **1 URL duy nhất** không bao giờ hết hạn
- URL được tạo bằng hash của: `document_id + post_date + wp_salt`
- Format: `/lift-docs/secure/?lift_secure=PERMANENT_HASH`

### 2. **Permanent Download URLs** 
- Mỗi file có URL download riêng, không hết hạn
- File đầu tiên: `/lift-docs/download/?lift_secure=PERMANENT_HASH`
- File thứ 2+: `/lift-docs/download/?lift_secure=PERMANENT_HASH_file_1`

### 3. **Consistent Layout**
- **Luôn sử dụng multiple files layout** cho view page
- Dù 1 file hay nhiều files đều hiển thị dạng danh sách
- File cards với icon, tên file, và download button

## 🏗️ Technical Implementation

### Token Generation
```php
// Tạo permanent token từ document data
$data_to_hash = $document_id . '|' . $post->post_date . '|' . wp_salt('secure_auth');
$permanent_token = hash('sha256', $data_to_hash);

// Lưu vào post meta
update_post_meta($document_id, '_lift_doc_permanent_token', $permanent_token);
```

### Token Verification
```php
// Tìm document bằng token
$document_id = $wpdb->get_var($wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} 
     WHERE meta_key = '_lift_doc_permanent_token' 
     AND meta_value = %s",
    $base_token
));
```

### File Index Handling
```php
// Parse file index từ token
if (strpos($token, '_file_') !== false) {
    $parts = explode('_file_', $token);
    $base_token = $parts[0];
    $file_index = intval($parts[1]);
}
```

## 🎨 Layout Consistency

### Before (Inconsistent)
- Single file: Simple input + button
- Multiple files: Complex list layout

### After (Consistent)
- **Luôn sử dụng files list layout**
- Mỗi file hiển thị trong card riêng
- File icon + tên + download button
- Responsive cho mobile

## 📱 Admin Modal Updates

### JavaScript Changes
```javascript
// Luôn sử dụng multiple files layout
$('#lift-single-secure-download').hide();
$('#lift-multiple-secure-downloads').show();

// Tạo file cards cho tất cả files
secureDownloadUrls.forEach(function(fileData, index) {
    // Render file card với icon + tên + button
});
```

## 🔧 Removed Components

### Settings Fields
- ❌ `encryption_key` field
- ❌ "Generate New Key" button  
- ❌ Encryption warning messages

### PHP Methods
- ❌ `encrypt_data()`
- ❌ `decrypt_data()`
- ❌ `get_encryption_key()`
- ❌ `get_encryption_key_internal()`
- ❌ `generate_encryption_key()`

## 🚀 Benefits

### 1. **Simplified Architecture**
- Không cần quản lý encryption keys
- Không lo key bị mất hoặc thay đổi
- Code đơn giản hơn, ít bug hơn

### 2. **Better UX**
- URLs không bao giờ hết hạn
- Consistent layout cho tất cả cases
- Mobile-friendly design

### 3. **Security**
- Tokens vẫn secure (SHA-256 hash)
- Không thể guess được document ID từ token
- Database lookup để verify

### 4. **Performance**
- Không cần encrypt/decrypt operations
- Fast hash comparison
- Cached post meta lookups

## 🔗 URL Examples

### View URLs
```
/lift-docs/secure/?lift_secure=a1b2c3d4e5f6...
```

### Download URLs
```
# File đầu tiên
/lift-docs/download/?lift_secure=a1b2c3d4e5f6...

# File thứ hai  
/lift-docs/download/?lift_secure=a1b2c3d4e5f6..._file_1

# File thứ ba
/lift-docs/download/?lift_secure=a1b2c3d4e5f6..._file_2
```

## 🧪 Testing

Chạy test script để verify:
```bash
php test-permanent-tokens.php
```

Test cases:
- ✅ Token generation
- ✅ Token verification  
- ✅ File index parsing
- ✅ Consistency check
- ✅ Invalid token handling
- ✅ Multiple files support

## 📋 Migration Notes

### Existing Documents
- Tự động tạo permanent tokens khi access lần đầu
- Không cần migration script
- Old encrypted tokens sẽ fail gracefully

### Admin Interface
- Encryption key field đã bị remove
- Settings page sạch hơn
- Không còn cảnh báo về key expiry

## 🎯 Result

✅ **Hệ thống đơn giản hơn nhiều**  
✅ **Layout nhất quán 100%**  
✅ **URLs permanent, không hết hạn**  
✅ **Không cần encryption key**  
✅ **Better security & performance**

Giờ đây mỗi document có **1 permanent URL** duy nhất và layout **luôn consistent** cho cả single và multiple files!
