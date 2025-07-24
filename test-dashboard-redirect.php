<?php
/**
 * Test dashboard redirect when not logged in
 * Access: /wp-content/plugins/wp-docs-manager/test-dashboard-redirect.php
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "<h2>Dashboard Redirect Test</h2>";

// Show current login status
$current_user = wp_get_current_user();
if (is_user_logged_in()) {
    echo "<p><strong>✅ Currently logged in as:</strong> {$current_user->display_name} (ID: {$current_user->ID})</p>";
    
    // Check if user has document access
    require_once('includes/class-lift-docs-frontend-login.php');
    $frontend = new LIFT_Docs_Frontend_Login();
    $has_access = $frontend->user_has_docs_access();
    echo "<p><strong>Document access:</strong> " . ($has_access ? '✅ Yes' : '❌ No') . "</p>";
    
    echo "<div style='background: #e7f7e7; padding: 15px; border: 1px solid #4caf50; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Test Results:</strong></p>";
    echo "<p>Since you are logged in, accessing <code>/document-dashboard/</code> should display the dashboard.</p>";
    echo "<p><a href='" . home_url('/document-dashboard/') . "' target='_blank'>Test Dashboard Access →</a></p>";
    echo "</div>";
    
    echo "<h3>To test redirect behavior:</h3>";
    echo "<ol>";
    echo "<li>Logout from WordPress admin</li>";
    echo "<li>Open incognito/private browser window</li>";
    echo "<li>Navigate to <code>" . home_url('/document-dashboard/') . "</code></li>";
    echo "<li>Should automatically redirect to <code>" . home_url('/document-login/') . "</code></li>";
    echo "</ol>";
    
} else {
    echo "<p><strong>❌ Not logged in</strong></p>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Perfect for testing!</strong></p>";
    echo "<p>Since you are not logged in, accessing dashboard should redirect to login page.</p>";
    echo "<p><a href='" . home_url('/document-dashboard/') . "' target='_blank'>Test Dashboard Redirect →</a></p>";
    echo "<p><em>(Should redirect to login page)</em></p>";
    echo "</div>";
}

// Show URL information
echo "<h3>URL Information:</h3>";
echo "<ul>";
echo "<li><strong>Dashboard URL:</strong> <a href='" . home_url('/document-dashboard/') . "' target='_blank'>" . home_url('/document-dashboard/') . "</a></li>";
echo "<li><strong>Login URL:</strong> <a href='" . home_url('/document-login/') . "' target='_blank'>" . home_url('/document-login/') . "</a></li>";
echo "</ul>";

// Check if pages exist
echo "<h3>Page Status:</h3>";
$dashboard_page = get_page_by_path('document-dashboard');
$login_page = get_page_by_path('document-login');

echo "<p><strong>Dashboard page:</strong> " . ($dashboard_page ? "✅ Exists (ID: {$dashboard_page->ID})" : "❌ Not found") . "</p>";
echo "<p><strong>Login page:</strong> " . ($login_page ? "✅ Exists (ID: {$login_page->ID})" : "❌ Not found") . "</p>";

if ($dashboard_page) {
    echo "<h4>Dashboard Page Content:</h4>";
    echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px;'>";
    echo htmlspecialchars($dashboard_page->post_content);
    echo "</div>";
}

// Test shortcode behavior
echo "<h3>Shortcode Test:</h3>";
if (shortcode_exists('docs_dashboard')) {
    echo "<p>✅ <code>[docs_dashboard]</code> shortcode is registered</p>";
    
    if (!is_user_logged_in()) {
        echo "<p><strong>Testing shortcode output for non-logged user:</strong></p>";
        echo "<div style='border: 1px solid #ddd; padding: 10px; background: #f9f9f9;'>";
        // Note: We don't execute shortcode here because it would trigger redirect
        echo "<em>Shortcode would trigger redirect when executed on the actual page</em>";
        echo "</div>";
    }
} else {
    echo "<p>❌ <code>[docs_dashboard]</code> shortcode not found</p>";
}

?>

<style>
h2, h3 { 
    color: #333; 
    border-bottom: 1px solid #ddd; 
    padding-bottom: 5px; 
}
code { 
    background: #f5f5f5; 
    padding: 2px 5px; 
    border-radius: 3px; 
    font-family: monospace; 
}
a {
    color: #0073aa;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
