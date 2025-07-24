<?php
/*
 * Quick Database Test for Form Submissions
 */

// Include WordPress
require_once '../../../wp-config.php';

echo "<h2>Database Test - Form Submissions</h2>";

global $wpdb;
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$submissions_table'");
echo "<p><strong>Table exists:</strong> " . ($table_exists ? 'Yes' : 'No') . "</p>";

if ($table_exists) {
    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE $submissions_table");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "<td>{$column->Default}</td>";
        echo "<td>{$column->Extra}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    $sample_data = $wpdb->get_results("SELECT * FROM $submissions_table ORDER BY submitted_at DESC LIMIT 3");
    echo "<h3>Sample Data (Latest 3):</h3>";
    if ($sample_data) {
        echo "<pre>";
        foreach ($sample_data as $row) {
            echo "ID: {$row->id}\n";
            echo "Form ID: {$row->form_id}\n";
            echo "User ID: {$row->user_id}\n";
            echo "Form Data: " . substr($row->form_data, 0, 200) . "...\n";
            echo "Submitted: {$row->submitted_at}\n";
            echo "---\n";
        }
        echo "</pre>";
    } else {
        echo "<p>No submissions found</p>";
    }
    
    // Test update query manually
    echo "<h3>Manual Update Test:</h3>";
    if ($sample_data && count($sample_data) > 0) {
        $test_submission = $sample_data[0];
        echo "<p>Testing update on submission ID: {$test_submission->id}</p>";
        
        $test_data = array(
            'form_data' => '{"test_field":"Updated at ' . date('Y-m-d H:i:s') . '"}',
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $submissions_table,
            $test_data,
            array('id' => $test_submission->id),
            array('%s', '%s'),
            array('%d')
        );
        
        echo "<p><strong>Update result:</strong> " . ($result !== false ? "Success (rows affected: $result)" : "Failed") . "</p>";
        if ($result === false) {
            echo "<p><strong>Error:</strong> " . $wpdb->last_error . "</p>";
        }
        echo "<p><strong>Last query:</strong> " . $wpdb->last_query . "</p>";
    }
}

// Test current user
$current_user = wp_get_current_user();
echo "<h3>Current User:</h3>";
echo "<p>ID: {$current_user->ID}</p>";
echo "<p>Login: {$current_user->user_login}</p>";
echo "<p>Is logged in: " . (is_user_logged_in() ? 'Yes' : 'No') . "</p>";

// Test LIFT_Forms class
echo "<h3>LIFT_Forms Class Test:</h3>";
if (class_exists('LIFT_Forms')) {
    $lift_forms = new LIFT_Forms();
    echo "<p>LIFT_Forms class loaded successfully</p>";
    
    // Test methods exist
    $methods = ['user_has_submitted_form', 'get_user_submission'];
    foreach ($methods as $method) {
        echo "<p>Method $method: " . (method_exists($lift_forms, $method) ? 'Exists' : 'Missing') . "</p>";
    }
} else {
    echo "<p>LIFT_Forms class not found</p>";
}
?>
