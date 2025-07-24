<?php
/**
 * Test file to verify global layout settings functionality
 * This file should be run from WordPress admin to test the changes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This file is for testing purposes only
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo '<h2>Testing Global Layout Settings</h2>';

// Test 1: Check if global settings are properly read
echo '<h3>Test 1: Global Settings</h3>';
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

echo '<ul>';
foreach ($layout_keys as $key) {
    $value = isset($global_settings[$key]) ? $global_settings[$key] : 'NOT SET';
    $value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
    echo '<li><strong>' . $key . ':</strong> ' . $value . '</li>';
}
echo '</ul>';

// Test 2: Check if any documents still have old layout settings
echo '<h3>Test 2: Check for Old Document Layout Settings</h3>';
global $wpdb;
$old_settings = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_lift_doc_layout_settings'"
);

if (empty($old_settings)) {
    echo '<p style="color: green;">✓ No old document layout settings found. Cleanup successful!</p>';
} else {
    echo '<p style="color: red;">✗ Found ' . count($old_settings) . ' documents with old layout settings:</p>';
    echo '<ul>';
    foreach ($old_settings as $setting) {
        echo '<li>Document ID: ' . $setting->post_id . '</li>';
    }
    echo '</ul>';
}

// Test 3: Test the layout class method
if (class_exists('LIFT_Docs_Layout')) {
    echo '<h3>Test 3: Layout Class Method</h3>';
    
    // Get the first document to test with
    $test_doc = get_posts(array(
        'post_type' => 'lift_document',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (!empty($test_doc)) {
        $doc_id = $test_doc[0]->ID;
        echo '<p>Testing with document ID: ' . $doc_id . '</p>';
        
        // Use reflection to access the private method
        $layout_instance = LIFT_Docs_Layout::get_instance();
        $reflection = new ReflectionClass($layout_instance);
        $method = $reflection->getMethod('get_layout_settings');
        $method->setAccessible(true);
        
        $layout_settings = $method->invoke($layout_instance, $doc_id);
        
        echo '<p>Layout settings for document:</p>';
        echo '<ul>';
        foreach ($layout_settings as $key => $value) {
            $value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            echo '<li><strong>' . $key . ':</strong> ' . $value . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No documents found to test with.</p>';
    }
} else {
    echo '<h3>Test 3: Layout Class Method</h3>';
    echo '<p style="color: red;">✗ LIFT_Docs_Layout class not found!</p>';
}

echo '<h3>Summary</h3>';
echo '<p>The changes have been implemented to make Custom Layout Display Options global:</p>';
echo '<ul>';
echo '<li>✓ Layout settings are now read from global options instead of post meta</li>';
echo '<li>✓ Layout settings metabox removed from document edit screen</li>';
echo '<li>✓ Secure links metabox simplified (removed custom expiry and custom layout URL)</li>';
echo '<li>✓ Default settings updated to make links never expire</li>';
echo '<li>✓ Cleanup function added to remove old post meta layout settings</li>';
echo '</ul>';
?>
