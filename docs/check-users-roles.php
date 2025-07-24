<?php
/**
 * Check Users and Roles
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔍 Users and Roles Check</h2>\n";

// Check if documents_user role exists
$roles = wp_roles();
if (isset($roles->roles['documents_user'])) {
    echo "<p>✅ 'documents_user' role exists</p>\n";
} else {
    echo "<p>❌ 'documents_user' role does not exist</p>\n";
    echo "<p>Available roles: " . implode(', ', array_keys($roles->roles)) . "</p>\n";
}

// Check all users
$all_users = get_users();
echo "<h3>All Users:</h3>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Roles</th><th>User Code</th></tr>\n";

foreach ($all_users as $user) {
    $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
    echo "<tr>\n";
    echo "<td>" . $user->ID . "</td>\n";
    echo "<td>" . esc_html($user->user_login) . "</td>\n";
    echo "<td>" . esc_html($user->user_email) . "</td>\n";
    echo "<td>" . implode(', ', $user->roles) . "</td>\n";
    echo "<td>" . ($user_code ? esc_html($user_code) : '-') . "</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

// Check for documents users specifically
$documents_users = get_users(array('role' => 'documents_user'));
if (!empty($documents_users)) {
    echo "<h3>Documents Users:</h3>\n";
    foreach ($documents_users as $user) {
        $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
        echo "<p>- <strong>" . esc_html($user->display_name) . "</strong> (ID: " . $user->ID . ")<br>\n";
        echo "  Username: " . esc_html($user->user_login) . "<br>\n";
        echo "  Email: " . esc_html($user->user_email) . "<br>\n";
        if ($user_code) {
            echo "  User Code: " . esc_html($user_code) . "<br>\n";
        }
        echo "</p>\n";
    }
} else {
    echo "<h3>No Documents Users Found</h3>\n";
    echo "<p>You need to create some documents users first.</p>\n";
    echo "<p><a href='" . admin_url('admin.php?page=lift-docs-users') . "' target='_blank'>Go to Document Users Management</a></p>\n";
}

echo "<hr>\n";
echo "<h3>Test Emergency Login:</h3>\n";
echo "<p><a href='" . home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php') . "' target='_blank'>Emergency Login Page</a></p>\n";

if (!empty($documents_users)) {
    echo "<p><strong>You can login with any of the documents users listed above.</strong></p>\n";
} else {
    echo "<p><strong>You need to create documents users first before testing login.</strong></p>\n";
}
?>
