<?php
/**
 * Nonce verification test
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

// Test nonce generation and verification
$action = 'search_document_users';
echo "<h1>Nonce Test for: $action</h1>\n";

// Generate multiple nonces
echo "<h2>Nonce Generation Test</h2>\n";
$nonce1 = wp_create_nonce($action);
$nonce2 = wp_create_nonce($action);
$nonce3 = wp_create_nonce($action);

echo "<p><strong>Nonce 1:</strong> $nonce1</p>\n";
echo "<p><strong>Nonce 2:</strong> $nonce2</p>\n";
echo "<p><strong>Nonce 3:</strong> $nonce3</p>\n";
echo "<p><strong>All same:</strong> " . ($nonce1 === $nonce2 && $nonce2 === $nonce3 ? 'YES' : 'NO') . "</p>\n";

// Test verification
echo "<h2>Nonce Verification Test</h2>\n";
$verify1 = wp_verify_nonce($nonce1, $action);
$verify2 = wp_verify_nonce($nonce2, $action);
$verify_wrong = wp_verify_nonce($nonce1, 'wrong_action');
$verify_fake = wp_verify_nonce('fake_nonce_12345', $action);

echo "<p><strong>Verify nonce1 with correct action:</strong> " . ($verify1 ? 'VALID' : 'INVALID') . " (result: $verify1)</p>\n";
echo "<p><strong>Verify nonce2 with correct action:</strong> " . ($verify2 ? 'VALID' : 'INVALID') . " (result: $verify2)</p>\n";
echo "<p><strong>Verify nonce1 with wrong action:</strong> " . ($verify_wrong ? 'VALID' : 'INVALID') . " (result: $verify_wrong)</p>\n";
echo "<p><strong>Verify fake nonce:</strong> " . ($verify_fake ? 'VALID' : 'INVALID') . " (result: $verify_fake)</p>\n";

// User info
echo "<h2>User Information</h2>\n";
$current_user = wp_get_current_user();
echo "<p><strong>User ID:</strong> {$current_user->ID}</p>\n";
echo "<p><strong>User Login:</strong> {$current_user->user_login}</p>\n";
echo "<p><strong>User Roles:</strong> " . implode(', ', $current_user->roles) . "</p>\n";
echo "<p><strong>Can edit_posts:</strong> " . (current_user_can('edit_posts') ? 'YES' : 'NO') . "</p>\n";
echo "<p><strong>Can administrator:</strong> " . (current_user_can('administrator') ? 'YES' : 'NO') . "</p>\n";

// WordPress settings
echo "<h2>WordPress Settings</h2>\n";
echo "<p><strong>WP_DEBUG:</strong> " . (defined('WP_DEBUG') && WP_DEBUG ? 'TRUE' : 'FALSE') . "</p>\n";
echo "<p><strong>DOING_AJAX:</strong> " . (defined('DOING_AJAX') && DOING_AJAX ? 'TRUE' : 'FALSE') . "</p>\n";
echo "<p><strong>Site URL:</strong> " . site_url() . "</p>\n";
echo "<p><strong>Admin URL:</strong> " . admin_url() . "</p>\n";
echo "<p><strong>AJAX URL:</strong> " . admin_url('admin-ajax.php') . "</p>\n";

// Test with POST simulation
echo "<h2>POST Simulation Test</h2>\n";
$_POST['nonce'] = $nonce1;
$_POST['action'] = $action;

$test_verify = wp_verify_nonce($_POST['nonce'], $action);
echo "<p><strong>Verify from \$_POST simulation:</strong> " . ($test_verify ? 'VALID' : 'INVALID') . " (result: $test_verify)</p>\n";

// Clean up
unset($_POST['nonce']);
unset($_POST['action']);

echo "<p style='margin-top: 30px; color: #666;'>Test completed at " . current_time('mysql') . "</p>\n";
?>
