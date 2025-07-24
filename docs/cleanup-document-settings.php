<?php
/**
 * Cleanup Document Settings Functionality
 * 
 * This file removes all references to Document Settings (featured, private, password)
 * functionality that was removed from the metabox interface.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove Document Settings functionality from Frontend class
 */
add_action('init', 'cleanup_document_settings_functionality', 1);

function cleanup_document_settings_functionality() {
    // Remove the problematic methods from being called
    remove_action('wp', 'LIFT_Docs_Frontend::check_document_access');
}

/**
 * Clean up old meta fields from database
 */
add_action('admin_init', 'cleanup_document_settings_meta', 1);

function cleanup_document_settings_meta() {
    // Only run cleanup once
    if (get_option('lift_docs_settings_cleanup_done', false)) {
        return;
    }
    
    global $wpdb;
    
    // List of meta keys to remove
    $meta_keys_to_remove = array(
        '_lift_doc_featured',
        '_lift_doc_private', 
        '_lift_doc_password_protected',
        '_lift_doc_password'
    );
    
    foreach ($meta_keys_to_remove as $meta_key) {
        $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => $meta_key),
            array('%s')
        );
    }
    
    // Mark cleanup as done
    update_option('lift_docs_settings_cleanup_done', true);
    
    // Log cleanup for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LIFT Docs: Document Settings meta fields cleaned up from database');
    }
}

/**
 * Override any remaining calls to document settings
 */
function lift_docs_disable_settings_checks() {
    // Always return false for private document checks
    add_filter('lift_docs_is_private_document', '__return_false', 999);
    
    // Always return false for password protection checks
    add_filter('lift_docs_is_password_protected', '__return_false', 999);
    
    // Always return false for featured document checks
    add_filter('lift_docs_is_featured_document', '__return_false', 999);
}
add_action('init', 'lift_docs_disable_settings_checks');

/**
 * Remove any admin notices related to document settings
 */
add_action('admin_notices', 'lift_docs_settings_removal_notice');

function lift_docs_settings_removal_notice() {
    $screen = get_current_screen();
    if ($screen && ($screen->post_type === 'lift_document' || $screen->id === 'lift_document')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>LIFT Docs System:</strong> ';
        echo 'Document Settings (Featured, Private, Password Protection) have been removed. ';
        echo 'Only Document Details and Secure Links are now available.';
        echo '</p>';
        echo '</div>';
    }
}

/**
 * Filter out any AJAX actions related to document settings
 */
add_action('wp_ajax_toggle_document_featured', 'lift_docs_disabled_ajax_response');
add_action('wp_ajax_toggle_document_private', 'lift_docs_disabled_ajax_response');

function lift_docs_disabled_ajax_response() {
    wp_send_json_error('Document settings functionality has been disabled.');
}

/**
 * Remove document settings from any queries or displays
 */
add_filter('lift_docs_document_meta', 'remove_settings_from_meta', 10, 2);

function remove_settings_from_meta($meta, $document_id) {
    // Remove settings-related meta from any display
    unset($meta['featured']);
    unset($meta['private']);
    unset($meta['password_protected']);
    unset($meta['password']);
    
    return $meta;
}

/**
 * Modify the document list columns to remove settings indicators
 */
add_filter('manage_lift_document_posts_columns', 'remove_settings_columns', 20);

function remove_settings_columns($columns) {
    unset($columns['featured']);
    unset($columns['private']);
    unset($columns['password']);
    
    return $columns;
}

/**
 * Clean up any remaining JavaScript or CSS related to document settings
 */
add_action('admin_footer', 'cleanup_settings_scripts');

function cleanup_settings_scripts() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Remove any remaining settings-related elements
            $('.lift-doc-settings, .document-settings, .featured-indicator, .private-indicator').remove();
            
            // Hide any buttons related to settings
            $('button[data-action*="featured"], button[data-action*="private"]').hide();
        });
        </script>
        
        <style type="text/css">
        /* Hide any remaining settings elements */
        .lift-doc-settings,
        .document-settings,
        .featured-indicator,
        .private-indicator,
        .password-indicator {
            display: none !important;
        }
        </style>
        <?php
    }
}

/**
 * Log the cleanup process
 */
add_action('wp_loaded', 'log_settings_cleanup');

function log_settings_cleanup() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LIFT Docs: Document Settings cleanup loaded successfully');
    }
}
