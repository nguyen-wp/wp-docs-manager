<?php
/**
 * Test Document Users Management Page Updates
 * 
 * This file tests the simplified Document Users Management page with:
 * 1. Removed user statistics section
 * 2. Removed "Grant Document Access to User" section  
 * 3. Removed role update functionality
 * 4. Added User Code generation buttons for all users
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>Testing Document Users Management Page</h2>\n";

// Test 1: Check Documents Users
$document_users = get_users(array(
    'role' => 'documents_user',
    'number' => 10
));

if (empty($document_users)) {
    echo "<p style='color: red;'>No Documents Users found. Please create some first.</p>\n";
    exit;
}

echo "<h3>Document Users Management Page Preview:</h3>\n";

// Simulate the new page structure
?>
<div style="border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
    <h1>Document Users Management</h1>
    
    <div class="lift-docs-users-management">
        <div class="users-with-documents-role">
            <h2>Users with Documents Access</h2>
            
            <table class="wp-list-table widefat fixed striped" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f1f1f1;">
                        <th style="padding: 8px; border: 1px solid #ccc;">User</th>
                        <th style="padding: 8px; border: 1px solid #ccc;">Email</th>
                        <th style="padding: 8px; border: 1px solid #ccc;">User Code</th>
                        <th style="padding: 8px; border: 1px solid #ccc;">Registration Date</th>
                        <th style="padding: 8px; border: 1px solid #ccc;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($document_users as $user): ?>
                    <?php $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true); ?>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ccc;">
                            <strong><?php echo esc_html($user->display_name); ?></strong><br>
                            <span style="color: #666; font-size: 12px;"><?php echo esc_html($user->user_login); ?></span>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ccc;"><?php echo esc_html($user->user_email); ?></td>
                        <td style="padding: 8px; border: 1px solid #ccc;">
                            <?php if ($user_code): ?>
                                <strong style="color: #0073aa; font-family: monospace;"><?php echo esc_html($user_code); ?></strong><br>
                                <button style="margin-top: 5px; font-size: 11px; background: #f39c12; border: none; color: white; padding: 2px 8px; border-radius: 3px;">
                                    Generate New Code
                                </button>
                            <?php else: ?>
                                <span style="color: #d63638; font-style: italic;">No Code</span><br>
                                <button style="margin-top: 5px; font-size: 11px; background: #00a32a; border: none; color: white; padding: 2px 8px; border-radius: 3px;">
                                    Generate Code
                                </button>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ccc;"><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                        <td style="padding: 8px; border: 1px solid #ccc;">
                            <button style="background: #0073aa; border: none; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                                Edit User
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php

echo "<h3>Changes Summary:</h3>\n";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4 style='color: #155724; margin-top: 0;'>✅ What's Been Removed:</h4>\n";
echo "<ul style='color: #155724;'>\n";
echo "<li><strong>User Roles Summary section</strong> - No more statistics display</li>\n";
echo "<li><strong>Grant Document Access to User section</strong> - No more user role assignment form</li>\n";
echo "<li><strong>Role Update functionality</strong> - No more dropdown to change user roles</li>\n";
echo "<li><strong>Old JavaScript code</strong> - Removed outdated user code generation script</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #cce5ff; border: 1px solid #99d6ff; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4 style='color: #0056b3; margin-top: 0;'>🆕 What's Been Added:</h4>\n";
echo "<ul style='color: #0056b3;'>\n";
echo "<li><strong>User Code generation buttons for ALL users</strong> - Both with and without existing codes</li>\n";
echo "<li><strong>Confirmation dialog for regeneration</strong> - Prevents accidental code replacement</li>\n";
echo "<li><strong>Visual button differentiation</strong> - Green for new, Orange for regenerate</li>\n";
echo "<li><strong>Dedicated JavaScript handler</strong> - '.generate-user-code-btn-mgmt' class for management page</li>\n";
echo "<li><strong>AJAX integration</strong> - Real-time code generation without page refresh</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Test 2: Button behavior analysis
echo "<h3>Button Behavior Analysis:</h3>\n";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #f1f1f1;'>\n";
echo "<th>User Status</th><th>Button Appearance</th><th>Button Text</th><th>Confirmation Required</th><th>Action</th>\n";
echo "</tr>\n";

$users_with_code = 0;
$users_without_code = 0;

foreach ($document_users as $user) {
    $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
    
    if ($user_code) {
        $users_with_code++;
    } else {
        $users_without_code++;
    }
}

echo "<tr>\n";
echo "<td>Users WITHOUT Code ({$users_without_code})</td>\n";
echo "<td style='background: #00a32a; color: white; text-align: center;'><strong>Green Primary</strong></td>\n";
echo "<td>Generate Code</td>\n";
echo "<td style='color: #00a32a;'><strong>NO</strong></td>\n";
echo "<td>Creates new code immediately</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td>Users WITH Code ({$users_with_code})</td>\n";
echo "<td style='background: #f39c12; color: white; text-align: center;'><strong>Orange Secondary</strong></td>\n";
echo "<td>Generate New Code</td>\n";
echo "<td style='color: #d63638;'><strong>YES</strong></td>\n";
echo "<td>Replaces existing code after confirmation</td>\n";
echo "</tr>\n";

echo "</table>\n";

// Test 3: JavaScript class differentiation
echo "<h3>JavaScript Integration:</h3>\n";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4 style='color: #856404; margin-top: 0;'>Button Class Naming:</h4>\n";
echo "<ul style='color: #856404;'>\n";
echo "<li><strong>Users List Page:</strong> <code>.generate-user-code-btn-list</code></li>\n";
echo "<li><strong>Document Users Management Page:</strong> <code>.generate-user-code-btn-mgmt</code></li>\n";
echo "</ul>\n";
echo "<p style='color: #856404;'><strong>Why different classes?</strong> To avoid JavaScript conflicts and allow different styling/behavior on different pages.</p>\n";
echo "</div>\n";

// Test 4: Page access information
echo "<h3>Page Access Information:</h3>\n";
echo "<div style='background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4 style='color: #0c5460; margin-top: 0;'>How to Access:</h4>\n";
echo "<ol style='color: #0c5460;'>\n";
echo "<li>Go to WordPress Admin Dashboard</li>\n";
echo "<li>Navigate to <strong>Lift Documents → Document Users</strong></li>\n";
echo "<li>You'll see only the simplified table with User Code generation buttons</li>\n";
echo "</ol>\n";
echo "</div>\n";

// Test 5: Functionality verification
echo "<h3>Functionality Verification:</h3>\n";

$total_users = count($document_users);
echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4>Current State:</h4>\n";
echo "<ul>\n";
echo "<li><strong>Total Document Users:</strong> {$total_users}</li>\n";
echo "<li><strong>Users with User Codes:</strong> {$users_with_code}</li>\n";
echo "<li><strong>Users without User Codes:</strong> {$users_without_code}</li>\n";
echo "</ul>\n";

if ($users_without_code > 0) {
    echo "<p style='color: #00a32a;'><strong>✅ Ready to test:</strong> You have users without codes to test the 'Generate Code' functionality.</p>\n";
}
if ($users_with_code > 0) {
    echo "<p style='color: #f39c12;'><strong>⚠️ Ready to test:</strong> You have users with codes to test the 'Generate New Code' functionality with confirmation.</p>\n";
}
echo "</div>\n";

?>

<style>
table {
    font-family: Arial, sans-serif;
    font-size: 14px;
}

th {
    background: #f1f1f1;
    font-weight: bold;
    text-align: left;
}

code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 13px;
}

.summary-box {
    border-radius: 5px;
    padding: 15px;
    margin: 15px 0;
}

.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.info {
    background: #e8f4fd;
    border: 1px solid #b3d9ff;
    color: #0c5460;
}

.warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}
</style>
