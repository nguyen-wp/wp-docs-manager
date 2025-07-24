<?php
/**
 * Test and fix user permissions for document access
 * Access: /wp-content/plugins/wp-docs-manager/fix-user-permissions.php
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    echo "Access denied. You must be an administrator.";
    exit;
}

echo "<h2>User Permissions Fix</h2>";

$current_user = wp_get_current_user();
echo "<p><strong>Current User:</strong> {$current_user->display_name} (ID: {$current_user->ID})</p>";
echo "<p><strong>Current Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";

// Check current capabilities
$has_view_docs = current_user_can('view_lift_documents');
$has_role = in_array('documents_user', $current_user->roles);
echo "<p><strong>Has 'view_lift_documents' capability:</strong> " . ($has_view_docs ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>Has 'documents_user' role:</strong> " . ($has_role ? '✅ Yes' : '❌ No') . "</p>";

// If user doesn't have access, let's add it
if (!$has_view_docs && !$has_role) {
    echo "<h3>Adding Document Access:</h3>";
    
    // Option 1: Add capability to current user
    $current_user->add_cap('view_lift_documents');
    echo "<p>✅ Added 'view_lift_documents' capability to current user</p>";
    
    // Option 2: Add documents_user role to current user (if role exists)
    if (get_role('documents_user')) {
        $current_user->add_role('documents_user');
        echo "<p>✅ Added 'documents_user' role to current user</p>";
    } else {
        echo "<p>⚠️ 'documents_user' role doesn't exist</p>";
    }
    
    // Option 3: Add capability to administrator role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('view_lift_documents');
        echo "<p>✅ Added 'view_lift_documents' capability to administrator role</p>";
    }
    
    echo "<p><strong>✅ Permissions updated! Please refresh and test again.</strong></p>";
} else {
    echo "<p>✅ User already has document access!</p>";
}

// Show all available roles
echo "<h3>Available Roles:</h3>";
$roles = wp_roles()->roles;
echo "<ul>";
foreach ($roles as $role_name => $role_info) {
    $role_capabilities = array_keys($role_info['capabilities']);
    $has_docs_cap = in_array('view_lift_documents', $role_capabilities);
    echo "<li><strong>{$role_name}</strong>: " . $role_info['name'];
    if ($has_docs_cap) {
        echo " <span style='color: green;'>✅ Has document access</span>";
    }
    echo "</li>";
}
echo "</ul>";

// Test dashboard access
echo "<h3>Test Dashboard Access:</h3>";
require_once('includes/class-lift-docs-frontend-login.php');
$frontend = new LIFT_Docs_Frontend_Login();

// Re-check access after potential updates
$user_has_access = $frontend->user_has_docs_access();
echo "<p><strong>User has document access:</strong> " . ($user_has_access ? '✅ Yes' : '❌ No') . "</p>";

if ($user_has_access) {
    echo "<div style='background: #e7f7e7; padding: 15px; border: 1px solid #4caf50; border-radius: 5px;'>";
    echo "<p><strong>✅ Great! You should now be able to access the dashboard.</strong></p>";
    echo "<p><a href='" . home_url('/document-dashboard/') . "' target='_blank'>Test Dashboard Access →</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff4444; border-radius: 5px;'>";
    echo "<p><strong>❌ Still no access. Let's debug further.</strong></p>";
    echo "</div>";
}

?>

<style>
h2, h3 { 
    color: #333; 
    border-bottom: 1px solid #ddd; 
    padding-bottom: 5px; 
}
</style>
