# Hướng dẫn Import Form Template từ JSON

## Tổng quan
File `sample-form-template.json` chứa cấu trúc form hoàn chỉnh với layout row/column và các fields tương ứng với form "Onsite Contractor Information" mà bạn đã tạo.

## Cấu trúc JSON Form

### 1. Cấu trúc tổng thể:
```json
{
  "layout": {
    "rows": [...]  // Định nghĩa layout với rows và columns
  },
  "fields": [...]  // Danh sách tất cả các fields
}
```

### 2. Cấu trúc Row (Hàng):
```json
{
  "id": "row-unique-id",
  "columns": [...]  // Các cột trong hàng này
}
```

### 3. Cấu trúc Column (Cột):
```json
{
  "id": "col-unique-id", 
  "width": "0.5",  // Độ rộng: 1 = 100%, 0.5 = 50%, 0.33 = 33%, etc.
  "fields": [...]  // Các fields trong cột này
}
```

### 4. Cấu trúc Field (Trường):
```json
{
  "id": "field-unique-id",
  "type": "text|textarea|select|radio|checkbox|date|email|number|file|signature|header",
  "name": "field_name",  // Tên field để lưu data
  "label": "Field Label",  // Nhãn hiển thị
  "placeholder": "Enter text...",  // Placeholder (tùy chọn)
  "required": true|false,  // Bắt buộc hay không
  "options": ["Option 1", "Option 2"]  // Cho select, radio, checkbox
}
```

## Các loại Fields hỗ trợ:

1. **text** - Ô nhập text đơn dòng
2. **textarea** - Ô nhập text nhiều dòng  
3. **email** - Ô nhập email (có validation)
4. **number** - Ô nhập số
5. **date** - Chọn ngày
6. **select** - Dropdown select
7. **radio** - Radio buttons (chọn 1)
8. **checkbox** - Checkboxes (chọn nhiều)
9. **file** - Upload file
10. **signature** - Chữ ký điện tử
11. **header** - Tiêu đề/header (không có input)

## Cách Import vào Form Builder:

### Phương pháp 1: Manual Import (Thủ công)
1. Vào WordPress Admin → LIFT Documents → Forms → Form Builder
2. Tạo form mới hoặc edit form hiện có
3. Copy nội dung JSON từ file `sample-form-template.json`
4. Trong Form Builder, mở Browser Developer Tools (F12)
5. Chạy lệnh JavaScript để load data:

```javascript
// Load form data từ JSON
const formData = {JSON_CONTENT_HERE};
if (window.formBuilder) {
    window.formBuilder.setFormData(formData.fields);
    // Load layout nếu có function loadLayout
    if (typeof loadLayout === 'function') {
        loadLayout(formData.layout);
    }
}
```

### Phương pháp 2: Database Import (Khuyến nghị)
1. Truy cập database WordPress
2. Tìm bảng `wp_lift_forms` (hoặc `{prefix}_lift_forms`)
3. Insert hoặc update record với dữ liệu:

```sql
INSERT INTO wp_lift_forms (name, description, form_fields, created_at, updated_at) 
VALUES (
    'Onsite Contractor Information Form',
    'Form for contractor pre-qualification and project details',
    '{JSON_CONTENT_HERE}',
    NOW(),
    NOW()
);
```

### Phương pháp 3: Programmatic Import (Lập trình)
Tạo file PHP import:

```php
<?php
// Load WordPress
require_once('wp-config.php');

// Read JSON file
$json_content = file_get_contents('data/sample-form-template.json');

// Insert into database
global $wpdb;
$result = $wpdb->insert(
    $wpdb->prefix . 'lift_forms',
    array(
        'name' => 'Onsite Contractor Information Form',
        'description' => 'Contractor pre-qualification form',
        'form_fields' => $json_content,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    )
);

if ($result) {
    echo "Form imported successfully!";
} else {
    echo "Error importing form: " . $wpdb->last_error;
}
?>
```

## Tùy chỉnh Template:

### 1. Thay đổi layout:
- Thêm/bớt rows: Thêm object row mới vào mảng `layout.rows`
- Thay đổi số cột: Thêm/bớt objects trong `columns` array
- Điều chỉnh độ rộng cột: Thay đổi giá trị `width` (0.25, 0.33, 0.5, 0.66, 0.75, 1)

### 2. Thêm fields mới:
```json
{
  "id": "field-new-field",
  "type": "text",
  "name": "new_field_name", 
  "label": "New Field Label",
  "placeholder": "Enter value...",
  "required": false
}
```

### 3. Tạo dropdown/select với options:
```json
{
  "id": "field-dropdown",
  "type": "select",
  "name": "dropdown_field",
  "label": "Choose Option",
  "required": true,
  "options": [
    "Option 1",
    "Option 2", 
    "Option 3"
  ]
}
```

### 4. Tạo checkbox groups:
```json
{
  "id": "field-checkboxes",
  "type": "checkbox", 
  "name": "checkbox_field",
  "label": "Select Multiple",
  "required": false,
  "options": [
    "Choice 1",
    "Choice 2",
    "Choice 3"
  ]
}
```

## Troubleshooting:

### 1. JSON Syntax Error:
- Kiểm tra dấu phẩy cuối (trailing commas)
- Đảm bảo tất cả strings được quote bằng dấu "
- Sử dụng JSON validator online để check syntax

### 2. Form không load:
- Kiểm tra cấu trúc layout có đúng không
- Đảm bảo mỗi field có ID unique
- Check browser console cho JavaScript errors

### 3. Fields không hiển thị:
- Verify field type có hỗ trợ không
- Check required properties cho mỗi field type
- Đảm bảo fields nằm trong đúng column structure

## Backup & Export:

### Export form hiện có thành JSON:
1. Vào Form Builder
2. Load form cần export
3. Chạy JavaScript trong console:

```javascript
// Get current form data
const currentData = window.formBuilder.getFormData();
const layoutData = buildLayoutStructure(); // if available

const exportData = {
    layout: layoutData,
    fields: currentData
};

console.log(JSON.stringify(exportData, null, 2));
```

### Backup database:
```sql
SELECT form_fields FROM wp_lift_forms WHERE id = YOUR_FORM_ID;
```

Lưu kết quả vào file .json để backup.

## Lưu ý quan trọng:

1. **ID Fields**: Mỗi field, row, column phải có ID unique
2. **Field Names**: Sử dụng underscore cho field names (VD: `company_name`)
3. **Width Values**: Sử dụng decimal cho width (0.5, 0.33, etc.) hoặc "1" cho full width
4. **Required Fields**: Set `required: true` cho các fields bắt buộc
5. **Options**: Chỉ cần cho `select`, `radio`, `checkbox` types

Template này tạo ra một form contractor information hoàn chỉnh với các sections:
- Contractor Information
- Project Details  
- Certifications & Qualifications
- Experience & References
- Additional Information
- Required Documents
- Signature & Authorization

Bạn có thể customize theo nhu cầu cụ thể của dự án!
