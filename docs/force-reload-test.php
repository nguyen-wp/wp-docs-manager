<?php
/**
 * Force Plugin Reload and Test
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔄 Force Plugin Reload and Test</h2>\n";

// Deactivate and reactivate plugin
$plugin_file = 'wp-docs-manager/lift-docs-system.php';

deactivate_plugins($plugin_file);
echo "<p>✅ Plugin deactivated</p>\n";

activate_plugin($plugin_file);
echo "<p>✅ Plugin reactivated</p>\n";

// Force flush rewrite rules
flush_rewrite_rules();
echo "<p>✅ Rewrite rules flushed</p>\n";

// Test query vars
global $wp;
if (in_array('docs_login', $wp->public_query_vars)) {
    echo "<p>✅ 'docs_login' query var is registered</p>\n";
} else {
    echo "<p>❌ 'docs_login' query var is NOT registered</p>\n";
}

// Test rewrite rules
$rules = get_option('rewrite_rules');
$docs_rules = array();
foreach ($rules as $rule => $match) {
    if (strpos($rule, 'docs') !== false) {
        $docs_rules[$rule] = $match;
    }
}

if (!empty($docs_rules)) {
    echo "<p>✅ Docs rewrite rules found:</p>\n";
    echo "<ul>\n";
    foreach ($docs_rules as $rule => $match) {
        echo "<li><code>$rule</code> => <code>$match</code></li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p>❌ No docs rewrite rules found</p>\n";
}

// Test class
if (class_exists('LIFT_Docs_Frontend_Login')) {
    echo "<p>✅ LIFT_Docs_Frontend_Login class exists</p>\n";
} else {
    echo "<p>❌ LIFT_Docs_Frontend_Login class not found</p>\n";
}

echo "<hr>\n";
echo "<h3>Test URLs:</h3>\n";
echo "<ul>\n";
echo "<li><a href='" . home_url('/docs-login') . "' target='_blank'>" . home_url('/docs-login') . "</a></li>\n";
echo "<li><a href='" . home_url('/docs-dashboard') . "' target='_blank'>" . home_url('/docs-dashboard') . "</a></li>\n";
echo "</ul>\n";

echo "<h3>Alternative (Emergency) URLs:</h3>\n";
echo "<ul>\n";
echo "<li><a href='" . home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php') . "' target='_blank'>Emergency Login</a></li>\n";
echo "<li><a href='" . home_url('/wp-content/plugins/wp-docs-manager/emergency-dashboard.php') . "' target='_blank'>Emergency Dashboard</a></li>\n";
echo "</ul>\n";
?>
