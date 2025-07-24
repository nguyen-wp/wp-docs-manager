<?php
/**
 * Test AJAX Login Directly
 */

// Include WordPress
require_once('../../../wp-config.php');

// Handle AJAX login test
if (isset($_POST['ajax_test'])) {
    // Set up AJAX environment
    define('DOING_AJAX', true);
    
    // Include the frontend login class
    require_once('class-lift-docs-frontend-login.php');
    
    // Create instance
    $frontend_login = new LIFT_Docs_Frontend_Login();
    
    // Set POST data
    $_POST['nonce'] = wp_create_nonce('docs_login_nonce');
    $_POST['username'] = 'demomo';
    $_POST['password'] = 'demomo';
    $_POST['remember'] = '0';
    
    echo "<h3>AJAX Test Result:</h3>\n";
    echo "<p>Testing with username: demomo, password: demomo</p>\n";
    
    // Capture output
    ob_start();
    try {
        $frontend_login->handle_ajax_login();
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>\n";
    }
    $ajax_output = ob_get_clean();
    
    echo "<p><strong>AJAX Output:</strong></p>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 4px;'>" . esc_html($ajax_output) . "</pre>\n";
    
    // Check current user
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        echo "<p style='color: green;'>✅ Login successful! Current user: " . $current_user->display_name . "</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Login failed - no user logged in</p>\n";
    }
    
    exit;
}

echo "<h2>🧪 Direct AJAX Login Test</h2>\n";

// Check user first
$user = get_user_by('login', 'demomo');
if ($user) {
    echo "<p style='color: green;'>✅ User 'demomo' exists</p>\n";
    echo "<ul>\n";
    echo "<li>ID: " . $user->ID . "</li>\n";
    echo "<li>Email: " . $user->user_email . "</li>\n";
    echo "<li>Roles: " . implode(', ', $user->roles) . "</li>\n";
    echo "<li>User Code: " . get_user_meta($user->ID, 'lift_docs_user_code', true) . "</li>\n";
    echo "</ul>\n";
} else {
    echo "<p style='color: red;'>❌ User 'demomo' not found</p>\n";
}

// Test password directly
if ($user) {
    $password_check = wp_check_password('demomo', $user->user_pass);
    echo "<p>Password check: " . ($password_check ? '✅ Valid' : '❌ Invalid') . "</p>\n";
}

echo "<hr>\n";
echo "<h3>Manual Tests:</h3>\n";

// Test 1: Direct wp_signon
echo "<h4>Test 1: Direct wp_signon</h4>\n";
if ($user) {
    $credentials = array(
        'user_login'    => 'demomo',
        'user_password' => 'demomo',
        'remember'      => false
    );
    
    $signon_result = wp_signon($credentials, false);
    
    if (is_wp_error($signon_result)) {
        echo "<p style='color: red;'>❌ wp_signon failed: " . $signon_result->get_error_message() . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ wp_signon successful!</p>\n";
        wp_logout(); // Logout for next test
    }
}

// Test 2: Check if can find user by different methods
echo "<h4>Test 2: Find user by different methods</h4>\n";
$methods = [
    'username' => get_user_by('login', 'demomo'),
    'email' => get_user_by('email', $user->user_email),
    'user_code' => get_users(array(
        'meta_key' => 'lift_docs_user_code',
        'meta_value' => get_user_meta($user->ID, 'lift_docs_user_code', true),
        'number' => 1
    ))
];

foreach ($methods as $method => $result) {
    if ($method === 'user_code') {
        $found = !empty($result);
        echo "<p>Find by $method: " . ($found ? '✅ Found' : '❌ Not found') . "</p>\n";
    } else {
        echo "<p>Find by $method: " . ($result ? '✅ Found' : '❌ Not found') . "</p>\n";
    }
}

?>

<hr>
<h3>🧪 Run AJAX Test</h3>
<form method="post">
    <input type="hidden" name="ajax_test" value="1">
    <button type="submit" style="padding: 15px 30px; background: #0073aa; color: white; border: none; border-radius: 4px; font-size: 16px;">
        Run AJAX Login Test
    </button>
</form>

<hr>
<h3>🔗 Other Test Links</h3>
<ul>
    <li><a href="<?php echo home_url('/docs-login'); ?>" target="_blank">Official /docs-login page</a></li>
    <li><a href="<?php echo home_url('/wp-content/plugins/wp-docs-manager/test-improved-login.php'); ?>" target="_blank">Improved login test</a></li>
    <li><a href="<?php echo home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php'); ?>" target="_blank">Emergency login</a></li>
</ul>
