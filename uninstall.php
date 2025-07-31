<?php
/**
 * Uninstall script for LIFT Docs System
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to delete plugins
if (!current_user_can('activate_plugins')) {
    exit;
}

// Check if this is the correct plugin being uninstalled
if (__FILE__ != WP_UNINSTALL_PLUGIN) {
    exit;
}

/**
 * Clean up function
 */
function lift_docs_cleanup() {
    global $wpdb;

    // Get all documents
    $documents = get_posts(array(
        'post_type' => 'lift_document',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    // Delete all documents and their meta
    foreach ($documents as $document) {
        wp_delete_post($document->ID, true);
    }

    // Delete taxonomies
    $taxonomies = array('lift_doc_category', 'lift_doc_tag');

    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }

    // Delete custom table
    $table_name = $wpdb->prefix . 'lift_docs_analytics';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Delete options
    $options_to_delete = array(
        'lift_docs_settings',
        'lift_docs_version',
        'lift_docs_db_version'
    );

    foreach ($options_to_delete as $option) {
        delete_option($option);
    }

    // Delete transients
    $transients_to_delete = array(
        'lift_docs_popular_documents',
        'lift_docs_analytics_cache',
        'lift_docs_categories_cache'
    );

    foreach ($transients_to_delete as $transient) {
        delete_transient($transient);
    }

    // Delete user meta
    $user_meta_to_delete = array(
        'lift_docs_viewed_documents',
        'lift_docs_favorite_documents'
    );

    foreach ($user_meta_to_delete as $meta_key) {
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => $meta_key),
            array('%s')
        );
    }

    // Clear any cached data
    wp_cache_flush();

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Ask user for confirmation before cleanup
$cleanup_data = get_option('lift_docs_cleanup_on_uninstall', false);

if ($cleanup_data) {
    lift_docs_cleanup();
}

// Optional: Log uninstall for debugging
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('LIFT Docs System: Plugin uninstalled and data cleaned up.');
}
