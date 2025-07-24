# LIFT Forms - Advanced Form Builder for WordPress

LIFT Forms là một tính năng mới trong hệ thống LIFT Docs, cung cấp khả năng tạo form động với giao diện kéo thả trực quan.

## 🚀 Tính Năng Chính

### 🎨 Form Builder Trực Quan
- **Kéo thả dễ dàng**: Kéo các field từ palette vào canvas
- **Preview real-time**: Xem trước form ngay lập tức
- **Sắp xếp linh hoạt**: Thay đổi thứ tự field bằng cách kéo thả
- **Responsive design**: Tự động tối ưu cho mobile

### 📝 Các Loại Field Phong Phú
- **Basic Fields**: Text, Email, Number, Date, Textarea, File Upload
- **Choice Fields**: Dropdown, Radio Buttons, Checkboxes
- **Layout Fields**: Sections, Columns (2-4 cột), HTML Blocks

### ⚙️ Tùy Chỉnh Chi Tiết
- **Validation**: Required fields, email validation, number ranges
- **Styling**: Custom labels, descriptions, placeholder text
- **Options**: Dynamic choices cho dropdown/radio/checkbox
- **File handling**: File type restrictions, size limits

### 📊 Quản Lý Submissions
- **Tracking**: Theo dõi tất cả form submissions
- **Filtering**: Lọc theo form, status, ngày tháng
- **Notifications**: Email thông báo cho admin
- **Export**: Xuất dữ liệu submissions

## 🛠️ Cài Đặt & Sử Dụng

### Bước 1: Truy Cập Form Builder
1. Vào WordPress Admin
2. Navigate to: **LIFT Documents → Forms → Form Builder**
3. Click "Add New Form"

### Bước 2: Tạo Form
1. **Đặt tên form** và mô tả
2. **Kéo fields** từ palette bên trái vào canvas
3. **Click vào field** để chỉnh sửa settings
4. **Sắp xếp layout** bằng sections và columns
5. **Lưu form**

### Bước 3: Hiển Thị Form
Sử dụng shortcode để hiển thị form:
```
[lift_form id="1" title="true"]
```

### Bước 4: Quản Lý Submissions
- Xem submissions tại: **LIFT Documents → Forms → Submissions**
- Filter theo form cụ thể
- View chi tiết từng submission

## 📋 Ví Dụ Shortcode

### Form Liên Hệ Cơ Bản
```
[lift_form id="1"]
```

### Form Không Hiển Thị Title
```
[lift_form id="1" title="false"]
```

### Form Với Custom Styling
```
<div class="custom-form-wrapper">
    [lift_form id="1"]
</div>
```

## 🎯 Cấu Trúc Database

### Bảng Forms (`wp_lift_forms`)
- `id`: Form ID
- `name`: Tên form
- `description`: Mô tả form
- `form_fields`: JSON data của các fields
- `settings`: Cài đặt form
- `status`: active/inactive
- `created_by`: User tạo form
- `created_at`, `updated_at`: Timestamps

### Bảng Submissions (`wp_lift_form_submissions`)
- `id`: Submission ID
- `form_id`: ID của form
- `form_data`: JSON data submissions
- `user_ip`: IP address
- `user_agent`: Browser info
- `submitted_at`: Thời gian submit
- `status`: read/unread

## 🔧 API và Hooks

### AJAX Actions
- `lift_forms_save`: Lưu form
- `lift_forms_delete`: Xóa form
- `lift_forms_submit`: Submit form từ frontend

### WordPress Hooks
```php
// Customize form validation
add_filter('lift_forms_validate_submission', 'custom_validation', 10, 3);

// Custom form fields
add_filter('lift_forms_field_types', 'add_custom_field_type');

// Email notifications
add_filter('lift_forms_notification_email', 'custom_notification');
```

## 🎨 Customization

