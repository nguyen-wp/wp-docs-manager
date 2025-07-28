<?php
/**
 * Plugin Activation and Deactivation Handler
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Activator {
    
    /**
     * Activate plugin
     */
    public static function activate() {
        // Create database tables if needed
        self::create_database_tables();
        
        // Flush rewrite rules to ensure our custom post types work
        flush_rewrite_rules();
        
        // Create roles will be handled by admin class on init
        
        // Set default options
        self::set_default_options();
        
        // Create default login pages
        self::create_default_login_pages();
    }
    
    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        // Flush rewrite rules to clean up
        flush_rewrite_rules();
        
        // Don't remove roles on deactivation as users might still need them
        // Only remove on uninstall
    }
    
    /**
     * Uninstall plugin - remove all data
     */
    public static function uninstall() {
        // Remove custom roles
        self::remove_custom_roles();
        
        // Remove capabilities from existing roles
        self::remove_capabilities_from_roles();
        
        // Remove plugin options
        self::remove_plugin_options();
        
        // Drop custom database tables
        self::drop_database_tables();
        
        // Clean up post meta
        self::cleanup_post_meta();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            action varchar(50) NOT NULL,
            ip_address varchar(100) NOT NULL,
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_settings = array(
            'enable_secure_links' => false,
            'require_login_to_view' => false,
            'require_login_to_download' => false,
            'enable_analytics' => true,
            'layout_style' => 'default',
            'show_download_count' => true,
            'show_file_size' => true,
            'enable_online_view' => true,
        );
        
        // Only set defaults if no settings exist
        if (!get_option('lift_docs_settings')) {
            update_option('lift_docs_settings', $default_settings);
        }
    }
    
    /**
     * Create default login pages
     */
    private static function create_default_login_pages() {
        // Create login page
        $login_page_id = get_option('lift_docs_login_page_id');
        if (!$login_page_id || !get_post($login_page_id)) {
            $login_page = array(
                'post_title' => __('Document Login', 'lift-docs-system'),
                'post_content' => '[docs_login_form]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'document-login'
            );
            
            $login_page_id = wp_insert_post($login_page);
            if ($login_page_id && !is_wp_error($login_page_id)) {
                update_option('lift_docs_login_page_id', $login_page_id);
                // Debug log
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('LIFT Docs Activator: Login page created with ID: ' . $login_page_id);
                }
            } else {
                // Debug log for errors
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('LIFT Docs Activator: Failed to create login page. Error: ' . (is_wp_error($login_page_id) ? $login_page_id->get_error_message() : 'Unknown error'));
                }
            }
        }
        
        // Create dashboard page
        $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
        if (!$dashboard_page_id || !get_post($dashboard_page_id)) {
            $dashboard_page = array(
                'post_title' => __('Document Dashboard', 'lift-docs-system'),
                'post_content' => '[docs_dashboard]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'document-dashboard'
            );
            
            $dashboard_page_id = wp_insert_post($dashboard_page);
            if ($dashboard_page_id && !is_wp_error($dashboard_page_id)) {
                update_option('lift_docs_dashboard_page_id', $dashboard_page_id);
                // Debug log
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('LIFT Docs Activator: Dashboard page created with ID: ' . $dashboard_page_id);
                }
            } else {
                // Debug log for errors
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('LIFT Docs Activator: Failed to create dashboard page. Error: ' . (is_wp_error($dashboard_page_id) ? $dashboard_page_id->get_error_message() : 'Unknown error'));
                }
            }
        }
        
        // Set flag that pages have been created
        update_option('lift_docs_default_pages_created', true);
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LIFT Docs Activator: Default pages creation process completed');
        }
    }
    
    /**
     * Remove custom roles
     */
    private static function remove_custom_roles() {
        remove_role('documents_user');
    }
    
    /**
     * Remove capabilities from existing roles
     */
    private static function remove_capabilities_from_roles() {
        $capabilities_to_remove = array(
            'view_lift_documents',
            'download_lift_documents',
            'read_lift_document',
            'read_private_lift_documents',
            'edit_lift_documents',
            'edit_lift_document',
            'edit_others_lift_documents',
            'edit_published_lift_documents',
            'edit_private_lift_documents',
            'publish_lift_documents',
            'delete_lift_documents',
            'delete_lift_document',
            'delete_others_lift_documents',
            'delete_published_lift_documents',
            'delete_private_lift_documents',
            'manage_lift_doc_categories',
            'edit_lift_doc_categories',
            'delete_lift_doc_categories',
            'assign_lift_doc_categories',
        );
        
        $roles_to_clean = array('administrator', 'editor');
        
        foreach ($roles_to_clean as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities_to_remove as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    /**
     * Remove plugin options
     */
    private static function remove_plugin_options() {
        delete_option('lift_docs_settings');
        delete_option('lift_docs_layout_cleanup_done');
        delete_option('lift_docs_roles_created');
        delete_option('lift_docs_default_pages_created');
        
        // Remove default pages
        $login_page_id = get_option('lift_docs_login_page_id');
        if ($login_page_id) {
            wp_delete_post($login_page_id, true);
            delete_option('lift_docs_login_page_id');
        }
        
        $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
        if ($dashboard_page_id) {
            wp_delete_post($dashboard_page_id, true);
            delete_option('lift_docs_dashboard_page_id');
        }
    }
    
    /**
     * Drop database tables
     */
    private static function drop_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    
    /**
     * Clean up post meta
     */
    private static function cleanup_post_meta() {
        global $wpdb;
        
        $meta_keys_to_remove = array(
            '_lift_doc_file_urls',
            '_lift_doc_file_url',
            '_lift_doc_file_size',
            '_lift_doc_views',
            '_lift_doc_downloads',
            '_lift_doc_layout_settings',
        );
        
        foreach ($meta_keys_to_remove as $meta_key) {
            $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
    }
}
