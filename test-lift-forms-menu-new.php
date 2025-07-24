<?php
/**
 * Test LIFT Forms Menu Integration - New Implementation
 * 
 * Run this file to check if LIFT Forms menu is properly integrated
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

echo "<h2>LIFT Forms Menu Integration Test - New Implementation</h2>";

// Check if main menu exists
global $menu, $submenu;

echo "<h3>Main Menu Items:</h3>";
echo "<pre>";
foreach ($menu as $menu_item) {
    if (strpos($menu_item[2], 'lift-docs') !== false) {
        print_r($menu_item);
    }
}
echo "</pre>";

echo "<h3>LIFT Docs Submenu Items:</h3>";
if (isset($submenu['lift-docs-system'])) {
    echo "<pre>";
    print_r($submenu['lift-docs-system']);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No submenu found for 'lift-docs-system'</p>";
}

echo "<h3>LIFT Forms Class Status:</h3>";
if (class_exists('LIFT_Forms')) {
    echo "<p style='color: green;'>✅ LIFT_Forms class exists</p>";
    
    // Test if we can create an instance
    try {
        $lift_forms = new LIFT_Forms();
        echo "<p style='color: green;'>✅ LIFT_Forms instance created successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating LIFT_Forms instance: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ LIFT_Forms class does not exist</p>";
}

echo "<h3>LIFT Docs Admin Class Status:</h3>";
if (class_exists('LIFT_Docs_Admin')) {
    echo "<p style='color: green;'>✅ LIFT_Docs_Admin class exists</p>";
    
    // Check if the new methods exist
    $admin = LIFT_Docs_Admin::get_instance();
    if (method_exists($admin, 'forms_admin_page')) {
        echo "<p style='color: green;'>✅ forms_admin_page method exists</p>";
    } else {
        echo "<p style='color: red;'>❌ forms_admin_page method missing</p>";
    }
    
    if (method_exists($admin, 'forms_builder_page')) {
        echo "<p style='color: green;'>✅ forms_builder_page method exists</p>";
    } else {
        echo "<p style='color: red;'>❌ forms_builder_page method missing</p>";
    }
    
    if (method_exists($admin, 'forms_submissions_page')) {
        echo "<p style='color: green;'>✅ forms_submissions_page method exists</p>";
    } else {
        echo "<p style='color: red;'>❌ forms_submissions_page method missing</p>";
    }
} else {
    echo "<p style='color: red;'>❌ LIFT_Docs_Admin class does not exist</p>";
}

echo "<h3>Recommendations:</h3>";
echo "<ol>";
echo "<li>Go to WordPress admin dashboard</li>";
echo "<li>Look for 'LIFT Docs' in the admin sidebar</li>";
echo "<li>You should see: Forms, Form Builder, Submissions as submenu items</li>";
echo "<li>If not visible, try deactivating and reactivating the plugin</li>";
echo "<li>Clear any caching if you're using cache plugins</li>";
echo "</ol>";

echo "<h3>Direct Menu URLs to Test:</h3>";
$admin_url = admin_url();
echo "<ul>";
echo "<li><a href='{$admin_url}admin.php?page=lift-forms' target='_blank'>Forms List</a></li>";
echo "<li><a href='{$admin_url}admin.php?page=lift-forms-builder' target='_blank'>Form Builder</a></li>";
echo "<li><a href='{$admin_url}admin.php?page=lift-forms-submissions' target='_blank'>Form Submissions</a></li>";
echo "</ul>";
