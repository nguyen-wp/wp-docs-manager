<?php
/**
 * Complete LIFT Docs Debug Script
 * 
 * This comprehensive script checks everything needed for secure access
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Admin access required');
}

echo '<html><head><title>LIFT Docs Complete Debug</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px}';
echo '.success{color:green}.error{color:red}.warning{color:orange}';
echo 'pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto}';
echo 'h2{border-bottom:2px solid #333;padding-bottom:5px}h3{color:#666}';
echo 'table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}';
echo 'th{background:#f2f2f2}</style></head><body>';

echo '<h1>🔍 LIFT Docs Complete Debug Report</h1>';

// Step 1: Plugin Status
echo '<h2>1. Plugin Status</h2>';
$is_plugin_active = is_plugin_active('wp-docs-manager/lift-docs-system.php');
echo '<p>Plugin Active: ' . ($is_plugin_active ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>') . '</p>';

// Step 2: Settings Check
echo '<h2>2. Settings Check</h2>';
$settings = get_option('lift_docs_settings', array());
$secure_enabled = $settings['enable_secure_links'] ?? false;
$encryption_key = $settings['encryption_key'] ?? '';

echo '<table>';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
echo '<tr><td>enable_secure_links</td><td>' . ($secure_enabled ? 'true' : 'false') . '</td><td>' . ($secure_enabled ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . '</td></tr>';
echo '<tr><td>encryption_key</td><td>' . (empty($encryption_key) ? 'Not set' : 'Set (' . strlen($encryption_key) . ' chars)') . '</td><td>' . (empty($encryption_key) ? '<span class="error">❌</span>' : '<span class="success">✅</span>') . '</td></tr>';
echo '</table>';

// Auto-fix settings if needed
if (!$secure_enabled || empty($encryption_key)) {
    echo '<p><strong>Auto-fixing settings...</strong></p>';
    $settings['enable_secure_links'] = true;
    if (empty($encryption_key)) {
        $settings['encryption_key'] = wp_generate_password(32, false);
    }
    update_option('lift_docs_settings', $settings);
    echo '<p class="success">✅ Settings updated!</p>';
}

// Step 3: Test Document
echo '<h2>3. Test Document</h2>';
$docs = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (empty($docs)) {
    echo '<p class="warning">No documents found. Creating test document...</p>';
    $test_doc_id = wp_insert_post(array(
        'post_title' => 'Debug Test Document',
        'post_content' => 'This is a test document for debugging secure access.',
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
    
    // Ensure file URL is set
    $file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
    if (empty($file_url)) {
        update_post_meta($doc->ID, '_lift_doc_file_url', includes_url('js/jquery/jquery.min.js'));
        echo '<p class="success">✅ File URL set</p>';
    }
}

// Step 4: Rewrite Rules
echo '<h2>4. Rewrite Rules</h2>';
$rules = get_option('rewrite_rules');
$lift_rules = array();

foreach ($rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift-docs') !== false) {
        $lift_rules[$pattern] = $replacement;
    }
}

if (empty($lift_rules)) {
    echo '<p class="error">❌ No LIFT Docs rewrite rules found</p>';
    echo '<p>Attempting to flush rewrite rules...</p>';
    
    // Force add rules
    $secure_links = LIFT_Docs_Secure_Links::get_instance();
    flush_rewrite_rules(false);
    
    // Check again
    $rules = get_option('rewrite_rules');
    $lift_rules = array();
    foreach ($rules as $pattern => $replacement) {
        if (strpos($pattern, 'lift-docs') !== false) {
            $lift_rules[$pattern] = $replacement;
        }
    }
    
    if (!empty($lift_rules)) {
        echo '<p class="success">✅ Rewrite rules added after flush</p>';
    } else {
        echo '<p class="error">❌ Still no rules found. You may need to go to Settings > Permalinks > Save Changes</p>';
    }
} else {
    echo '<p class="success">✅ LIFT Docs rewrite rules found</p>';
}

echo '<table>';
echo '<tr><th>Pattern</th><th>Replacement</th></tr>';
foreach ($lift_rules as $pattern => $replacement) {
    echo '<tr><td>' . esc_html($pattern) . '</td><td>' . esc_html($replacement) . '</td></tr>';
}
echo '</table>';

// Step 5: Generate Test Links
echo '<h2>5. Generate Test Links</h2>';
$secure_view_link = LIFT_Docs_Settings::generate_secure_link($doc->ID, 1);
$secure_download_link = LIFT_Docs_Settings::generate_secure_download_link($doc->ID, 1);

echo '<table>';
echo '<tr><th>Link Type</th><th>URL</th></tr>';
echo '<tr><td>Secure View</td><td><a href="' . esc_url($secure_view_link) . '" target="_blank">' . esc_html($secure_view_link) . '</a></td></tr>';
echo '<tr><td>Secure Download</td><td><a href="' . esc_url($secure_download_link) . '" target="_blank">' . esc_html($secure_download_link) . '</a></td></tr>';
echo '</table>';

// Step 6: Token Verification
echo '<h2>6. Token Verification</h2>';

// Extract tokens
$view_parsed = parse_url($secure_view_link);
parse_str($view_parsed['query'], $view_params);
$view_token = $view_params['lift_secure'] ?? '';

$download_parsed = parse_url($secure_download_link);
parse_str($download_parsed['query'], $download_params);
$download_token = $download_params['lift_secure'] ?? '';

echo '<h3>Raw Tokens:</h3>';
echo '<p><strong>View Token:</strong> ' . esc_html(substr($view_token, 0, 100)) . '...</p>';
echo '<p><strong>Download Token:</strong> ' . esc_html(substr($download_token, 0, 100)) . '...</p>';

// Verify tokens
echo '<h3>Token Verification Results:</h3>';
echo '<table>';
echo '<tr><th>Token Type</th><th>Verification Result</th><th>Document ID</th><th>Status</th></tr>';

$view_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($view_token));
$view_status = ($view_verification && isset($view_verification['document_id']) && $view_verification['document_id'] == $doc->ID) ? 'success' : 'error';
echo '<tr><td>View Token</td><td>' . print_r($view_verification, true) . '</td><td>' . ($view_verification['document_id'] ?? 'N/A') . '</td><td><span class="' . $view_status . '">' . ($view_status === 'success' ? '✅' : '❌') . '</span></td></tr>';

$download_verification = LIFT_Docs_Settings::verify_secure_link(urldecode($download_token));
$download_status = ($download_verification && isset($download_verification['document_id']) && $download_verification['document_id'] == $doc->ID) ? 'success' : 'error';
echo '<tr><td>Download Token</td><td>' . print_r($download_verification, true) . '</td><td>' . ($download_verification['document_id'] ?? 'N/A') . '</td><td><span class="' . $download_status . '">' . ($download_status === 'success' ? '✅' : '❌') . '</span></td></tr>';

echo '</table>';

// Step 7: Manual Test Instructions
echo '<h2>7. Manual Test Instructions</h2>';
echo '<div style="background:#e7f3ff;padding:15px;border-radius:5px;border-left:5px solid #2196F3">';
echo '<h3>🧪 Test Steps:</h3>';
echo '<ol>';
echo '<li><strong>Test Secure View:</strong> Click the "Secure View" link above. You should see the document content with secure access notice.</li>';
echo '<li><strong>Test Download:</strong> Click the "Secure Download" link above. It should download the test file (jQuery).</li>';
echo '<li><strong>Check for Errors:</strong> If you see "Access denied" or 404 errors, check the issues below.</li>';
echo '</ol>';
echo '</div>';

// Step 8: Common Issues
echo '<h2>8. Common Issues & Solutions</h2>';
echo '<div style="background:#fff3cd;padding:15px;border-radius:5px;border-left:5px solid #ffc107">';

if (empty($lift_rules)) {
    echo '<h3>🔧 Rewrite Rules Issue:</h3>';
    echo '<p>1. Go to WordPress Admin > Settings > Permalinks</p>';
    echo '<p>2. Click "Save Changes" (this flushes rewrite rules)</p>';
    echo '<p>3. Try the links again</p>';
}

if (!$secure_enabled) {
    echo '<h3>🔧 Secure Links Disabled:</h3>';
    echo '<p>Secure links are disabled in settings. This should be auto-fixed above.</p>';
}

if (empty($encryption_key)) {
    echo '<h3>🔧 Missing Encryption Key:</h3>';
    echo '<p>Encryption key is missing. This should be auto-fixed above.</p>';
}

echo '<h3>🔧 If Still Not Working:</h3>';
echo '<ol>';
echo '<li>Check WordPress debug.log for error messages</li>';
echo '<li>Ensure the plugin is properly activated</li>';
echo '<li>Try deactivating and reactivating the plugin</li>';
echo '<li>Check if there are conflicts with other plugins</li>';
echo '</ol>';
echo '</div>';

// Step 9: Final Status
echo '<h2>9. Final Status</h2>';
$all_good = $secure_enabled && !empty($encryption_key) && !empty($lift_rules) && 
           ($view_status === 'success') && ($download_status === 'success');

if ($all_good) {
    echo '<div style="background:#d4edda;padding:15px;border-radius:5px;border-left:5px solid #28a745">';
    echo '<h3 class="success">🎉 All Systems Go!</h3>';
    echo '<p>Everything looks good! Your secure links should work properly.</p>';
    echo '</div>';
} else {
    echo '<div style="background:#f8d7da;padding:15px;border-radius:5px;border-left:5px solid #dc3545">';
    echo '<h3 class="error">⚠️ Issues Found</h3>';
    echo '<p>Some issues were detected. Please follow the solutions above.</p>';
    echo '</div>';
}

echo '<hr><p><small>Debug report generated on ' . date('Y-m-d H:i:s') . '</small></p>';
echo '</body></html>';
?>
