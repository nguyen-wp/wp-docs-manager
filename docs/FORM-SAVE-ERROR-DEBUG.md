# LIFT Forms Save Error Debug Fix

## 🚨 Problem
Form Builder báo lỗi: `{"success":false,"data":"Invalid fields data format: Syntax error"}`

## 🔧 Root Cause Analysis
Vấn đề có thể do:
1. **Circular References** trong field objects
2. **Undefined Values** trong field data  
3. **Non-serializable Properties** (functions, DOM elements)
4. **Corrupted Field Data** khi drag/drop

## ✅ Solution Implemented

### 1. Enhanced JavaScript Data Cleaning (`assets/js/forms-builder.js`)

#### Added `cleanFieldData()` Function
```javascript
cleanFieldData: function(field) {
    const cleaned = {};
    
    // Copy only serializable properties
    const allowedProps = [
        'id', 'name', 'type', 'label', 'placeholder', 'required', 
        'description', 'options', 'min', 'max', 'step', 'rows', 
        'multiple', 'accept', 'content', 'order', 'validation'
    ];
    
    allowedProps.forEach(prop => {
        if (field.hasOwnProperty(prop) && field[prop] !== undefined) {
            // Handle arrays and filter out undefined/null
            if (Array.isArray(field[prop])) {
                cleaned[prop] = field[prop].filter(item => item !== undefined && item !== null);
            } 
            // Test object serializability
            else if (typeof field[prop] === 'object' && field[prop] !== null) {
                try {
                    JSON.stringify(field[prop]);
                    cleaned[prop] = field[prop];
                } catch (e) {
                    console.warn(`Skipping unserializable property ${prop}:`, field[prop]);
                }
            } 
            // Handle primitives
            else {
                cleaned[prop] = field[prop];
            }
        }
    });
    
    return cleaned;
}
```

#### Added `cleanFormData()` Function
```javascript
cleanFormData: function() {
    return {
        fields: this.formData.fields.map(field => this.cleanFieldData(field)),
        settings: this.formData.settings || {}
    };
}
```

#### Enhanced `saveForm()` Function
- ✅ Uses cleaned data before JSON.stringify
- ✅ Client-side JSON validation before sending
- ✅ Comprehensive error logging
- ✅ Graceful error handling with user feedback

### 2. Enhanced PHP Server Validation (`includes/class-lift-forms.php`)

#### Comprehensive Debug Logging
```php
// Enhanced debug logging
error_log('=== LIFT FORMS SAVE DEBUG ===');
error_log('Fields data received: ' . print_r($fields, true));
error_log('Fields type: ' . gettype($fields));
error_log('Fields length: ' . strlen($fields));
error_log('Fields first 100 chars: ' . substr($fields, 0, 100));
error_log('Fields last 100 chars: ' . substr($fields, -100));
```

#### Enhanced JSON Validation
- ✅ Check for common problematic strings (`undefined`, `null`)
- ✅ Step-by-step cleaning with logging
- ✅ BOM removal detection
- ✅ Control character removal
- ✅ Trailing comma fixes
- ✅ Error context reporting (shows exact position of JSON error)

#### Error Context Reporting
```php
// Try to provide more helpful error info
$error_position = null;
preg_match('/position (\d+)/', $json_error, $matches);
if (isset($matches[1])) {
    $error_position = intval($matches[1]);
    $context_start = max(0, $error_position - 20);
    $context_end = min(strlen($fields), $error_position + 20);
    $context = substr($fields, $context_start, $context_end - $context_start);
    error_log('Error context: ' . $context);
}
```

### 3. Version Bump & Cache Busting
- Updated script version to `1.0.8` để clear browser cache
- Force reload của updated JavaScript code

## 🔍 Debugging Process

### Client Side (Browser Console)
```javascript
// Will now show:
=== FORM SAVE DEBUG ===
Original fields: [field objects]
Cleaned fields: [cleaned objects]
Fields type: object
Fields length: 2
JSON stringify successful: [{"id":"field_1",...}]
JSON length: 156
JSON parse test successful: [parsed objects]
```

### Server Side (WordPress Debug Log)
```php
// Will now show:
=== LIFT FORMS SAVE DEBUG ===
Fields data received: [{"id":"field_1",...}]
Fields type: string
Fields length: 156
Fields first 100 chars: [{"id":"field_1","name":"text_1",...
Fields last 100 chars: ...,"required":false}]
```

## 🚀 How to Test

1. **Enable WordPress Debug Logging**:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Clear Browser Cache** (Ctrl+F5 hoặc Cmd+Shift+R)

3. **Test Form Creation**:
   - Add field to form builder
   - Try to save form
   - Check browser console for debug info
   - Check `/wp-content/debug.log` for server debug info

4. **If Still Errors**:
   - Console will show exactly what's wrong
   - Error log will show server-side validation details
   - Use JSON Test tool: LIFT Forms → JSON Test

## 💡 Prevention Features

- **Whitelist Approach**: Only serialize known-safe properties
- **Array Filtering**: Remove undefined/null values from arrays  
- **Object Testing**: Test serializability before including objects
- **Multi-layer Validation**: Client + Server validation
- **Detailed Logging**: Identify exact issue quickly

The form builder should now handle edge cases gracefully and provide clear error messages for debugging! 🎉
