<?php
/**
 * Quick Download Links Verification Script# Test 4: Check file attachment
echo "4. Checking file attachment...\n";
$file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
if ($file_url) {
    // Check if it's a local file
    $upload_dir = wp_upload_dir();
    if (strpos($file_url, $upload_dir['baseurl']) === 0) {
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        if (file_exists($file_path)) {
            echo "   ✅ Local file exists: " . basename($file_path) . "\n";
        } else {
            echo "   ❌ Local file not found\n";
        }
    } else {
        echo "   ℹ️ External file URL: " . substr($file_url, 0, 50) . "...\n";
    }
} else {
    echo "   ❌ No file URL set\n";
}his script to quickly verify download links are working
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo "=== LIFT Docs Download Links Quick Test ===\n\n";

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

// Test 1: Generate link
echo "1. Generating download link...\n";
$link = LIFT_Docs_Settings::generate_secure_download_link($doc->ID, 1);
echo "   Generated: " . substr($link, 0, 80) . "...\n";

// Test 2: Extract token
$parsed = parse_url($link);
parse_str($parsed['query'], $params);
$token = $params['lift_secure'] ?? '';
echo "2. Token extracted: " . (empty($token) ? "❌ FAILED" : "✅ SUCCESS") . "\n";

// Test 3: Verify token
echo "3. Verifying token...\n";
$verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
if ($verification && isset($verification['document_id']) && $verification['document_id'] == $doc->ID) {
    echo "   ✅ Token verification SUCCESS\n";
    echo "   Document ID matches: {$verification['document_id']}\n";
} else {
    echo "   ❌ Token verification FAILED\n";
    print_r($verification);
}

// Test 4: Check file attachment
echo "4. Checking file attachment...\n";
$file_id = get_post_meta($doc->ID, '_lift_file_id', true);
if ($file_id) {
    $file_path = get_attached_file($file_id);
    if ($file_path && file_exists($file_path)) {
        echo "   ✅ File exists: " . basename($file_path) . "\n";
    } else {
        echo "   ❌ File not found\n";
    }
} else {
    echo "   ❌ No file attached\n";
}

// Test 5: Settings check
echo "5. Checking settings...\n";
$secure_enabled = LIFT_Docs_Settings::get_setting('enable_secure_links', false);
$encryption_key = LIFT_Docs_Settings::get_encryption_key();
echo "   Secure links: " . ($secure_enabled ? "✅ Enabled" : "❌ Disabled") . "\n";
echo "   Encryption key: " . (empty($encryption_key) ? "❌ Missing" : "✅ Set") . "\n";

echo "\n=== Test Complete ===\n";
echo "If all items show ✅, your download links should work.\n";
echo "Test the actual download by visiting:\n";
echo $link . "\n";
?>
