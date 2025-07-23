<?php
/**
 * Test Script for Download Links
 * 
 * This script tests the download link fu# Test 3: Check file attachment
echo '<h3>Test 3: File Attachment Check</h3>';
$file_url = get_post_meta($doc_id, '_lift_doc_file_url', true);

if ($file_url) {
    echo '<p style="color: green;">✅ File URL set: ' . esc_html($file_url) . '</p>';
    
    // Check if it's a local file
    $upload_dir = wp_upload_dir();
    if (strpos($file_url, $upload_dir['baseurl']) === 0) {
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        if (file_exists($file_path)) {
            echo '<p style="color: green;">✅ Local file exists at: ' . esc_html($file_path) . '</p>';
            echo '<p><strong>File size:</strong> ' . size_format(filesize($file_path)) . '</p>';
        } else {
            echo '<p style="color: red;">❌ Local file not found at: ' . esc_html($file_path) . '</p>';
        }
    } else {
        echo '<p style="color: blue;">ℹ️ External file URL</p>';
    }
} else {
    echo '<p style="color: red;">❌ No file URL set for this document</p>';
}sure consistency.
 * Place this in your plugin directory and access via: yoursite.com/wp-content/plugins/wp-docs-manager/test-download-links.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied: Admin privileges required');
}

echo '<h1>LIFT Docs Download Links Test</h1>';

// Get a test document
$docs = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($docs)) {
    echo '<p style="color: red;">No LIFT documents found. Please create a document first.</p>';
    exit;
}

$doc = $docs[0];
$doc_id = $doc->ID;

echo '<h2>Testing Document: ' . esc_html($doc->post_title) . ' (ID: ' . $doc_id . ')</h2>';

// Test 1: Generate secure download link
echo '<h3>Test 1: Generate Secure Download Link</h3>';
$download_link = LIFT_Docs_Settings::generate_secure_download_link($doc_id, 1);
echo '<p><strong>Generated Link:</strong> <a href="' . esc_url($download_link) . '" target="_blank">' . esc_html($download_link) . '</a></p>';

// Test 2: Extract and verify token
echo '<h3>Test 2: Token Verification</h3>';
$parsed_url = parse_url($download_link);
parse_str($parsed_url['query'], $query_params);
$token = $query_params['lift_secure'] ?? '';

if ($token) {
    echo '<p><strong>Extracted Token:</strong> ' . esc_html($token) . '</p>';
    
    // Verify token
    $verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
    echo '<p><strong>Verification Result:</strong></p>';
    echo '<pre>' . print_r($verification, true) . '</pre>';
    
    if ($verification && isset($verification['document_id'])) {
        echo '<p style="color: green;">✅ Token verification successful!</p>';
        echo '<p><strong>Document ID from token:</strong> ' . $verification['document_id'] . '</p>';
        
        if ($verification['document_id'] == $doc_id) {
            echo '<p style="color: green;">✅ Document ID matches!</p>';
        } else {
            echo '<p style="color: red;">❌ Document ID mismatch!</p>';
        }
        
        // Check expiry
        if (isset($verification['expires'])) {
            $expires_time = $verification['expires'];
            $current_time = time();
            
            if ($expires_time == 0) {
                echo '<p><strong>Expiry:</strong> Never expires</p>';
            } else {
                echo '<p><strong>Expires at:</strong> ' . date('Y-m-d H:i:s', $expires_time) . '</p>';
                echo '<p><strong>Current time:</strong> ' . date('Y-m-d H:i:s', $current_time) . '</p>';
                
                if ($current_time < $expires_time) {
                    echo '<p style="color: green;">✅ Token is still valid</p>';
                } else {
                    echo '<p style="color: red;">❌ Token has expired</p>';
                }
            }
        }
    } else {
        echo '<p style="color: red;">❌ Token verification failed!</p>';
    }
} else {
    echo '<p style="color: red;">❌ No token found in URL!</p>';
}

// Test 3: Check file attachment
echo '<h3>Test 3: File Attachment Check</h3>';
$file_id = get_post_meta($doc_id, '_lift_file_id', true);

if ($file_id) {
    echo '<p style="color: green;">✅ File attached (ID: ' . $file_id . ')</p>';
    
    $file_path = get_attached_file($file_id);
    if ($file_path && file_exists($file_path)) {
        echo '<p style="color: green;">✅ File exists at: ' . esc_html($file_path) . '</p>';
        echo '<p><strong>File size:</strong> ' . size_format(filesize($file_path)) . '</p>';
    } else {
        echo '<p style="color: red;">❌ File not found at: ' . esc_html($file_path) . '</p>';
    }
} else {
    echo '<p style="color: red;">❌ No file attached to this document</p>';
}

// Test 4: Settings check
echo '<h3>Test 4: Plugin Settings</h3>';
$secure_links_enabled = LIFT_Docs_Settings::get_setting('enable_secure_links', false);
echo '<p><strong>Secure Links Enabled:</strong> ' . ($secure_links_enabled ? '✅ Yes' : '❌ No') . '</p>';

$encryption_key = LIFT_Docs_Settings::get_encryption_key();
echo '<p><strong>Encryption Key:</strong> ' . (empty($encryption_key) ? '❌ Not set' : '✅ Set (' . strlen($encryption_key) . ' characters)') . '</p>';

$expiry_hours = LIFT_Docs_Settings::get_setting('secure_link_expiry', 24);
echo '<p><strong>Default Expiry:</strong> ' . $expiry_hours . ' hours</p>';

echo '<h3>Test Summary</h3>';
echo '<p>If all tests show ✅ green checkmarks, your download links should work properly.</p>';
echo '<p>Click the generated download link above to test the actual download functionality.</p>';

// Add some styling
echo '
<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h1, h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
p { margin: 10px 0; }
a { color: #0073aa; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
';
?>
