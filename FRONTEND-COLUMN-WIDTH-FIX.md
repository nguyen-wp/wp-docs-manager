# Frontend Column Width Fix

## Vấn đề
Trong form builder `/wp-admin/admin.php?page=lift-forms-builder&id=29`, người dùng có thể thiết lập độ rộng cột khác nhau (column width) và nó hiển thị tốt trong builder. Tuy nhiên, ngoài frontend `/document-form/13/29/`, các column width này không được áp dụng, tất cả columns hiển thị với độ rộng bằng nhau.

## Nguyên nhân
1. **Conflict trong rendering system**: Form builder sử dụng flexbox với values như `0.33`, `0.66`, nhưng frontend sử dụng CSS Grid với classes như `col-1`, `col-2`, `col-3`.

2. **Data structure mismatch**: 
   - Form builder lưu data với structure: `{layout: {rows: [{columns: [{width, fields}]}]}, fields: []}`
   - Frontend parsing đúng structure này nhưng không sử dụng width values

3. **CSS conflicts**: Responsive CSS override custom flex values với `flex: none !important`

## Các sửa chữa thực hiện

### 1. Sửa `render_structured_layout()` trong `class-lift-docs-frontend-login.php`

**Thay đổi**: Thêm logic để sử dụng custom width values từ form builder

```php
// Trước:
$column_width = $this->calculate_column_width(count($columns), $col_fields);
echo '<div class="form-column ' . esc_attr($column_width) . '" data-column="' . esc_attr($col_index) . '">';

// Sau:
// Check if any field in this column has a custom width value
$custom_width = null;
foreach ($col_fields as $field) {
    if (isset($field['width']) && is_numeric($field['width'])) {
        $custom_width = $field['width'];
        break;
    }
}

if ($custom_width) {
    $column_style = 'style="flex: ' . esc_attr($custom_width) . ';"';
    $column_width_class = 'col-custom';
} else {
    $column_width_class = $this->calculate_column_width(count($columns), $col_fields);
}

echo '<div class="form-column ' . esc_attr($column_width_class) . '" data-column="' . esc_attr($col_index) . '" ' . $column_style . '>';
```

### 2. Thêm detection cho custom width rows

```php
// Check if this row has custom column widths
$has_custom_widths = false;
foreach ($row_fields as $field) {
    if (isset($field['width']) && is_numeric($field['width'])) {
        $has_custom_widths = true;
        break;
    }
}

$row_class = $has_custom_widths ? 'form-row custom-widths' : 'form-row';
echo '<div class="' . $row_class . '" data-row="' . esc_attr($row_index) . '">';
```

### 3. Cập nhật CSS trong `secure-frontend.css`

**Thêm CSS cho custom width layout**:
```css
/* Use flexbox for custom column widths */
.form-row.custom-widths {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

/* Custom column width (uses flex value from inline style) */
.form-column.col-custom {
    flex: 1; /* fallback */
}
```

**Sửa responsive CSS**:
```css
/* Mobile: Only force mobile layout for grid-based columns, not custom flex columns */
.form-column:not(.col-custom) {
    flex: none !important;
    width: 100% !important;
}

/* Custom columns stack on mobile but maintain relative sizing */
.form-row.custom-widths {
    flex-direction: column;
}
```

## Kết quả
- ✅ Column widths từ form builder được áp dụng đúng trên frontend
- ✅ Responsive design vẫn hoạt động (stack trên mobile)
- ✅ Backward compatibility với forms không có custom widths
- ✅ Performance không bị ảnh hưởng

## Test cases
1. **Form với custom column widths**: 33%/67%, 25%/50%/25%
2. **Form không có custom widths**: Fallback về grid layout
3. **Responsive**: Stack columns trên mobile
4. **Mixed layouts**: Một số rows có custom widths, một số không

## Files modified
- `includes/class-lift-docs-frontend-login.php`
- `assets/css/secure-frontend.css`

## Files created for testing
- `debug-column-width-frontend.php`
- `test-frontend-column-width.html`
