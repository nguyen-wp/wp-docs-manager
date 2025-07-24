<?php
/**
 * Test JSON Parsing Fixes for LIFT Forms
 * Verify that the enhanced JSON parsing works correctly
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

function test_lift_forms_json_parsing() {
    echo "<div style='padding: 20px; font-family: Arial, sans-serif; max-width: 1200px;'>";
    echo "<h1>🧪 LIFT Forms JSON Parsing Test</h1>";
    
    // Test cases - various corrupted JSON data
    $test_cases = array(
        'valid_json' => '[{"type":"text","label":"Name","required":true}]',
        'bom_prefix' => "\xEF\xBB\xBF" . '[{"type":"email","label":"Email"}]',
        'trailing_comma_object' => '[{"type":"text","label":"Name","required":true,}]',
        'trailing_comma_array' => '[{"type":"text","label":"Name"},]',
        'control_chars' => '[{"type":"text","label":"Name\x00\x01\x02"}]',
        'mixed_issues' => "\xEF\xBB\xBF" . '[{"type":"text","label":"Name","required":true,},]',
        'empty_string' => '',
        'null_value' => null,
        'malformed_quotes' => '[{"type":"text,"label":"Name"}]',
        'unescaped_chars' => '[{"type":"text","label":"Name\nwith\tspecial"}]'
    );
    
    echo "<h2>Testing safeJsonParse Function</h2>";
    
    foreach ($test_cases as $test_name => $test_data) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>Test Case: " . esc_html($test_name) . "</h3>";
        
        // Show raw data (truncated for display)
        $display_data = $test_data === null ? 'NULL' : $test_data;
        $display_data = strlen($display_data) > 100 ? substr($display_data, 0, 100) . '...' : $display_data;
        echo "<p><strong>Input:</strong> <code>" . esc_html($display_data) . "</code></p>";
        
        // Test with standard json_decode
        $standard_result = json_decode($test_data, true);
        $standard_error = json_last_error_msg();
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ Standard JSON: Success (" . count($standard_result) . " items)</p>";
        } else {
            echo "<p style='color: red;'>❌ Standard JSON: " . esc_html($standard_error) . "</p>";
        }
        
        // Test with our safeJsonParse (simulate the function)
        $safe_result = test_safe_json_parse($test_data);
        
        if ($safe_result !== false) {
            echo "<p style='color: green;'>✅ Safe JSON Parse: Success (" . count($safe_result) . " items)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Safe JSON Parse: Fallback to empty array</p>";
        }
        
        echo "</div>";
    }
    
    // Test database integration
    echo "<h2>Testing Database Integration</h2>";
    test_database_json_handling();
    
    echo "</div>";
}

function test_safe_json_parse($json_string) {
    // Replicate the safeJsonParse logic from JavaScript
    if (empty($json_string)) {
        return array();
    }
    
    // Clean the JSON string
    $cleaned = trim($json_string);
    
    // Remove BOM if present
    if (substr($cleaned, 0, 3) === "\xEF\xBB\xBF") {
        $cleaned = substr($cleaned, 3);
    }
    
    // Remove control characters
    $cleaned = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cleaned);
    
    // Fix trailing commas
    $cleaned = preg_replace('/,\s*}/', '}', $cleaned);
    $cleaned = preg_replace('/,\s*]/', ']', $cleaned);
    
    // Try to decode
    $result = json_decode($cleaned, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
        return $result;
    }
    
    return false; // Indicates fallback needed
}

function test_database_json_handling() {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table;
    
    if (!$table_exists) {
        echo "<p style='color: orange;'>⚠️ LIFT Forms table does not exist yet. Run plugin activation first.</p>";
        return;
    }
    
    // Get sample forms
    $forms = $wpdb->get_results("SELECT id, name, form_fields FROM $forms_table LIMIT 3");
    
    if (empty($forms)) {
        echo "<p>No forms found in database to test.</p>";
        return;
    }
    
    echo "<h3>Sample Forms in Database</h3>";
    
    foreach ($forms as $form) {
        echo "<div style='border-left: 4px solid #0073aa; padding-left: 15px; margin: 10px 0;'>";
        echo "<h4>Form #{$form->id}: " . esc_html($form->name) . "</h4>";
        
        if (empty($form->form_fields)) {
            echo "<p style='color: #666;'>No form fields data</p>";
        } else {
            $test_result = json_decode($form->form_fields, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p style='color: green;'>✅ Valid JSON (" . count($test_result) . " fields)</p>";
            } else {
                echo "<p style='color: red;'>❌ JSON Error: " . json_last_error_msg() . "</p>";
            }
        }
        
        echo "</div>";
    }
}

// Add menu item to test
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'lift-forms',
            'JSON Test',
            'JSON Test',
            'manage_options',
            'lift-forms-json-test',
            function() {
                test_lift_forms_json_parsing();
            }
        );
    }
}, 20);

// Run test if accessed directly
if (isset($_GET['test_lift_forms_json'])) {
    test_lift_forms_json_parsing();
    exit;
}
?>
