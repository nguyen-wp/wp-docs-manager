<?php
// Temporarily switch to themed layout for comparison
add_filter('lift_docs_secure_use_clean_layout', '__return_false');

require_once '../../../wp-load.php';

$document_id = 40;
$token = LIFT_Docs_Settings::generate_secure_link($document_id);
$themed_url = home_url('/lift-docs/secure/?lift_secure=' . urlencode($token));

echo "<h2>Testing Themed Layout (Original)</h2>";
echo "<p>This should show the original layout với theme header/footer</p>";
echo "<p><a href='" . esc_url($themed_url) . "' target='_blank'>🔗 Open Themed Layout</a></p>";
?>
