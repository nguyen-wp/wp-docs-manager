<?php
/**
 * Test edit mode submission
 * Access: /wp-content/plugins/wp-docs-manager/test-edit-submission.php
 */

// Load WordPress
require_once('../../../wp-config.php');
require_once('class-lift-forms.php');

// Check login status
if (!is_user_logged_in()) {
    echo "<p>You need to be logged in. <a href='" . wp_login_url() . "'>Login here</a></p>";
    exit;
}

$current_user = wp_get_current_user();
echo "<h2>Edit Submission Test</h2>";
echo "<p><strong>Current User:</strong> {$current_user->display_name} (ID: {$current_user->ID})</p>";

// Get existing submissions for current user
global $wpdb;
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

$user_submissions = $wpdb->get_results($wpdb->prepare(
    "SELECT s.*, f.name as form_name FROM {$submissions_table} s 
     LEFT JOIN {$wpdb->prefix}lift_forms f ON s.form_id = f.id 
     WHERE s.user_id = %d 
     ORDER BY s.submitted_at DESC",
    $current_user->ID
));

if (empty($user_submissions)) {
    echo "<p>No submissions found for current user. Please submit a form first.</p>";
    exit;
}

echo "<h3>Your Submissions:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Form</th><th>Submitted</th><th>Updated</th><th>Action</th></tr>";
foreach ($user_submissions as $sub) {
    echo "<tr>";
    echo "<td>{$sub->id}</td>";
    echo "<td>{$sub->form_name}</td>";
    echo "<td>{$sub->submitted_at}</td>";
    echo "<td>" . ($sub->updated_at ?: 'Never') . "</td>";
    echo "<td><a href='?test_update={$sub->id}'>Test Update</a></td>";
    echo "</tr>";
}
echo "</table>";

// Handle test update
if (isset($_GET['test_update'])) {
    $submission_id = intval($_GET['test_update']);
    echo "<h3>Testing Update for Submission ID: {$submission_id}</h3>";
    
    // Simulate the AJAX request data
    $_POST = array(
        'nonce' => wp_create_nonce('lift_forms_submit_nonce'),
        'form_id' => $user_submissions[0]->form_id, // Use the first form
        'is_edit' => '1',
        'submission_id' => $submission_id,
        'form_fields' => array(
            'test_field' => 'Updated value: ' . current_time('mysql'),
            'another_field' => 'Test data ' . rand(1000, 9999)
        )
    );
    
    echo "<p><strong>Simulated POST data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    try {
        // Create forms instance and call the AJAX method directly
        $forms = new LIFT_Forms();
        
        // Capture output
        ob_start();
        $forms->ajax_submit_form();
        $output = ob_get_clean();
        
        echo "<p><strong>AJAX Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    }
    
    // Check if the record was actually updated
    $updated_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$submissions_table} WHERE id = %d",
        $submission_id
    ));
    
    echo "<h4>Record After Update:</h4>";
    echo "<pre>" . print_r($updated_record, true) . "</pre>";
}

?>
