<?php
/**
 * Test New Pure Flexbox Frontend Rendering
 * No col- classes, pure flex values like form builder backend
 */

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
                                'label' => 'Customer Name (33%)',
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
                                'label' => 'Email Address (67%)',
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
                        'width' => '0.25',
                        'fields' => [
                            [
                                'id' => 'field-phone',
                                'type' => 'text',
                                'name' => 'phone',
                                'label' => 'Phone (25%)',
                                'required' => false
                            ]
                        ]
                    ],
                    [
                        'id' => 'col-2-2',
                        'width' => '0.5',
                        'fields' => [
                            [
                                'id' => 'field-company',
                                'type' => 'text',
                                'name' => 'company',
                                'label' => 'Company (50%)',
                                'required' => false
                            ]
                        ]
                    ],
                    [
                        'id' => 'col-2-3',
                        'width' => '0.25',
                        'fields' => [
                            [
                                'id' => 'field-department',
                                'type' => 'text',
                                'name' => 'department',
                                'label' => 'Department (25%)',
                                'required' => false
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'row-3',
                'columns' => [
                    [
                        'id' => 'col-3-1',
                        'width' => '1',
                        'fields' => [
                            [
                                'id' => 'field-message',
                                'type' => 'textarea',
                                'name' => 'message',
                                'label' => 'Message (100%)',
                                'required' => false
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n";
echo "<title>Test Pure Flexbox Frontend Rendering</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }\n";

// CSS matching the updated secure-frontend.css
echo ".form-row {\n";
echo "    display: flex;\n";
echo "    gap: 16px;\n";
echo "    margin-bottom: 16px;\n";
echo "    flex-wrap: wrap;\n";
echo "    border: 2px solid #0073aa;\n";
echo "    padding: 10px;\n";
echo "    border-radius: 4px;\n";
echo "}\n";

echo ".form-column {\n";
echo "    min-width: 0;\n";
echo "    flex: 1;\n";
echo "    border: 1px solid #ff6600;\n";
echo "    padding: 10px;\n";
echo "    background: #fff3e6;\n";
echo "    border-radius: 4px;\n";
echo "}\n";

echo ".form-field { margin-bottom: 8px; }\n";
echo ".form-field label { display: block; font-weight: bold; margin-bottom: 4px; color: #333; }\n";
echo ".form-field input, .form-field textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }\n";
echo ".debug-info { background: #e6f3ff; padding: 8px; margin-bottom: 10px; font-size: 12px; border-radius: 4px; }\n";

// Responsive
echo "@media (max-width: 768px) {\n";
echo "    .form-row { flex-direction: column; }\n";
echo "    .form-column { flex: none !important; width: 100% !important; }\n";
echo "}\n";

echo "</style>\n";
echo "</head>\n<body>\n";

echo "<div class='container'>\n";
echo "<h1>🎯 Test Pure Flexbox Frontend Rendering</h1>\n";
echo "<p><strong>New approach:</strong> No col- classes, pure flex values like form builder backend</p>\n";

// Parse form data like new frontend does
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

// Render like new frontend does
$rows = array();
foreach ($form_fields as $field) {
    $row_index = isset($field['row']) ? $field['row'] : 0;
    if (!isset($rows[$row_index])) {
        $rows[$row_index] = array();
    }
    $rows[$row_index][] = $field;
}

foreach ($rows as $row_index => $row_fields) {
    // Always use flexbox layout like form builder backend
    echo '<div class="form-row" data-row="' . $row_index . '">';
    
    echo '<div class="debug-info">Row ' . $row_index . ' - Pure Flexbox Layout</div>';

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
        // Always use flexbox with width values like form builder backend
        $column_width = '1'; // Default flex value
        
        // Check if any field in this column has a custom width value
        foreach ($col_fields as $field) {
            if (isset($field['width']) && is_numeric($field['width'])) {
                $column_width = $field['width'];
                break;
            }
        }
        
        // Calculate default width based on number of columns if no custom width
        if ($column_width === '1' && count($columns) > 1) {
            $column_width = number_format(1 / count($columns), 3);
        }
        
        echo '<div class="form-column" data-column="' . $col_index . '" style="flex: ' . $column_width . ';">';
        
        echo '<div class="debug-info">Column ' . $col_index . ' - flex: ' . $column_width . '</div>';

        foreach ($col_fields as $field) {
            echo '<div class="form-field">';
            echo '<label>' . $field['label'] . '</label>';
            
            switch ($field['type']) {
                case 'textarea':
                    echo '<textarea name="' . $field['name'] . '" rows="3"></textarea>';
                    break;
                case 'email':
                    echo '<input type="email" name="' . $field['name'] . '">';
                    break;
                default:
                    echo '<input type="text" name="' . $field['name'] . '">';
                    break;
            }
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
}

echo "<div style='background: #e8f5e8; padding: 15px; margin-top: 20px; border-radius: 4px;'>\n";
echo "<h3>✅ Key Improvements:</h3>\n";
echo "<ul>\n";
echo "<li>🎯 <strong>Pure flexbox</strong> - No col- classes, direct flex values</li>\n";
echo "<li>🔄 <strong>Consistent with backend</strong> - Same rendering approach as form builder</li>\n";
echo "<li>📱 <strong>Responsive</strong> - Stacks on mobile while maintaining proportions</li>\n";
echo "<li>⚡ <strong>Simplified</strong> - Less CSS classes, more direct styling</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n</html>\n";
?>
