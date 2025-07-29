<?php
/**
 * Test file for LIFT Docs Post States functionality
 * 
 * This file can be used to test the post-state features we just implemented.
 * Simply include this file in WordPress admin to see the post states in action.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test Post States Implementation
 */
function lift_docs_test_post_states() {
    echo "<h2>LIFT Docs Post States Test</h2>";
    
    // Get the login and dashboard page IDs
    $login_page_id = get_option('lift_docs_login_page_id');
    $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
    
    echo "<h3>Page Information:</h3>";
    echo "<ul>";
    echo "<li>Login Page ID: " . ($login_page_id ? $login_page_id : 'Not set') . "</li>";
    echo "<li>Dashboard Page ID: " . ($dashboard_page_id ? $dashboard_page_id : 'Not set') . "</li>";
    echo "</ul>";
    
    // Test the frontend login class
    if (class_exists('LIFT_Docs_Frontend_Login')) {
        $frontend = new LIFT_Docs_Frontend_Login();
        
        echo "<h3>Post State Features Added:</h3>";
        echo "<ul>";
        echo "<li>✅ WordPress post states filter added (display_post_states)</li>";
        echo "<li>✅ Login form shortcode enhanced with state indicator</li>";
        echo "<li>✅ Dashboard shortcode enhanced with state indicator</li>";
        echo "<li>✅ Meta data setup for page identification</li>";
        echo "<li>✅ Admin notice system for post state updates</li>";
        echo "<li>✅ Utility methods for batch page updates</li>";
        echo "</ul>";
        
        // Test post state info for specific pages
        if ($login_page_id) {
            $login_state = $frontend->get_page_state_info($login_page_id);
            echo "<h4>Login Page State Info:</h4>";
            echo "<pre>" . print_r($login_state, true) . "</pre>";
        }
        
        if ($dashboard_page_id) {
            $dashboard_state = $frontend->get_page_state_info($dashboard_page_id);
            echo "<h4>Dashboard Page State Info:</h4>";
            echo "<pre>" . print_r($dashboard_state, true) . "</pre>";
        }
    }
    
    echo "<h3>Shortcode Examples:</h3>";
    echo "<p>Login Form with State: <code>[docs_login_form show_state='true']</code></p>";
    echo "<p>Dashboard with State: <code>[docs_dashboard show_state='true']</code></p>";
    echo "<p>Login Form without State: <code>[docs_login_form show_state='false']</code></p>";
    echo "<p>Dashboard without State: <code>[docs_dashboard show_state='false']</code></p>";
}

// Add admin page for testing (if in admin context)
if (is_admin() && current_user_can('manage_options')) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            'LIFT Docs Post States Test',
            'LIFT Docs Test',
            'manage_options',
            'lift-docs-test',
            'lift_docs_test_post_states'
        );
    });
}
