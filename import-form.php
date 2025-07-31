<?php
/**
 * Form Import Script for LIFT Documents System
 * 
 * Script này giúp import form từ file JSON vào database
 * 
 * Cách sử dụng:
 * 1. Đặt file này trong thư mục root của WordPress
 * 2. Đảm bảo file JSON nằm trong thư mục đúng
 * 3. Truy cập: yoursite.com/import-form.php
 * 4. Hoặc chạy qua command line: php import-form.php
 */

// Kiểm tra nếu chạy từ command line
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // Load WordPress nếu chạy từ web
    require_once('wp-config.php');
} else {
    // Load WordPress nếu chạy từ CLI
    require_once(dirname(__FILE__) . '/wp-config.php');
}

/**
 * Import form từ JSON file
 */
function import_form_from_json($json_file_path, $form_name = null, $form_description = null) {
    global $wpdb;
    
    // Kiểm tra file tồn tại
    if (!file_exists($json_file_path)) {
        return array('success' => false, 'message' => 'JSON file not found: ' . $json_file_path);
    }
    
    // Đọc và parse JSON
    $json_content = file_get_contents($json_file_path);
    $form_data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg());
    }
    
    // Validate cấu trúc JSON
    if (!isset($form_data['layout']) || !isset($form_data['fields'])) {
        return array('success' => false, 'message' => 'Invalid form structure. Missing layout or fields.');
    }
    
    // Set default values
    if (!$form_name) {
        $form_name = 'Imported Form - ' . date('Y-m-d H:i:s');
    }
    if (!$form_description) {
        $form_description = 'Form imported from JSON file';
    }
    
    // Prepare data for database
    $table_name = $wpdb->prefix . 'lift_forms';
    $insert_data = array(
        'name' => $form_name,
        'description' => $form_description,
        'form_fields' => $json_content, // Lưu raw JSON
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
        'created_by' => get_current_user_id() // Sẽ là 0 nếu chạy từ CLI
    );
    
    // Insert vào database
    $result = $wpdb->insert($table_name, $insert_data);
    
    if ($result === false) {
        return array(
            'success' => false, 
            'message' => 'Database error: ' . $wpdb->last_error
        );
    }
    
    $form_id = $wpdb->insert_id;
    
    return array(
        'success' => true, 
        'message' => 'Form imported successfully!',
        'form_id' => $form_id,
        'form_name' => $form_name
    );
}

/**
 * Validate form structure
 */
function validate_form_structure($form_data) {
    $errors = array();
    
    // Check layout structure
    if (!isset($form_data['layout']['rows']) || !is_array($form_data['layout']['rows'])) {
        $errors[] = 'Invalid layout structure - missing rows array';
    }
    
    // Check fields array
    if (!isset($form_data['fields']) || !is_array($form_data['fields'])) {
        $errors[] = 'Invalid fields structure - missing fields array';
    }
    
    // Validate each field has required properties
    if (isset($form_data['fields'])) {
        foreach ($form_data['fields'] as $index => $field) {
            if (!isset($field['id'])) {
                $errors[] = "Field at index {$index} missing ID";
            }
            if (!isset($field['type'])) {
                $errors[] = "Field at index {$index} missing type";
            }
            if (!isset($field['label'])) {
                $errors[] = "Field at index {$index} missing label";
            }
        }
    }
    
    return $errors;
}

/**
 * List available JSON templates
 */
function list_available_templates() {
    $template_dir = dirname(__FILE__) . '/wp-content/plugins/wp-docs-manager/data/';
    $templates = array();
    
    if (is_dir($template_dir)) {
        $files = glob($template_dir . '*.json');
        foreach ($files as $file) {
            $templates[] = array(
                'file' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            );
        }
    }
    
    return $templates;
}

// Main execution
if (!$is_cli) {
    // Web interface
    echo '<h1>LIFT Forms - JSON Import Tool</h1>';
    
    if (isset($_POST['import'])) {
        $json_file = $_POST['json_file'];
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_description = sanitize_textarea_field($_POST['form_description']);
        
        $result = import_form_from_json($json_file, $form_name, $form_description);
        
        if ($result['success']) {
            echo '<div style="color: green; padding: 10px; border: 1px solid green; background: #f0fff0;">';
            echo '<strong>Success!</strong> ' . $result['message'];
            echo '<br>Form ID: ' . $result['form_id'];
            echo '<br>Form Name: ' . $result['form_name'];
            echo '<br><a href="/wp-admin/admin.php?page=lift-forms-builder&id=' . $result['form_id'] . '">Edit Form</a>';
            echo '</div>';
        } else {
            echo '<div style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">';
            echo '<strong>Error!</strong> ' . $result['message'];
            echo '</div>';
        }
    }
    
    // Show form
    echo '<form method="post" style="max-width: 600px; margin: 20px 0;">';
    echo '<h2>Import Form from JSON</h2>';
    
    echo '<p><label>JSON File Path:</label><br>';
    echo '<input type="text" name="json_file" value="' . dirname(__FILE__) . '/wp-content/plugins/wp-docs-manager/data/sample-form-template.json" style="width: 100%; padding: 5px;" required></p>';
    
    echo '<p><label>Form Name:</label><br>';
    echo '<input type="text" name="form_name" value="Onsite Contractor Information Form" style="width: 100%; padding: 5px;" required></p>';
    
    echo '<p><label>Form Description:</label><br>';
    echo '<textarea name="form_description" style="width: 100%; padding: 5px; height: 80px;">Contractor pre-qualification and project information form</textarea></p>';
    
    echo '<p><input type="submit" name="import" value="Import Form" style="padding: 10px 20px; background: #0073aa; color: white; border: none; cursor: pointer;"></p>';
    echo '</form>';
    
    // Show available templates
    echo '<h3>Available Templates:</h3>';
    $templates = list_available_templates();
    if (!empty($templates)) {
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<tr><th>File</th><th>Size</th><th>Modified</th><th>Action</th></tr>';
        foreach ($templates as $template) {
            echo '<tr>';
            echo '<td>' . $template['file'] . '</td>';
            echo '<td>' . number_format($template['size']) . ' bytes</td>';
            echo '<td>' . $template['modified'] . '</td>';
            echo '<td><a href="#" onclick="document.querySelector(\'input[name=json_file]\').value=\'' . $template['path'] . '\'; return false;">Use This</a></td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No JSON templates found in data directory.</p>';
    }
    
} else {
    // CLI interface
    echo "LIFT Forms - JSON Import Tool (CLI)\n";
    echo "===================================\n\n";
    
    // Default file path
    $json_file = dirname(__FILE__) . '/wp-content/plugins/wp-docs-manager/data/sample-form-template.json';
    $form_name = 'Onsite Contractor Information Form';
    $form_description = 'Contractor pre-qualification and project information form';
    
    // Check for command line arguments
    if (isset($argv[1])) {
        $json_file = $argv[1];
    }
    if (isset($argv[2])) {
        $form_name = $argv[2];
    }
    if (isset($argv[3])) {
        $form_description = $argv[3];
    }
    
    echo "Importing form...\n";
    echo "File: {$json_file}\n";
    echo "Name: {$form_name}\n";
    echo "Description: {$form_description}\n\n";
    
    $result = import_form_from_json($json_file, $form_name, $form_description);
    
    if ($result['success']) {
        echo "SUCCESS: " . $result['message'] . "\n";
        echo "Form ID: " . $result['form_id'] . "\n";
        echo "Form Name: " . $result['form_name'] . "\n";
    } else {
        echo "ERROR: " . $result['message'] . "\n";
        exit(1);
    }
}
?>
