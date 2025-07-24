<?php
// Test demomo user permissions
require_once '../../../wp-load.php';

echo "<h2>Testing demomo user permissions</h2>";

$user = get_user_by('login', 'demomo');

if (!$user) {
    echo "<p style='color: red;'>❌ User demomo not found</p>";
    exit;
}

echo "<p>✅ User found: ID {$user->ID}</p>";
echo "<p>Username: {$user->user_login}</p>";
echo "<p>Email: {$user->user_email}</p>";

// Check roles
echo "<h3>User Roles:</h3>";
foreach ($user->roles as $role) {
    echo "<p>- {$role}</p>";
}

// Check specific capabilities
echo "<h3>Capability Tests:</h3>";

$capabilities = [
    'read',
    'view_lift_documents',
    'access_documents',
    'subscriber',
    'documents_user'
];

foreach ($capabilities as $cap) {
    $has_cap = user_can($user->ID, $cap);
    $status = $has_cap ? '✅' : '❌';
    echo "<p>{$status} {$cap}: " . ($has_cap ? 'YES' : 'NO') . "</p>";
}

// Check if user is in documents_user role
$in_documents_role = in_array('documents_user', $user->roles);
echo "<p>" . ($in_documents_role ? '✅' : '❌') . " In documents_user role: " . ($in_documents_role ? 'YES' : 'NO') . "</p>";

// Test the exact condition from the login handler
$has_access = in_array('documents_user', $user->roles) || user_can($user->ID, 'view_lift_documents');
echo "<h3>Final Access Check:</h3>";
echo "<p>" . ($has_access ? '✅' : '❌') . " Has document access: " . ($has_access ? 'YES' : 'NO') . "</p>";

// Check user meta
echo "<h3>User Meta:</h3>";
$user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
echo "<p>User Code: " . ($user_code ?: 'Not set') . "</p>";

// Check all user meta
$all_meta = get_user_meta($user->ID);
echo "<h4>All Meta:</h4>";
foreach ($all_meta as $key => $value) {
    echo "<p>{$key}: " . (is_array($value) ? implode(', ', $value) : $value) . "</p>";
}
?>
