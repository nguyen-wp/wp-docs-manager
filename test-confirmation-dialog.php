<?php
/**
 * Test Confirmation Dialog for User Code Generation
 * 
 * This file specifically tests the confirmation dialog behavior
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>Testing User Code Generation Confirmation</h2>\n";

// Test 1: Get sample users with different states
$document_users = get_users(array(
    'role' => 'documents_user',
    'number' => 5
));

if (empty($document_users)) {
    echo "<p style='color: red;'>No Documents Users found. Please create some first.</p>\n";
    exit;
}

echo "<h3>Test Scenarios:</h3>\n";

$scenario_1_user = null;
$scenario_2_user = null;

foreach ($document_users as $user) {
    $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
    
    if (!$user_code && !$scenario_1_user) {
        $scenario_1_user = $user;
    } else if ($user_code && !$scenario_2_user) {
        $scenario_2_user = $user;
    }
    
    if ($scenario_1_user && $scenario_2_user) break;
}

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #f9f9f9;'>\n";
echo "<th>Scenario</th><th>User</th><th>Current Code</th><th>Expected Button</th><th>Confirmation Required</th><th>Expected Message</th>\n";
echo "</tr>\n";

// Scenario 1: User without code
if ($scenario_1_user) {
    echo "<tr>\n";
    echo "<td><strong>Scenario 1</strong><br>New Code Generation</td>\n";
    echo "<td>{$scenario_1_user->display_name}<br><small>ID: {$scenario_1_user->ID}</small></td>\n";
    echo "<td><em style='color: #d63638;'>No Code</em></td>\n";
    echo "<td style='background: #00a32a; color: white; text-align: center;'><strong>Generate Code</strong><br><small>(Green Primary)</small></td>\n";
    echo "<td style='color: #00a32a; text-align: center;'><strong>NO</strong></td>\n";
    echo "<td>No confirmation dialog</td>\n";
    echo "</tr>\n";
} else {
    echo "<tr>\n";
    echo "<td><strong>Scenario 1</strong><br>New Code Generation</td>\n";
    echo "<td colspan='5' style='color: #d63638; text-align: center;'><em>No user without code found</em></td>\n";
    echo "</tr>\n";
}

// Scenario 2: User with existing code
if ($scenario_2_user) {
    $existing_code = get_user_meta($scenario_2_user->ID, 'lift_docs_user_code', true);
    echo "<tr>\n";
    echo "<td><strong>Scenario 2</strong><br>Code Regeneration</td>\n";
    echo "<td>{$scenario_2_user->display_name}<br><small>ID: {$scenario_2_user->ID}</small></td>\n";
    echo "<td><code style='background: #f0f8ff; padding: 2px 6px;'>{$existing_code}</code></td>\n";
    echo "<td style='background: #f39c12; color: white; text-align: center;'><strong>Generate New Code</strong><br><small>(Orange Secondary)</small></td>\n";
    echo "<td style='color: #d63638; text-align: center;'><strong>YES</strong></td>\n";
    echo "<td>\"Are you sure you want to generate a new User Code? This will replace the existing code and may affect document access.\"</td>\n";
    echo "</tr>\n";
} else {
    echo "<tr>\n";
    echo "<td><strong>Scenario 2</strong><br>Code Regeneration</td>\n";
    echo "<td colspan='5' style='color: #d63638; text-align: center;'><em>No user with existing code found</em></td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

// Test 2: JavaScript behavior explanation
echo "<h3>JavaScript Behavior Analysis:</h3>\n";
echo "<div style='background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4>Button Detection Logic:</h4>\n";
echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #e9ecef; border-radius: 3px;'>\n";
echo "var isRegenerate = \$button.hasClass('button-secondary');\n\n";
echo "if (isRegenerate) {\n";
echo "    // Show confirmation dialog\n";
echo "    var confirmMessage = 'Are you sure you want to generate a new User Code?...';\n";
echo "    if (!confirm(confirmMessage)) {\n";
echo "        return; // Cancel operation\n";
echo "    }\n";
echo "}\n\n";
echo "// Proceed with AJAX call\n";
echo "</pre>\n";
echo "</div>\n";

// Test 3: Confirmation message details
echo "<h3>Confirmation Message Details:</h3>\n";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4>Full Confirmation Text:</h4>\n";
echo "<blockquote style='border-left: 4px solid #f39c12; padding-left: 15px; margin: 10px 0; font-style: italic;'>\n";
echo "\"Are you sure you want to generate a new User Code? This will replace the existing code and may affect document access.\"\n";
echo "</blockquote>\n";
echo "<h4>Why This Warning is Important:</h4>\n";
echo "<ul>\n";
echo "<li><strong>Existing code will be lost:</strong> The old code becomes invalid</li>\n";
echo "<li><strong>Document access impact:</strong> If documents are assigned specifically to this code, access may be affected</li>\n";
echo "<li><strong>Search references:</strong> Any saved searches or bookmarks using the old code will need updating</li>\n";
echo "<li><strong>User notification:</strong> The user may need to be informed of their new code</li>\n";
echo "</ul>\n";
echo "</div>\n";

// Test 4: User interaction flow
echo "<h3>Complete User Interaction Flow:</h3>\n";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4>For Users WITHOUT Code (Green Button):</h4>\n";
echo "<ol>\n";
echo "<li>Admin clicks \"Generate Code\" button</li>\n";
echo "<li>Button immediately disables and shows \"Generating...\"</li>\n";
echo "<li>AJAX call executes</li>\n";
echo "<li>New code appears with \"Generate New Code\" button (orange)</li>\n";
echo "<li>Success message: \"User Code generated successfully!\"</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 15px 0;'>\n";
echo "<h4>For Users WITH Code (Orange Button):</h4>\n";
echo "<ol>\n";
echo "<li>Admin clicks \"Generate New Code\" button</li>\n";
echo "<li><strong>⚠️ Confirmation dialog appears</strong></li>\n";
echo "<li>If admin clicks \"Cancel\": Operation stops, no changes made</li>\n";
echo "<li>If admin clicks \"OK\": Button disables and shows \"Generating...\"</li>\n";
echo "<li>AJAX call executes</li>\n";
echo "<li>New code replaces old code, button stays orange</li>\n";
echo "<li>Success message: \"User Code regenerated successfully!\"</li>\n";
echo "</ol>\n";
echo "</div>\n";

// Test 5: Browser compatibility
echo "<h3>Browser Compatibility Notes:</h3>\n";
echo "<ul>\n";
echo "<li><strong>confirm() function:</strong> Supported in all major browsers</li>\n";
echo "<li><strong>Modal appearance:</strong> Browser-native dialog box</li>\n";
echo "<li><strong>Button text:</strong> \"OK\" and \"Cancel\" (may vary by browser language)</li>\n";
echo "<li><strong>Return value:</strong> true if OK clicked, false if Cancel clicked</li>\n";
echo "</ul>\n";

?>

<style>
table {
    font-family: Arial, sans-serif;
    font-size: 14px;
}

th {
    background: #f1f1f1;
    font-weight: bold;
    text-align: left;
}

pre {
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

blockquote {
    font-size: 16px;
    color: #856404;
}

.highlight {
    background: #fff3cd;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #ffeaa7;
}
</style>
