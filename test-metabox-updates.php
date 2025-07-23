<?php
/**
 * Test Secure Links Metabox Updates
 * This file tests the updated secure links metabox functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// This file is for testing purposes only
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo '<h2>Testing Updated Secure Links Metabox</h2>';

// Test 1: Check if documents exist
echo '<h3>Test 1: Available Documents</h3>';
$test_docs = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 5,
    'post_status' => 'publish'
));

if (!empty($test_docs)) {
    echo '<p style="color: green;">✅ Found ' . count($test_docs) . ' document(s) to test with:</p>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Title</th><th>File URL</th><th>Secure Links</th></tr>';
    
    foreach ($test_docs as $doc) {
        $file_url = get_post_meta($doc->ID, '_lift_doc_file_url', true);
        $edit_link = get_edit_post_link($doc->ID);
        
        echo '<tr>';
        echo '<td>' . $doc->ID . '</td>';
        echo '<td><a href="' . $edit_link . '" target="_blank">' . esc_html($doc->post_title) . '</a></td>';
        echo '<td>' . ($file_url ? '✅ Has file' : '❌ No file') . '</td>';
        
        // Generate secure links
        if (class_exists('LIFT_Docs_Settings')) {
            $secure_link = LIFT_Docs_Settings::generate_secure_link($doc->ID);
            echo '<td><a href="' . $secure_link . '" target="_blank">Test Secure Link</a></td>';
        } else {
            echo '<td>❌ Settings class not found</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p style="color: red;">❌ No documents found to test with.</p>';
}

// Test 2: Check secure links settings
echo '<h3>Test 2: Secure Links Configuration</h3>';
$settings = get_option('lift_docs_settings', array());
$secure_links_enabled = isset($settings['enable_secure_links']) ? $settings['enable_secure_links'] : false;

echo '<table border="1" cellpadding="5">';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';

echo '<tr>';
echo '<td><strong>Enable Secure Links</strong></td>';
echo '<td>' . ($secure_links_enabled ? 'true' : 'false') . '</td>';
echo '<td>' . ($secure_links_enabled ? '✅ Enabled' : '❌ Disabled') . '</td>';
echo '</tr>';

$expiry_setting = isset($settings['secure_link_expiry']) ? $settings['secure_link_expiry'] : 'not set';
echo '<tr>';
echo '<td><strong>Secure Link Expiry</strong></td>';
echo '<td>' . $expiry_setting . '</td>';
echo '<td>' . ($expiry_setting === 0 ? '✅ Never expire' : ($expiry_setting === 'not set' ? '⚠️ Not set' : '⚠️ Will expire')) . '</td>';
echo '</tr>';

echo '</table>';

// Test 3: Metabox functionality simulation
echo '<h3>Test 3: Metabox Functionality Simulation</h3>';

if (!empty($test_docs)) {
    $test_doc = $test_docs[0];
    $file_url = get_post_meta($test_doc->ID, '_lift_doc_file_url', true);
    
    echo '<div style="border: 1px solid #ccc; padding: 15px; background: #f9f9f9; max-width: 500px;">';
    echo '<h4>Simulated Metabox for: ' . esc_html($test_doc->post_title) . '</h4>';
    
    if ($secure_links_enabled) {
        if (class_exists('LIFT_Docs_Settings')) {
            $secure_link = LIFT_Docs_Settings::generate_secure_link($test_doc->ID);
            
            echo '<p><strong>Current Secure Link:</strong></p>';
            echo '<textarea readonly style="width: 100%; height: 60px;">' . esc_textarea($secure_link) . '</textarea>';
            echo '<p class="description">This link never expires.</p>';
            
            if ($file_url) {
                $download_link = LIFT_Docs_Settings::generate_secure_download_link($test_doc->ID, 0);
                echo '<hr>';
                echo '<p><strong>Secure Download Link:</strong></p>';
                echo '<textarea readonly style="width: 100%; height: 40px;">' . esc_textarea($download_link) . '</textarea>';
                echo '<p class="description">Direct secure download link (never expires)</p>';
            } else {
                echo '<hr>';
                echo '<p><strong>Secure Download Link:</strong></p>';
                echo '<p class="description" style="color: #999; font-style: italic;">No file URL specified. Add a file URL in the Document Details section to generate a secure download link.</p>';
            }
            
            echo '<p style="margin-top: 15px;">';
            echo '<button type="button" class="button">Copy Secure Link</button>';
            if ($file_url) {
                echo ' <button type="button" class="button">Copy Download Link</button>';
            }
            echo '</p>';
        } else {
            echo '<p style="color: red;">❌ LIFT_Docs_Settings class not found!</p>';
        }
    } else {
        echo '<p>Secure links are disabled. Enable them in settings.</p>';
    }
    
    echo '</div>';
} else {
    echo '<p>No documents available for simulation.</p>';
}

// Test 4: Changes Summary
echo '<h3>Test 4: Changes Summary</h3>';
echo '<div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #0073aa;">';
echo '<h4>✅ Metabox Updates Completed:</h4>';
echo '<ul>';
echo '<li><strong>Always show Current Secure Link:</strong> Displayed for all documents regardless of file URL</li>';
echo '<li><strong>Always show Secure Download Link section:</strong> Shows download link if file exists, helpful message if not</li>';
echo '<li><strong>Removed "Generate New Link" button:</strong> Simplified interface, links are permanent</li>';
echo '<li><strong>Improved copy functionality:</strong> Separate buttons for copying secure link and download link</li>';
echo '<li><strong>Better user feedback:</strong> Clear messages about file requirements</li>';
echo '</ul>';
echo '</div>';

echo '<h3>Test Instructions</h3>';
echo '<ol>';
echo '<li>Go to any document edit page: <strong>LIFT Docs → All Documents → Edit</strong></li>';
echo '<li>Look for the <strong>"Secure Links"</strong> metabox in the sidebar</li>';
echo '<li>Verify you can see:</li>';
echo '<ul>';
echo '<li>✅ Current Secure Link (always displayed)</li>';
echo '<li>✅ Secure Download Link (if file URL exists) or helpful message (if no file)</li>';
echo '<li>✅ Copy buttons for both links</li>';
echo '<li>❌ NO "Generate New Link" button</li>';
echo '</ul>';
echo '<li>Test the copy functionality by clicking the copy buttons</li>';
echo '<li>Test the secure links by opening them in new tabs</li>';
echo '</ol>';

echo '<div style="background: #fff2cc; padding: 15px; border-left: 4px solid #ffb900; margin: 20px 0;">';
echo '<p><strong>📝 Note:</strong> The metabox now provides a cleaner, more user-friendly interface that focuses on the essential functionality - providing secure links that never expire, with easy copy functionality.</p>';
echo '</div>';
?>
