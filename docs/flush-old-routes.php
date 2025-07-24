<?php
// Flush rewrite rules to remove old /docs-login/ routes
require_once '../../../wp-load.php';

echo "<h2>Flushing Rewrite Rules</h2>";

flush_rewrite_rules();

echo "<p>✅ Rewrite rules flushed!</p>";
echo "<p>Old /docs-login/ and /docs-dashboard/ routes should now be removed.</p>";
echo "<p>Please use the regular page URLs:</p>";
echo "<ul>";
echo "<li><a href='" . home_url('/document-login/') . "'>/document-login/</a></li>";
echo "<li><a href='" . home_url('/document-dashboard/') . "'>/document-dashboard/</a></li>";
echo "</ul>";
?>
