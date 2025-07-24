<?php
require_once '../../../wp-load.php';

echo "<h2>URL Consolidation Test</h2>";

echo "<h3>Testing Login URLs:</h3>";
echo "<ul>";
echo "<li><a href='" . home_url('/document-login/') . "' target='_blank'>/document-login/ (SHOULD WORK)</a></li>";
echo "<li><a href='" . home_url('/docs-login/') . "' target='_blank'>/docs-login/ (SHOULD BE 404)</a></li>";
echo "</ul>";

echo "<h3>Testing Dashboard URLs:</h3>";
echo "<ul>";
echo "<li><a href='" . home_url('/document-dashboard/') . "' target='_blank'>/document-dashboard/ (SHOULD WORK)</a></li>";
echo "<li><a href='" . home_url('/docs-dashboard/') . "' target='_blank'>/docs-dashboard/ (SHOULD BE 404)</a></li>";
echo "</ul>";

echo "<h3>Helper Methods Test:</h3>";
// Test URL detection
$login_page_id = get_option('lift_docs_login_page_id');
$dashboard_page_id = get_option('lift_docs_dashboard_page_id');

echo "<p>Login page ID from option: " . ($login_page_id ?: 'Not set') . "</p>";
echo "<p>Dashboard page ID from option: " . ($dashboard_page_id ?: 'Not set') . "</p>";

if ($login_page_id) {
    echo "<p>Login page URL: " . get_permalink($login_page_id) . "</p>";
}
if ($dashboard_page_id) {
    echo "<p>Dashboard page URL: " . get_permalink($dashboard_page_id) . "</p>";
}

echo "<h3>Test Login with demomo:</h3>";
echo "<p>Try logging in with demomo/demomo on the document-login page.</p>";
?>
