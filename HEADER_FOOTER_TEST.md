# Test Hướng Dẫn: Header/Footer trong Form Builder

## Những gì đã được implement:

### 1. **Form Builder (Admin)**
- ✅ Thay thế textarea bằng WordPress Editor (TinyMCE)
- ✅ Hỗ trợ rich text editing với toolbar: bold, italic, underline, lists, links, colors
- ✅ Auto-save header/footer content khi save form
- ✅ Load header/footer content khi edit form

### 2. **Frontend Display**
- ✅ Hiển thị header/footer trên form page (/document-form/ID/)
- ✅ CSS styling cho header/footer
- ✅ Responsive design
- ✅ Dark mode support

## Cách test:

### **Bước 1: Tạo/Edit Form với Header/Footer**
1. Đi tới Admin Panel → LIFT Forms → Add New Form (hoặc edit form existing)
2. Trong Form Builder, bạn sẽ thấy 2 editor sections:
   - **Form Header** (phía trên form fields)
   - **Form Footer** (phía dưới form fields)
3. Thêm content vào header/footer (có thể dùng rich text formatting)
4. Save form

### **Bước 2: Kiểm tra trên Frontend**
1. Đi tới trang form: `/document-form/13/39/` (hoặc URL tương tự)
2. Header sẽ hiển thị phía trên form fields
3. Footer sẽ hiển thị phía dưới form fields

### **Bước 3: Debug (nếu không thấy header/footer)**
1. Thêm `?debug=1` vào URL form page (chỉ admin mới thấy)
2. Kiểm tra HTML source để xem debug comments:
   ```html
   <!-- Debug: Form settings: {...} -->
   <!-- Debug: Form header: [content] -->
   <!-- Debug: Form footer: [content] -->
   ```

## Styling của Header/Footer:

### **Header**
- Background: Light blue gradient
- Border-left: Blue (4px)
- Margin-bottom: 30px

### **Footer**  
- Background: Light gray gradient
- Border-left: Gray (4px)
- Margin-top: 30px

### **Admin Readonly View**
- Background: Yellow warning style
- Thể hiện đây là admin view

## Responsive:
- Mobile: Padding và margin được giảm
- Dark Mode: Tự động adapt colors

## Troubleshooting:

### **Nếu không thấy Header/Footer:**
1. Kiểm tra xem form có header/footer content không (dùng `?debug=1`)
2. Kiểm tra CSS có được load không
3. Kiểm tra JavaScript console có lỗi không

### **Nếu WordPress Editor không load:**
1. Kiểm tra browser console có lỗi không
2. Đảm bảo `wp.editor` API có available
3. Fallback về textarea sẽ tự động activate

### **File liên quan:**
- **PHP**: `includes/class-lift-forms.php` (render_form_shortcode, ajax_save_form)
- **JS**: `assets/js/form-builder-bpmn.js` (getHeaderFooterData, loadHeaderFooterData)
- **CSS**: `assets/css/forms-frontend.css` (.form-header-content, .form-footer-content)

## Ví dụ Test Content:

### **Header Example:**
```html
<h2>🔥 Important Notice</h2>
<p><strong>Please read carefully before filling out this form.</strong></p>
<ul>
  <li>All fields marked with * are required</li>
  <li>Make sure all information is accurate</li>
</ul>
```

### **Footer Example:**
```html
<p><em>By submitting this form, you agree to our terms and conditions.</em></p>
<p>For questions, contact: <a href="mailto:support@example.com">support@example.com</a></p>
```

---

**Tóm tắt:** Header/Footer đã được hoàn toàn implement với WordPress Editor trong admin và hiển thị đúng trên frontend với styling đẹp mắt.
