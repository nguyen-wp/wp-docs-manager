<?php
/**
 * Fix LIFT Forms JSON Data
 * Tool để fix dữ liệu JSON bị corrupt trong database
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

function fix_lift_forms_json_data() {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    
    echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
    echo "<h1>🔧 LIFT Forms JSON Data Fixer</h1>";
    
    // Get all forms
    $forms = $wpdb->get_results("SELECT id, name, form_fields FROM $forms_table");
    
    if (empty($forms)) {
        echo "<p>No forms found in database.</p>";
        echo "</div>";
        return;
    }
    
    echo "<h2>Checking " . count($forms) . " forms...</h2>";
    
    $fixed_count = 0;
    $broken_count = 0;
    
    foreach ($forms as $form) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>Form #{$form->id}: " . esc_html($form->name) . "</h3>";
        
        if (empty($form->form_fields)) {
            echo "<p style='color: #666;'>✅ No form_fields data (OK)</p>";
        } else {
            // Test current JSON
            $test_decode = json_decode($form->form_fields, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p style='color: green;'>✅ JSON is valid (" . count($test_decode) . " fields)</p>";
            } else {
                $broken_count++;
                echo "<p style='color: red;'>❌ JSON Error: " . json_last_error_msg() . "</p>";
                echo "<p><strong>Raw data:</strong> <code>" . esc_html(substr($form->form_fields, 0, 100)) . "...</code></p>";
                
                // Try to fix
                $fixed_json = $form->form_fields;
                
                // Clean steps
                $fixed_json = trim($fixed_json);
                $fixed_json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fixed_json); // Remove control chars
                $fixed_json = preg_replace('/,\s*}/', '}', $fixed_json); // Remove trailing commas before }
                $fixed_json = preg_replace('/,\s*]/', ']', $fixed_json); // Remove trailing commas before ]
                
                // Test fixed version
                $test_fixed = json_decode($fixed_json, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Update database
                    $result = $wpdb->update(
                        $forms_table,
                        array('form_fields' => $fixed_json),
                        array('id' => $form->id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($result !== false) {
                        $fixed_count++;
                        echo "<p style='color: green;'>✅ Fixed and updated! (" . count($test_fixed) . " fields)</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Failed to update database</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Could not fix JSON: " . json_last_error_msg() . "</p>";
                    
                    // Last resort - set to empty array
                    $result = $wpdb->update(
                        $forms_table,
                        array('form_fields' => '[]'),
                        array('id' => $form->id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($result !== false) {
                        $fixed_count++;
                        echo "<p style='color: orange;'>⚠️ Set to empty array (you'll need to rebuild this form)</p>";
                    }
                }
            }
        }
        
        echo "</div>";
    }
    
    // Summary
    echo "<div style='background: #f0f8ff; border: 1px solid #0073aa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>📊 Summary</h2>";
    echo "<ul>";
    echo "<li><strong>Total forms checked:</strong> " . count($forms) . "</li>";
    echo "<li><strong>Broken JSON found:</strong> " . $broken_count . "</li>";
    echo "<li><strong>Forms fixed:</strong> " . $fixed_count . "</li>";
    echo "</ul>";
    
    if ($fixed_count > 0) {
        echo "<p style='color: green;'><strong>✅ Operation completed! Fixed forms should now load properly.</strong></p>";
    } else if ($broken_count === 0) {
        echo "<p style='color: green;'><strong>✅ All forms have valid JSON data!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Some forms could not be fixed automatically.</strong></p>";
    }
    echo "</div>";
    
    echo "</div>";
}

// Run the fixer if accessed directly
if (isset($_GET['fix_lift_forms_json'])) {
    fix_lift_forms_json_data();
    exit;
}

// Add admin notice with link to run fixer
add_action('admin_notices', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'lift-forms') !== false) {
        $fix_url = add_query_arg('fix_lift_forms_json', '1', admin_url('admin.php?page=lift-forms'));
        echo '<div class="notice notice-info">';
        echo '<p><strong>LIFT Forms JSON Fixer:</strong> ';
        echo '<a href="' . esc_url($fix_url) . '" class="button">Check & Fix JSON Data</a> ';
        echo '(Use this if you encounter JSON parsing errors)</p>';
        echo '</div>';
    }
});
?>
