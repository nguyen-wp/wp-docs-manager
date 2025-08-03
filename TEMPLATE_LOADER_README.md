# Load from Template Feature

## Tính năng "Load from Template" cho LIFT Forms

Tính năng này cho phép bạn nhanh chóng tạo form mới từ các template JSON đã có sẵn.

## Cách sử dụng

### 1. Truy cập trang Create New Form
- Vào `LIFT Forms > Add New Form`
- Bạn sẽ thấy nút "Load from Template" bên cạnh nút "Save Form"

### 2. Load Template
1. Click vào nút "**Load from Template**"
2. Chọn file JSON template từ máy tính của bạn
3. Chọn có muốn sử dụng tên từ template hay không
4. Click "**Load Template**"

### 3. Template sẽ được load
- Form name và description sẽ được điền tự động
- Header và Footer content sẽ được load vào editors
- Form fields và layout sẽ được tạo trong form builder
- Bạn có thể tiếp tục chỉnh sửa form theo ý muốn

## Template Format

Template phải là file JSON với cấu trúc sau:

```json
{
  "name": "Form Name",
  "description": "Form Description",
  "layout": {
    "rows": [...]
  },
  "fields": [...],
  "form_header": "HTML content",
  "form_footer": "HTML content"
}
```

## Sample Templates

Plugin bao gồm 2 template mẫu trong thư mục `templates/`:

1. **sample-contact-form.json** - Form liên hệ hoàn chỉnh với header/footer
2. **simple-registration-form.json** - Form đăng ký đơn giản

## Tạo Template của riêng bạn

1. Tạo một form trong LIFT Forms
2. Export form đó ra file JSON
3. File JSON này có thể được sử dụng làm template cho các form mới

## Lưu ý

- Tính năng chỉ khả dụng khi tạo form mới (không hiển thị khi edit form)
- Template phải có đúng format JSON và chứa các field bắt buộc
- Sau khi load template, bạn vẫn có thể chỉnh sửa tùy ý
- Form sẽ được đặt tên với suffix "(From Template)" nếu chọn sử dụng tên từ template

## Validation

Hệ thống sẽ validate template để đảm bảo:
- File có đúng format JSON
- Có đầy đủ các field bắt buộc: name, layout, fields
- Layout có cấu trúc đúng với mảng rows
- Fields là một mảng

Nếu template không hợp lệ, sẽ có thông báo lỗi chi tiết.
