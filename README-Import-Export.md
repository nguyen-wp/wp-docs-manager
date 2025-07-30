# LIFT Forms Import/Export Feature

## Tổng quan
Tính năng Import/Export của LIFT Forms cho phép bạn:
- **Import**: Nhập forms từ file JSON vào hệ thống
- **Export**: Xuất forms ra file JSON để backup hoặc chuyển đổi

## Cách sử dụng Import

### 1. Truy cập trang LIFT Forms
- Vào WordPress Admin → LIFT Forms
- Bạn sẽ thấy các button "Import Form" và "Export All Forms" ở đầu trang

### 2. Import một form
1. Click button **"Import Form"**
2. Chọn file JSON hợp lệ (đã được export từ LIFT Forms)
3. Tùy chọn: Nhập tên mới cho form (nếu không nhập sẽ dùng tên gốc)
4. Click **"Import Form"** để hoàn tất

### 3. File JSON hợp lệ
File JSON cần có cấu trúc:
```json
{
  "name": "Tên form",
  "description": "Mô tả form",
  "layout": { ... },
  "fields": { ... }
}
```

## Cách sử dụng Export

### 1. Export một form cụ thể
- Ở bảng danh sách forms, click button **"Export"** bên cạnh form muốn xuất
- File JSON sẽ được download tự động

### 2. Export tất cả forms
- Click button **"Export All Forms"** ở đầu trang
- File backup chứa tất cả forms sẽ được download

## File mẫu
Trong thư mục plugin có file `sample-import-contact-form.json` để test import.

## Lưu ý quan trọng

### ✅ Điều nên làm:
- Chỉ import file JSON đã được export từ LIFT Forms
- Backup forms trước khi import
- Kiểm tra tên form để tránh trùng lặp

### ❌ Điều không nên làm:
- Import file JSON từ nguồn không rõ
- Import file backup nhiều forms (chỉ import từng form một)
- Sửa đổi file JSON export trừ khi hiểu rõ cấu trúc

## Cấu trúc File Export

### Single Form Export
```json
{
  "name": "Form Name",
  "description": "Form Description", 
  "layout": {
    "rows": [...]
  },
  "fields": {
    "field_id": {
      "type": "text",
      "label": "Field Label",
      ...
    }
  },
  "export_info": {
    "exported_at": "2024-01-15 10:30:00",
    "exported_by": "User Name",
    "plugin_version": "1.0.0"
  }
}
```

### All Forms Backup
```json
{
  "forms": [
    {
      "name": "Form 1",
      "description": "...",
      "layout": {...},
      "fields": {...}
    },
    {
      "name": "Form 2", 
      "description": "...",
      "layout": {...},
      "fields": {...}
    }
  ],
  "export_info": {
    "exported_at": "2024-01-15 10:30:00",
    "total_forms": 2,
    ...
  }
}
```

## Xử lý lỗi

### Lỗi thường gặp:
- **"Invalid JSON format"**: File không đúng định dạng JSON
- **"Missing required field"**: Thiếu trường bắt buộc (name, layout, fields)
- **"Form name already exists"**: Tên form đã tồn tại (hệ thống sẽ tự động thêm timestamp)

### Giải pháp:
- Kiểm tra file JSON bằng JSON validator
- Đảm bảo file được export từ LIFT Forms
- Thử đổi tên form khi import

## Bảo mật

- Chỉ admin có quyền import/export
- Tất cả dữ liệu được validate trước khi import
- Nonce được sử dụng để chống CSRF
- File upload được kiểm tra định dạng

## Support
Nếu gặp vấn đề với import/export, hãy kiểm tra:
1. Quyền admin
2. Định dạng file JSON
3. Cấu trúc dữ liệu
4. Console browser để xem lỗi JavaScript
