# CLASSIC EDITOR CHANGES

## Những thay đổi đã thực hiện

### 1. Tắt WordPress Block Editor (Gutenberg) cho post type `lift_document`

**File đã chỉnh sửa:** `includes/class-lift-docs-post-types.php`

**Thay đổi:**
- Thêm hook `use_block_editor_for_post_type` để tắt Gutenberg
- Thêm method `disable_gutenberg_for_documents()` 
- Đặt `show_in_rest` thành `false` để tắt REST API và Gutenberg

**Kết quả:**
- Post type `lift_document` sẽ sử dụng Classic Editor thay vì Block Editor
- Giao diện edit post sẽ đơn giản hơn và phù hợp với workflow truyền thống

### 2. Loại bỏ hoàn toàn "Document Settings" Metabox

**File đã chỉnh sửa:** 
- `includes/class-lift-docs-admin.php`

**Thay đổi:**
- Xóa hoàn toàn `document_settings_meta_box()` method
- Loại bỏ registration của Document Settings metabox
- Cập nhật `save_meta_boxes()` để chỉ save Document Details fields
- Loại bỏ các fields: featured, private, password_protected, password

**File cleanup:** `cleanup-document-settings.php`
- Tự động xóa meta fields cũ từ database
- Vô hiệu hóa các AJAX actions liên quan
- Ẩn elements trong admin interface
- Override filter hooks để disable functionality

### 3. Giữ lại và tối ưu hóa 2 Metaboxes chính

**Metaboxes còn lại:**

#### Document Details Metabox:
- **Vị trí:** Normal area, high priority
- **Chức năng:** 
  - File URL input với upload button
  - File size field (auto-detect)
  - Download count display (read-only)

#### Secure Links Metabox:
- **Vị trí:** Normal area, default priority  
- **Chức năng:**
  - Secure view link generation
  - Secure download link generation
  - Copy buttons với visual feedback
  - Table layout tối ưu cho normal area

### 4. Cải thiện UX

**Tính năng:**
- Layout table rộng rãi cho normal area
- Copy buttons có feedback visual (hiển thị "Copied!" trong 2 giây)
- Auto-detection file size khi upload
- Responsive design
- Clean interface không còn clutter

### 5. Database Cleanup

**File:** `cleanup-document-settings.php`

**Chức năng:**
- Tự động xóa meta fields cũ: `_lift_doc_featured`, `_lift_doc_private`, `_lift_doc_password_protected`, `_lift_doc_password`
- Override filter hooks để disable các functionality cũ
- Ẩn elements interface còn sót lại
- Admin notice thông báo việc loại bỏ Document Settings

## Cấu trúc Metabox mới

### Thứ tự hiển thị trong Normal Area:

1. **Document Details** (High Priority)
   - File URL với upload button
   - File size (bytes)
   - Download count

2. **Secure Links** (Default Priority)
   - Current secure link
   - Secure download link (nếu có file)
   - Copy buttons

3. **WordPress Editor** (Classic Editor)
   - Mô tả/nội dung document

## Cách sử dụng

1. **Tạo/Chỉnh sửa Document:**
   - Vào Admin → LIFT Docs → Add New hoặc All Documents  
   - Giao diện Classic Editor đơn giản
   - Chỉ 2 metaboxes: Document Details và Secure Links

2. **Document Details:**
   - Upload file hoặc nhập URL
   - File size tự động detect
   - Xem download statistics

3. **Secure Links:**
   - Auto-generate secure links
   - Copy để share dễ dàng
   - Secure download nếu có file

## Files đã thay đổi

- ✅ `includes/class-lift-docs-post-types.php` - Disable Gutenberg
- ✅ `includes/class-lift-docs-admin.php` - Remove Document Settings
- ✅ `includes/class-lift-docs-secure-links.php` - Tối ưu layout
- ✅ `cleanup-document-settings.php` - Database cleanup
- ✅ `test-classic-editor.php` - Testing functionality

## Lưu ý kỹ thuật

- Document Settings functionality hoàn toàn bị loại bỏ
- Database được cleanup tự động
- Secure Links và Document Details vẫn giữ nguyên functionality
- Classic Editor forced cho lift_document post type
- REST API disabled để tăng bảo mật
- Backward compatibility được đảm bảo cho Document Details

## Testing

1. Include cleanup file: `require_once 'cleanup-document-settings.php';`
2. Enable WP_DEBUG để xem cleanup logs
3. Tạo/edit document để xem interface mới
4. Kiểm tra database đã cleanup chưa

## Rollback

Để khôi phục Document Settings (nếu cần):

1. Remove cleanup file
2. Restore Document Settings metabox code in admin file
3. Add back settings fields in save_meta_boxes method
