<?php
// Temporary debug script for checking submissions
// Remove this file after debugging

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

echo "<h1>Debug Submissions Database</h1>";

$submissions_table = $wpdb->prefix . 'lift_form_submissions';
$forms_table = $wpdb->prefix . 'lift_forms';

echo "<h2>Table Information</h2>";
echo "<p><strong>Submissions table:</strong> $submissions_table</p>";
echo "<p><strong>Forms table:</strong> $forms_table</p>";

// Check if tables exist
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}lift_%'");
echo "<h3>LIFT Tables in Database:</h3>";
echo "<ul>";
foreach ($tables as $table) {
    $table_name = array_values((array)$table)[0];
    echo "<li>$table_name</li>";
}
echo "</ul>";

// Check submissions table structure
echo "<h3>Submissions Table Structure:</h3>";
$columns = $wpdb->get_results("DESCRIBE $submissions_table");
if ($columns) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col->Field}</td>";
        echo "<td>{$col->Type}</td>";
        echo "<td>{$col->Null}</td>";
        echo "<td>{$col->Key}</td>";
        echo "<td>{$col->Default}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Table $submissions_table does not exist!</p>";
}

// Count submissions
$count = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
echo "<h3>Total Submissions: $count</h3>";

if ($count > 0) {
    echo "<h3>Sample Submissions:</h3>";
    $samples = $wpdb->get_results("SELECT * FROM $submissions_table ORDER BY submitted_at DESC LIMIT 5");
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr><th>ID</th><th>Form ID</th><th>User ID</th><th>Status</th><th>Submitted At</th><th>Form Data (first 100 chars)</th></tr>";
    
    foreach ($samples as $submission) {
        echo "<tr>";
        echo "<td>{$submission->id}</td>";
        echo "<td>{$submission->form_id}</td>";
        echo "<td>{$submission->user_id}</td>";
        echo "<td>{$submission->status}</td>";
        echo "<td>{$submission->submitted_at}</td>";
        echo "<td>" . substr($submission->form_data, 0, 100) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check forms table
echo "<h3>Forms Table:</h3>";
$forms_count = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");
echo "<p>Total forms: $forms_count</p>";

if ($forms_count > 0) {
    $forms = $wpdb->get_results("SELECT id, name, status FROM $forms_table");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
    foreach ($forms as $form) {
        echo "<tr><td>{$form->id}</td><td>{$form->name}</td><td>{$form->status}</td></tr>";
    }
    echo "</table>";
}

// Test the query used in submissions page
echo "<h3>Test Submissions Query:</h3>";
$test_query = "SELECT s.*, f.name as form_name, u.display_name as user_name, u.user_email as user_email
               FROM $submissions_table s 
               LEFT JOIN $forms_table f ON s.form_id = f.id 
               LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
               WHERE 1=1 
               ORDER BY s.submitted_at DESC";

echo "<p><strong>Query:</strong> $test_query</p>";

$test_results = $wpdb->get_results($test_query);
echo "<p><strong>Results count:</strong> " . count($test_results) . "</p>";

if ($wpdb->last_error) {
    echo "<p style='color: red;'><strong>Query Error:</strong> {$wpdb->last_error}</p>";
}

if (!empty($test_results)) {
    echo "<h4>Query Results:</h4>";
    echo "<pre>" . print_r($test_results, true) . "</pre>";
}

echo "<p><a href='/wp-admin/admin.php?page=lift-forms-submissions'>Go to Submissions Page</a></p>";
?>
