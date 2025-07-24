<?php
// Reset demomo user password
require_once '../../../wp-load.php';

$user = get_user_by('login', 'demomo');

if (!$user) {
    echo "User demomo not found";
    exit;
}

echo "<h2>Resetting demomo user password</h2>";
echo "<p>User ID: {$user->ID}</p>";
echo "<p>Current username: {$user->user_login}</p>";

// Reset password to 'demomo'
$new_password = 'demomo';
wp_set_password($new_password, $user->ID);

echo "<p>✅ Password reset to: {$new_password}</p>";

// Test the password
$test_user = wp_authenticate($user->user_login, $new_password);
if (is_wp_error($test_user)) {
    echo "<p style='color: red;'>❌ Password test failed: " . $test_user->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Password test successful</p>";
}

// Also test wp_signon
$credentials = array(
    'user_login'    => $user->user_login,
    'user_password' => $new_password,
    'remember'      => false
);

$signon_test = wp_signon($credentials, false);
if (is_wp_error($signon_test)) {
    echo "<p style='color: red;'>❌ wp_signon test failed: " . $signon_test->get_error_message() . "</p>";
} else {
    echo "<p style='color: green;'>✅ wp_signon test successful</p>";
    wp_logout(); // Clean up
}
?>
