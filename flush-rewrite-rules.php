<?php
/**
 * Flush Rewrite Rules for LIFT Docs
 * 
 * This script flushes WordPress rewrite rules to ensure 
 * /lift-docs/secure/ and /lift-docs/download/ work properly
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo "=== LIFT Docs Rewrite Rules Flush ===\n\n";

echo "Before flush - checking current rules...\n";
$rules = get_option('rewrite_rules');
$lift_rules_found = 0;

foreach ($rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift-docs') !== false) {
        echo "   Found: $pattern -> $replacement\n";
        $lift_rules_found++;
    }
}

if ($lift_rules_found === 0) {
    echo "   ❌ No LIFT Docs rules found\n";
} else {
    echo "   ✅ Found $lift_rules_found LIFT Docs rules\n";
}

echo "\nFlushing rewrite rules...\n";

// Initialize the LIFT Docs Secure Links class to register rules
$secure_links = LIFT_Docs_Secure_Links::get_instance();

// Flush rewrite rules
flush_rewrite_rules(false); // false = don't hard flush

echo "✅ Rewrite rules flushed\n\n";

echo "After flush - checking rules again...\n";
$rules = get_option('rewrite_rules');
$lift_rules_found = 0;

foreach ($rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift-docs') !== false) {
        echo "   Found: $pattern -> $replacement\n";
        $lift_rules_found++;
    }
}

if ($lift_rules_found === 0) {
    echo "   ❌ Still no LIFT Docs rules found\n";
    echo "   You may need to:\n";
    echo "   1. Go to WordPress Admin > Settings > Permalinks\n";
    echo "   2. Click 'Save Changes'\n";
    echo "   3. Or try hard flush by setting first parameter to true\n";
} else {
    echo "   ✅ Found $lift_rules_found LIFT Docs rules\n";
}

// Test the query vars
echo "\nTesting query vars...\n";
$query_vars = $GLOBALS['wp']->public_query_vars ?? array();

$lift_vars = array('lift_secure_page', 'lift_secure', 'lift_download');
foreach ($lift_vars as $var) {
    if (in_array($var, $query_vars)) {
        echo "   ✅ $var is registered\n";
    } else {
        echo "   ❌ $var is NOT registered\n";
    }
}

echo "\n=== Test URLs ===\n";
$base_url = home_url();
echo "Test these URLs in your browser:\n";
echo "1. $base_url/lift-docs/secure/?lift_secure=test\n";
echo "2. $base_url/lift-docs/download/?lift_secure=test\n";
echo "\nBoth should show 'Missing security token' or 'Invalid token' instead of 404\n";

echo "\n=== Rewrite Rules Flush Complete ===\n";
?>
