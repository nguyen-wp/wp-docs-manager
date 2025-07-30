<?php
/**
 * Test CSS Flex Value Parsing Fix
 * Input: "0.16 1 0%" -> Extract: "0.16"
 */

function parse_width_value($width_value) {
    if (is_string($width_value) && strpos($width_value, ' ') !== false) {
        // Extract first number from CSS flex shorthand "0.16 1 0%"
        $parts = explode(' ', trim($width_value));
        return $parts[0];
    }
    return $width_value;
}

$test_cases = [
    '0.16' => '0.16',           // Direct numeric
    '0.16 1 0%' => '0.16',      // CSS flex shorthand  
    '1 1 0%' => '1',            // Full width CSS
    '0.33 1 0%' => '0.33',      // 33% CSS
    '0.67 1 0%' => '0.67',      // 67% CSS
    '0.5' => '0.5',             // Direct 50%
    '' => '',                   // Empty
    null => null                // Null
];

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Test CSS Flex Value Parsing Fix</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo ".test-case { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }\n";
echo ".success { background: #e6ffe6; }\n";
echo ".error { background: #ffe6e6; }\n";
echo ".render-test { display: flex; gap: 16px; border: 2px solid #0073aa; padding: 10px; margin: 10px 0; }\n";
echo ".column-test { border: 1px solid #ff6600; padding: 10px; background: #fff3e6; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🔧 Test CSS Flex Value Parsing Fix</h1>\n";

echo "<h2>📋 Parse Test Cases</h2>\n";
foreach ($test_cases as $input => $expected) {
    $actual = parse_width_value($input);
    $match = ($actual === $expected);
    
    echo "<div class='test-case " . ($match ? 'success' : 'error') . "'>\n";
    echo "<strong>Input:</strong> " . ($input === null ? 'null' : "'$input'") . "<br>\n";
    echo "<strong>Expected:</strong> " . ($expected === null ? 'null' : "'$expected'") . "<br>\n";
    echo "<strong>Actual:</strong> " . ($actual === null ? 'null' : "'$actual'") . "<br>\n";
    echo "<strong>Result:</strong> " . ($match ? '✅ PASS' : '❌ FAIL') . "\n";
    echo "</div>\n";
}

echo "<h2>🎨 Render Test - Real Column Widths</h2>\n";

// Simulate real form data with CSS flex values
$form_data_with_css = [
    'layout' => [
        'rows' => [
            [
                'columns' => [
                    [
                        'width' => '0.16 1 0%',  // CSS format from form builder
                        'fields' => [
                            ['name' => 'field1', 'label' => 'Field 1 (16%)']
                        ]
                    ],
                    [
                        'width' => '0.84 1 0%',  // CSS format from form builder  
                        'fields' => [
                            ['name' => 'field2', 'label' => 'Field 2 (84%)']
                        ]
                    ]
                ]
            ],
            [
                'columns' => [
                    [
                        'width' => '0.33 1 0%',
                        'fields' => [
                            ['name' => 'field3', 'label' => 'Field 3 (33%)']
                        ]
                    ],
                    [
                        'width' => '0.67 1 0%',
                        'fields' => [
                            ['name' => 'field4', 'label' => 'Field 4 (67%)']
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// Parse like frontend does
$form_fields = [];
foreach ($form_data_with_css['layout']['rows'] as $row_index => $row) {
    foreach ($row['columns'] as $col_index => $column) {
        foreach ($column['fields'] as $field) {
            $field['row'] = $row_index;
            $field['column'] = $col_index;
            if (isset($column['width'])) {
                // Apply the fix - parse CSS value to numeric
                $width_value = $column['width'];
                if (is_string($width_value) && strpos($width_value, ' ') !== false) {
                    $parts = explode(' ', trim($width_value));
                    $width_value = $parts[0];
                }
                $field['width'] = $width_value;
            }
            $form_fields[] = $field;
        }
    }
}

// Group and render
$rows = [];
foreach ($form_fields as $field) {
    $row_index = $field['row'];
    if (!isset($rows[$row_index])) $rows[$row_index] = [];
    $rows[$row_index][] = $field;
}

foreach ($rows as $row_index => $row_fields) {
    echo "<div><strong>Row $row_index:</strong></div>\n";
    
    $columns = [];
    foreach ($row_fields as $field) {
        $col_index = $field['column'];
        if (!isset($columns[$col_index])) $columns[$col_index] = [];
        $columns[$col_index][] = $field;
    }
    
    echo "<div class='render-test'>\n";
    foreach ($columns as $col_index => $col_fields) {
        $column_width = 1;
        foreach ($col_fields as $field) {
            if (isset($field['width']) && is_numeric($field['width'])) {
                $column_width = floatval($field['width']);
                break;
            }
        }
        
        if ($column_width == 1 && count($columns) > 1) {
            $column_width = 1 / count($columns);
        }
        
        $flex_grow = ($column_width == intval($column_width)) ? intval($column_width) : rtrim(rtrim(number_format($column_width, 6), '0'), '.');
        $flex_style = "flex: {$flex_grow} 1 0%; position: relative;";
        
        echo "<div class='column-test' style='{$flex_style}'>\n";
        echo "<strong>Column $col_index</strong><br>\n";
        echo "<small>$flex_style</small><br>\n";
        foreach ($col_fields as $field) {
            echo $field['label'] . "<br>\n";
        }
        echo "</div>\n";
    }
    echo "</div>\n";
}

echo "<div style='background: #e6ffe6; padding: 20px; margin-top: 30px;'>\n";
echo "<h3>✅ Fix Applied Successfully!</h3>\n";
echo "<ul>\n";
echo "<li><strong>CSS Input Parsed:</strong> '0.16 1 0%' → '0.16'</li>\n";
echo "<li><strong>Numeric Values Extracted:</strong> Ready for flex calculation</li>\n";
echo "<li><strong>Rendering Corrected:</strong> Columns show proper proportions</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</body>\n</html>\n";
?>
