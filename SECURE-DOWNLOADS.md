# LIFT Docs System - Secure Download Feature

## Tính năng mã hóa Download Links

### Mô tả:
- Tất cả download links đều được mã hóa
- Không ai có thể biết được đường dẫn file thực
- File được serve thông qua proxy bảo mật
- Tracking download với analytics

### URL Structure:

```
✅ Secure View: /lift-docs/secure/?lift_secure=abc123xyz
✅ Secure Download: /lift-docs/download/?lift_secure=abc123xyz
❌ Direct File Access: /wp-content/uploads/file.pdf (blocked)
```

### Cách hoạt động:

1. **File Upload:**
   - File được upload vào /wp-content/uploads/
   - URL thực được lưu trong post meta `_lift_doc_file_url`
   - URL thực không bao giờ được hiển thị công khai

2. **Secure View Page:**
   - Hiển thị thông tin document
   - Download button link đến `/lift-docs/download/`
   - Sử dụng cùng token với view page

3. **Secure Download:**
   - Verify token encryption
   - Check document permissions
   - Track download analytics
   - Serve file với proper headers
   - Clean filename for download

### Security Features:

- ✅ **Token-based Access**: Chỉ có token hợp lệ mới download được
- ✅ **File Path Hidden**: URL thực không bao giờ lộ ra
- ✅ **Expiry Control**: Token có thể set thời gian hết hạn
- ✅ **Download Tracking**: Ghi lại mọi lần download
- ✅ **Proper Headers**: Set Content-Disposition, MIME type
- ✅ **Bot Protection**: X-Robots-Tag, robots.txt blocking

### File Serving Methods:

1. **Local Files:**
   ```php
   // File trong /wp-content/uploads/
   // Đọc trực tiếp từ filesystem
   // Stream với buffer 8KB
   ```

2. **Remote Files:**
   ```php
   // File external URLs
   // Proxy download qua wp_remote_get
   // Forward headers và content
   ```

### Admin Interface:

```
Meta Box: "Secure Links"
├── Secure View Link: /lift-docs/secure/?lift_secure=xyz
├── Secure Download Link: /lift-docs/download/?lift_secure=xyz  
├── Custom Expiry Options: 1h, 6h, 24h, 3d, 1w, never
└── Copy to Clipboard buttons
```

### Analytics Tracking:

```php
Actions tracked:
- 'secure_view': Khi view document
- 'secure_download': Khi download file
- IP address, User agent, timestamp
- Update view_count, download_count trong post meta
```

### Implementation Details:

1. **Rewrite Rules:**
   ```php
   '^lift-docs/download/?$' => 'index.php?lift_download=1'
   ```

2. **Token Structure:**
   ```php
   $data = array(
       'document_id' => $id,
       'expires' => $timestamp,
       'type' => 'download',
       'timestamp' => time()
   );
   $token = base64(IV + AES256CBC_encrypt(json_encode($data)))
   ```

3. **File Headers:**
   ```php
   Content-Type: application/pdf
   Content-Disposition: attachment; filename="document.pdf"
   Content-Length: 1234567
   Cache-Control: private
   X-Robots-Tag: noindex, nofollow
   ```

### Security Considerations:

- ⚠️ **Token Reuse**: Cùng token có thể dùng cho view và download
- ✅ **Expiry Time**: Download links có thể set expiry riêng
- ✅ **File Access**: Không thể truy cập trực tiếp file
- ✅ **Session Independent**: Không cần session để download
- ✅ **Cross-Device**: Token có thể share giữa devices

### Testing:

```bash
# Test secure download
curl -L "https://domain.com/lift-docs/download/?lift_secure=abc123" \
     -H "User-Agent: Test" \
     -o downloaded_file.pdf

# Should return file with proper headers
# Should NOT work without valid token
```

### Troubleshooting:

1. **File not found:**
   - Check `_lift_doc_file_url` meta field
   - Verify file exists in uploads directory

2. **Download fails:**
   - Check token expiry
   - Verify encryption key
   - Check file permissions

3. **Headers issues:**
   - Clear output buffer before headers
   - Check for WordPress output

### Configuration:

```php
// Settings có thể điều chỉnh:
'enable_secure_links' => true,
'secure_link_expiry' => 24, // hours
'encryption_key' => 'auto-generated-32-chars'
```
