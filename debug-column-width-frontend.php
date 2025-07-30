<?php
/**
 * Debug Column Width Issue in Frontend
 * Test if frontend properly renders column widths from form builder
 */

// Include WordPress
require_once(dirname(__FILE__) . '/../../../wp-config.php');

// Test form data structure
$test_form_data = [
    'layout' => [
        'rows' => [
            [
                'id' => 'row-1',
                'columns' => [
                    [
                        'id' => 'col-1',
                        'width' => '0.33',
                        'fields' => [
                            [
                                'id' => 'field-1',
                                'type' => 'text',
                                'name' => 'test_field_1',
                                'label' => 'Test Field 1'
                            ]
                        ]
                    ],
                    [
                        'id' => 'col-2', 
                        'width' => '0.66',
                        'fields' => [
                            [
                                'id' => 'field-2',
                                'type' => 'text',
                                'name' => 'test_field_2',
                                'label' => 'Test Field 2'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'fields' => [
        [
            'id' => 'field-1',
            'type' => 'text',
            'name' => 'test_field_1',
            'label' => 'Test Field 1'
        ],
        [
            'id' => 'field-2',
            'type' => 'text',
            'name' => 'test_field_2', 
            'label' => 'Test Field 2'
        ]
    ]
];

echo "=== Debug Column Width Frontend ===\n\n";

echo "1. Test form data structure:\n";
echo json_encode($test_form_data, JSON_PRETTY_PRINT) . "\n\n";

echo "2. Parsing form data like frontend does:\n";
$form_fields = array();
$parsed_data = $test_form_data;

if (isset($parsed_data['layout']) && isset($parsed_data['layout']['rows'])) {
    echo "   Found new structure with layout.rows\n";
    
    foreach ($parsed_data['layout']['rows'] as $row_index => $row) {
        echo "   Row {$row_index}: {$row['id']}\n";
        
        if (isset($row['columns'])) {
            foreach ($row['columns'] as $col_index => $column) {
                echo "   - Column {$col_index}: {$column['id']}, width: {$column['width']}\n";
                
                if (isset($column['fields'])) {
                    foreach ($column['fields'] as $field) {
                        // Add row/column information to field
                        $field['row'] = $row_index;
                        $field['column'] = $col_index;
                        if (isset($column['width'])) {
                            $field['width'] = $column['width'];
                        }
                        $form_fields[] = $field;
                        echo "     Field: {$field['id']}, row: {$field['row']}, column: {$field['column']}, width: {$field['width']}\n";
                    }
                }
            }
        }
    }
}

echo "\n3. Final form_fields array:\n";
print_r($form_fields);

echo "\n4. Test rendering logic:\n";

// Group fields by rows
$rows = array();
foreach ($form_fields as $field) {
    $row_index = isset($field['row']) ? $field['row'] : 0;
    if (!isset($rows[$row_index])) {
        $rows[$row_index] = array();
    }
    $rows[$row_index][] = $field;
}

foreach ($rows as $row_index => $row_fields) {
    echo "Row {$row_index}:\n";
    
    // Check if this row has custom column widths
    $has_custom_widths = false;
    foreach ($row_fields as $field) {
        if (isset($field['width']) && is_numeric($field['width'])) {
            $has_custom_widths = true;
            break;
        }
    }
    
    echo "  Has custom widths: " . ($has_custom_widths ? 'Yes' : 'No') . "\n";
    
    // Group fields by columns within this row
    $columns = array();
    foreach ($row_fields as $field) {
        $col_index = isset($field['column']) ? $field['column'] : 0;
        if (!isset($columns[$col_index])) {
            $columns[$col_index] = array();
        }
        $columns[$col_index][] = $field;
    }
    
    foreach ($columns as $col_index => $col_fields) {
        echo "  Column {$col_index}:\n";
        
        // Check if any field in this column has a custom width value
        $custom_width = null;
        foreach ($col_fields as $field) {
            if (isset($field['width']) && is_numeric($field['width'])) {
                $custom_width = $field['width'];
                break;
            }
        }
        
        echo "    Custom width: " . ($custom_width ?: 'None') . "\n";
        echo "    Would render: <div class=\"form-column col-custom\" style=\"flex: {$custom_width};\">\n";
        
        foreach ($col_fields as $field) {
            echo "      Field: {$field['label']} (id: {$field['id']})\n";
        }
    }
    echo "\n";
}

echo "\n=== Test Complete ===\n";
?>
