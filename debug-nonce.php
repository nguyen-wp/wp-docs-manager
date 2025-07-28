<?php
/**
 * Debug script để kiểm tra nonce issues
 * Chỉ chạy khi có debug parameter
 */

// Chỉ chạy khi có debug mode enabled
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

add_action('wp_ajax_debug_nonce_check', function() {
    error_log('DEBUG: Checking nonce for AJAX form save');
    
    $nonce = $_POST['nonce'] ?? '';
    error_log('DEBUG: Received nonce: ' . $nonce);
    
    $valid_forms_nonce = wp_verify_nonce($nonce, 'lift_forms_nonce');
    $valid_builder_nonce = wp_verify_nonce($nonce, 'lift_form_builder_nonce');
    
    error_log('DEBUG: Valid lift_forms_nonce: ' . ($valid_forms_nonce ? 'YES' : 'NO'));
    error_log('DEBUG: Valid lift_form_builder_nonce: ' . ($valid_builder_nonce ? 'YES' : 'NO'));
    
    // Kiểm tra current nonce values
    $current_forms_nonce = wp_create_nonce('lift_forms_nonce');
    $current_builder_nonce = wp_create_nonce('lift_form_builder_nonce');
    
    error_log('DEBUG: Current lift_forms_nonce: ' . $current_forms_nonce);
    error_log('DEBUG: Current lift_form_builder_nonce: ' . $current_builder_nonce);
    
    wp_send_json_success(array(
        'received_nonce' => $nonce,
        'valid_forms_nonce' => $valid_forms_nonce,
        'valid_builder_nonce' => $valid_builder_nonce,
        'current_forms_nonce' => $current_forms_nonce,
        'current_builder_nonce' => $current_builder_nonce
    ));
});

// Thêm debug info vào console JavaScript
add_action('admin_footer', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'lift-forms') !== false) {
        ?>
        <script>
        console.log('LIFT Forms Debug Info:');
        console.log('liftForms nonce:', typeof liftForms !== 'undefined' ? liftForms.nonce : 'NOT DEFINED');
        console.log('liftFormBuilder nonce:', typeof liftFormBuilder !== 'undefined' ? liftFormBuilder.nonce : 'NOT DEFINED');
        
        // Test nonce validation
        if (typeof liftForms !== 'undefined' && liftForms.nonce) {
            jQuery.post(ajaxurl, {
                action: 'debug_nonce_check',
                nonce: liftForms.nonce
            }, function(response) {
                console.log('liftForms nonce test result:', response);
            });
        }
        
        if (typeof liftFormBuilder !== 'undefined' && liftFormBuilder.nonce) {
            jQuery.post(ajaxurl, {
                action: 'debug_nonce_check', 
                nonce: liftFormBuilder.nonce
            }, function(response) {
                console.log('liftFormBuilder nonce test result:', response);
            });
        }
        </script>
        <?php
    }
});

// Thêm error logging cho tất cả AJAX requests của plugin
add_action('wp_ajax_lift_forms_save', function() {
    error_log('DEBUG: AJAX lift_forms_save called');
    error_log('DEBUG: POST data: ' . print_r($_POST, true));
}, 1);
?>
