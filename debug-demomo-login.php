<?php
/**
 * Debug Login for User demomo
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔍 Debug Login for User: demomo</h2>\n";

// Test find_user_by_login function
require_once('class-lift-docs-frontend-login.php');
$frontend_login = new LIFT_Docs_Frontend_Login();

// Test method call
$user = $frontend_login->debug_find_user('demomo');

if ($user) {
    echo "<p style='color: green;'>✅ find_user_by_login() found user:</p>\n";
    echo "<ul>\n";
    echo "<li><strong>ID:</strong> " . $user->ID . "</li>\n";
    echo "<li><strong>Username:</strong> " . $user->user_login . "</li>\n";
    echo "<li><strong>Email:</strong> " . $user->user_email . "</li>\n";
    echo "<li><strong>Roles:</strong> " . implode(', ', $user->roles) . "</li>\n";
    echo "<li><strong>User Code:</strong> " . get_user_meta($user->ID, 'lift_docs_user_code', true) . "</li>\n";
    echo "</ul>\n";
    
    // Test wp_signon
    echo "<h3>Testing wp_signon:</h3>\n";
    
    $credentials = array(
        'user_login'    => $user->user_login,
        'user_password' => 'demomo',
        'remember'      => false
    );
    
    $user_signon = wp_signon($credentials, false);
    
    if (is_wp_error($user_signon)) {
        echo "<p style='color: red;'>❌ wp_signon failed:</p>\n";
        echo "<ul>\n";
        foreach ($user_signon->get_error_messages() as $error) {
            echo "<li>" . esc_html($error) . "</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p style='color: green;'>✅ wp_signon successful!</p>\n";
        echo "<p>Logged in user: " . $user_signon->display_name . "</p>\n";
        
        // Test document access
        if (in_array('documents_user', $user_signon->roles) || user_can($user_signon->ID, 'view_lift_documents')) {
            echo "<p style='color: green;'>✅ User has document access</p>\n";
        } else {
            echo "<p style='color: red;'>❌ User does NOT have document access</p>\n";
        }
    }
    
} else {
    echo "<p style='color: red;'>❌ find_user_by_login() did NOT find user</p>\n";
}

// Test direct AJAX simulation
echo "<hr>\n";
echo "<h3>Simulating AJAX Login Request:</h3>\n";

// Simulate POST data
$_POST = array(
    'nonce' => wp_create_nonce('docs_login_nonce'),
    'username' => 'demomo',
    'password' => 'demomo',
    'remember' => '0'
);

echo "<p>Testing with POST data:</p>\n";
echo "<pre>" . print_r($_POST, true) . "</pre>\n";

// Test AJAX handler manually
try {
    ob_start();
    $frontend_login->handle_ajax_login();
    $output = ob_get_clean();
    echo "<p>AJAX handler output: <code>" . esc_html($output) . "</code></p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>AJAX handler threw exception: " . $e->getMessage() . "</p>\n";
}

// Check if user is now logged in
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<p style='color: green;'>✅ User is now logged in: " . $current_user->display_name . "</p>\n";
} else {
    echo "<p style='color: red;'>❌ User is NOT logged in after test</p>\n";
}

echo "<hr>\n";
echo "<h3>Direct Test Form:</h3>\n";
?>

<form method="post" style="background: #f9f9f9; padding: 20px; border-radius: 8px; max-width: 400px;">
    <h4>Test Login Form</h4>
    <p>
        <label>Username:</label><br>
        <input type="text" name="test_username" value="demomo" style="width: 100%; padding: 8px;">
    </p>
    <p>
        <label>Password:</label><br>
        <input type="password" name="test_password" value="demomo" style="width: 100%; padding: 8px;">
    </p>
    <p>
        <input type="submit" name="test_login" value="Test Login" style="padding: 10px 20px; background: #0073aa; color: white; border: none;">
    </p>
</form>

<?php
if (isset($_POST['test_login'])) {
    echo "<h4>Form Test Result:</h4>\n";
    
    $test_user = get_user_by('login', $_POST['test_username']);
    if ($test_user) {
        $test_credentials = array(
            'user_login'    => $test_user->user_login,
            'user_password' => $_POST['test_password'],
            'remember'      => false
        );
        
        $test_signon = wp_signon($test_credentials, false);
        
        if (is_wp_error($test_signon)) {
            echo "<p style='color: red;'>Form test failed: " . $test_signon->get_error_message() . "</p>\n";
        } else {
            echo "<p style='color: green;'>Form test successful! User: " . $test_signon->display_name . "</p>\n";
            echo "<p><a href='" . home_url('/wp-content/plugins/wp-docs-manager/emergency-dashboard.php') . "'>Go to Dashboard</a></p>\n";
        }
    } else {
        echo "<p style='color: red;'>User not found in form test</p>\n";
    }
}

echo "<hr>\n";
echo "<p><a href='" . home_url('/docs-login') . "'>← Back to /docs-login</a></p>\n";
?>
