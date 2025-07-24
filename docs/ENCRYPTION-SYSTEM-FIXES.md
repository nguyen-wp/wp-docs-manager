# LIFT Docs Encryption System - Complete Analysis & Fixes

## 🔍 Vấn đề đã phát hiện và sửa

### 1. **Sự không nhất quán trong verify_secure_link()**
**Vấn đề:** Method trả về `document_id` (integer) nhưng code expect array
**Fix:** Cập nhật để trả về toàn bộ data array thay vì chỉ document_id

```php
// Trước (SAI):
return $data['document_id'];

// Sau (ĐÚNG):
return $data;
```

### 2. **Encryption Key Access Methods không nhất quán**
**Vấn đề:** 
- Có method private `get_encryption_key()` 
- Nhiều test scripts gọi public `LIFT_Docs_Settings::get_encryption_key()` nhưng method không tồn tại

**Fix:**
- Tạo public `get_encryption_key()` cho external access
- Rename private method thành `get_encryption_key_internal()` 
- Cập nhật tất cả internal calls

### 3. **Token URL Decoding không nhất quán**
**Vấn đề:** Một số chỗ decode token, một số chỗ không

**Fix:** Đảm bảo tất cả handlers decode token trước khi verify:
```php
$verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
```

### 4. **generate_secure_link() sử dụng wrong key method**
**Vấn đề:** Sử dụng `get_setting('encryption_key')` thay vì method chuyên dụng

**Fix:** Cập nhật để sử dụng `get_encryption_key_internal()`

## 📁 Files đã được cập nhật

### class-lift-docs-settings.php
- ✅ `verify_secure_link()`: Trả về full data array
- ✅ `get_encryption_key()`: Public method cho external access  
- ✅ `get_encryption_key_internal()`: Private method với auto-generation
- ✅ `generate_secure_link()`: Sử dụng internal key method
- ✅ Tất cả internal methods sử dụng `get_encryption_key_internal()`

### class-lift-docs-secure-links.php
- ✅ `handle_secure_access()`: URL decode token trước khi verify
- ✅ `handle_secure_download()`: URL decode token + xử lý array verification

### class-lift-docs-layout.php  
- ✅ `handle_secure_download()`: URL decode token + xử lý array verification

## 🧪 Test Scripts được tạo

### test-encryption-system.php
- **Comprehensive test** cho toàn bộ encryption system
- Test raw encrypt/decrypt methods
- Test key consistency 
- Test URL encoding/decoding
- Test error conditions
- **Khuyên dùng script này trước tiên**

### Các test scripts khác:
- `complete-debug.php`: Debug toàn diện
- `test-secure-access.php`: Test secure access
- `debug-token-verification.php`: Debug token chi tiết

## 🔧 Cách test và verify

### Step 1: Test Encryption System
```
yoursite.com/wp-content/plugins/wp-docs-manager/test-encryption-system.php
```
**Kiểm tra:**
- ✅ Key consistency across methods
- ✅ Raw encrypt/decrypt functionality  
- ✅ Token generation & verification
- ✅ URL encoding/decoding
- ✅ Error condition handling

### Step 2: Test Secure Access
```
yoursite.com/wp-content/plugins/wp-docs-manager/complete-debug.php
```
**Kiểm tra:**
- ✅ Rewrite rules
- ✅ Settings configuration
- ✅ Token verification trong context
- ✅ Manual testing links

### Step 3: Test Real Functionality
- Truy cập secure links được generate
- Test download links
- Kiểm tra metabox trong admin

## ⚠️ Potential Issues để watch out

### 1. **Key Generation Timing**
- Auto-generation có thể tạo keys khác nhau nếu gọi nhiều lần
- Đảm bảo key được persist properly

### 2. **URL Encoding Layers**
- WordPress có thể add thêm encoding layers
- Có thể cần điều chỉnh decode logic

### 3. **Session Management**
- `session_start()` calls có thể conflict với caching
- Monitor session behavior

### 4. **Performance với large tokens**
- AES-256-CBC với base64 encoding tạo tokens dài
- Monitor URL length limits

## 🎯 Expected Results sau khi fix

### ✅ Secure View Links
```
/lift-docs/secure/?lift_secure=ENCRYPTED_TOKEN
```
- Should load document với secure access notice
- Should respect global layout settings

### ✅ Secure Download Links  
```
/lift-docs/download/?lift_secure=ENCRYPTED_TOKEN
```
- Should trigger file download với proper headers
- Should track download counts

### ✅ Metabox Links
- Current Secure Link: Always shows
- Secure Download Link: Shows nếu có file URL
- Copy buttons: Hoạt động đúng

### ✅ Token Format nhất quán
```json
{
  "document_id": 123,
  "expires": 1640995200,
  "timestamp": 1640908800
}
```

## 🔐 Security Notes

### Encryption Specs:
- **Algorithm:** AES-256-CBC
- **Key:** 32-byte SHA-256 hash của settings key
- **IV:** 16-byte random per token
- **Format:** base64(IV + encrypted_data)

### Token Structure:
- **JSON payload** chứa document_id, expires, timestamp
- **URL-encoded** trong query parameters
- **Verification** checks expiry + document existence

## 📝 Next Steps nếu vẫn có issues

1. **Enable WordPress Debug:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. **Check debug.log** cho encryption errors

3. **Run encryption test** để isolate issues

4. **Flush rewrite rules** nếu URLs return 404

5. **Check key consistency** across different contexts

Với những fixes này, encryption system sẽ hoạt động nhất quán và secure! 🚀
