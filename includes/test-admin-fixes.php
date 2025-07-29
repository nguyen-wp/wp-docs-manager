<?php
/**
 * Test file to verify admin fixes implementation
 * This file checks if all the admin enhancements are properly implemented
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Simple test to verify key components
function lift_test_admin_fixes() {
    $results = array();
    
    // Test 1: Check if LIFT_Forms class has the new AJAX methods
    if (class_exists('LIFT_Forms')) {
        $lift_forms = new LIFT_Forms();
        $results['ajax_methods'] = array(
            'ajax_update_form_status' => method_exists($lift_forms, 'ajax_update_form_status'),
            'ajax_update_submission_status' => method_exists($lift_forms, 'ajax_update_submission_status')
        );
    }
    
    // Test 2: Check if admin page permissions are in place
    $admin_class = LIFT_Docs_Admin::get_instance();
    $results['admin_class'] = !empty($admin_class);
    
    // Test 3: Check database structure for forms status
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    $columns = $wpdb->get_results("DESCRIBE $forms_table");
    $has_status_column = false;
    foreach ($columns as $column) {
        if ($column->Field === 'status') {
            $has_status_column = true;
            break;
        }
    }
    $results['forms_status_column'] = $has_status_column;
    
    // Test 4: Check submissions table structure
    $submissions_table = $wpdb->prefix . 'lift_form_submissions';
    $sub_columns = $wpdb->get_results("DESCRIBE $submissions_table");
    $has_sub_status_column = false;
    foreach ($sub_columns as $column) {
        if ($column->Field === 'status') {
            $has_sub_status_column = true;
            break;
        }
    }
    $results['submissions_status_column'] = $has_sub_status_column;
    
    return $results;
}

// Display test results in admin
add_action('admin_notices', function() {
    if (isset($_GET['test_admin_fixes']) && current_user_can('manage_options')) {
        $results = lift_test_admin_fixes();
        ?>
        <div class="notice notice-info">
            <h3>Admin Fixes Test Results</h3>
            <ul>
                <?php foreach ($results as $test => $result): ?>
                    <li>
                        <strong><?php echo esc_html($test); ?>:</strong> 
                        <span style="color: <?php echo $result ? 'green' : 'red'; ?>">
                            <?php echo $result ? 'PASS' : 'FAIL'; ?>
                        </span>
                        <?php if (is_array($result)): ?>
                            <ul>
                                <?php foreach ($result as $sub_test => $sub_result): ?>
                                    <li><?php echo esc_html($sub_test); ?>: 
                                        <span style="color: <?php echo $sub_result ? 'green' : 'red'; ?>">
                                            <?php echo $sub_result ? 'PASS' : 'FAIL'; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><em>Test completed on <?php echo date('Y-m-d H:i:s'); ?></em></p>
        </div>
        <?php
    }
});

// Add test link to admin bar for testing
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (current_user_can('manage_options')) {
        $wp_admin_bar->add_node(array(
            'id' => 'test-admin-fixes',
            'title' => 'Test Admin Fixes',
            'href' => admin_url('admin.php?page=lift-forms&test_admin_fixes=1'),
        ));
    }
}, 100);
