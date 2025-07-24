<?php
/**
 * Fix Form Submit - Sửa lỗi "Invalid fields data format: Syntax error"
 */

if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Form_Submit_Fix {
    
    public function __construct() {
        // Hook vào AJAX handlers để fix data trước khi xử lý
        add_action('wp_ajax_lift_forms_save', array($this, 'fix_save_form_data'), 1);
        add_action('wp_ajax_lift_forms_submit', array($this, 'fix_submit_form_data'), 1);
        
        // Add admin page for testing
        add_action('admin_menu', array($this, 'add_admin_page'));
    }
    
    /**
     * Fix form save data before processing
     */
    public function fix_save_form_data() {
        // Chỉ chạy nếu có POST data
        if (empty($_POST['fields'])) {
            return;
        }
        
        $fields = $_POST['fields'];
        
        // Log original data for debugging
        error_log('LIFT Forms Fix - Original fields data: ' . print_r($fields, true));
        
        // Clean and fix the JSON data
        $cleaned_fields = $this->clean_json_data($fields);
        
        if ($cleaned_fields !== $fields) {
            $_POST['fields'] = $cleaned_fields;
            error_log('LIFT Forms Fix - Cleaned fields data: ' . print_r($cleaned_fields, true));
        }
    }
    
    /**
     * Fix form submit data before processing  
     */
    public function fix_submit_form_data() {
        // Log submit data for debugging
        if (!empty($_POST['form_data'])) {
            error_log('LIFT Forms Fix - Form submit data: ' . print_r($_POST['form_data'], true));
        }
    }
    
    /**
     * Clean JSON data to prevent syntax errors
     */
    private function clean_json_data($json_string) {
        if (empty($json_string)) {
            return $json_string;
        }
        
        // If it's already an array/object, convert to JSON first
        if (is_array($json_string) || is_object($json_string)) {
            try {
                $json_string = json_encode($json_string);
            } catch (Exception $e) {
                error_log('LIFT Forms Fix - Error encoding to JSON: ' . $e->getMessage());
                return '[]';
            }
        }
        
        // Ensure it's a string
        if (!is_string($json_string)) {
            return '[]';
        }
        
        $cleaned = trim($json_string);
        
        // Remove BOM if present
        if (substr($cleaned, 0, 3) === "\xEF\xBB\xBF") {
            $cleaned = substr($cleaned, 3);
        }
        
        // Remove null bytes and other problematic characters
        $cleaned = str_replace("\0", '', $cleaned);
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);
        
        // Fix common JSON issues
        $cleaned = preg_replace('/,\s*}/', '}', $cleaned); // Remove trailing commas before }
        $cleaned = preg_replace('/,\s*]/', ']', $cleaned); // Remove trailing commas before ]
        $cleaned = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $cleaned); // Quote unquoted keys
        
        // Test if valid JSON
        json_decode($cleaned);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('LIFT Forms Fix - JSON still invalid after cleaning: ' . json_last_error_msg());
            // Last resort - return empty array
            return '[]';
        }
        
        return $cleaned;
    }
    
    /**
     * Add admin page for testing fixes
     */
    public function add_admin_page() {
        add_submenu_page(
            'edit.php?post_type=lift_document',
            'Form Fix Tools',
            'Form Fix Tools',
            'manage_options',
            'lift-form-fix-tools',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🔧 Form Fix Tools</h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                <h2>Fix Database Forms JSON</h2>
                <p>Sửa các form có JSON data bị lỗi trong database:</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('fix_forms_json', 'fix_forms_nonce'); ?>
                    <input type="hidden" name="action" value="fix_all_forms">
                    <button type="submit" class="button button-primary">Fix All Forms JSON</button>
                </form>
                
                <?php
                if (isset($_POST['action']) && $_POST['action'] === 'fix_all_forms' && 
                    wp_verify_nonce($_POST['fix_forms_nonce'], 'fix_forms_json')) {
                    $this->fix_all_forms();
                }
                ?>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                <h2>Test JSON Cleaning</h2>
                <p>Test JSON cleaning function với dữ liệu mẫu:</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('test_json_clean', 'test_json_nonce'); ?>
                    <input type="hidden" name="action" value="test_json_clean">
                    
                    <p>
                        <label for="test_json">JSON Data to Test:</label><br>
                        <textarea name="test_json" id="test_json" rows="10" cols="80" style="width: 100%;">[{"id":"field_1","name":"test","type":"text","label":"Test Field",}]</textarea>
                    </p>
                    
                    <button type="submit" class="button">Test Clean JSON</button>
                </form>
                
                <?php
                if (isset($_POST['action']) && $_POST['action'] === 'test_json_clean' && 
                    wp_verify_nonce($_POST['test_json_nonce'], 'test_json_clean')) {
                    $this->test_json_cleaning();
                }
                ?>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                <h2>Current Forms Status</h2>
                <?php $this->show_forms_status(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Fix all forms in database
     */
    private function fix_all_forms() {
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $forms = $wpdb->get_results("SELECT id, name, form_fields FROM $forms_table");
        $fixed_count = 0;
        $error_count = 0;
        
        echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #0073aa; margin: 15px 0;'>";
        echo "<h3>Fixing Forms...</h3>";
        
        foreach ($forms as $form) {
            echo "<p><strong>Form #{$form->id}: " . esc_html($form->name) . "</strong> - ";
            
            if (empty($form->form_fields)) {
                echo "Empty fields, setting to []</p>";
                
                $wpdb->update(
                    $forms_table,
                    array('form_fields' => '[]'),
                    array('id' => $form->id),
                    array('%s'),
                    array('%d')
                );
                $fixed_count++;
                continue;
            }
            
            // Test current JSON
            json_decode($form->form_fields);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "Already valid ✅</p>";
                continue;
            }
            
            // Try to fix
            $cleaned = $this->clean_json_data($form->form_fields);
            
            // Test cleaned JSON
            json_decode($cleaned);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Update database
                $result = $wpdb->update(
                    $forms_table,
                    array('form_fields' => $cleaned),
                    array('id' => $form->id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result !== false) {
                    echo "Fixed ✅</p>";
                    $fixed_count++;
                } else {
                    echo "Database update failed ❌</p>";
                    $error_count++;
                }
            } else {
                echo "Could not fix: " . json_last_error_msg() . " ❌</p>";
                $error_count++;
            }
        }
        
        echo "<h4>Summary: Fixed $fixed_count forms, $error_count errors</h4>";
        echo "</div>";
    }
    
    /**
     * Test JSON cleaning function
     */
    private function test_json_cleaning() {
        $test_json = $_POST['test_json'];
        
        echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #0073aa; margin: 15px 0;'>";
        echo "<h3>JSON Cleaning Test Results</h3>";
        
        echo "<h4>Original JSON:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>" . esc_html($test_json) . "</pre>";
        
        echo "<h4>Original JSON Test:</h4>";
        json_decode($test_json);
        $original_error = json_last_error();
        if ($original_error === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ Original JSON is valid</p>";
        } else {
            echo "<p style='color: red;'>❌ Original JSON error: " . json_last_error_msg() . "</p>";
        }
        
        echo "<h4>Cleaned JSON:</h4>";
        $cleaned = $this->clean_json_data($test_json);
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>" . esc_html($cleaned) . "</pre>";
        
        echo "<h4>Cleaned JSON Test:</h4>";
        json_decode($cleaned);
        $cleaned_error = json_last_error();
        if ($cleaned_error === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ Cleaned JSON is valid</p>";
        } else {
            echo "<p style='color: red;'>❌ Cleaned JSON error: " . json_last_error_msg() . "</p>";
        }
        
        echo "</div>";
    }
    
    /**
     * Show current forms status
     */
    private function show_forms_status() {
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $forms = $wpdb->get_results("SELECT id, name, form_fields FROM $forms_table LIMIT 10");
        
        if (empty($forms)) {
            echo "<p>No forms found in database.</p>";
            return;
        }
        
        echo "<table class='wp-list-table widefat fixed striped'>";
        echo "<thead><tr><th>ID</th><th>Name</th><th>JSON Status</th><th>Field Count</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($forms as $form) {
            echo "<tr>";
            echo "<td>" . $form->id . "</td>";
            echo "<td>" . esc_html($form->name) . "</td>";
            
            if (empty($form->form_fields)) {
                echo "<td style='color: orange;'>Empty</td>";
                echo "<td>0</td>";
            } else {
                $fields = json_decode($form->form_fields, true);
                $json_error = json_last_error();
                
                if ($json_error === JSON_ERROR_NONE) {
                    echo "<td style='color: green;'>✅ Valid</td>";
                    echo "<td>" . (is_array($fields) ? count($fields) : 0) . "</td>";
                } else {
                    echo "<td style='color: red;'>❌ " . json_last_error_msg() . "</td>";
                    echo "<td>Unknown</td>";
                }
            }
            
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    }
}

// Initialize the fix
new LIFT_Form_Submit_Fix();
