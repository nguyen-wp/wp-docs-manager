<?php
/**
 * Direct test for form update functionality
 * Access: /wp-content/plugins/wp-docs-manager/test-update-direct.php
 */

// Load WordPress
require_once('../../../wp-config.php');

// Force WordPress initialization
global $wpdb;

// Check if form submission table exists and has updated_at column
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

echo "<h2>Database Structure Check</h2>";

// Check table structure
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$submissions_table}");
echo "<h3>Table Columns:</h3>";
echo "<ul>";
foreach ($columns as $column) {
    echo "<li><strong>{$column->Field}</strong> - {$column->Type} - {$column->Null} - {$column->Key} - {$column->Default} - {$column->Extra}</li>";
}
echo "</ul>";

// Check if there are any submissions
$count = $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table}");
echo "<p><strong>Total submissions:</strong> {$count}</p>";

if ($count > 0) {
    $submissions = $wpdb->get_results("SELECT id, form_id, user_id, submitted_at, updated_at FROM {$submissions_table} ORDER BY id DESC LIMIT 5");
    echo "<h3>Recent Submissions:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Form ID</th><th>User ID</th><th>Submitted At</th><th>Updated At</th></tr>";
    foreach ($submissions as $sub) {
        echo "<tr>";
        echo "<td>{$sub->id}</td>";
        echo "<td>{$sub->form_id}</td>";
        echo "<td>{$sub->user_id}</td>";
        echo "<td>{$sub->submitted_at}</td>";
        echo "<td>{$sub->updated_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test a direct update
    $test_id = $submissions[0]->id;
    echo "<h3>Testing Direct Update on ID: {$test_id}</h3>";
    
    $test_data = array(
        'form_data' => json_encode(array('test_field' => 'updated_value_' . time())),
        'updated_at' => current_time('mysql')
    );
    
    echo "<p><strong>Update data:</strong> " . print_r($test_data, true) . "</p>";
    
    $result = $wpdb->update(
        $submissions_table,
        $test_data,
        array('id' => $test_id),
        array('%s', '%s'),
        array('%d')
    );
    
    echo "<p><strong>Update result:</strong> " . ($result !== false ? "Success (rows affected: {$result})" : "Failed") . "</p>";
    
    if ($result === false) {
        echo "<p><strong>Error:</strong> " . $wpdb->last_error . "</p>";
        echo "<p><strong>Last query:</strong> " . $wpdb->last_query . "</p>";
    }
    
    // Check the updated record
    $updated_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$submissions_table} WHERE id = %d", $test_id));
    echo "<h4>Updated Record:</h4>";
    echo "<pre>" . print_r($updated_record, true) . "</pre>";
}

echo "<h3>WordPress Database Info:</h3>";
echo "<p><strong>DB Version:</strong> " . $wpdb->db_version() . "</p>";
echo "<p><strong>WP Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>Table Prefix:</strong> " . $wpdb->prefix . "</p>";

?>
