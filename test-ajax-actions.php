<?php
/**
 * Test AJAX actions registration
 */

// Load WordPress environment
$wp_config_path = '../../../wp-config.php';
if (file_exists($wp_config_path)) {
    require_once($wp_config_path);
} else {
    die('WordPress not found');
}

// Ensure user is logged in
if (!is_user_logged_in()) {
    wp_die('Please login first');
}

echo "<h1>AJAX Actions Registration Test</h1>\n";

// Check if we're in admin or AJAX context
echo "<h2>Context Information</h2>\n";
echo "<p><strong>is_admin():</strong> " . (is_admin() ? 'TRUE' : 'FALSE') . "</p>\n";
echo "<p><strong>DOING_AJAX:</strong> " . (defined('DOING_AJAX') && DOING_AJAX ? 'TRUE' : 'FALSE') . "</p>\n";

// Check global $wp_filter for our action
global $wp_filter;
echo "<h2>Registered AJAX Actions</h2>\n";

$ajax_action = 'wp_ajax_search_document_users';
if (isset($wp_filter[$ajax_action])) {
    echo "<p><strong>$ajax_action:</strong> REGISTERED</p>\n";
    echo "<pre>";
    print_r($wp_filter[$ajax_action]);
    echo "</pre>";
} else {
    echo "<p><strong>$ajax_action:</strong> NOT REGISTERED</p>\n";
}

// Check if LIFT_Docs_Admin class exists and is instantiated
echo "<h2>Class Status</h2>\n";
echo "<p><strong>LIFT_Docs_Admin class exists:</strong> " . (class_exists('LIFT_Docs_Admin') ? 'YES' : 'NO') . "</p>\n";

if (class_exists('LIFT_Docs_Admin')) {
    // Try to get instance
    try {
        $admin_instance = LIFT_Docs_Admin::get_instance();
        echo "<p><strong>LIFT_Docs_Admin instance:</strong> SUCCESS</p>\n";
        echo "<p><strong>Instance class:</strong> " . get_class($admin_instance) . "</p>\n";
        
        // Check if method exists
        if (method_exists($admin_instance, 'ajax_search_document_users')) {
            echo "<p><strong>ajax_search_document_users method:</strong> EXISTS</p>\n";
        } else {
            echo "<p><strong>ajax_search_document_users method:</strong> NOT EXISTS</p>\n";
        }
    } catch (Exception $e) {
        echo "<p><strong>LIFT_Docs_Admin instance:</strong> ERROR - " . $e->getMessage() . "</p>\n";
    }
}

// Check all AJAX actions that start with wp_ajax_
echo "<h2>All AJAX Actions</h2>\n";
$ajax_actions = array();
foreach ($wp_filter as $hook => $callbacks) {
    if (strpos($hook, 'wp_ajax_') === 0) {
        $ajax_actions[] = $hook;
    }
}

if (!empty($ajax_actions)) {
    echo "<ul>\n";
    foreach ($ajax_actions as $action) {
        echo "<li>$action</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p>No AJAX actions found!</p>\n";
}

// Check LIFT specific actions
echo "<h2>LIFT Docs AJAX Actions</h2>\n";
$lift_actions = array_filter($ajax_actions, function($action) {
    return strpos($action, 'lift') !== false || strpos($action, 'search_document_users') !== false;
});

if (!empty($lift_actions)) {
    echo "<ul>\n";
    foreach ($lift_actions as $action) {
        echo "<li><strong>$action</strong></li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p>No LIFT Docs AJAX actions found!</p>\n";
}

echo "<p style='margin-top: 30px; color: #666;'>Test completed at " . current_time('mysql') . "</p>\n";
?>
