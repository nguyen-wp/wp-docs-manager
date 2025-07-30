<?php
/**
 * Debug Frontend Column Width Issues
 * Check what's actually being output in HTML
 */

// Simulate different column scenarios
$test_scenarios = [
    'custom_widths' => [
        'description' => 'Custom widths (33% / 67%)',
        'columns' => [
            ['width' => '0.33'],
            ['width' => '0.67']
        ]
    ],
    'two_equal' => [
        'description' => 'Two equal columns (auto)',
        'columns' => [
            [],
            []
        ]
    ],
    'three_equal' => [
        'description' => 'Three equal columns (auto)',
        'columns' => [
            [],
            [],
            []
        ]
    ],
    'mixed' => [
        'description' => 'Mixed: custom + auto',
        'columns' => [
            ['width' => '0.25'],
            [],
            []
        ]
    ]
];

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Debug Column Width Issues</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; }\n";
echo ".test-scenario { margin: 30px 0; padding: 20px; border: 2px solid #ddd; }\n";
echo ".form-row { display: flex; gap: 16px; margin: 10px 0; border: 1px solid #0073aa; padding: 10px; }\n";
echo ".form-column { border: 1px solid #ff6600; padding: 10px; background: #f9f9f9; min-height: 50px; }\n";
echo ".debug-info { background: #ffffcc; padding: 5px; font-size: 12px; margin-bottom: 5px; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🐛 Debug Column Width Issues</h1>\n";

foreach ($test_scenarios as $key => $scenario) {
    echo "<div class='test-scenario'>\n";
    echo "<h3>📊 " . $scenario['description'] . "</h3>\n";
    
    $columns = $scenario['columns'];
    
    echo "<div class='form-row'>\n";
    
    foreach ($columns as $col_index => $column) {
        // Replicate the current logic
        $column_width = '1'; // Default flex value
        
        // Check if column has custom width
        if (isset($column['width']) && is_numeric($column['width'])) {
            $column_width = $column['width'];
        }
        
        // Calculate default width based on number of columns if no custom width
        if ($column_width === '1' && count($columns) > 1) {
            $column_width = number_format(1 / count($columns), 3);
        }
        
        echo "<div class='form-column' style='flex: " . $column_width . ";'>\n";
        echo "<div class='debug-info'>Column $col_index</div>\n";
        echo "<div class='debug-info'>flex: $column_width</div>\n";
        
        // Show what this translates to in percentage
        $percentage = round(floatval($column_width) * 100, 1);
        echo "<div class='debug-info'>~{$percentage}%</div>\n";
        
        echo "<div>Sample content here...</div>\n";
        echo "</div>\n";
    }
    
    echo "</div>\n";
    
    // Show HTML output
    echo "<h4>Generated HTML:</h4>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 11px;'>";
    
    foreach ($columns as $col_index => $column) {
        $column_width = '1';
        if (isset($column['width']) && is_numeric($column['width'])) {
            $column_width = $column['width'];
        }
        if ($column_width === '1' && count($columns) > 1) {
            $column_width = number_format(1 / count($columns), 3);
        }
        
        echo htmlspecialchars('<div class="form-column" style="flex: ' . $column_width . ';">') . "\n";
    }
    
    echo "</pre>\n";
    
    echo "</div>\n";
}

echo "<div style='background: #ffe6e6; padding: 15px; margin-top: 30px;'>\n";
echo "<h3>🚨 Potential Issues Found:</h3>\n";
echo "<ul>\n";
echo "<li><strong>number_format(1/2, 3)</strong> = '0.500' - Extra trailing zeros</li>\n";
echo "<li><strong>number_format(1/3, 3)</strong> = '0.333' - Not exactly 1/3</li>\n";
echo "<li><strong>Flexbox expects simple numbers</strong> - '0.5' better than '0.500'</li>\n";
echo "<li><strong>Mixed scenarios</strong> - What happens when 1 custom + 2 auto?</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #e6ffe6; padding: 15px; margin-top: 20px;'>\n";
echo "<h3>💡 Recommended Fixes:</h3>\n";
echo "<ul>\n";
echo "<li>Remove number_format() for cleaner values</li>\n";
echo "<li>Use direct division: 1/count() without formatting</li>\n";
echo "<li>Handle mixed scenarios properly</li>\n";
echo "<li>Test with real flexbox behavior</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</body>\n</html>\n";
?>
