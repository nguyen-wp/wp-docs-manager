<?php
/**
 * Test Real Frontend Rendering with Fixed Logic
 * Simulate actual form rendering process
 */

// Simulate real form data like from WordPress
$real_form_data = [
    'layout' => [
        'rows' => [
            [
                'columns' => [
                    [
                        'width' => '0.33',
                        'fields' => [
                            [
                                'id' => 'name',
                                'type' => 'text',
                                'label' => 'Name (33%)',
                                'name' => 'customer_name'
                            ]
                        ]
                    ],
                    [
                        'width' => '0.67',
                        'fields' => [
                            [
                                'id' => 'email',
                                'type' => 'email', 
                                'label' => 'Email Address (67%)',
                                'name' => 'customer_email'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'columns' => [
                    [
                        'fields' => [
                            [
                                'id' => 'phone',
                                'type' => 'text',
                                'label' => 'Phone (Equal 1/3)',
                                'name' => 'phone'
                            ]
                        ]
                    ],
                    [
                        'fields' => [
                            [
                                'id' => 'company',
                                'type' => 'text',
                                'label' => 'Company (Equal 2/3)',
                                'name' => 'company'
                            ]
                        ]
                    ],
                    [
                        'fields' => [
                            [
                                'id' => 'department',
                                'type' => 'text',
                                'label' => 'Department (Equal 3/3)',
                                'name' => 'department'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// Parse form data exactly like the frontend does
$form_fields = array();
if (isset($real_form_data['layout']) && isset($real_form_data['layout']['rows'])) {
    foreach ($real_form_data['layout']['rows'] as $row_index => $row) {
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

// Render exactly like the fixed frontend does
function render_test_layout($form_fields) {
    // Group fields by rows
    $rows = array();
    foreach ($form_fields as $field) {
        $row_index = isset($field['row']) ? $field['row'] : 0;
        if (!isset($rows[$row_index])) {
            $rows[$row_index] = array();
        }
        $rows[$row_index][] = $field;
    }

    // Sort rows by index
    ksort($rows);

    foreach ($rows as $row_index => $row_fields) {
        echo '<div class="form-row" data-row="' . $row_index . '">';
        echo '<div class="debug-row">Row ' . $row_index . '</div>';

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
            // FIXED LOGIC - exactly like the updated backend
            $column_width = 1; // Default flex value as number
            
            // Check if any field in this column has a custom width value
            foreach ($col_fields as $field) {
                if (isset($field['width']) && is_numeric($field['width'])) {
                    $column_width = floatval($field['width']);
                    break;
                }
            }
            
            // Calculate default width based on number of columns if no custom width
            if ($column_width == 1 && count($columns) > 1) {
                $column_width = 1 / count($columns);
            }
            
            // Format for clean output - remove unnecessary decimals
            $flex_value = ($column_width == intval($column_width)) ? intval($column_width) : rtrim(rtrim(number_format($column_width, 6), '0'), '.');
            
            echo '<div class="form-column" data-column="' . $col_index . '" style="flex: ' . $flex_value . ';">';
            echo '<div class="debug-col">Col ' . $col_index . ' - flex: ' . $flex_value . '</div>';

            foreach ($col_fields as $field) {
                echo '<div class="form-field">';
                echo '<label>' . $field['label'] . '</label>';
                echo '<input type="' . $field['type'] . '" name="' . $field['name'] . '">';
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';
    }
}

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Test Real Frontend Rendering - FIXED</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo ".container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; }\n";
echo ".form-row { display: flex; gap: 16px; margin-bottom: 16px; border: 2px solid #0073aa; padding: 10px; }\n";
echo ".form-column { border: 1px solid #ff6600; padding: 10px; background: #fff3e6; min-width: 0; }\n";
echo ".form-field { margin-bottom: 10px; }\n";
echo ".form-field label { display: block; font-weight: bold; margin-bottom: 4px; }\n";
echo ".form-field input { width: 100%; padding: 8px; border: 1px solid #ddd; }\n";
echo ".debug-row, .debug-col { background: #e6f3ff; padding: 3px 6px; font-size: 11px; margin-bottom: 5px; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<div class='container'>\n";
echo "<h1>🎯 Test Real Frontend Rendering - FIXED</h1>\n";

echo "<h3>Before Fix Issues:</h3>\n";
echo "<ul>\n";
echo "<li>❌ Two equal columns: style=\"flex: 0.500;\" (ugly)</li>\n";
echo "<li>❌ Three equal: style=\"flex: 0.333;\" (imprecise)</li>\n";
echo "<li>❌ Number formatting issues</li>\n";
echo "</ul>\n";

echo "<h3>After Fix Results:</h3>\n";

render_test_layout($form_fields);

echo "<div style='background: #e6ffe6; padding: 15px; margin-top: 20px;'>\n";
echo "<h3>✅ Fixed Issues:</h3>\n";
echo "<ul>\n";
echo "<li>✅ Clean flex values: '0.5' instead of '0.500'</li>\n";
echo "<li>✅ Better precision: '0.333333' for 1/3</li>\n";
echo "<li>✅ Integer values: '1' for full width</li>\n";
echo "<li>✅ Proper flexbox behavior</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n</html>\n";
?>
