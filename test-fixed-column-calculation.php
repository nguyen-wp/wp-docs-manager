<?php
/**
 * Test Fixed Column Width Calculation
 * Verify the improved logic works correctly
 */

// Test the improved calculation logic
function calculate_flex_value($column_width, $total_columns) {
    // Default flex value as number
    if (!isset($column_width) || !is_numeric($column_width)) {
        $column_width = 1;
    } else {
        $column_width = floatval($column_width);
    }
    
    // Calculate default width based on number of columns if no custom width
    if ($column_width == 1 && $total_columns > 1) {
        $column_width = 1 / $total_columns;
    }
    
    // Format for clean output - remove unnecessary decimals
    $flex_value = ($column_width == intval($column_width)) ? intval($column_width) : rtrim(rtrim(number_format($column_width, 6), '0'), '.');
    
    return $flex_value;
}

$test_cases = [
    [
        'name' => 'Two equal columns',
        'columns' => [null, null],
        'expected' => ['0.5', '0.5']
    ],
    [
        'name' => 'Three equal columns', 
        'columns' => [null, null, null],
        'expected' => ['0.333333', '0.333333', '0.333333']
    ],
    [
        'name' => 'Custom 33% / 67%',
        'columns' => ['0.33', '0.67'],
        'expected' => ['0.33', '0.67']
    ],
    [
        'name' => 'Single full column',
        'columns' => [null],
        'expected' => ['1']
    ],
    [
        'name' => 'Four equal columns',
        'columns' => [null, null, null, null],
        'expected' => ['0.25', '0.25', '0.25', '0.25']
    ],
    [
        'name' => 'Mixed: 0.5 + auto + auto',
        'columns' => ['0.5', null, null],
        'expected' => ['0.5', '0.333333', '0.333333']
    ]
];

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Test Fixed Column Width Calculation</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo ".test-case { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }\n";
echo ".form-row { display: flex; gap: 16px; margin: 10px 0; border: 1px solid #0073aa; padding: 10px; }\n";
echo ".form-column { border: 1px solid #ff6600; padding: 10px; background: #f9f9f9; min-height: 40px; }\n";
echo ".results { background: #f0f8ff; padding: 10px; margin: 10px 0; font-family: monospace; }\n";
echo ".success { color: green; font-weight: bold; }\n";
echo ".error { color: red; font-weight: bold; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🧪 Test Fixed Column Width Calculation</h1>\n";

foreach ($test_cases as $test) {
    echo "<div class='test-case'>\n";
    echo "<h3>📋 " . $test['name'] . "</h3>\n";
    
    $columns = $test['columns'];
    $total_columns = count($columns);
    
    echo "<div class='form-row'>\n";
    
    $actual_results = [];
    foreach ($columns as $col_index => $column_width) {
        $flex_value = calculate_flex_value($column_width, $total_columns);
        $actual_results[] = $flex_value;
        
        echo "<div class='form-column' style='flex: " . $flex_value . ";'>\n";
        echo "Column " . ($col_index + 1) . "<br>";
        echo "flex: " . $flex_value . "\n";
        echo "</div>\n";
    }
    
    echo "</div>\n";
    
    // Compare results
    echo "<div class='results'>\n";
    echo "<strong>Input:</strong> [" . implode(', ', array_map(function($v) { return $v ?? 'null'; }, $columns)) . "]<br>\n";
    echo "<strong>Expected:</strong> [" . implode(', ', $test['expected']) . "]<br>\n";
    echo "<strong>Actual:</strong> [" . implode(', ', $actual_results) . "]<br>\n";
    
    // Check if results match (with some tolerance for floating point)
    $matches = true;
    for ($i = 0; $i < count($test['expected']); $i++) {
        $expected = floatval($test['expected'][$i]);
        $actual = floatval($actual_results[$i]);
        if (abs($expected - $actual) > 0.000001) {
            $matches = false;
            break;
        }
    }
    
    if ($matches) {
        echo "<span class='success'>✅ PASS</span>\n";
    } else {
        echo "<span class='error'>❌ FAIL</span>\n";
    }
    
    echo "</div>\n";
    echo "</div>\n";
}

echo "<div style='background: #e6ffe6; padding: 15px; margin-top: 30px;'>\n";
echo "<h3>🎯 Key Improvements:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Clean numbers:</strong> '0.5' instead of '0.500'</li>\n";
echo "<li><strong>Better precision:</strong> Proper handling of 1/3 = 0.333333</li>\n";
echo "<li><strong>Integer handling:</strong> Full width shows as '1' not '1.0'</li>\n";
echo "<li><strong>Flexbox compatible:</strong> Clean values that flexbox understands</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</body>\n</html>\n";
?>
