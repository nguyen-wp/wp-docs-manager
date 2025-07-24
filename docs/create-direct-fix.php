<?php
/**
 * Direct Fix for Frontend Login System
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔧 Direct Fix for /docs-login/</h2>\n";

// Step 1: Force add rewrite rules directly
global $wp_rewrite;

// Add rules manually
$new_rules = array(
    '^docs-login/?$' => 'index.php?docs_login=1',
    '^docs-dashboard/?$' => 'index.php?docs_dashboard=1'
);

// Get current rules
$current_rules = get_option('rewrite_rules', array());

// Merge new rules
$updated_rules = array_merge($new_rules, $current_rules);

// Update rewrite rules
update_option('rewrite_rules', $updated_rules);
echo "✅ Added rewrite rules directly<br>\n";

// Step 2: Add query vars manually
global $wp;
$wp->add_query_var('docs_login');
$wp->add_query_var('docs_dashboard');
echo "✅ Added query vars manually<br>\n";

// Step 3: Test with direct hook
function handle_docs_login_direct() {
    if (get_query_var('docs_login')) {
        // Check if user is already logged in and has docs access
        if (is_user_logged_in() && (current_user_can('view_lift_documents') || in_array('documents_user', wp_get_current_user()->roles))) {
            wp_redirect(home_url('/docs-dashboard'));
            exit;
        }
        
        // Display login page
        include_once(plugin_dir_path(__FILE__) . 'simple-login-test.php');
        exit;
    }
}

function handle_docs_dashboard_direct() {
    if (get_query_var('docs_dashboard')) {
        // Check if user is logged in and has docs access
        if (!is_user_logged_in() || (!current_user_can('view_lift_documents') && !in_array('documents_user', wp_get_current_user()->roles))) {
            wp_redirect(home_url('/docs-login'));
            exit;
        }
        
        // Display dashboard page
        get_header();
        echo '<div class="container"><h1>Document Dashboard</h1><p>Welcome to your dashboard!</p><a href="' . wp_logout_url(home_url('/docs-login')) . '">Logout</a></div>';
        get_footer();
        exit;
    }
}

add_action('template_redirect', 'handle_docs_login_direct', 1);
add_action('template_redirect', 'handle_docs_dashboard_direct', 1);

echo "✅ Added direct handlers<br>\n";

// Step 4: Create simple test login page
$simple_login_content = '<?php
/**
 * Simple Login Page Test
 */
get_header();
?>
<div style="max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2>Document Login</h2>
    <form method="post" action="' . admin_url('admin-ajax.php') . '">
        <input type="hidden" name="action" value="docs_login">
        <input type="hidden" name="nonce" value="' . wp_create_nonce('docs_login_nonce') . '">
        
        <p>
            <label>Username/Email/User Code:</label><br>
            <input type="text" name="username" required style="width: 100%; padding: 8px;">
        </p>
        
        <p>
            <label>Password:</label><br>
            <input type="password" name="password" required style="width: 100%; padding: 8px;">
        </p>
        
        <p>
            <input type="submit" value="Login" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px;">
        </p>
    </form>
    
    <p><a href="' . home_url() . '">← Back to Home</a></p>
</div>
<?php
get_footer();
?>';

file_put_contents(plugin_dir_path(__FILE__) . 'simple-login-test.php', $simple_login_content);
echo "✅ Created simple login test page<br>\n";

// Step 5: Test current state
echo "<h3>Testing Current State:</h3>\n";

// Simulate URL test
$_SERVER['REQUEST_URI'] = '/docs-login/';
$wp->parse_request();

echo "Query vars after parse: <pre>" . print_r($wp->query_vars, true) . "</pre>\n";

if (get_query_var('docs_login')) {
    echo "✅ docs_login query var is working!<br>\n";
} else {
    echo "❌ docs_login query var still not working<br>\n";
}

echo "<p><strong>Test URLs:</strong></p>\n";
echo "<ul>\n";
echo "<li><a href='" . home_url('/docs-login') . "' target='_blank'>Login Page</a></li>\n";
echo "<li><a href='" . home_url('/docs-dashboard') . "' target='_blank'>Dashboard Page</a></li>\n";
echo "</ul>\n";

echo "<p><em>Note: If still not working, the issue might be with WordPress permalink structure or server configuration.</em></p>\n";
?>';

if (!file_exists(plugin_dir_path(__FILE__) . 'direct-fix-frontend.php')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'direct-fix-frontend.php', $content);
    echo "✅ Created direct fix file<br>\n";
}

echo "<p><strong>Run the fix:</strong> <a href='" . plugins_url('direct-fix-frontend.php', __FILE__) . "' target='_blank'>Execute Direct Fix</a></p>\n";
?>
