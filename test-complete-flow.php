<?php
/**
 * Complete test for dashboard redirect functionality
 * Access: /wp-content/plugins/wp-docs-manager/test-complete-flow.php
 */

// Load WordPress
require_once('../../../wp-config.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Redirect Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #e7f7e7; border: 1px solid #4caf50; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; }
        .error { background: #ffe6e6; border: 1px solid #ff4444; }
        .test-button { 
            display: inline-block; 
            padding: 10px 20px; 
            margin: 5px; 
            background: #0073aa; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        .test-button:hover { background: #005a87; }
        .test-button.logout { background: #dc3545; }
        .test-button.logout:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>🔒 Dashboard Redirect Test</h1>
    
    <?php
    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();
    
    if ($is_logged_in) {
        require_once('includes/class-lift-docs-frontend-login.php');
        $frontend = new LIFT_Docs_Frontend_Login();
        $has_access = $frontend->user_has_docs_access();
        
        echo "<div class='status success'>";
        echo "<h2>✅ Logged In</h2>";
        echo "<p><strong>User:</strong> {$current_user->display_name} (ID: {$current_user->ID})</p>";
        echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
        echo "<p><strong>Document Access:</strong> " . ($has_access ? '✅ Yes' : '❌ No') . "</p>";
        echo "</div>";
        
        if ($has_access) {
            echo "<div class='status success'>";
            echo "<h3>✅ Should Access Dashboard</h3>";
            echo "<p>Since you're logged in and have document access, you should be able to access the dashboard normally.</p>";
            echo "<a href='" . home_url('/document-dashboard/') . "' class='test-button' target='_blank'>Test Dashboard Access</a>";
            echo "</div>";
        } else {
            echo "<div class='status warning'>";
            echo "<h3>⚠️ No Document Access</h3>";
            echo "<p>You're logged in but don't have document access. You should be redirected to login page.</p>";
            echo "<a href='" . home_url('/document-dashboard/') . "' class='test-button' target='_blank'>Test Dashboard Access</a>";
            echo "</div>";
        }
        
        echo "<h3>🔐 Test Logout Scenario</h3>";
        echo "<p>To test the redirect for non-logged users:</p>";
        echo "<a href='?action=logout' class='test-button logout'>Logout and Test</a>";
        
    } else {
        echo "<div class='status error'>";
        echo "<h2>❌ Not Logged In</h2>";
        echo "<p>Perfect for testing! You should be redirected to login when accessing dashboard.</p>";
        echo "</div>";
        
        echo "<div class='status warning'>";
        echo "<h3>🚀 Test Dashboard Redirect</h3>";
        echo "<p>Click below to test dashboard access. You should be automatically redirected to the login page.</p>";
        echo "<a href='" . home_url('/document-dashboard/') . "' class='test-button' target='_blank'>Test Dashboard Access (Should Redirect)</a>";
        echo "</div>";
        
        echo "<h3>🔑 After Testing</h3>";
        echo "<p>After testing the redirect, you can login again:</p>";
        echo "<a href='" . wp_login_url() . "' class='test-button' target='_blank'>WordPress Login</a>";
        echo "<a href='" . home_url('/document-login/') . "' class='test-button' target='_blank'>Document Login</a>";
    }
    
    // Handle logout
    if (isset($_GET['action']) && $_GET['action'] === 'logout' && $is_logged_in) {
        wp_logout();
        wp_clear_auth_cookie();
        echo "<script>
            setTimeout(function() {
                window.location.href = '" . home_url('/wp-content/plugins/wp-docs-manager/test-complete-flow.php') . "';
            }, 2000);
        </script>";
        
        echo "<div class='status success'>";
        echo "<h3>✅ Logged Out</h3>";
        echo "<p>Redirecting to test page...</p>";
        echo "</div>";
        exit;
    }
    ?>
    
    <hr>
    
    <h3>📋 Test Checklist</h3>
    <ol>
        <li><strong>Logged In User with Access:</strong> ✅ Should access dashboard normally</li>
        <li><strong>Logged In User without Access:</strong> ⚠️ Should redirect to login</li>
        <li><strong>Not Logged In User:</strong> ❌ Should redirect to login</li>
    </ol>
    
    <h3>🔗 URLs</h3>
    <ul>
        <li><strong>Dashboard:</strong> <a href="<?php echo home_url('/document-dashboard/'); ?>" target="_blank"><?php echo home_url('/document-dashboard/'); ?></a></li>
        <li><strong>Login:</strong> <a href="<?php echo home_url('/document-login/'); ?>" target="_blank"><?php echo home_url('/document-login/'); ?></a></li>
        <li><strong>WP Admin:</strong> <a href="<?php echo admin_url(); ?>" target="_blank"><?php echo admin_url(); ?></a></li>
    </ul>
    
    <h3>🔧 Implementation Details</h3>
    <p>The redirect functionality has been implemented in multiple places for robustness:</p>
    <ul>
        <li><strong>wp_loaded hook:</strong> Early redirect check based on REQUEST_URI</li>
        <li><strong>template_redirect hook:</strong> Check when loading dashboard page</li>
        <li><strong>Shortcode level:</strong> Fallback redirect in [docs_dashboard] shortcode</li>
    </ul>
    
</body>
</html>
