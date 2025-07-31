#!/usr/bin/env php
<?php
/**
 * JSON Form Validator for LIFT Documents System
 * 
 * Script này kiểm tra tính hợp lệ của file JSON form
 * 
 * Cách sử dụng:
 * php validate-form-json.php [path-to-json-file]
 * 
 * Hoặc:
 * ./validate-form-json.php [path-to-json-file]
 */

// Màu sắc cho terminal
class Colors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

/**
 * Validate JSON form structure
 */
function validate_form_json($json_file) {
    echo Colors::BLUE . "Validating form JSON: " . basename($json_file) . Colors::RESET . "\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $errors = array();
    $warnings = array();
    $stats = array();
    
    // Check file exists
    if (!file_exists($json_file)) {
        $errors[] = "File not found: {$json_file}";
        return array('valid' => false, 'errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
    }
    
    // Read file
    $json_content = file_get_contents($json_file);
    if ($json_content === false) {
        $errors[] = "Cannot read file: {$json_file}";
        return array('valid' => false, 'errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
    }
    
    // Parse JSON
    $form_data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "Invalid JSON syntax: " . json_last_error_msg();
        return array('valid' => false, 'errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
    }
    
    // Check main structure
    if (!isset($form_data['layout'])) {
        $errors[] = "Missing 'layout' property in root object";
    }
    
    if (!isset($form_data['fields'])) {
        $errors[] = "Missing 'fields' property in root object";
    }
    
    // Validate layout
    if (isset($form_data['layout'])) {
        $layout_result = validate_layout($form_data['layout']);
        $errors = array_merge($errors, $layout_result['errors']);
        $warnings = array_merge($warnings, $layout_result['warnings']);
        $stats['layout'] = $layout_result['stats'];
    }
    
    // Validate fields
    if (isset($form_data['fields'])) {
        $fields_result = validate_fields($form_data['fields']);
        $errors = array_merge($errors, $fields_result['errors']);
        $warnings = array_merge($warnings, $fields_result['warnings']);
        $stats['fields'] = $fields_result['stats'];
    }
    
    // Cross-validation between layout and fields
    if (isset($form_data['layout']) && isset($form_data['fields'])) {
        $cross_result = validate_cross_references($form_data['layout'], $form_data['fields']);
        $errors = array_merge($errors, $cross_result['errors']);
        $warnings = array_merge($warnings, $cross_result['warnings']);
    }
    
    $stats['file_size'] = filesize($json_file);
    $stats['total_errors'] = count($errors);
    $stats['total_warnings'] = count($warnings);
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
        'stats' => $stats
    );
}

/**
 * Validate layout structure
 */
function validate_layout($layout) {
    $errors = array();
    $warnings = array();
    $stats = array('rows' => 0, 'columns' => 0);
    
    if (!isset($layout['rows']) || !is_array($layout['rows'])) {
        $errors[] = "Layout must have 'rows' array";
        return array('errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
    }
    
    $row_ids = array();
    $col_ids = array();
    
    foreach ($layout['rows'] as $row_index => $row) {
        $stats['rows']++;
        
        // Check row ID
        if (!isset($row['id'])) {
            $errors[] = "Row at index {$row_index} missing 'id' property";
        } else {
            if (in_array($row['id'], $row_ids)) {
                $errors[] = "Duplicate row ID: {$row['id']}";
            }
            $row_ids[] = $row['id'];
        }
        
        // Check columns
        if (!isset($row['columns']) || !is_array($row['columns'])) {
            $errors[] = "Row '{$row['id']}' missing 'columns' array";
            continue;
        }
        
        if (empty($row['columns'])) {
            $warnings[] = "Row '{$row['id']}' has no columns";
            continue;
        }
        
        $total_width = 0;
        foreach ($row['columns'] as $col_index => $column) {
            $stats['columns']++;
            
            // Check column ID
            if (!isset($column['id'])) {
                $errors[] = "Column at row '{$row['id']}', index {$col_index} missing 'id' property";
            } else {
                if (in_array($column['id'], $col_ids)) {
                    $errors[] = "Duplicate column ID: {$column['id']}";
                }
                $col_ids[] = $column['id'];
            }
            
            // Check width
            if (isset($column['width'])) {
                $width = is_numeric($column['width']) ? (float)$column['width'] : 0;
                $total_width += $width;
                if ($width <= 0 || $width > 3) {
                    $warnings[] = "Column '{$column['id']}' has unusual width: {$column['width']}";
                }
            } else {
                $total_width += 1; // Default width
            }
            
            // Check fields array
            if (!isset($column['fields'])) {
                $warnings[] = "Column '{$column['id']}' missing 'fields' array";
            } elseif (!is_array($column['fields'])) {
                $errors[] = "Column '{$column['id']}' 'fields' must be an array";
            }
        }
        
        // Check total width per row
        if ($total_width > 3) {
            $warnings[] = "Row '{$row['id']}' total width ({$total_width}) seems too large";
        }
    }
    
    $stats['unique_row_ids'] = count(array_unique($row_ids));
    $stats['unique_col_ids'] = count(array_unique($col_ids));
    
    return array('errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
}

/**
 * Validate fields array
 */
function validate_fields($fields) {
    $errors = array();
    $warnings = array();
    $stats = array('total_fields' => 0, 'required_fields' => 0, 'field_types' => array());
    
    if (!is_array($fields)) {
        $errors[] = "Fields must be an array";
        return array('errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
    }
    
    $field_ids = array();
    $field_names = array();
    $supported_types = array(
        'text', 'textarea', 'email', 'number', 'date', 'select', 
        'radio', 'checkbox', 'file', 'signature', 'header', 'paragraph'
    );
    
    foreach ($fields as $field_index => $field) {
        $stats['total_fields']++;
        
        // Check required properties
        if (!isset($field['id'])) {
            $errors[] = "Field at index {$field_index} missing 'id' property";
        } else {
            if (in_array($field['id'], $field_ids)) {
                $errors[] = "Duplicate field ID: {$field['id']}";
            }
            $field_ids[] = $field['id'];
        }
        
        if (!isset($field['type'])) {
            $errors[] = "Field '{$field['id']}' missing 'type' property";
        } else {
            $type = $field['type'];
            if (!in_array($type, $supported_types)) {
                $errors[] = "Field '{$field['id']}' has unsupported type: {$type}";
            }
            
            // Count field types
            if (!isset($stats['field_types'][$type])) {
                $stats['field_types'][$type] = 0;
            }
            $stats['field_types'][$type]++;
        }
        
        if (!isset($field['label'])) {
            $errors[] = "Field '{$field['id']}' missing 'label' property";
        }
        
        // Check field name (for non-display fields)
        if (isset($field['type']) && !in_array($field['type'], array('header', 'paragraph'))) {
            if (!isset($field['name'])) {
                $warnings[] = "Field '{$field['id']}' missing 'name' property";
            } else {
                if (in_array($field['name'], $field_names)) {
                    $warnings[] = "Duplicate field name: {$field['name']}";
                }
                $field_names[] = $field['name'];
            }
        }
        
        // Check required flag
        if (isset($field['required']) && $field['required'] === true) {
            $stats['required_fields']++;
        }
        
        // Type-specific validation
        if (isset($field['type'])) {
            switch ($field['type']) {
                case 'select':
                case 'radio':
                case 'checkbox':
                    if (!isset($field['options']) || !is_array($field['options'])) {
                        $errors[] = "Field '{$field['id']}' of type '{$field['type']}' must have 'options' array";
                    } elseif (empty($field['options'])) {
                        $warnings[] = "Field '{$field['id']}' has empty options array";
                    }
                    break;
                    
                case 'email':
                    if (isset($field['placeholder']) && !filter_var($field['placeholder'], FILTER_VALIDATE_EMAIL) && strpos($field['placeholder'], '@') !== false) {
                        $warnings[] = "Field '{$field['id']}' placeholder doesn't look like a valid email format";
                    }
                    break;
            }
        }
    }
    
    $stats['unique_field_ids'] = count(array_unique($field_ids));
    $stats['unique_field_names'] = count(array_unique($field_names));
    
    return array('errors' => $errors, 'warnings' => $warnings, 'stats' => $stats);
}

/**
 * Validate cross-references between layout and fields
 */
function validate_cross_references($layout, $fields) {
    $errors = array();
    $warnings = array();
    
    // Get all field IDs from fields array
    $field_ids = array();
    foreach ($fields as $field) {
        if (isset($field['id'])) {
            $field_ids[] = $field['id'];
        }
    }
    
    // Get all field IDs referenced in layout
    $layout_field_ids = array();
    foreach ($layout['rows'] as $row) {
        if (isset($row['columns'])) {
            foreach ($row['columns'] as $column) {
                if (isset($column['fields'])) {
                    foreach ($column['fields'] as $field) {
                        if (isset($field['id'])) {
                            $layout_field_ids[] = $field['id'];
                        }
                    }
                }
            }
        }
    }
    
    // Check for missing field definitions
    $missing_in_fields = array_diff($layout_field_ids, $field_ids);
    foreach ($missing_in_fields as $missing_id) {
        $errors[] = "Field '{$missing_id}' referenced in layout but not defined in fields array";
    }
    
    // Check for unused field definitions
    $unused_fields = array_diff($field_ids, $layout_field_ids);
    foreach ($unused_fields as $unused_id) {
        $warnings[] = "Field '{$unused_id}' defined but not used in layout";
    }
    
    return array('errors' => $errors, 'warnings' => $warnings);
}

/**
 * Print validation results
 */
function print_results($result) {
    $stats = $result['stats'];
    
    // Print stats
    echo Colors::BOLD . "Statistics:" . Colors::RESET . "\n";
    echo "File size: " . number_format($stats['file_size']) . " bytes\n";
    
    if (isset($stats['layout'])) {
        echo "Rows: " . $stats['layout']['rows'] . "\n";
        echo "Columns: " . $stats['layout']['columns'] . "\n";
    }
    
    if (isset($stats['fields'])) {
        echo "Total fields: " . $stats['fields']['total_fields'] . "\n";
        echo "Required fields: " . $stats['fields']['required_fields'] . "\n";
        
        if (!empty($stats['fields']['field_types'])) {
            echo "Field types:\n";
            foreach ($stats['fields']['field_types'] as $type => $count) {
                echo "  - {$type}: {$count}\n";
            }
        }
    }
    
    echo "\n";
    
    // Print errors
    if (!empty($result['errors'])) {
        echo Colors::RED . Colors::BOLD . "ERRORS (" . count($result['errors']) . "):" . Colors::RESET . "\n";
        foreach ($result['errors'] as $error) {
            echo Colors::RED . "✗ " . $error . Colors::RESET . "\n";
        }
        echo "\n";
    }
    
    // Print warnings
    if (!empty($result['warnings'])) {
        echo Colors::YELLOW . Colors::BOLD . "WARNINGS (" . count($result['warnings']) . "):" . Colors::RESET . "\n";
        foreach ($result['warnings'] as $warning) {
            echo Colors::YELLOW . "⚠ " . $warning . Colors::RESET . "\n";
        }
        echo "\n";
    }
    
    // Final result
    if ($result['valid']) {
        echo Colors::GREEN . Colors::BOLD . "✓ VALIDATION PASSED" . Colors::RESET . "\n";
        if (!empty($result['warnings'])) {
            echo Colors::YELLOW . "Note: There are warnings that should be reviewed." . Colors::RESET . "\n";
        }
    } else {
        echo Colors::RED . Colors::BOLD . "✗ VALIDATION FAILED" . Colors::RESET . "\n";
        echo Colors::RED . "Please fix the errors above before using this form." . Colors::RESET . "\n";
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

// Get file path from command line argument
$json_file = isset($argv[1]) ? $argv[1] : null;

if (!$json_file) {
    echo "Usage: php validate-form-json.php <path-to-json-file>\n";
    echo "\nExample:\n";
    echo "php validate-form-json.php data/sample-form-template.json\n";
    exit(1);
}

// Validate the file
$result = validate_form_json($json_file);

// Print results
print_results($result);

// Exit with appropriate code
exit($result['valid'] ? 0 : 1);
?>
