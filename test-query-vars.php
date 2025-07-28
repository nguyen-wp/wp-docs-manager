<?php
/**
 * Test query vars và rewrite rules
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please login as admin first.');
}

echo "<h1>Query Vars Test</h1>";

// Test query vars
echo "<h2>Current Query Vars:</h2>";
echo "lift_secure_page: " . get_query_var('lift_secure_page') . "<br>";
echo "lift_download: " . get_query_var('lift_download') . "<br>";
echo "lift_secure: " . ($_GET['lift_secure'] ?? 'not set') . "<br>";

echo "<h2>$_GET Parameters:</h2>";
echo "<pre>" . print_r($_GET, true) . "</pre>";

echo "<h2>Current URL:</h2>";
echo $_SERVER['REQUEST_URI'] ?? 'not set';

echo "<h2>Rewrite Rules (LIFT related):</h2>";
$rewrite_rules = get_option('rewrite_rules');
foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift') !== false || strpos($replacement, 'lift') !== false) {
        echo "$pattern → $replacement<br>";
    }
}

echo "<h2>Test URLs:</h2>";
if (!empty(get_posts(['post_type' => 'lift_document', 'posts_per_page' => 1]))) {
    $doc = get_posts(['post_type' => 'lift_document', 'posts_per_page' => 1])[0];
    if (class_exists('LIFT_Docs_Settings')) {
        $view_url = LIFT_Docs_Settings::generate_secure_link($doc->ID);
        $download_url = LIFT_Docs_Settings::generate_secure_download_link($doc->ID);
        
        echo "Sample secure view URL:<br>";
        echo "<a href='$view_url' target='_blank'>$view_url</a><br><br>";
        
        echo "Sample secure download URL:<br>";
        echo "<a href='$download_url' target='_blank'>$download_url</a><br><br>";
    }
}

echo "<h2>Debug Actions:</h2>";
echo '<a href="' . home_url('/lift-docs/secure/?test=1') . '" target="_blank">Test Secure Page Route</a><br>';
echo '<a href="' . home_url('/lift-docs/download/?test=1') . '" target="_blank">Test Download Page Route</a><br>';

?>
