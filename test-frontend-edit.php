<?php
/**
 * Simple frontend form test for edit mode
 * Access: /wp-content/plugins/wp-docs-manager/test-frontend-edit.php
 */

// Load WordPress
require_once('../../../wp-config.php');

// Ensure user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
}

get_header();
?>

<style>
.test-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
}
.form-container {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccc;
}
.debug-info {
    background: #ffffcc;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ffcc00;
    font-family: monospace;
    font-size: 12px;
}
</style>

<div class="test-container">
    <h1>Frontend Form Edit Test</h1>
    
    <?php
    $current_user = wp_get_current_user();
    echo "<div class='debug-info'>";
    echo "<strong>Current User:</strong> {$current_user->display_name} (ID: {$current_user->ID})<br>";
    echo "<strong>User Roles:</strong> " . implode(', ', $current_user->roles) . "<br>";
    echo "<strong>Time:</strong> " . current_time('mysql');
    echo "</div>";
    
    // Get user's submissions
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'lift_form_submissions';
    $forms_table = $wpdb->prefix . 'lift_forms';
    
    $user_submissions = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, f.name as form_name, f.form_fields 
         FROM {$submissions_table} s 
         LEFT JOIN {$forms_table} f ON s.form_id = f.id 
         WHERE s.user_id = %d 
         ORDER BY s.submitted_at DESC 
         LIMIT 5",
        $current_user->ID
    ));
    
    if (empty($user_submissions)) {
        echo "<p>No submissions found. Please submit a form first through the document system.</p>";
    } else {
        echo "<h2>Your Recent Submissions:</h2>";
        
        foreach ($user_submissions as $submission) {
            $form_data = json_decode($submission->form_data, true);
            $form_fields = json_decode($submission->form_fields, true);
            
            echo "<div class='form-container'>";
            echo "<h3>Form: {$submission->form_name} (ID: {$submission->id})</h3>";
            echo "<p><strong>Submitted:</strong> {$submission->submitted_at}</p>";
            if ($submission->updated_at) {
                echo "<p><strong>Last Updated:</strong> {$submission->updated_at}</p>";
            }
            
            // Show current data
            echo "<h4>Current Data:</h4>";
            echo "<ul>";
            if ($form_data) {
                foreach ($form_data as $key => $value) {
                    if (strpos($key, '_') !== 0) { // Skip meta fields
                        echo "<li><strong>{$key}:</strong> " . esc_html($value) . "</li>";
                    }
                }
            }
            echo "</ul>";
            
            // Simple edit form
            echo "<h4>Edit Form:</h4>";
            echo "<form class='edit-form' data-submission-id='{$submission->id}' data-form-id='{$submission->form_id}'>";
            echo wp_nonce_field('lift_forms_submit_nonce', 'lift_forms_nonce', true, false);
            
            if ($form_fields && is_array($form_fields)) {
                foreach ($form_fields as $field) {
                    $field_name = $field['name'] ?? '';
                    $field_label = $field['label'] ?? $field_name;
                    $current_value = $form_data[$field_name] ?? '';
                    
                    echo "<p>";
                    echo "<label><strong>{$field_label}:</strong></label><br>";
                    echo "<input type='text' name='{$field_name}' value='" . esc_attr($current_value) . "' style='width: 100%; padding: 5px;' />";
                    echo "</p>";
                }
            }
            
            echo "<button type='submit' style='background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Update Submission</button>";
            echo "</form>";
            
            echo "<div class='form-messages' style='margin-top: 10px;'></div>";
            echo "</div>";
        }
    }
    ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(document).ready(function($) {
    $('.edit-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $messages = $form.siblings('.form-messages');
        var submissionId = $form.data('submission-id');
        var formId = $form.data('form-id');
        
        // Collect form data
        var formData = {};
        $form.find('input[name]').each(function() {
            formData[$(this).attr('name')] = $(this).val();
        });
        
        console.log('Submitting edit:', {
            submission_id: submissionId,
            form_id: formId,
            form_fields: formData
        });
        
        $messages.html('<p style="color: blue;">Updating...</p>');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'lift_forms_submit',
                nonce: $form.find('[name="lift_forms_nonce"]').val(),
                form_id: formId,
                submission_id: submissionId,
                is_edit: '1',
                form_fields: formData
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                if (response.success) {
                    $messages.html('<p style="color: green;">' + response.data.message + '</p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $messages.html('<p style="color: red;">Error: ' + (response.data.message || response.data || 'Unknown error') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.responseText);
                $messages.html('<p style="color: red;">Network error: ' + error + '</p>');
            }
        });
    });
});
</script>

<?php get_footer(); ?>
