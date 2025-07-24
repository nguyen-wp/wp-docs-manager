<?php
/**
 * Test User Code Generation in Different Contexts
 * 
 * This file tests the User Code generation functionality in both:
 * 1. User profile/edit page context
 * 2. Users list page context
 */

// Include WordPress
require_once('../../../wp-config.php');

// Test scenarios
echo "<h2>Testing User Code Generation</h2>\n";

// Test 1: Check if Documents Users exist
$document_users = get_users(array(
    'role' => 'documents_user',
    'number' => 5
));

if (empty($document_users)) {
    echo "<p style='color: red;'>No Documents Users found. Please create some first.</p>\n";
    exit;
}

echo "<h3>Found " . count($document_users) . " Documents Users:</h3>\n";
echo "<ul>\n";
foreach ($document_users as $user) {
    $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
    $code_display = $user_code ? $user_code : 'No Code';
    echo "<li>ID: {$user->ID} - {$user->display_name} - Code: <strong>{$code_display}</strong></li>\n";
}
echo "</ul>\n";

// Test 2: Test nonce generation for different contexts
$test_user_id = $document_users[0]->ID;

echo "<h3>Testing Nonce Generation:</h3>\n";

// User profile context nonce
$profile_nonce = wp_create_nonce('generate_user_code_' . $test_user_id);
echo "<p><strong>User Profile Nonce:</strong> {$profile_nonce}</p>\n";

// Users list context nonce  
$list_nonce = wp_create_nonce('generate_user_code');
echo "<p><strong>Users List Nonce:</strong> {$list_nonce}</p>\n";

// Test 3: Verify nonce validation
echo "<h3>Testing Nonce Validation:</h3>\n";

$profile_valid = wp_verify_nonce($profile_nonce, 'generate_user_code_' . $test_user_id);
echo "<p>Profile nonce valid: " . ($profile_valid ? 'YES' : 'NO') . "</p>\n";

$list_valid = wp_verify_nonce($list_nonce, 'generate_user_code');
echo "<p>List nonce valid: " . ($list_valid ? 'YES' : 'NO') . "</p>\n";

// Cross-validation test
$cross_valid_1 = wp_verify_nonce($profile_nonce, 'generate_user_code');
$cross_valid_2 = wp_verify_nonce($list_nonce, 'generate_user_code_' . $test_user_id);
echo "<p>Profile nonce with list action: " . ($cross_valid_1 ? 'YES' : 'NO') . "</p>\n";
echo "<p>List nonce with profile action: " . ($cross_valid_2 ? 'YES' : 'NO') . "</p>\n";

// Test 4: Generate test AJAX requests
echo "<h3>Test AJAX Request Examples:</h3>\n";

echo "<h4>For User Profile Page:</h4>\n";
echo "<pre>";
echo "jQuery.ajax({\n";
echo "    url: ajaxurl,\n";
echo "    type: 'POST',\n";
echo "    data: {\n";
echo "        action: 'generate_user_code',\n";
echo "        user_id: {$test_user_id},\n";
echo "        nonce: '{$profile_nonce}'\n";
echo "    }\n";
echo "});\n";
echo "</pre>";

echo "<h4>For Users List Page:</h4>\n";
echo "<pre>";
echo "jQuery.ajax({\n";
echo "    url: ajaxurl,\n";
echo "    type: 'POST',\n";
echo "    data: {\n";
echo "        action: 'generate_user_code',\n";
echo "        user_id: {$test_user_id},\n";
echo "        nonce: '{$list_nonce}'\n";
echo "    }\n";
echo "});\n";
echo "</pre>";

// Test 5: Check current user permissions
echo "<h3>Current User Permissions:</h3>\n";
$current_user = wp_get_current_user();
echo "<p>Current User: {$current_user->display_name} (ID: {$current_user->ID})</p>\n";
echo "<p>Can edit users: " . (current_user_can('edit_users') ? 'YES' : 'NO') . "</p>\n";
echo "<p>Can manage options: " . (current_user_can('manage_options') ? 'YES' : 'NO') . "</p>\n";

?>

<script>
// Test JavaScript for debugging
console.log('User Code Generation Test Page Loaded');

// Function to test AJAX call
function testAjaxCall(userId, nonce, context) {
    console.log('Testing AJAX for context:', context);
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'generate_user_code',
            user_id: userId,
            nonce: nonce
        },
        success: function(response) {
            console.log(context + ' Success:', response);
        },
        error: function(xhr, status, error) {
            console.log(context + ' Error:', status, error);
            console.log('Response Text:', xhr.responseText);
        }
    });
}

// Only run if we have jQuery and ajaxurl
if (typeof jQuery !== 'undefined' && typeof ajaxurl !== 'undefined') {
    jQuery(document).ready(function($) {
        console.log('Ready to test AJAX calls');
        
        // Test both nonce types
        // testAjaxCall(<?php echo $test_user_id; ?>, '<?php echo $profile_nonce; ?>', 'Profile Context');
        // testAjaxCall(<?php echo $test_user_id; ?>, '<?php echo $list_nonce; ?>', 'List Context');
    });
} else {
    console.log('jQuery or ajaxurl not available');
}
</script>
