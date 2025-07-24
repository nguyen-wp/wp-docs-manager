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

### 3. Tích hợp Secure Links vào Document Details

**Thay đổi lớn:**
- **Loại bỏ** Secure Links metabox riêng biệt
- **Tích hợp** toàn bộ Secure Links functionality vào Document Details metabox
- **Metabox duy nhất:** "Document Details & Secure Links"

**Cấu trúc mới:**

#### Document Details & Secure Links Metabox:
- **Vị trí:** Normal area, high priority
- **Sections:**
  1. **File Information:**
     - File URL với upload button
     - File size field (auto-detect)
     - Download count display
  
  2. **🔒 Secure Links:** (tự động hiển thị nếu enabled)
     - Current secure link với copy button
     - Secure download link (nếu có file URL)
     - Visual indicators và feedback

**Layout tối ưu:**
- Header riêng biệt cho Secure Links section
- Border và background để phân biệt sections
- Copy buttons với visual feedback
- Responsive design

### 4. Cải thiện UX

**Tính năng:**
- Single metabox thay vì nhiều metabox rời rạc
- Visual separation giữa Document Details và Secure Links
- Auto-detection secure links status
- Copy buttons có feedback visual
- Clean interface tập trung

### 5. Database Cleanup

**File:** `cleanup-document-settings.php`

**Chức năng:**
- Tự động xóa meta fields cũ: `_lift_doc_featured`, `_lift_doc_private`, `_lift_doc_password_protected`, `_lift_doc_password`
- Override filter hooks để disable các functionality cũ
- Ẩn elements interface còn sót lại
- Admin notice thông báo việc loại bỏ Document Settings

## Cấu trúc Metabox mới

### Metabox duy nhất trong Normal Area:

**Document Details & Secure Links** (High Priority)

**Section 1: File Information**
- File URL với upload button
- File size (bytes) - auto-detect
- Download count display

**Section 2: 🔒 Secure Links** (conditional)
- Current secure link với copy button
- Secure download link (nếu có file)
- Status messages nếu disabled

**Section 3: WordPress Editor** (Classic Editor)
- Mô tả/nội dung document

## Cách sử dụng

1. **Tạo/Chỉnh sửa Document:**
   - Vào Admin → LIFT Docs → Add New hoặc All Documents  
   - Giao diện Classic Editor đơn giản
   - **Chỉ 1 metabox duy nhất:** Document Details & Secure Links

2. **File Information:**
   - Upload file hoặc nhập URL
   - File size tự động detect
   - Xem download statistics

3. **Secure Links (trong cùng metabox):**
   - Auto-generate secure links khi file có sẵn
   - Copy buttons để share dễ dàng
   - Visual feedback khi copy thành công

## Files đã thay đổi

- ✅ `includes/class-lift-docs-post-types.php` - Disable Gutenberg
- ✅ `includes/class-lift-docs-admin.php` - Tích hợp Secure Links vào Document Details
- ✅ `includes/class-lift-docs-secure-links.php` - Comment out separate metabox
- ✅ `cleanup-document-settings.php` - Database cleanup  
- ✅ `test-integration.php` - Testing integrated functionality

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
