# Tóm tắt: Files JSON Form Template và Tools

## Files đã được tạo

### 1. Form Templates (JSON)

#### `data/sample-form-template.json`
- **Mô tả**: Form template hoàn chỉnh cho "Onsite Contractor Information"
- **Nội dung**: Form đầy đủ với 15 rows, 20 columns, 37 fields
- **Bao gồm**: 
  - Thông tin công ty
  - Chi tiết dự án
  - Chứng chỉ & trình độ
  - Kinh nghiệm & tham khảo
  - Thông tin bổ sung
  - Tài liệu yêu cầu
  - Chữ ký & ủy quyền

#### `data/simple-contact-form.json`
- **Mô tả**: Form liên hệ đơn giản làm ví dụ
- **Nội dung**: 2 rows, 3 columns, 6 fields cơ bản
- **Phù hợp**: Học cách sử dụng và test

### 2. Import/Export Tools

#### `import-form.php`
- **Chức năng**: Import form từ JSON vào database
- **Sử dụng**: 
  - Web interface: Truy cập `/import-form.php`
  - Command line: `php import-form.php [json-file] [form-name] [description]`
- **Tính năng**:
  - Validate JSON structure
  - Insert vào database
  - List available templates
  - Error handling

#### `validate-form-json.php`
- **Chức năng**: Validate cấu trúc JSON form
- **Sử dụng**: `php validate-form-json.php <json-file>`
- **Kiểm tra**:
  - JSON syntax
  - Required properties
  - Field types hỗ trợ
  - Cross-references
  - Duplicated IDs
  - Statistics

### 3. Documentation

#### `data/README-Form-Import.md`
- **Nội dung**: Hướng dẫn chi tiết về:
  - Cấu trúc JSON form
  - Các loại fields hỗ trợ
  - Cách import form
  - Customization
  - Troubleshooting

## Cách sử dụng nhanh

### Import form mẫu:
```bash
# Validate trước
php validate-form-json.php data/sample-form-template.json

# Import vào database
php import-form.php data/sample-form-template.json "My Contractor Form" "Form description"
```

### Hoặc sử dụng web interface:
1. Truy cập: `yoursite.com/wp-content/plugins/wp-docs-manager/import-form.php`
2. Chọn file JSON template
3. Nhập tên và mô tả form
4. Click "Import Form"

## Cấu trúc JSON Form

### Basic Structure:
```json
{
  "layout": {
    "rows": [
      {
        "id": "row-id",
        "columns": [
          {
            "id": "col-id",
            "width": "0.5",
            "fields": [...]
          }
        ]
      }
    ]
  },
  "fields": [...]
}
```

### Field Types hỗ trợ:
- `text` - Single line text input
- `textarea` - Multi-line text input
- `email` - Email input với validation
- `number` - Number input
- `date` - Date picker
- `select` - Dropdown select
- `radio` - Radio buttons (single choice)
- `checkbox` - Checkboxes (multiple choice)
- `file` - File upload
- `signature` - Digital signature
- `header` - Section header (display only)
- `paragraph` - Text paragraph (display only)

### Width Values:
- `"1"` = 100% width (full column)
- `"0.5"` = 50% width (half column)
- `"0.33"` = 33.33% width (1/3 column)
- `"0.25"` = 25% width (1/4 column)
- `"0.66"` = 66.67% width (2/3 column)
- `"0.75"` = 75% width (3/4 column)

## Customization

### Thêm field mới:
```json
{
  "id": "field-unique-id",
  "type": "text",
  "name": "field_name",
  "label": "Field Label",
  "placeholder": "Enter text...",
  "required": true
}
```

### Tạo dropdown với options:
```json
{
  "id": "field-dropdown",
  "type": "select",
  "name": "dropdown_field",
  "label": "Choose Option",
  "required": true,
  "options": ["Option 1", "Option 2", "Option 3"]
}
```

### Tạo checkbox group:
```json
{
  "id": "field-checkboxes",
  "type": "checkbox",
  "name": "checkbox_field",
  "label": "Select Multiple",
  "required": false,
  "options": ["Choice 1", "Choice 2", "Choice 3"]
}
```

## Troubleshooting

### JSON Syntax Errors:
- Sử dụng `validate-form-json.php` để check
- Dùng JSON validator online
- Kiểm tra dấu phẩy cuối, quotes

### Form không load:
- Check browser console cho JavaScript errors
- Verify database có data
- Ensure Form Builder đang hoạt động

### Fields không hiển thị:
- Validate field types có hỗ trợ
- Check required properties
- Verify layout structure

## Backup & Export

### Export form hiện có:
```javascript
// Trong Form Builder console
const formData = window.formBuilder.getFormData();
const layoutData = buildLayoutStructure();
const exportData = {
    layout: layoutData,
    fields: formData
};
console.log(JSON.stringify(exportData, null, 2));
```

### Backup database:
```sql
SELECT form_fields FROM wp_lift_forms WHERE id = YOUR_FORM_ID;
```

## Support

Nếu gặp vấn đề:
1. Chạy validation script trước
2. Check browser console cho errors
3. Verify database connection
4. Test với simple form trước

Form templates này tương thích với LIFT Documents System Form Builder và có thể được customize theo nhu cầu cụ thể của dự án.