### CSS Classes
```css
.lift-form-container { /* Form wrapper */ }
.lift-form { /* Form element */ }
.lift-form-field { /* Individual field wrapper */ }
.lift-form-submit-btn { /* Submit button */ }
.form-error { /* Error messages */ }
.form-success { /* Success messages */ }
```

### JavaScript Events
```javascript
// Form submission start
$(document).on('lift_form_submit_start', function(e, form) {
    // Custom logic
});

// Form submission complete
$(document).on('lift_form_submit_complete', function(e, form, response) {
    // Custom logic
});
```

## 📱 Responsive Design

Forms tự động responsive với:
- **Desktop**: Full layout với columns
- **Tablet**: Columns tự động stack
- **Mobile**: Single column layout
- **Touch-friendly**: Buttons và inputs tối ưu cho touch

## 🔐 Security Features

- **Nonce verification**: Bảo vệ CSRF attacks
- **Input sanitization**: Làm sạch dữ liệu input
- **File validation**: Kiểm tra loại file và kích thước
- **Rate limiting**: Giới hạn số lần submit
- **SQL injection protection**: Prepared statements

## 🚀 Performance

- **AJAX submissions**: Không reload page
- **Lazy loading**: Chỉ load scripts khi cần
- **Minified assets**: CSS/JS được optimize
- **Caching friendly**: Compatible với caching plugins
- **Database optimization**: Efficient queries

## 🐛 Troubleshooting

### Form Không Hiển Thị
1. Kiểm tra form status = "active"
2. Verify shortcode ID đúng
3. Đảm bảo form có ít nhất 1 field
4. Check plugin conflicts

### Submissions Không Hoạt Động
1. Kiểm tra AJAX functionality
2. Verify nonce validation
3. Review server error logs
4. Test với default theme

### File Upload Issues
1. Check upload permissions
2. Verify file size limits
3. Review accepted file types
4. Check server PHP limits

### Debug Mode
Enable WordPress debug:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📚 Examples

### Contact Form
```
Fields: Name (text), Email (email), Subject (select), Message (textarea)
Validation: Name và Email required
Options: Subject có General, Support, Sales
```

### Job Application
```
Section: Personal Information
Fields: Full Name, Email, Phone, Resume (file)

Section: Experience  
Fields: Previous Role (text), Years of Experience (number), Skills (checkbox)

Section: Availability
Fields: Start Date (date), Salary Expectation (number)
```

### Event Registration
```
Section: Event Details
HTML Block: Event information

Section: Attendee Information
Fields: Name, Email, Company

Section: Preferences
Fields: Session Track (radio), Meal Preference (select), Special Requirements (textarea)
```

## 🎉 Best Practices

### Form Design
- Giữ form ngắn gọn và tập trung
- Sử dụng labels rõ ràng
- Nhóm fields liên quan trong sections
- Đánh dấu required fields rõ ràng

### User Experience
- Test trên mobile devices
- Sử dụng input types phù hợp
- Cung cấp error messages rõ ràng
- Xác nhận submission thành công

### Performance
- Optimize file upload sizes
- Use pagination cho forms dài
- Monitor submission volumes
- Regular database cleanup

## 🆕 Tính Năng Sắp Tới

- [ ] **Multi-step forms**: Forms nhiều bước với progress bar
- [ ] **Conditional logic**: Hiện/ẩn fields dựa trên điều kiện
- [ ] **Form templates**: Pre-built form templates
- [ ] **Advanced validation**: Custom validation rules
- [ ] **Integration**: MailChimp, Google Sheets, Zapier
- [ ] **Analytics**: Form completion rates, field analytics
- [ ] **A/B Testing**: Test different form versions

## 🤝 Hỗ Trợ

Nếu gặp vấn đề hoặc có góp ý:
1. Check documentation trước
2. Review error logs
3. Test với plugins khác disabled
4. Liên hệ support với thông tin chi tiết

---

**LIFT Forms** - Tạo forms chuyên nghiệp, dễ dàng và nhanh chóng! 🚀
