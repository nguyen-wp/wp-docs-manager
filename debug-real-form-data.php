<?php
/**
 * Debug Real Form Data from Database
 * Check if form builder data is actually saved with custom widths
 */

// This script should be run in WordPress context to access database
// But we'll create a simulation to test data structure

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Debug Real Form Data - Column Width Issue</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo ".debug-section { margin: 20px 0; padding: 15px; border: 2px solid #ddd; }\n";
echo ".json-block { background: #f5f5f5; padding: 10px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; }\n";
echo ".error { background: #ffe6e6; color: #cc0000; }\n";
echo ".success { background: #e6ffe6; color: #006600; }\n";
echo ".warning { background: #fff3cd; color: #856404; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🔍 Debug Real Form Data - Column Width Issue</h1>\n";

// Simulate what the form data might look like
$possible_data_structures = [
    'correct_structure' => [
        'layout' => [
            'rows' => [
                [
                    'id' => 'row-1',
                    'columns' => [
                        [
                            'id' => 'col-1-1', 
                            'width' => '0.33',
                            'fields' => [
                                ['id' => 'field-1', 'type' => 'text', 'name' => 'name', 'label' => 'Name']
                            ]
                        ],
                        [
                            'id' => 'col-1-2',
                            'width' => '0.67', 
                            'fields' => [
                                ['id' => 'field-2', 'type' => 'email', 'name' => 'email', 'label' => 'Email']
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    
    'missing_width_structure' => [
        'layout' => [
            'rows' => [
                [
                    'id' => 'row-1',
                    'columns' => [
                        [
                            'id' => 'col-1-1',
                            // Missing 'width' property!
                            'fields' => [
                                ['id' => 'field-1', 'type' => 'text', 'name' => 'name', 'label' => 'Name']
                            ]
                        ],
                        [
                            'id' => 'col-1-2',
                            // Missing 'width' property!
                            'fields' => [
                                ['id' => 'field-2', 'type' => 'email', 'name' => 'email', 'label' => 'Email']
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    
    'legacy_structure' => [
        // Old structure without layout.rows.columns
        [
            ['id' => 'field-1', 'type' => 'text', 'name' => 'name', 'label' => 'Name'],
            ['id' => 'field-2', 'type' => 'email', 'name' => 'email', 'label' => 'Email']
        ]
    ]
];

foreach ($possible_data_structures as $structure_name => $data) {
    echo "<div class='debug-section'>\n";
    echo "<h3>📋 Testing: " . ucwords(str_replace('_', ' ', $structure_name)) . "</h3>\n";
    
    echo "<h4>Raw Data Structure:</h4>\n";
    echo "<div class='json-block'>" . json_encode($data, JSON_PRETTY_PRINT) . "</div>\n";
    
    // Simulate the frontend parsing logic
    $form_fields = array();
    $has_custom_widths = false;
    
    if (isset($data['layout']) && isset($data['layout']['rows'])) {
        echo "<div class='success'>✅ Found layout.rows structure</div>\n";
        
        foreach ($data['layout']['rows'] as $row_index => $row) {
            if (isset($row['columns'])) {
                echo "<div class='success'>✅ Found columns in row $row_index</div>\n";
                
                foreach ($row['columns'] as $col_index => $column) {
                    if (isset($column['width'])) {
                        echo "<div class='success'>✅ Found width: {$column['width']} in column $col_index</div>\n";
                        $has_custom_widths = true;
                    } else {
                        echo "<div class='warning'>⚠️ Missing width in column $col_index</div>\n";
                    }
                    
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
            } else {
                echo "<div class='error'>❌ No columns found in row $row_index</div>\n";
            }
        }
    } else {
        echo "<div class='error'>❌ No layout.rows structure found</div>\n";
        // Handle legacy structure
        if (is_array($data) && isset($data[0])) {
            echo "<div class='warning'>⚠️ Using legacy structure fallback</div>\n";
            $form_fields = $data[0] ?? $data;
        }
    }
    
    echo "<h4>Parsed Form Fields:</h4>\n";
    echo "<div class='json-block'>" . json_encode($form_fields, JSON_PRETTY_PRINT) . "</div>\n";
    
    echo "<h4>Would Render As:</h4>\n";
    if ($has_custom_widths) {
        echo "<div class='success'>✅ Custom widths detected - would use flexbox</div>\n";
        
        // Group by rows
        $rows = array();
        foreach ($form_fields as $field) {
            $row_index = isset($field['row']) ? $field['row'] : 0;
            if (!isset($rows[$row_index])) $rows[$row_index] = array();
            $rows[$row_index][] = $field;
        }
        
        foreach ($rows as $row_index => $row_fields) {
            echo "<div style='margin: 10px 0; padding: 8px; border: 1px solid #0073aa;'>\n";
            echo "<strong>Row $row_index:</strong><br>\n";
            
            // Group by columns
            $columns = array();
            foreach ($row_fields as $field) {
                $col_index = isset($field['column']) ? $field['column'] : 0;
                if (!isset($columns[$col_index])) $columns[$col_index] = array();
                $columns[$col_index][] = $field;
            }
            
            foreach ($columns as $col_index => $col_fields) {
                $width = '1';
                foreach ($col_fields as $field) {
                    if (isset($field['width'])) {
                        $width = $field['width'];
                        break;
                    }
                }
                if ($width === '1' && count($columns) > 1) {
                    $width = number_format(1 / count($columns), 3);
                }
                
                echo "<span style='display: inline-block; background: #e6f3ff; padding: 4px 8px; margin: 2px; border: 1px solid #0073aa;'>";
                echo "Col $col_index: flex: $width";
                echo "</span>\n";
            }
            echo "</div>\n";
        }
    } else {
        echo "<div class='warning'>⚠️ No custom widths - would use equal columns</div>\n";
    }
    
    echo "</div>\n";
}

echo "<div style='background: #ffe6e6; padding: 20px; margin-top: 30px;'>\n";
echo "<h3>🚨 Possible Issues:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Form builder not saving width:</strong> Check if form builder saves column width data</li>\n";
echo "<li><strong>Database storage issue:</strong> Width data might not be persisted</li>\n";
echo "<li><strong>Wrong data structure:</strong> Frontend expects layout.rows.columns but gets different format</li>\n";
echo "<li><strong>Form ID mismatch:</strong> Reading wrong form data from database</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #e6ffe6; padding: 20px; margin-top: 20px;'>\n";
echo "<h3>🔍 Next Steps to Debug:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Check database:</strong> Look at actual form data in wp_posts table</li>\n";
echo "<li><strong>Verify form builder save:</strong> Ensure width values are being saved</li>\n";
echo "<li><strong>Add debug output:</strong> Print raw data in frontend rendering</li>\n";
echo "<li><strong>Test with simple form:</strong> Create new form with custom widths</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "</body>\n</html>\n";
?>
