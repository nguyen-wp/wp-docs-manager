<?php
/**
 * Test Fixed Frontend with Full Flex Notation
 * Now matches backend: flex: X 1 0%; position: relative;
 */

// Test scenarios with various column configurations
$test_scenarios = [
    [
        'name' => 'Backend Example: 16% / 84%',
        'columns' => [
            ['width' => '0.16'], // 16%
            ['width' => '0.84']  // 84%
        ]
    ],
    [
        'name' => 'Common: 33% / 67%', 
        'columns' => [
            ['width' => '0.33'], // 33%
            ['width' => '0.67']  // 67%
        ]
    ],
    [
        'name' => 'Three Equal Columns (Auto)',
        'columns' => [
            [], [], [] // No width specified = auto
        ]
    ],
    [
        'name' => 'Two Equal Columns (Auto)',
        'columns' => [
            [], [] // No width specified = auto
        ]
    ]
];

// Simulate the FIXED frontend logic
function render_fixed_columns($columns) {
    $total_columns = count($columns);
    
    echo '<div class="form-row" style="display: flex; gap: 16px; margin-bottom: 16px; border: 2px solid #0073aa; padding: 10px;">';
    
    foreach ($columns as $col_index => $column) {
        // FIXED LOGIC - exactly like updated backend
        $column_width = 1; // Default flex value as number
        
        // Check if column has custom width
        if (isset($column['width']) && is_numeric($column['width'])) {
            $column_width = floatval($column['width']);
        }
        
        // Calculate default width based on number of columns if no custom width
        if ($column_width == 1 && $total_columns > 1) {
            $column_width = 1 / $total_columns;
        }
        
        // Format for clean output - remove unnecessary decimals  
        $flex_grow = ($column_width == intval($column_width)) ? intval($column_width) : rtrim(rtrim(number_format($column_width, 6), '0'), '.');
        
        // Use full flex notation like backend: flex-grow flex-shrink flex-basis
        $flex_style = "flex: {$flex_grow} 1 0%; position: relative;";
        
        echo '<div class="form-column" style="' . $flex_style . ' border: 1px solid #ff6600; padding: 10px; background: #fff3e6;">';
        echo '<div style="background: #e6f3ff; padding: 5px; font-size: 12px; margin-bottom: 5px;">Column ' . ($col_index + 1) . '</div>';
        echo '<div style="background: #ffffcc; padding: 3px; font-size: 11px;">' . $flex_style . '</div>';
        echo '<div>Sample field content here...</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Test Fixed Frontend - Full Flex Notation</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; }\n";
echo ".test-scenario { margin: 30px 0; }\n";
echo ".comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<div class='container'>\n";
echo "<h1>🎯 Test Fixed Frontend - Full Flex Notation</h1>\n";

foreach ($test_scenarios as $scenario) {
    echo "<div class='test-scenario'>\n";
    echo "<h3>📊 " . $scenario['name'] . "</h3>\n";
    
    render_fixed_columns($scenario['columns']);
    
    echo "</div>\n";
}

echo "<div style='background: #e6ffe6; padding: 20px; margin-top: 30px;'>\n";
echo "<h3>✅ Fixed Issues:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Full flex notation:</strong> <code>flex: 0.16 1 0%</code> instead of <code>flex: 0.16</code></li>\n";
echo "<li><strong>Added position:</strong> <code>position: relative</code> to match backend</li>\n";
echo "<li><strong>Consistent with form builder:</strong> Same format as admin panel</li>\n";
echo "<li><strong>Better browser compatibility:</strong> Explicit flex-shrink and flex-basis</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #fffacd; padding: 20px; margin-top: 20px;'>\n";
echo "<h3>🔍 Before vs After:</h3>\n";
echo "<div class='comparison'>\n";
echo "<div>\n";
echo "<h4>❌ Before (Wrong):</h4>\n";
echo "<code>style=\"flex: 0.5;\"</code>\n";
echo "</div>\n";
echo "<div>\n";
echo "<h4>✅ After (Correct):</h4>\n";
echo "<code>style=\"flex: 0.5 1 0%; position: relative;\"</code>\n";  
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n</html>\n";
?>
