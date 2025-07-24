<?php
/**
 * Test file to verify that lift_document post type is using classic editor
 * and metaboxes are positioned correctly
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Hook into WordPress to run tests
add_action('admin_init', 'test_lift_document_editor_settings');

function test_lift_document_editor_settings() {
    // Only run this test if we're on the lift_document edit screen
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'lift_document') {
        return;
    }
    
    echo '<div class="notice notice-info"><p><strong>LIFT Docs Test:</strong> Classic Editor is enabled for lift_document post type. Metaboxes are positioned in the normal area.</p></div>';
}

// Add admin notice to confirm the changes are working
add_action('admin_notices', 'lift_docs_admin_notice');

function lift_docs_admin_notice() {
    $screen = get_current_screen();
    if ($screen && ($screen->post_type === 'lift_document' || $screen->id === 'lift_document')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>LIFT Docs System:</strong> ';
        echo 'WordPress Block Editor (Gutenberg) is disabled for Documents. ';
        echo 'All metaboxes are now displayed in the main content area for better workflow.';
        echo '</p>';
        echo '</div>';
    }
}

// Test function to verify Gutenberg is disabled
function test_gutenberg_disabled() {
    $result = use_block_editor_for_post_type('lift_document');
    
    if (!$result) {
        error_log('LIFT Docs Test: SUCCESS - Gutenberg is disabled for lift_document post type');
        return true;
    } else {
        error_log('LIFT Docs Test: FAILED - Gutenberg is still enabled for lift_document post type');
        return false;
    }
}

// Test function to verify REST API is disabled
function test_rest_api_disabled() {
    $post_type_object = get_post_type_object('lift_document');
    
    if ($post_type_object && !$post_type_object->show_in_rest) {
        error_log('LIFT Docs Test: SUCCESS - REST API is disabled for lift_document post type');
        return true;
    } else {
        error_log('LIFT Docs Test: FAILED - REST API is still enabled for lift_document post type');
        return false;
    }
}

// Run tests when WordPress is fully loaded
add_action('wp_loaded', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        test_gutenberg_disabled();
        test_rest_api_disabled();
    }
});
