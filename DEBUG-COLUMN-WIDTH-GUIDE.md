# 🔍 DEBUG GUIDE - Column Width Not Working

## Issue
Frontend vẫn chia đều các cột mặc dù đã set giá trị khác nhau trong form builder.

## Quick Debug Steps

### 1. **Check Form Data in Browser**
Truy cập frontend form với debug parameter:
```
/document-form/13/29/?debug_form_data=1
```

Sau đó **View Source** và tìm các comment debug:
```html
<!-- DEBUG: Raw form data -->
<!-- DEBUG: Parsed data structure -->
<!-- DEBUG: Found layout.rows structure -->
<!-- DEBUG: Column 0 width: 0.33 -->
<!-- DEBUG: Column 1 width: 0.67 -->
```

### 2. **Check WordPress Database Debug**
Đặt file `wp-debug-form-data.php` vào WordPress root và truy cập:
```
yoursite.com/wp-debug-form-data.php?form_id=29
```

### 3. **Possible Issues & Solutions**

#### ❌ Issue 1: No width data in database
**Symptoms:**
```html
<!-- DEBUG: Column 0 width: NO WIDTH -->
<!-- DEBUG: Column 1 width: NO WIDTH -->
```

**Solution:** Form builder không save width data
- Check form builder save function
- Verify form builder UI is working
- Test creating new form with custom widths

#### ❌ Issue 2: Wrong data structure  
**Symptoms:**
```html
<!-- DEBUG: Using legacy/other structure -->
```

**Solution:** Form uses old format
- Convert form to new layout.rows.columns structure
- Or add legacy format support

#### ❌ Issue 3: Width data exists but not applied
**Symptoms:**
```html
<!-- DEBUG: Column 0 - Raw width: 0.33 Final width: 0.5 -->
```

**Solution:** Logic bug in width calculation
- Check parsing logic
- Verify numeric conversion

#### ❌ Issue 4: CSS overriding flex values
**Symptoms:** Debug shows correct flex values but visual is equal
- Check CSS cascade
- Look for !important rules
- Verify flexbox container setup

### 4. **Test with Simple Form**

Create new form with just 2 fields:
- Set first column to 25% (0.25)
- Set second column to 75% (0.75)
- Save and test frontend

### 5. **Remove Debug Code After Testing**

Once issue is found, remove debug output:
```php
// Remove all lines with:
if (isset($_GET['debug_form_data'])) {
    echo "<!-- DEBUG: ... -->";
}
```

## Expected Debug Output (Working)

```html
<!-- DEBUG: Found layout.rows structure -->
<!-- DEBUG: Column 0 width: 0.33 -->
<!-- DEBUG: Column 1 width: 0.67 -->
<!-- DEBUG: Column 0 - Raw width: 0.33 Final width: 0.33 -->
<!-- DEBUG: Column 1 - Raw width: 0.67 Final width: 0.67 -->
```

And in rendered HTML:
```html
<div class="form-column" style="flex: 0.33 1 0%; position: relative;">
<div class="form-column" style="flex: 0.67 1 0%; position: relative;">
```

## Next Steps

1. **Run debug** with `?debug_form_data=1`
2. **Check debug output** in browser source
3. **Identify which issue** from list above
4. **Apply appropriate solution**
5. **Test and verify fix**
6. **Remove debug code**
