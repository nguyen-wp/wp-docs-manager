<?php
add_action('wp_ajax_lift_forms_save', 'emergency_json_fixer', 5);

function emergency_json_fixer() {
    // Only run if fields parameter exists
    if (!isset($_POST['fields'])) {
        return;
    }
    
    $fields = $_POST['fields'];
    
    // Try various JSON fixes
    $fixed_fields = emergency_fix_json($fields);
    
    if ($fixed_fields !== $fields) {
        $_POST['fields'] = $fixed_fields;
    }
}

function emergency_fix_json($json_string) {
    if (empty($json_string)) {
        return $json_string;
    }
    
    // Remove any URL encoding
    $json_string = urldecode($json_string);
    
    // Try to handle common FormData issues
    if (strpos($json_string, 'FormData') !== false) {
        return '[]'; // Return empty array as fallback
    }
    
    // Handle escaped quotes
    $json_string = stripslashes($json_string);
    
    // Fix common JavaScript object notation issues
    // Convert single quotes to double quotes
    $json_string = str_replace("'", '"', $json_string);
    
    // Fix unquoted property names
    $json_string = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $json_string);
    
    // Fix trailing commas
    $json_string = preg_replace('/,\s*([}\]])/', '$1', $json_string);
    
    // Remove any remaining control characters
    $json_string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $json_string);
    
    // Test if it's valid now
    $test_decode = json_decode($json_string, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $json_string;
    }
    
    // If still invalid, try to extract field data manually
    
    // Look for field patterns in the string
    $manual_fields = extract_fields_manually($json_string);
    if (!empty($manual_fields)) {
        return json_encode($manual_fields);
    }
    
    // Last resort - return a valid empty array
    return '[]';
}

function extract_fields_manually($string) {
    $fields = [];
    
    // Try to find field data using regex patterns
    // Look for id patterns
    if (preg_match_all('/field_\d+/', $string, $matches)) {
        foreach ($matches[0] as $i => $field_id) {
            $fields[] = [
                'id' => $field_id,
                'name' => $field_id,
                'type' => 'text',
                'label' => 'Field ' . ($i + 1),
                'placeholder' => '',
                'required' => false,
                'description' => '',
                'order' => $i
            ];
        }
    }
    
    return $fields;
}

// Add manual field recovery menu
add_action('admin_menu', 'emergency_json_menu');

function emergency_json_menu() {
    add_submenu_page(
        null,
        'Emergency JSON Fixer',
        'Emergency JSON Fixer',
        'manage_options',
        'emergency-json-fixer',
        'emergency_json_page'
    );
}

function emergency_json_page() {
    if (isset($_POST['test_json'])) {
        $test_input = $_POST['json_input'];
        $fixed_json = emergency_fix_json($test_input);
        
        echo '<div class="notice notice-info">';
        echo '<h3>Original JSON:</h3>';
        echo '<pre>' . esc_html($test_input) . '</pre>';
        echo '<h3>Fixed JSON:</h3>';
        echo '<pre>' . esc_html($fixed_json) . '</pre>';
        
        // Test if fixed JSON is valid
        $decoded = json_decode($fixed_json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<p style="color: green;">✅ Fixed JSON is valid!</p>';
        } else {
            echo '<p style="color: red;">❌ Fixed JSON is still invalid: ' . json_last_error_msg() . '</p>';
        }
        echo '</div>';
    }
    
    ?>
    <div class="wrap">
        <h1>🚨 Emergency JSON Fixer</h1>
        
        <div class="card">
            <h2>Test JSON Fixing</h2>
            <form method="post">
                <p>Paste problematic JSON here:</p>
                <textarea name="json_input" rows="10" cols="80" placeholder='Paste your broken JSON here...'><?php echo isset($_POST['json_input']) ? esc_textarea($_POST['json_input']) : ''; ?></textarea><br><br>
                <input type="submit" name="test_json" value="🔧 Fix JSON" class="button button-primary">
            </form>
        </div>
        
        <div class="card">
            <h2>Emergency Actions</h2>
            <p>If Form Builder is completely broken:</p>
            <ol>
                <li>Go to <a href="<?php echo admin_url('admin.php?page=lift-save-debug'); ?>">Basic Debug Tool</a></li>
                <li>Try "Test Manual Save" to verify database works</li>
                <li>Try "Test AJAX Save" to test the handler</li>
                <li>Check browser console for JavaScript errors</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Common JSON Issues</h2>
            <ul>
                <li><strong>Trailing commas:</strong> [1,2,] → [1,2]</li>
                <li><strong>Single quotes:</strong> {'key':'value'} → {"key":"value"}</li>
                <li><strong>Unquoted keys:</strong> {key:"value"} → {"key":"value"}</li>
                <li><strong>FormData objects:</strong> Cannot be JSON stringified</li>
            </ul>
        </div>
    </div>
    <?php
}