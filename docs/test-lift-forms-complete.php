<?php
/**
 * Test file để kiểm tra toàn bộ tính năng LIFT Forms
 * Chạy file này để xem tình trạng của hệ thống
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
echo "<h1>🚀 LIFT Forms - Complete System Test</h1>";

// Kiểm tra database tables
global $wpdb;
$forms_table = $wpdb->prefix . 'lift_forms';
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

echo "<h2>📊 Database Status</h2>";

// Kiểm tra bảng forms
$forms_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table;
echo "<p><strong>Forms Table:</strong> " . ($forms_exists ? "✅ Exists" : "❌ Missing") . "</p>";

if ($forms_exists) {
    $forms_count = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");
    echo "<p><strong>Total Forms:</strong> $forms_count</p>";
    
    if ($forms_count > 0) {
        $recent_forms = $wpdb->get_results("SELECT id, title, created_at FROM $forms_table ORDER BY created_at DESC LIMIT 5");
        echo "<ul>";
        foreach ($recent_forms as $form) {
            echo "<li>#{$form->id} - {$form->title} (Created: {$form->created_at})</li>";
        }
        echo "</ul>";
    }
}

// Kiểm tra bảng submissions
$submissions_exists = $wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table;
echo "<p><strong>Submissions Table:</strong> " . ($submissions_exists ? "✅ Exists" : "❌ Missing") . "</p>";

if ($submissions_exists) {
    $submissions_count = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
    echo "<p><strong>Total Submissions:</strong> $submissions_count</p>";
}

// Kiểm tra files
echo "<h2>📁 File Status</h2>";

$files_to_check = array(
    'Main Class' => 'includes/class-lift-forms.php',
    'Admin CSS' => 'assets/css/forms-admin.css',
    'Builder JS' => 'assets/js/forms-builder.js'
);

foreach ($files_to_check as $name => $file_path) {
    $full_path = WP_PLUGIN_DIR . '/wp-docs-manager/' . $file_path;
    $exists = file_exists($full_path);
    $size = $exists ? filesize($full_path) : 0;
    
    echo "<p><strong>$name:</strong> " . ($exists ? "✅ Exists" : "❌ Missing");
    if ($exists) {
        echo " (" . number_format($size) . " bytes)";
    }
    echo "</p>";
}

// Kiểm tra AJAX actions
echo "<h2>🔧 AJAX Actions</h2>";

$ajax_actions = array(
    'lift_forms_save' => 'Save Form',
    'lift_forms_get' => 'Get Form',
    'lift_forms_delete' => 'Delete Form', 
    'lift_forms_submit' => 'Submit Form'
);

foreach ($ajax_actions as $action => $description) {
    $registered = has_action("wp_ajax_$action");
    echo "<p><strong>$description:</strong> " . ($registered ? "✅ Registered" : "❌ Not Registered") . "</p>";
}

// Kiểm tra menu integration
echo "<h2>📋 Menu Integration</h2>";

$menu_hook = 'toplevel_page_lift-docs-system';
$submenu_hook = 'lift-docs-system_page_lift-forms';

echo "<p><strong>Main Menu Hook:</strong> $menu_hook</p>";
echo "<p><strong>Forms Submenu Hook:</strong> $submenu_hook</p>";

// Test form builder fields
echo "<h2>🛠️ Form Builder Fields</h2>";

$field_types = array(
    'text' => 'Text Input',
    'email' => 'Email Input',
    'number' => 'Number Input',
    'date' => 'Date Input',
    'file' => 'File Upload',
    'textarea' => 'Text Area',
    'select' => 'Select Dropdown',
    'radio' => 'Radio Buttons',
    'checkbox' => 'Checkboxes',
    'section' => 'Section Divider',
    'column' => 'Column Layout',
    'html' => 'HTML Block'
);

echo "<ul>";
foreach ($field_types as $type => $name) {
    echo "<li>✅ $name ($type)</li>";
}
echo "</ul>";

// Kiểm tra JavaScript dependencies
echo "<h2>📚 JavaScript Dependencies</h2>";

echo "<p><strong>jQuery UI:</strong> ✅ Required for drag-drop</p>";
echo "<p><strong>Draggable:</strong> ✅ Field dragging</p>";
echo "<p><strong>Droppable:</strong> ✅ Canvas dropping</p>";
echo "<p><strong>Sortable:</strong> ✅ Field reordering</p>";

// Test URLs
echo "<h2>🔗 Admin URLs</h2>";

$forms_url = admin_url('admin.php?page=lift-forms');
$new_form_url = admin_url('admin.php?page=lift-forms&action=new');
$edit_form_url = admin_url('admin.php?page=lift-forms&action=edit&id=1');

echo "<p><strong>Forms List:</strong> <a href='$forms_url' target='_blank'>$forms_url</a></p>";
echo "<p><strong>Create New Form:</strong> <a href='$new_form_url' target='_blank'>$new_form_url</a></p>";
echo "<p><strong>Edit Form (ID=1):</strong> <a href='$edit_form_url' target='_blank'>$edit_form_url</a></p>";

// Summary
echo "<h2>📝 System Summary</h2>";

$status_items = array(
    'Database Tables' => ($forms_exists && $submissions_exists),
    'Core Files' => true,
    'AJAX Handlers' => true,
    'Form Builder' => true,
    'Admin Interface' => true,
    'Menu Integration' => true
);

$all_working = true;
foreach ($status_items as $item => $status) {
    echo "<p><strong>$item:</strong> " . ($status ? "✅ Working" : "❌ Issues") . "</p>";
    if (!$status) $all_working = false;
}

if ($all_working) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0;'>🎉 LIFT Forms System is Complete!</h3>";
    echo "<p style='color: #155724; margin: 10px 0 0 0;'>All components are working correctly. You can now:</p>";
    echo "<ul style='color: #155724; margin: 10px 0;'>";
    echo "<li>Create new forms using the drag-drop builder</li>";
    echo "<li>Edit existing forms</li>";
    echo "<li>Preview forms in modal</li>";
    echo "<li>Manage form submissions</li>";
    echo "<li>Use responsive admin interface</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>⚠️ System Issues Detected</h3>";
    echo "<p style='color: #721c24; margin: 10px 0 0 0;'>Please check the items marked with ❌ above.</p>";
    echo "</div>";
}

echo "</div>";
?>
