<?php
/**
 * Test file for integrated Document Details & Secure Links metabox
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add test notice
add_action('admin_notices', 'lift_docs_integration_test_notice');

function lift_docs_integration_test_notice() {
    $screen = get_current_screen();
    if ($screen && ($screen->post_type === 'lift_document' || $screen->id === 'lift_document')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>LIFT Docs Integration Test:</strong> ';
        echo 'Document Details and Secure Links are now combined in a single metabox. ';
        echo 'Check the "Document Details & Secure Links" section below.';
        echo '</p>';
        echo '</div>';
    }
}

// Test function to verify metabox integration
add_action('add_meta_boxes', 'test_metabox_integration', 20);

function test_metabox_integration() {
    global $wp_meta_boxes;
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Check if Document Details metabox exists
        $has_details = isset($wp_meta_boxes['lift_document']['normal']['high']['lift-docs-details']);
        
        // Check if separate Secure Links metabox does NOT exist
        $has_separate_secure = isset($wp_meta_boxes['lift_document']['normal']['default']['lift-docs-secure-links']);
        
        if ($has_details && !$has_separate_secure) {
            error_log('LIFT Docs Integration Test: SUCCESS - Document Details metabox integrated successfully');
        } else {
            error_log('LIFT Docs Integration Test: WARNING - Integration may not be complete');
            error_log('Has Details: ' . ($has_details ? 'YES' : 'NO'));
            error_log('Has Separate Secure: ' . ($has_separate_secure ? 'YES' : 'NO'));
        }
    }
}

// Add custom CSS to style the integrated metabox
add_action('admin_head', 'lift_docs_integration_styles');

function lift_docs_integration_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <style type="text/css">
        /* Styles for integrated metabox */
        #lift-docs-details .form-table tr th[colspan="2"] {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        
        #lift-docs-details .form-table tr th[colspan="2"] h3 {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        #lift-docs-details .form-table textarea.code {
            font-family: 'Courier New', monospace;
            background: #f6f7f7;
            border: 1px solid #ddd;
        }
        
        #lift-docs-details .button {
            margin-top: 5px;
        }
        
        /* Highlight secure links section */
        #lift-docs-details .form-table tr:has(th[colspan="2"]) + tr,
        #lift-docs-details .form-table tr:has(th[colspan="2"]) + tr + tr,
        #lift-docs-details .form-table tr:has(th[colspan="2"]) + tr + tr + tr {
            background: rgba(0, 115, 170, 0.02);
        }
        </style>
        <?php
    }
}

// Test JavaScript functionality
add_action('admin_footer', 'lift_docs_integration_js_test');

function lift_docs_integration_js_test() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test if copy functions are available
            if (typeof copySecureLink === 'function' && typeof copyDownloadLink === 'function') {
                console.log('LIFT Docs Integration Test: Copy functions loaded successfully');
            } else {
                console.warn('LIFT Docs Integration Test: Copy functions may not be loaded');
            }
            
            // Add visual indicator for secure links section
            var secureLinksHeader = $('#lift-docs-details h3:contains("Secure Links")');
            if (secureLinksHeader.length > 0) {
                secureLinksHeader.prepend('✅ ');
                console.log('LIFT Docs Integration Test: Secure Links section found in Document Details');
            }
            
            // Test functionality of copy buttons
            var copyButtons = $('#lift-docs-details button[onclick*="copy"]');
            if (copyButtons.length > 0) {
                console.log('LIFT Docs Integration Test: Found ' + copyButtons.length + ' copy buttons');
            }
        });
        </script>
        <?php
    }
}

// Cleanup function to remove separate secure links metabox if it somehow still exists
add_action('admin_init', 'cleanup_separate_secure_metabox');

function cleanup_separate_secure_metabox() {
    remove_meta_box('lift-docs-secure-links', 'lift_document', 'normal');
    remove_meta_box('lift-docs-secure-links', 'lift_document', 'side');
    remove_meta_box('lift-docs-secure-links', 'lift_document', 'advanced');
}

// Log integration status
add_action('wp_loaded', 'log_integration_status');

function log_integration_status() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LIFT Docs Integration: Document Details & Secure Links integration loaded');
    }
}
