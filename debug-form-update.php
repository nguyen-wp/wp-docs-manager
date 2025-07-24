<?php
/*
 * Debug Form Submission Update
 * Place this file in wp-content/plugins/wp-docs-manager/debug-form-update.php
 */

// Include WordPress
require_once '../../../wp-config.php';

// Force login as admin for testing
if (!current_user_can('manage_options')) {
    echo "Please login as admin first";
    exit;
}

echo "<h2>Debug Form Submission Update</h2>";

global $wpdb;
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

// Get recent submissions
$recent_submissions = $wpdb->get_results("
    SELECT s.*, f.name as form_name, u.display_name 
    FROM {$submissions_table} s 
    LEFT JOIN {$wpdb->prefix}lift_forms f ON s.form_id = f.id 
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
    ORDER BY s.submitted_at DESC 
    LIMIT 5
");

echo "<h3>Recent Submissions:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Form</th><th>User</th><th>User ID</th><th>Submitted</th><th>Actions</th></tr>";

foreach ($recent_submissions as $submission) {
    echo "<tr>";
    echo "<td>{$submission->id}</td>";
    echo "<td>{$submission->form_name}</td>";
    echo "<td>{$submission->display_name}</td>";
    echo "<td>{$submission->user_id}</td>";
    echo "<td>{$submission->submitted_at}</td>";
    echo "<td>";
    echo "<button onclick='testUpdate({$submission->id}, {$submission->form_id}, {$submission->user_id})'>Test Update</button>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Get current user info
$current_user = wp_get_current_user();
echo "<h3>Current User Info:</h3>";
echo "<p>User ID: {$current_user->ID}</p>";
echo "<p>User Login: {$current_user->user_login}</p>";
echo "<p>Display Name: {$current_user->display_name}</p>";

?>

<script>
function testUpdate(submissionId, formId, userId) {
    console.log('Testing update for submission:', submissionId);
    
    var formData = new FormData();
    formData.append('action', 'lift_forms_submit');
    formData.append('nonce', '<?php echo wp_create_nonce('lift_forms_submit_nonce'); ?>');
    formData.append('form_id', formId);
    formData.append('document_id', '1'); // Test document ID
    formData.append('submission_id', submissionId);
    formData.append('is_edit', '1');
    formData.append('form_fields[test_field]', 'Updated at ' + new Date().toLocaleString());
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            alert('Update successful: ' + data.data.message);
        } else {
            alert('Update failed: ' + data.data);
            console.error('Error details:', data);
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('Network error: ' + error.message);
    });
}
</script>

<style>
table { border: 1px solid #ddd; margin: 20px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
button { padding: 5px 10px; background: #0073aa; color: white; border: none; cursor: pointer; }
button:hover { background: #005a87; }
</style>

<?php
// Check error log
echo "<h3>Recent Error Log (last 50 lines):</h3>";
$error_log_file = ini_get('error_log');
if (file_exists($error_log_file)) {
    $lines = file($error_log_file);
    $recent_lines = array_slice($lines, -50);
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>";
    foreach ($recent_lines as $line) {
        if (strpos($line, 'LIFT') !== false || strpos($line, 'lift_forms') !== false) {
            echo "<strong style='color: red;'>" . htmlspecialchars($line) . "</strong>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log file not found: " . $error_log_file . "</p>";
}
?>
