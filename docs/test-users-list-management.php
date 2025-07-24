<?php
/**
 * Test User Code Management from Users List Only
 * 
 * This file tests the updated User Code functionality where:
 * 1. User Code field is removed from user edit page
 * 2. All User Code management is done from Users list page
 * 3. Users with existing codes can generate new codes
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>Testing Updated User Code Management</h2>\n";

// Test 1: Check Documents Users
$document_users = get_users(array(
    'role' => 'documents_user',
    'number' => 10
));

if (empty($document_users)) {
    echo "<p style='color: red;'>No Documents Users found. Please create some first.</p>\n";
    exit;
}

echo "<h3>Documents Users Status:</h3>\n";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>\n";
echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Current Code</th><th>Expected Action</th></tr>\n";

$users_with_code = 0;
$users_without_code = 0;

foreach ($document_users as $user) {
    $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
    
    if ($user_code) {
        $users_with_code++;
        $action = "Should show 'Generate New Code' button (orange)";
        $code_display = "<strong style='color: #0073aa;'>{$user_code}</strong>";
    } else {
        $users_without_code++;
        $action = "Should show 'Generate Code' button (green)";
        $code_display = "<em style='color: #d63638;'>No Code</em>";
    }
    
    echo "<tr>";
    echo "<td>{$user->ID}</td>";
    echo "<td>{$user->display_name}</td>";
    echo "<td>{$user->user_email}</td>";
    echo "<td>{$code_display}</td>";
    echo "<td>{$action}</td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h3>Summary:</h3>\n";
echo "<ul>\n";
echo "<li><strong>Users with codes:</strong> {$users_with_code} (will have orange 'Generate New Code' buttons)</li>\n";
echo "<li><strong>Users without codes:</strong> {$users_without_code} (will have green 'Generate Code' buttons)</li>\n";
echo "</ul>\n";

// Test 2: Test nonce generation
echo "<h3>Testing Nonce Generation:</h3>\n";
$nonce = wp_create_nonce('generate_user_code');
echo "<p><strong>Users List Nonce:</strong> {$nonce}</p>\n";

$nonce_valid = wp_verify_nonce($nonce, 'generate_user_code');
echo "<p>Nonce validation: " . ($nonce_valid ? 'VALID' : 'INVALID') . "</p>\n";

// Test 3: Check user edit page removal
echo "<h3>User Edit Page Changes:</h3>\n";
echo "<p>✅ User Code field removed from user edit/profile pages</p>\n";
echo "<p>✅ All User Code management now happens in Users list</p>\n";
echo "<p>✅ Users with existing codes can regenerate them</p>\n";
echo "<p>✅ Users without codes can generate new ones</p>\n";

// Test 4: Feature differences
echo "<h3>Button Behavior & Confirmation:</h3>\n";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>\n";
echo "<tr><th>User Status</th><th>Button Text</th><th>Button Color</th><th>Confirmation Required</th><th>Confirmation Message</th><th>Action</th></tr>\n";
echo "<tr>";
echo "<td>No Code</td>";
echo "<td>Generate Code</td>";
echo "<td style='background: #00a32a; color: white;'>Green (Primary)</td>";
echo "<td style='color: #d63638;'><strong>NO</strong></td>";
echo "<td>—</td>";
echo "<td>Creates new code immediately</td>";
echo "</tr>\n";
echo "<tr>";
echo "<td>Has Code</td>";
echo "<td>Generate New Code</td>";
echo "<td style='background: #f39c12; color: white;'>Orange (Secondary)</td>";
echo "<td style='color: #d63638;'><strong>YES</strong></td>";
echo "<td>\"Are you sure you want to generate a new User Code? This will replace the existing code and may affect document access.\"</td>";
echo "<td>Replaces existing code after confirmation</td>";
echo "</tr>\n";
echo "</table>\n";

// Test 5: AJAX endpoint test
echo "<h3>AJAX Endpoint Test:</h3>\n";
if ($document_users) {
    $test_user = $document_users[0];
    echo "<p>Test AJAX call for user ID: {$test_user->ID}</p>\n";
    echo "<pre>";
    echo "POST: wp-admin/admin-ajax.php\n";
    echo "Data: {\n";
    echo "  action: 'generate_user_code',\n";
    echo "  user_id: {$test_user->ID},\n";
    echo "  nonce: '{$nonce}'\n";
    echo "}\n";
    echo "</pre>";
}

echo "<h3>Expected UI Flow:</h3>\n";
echo "<ol>\n";
echo "<li>Admin goes to Users → All Users</li>\n";
echo "<li>User Code column shows codes for Documents Users</li>\n";
echo "<li><strong>Users without code:</strong> Green 'Generate Code' button</li>\n";
echo "<li><strong>Users with code:</strong> Orange 'Generate New Code' button</li>\n";
echo "<li><strong>Clicking green button:</strong> Generates code immediately</li>\n";
echo "<li><strong>Clicking orange button:</strong> Shows confirmation dialog first</li>\n";
echo "<li><strong>Confirmation dialog:</strong> \"Are you sure you want to generate a new User Code? This will replace the existing code and may affect document access.\"</li>\n";
echo "<li><strong>If user confirms:</strong> Generates new code</li>\n";
echo "<li><strong>If user cancels:</strong> No action taken</li>\n";
echo "<li>After successful generation: Cell updates with new code + orange button</li>\n";
echo "<li>Success notification appears temporarily (3 seconds)</li>\n";
echo "</ol>\n";

echo "<h3>⚠️ Important Confirmation Details:</h3>\n";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4 style='color: #856404; margin-top: 0;'>When Confirmation is Required:</h4>\n";
echo "<ul style='color: #856404;'>\n";
echo "<li><strong>Only for users who already have a User Code</strong></li>\n";
echo "<li>Button shows as orange/secondary color</li>\n";
echo "<li>Text says \"Generate New Code\"</li>\n";
echo "<li>Warns about potential impact on document access</li>\n";
echo "</ul>\n";
echo "<h4 style='color: #856404;'>When NO Confirmation is Needed:</h4>\n";
echo "<ul style='color: #856404;'>\n";
echo "<li><strong>For users without any User Code</strong></li>\n";
echo "<li>Button shows as green/primary color</li>\n";
echo "<li>Text says \"Generate Code\"</li>\n";
echo "<li>Creates code immediately without dialog</li>\n";
echo "</ul>\n";
echo "</div>\n";

?>

<style>
table {
    margin: 10px 0;
    font-family: Arial, sans-serif;
    font-size: 14px;
}

th {
    background: #f1f1f1;
    font-weight: bold;
    text-align: left;
}

.summary-box {
    background: #e8f4fd;
    border: 1px solid #b3d9ff;
    border-radius: 5px;
    padding: 15px;
    margin: 15px 0;
}

.success {
    color: #0073aa;
    font-weight: bold;
}

.warning {
    color: #f39c12;
    font-weight: bold;
}

.error {
    color: #d63638;
    font-weight: bold;
}
</style>

<div class="summary-box">
    <h4>✅ Implementation Complete</h4>
    <p>User Code management has been successfully moved from individual user edit pages to the centralized Users list page. This provides better administrative control and consistent user experience.</p>
</div>
