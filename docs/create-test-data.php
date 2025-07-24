<?php
/**
 * Create Test Data for LIFT Docs
 * 
 * This script creates a test document with file URL for testing
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo "=== Creating Test Data ===\n\n";

// Check if test document already exists
$existing = get_posts(array(
    'post_type' => 'lift_document',
    'title' => 'Test Download Document',
    'post_status' => 'any',
    'posts_per_page' => 1
));

if (!empty($existing)) {
    $test_doc = $existing[0];
    echo "Test document already exists: {$test_doc->post_title} (ID: {$test_doc->ID})\n";
} else {
    // Create test document
    $test_doc_id = wp_insert_post(array(
        'post_title' => 'Test Download Document',
        'post_content' => 'This is a test document for download functionality testing.',
        'post_status' => 'publish',
        'post_type' => 'lift_document'
    ));
    
    if (is_wp_error($test_doc_id)) {
        echo "❌ Failed to create test document\n";
        exit;
    }
    
    $test_doc = get_post($test_doc_id);
    echo "✅ Created test document: {$test_doc->post_title} (ID: {$test_doc->ID})\n";
}

// Check if file URL is set
$file_url = get_post_meta($test_doc->ID, '_lift_doc_file_url', true);

if (empty($file_url)) {
    // Create a test file URL (using a WordPress default file)
    $test_file_url = includes_url('js/jquery/jquery.min.js'); // Use jQuery as test file
    
    update_post_meta($test_doc->ID, '_lift_doc_file_url', $test_file_url);
    update_post_meta($test_doc->ID, '_lift_doc_file_size', '95000'); // Approximate size
    
    echo "✅ Set test file URL: $test_file_url\n";
} else {
    echo "File URL already set: $file_url\n";
}

// Enable secure links if not enabled
$secure_enabled = LIFT_Docs_Settings::get_setting('enable_secure_links', false);
if (!$secure_enabled) {
    $settings = get_option('lift_docs_settings', array());
    $settings['enable_secure_links'] = true;
    update_option('lift_docs_settings', $settings);
    echo "✅ Enabled secure links\n";
} else {
    echo "Secure links already enabled\n";
}

// Check encryption key
$encryption_key = LIFT_Docs_Settings::get_encryption_key();
if (empty($encryption_key)) {
    // Generate a key
    $new_key = wp_generate_password(32, false);
    $settings = get_option('lift_docs_settings', array());
    $settings['encryption_key'] = $new_key;
    update_option('lift_docs_settings', $settings);
    echo "✅ Generated encryption key\n";
} else {
    echo "Encryption key already set\n";
}

echo "\n=== Test Data Ready ===\n";
echo "Document ID: {$test_doc->ID}\n";
echo "File URL: " . get_post_meta($test_doc->ID, '_lift_doc_file_url', true) . "\n";

// Generate test links
echo "\n=== Test Links ===\n";
$secure_link = LIFT_Docs_Settings::generate_secure_link($test_doc->ID, 1);
$download_link = LIFT_Docs_Settings::generate_secure_download_link($test_doc->ID, 1);

echo "Secure View: $secure_link\n";
echo "Download: $download_link\n";

echo "\nYou can now test the download functionality!\n";
?>
