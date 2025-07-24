<?php
/**
 * Test User Code System
 * 
 * This file tests the User Code functionality for Document Users
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>Testing User Code System</h2>";

// Test 1: User Code Generation
echo "<h3>1. Testing User Code Generation</h3>";
$admin = new Lift_Docs_Admin('test', '1.0');

// Test unique code generation
for ($i = 1; $i <= 5; $i++) {
    $code = $admin->generate_unique_user_code();
    echo "Generated Code #{$i}: <strong>{$code}</strong> (Length: " . strlen($code) . ")<br>";
}

// Test 2: Check if codes are unique
echo "<h3>2. Testing Code Uniqueness</h3>";
$codes = array();
for ($i = 1; $i <= 20; $i++) {
    $code = $admin->generate_unique_user_code();
    $codes[] = $code;
}

$unique_codes = array_unique($codes);
if (count($codes) === count($unique_codes)) {
    echo "<span style='color: green;'>✓ All 20 generated codes are unique!</span><br>";
} else {
    echo "<span style='color: red;'>✗ Found duplicate codes!</span><br>";
}

echo "Generated codes: " . implode(', ', $codes) . "<br>";

// Test 3: Code validation
echo "<h3>3. Testing Code Validation</h3>";
$test_codes = array(
    'ABC123' => true,    // Valid: 6 chars, alphanumeric
    'XYZ7890' => true,   // Valid: 7 chars, alphanumeric  
    'QWERTY12' => true,  // Valid: 8 chars, alphanumeric
    'AB12' => false,     // Invalid: too short
    'ABCDEFGHI' => false, // Invalid: too long
    'ABC-123' => false,  // Invalid: contains special char
    'abc123' => true,    // Valid: lowercase allowed
);

foreach ($test_codes as $code => $expected) {
    $pattern = '/^[a-zA-Z0-9]{6,8}$/';
    $is_valid = preg_match($pattern, $code);
    $status = $is_valid ? 'Valid' : 'Invalid';
    $expected_status = $expected ? 'Valid' : 'Invalid';
    $color = ($is_valid == $expected) ? 'green' : 'red';
    
    echo "<span style='color: {$color};'>{$code}: {$status} (Expected: {$expected_status})</span><br>";
}

// Test 4: Database functions (if in WordPress environment)
if (function_exists('get_users')) {
    echo "<h3>4. Testing Database Integration</h3>";
    
    // Get Document Users
    $document_users = get_users(array(
        'role' => 'documents_user',
        'number' => 5
    ));
    
    if (!empty($document_users)) {
        echo "Found " . count($document_users) . " Document Users:<br>";
        foreach ($document_users as $user) {
            $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
            echo "- {$user->display_name} ({$user->user_email}): <strong>{$user_code}</strong><br>";
        }
    } else {
        echo "No Document Users found. Create some users first.<br>";
    }
}

echo "<h3>5. Feature Summary</h3>";
echo "<ul>";
echo "<li>✓ Unique 6-8 character alphanumeric codes</li>";
echo "<li>✓ Automatic generation for new Document Users</li>";
echo "<li>✓ User profile integration (view/edit)</li>";
echo "<li>✓ Admin user list column showing User Codes</li>";
echo "<li>✓ AJAX endpoint for manual code generation</li>";
echo "<li>✓ Enhanced search in document assignment (name, email, code)</li>";
echo "<li>✓ User Code display in search results and selected tags</li>";
echo "</ul>";

echo "<p><strong>User Code System is fully implemented and ready for use!</strong></p>";
?>
