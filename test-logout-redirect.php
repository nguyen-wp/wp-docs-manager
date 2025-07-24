<?php
/**
 * Simple test to simulate non-logged user accessing dashboard
 * Access: /wp-content/plugins/wp-docs-manager/test-logout-redirect.php
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "<h2>Logout and Redirect Test</h2>";

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Force logout
    wp_logout();
    wp_clear_auth_cookie();
    wp_destroy_current_session();
    
    echo "<p>✅ Logged out successfully!</p>";
    echo "<h3>Now test dashboard access:</h3>";
    echo "<p><a href='" . home_url('/document-dashboard/') . "' target='_blank'>Go to Dashboard →</a></p>";
    echo "<p><em>Should redirect to login page</em></p>";
    
} else {
    $current_user = wp_get_current_user();
    
    if (is_user_logged_in()) {
        echo "<p><strong>Currently logged in as:</strong> {$current_user->display_name}</p>";
        echo "<p><a href='?action=logout' class='button'>Logout and Test Redirect</a></p>";
    } else {
        echo "<p><strong>✅ Not logged in</strong></p>";
        echo "<h3>Test Dashboard Access:</h3>";
        echo "<p><a href='" . home_url('/document-dashboard/') . "' target='_blank'>Go to Dashboard →</a></p>";
        echo "<p><em>Should redirect to login page</em></p>";
        
        echo "<h3>After testing, you can login again:</h3>";
        echo "<p><a href='" . wp_login_url() . "' target='_blank'>WordPress Login →</a></p>";
    }
}

echo "<hr>";
echo "<h3>URL Information:</h3>";
echo "<ul>";
echo "<li><strong>Dashboard:</strong> <a href='" . home_url('/document-dashboard/') . "'>" . home_url('/document-dashboard/') . "</a></li>";
echo "<li><strong>Login:</strong> <a href='" . home_url('/document-login/') . "'>" . home_url('/document-login/') . "</a></li>";
echo "<li><strong>WP Login:</strong> <a href='" . wp_login_url() . "'>" . wp_login_url() . "</a></li>";
echo "</ul>";

?>

<style>
.button {
    display: inline-block;
    padding: 10px 20px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
.button:hover {
    background: #005a87;
}
</style>
