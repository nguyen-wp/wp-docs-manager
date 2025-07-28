<?php
/**
 * Flush rewrite rules manually
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please login as admin first.');
}

echo "<h1>Flush Rewrite Rules</h1>";

// Flush rewrite rules
flush_rewrite_rules();

echo "<p style='color: green; font-weight: bold;'>✅ Rewrite rules flushed successfully!</p>";
echo "<p>Now try your download links again.</p>";

echo "<p><a href='test-query-vars.php'>← Back to Query Vars Test</a></p>";
echo "<p><a href='view-debug-log.php'>View Debug Log →</a></p>";
?>
