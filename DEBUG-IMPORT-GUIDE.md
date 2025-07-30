# 🔧 LIFT Forms Import Debug Guide

## Lỗi hiện tại: "Invalid form structure: Missing required field: layout"

### 📋 Files đã tạo để debug:

1. **simple-test-form.json** - Form JSON đơn giản để test
2. **test-form.json** - Bản sao của sample form
3. **debug-json.php** - Script kiểm tra JSON files
4. **test-upload.html** - Trang web test upload file

### 🔍 Các bước debug:

#### 1. Kiểm tra JSON files hợp lệ:
```bash
php debug-json.php
```
✅ Kết quả: Cả hai files đều hợp lệ và có đầy đủ fields

#### 2. Test upload với HTML page:
Mở `test-upload.html` trong browser và test upload JSON files

#### 3. Kiểm tra WordPress error logs:
```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

#### 4. Debug trong browser:
- Mở Developer Tools → Console
- Thử import form và xem logs
- Kiểm tra Network tab cho AJAX requests

### 🚨 Các nguyên nhân có thể:

1. **File upload issue**: File không được upload đúng cách
2. **JSON parsing error**: File bị corrupt khi upload
3. **Validation logic error**: Validation function nhận data sai
4. **MIME type issue**: Server reject JSON files

### 🔧 Code modifications đã thêm:

#### Debug trong AJAX handler:
- Log tất cả POST và FILES data
- Log file upload details
- Log JSON parsing process
- Enhanced error messages

#### Debug trong JavaScript:
- Log file selection
- Validate file before upload
- Log server responses
- Better error handling

### 📝 Test steps để debug:

1. **WordPress Admin Test**:
   - Vào LIFT Forms admin page
   - Mở Developer Console
   - Click "Import Form"
   - Chọn `simple-test-form.json`
   - Submit và xem logs

2. **HTML Test**:
   - Mở `test-upload.html`
   - Upload cùng file JSON
   - So sánh kết quả

3. **Log Analysis**:
   - Kiểm tra WordPress debug logs
   - Tìm "LIFT Forms Import" entries
   - Analyze từng step

### 🎯 Expected debug output:

```
LIFT Forms Import: POST data: Array ( [action] => lift_forms_import [nonce] => ... [form_name] => ... )
LIFT Forms Import: FILES data: Array ( [import_file] => Array ( [name] => ... [type] => ... ) )
LIFT Forms Import: File info - Name: simple-test-form.json, Type: application/json, Size: 476
LIFT Forms Import: Raw JSON length: 476
LIFT Forms Import: First 200 chars: { "name": "Simple Test Form", ...
LIFT Forms Import: Decoded data keys: name, description, layout, fields
LIFT Forms Import Debug: Array ( [0] => name [1] => description [2] => layout [3] => fields )
LIFT Forms Import: Validation passed successfully
```

### ⚡ Quick fixes to try:

1. **Check PHP upload limits**:
```php
echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . "\n";
echo 'post_max_size: ' . ini_get('post_max_size') . "\n";
```

2. **Test with minimal JSON**:
Use `simple-test-form.json` (smaller file)

3. **Check file permissions**:
Ensure WordPress can write to upload directory

4. **Disable other plugins**:
Test with only LIFT plugin active

### 📞 Next actions:

1. Run debug steps above
2. Check error logs
3. Test with HTML page
4. Compare results
5. Report specific error details

---

🔍 **Current status**: Debugging phase - investigating why validation fails despite valid JSON files
