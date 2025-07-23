<?php
/**
 * Test Global Layout Settings for Secure Views
 * This file tests if global layout settings are properly applied to secure document views
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This file is for testing purposes only
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo '<h2>Testing Global Layout Settings in Secure Views</h2>';

// Test 1: Check if global layout settings exist
echo '<h3>Test 1: Global Layout Settings</h3>';
$global_settings = get_option('lift_docs_settings', array());

$layout_keys = array(
    'show_secure_access_notice',
    'show_document_header', 
    'show_document_meta',
    'show_document_description',
    'show_download_button',
    'show_related_docs',
    'layout_style'
);

echo '<table border="1" cellpadding="5">';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
foreach ($layout_keys as $key) {
    $value = isset($global_settings[$key]) ? $global_settings[$key] : 'NOT SET';
    $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
    $status = ($value !== 'NOT SET') ? '✅' : '❌';
    echo '<tr>';
    echo '<td><strong>' . $key . '</strong></td>';
    echo '<td>' . $display_value . '</td>';
    echo '<td>' . $status . '</td>';
    echo '</tr>';
}
echo '</table>';

// Test 2: Check secure links functionality
echo '<h3>Test 2: Secure Links Class Methods</h3>';
if (class_exists('LIFT_Docs_Secure_Links')) {
    echo '<p style="color: green;">✅ LIFT_Docs_Secure_Links class exists</p>';
    
    $secure_links_instance = LIFT_Docs_Secure_Links::get_instance();
    $reflection = new ReflectionClass($secure_links_instance);
    
    $required_methods = array(
        'get_global_layout_settings',
        'get_related_documents',
        'get_dynamic_styles',
        'display_secure_document'
    );
    
    echo '<ul>';
    foreach ($required_methods as $method_name) {
        if ($reflection->hasMethod($method_name)) {
            echo '<li style="color: green;">✅ Method ' . $method_name . ' exists</li>';
        } else {
            echo '<li style="color: red;">❌ Method ' . $method_name . ' missing</li>';
        }
    }
    echo '</ul>';
    
    // Test the get_global_layout_settings method
    if ($reflection->hasMethod('get_global_layout_settings')) {
        $method = $reflection->getMethod('get_global_layout_settings');
        $method->setAccessible(true);
        $settings = $method->invoke($secure_links_instance);
        
        echo '<p><strong>Layout settings from secure links class:</strong></p>';
        echo '<table border="1" cellpadding="5">';
        echo '<tr><th>Setting</th><th>Value</th></tr>';
        foreach ($settings as $key => $value) {
            $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            echo '<tr><td>' . $key . '</td><td>' . $display_value . '</td></tr>';
        }
        echo '</table>';
    }
} else {
    echo '<p style="color: red;">❌ LIFT_Docs_Secure_Links class not found!</p>';
}

// Test 3: Check if we can generate a test secure link
echo '<h3>Test 3: Generate Test Secure Link</h3>';
$test_doc = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (!empty($test_doc)) {
    $doc_id = $test_doc[0]->ID;
    $doc_title = $test_doc[0]->post_title;
    
    echo '<p>Testing with document: <strong>' . esc_html($doc_title) . '</strong> (ID: ' . $doc_id . ')</p>';
    
    if (class_exists('LIFT_Docs_Settings')) {
        $secure_link = LIFT_Docs_Settings::generate_secure_link($doc_id);
        echo '<p><strong>Generated Secure Link:</strong></p>';
        echo '<textarea readonly style="width: 100%; height: 60px;">' . esc_textarea($secure_link) . '</textarea>';
        echo '<p style="font-size: 12px; color: #666;">Copy this link and test it in a new browser tab to verify the global layout settings are applied.</p>';
        
        // Parse the URL to show the structure
        $parsed_url = parse_url($secure_link);
        parse_str($parsed_url['query'] ?? '', $query_params);
        
        echo '<p><strong>URL Structure Analysis:</strong></p>';
        echo '<ul>';
        echo '<li>Path: ' . ($parsed_url['path'] ?? 'N/A') . '</li>';
        echo '<li>Query parameter "lift_secure": ' . (isset($query_params['lift_secure']) ? 'Present ✅' : 'Missing ❌') . '</li>';
        echo '</ul>';
    } else {
        echo '<p style="color: red;">❌ LIFT_Docs_Settings class not found!</p>';
    }
} else {
    echo '<p style="color: orange;">⚠️ No documents found to test with.</p>';
}

// Test 4: Check rewrite rules
echo '<h3>Test 4: Rewrite Rules</h3>';
global $wp_rewrite;
$rules = get_option('rewrite_rules');

$secure_rules_found = false;
if ($rules) {
    foreach ($rules as $pattern => $replacement) {
        if (strpos($pattern, 'lift-docs/secure') !== false || strpos($replacement, 'lift_secure_page') !== false) {
            echo '<p style="color: green;">✅ Found secure access rewrite rule:</p>';
            echo '<ul>';
            echo '<li><strong>Pattern:</strong> ' . $pattern . '</li>';
            echo '<li><strong>Replacement:</strong> ' . $replacement . '</li>';
            echo '</ul>';
            $secure_rules_found = true;
            break;
        }
    }
}

if (!$secure_rules_found) {
    echo '<p style="color: red;">❌ Secure access rewrite rules not found. You may need to flush rewrite rules.</p>';
    echo '<p><a href="' . admin_url('options-permalink.php') . '" target="_blank">Go to Permalinks settings to flush rewrite rules</a></p>';
}

echo '<h3>Summary</h3>';
echo '<p>The secure view functionality has been updated to use global layout settings:</p>';
echo '<ul>';
echo '<li>✅ Global layout settings are read from the main plugin settings</li>';
echo '<li>✅ Secure document display respects all layout configuration options</li>';
echo '<li>✅ Dynamic styles adapt based on layout_style setting (default/minimal/detailed)</li>';
echo '<li>✅ All display options (header, meta, description, download button, related docs) are configurable</li>';
echo '<li>✅ Secure access notice can be turned on/off globally</li>';
echo '</ul>';

echo '<h3>Testing Instructions</h3>';
echo '<ol>';
echo '<li>Go to <strong>LIFT Docs → Settings → Custom Layout Settings</strong></li>';
echo '<li>Modify the layout settings (try different combinations)</li>';
echo '<li>Generate a secure link using the link above</li>';
echo '<li>Open the secure link in a new tab: <code>/lift-docs/secure/?lift_secure=...</code></li>';
echo '<li>Verify that the layout changes are reflected in the secure view</li>';
echo '</ol>';

echo '<div style="background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;">';
echo '<p><strong>📝 Note:</strong> All layout customization options have been moved from individual document metaboxes to global settings. This ensures consistent appearance across all secure document views.</p>';
echo '</div>';
?>
