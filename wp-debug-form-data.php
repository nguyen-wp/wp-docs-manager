<?php
/**
 * WordPress Debug Script - Check Real Form Data
 * Place this in WordPress root and access via browser
 * Example: yoursite.com/debug-form-data.php?form_id=29
 */

// Load WordPress
require_once('wp-load.php');

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 29;

?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Form Data Debug - Form ID: <?php echo $form_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 2px solid #ddd; }
        .json-block { background: #f5f5f5; padding: 10px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; max-height: 400px; }
        .success { background: #e6ffe6; color: #006600; padding: 8px; margin: 5px 0; }
        .error { background: #ffe6e6; color: #cc0000; padding: 8px; margin: 5px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 8px; margin: 5px 0; }
        .render-test { border: 2px solid #0073aa; padding: 10px; margin: 10px 0; display: flex; gap: 16px; }
        .column-test { border: 1px solid #ff6600; padding: 8px; background: #fff3e6; }
    </style>
</head>
<body>

<h1>🔍 WordPress Form Data Debug - Form ID: <?php echo $form_id; ?></h1>

<?php
// Get form post
$form_post = get_post($form_id);

if (!$form_post) {
    echo "<div class='error'>❌ Form ID $form_id not found!</div>";
    exit;
}

echo "<div class='debug-section'>";
echo "<h3>📋 Form Post Data</h3>";
echo "<div class='success'>✅ Form found: " . $form_post->post_title . "</div>";
echo "<div>Post Type: " . $form_post->post_type . "</div>";
echo "<div>Post Status: " . $form_post->post_status . "</div>";
echo "</div>";

// Get form content (structure)
$form_content = $form_post->post_content;

echo "<div class='debug-section'>";
echo "<h3>📄 Raw Post Content</h3>";
echo "<div class='json-block'>" . htmlspecialchars($form_content) . "</div>";
echo "</div>";

// Try to parse as JSON
$parsed_data = json_decode($form_content, true);

echo "<div class='debug-section'>";
echo "<h3>🧩 Parsed JSON Data</h3>";

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<div class='error'>❌ JSON Parse Error: " . json_last_error_msg() . "</div>";
} else {
    echo "<div class='success'>✅ JSON parsed successfully</div>";
    echo "<div class='json-block'>" . json_encode($parsed_data, JSON_PRETTY_PRINT) . "</div>";
}
echo "</div>";

// Check for layout structure
echo "<div class='debug-section'>";
echo "<h3>🏗️ Layout Structure Analysis</h3>";

if (isset($parsed_data['layout'])) {
    echo "<div class='success'>✅ Found 'layout' key</div>";
    
    if (isset($parsed_data['layout']['rows'])) {
        echo "<div class='success'>✅ Found 'layout.rows' - " . count($parsed_data['layout']['rows']) . " rows</div>";
        
        foreach ($parsed_data['layout']['rows'] as $row_index => $row) {
            echo "<div style='margin-left: 20px;'>";
            echo "<strong>Row $row_index:</strong><br>";
            
            if (isset($row['columns'])) {
                echo "<div class='success'>✅ Found " . count($row['columns']) . " columns</div>";
                
                foreach ($row['columns'] as $col_index => $column) {
                    echo "<div style='margin-left: 40px;'>";
                    echo "<strong>Column $col_index:</strong><br>";
                    
                    if (isset($column['width'])) {
                        echo "<div class='success'>✅ Width: " . $column['width'] . "</div>";
                    } else {
                        echo "<div class='warning'>⚠️ No width property</div>";
                    }
                    
                    if (isset($column['fields'])) {
                        echo "<div class='success'>✅ Fields: " . count($column['fields']) . "</div>";
                    } else {
                        echo "<div class='warning'>⚠️ No fields</div>";
                    }
                    
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>❌ No columns in row</div>";
            }
            echo "</div>";
        }
    } else {
        echo "<div class='error'>❌ No 'layout.rows' found</div>";
    }
} else {
    echo "<div class='error'>❌ No 'layout' key found</div>";
    echo "<div class='warning'>⚠️ This might be legacy format or different structure</div>";
}

echo "</div>";

// Simulate frontend parsing
echo "<div class='debug-section'>";
echo "<h3>⚙️ Frontend Parsing Simulation</h3>";

$form_fields = array();

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
                            echo "<div class='success'>✅ Field '{$field['name']}' assigned width: {$column['width']}</div>";
                        } else {
                            echo "<div class='warning'>⚠️ Field '{$field['name']}' has no width</div>";
                        }
                        $form_fields[] = $field;
                    }
                }
            }
        }
    }
} else {
    echo "<div class='error'>❌ Cannot parse layout structure</div>";
}

echo "<div><strong>Total parsed fields:</strong> " . count($form_fields) . "</div>";
echo "</div>";

// Render test
if (!empty($form_fields)) {
    echo "<div class='debug-section'>";
    echo "<h3>🎨 Render Test</h3>";
    
    // Group by rows
    $rows = array();
    foreach ($form_fields as $field) {
        $row_index = isset($field['row']) ? $field['row'] : 0;
        if (!isset($rows[$row_index])) {
            $rows[$row_index] = array();
        }
        $rows[$row_index][] = $field;
    }
    
    foreach ($rows as $row_index => $row_fields) {
        echo "<div><strong>Row $row_index:</strong></div>";
        
        // Group by columns
        $columns = array();
        foreach ($row_fields as $field) {
            $col_index = isset($field['column']) ? $field['column'] : 0;
            if (!isset($columns[$col_index])) {
                $columns[$col_index] = array();
            }
            $columns[$col_index][] = $field;
        }
        
        echo "<div class='render-test'>";
        foreach ($columns as $col_index => $col_fields) {
            // Calculate width like frontend does
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
            
            echo "<div class='column-test' style='{$flex_style}'>";
            echo "<strong>Column $col_index</strong><br>";
            echo "<small>$flex_style</small><br>";
            foreach ($col_fields as $field) {
                echo $field['label'] ?? $field['name'] ?? 'Unknown field';
                echo "<br>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "</div>";
}
?>

<div class="debug-section" style="background: #e6ffe6;">
    <h3>🎯 Action Items</h3>
    <ol>
        <li><strong>If no width properties found:</strong> Form builder is not saving width data correctly</li>
        <li><strong>If width properties exist but render equally:</strong> Frontend parsing logic issue</li>
        <li><strong>If JSON parse fails:</strong> Data corruption or different storage format</li>
        <li><strong>If layout.rows missing:</strong> Form uses legacy structure or different format</li>
    </ol>
</div>

</body>
</html>
