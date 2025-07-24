<?php
/**
 * Flush Rewrite Rules for Frontend Login System
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>Flushing Rewrite Rules for Frontend Login System</h2>\n";

// Add rewrite rules
add_rewrite_rule('^docs-login/?$', 'index.php?docs_login=1', 'top');
add_rewrite_rule('^docs-dashboard/?$', 'index.php?docs_dashboard=1', 'top');

// Flush rewrite rules
flush_rewrite_rules();

// Set flag that rules have been flushed
update_option('lift_docs_rewrite_rules_flushed', true);

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 20px; margin: 20px 0;'>\n";
echo "<h3 style='color: #155724; margin-top: 0;'>✅ Rewrite Rules Flushed Successfully!</h3>\n";
echo "<p style='color: #155724;'>The following URLs should now be active:</p>\n";
echo "<ul style='color: #155724;'>\n";
echo "<li><strong>Login Page:</strong> <a href='" . home_url('/docs-login') . "' target='_blank'>" . home_url('/docs-login') . "</a></li>\n";
echo "<li><strong>Dashboard Page:</strong> <a href='" . home_url('/docs-dashboard') . "' target='_blank'>" . home_url('/docs-dashboard') . "</a></li>\n";
echo "</ul>\n";
echo "</div>\n";

// Test the query vars
global $wp;
$wp->add_query_var('docs_login');
$wp->add_query_var('docs_dashboard');

echo "<h3>Testing URL Recognition:</h3>\n";

// Test docs-login
$login_url = home_url('/docs-login');
echo "<p><strong>Login URL:</strong> <a href='{$login_url}' target='_blank'>{$login_url}</a></p>\n";

// Test docs-dashboard  
$dashboard_url = home_url('/docs-dashboard');
echo "<p><strong>Dashboard URL:</strong> <a href='{$dashboard_url}' target='_blank'>{$dashboard_url}</a></p>\n";

echo "<div style='background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; margin: 15px 0;'>\n";
echo "<h4 style='color: #1976d2; margin-top: 0;'>Next Steps:</h4>\n";
echo "<ol style='color: #1976d2;'>\n";
echo "<li>Click the links above to test the pages</li>\n";
echo "<li>Try logging in with a documents user account</li>\n";
echo "<li>Test the dashboard functionality</li>\n";
echo "<li>Check analytics tracking</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><strong>Status:</strong> Rewrite rules have been flushed and the frontend login system is ready for testing!</p>\n";
