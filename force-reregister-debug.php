<?php
/**
 * Force Re-register Frontend Login Hooks
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔄 Force Re-register Frontend Login System</h2>\n";

// Remove existing hooks first
remove_all_actions('template_redirect');
remove_all_filters('query_vars');

// Manually add query vars
global $wp;
if (!in_array('docs_login', $wp->public_query_vars)) {
    $wp->add_query_var('docs_login');
    echo "✅ Added 'docs_login' query var<br>\n";
}

if (!in_array('docs_dashboard', $wp->public_query_vars)) {
    $wp->add_query_var('docs_dashboard');
    echo "✅ Added 'docs_dashboard' query var<br>\n";
}

// Re-initialize the frontend login class
if (class_exists('LIFT_Docs_Frontend_Login')) {
    $frontend_login = new LIFT_Docs_Frontend_Login();
    echo "✅ Re-initialized LIFT_Docs_Frontend_Login<br>\n";
} else {
    echo "❌ LIFT_Docs_Frontend_Login class not found<br>\n";
}

// Force flush rewrite rules again
flush_rewrite_rules();
echo "✅ Flushed rewrite rules<br>\n";

// Test current query vars
echo "<h3>Current Query Vars:</h3>\n";
$current_vars = $wp->public_query_vars;
echo "<pre>" . print_r(array_filter($current_vars, function($var) {
    return strpos($var, 'docs') !== false || strpos($var, 'lift') !== false;
}), true) . "</pre>\n";

// Test direct URL simulation
echo "<h3>Direct URL Test:</h3>\n";
$_SERVER['REQUEST_URI'] = '/docs-login/';
$wp->parse_request();

if (get_query_var('docs_login')) {
    echo "✅ docs_login query var is working: " . get_query_var('docs_login') . "<br>\n";
} else {
    echo "❌ docs_login query var is not working<br>\n";
}

echo "<p><strong>Try accessing:</strong> <a href='" . home_url('/docs-login') . "' target='_blank'>" . home_url('/docs-login') . "</a></p>\n";
?>
