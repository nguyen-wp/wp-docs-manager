# LIFT Forms - Column Width Saving Fix

## Vấn đề được báo cáo
Column-actions trong form builder không được lưu khi user thay đổi width của columns.

## Nguyên nhân
1. Hàm `changeColumnWidth()` chỉ thay đổi CSS mà không cập nhật data structure
2. Không có mechanism để mark form là modified khi column width thay đổi
3. Column width selector không được set đúng giá trị khi load form từ database
4. Thiếu auto-save khi column settings thay đổi

## Các sửa chữa đã thực hiện

### 1. Cập nhật `changeColumnWidth()` function
**File:** `assets/js/form-builder-bpmn.js`
**Dòng:** ~2498

**Trước:**
```javascript
function changeColumnWidth(columnId, flexValue) {
    const column = $(`.form-column[data-column-id="${columnId}"]`);
    column.css('flex', flexValue);
}
```

**Sau:**
```javascript
function changeColumnWidth(columnId, flexValue) {
    const column = $(`.form-column[data-column-id="${columnId}"]`);
    column.css('flex', flexValue);
    
    // Also update the column width selector to reflect the change
    column.find('.column-width-selector').val(flexValue);
    
    // Mark form as modified to enable save
    markFormAsModified();
    
    // Optional: Auto-save after a short delay
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    autoSaveTimeout = setTimeout(function() {
        saveForm(true); // Silent save
    }, 2000);
}
```

### 2. Thêm biến global và hàm `markFormAsModified()`
**File:** `assets/js/form-builder-bpmn.js`
**Dòng:** ~8-15

**Thêm:**
```javascript
let autoSaveTimeout = null;
let formModified = false;

function markFormAsModified() {
    formModified = true;
    // Change save button text to indicate unsaved changes
    const saveBtn = $('#save-form');
    if (saveBtn.length && !saveBtn.hasClass('saving')) {
        saveBtn.text('Save Changes *');
    }
}
```

### 3. Cập nhật `saveColumnSettings()` function
**File:** `assets/js/form-builder-bpmn.js`
**Dòng:** ~2536

**Thêm auto-save logic:**
```javascript
// Mark form as modified and trigger save
markFormAsModified();

// Auto-save after changes
if (autoSaveTimeout) {
    clearTimeout(autoSaveTimeout);
}
autoSaveTimeout = setTimeout(function() {
    saveForm(true); // Silent save
}, 1000);
```

### 4. Reset form modified state khi save thành công
**File:** `assets/js/form-builder-bpmn.js`
**Dòng:** ~1752

**Thêm:**
```javascript
if (response.success) {
    // Reset form modified state
    formModified = false;
    $('#save-form').text('Save Form');
    
    // ... existing code
}
```

### 5. Sửa tất cả column width selectors
**File:** `assets/js/form-builder-bpmn.js`

**Cập nhật tất cả các chỗ render column selector để:**
- Có `selected` attribute đúng cho giá trị hiện tại
- Sử dụng cùng options values tutôi tụ nhất
- Remove các values không cần thiết như `col-1`, `col-2`, etc.

### 6. Cập nhật `loadLayout()` function
**File:** `assets/js/form-builder-bpmn.js`
**Dòng:** ~1942

**Đảm bảo khi load layout, width selector được set đúng:**
```javascript
<option value="1" ${columnWidth == '1' ? 'selected' : ''}>Auto</option>
<option value="0.16" ${columnWidth == '0.16' ? 'selected' : ''}>16.67% (1/6)</option>
// ... tất cả options với selected logic
```

### 7. Thêm event handlers cho column settings modal
**File:** `assets/js/form-builder-bpmn.js`
**Dòng:** ~1670

**Thêm các handlers để quản lý modal:**
```javascript
// Column settings modal handlers
$(document).on('click', '#save-column-settings', function() {
    saveColumnSettings();
});

$(document).on('click', '.column-settings-close, #column-settings-modal .modal-close', function() {
    $('#column-settings-modal').hide();
});
```

## Kết quả
✅ Column width changes được lưu vào database
✅ Form được mark là modified khi column width thay đổi  
✅ Auto-save after 2 giây khi user change column width
✅ Column width selector hiển thị đúng giá trị khi load form
✅ Column settings modal hoạt động đúng
✅ Save button text changes để indicate unsaved changes

## Test Files
- `test-column-width.html` - Test script để verify functionality
- Có thể test trực tiếp trong WordPress admin form builder

## Cách test
1. Tạo hoặc edit một form trong LIFT Forms
2. Thay đổi column width bằng dropdown selector  
3. Kiểm tra form có được mark là modified (Save button text changes)
4. Save form và reload page
5. Verify column width được maintain đúng

## Technical Notes
- `buildLayoutStructure()` function đã đọc width từ CSS `columnElement.css('flex')`
- Column width được lưu trong JSON structure: `{id, width, fields}`  
- Auto-save timeout được set để avoid quá nhiều save requests
- Form modified state được reset sau khi save thành công
