<?php
/**
 * Test Both Secure Access and Download
 * 
 * This script tests both secure view and download functionality
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo "=== LIFT Docs Secure Access & Download Test ===\n\n";

// Get test document
$docs = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($docs)) {
    echo "❌ No documents found. Creating test data...\n";
    
    // Create test document
    $test_doc_id = wp_insert_post(array(
        'post_title' => 'Test Document for Access',
        'post_content' => 'This is a test document.',
        'post_status' => 'publish',
        'post_type' => 'lift_document'
    ));
    
    if (!is_wp_error($test_doc_id)) {
        // Add file URL
        update_post_meta($test_doc_id, '_lift_doc_file_url', includes_url('js/jquery/jquery.min.js'));
        $doc = get_post($test_doc_id);
        echo "✅ Created test document: {$doc->post_title} (ID: {$doc->ID})\n";
    } else {
        echo "❌ Failed to create test document\n";
        exit;
    }
} else {
    $doc = $docs[0];
}

echo "Testing document: {$doc->post_title} (ID: {$doc->ID})\n\n";

// Ensure settings are correct
$settings = get_option('lift_docs_settings', array());
$settings['enable_secure_links'] = true;

if (empty($settings['encryption_key'])) {
    $settings['encryption_key'] = wp_generate_password(32, false);
}

update_option('lift_docs_settings', $settings);
echo "✅ Settings configured\n\n";

// Test 1: Generate links
echo "1. Generating secure links...\n";
$secure_view_link = LIFT_Docs_Settings::generate_secure_link($doc->ID, 1);
$secure_download_link = LIFT_Docs_Settings::generate_secure_download_link($doc->ID, 1);

echo "   View Link: " . substr($secure_view_link, 0, 80) . "...\n";
echo "   Download Link: " . substr($secure_download_link, 0, 80) . "...\n\n";

// Test 2: Parse tokens
$view_parsed = parse_url($secure_view_link);
parse_str($view_parsed['query'], $view_params);
$view_token = $view_params['lift_secure'] ?? '';

$download_parsed = parse_url($secure_download_link);
parse_str($download_parsed['query'], $download_params);
$download_token = $download_params['lift_secure'] ?? '';

echo "2. Token extraction...\n";
echo "   View token extracted: " . (empty($view_token) ? "❌ FAILED" : "✅ SUCCESS") . "\n";
echo "   Download token extracted: " . (empty($download_token) ? "❌ FAILED" : "✅ SUCCESS") . "\n\n";

// Test 3: Verify tokens (with urldecode)
echo "3. Token verification (with urldecode)...\n";

$view_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($view_token));
echo "   View token verification: ";
if ($view_verification && isset($view_verification['document_id']) && $view_verification['document_id'] == $doc->ID) {
    echo "✅ SUCCESS\n";
} else {
    echo "❌ FAILED\n";
    echo "     Result: " . print_r($view_verification, true) . "\n";
}

$download_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($download_token));
echo "   Download token verification: ";
if ($download_verification && isset($download_verification['document_id']) && $download_verification['document_id'] == $doc->ID) {
    echo "✅ SUCCESS\n";
} else {
    echo "❌ FAILED\n";
    echo "     Result: " . print_r($download_verification, true) . "\n";
}

// Test 4: Check file URL
echo "\n4. File URL check...\n";
$file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
if (empty($file_url)) {
    update_post_meta($doc->ID, '_lift_doc_file_url', includes_url('js/jquery/jquery.min.js'));
    $file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
    echo "   ✅ File URL set: $file_url\n";
} else {
    echo "   ✅ File URL exists: $file_url\n";
}

// Test 5: URL structure check
echo "\n5. URL structure check...\n";
echo "   View URL path: " . parse_url($secure_view_link, PHP_URL_PATH) . "\n";
echo "   Download URL path: " . parse_url($secure_download_link, PHP_URL_PATH) . "\n";

$expected_view = '/lift-docs/secure/';
$expected_download = '/lift-docs/download/';

$view_path_ok = parse_url($secure_view_link, PHP_URL_PATH) === $expected_view;
$download_path_ok = parse_url($secure_download_link, PHP_URL_PATH) === $expected_download;

echo "   View path correct: " . ($view_path_ok ? "✅ YES" : "❌ NO") . "\n";
echo "   Download path correct: " . ($download_path_ok ? "✅ YES" : "❌ NO") . "\n";

echo "\n=== Test URLs ===\n";
echo "Secure View: $secure_view_link\n";
echo "Secure Download: $secure_download_link\n";

echo "\n=== Instructions ===\n";
echo "1. Copy the Secure View URL and test in your browser\n";
echo "2. Copy the Secure Download URL and test in your browser\n";
echo "3. Both should work without 'Access denied' errors\n";

echo "\n=== Rewrite Rules Check ===\n";
// Check if rewrite rules might need flushing
global $wp_rewrite;
$rules = get_option('rewrite_rules');
$lift_rules_exist = false;

foreach ($rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift-docs') !== false) {
        $lift_rules_exist = true;
        break;
    }
}

if (!$lift_rules_exist) {
    echo "❌ LIFT Docs rewrite rules not found in WordPress rewrite rules\n";
    echo "   You may need to flush rewrite rules:\n";
    echo "   - Go to Settings > Permalinks in WordPress admin\n";
    echo "   - Click 'Save Changes' (this flushes rewrite rules)\n";
} else {
    echo "✅ LIFT Docs rewrite rules found\n";
}

echo "\n=== Debug Complete ===\n";
?>
