<?php
/**
 * Debug Token Verification
 * 
 * This script helps debug token verification issues
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo "=== Token Verification Debug ===\n\n";

// Get test document
$docs = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($docs)) {
    echo "❌ No documents found\n";
    exit;
}

$doc = $docs[0];
echo "Testing document: {$doc->post_title} (ID: {$doc->ID})\n\n";

// Test 1: Generate both types of links
echo "1. Generating links...\n";
$secure_link = LIFT_Docs_Settings::generate_secure_link($doc->ID, 1);
$download_link = LIFT_Docs_Settings::generate_secure_download_link($doc->ID, 1);

echo "   Secure Link: " . substr($secure_link, 0, 80) . "...\n";
echo "   Download Link: " . substr($download_link, 0, 80) . "...\n\n";

// Test 2: Extract tokens
$secure_parsed = parse_url($secure_link);
parse_str($secure_parsed['query'], $secure_params);
$secure_token = $secure_params['lift_secure'] ?? '';

$download_parsed = parse_url($download_link);
parse_str($download_parsed['query'], $download_params);
$download_token = $download_params['lift_secure'] ?? '';

echo "2. Extracted tokens:\n";
echo "   Secure token: " . substr($secure_token, 0, 50) . "...\n";
echo "   Download token: " . substr($download_token, 0, 50) . "...\n";
echo "   Tokens match: " . ($secure_token === $download_token ? "✅ YES" : "❌ NO") . "\n\n";

// Test 3: Verify tokens
echo "3. Verifying tokens...\n";

echo "   Secure token verification:\n";
$secure_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($secure_token));
if ($secure_verification) {
    echo "     ✅ SUCCESS\n";
    echo "     Document ID: " . ($secure_verification['document_id'] ?? 'MISSING') . "\n";
    echo "     Expires: " . ($secure_verification['expires'] ? date('Y-m-d H:i:s', $secure_verification['expires']) : 'Never') . "\n";
} else {
    echo "     ❌ FAILED\n";
}

echo "\n   Download token verification:\n";
$download_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($download_token));
if ($download_verification) {
    echo "     ✅ SUCCESS\n";
    echo "     Document ID: " . ($download_verification['document_id'] ?? 'MISSING') . "\n";
    echo "     Expires: " . ($download_verification['expires'] ? date('Y-m-d H:i:s', $download_verification['expires']) : 'Never') . "\n";
} else {
    echo "     ❌ FAILED\n";
}

// Test 4: Check file URL
echo "\n4. Checking file URL...\n";
$file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
if ($file_url) {
    echo "   ✅ File URL set: " . substr($file_url, 0, 60) . "...\n";
    
    $upload_dir = wp_upload_dir();
    if (strpos($file_url, $upload_dir['baseurl']) === 0) {
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        echo "   Local file path: " . $file_path . "\n";
        echo "   File exists: " . (file_exists($file_path) ? "✅ YES" : "❌ NO") . "\n";
    } else {
        echo "   External URL detected\n";
    }
} else {
    echo "   ❌ No file URL set\n";
}

// Test 5: Check settings
echo "\n5. Plugin settings:\n";
$secure_enabled = LIFT_Docs_Settings::get_setting('enable_secure_links', false);
echo "   Secure links enabled: " . ($secure_enabled ? "✅ YES" : "❌ NO") . "\n";

$encryption_key = LIFT_Docs_Settings::get_encryption_key();
echo "   Encryption key: " . (empty($encryption_key) ? "❌ NOT SET" : "✅ SET (" . strlen($encryption_key) . " chars)") . "\n";

// Test 6: Manual test URLs
echo "\n6. Test URLs:\n";
echo "   Secure view: " . $secure_link . "\n";
echo "   Download: " . $download_link . "\n";

echo "\n=== Debug Complete ===\n";
echo "Try visiting the URLs above to test functionality.\n";
?>
