<?php
/**
 * Complete Encryption System Test
 * 
 * This script thoroughly tests the entire encryption/decryption system
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo '<html><head><title>LIFT Docs Encryption System Test</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px}';
echo '.success{color:green}.error{color:red}.warning{color:orange}.info{color:blue}';
echo 'pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;font-size:12px}';
echo 'h2{border-bottom:2px solid #333;padding-bottom:5px}h3{color:#666}';
echo 'table{border-collapse:collapse;width:100%;margin:10px 0}th,td{border:1px solid #ddd;padding:12px;text-align:left;vertical-align:top}';
echo 'th{background:#f2f2f2;font-weight:bold}.test-box{background:#f9f9f9;padding:15px;margin:10px 0;border-radius:5px;border-left:5px solid #007cba}';
echo '</style></head><body>';

echo '<h1>🔐 LIFT Docs Encryption System Complete Test</h1>';

// Test 1: Settings & Key Management
echo '<h2>1. Settings & Encryption Key</h2>';

echo '<div class="test-box">';
echo '<h3>Current Settings</h3>';
$settings = get_option('lift_docs_settings', array());
$secure_enabled = $settings['enable_secure_links'] ?? false;
$stored_key = $settings['encryption_key'] ?? '';

echo '<table>';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
echo '<tr><td>enable_secure_links</td><td>' . ($secure_enabled ? 'true' : 'false') . '</td><td>' . ($secure_enabled ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . '</td></tr>';
echo '<tr><td>encryption_key (stored)</td><td>' . (empty($stored_key) ? 'Not set' : 'Set (' . strlen($stored_key) . ' chars)') . '</td><td>' . (empty($stored_key) ? '<span class="error">❌</span>' : '<span class="success">✅</span>') . '</td></tr>';

// Test public method
$public_key = LIFT_Docs_Settings::get_encryption_key();
echo '<tr><td>get_encryption_key() (public)</td><td>' . (empty($public_key) ? 'Empty' : 'Set (' . strlen($public_key) . ' chars)') . '</td><td>' . (empty($public_key) ? '<span class="error">❌</span>' : '<span class="success">✅</span>') . '</td></tr>';

echo '</table>';

// Auto-fix if needed
if (!$secure_enabled) {
    $settings['enable_secure_links'] = true;
    update_option('lift_docs_settings', $settings);
    echo '<p class="success">✅ Auto-enabled secure links</p>';
}

if (empty($stored_key)) {
    $new_key = base64_encode(random_bytes(32));
    $settings['encryption_key'] = $new_key;
    update_option('lift_docs_settings', $settings);
    echo '<p class="success">✅ Auto-generated encryption key</p>';
    $stored_key = $new_key;
}

echo '</div>';

// Test 2: Test Document
echo '<h2>2. Test Document</h2>';

echo '<div class="test-box">';
$docs = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($docs)) {
    echo '<p class="warning">No documents found. Creating test document...</p>';
    $test_doc_id = wp_insert_post(array(
        'post_title' => 'Encryption Test Document',
        'post_content' => 'This document is used to test the encryption system.',
        'post_status' => 'publish',
        'post_type' => 'lift_document'
    ));
    
    if (!is_wp_error($test_doc_id)) {
        update_post_meta($test_doc_id, '_lift_doc_file_url', includes_url('js/jquery/jquery.min.js'));
        $doc = get_post($test_doc_id);
        echo '<p class="success">✅ Created test document: ' . $doc->post_title . ' (ID: ' . $doc->ID . ')</p>';
    } else {
        echo '<p class="error">❌ Failed to create test document</p>';
        echo '</body></html>';
        exit;
    }
} else {
    $doc = $docs[0];
    echo '<p class="success">✅ Using document: ' . $doc->post_title . ' (ID: ' . $doc->ID . ')</p>';
    
    // Ensure file URL
    $file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
    if (empty($file_url)) {
        update_post_meta($doc->ID, '_lift_doc_file_url', includes_url('js/jquery/jquery.min.js'));
        echo '<p class="info">ℹ️ Set test file URL</p>';
    }
}
echo '</div>';

// Test 3: Raw Encryption/Decryption
echo '<h2>3. Raw Encryption/Decryption Test</h2>';

echo '<div class="test-box">';
echo '<h3>Direct encrypt_data/decrypt_data Test</h3>';

// Access private methods via reflection
$reflection = new ReflectionClass('LIFT_Docs_Settings');
$encrypt_method = $reflection->getMethod('encrypt_data');
$encrypt_method->setAccessible(true);
$decrypt_method = $reflection->getMethod('decrypt_data');
$decrypt_method->setAccessible(true);
$get_key_internal = $reflection->getMethod('get_encryption_key_internal');
$get_key_internal->setAccessible(true);

$test_data = array(
    'test_field' => 'test_value',
    'document_id' => $doc->ID,
    'timestamp' => time(),
    'expires' => time() + 3600
);

echo '<p><strong>Test Data:</strong></p>';
echo '<pre>' . print_r($test_data, true) . '</pre>';

$internal_key = $get_key_internal->invoke(null);
echo '<p><strong>Internal Key:</strong> ' . substr($internal_key, 0, 20) . '... (' . strlen($internal_key) . ' chars)</p>';

$encrypted = $encrypt_method->invoke(null, $test_data, $internal_key);
echo '<p><strong>Encryption Result:</strong> ' . ($encrypted ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</p>';

if ($encrypted) {
    echo '<p><strong>Encrypted Token:</strong> ' . substr($encrypted, 0, 100) . '... (' . strlen($encrypted) . ' chars)</p>';
    
    $decrypted = $decrypt_method->invoke(null, $encrypted, $internal_key);
    echo '<p><strong>Decryption Result:</strong> ' . ($decrypted ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</p>';
    
    if ($decrypted) {
        echo '<p><strong>Decrypted Data:</strong></p>';
        echo '<pre>' . print_r($decrypted, true) . '</pre>';
        
        $data_match = ($test_data === $decrypted);
        echo '<p><strong>Data Integrity:</strong> ' . ($data_match ? '<span class="success">✅ MATCH</span>' : '<span class="error">❌ MISMATCH</span>') . '</p>';
    }
}
echo '</div>';

// Test 4: generate_secure_link & verify_secure_link
echo '<h2>4. Secure Link Generation & Verification</h2>';

echo '<div class="test-box">';
echo '<h3>generate_secure_link() Test</h3>';

$secure_link = LIFT_Docs_Settings::generate_secure_link($doc->ID, 1);
echo '<p><strong>Generated Link:</strong> <a href="' . esc_url($secure_link) . '" target="_blank">' . esc_html($secure_link) . '</a></p>';

// Extract token
$parsed_url = parse_url($secure_link);
parse_str($parsed_url['query'] ?? '', $query_params);
$extracted_token = $query_params['lift_secure'] ?? '';

echo '<p><strong>Extracted Token:</strong> ' . substr($extracted_token, 0, 100) . '... (' . strlen($extracted_token) . ' chars)</p>';

echo '<h3>verify_secure_link() Test</h3>';

$verification = LIFT_Docs_Settings::verify_secure_link(urldecode($extracted_token));
echo '<p><strong>Verification Result:</strong> ' . ($verification ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</p>';

if ($verification) {
    echo '<p><strong>Verification Data:</strong></p>';
    echo '<pre>' . print_r($verification, true) . '</pre>';
    
    $doc_id_match = (isset($verification['document_id']) && $verification['document_id'] == $doc->ID);
    echo '<p><strong>Document ID Match:</strong> ' . ($doc_id_match ? '<span class="success">✅ MATCH</span>' : '<span class="error">❌ MISMATCH</span>') . '</p>';
    
    if (isset($verification['expires'])) {
        $not_expired = ($verification['expires'] == 0 || time() < $verification['expires']);
        echo '<p><strong>Expiry Check:</strong> ' . ($not_expired ? '<span class="success">✅ VALID</span>' : '<span class="error">❌ EXPIRED</span>') . '</p>';
        echo '<p><strong>Expires At:</strong> ' . ($verification['expires'] ? date('Y-m-d H:i:s', $verification['expires']) : 'Never') . '</p>';
        echo '<p><strong>Current Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    }
}
echo '</div>';

// Test 5: generate_secure_download_link
echo '<h2>5. Secure Download Link Test</h2>';

echo '<div class="test-box">';
echo '<h3>generate_secure_download_link() Test</h3>';

$download_link = LIFT_Docs_Settings::generate_secure_download_link($doc->ID, 1);
echo '<p><strong>Generated Download Link:</strong> <a href="' . esc_url($download_link) . '" target="_blank">' . esc_html($download_link) . '</a></p>';

// Extract download token
$download_parsed = parse_url($download_link);
parse_str($download_parsed['query'] ?? '', $download_params);
$download_token = $download_params['lift_secure'] ?? '';

echo '<p><strong>Download Token:</strong> ' . substr($download_token, 0, 100) . '... (' . strlen($download_token) . ' chars)</p>';

$download_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($download_token));
echo '<p><strong>Download Verification:</strong> ' . ($download_verification ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</p>';

if ($download_verification) {
    echo '<p><strong>Download Verification Data:</strong></p>';
    echo '<pre>' . print_r($download_verification, true) . '</pre>';
}

// Test token compatibility
$tokens_compatible = ($extracted_token === $download_token);
echo '<p><strong>Token Compatibility:</strong> ' . ($tokens_compatible ? '<span class="info">ℹ️ SAME TOKEN FORMAT</span>' : '<span class="info">ℹ️ DIFFERENT TOKEN FORMATS</span>') . '</p>';

echo '</div>';

// Test 6: URL Decoding Test
echo '<h2>6. URL Encoding/Decoding Test</h2>';

echo '<div class="test-box">';
echo '<p><strong>Original Token:</strong> ' . substr($extracted_token, 0, 100) . '...</p>';
echo '<p><strong>URL Decoded:</strong> ' . substr(urldecode($extracted_token), 0, 100) . '...</p>';
echo '<p><strong>Double Decoded:</strong> ' . substr(urldecode(urldecode($extracted_token)), 0, 100) . '...</p>';

$single_decode_verify = LIFT_Docs_Settings::verify_secure_link(urldecode($extracted_token));
$double_decode_verify = LIFT_Docs_Settings::verify_secure_link(urldecode(urldecode($extracted_token)));
$no_decode_verify = LIFT_Docs_Settings::verify_secure_link($extracted_token);

echo '<table>';
echo '<tr><th>Decode Method</th><th>Verification Result</th></tr>';
echo '<tr><td>No Decode</td><td>' . ($no_decode_verify ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</td></tr>';
echo '<tr><td>Single urldecode()</td><td>' . ($single_decode_verify ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</td></tr>';
echo '<tr><td>Double urldecode()</td><td>' . ($double_decode_verify ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>') . '</td></tr>';
echo '</table>';
echo '</div>';

// Test 7: Key Consistency
echo '<h2>7. Key Consistency Test</h2>';

echo '<div class="test-box">';
$public_key = LIFT_Docs_Settings::get_encryption_key();
$settings_key = $settings['encryption_key'] ?? '';
$internal_key = $get_key_internal->invoke(null);

echo '<table>';
echo '<tr><th>Key Source</th><th>Value</th><th>Length</th></tr>';
echo '<tr><td>Settings Array</td><td>' . substr($settings_key, 0, 20) . '...</td><td>' . strlen($settings_key) . '</td></tr>';
echo '<tr><td>Public Method</td><td>' . substr($public_key, 0, 20) . '...</td><td>' . strlen($public_key) . '</td></tr>';
echo '<tr><td>Internal Method</td><td>' . substr($internal_key, 0, 20) . '...</td><td>' . strlen($internal_key) . '</td></tr>';
echo '</table>';

$keys_match = ($settings_key === $public_key && $public_key === $internal_key);
echo '<p><strong>Key Consistency:</strong> ' . ($keys_match ? '<span class="success">✅ ALL MATCH</span>' : '<span class="error">❌ MISMATCH DETECTED</span>') . '</p>';
echo '</div>';

// Test 8: Error Conditions
echo '<h2>8. Error Conditions Test</h2>';

echo '<div class="test-box">';
echo '<h3>Invalid Token Tests</h3>';

$invalid_tests = array(
    'Empty string' => '',
    'Random string' => 'invalid_token_123',
    'Base64 garbage' => base64_encode('garbage_data'),
    'Malformed token' => 'dGVzdA==', // 'test' in base64
);

echo '<table>';
echo '<tr><th>Test Case</th><th>Token</th><th>Verification Result</th></tr>';

foreach ($invalid_tests as $test_name => $invalid_token) {
    $result = LIFT_Docs_Settings::verify_secure_link($invalid_token);
    echo '<tr><td>' . $test_name . '</td><td>' . substr($invalid_token, 0, 30) . '...</td><td>' . ($result ? '<span class="error">❌ SHOULD FAIL</span>' : '<span class="success">✅ CORRECTLY FAILED</span>') . '</td></tr>';
}
echo '</table>';
echo '</div>';

// Final Summary
echo '<h2>9. Final Test Summary</h2>';

$all_tests_passed = (
    $secure_enabled && 
    !empty($stored_key) && 
    !empty($public_key) && 
    $encrypted && 
    $decrypted && 
    ($test_data === $decrypted) &&
    $verification &&
    isset($verification['document_id']) &&
    $verification['document_id'] == $doc->ID &&
    $download_verification &&
    $keys_match
);

echo '<div class="test-box">';
if ($all_tests_passed) {
    echo '<h3 class="success">🎉 All Tests Passed!</h3>';
    echo '<p>The encryption system is working correctly. You can now test the actual secure links:</p>';
    echo '<ul>';
    echo '<li><strong>Secure View:</strong> <a href="' . esc_url($secure_link) . '" target="_blank">Test Secure Access</a></li>';
    echo '<li><strong>Secure Download:</strong> <a href="' . esc_url($download_link) . '" target="_blank">Test Secure Download</a></li>';
    echo '</ul>';
} else {
    echo '<h3 class="error">⚠️ Some Tests Failed</h3>';
    echo '<p>There are issues with the encryption system that need to be addressed.</p>';
}
echo '</div>';

echo '<hr><p><small>Encryption test completed on ' . date('Y-m-d H:i:s') . '</small></p>';
echo '</body></html>';
?>
