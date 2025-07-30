<?php
/**
 * Test Frontend Column Width Rendering
 * Create a form with custom column widths and verify frontend rendering
 */

// This would be added to WordPress testing or run via WP-CLI

// Test form data with custom column widths
$test_form_data = [
    'layout' => [
        'rows' => [
            [
                'id' => 'row-1',
                'columns' => [
                    [
                        'id' => 'col-1-1',
                        'width' => '0.33',
                        'fields' => [
                            [
                                'id' => 'field-name',
                                'type' => 'text',
                                'name' => 'customer_name',
                                'label' => 'Customer Name',
                                'required' => true
                            ]
                        ]
                    ],
                    [
                        'id' => 'col-1-2',
                        'width' => '0.67',
                        'fields' => [
                            [
                                'id' => 'field-email',
                                'type' => 'email',
                                'name' => 'customer_email',
                                'label' => 'Email Address',
                                'required' => true
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'row-2',
                'columns' => [
                    [
                        'id' => 'col-2-1',
                        'width' => '1',
                        'fields' => [
                            [
                                'id' => 'field-message',
                                'type' => 'textarea',
                                'name' => 'message',
                                'label' => 'Message',
                                'required' => false
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    'fields' => [
        [
            'id' => 'field-name',
            'type' => 'text',
            'name' => 'customer_name',
            'label' => 'Customer Name',
            'required' => true
        ],
        [
            'id' => 'field-email',
            'type' => 'email',
            'name' => 'customer_email',
            'label' => 'Email Address',
            'required' => true
        ],
        [
            'id' => 'field-message',
            'type' => 'textarea',
            'name' => 'message',
            'label' => 'Message',
            'required' => false
        ]
    ]
];

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<title>Frontend Column Width Test</title>\n";
echo "<link rel='stylesheet' href='assets/css/secure-frontend.css'>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo ".debug-info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>Frontend Column Width Test</h1>\n";

echo "<div class='debug-info'>\n";
echo "<strong>Form Data Structure:</strong><br>\n";
echo "Row 1: 33% / 67% columns<br>\n";
echo "Row 2: 100% column<br>\n";
echo "</div>\n";

// Parse form data like frontend does
$form_fields = array();
$parsed_data = $test_form_data;

if (isset($parsed_data['layout']) && isset($parsed_data['layout']['rows'])) {
    foreach ($parsed_data['layout']['rows'] as $row_index => $row) {
        if (isset($row['columns'])) {
            foreach ($row['columns'] as $col_index => $column) {
                if (isset($column['fields'])) {
                    foreach ($column['fields'] as $field) {
                        $field['row'] = $row_index;
                        $field['column'] = $col_index;
                        if (isset($column['width'])) {
                            $field['width'] = $column['width'];
                        }
                        $form_fields[] = $field;
                    }
                }
            }
        }
    }
}

// Render like frontend does
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
    // Check if this row has custom column widths
    $has_custom_widths = false;
    foreach ($row_fields as $field) {
        if (isset($field['width']) && is_numeric($field['width'])) {
            $has_custom_widths = true;
            break;
        }
    }
    
    $row_class = $has_custom_widths ? 'form-row custom-widths' : 'form-row';
    echo '<div class="' . $row_class . '" data-row="' . $row_index . '" style="border: 2px solid #' . ($has_custom_widths ? '0073aa' : 'ccc') . '; padding: 10px; margin: 10px 0;">';
    
    echo '<div class="debug-info">Row ' . $row_index . ' - ' . ($has_custom_widths ? 'Custom Widths (Flexbox)' : 'Default (Grid)') . '</div>';

    // Group fields by columns within this row
    $columns = array();
    foreach ($row_fields as $field) {
        $col_index = isset($field['column']) ? $field['column'] : 0;
        if (!isset($columns[$col_index])) {
            $columns[$col_index] = array();
        }
        $columns[$col_index][] = $field;
    }

    // Sort columns by index
    ksort($columns);

    foreach ($columns as $col_index => $col_fields) {
        // Use flexbox width from form builder if available
        $column_style = '';
        $column_width_class = '';
        
        // Check if any field in this column has a custom width value
        $custom_width = null;
        foreach ($col_fields as $field) {
            if (isset($field['width']) && is_numeric($field['width'])) {
                $custom_width = $field['width'];
                break;
            }
        }
        
        if ($custom_width) {
            $column_style = 'style="flex: ' . $custom_width . '; border: 1px solid #ff6600; background: #fff3e6;"';
            $column_width_class = 'col-custom';
        } else {
            $column_width_class = 'col-' . count($columns);
            $column_style = 'style="border: 1px solid #ccc; background: #f9f9f9;"';
        }
        
        echo '<div class="form-column ' . $column_width_class . '" data-column="' . $col_index . '" ' . $column_style . '>';
        
        echo '<div style="font-size: 11px; color: #666; margin-bottom: 5px;">Column ' . $col_index . ' - ' . ($custom_width ? 'flex: ' . $custom_width : $column_width_class) . '</div>';

        foreach ($col_fields as $field) {
            echo '<div class="form-field">';
            echo '<label style="display: block; font-weight: bold; margin-bottom: 4px;">' . $field['label'] . '</label>';
            
            switch ($field['type']) {
                case 'textarea':
                    echo '<textarea name="' . $field['name'] . '" style="width: 100%; padding: 8px; border: 1px solid #ddd;" rows="3"></textarea>';
                    break;
                case 'email':
                    echo '<input type="email" name="' . $field['name'] . '" style="width: 100%; padding: 8px; border: 1px solid #ddd;">';
                    break;
                default:
                    echo '<input type="text" name="' . $field['name'] . '" style="width: 100%; padding: 8px; border: 1px solid #ddd;">';
                    break;
            }
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
}

echo "<div class='debug-info'>\n";
echo "<strong>Legend:</strong><br>\n";
echo "🟦 Blue border = Custom width row (flexbox)<br>\n";
echo "⬜ Gray border = Default row (grid)<br>\n";
echo "🟧 Orange background = Custom width column<br>\n";
echo "⬜ Gray background = Default column<br>\n";
echo "</div>\n";

echo "</body>\n</html>\n";
?>
