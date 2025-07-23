<?php
/**
 * Post Types for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Post_Types {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('template_redirect', array($this, 'track_document_view'));
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Document post type
        $document_labels = array(
            'name'                  => _x('Documents', 'Post type general name', 'lift-docs-system'),
            'singular_name'         => _x('Document', 'Post type singular name', 'lift-docs-system'),
            'menu_name'             => _x('Documents', 'Admin Menu text', 'lift-docs-system'),
            'name_admin_bar'        => _x('Document', 'Add New on Toolbar', 'lift-docs-system'),
            'add_new'               => __('Add New', 'lift-docs-system'),
            'add_new_item'          => __('Add New Document', 'lift-docs-system'),
            'new_item'              => __('New Document', 'lift-docs-system'),
            'edit_item'             => __('Edit Document', 'lift-docs-system'),
            'view_item'             => __('View Document', 'lift-docs-system'),
            'all_items'             => __('All Documents', 'lift-docs-system'),
            'search_items'          => __('Search Documents', 'lift-docs-system'),
            'parent_item_colon'     => __('Parent Documents:', 'lift-docs-system'),
            'not_found'             => __('No documents found.', 'lift-docs-system'),
            'not_found_in_trash'    => __('No documents found in Trash.', 'lift-docs-system'),
            'featured_image'        => _x('Document Cover Image', 'Overrides the "Featured Image" phrase', 'lift-docs-system'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'lift-docs-system'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'lift-docs-system'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'lift-docs-system'),
            'archives'              => _x('Document archives', 'The post type archive label', 'lift-docs-system'),
            'insert_into_item'      => _x('Insert into document', 'Overrides the "Insert into post" phrase', 'lift-docs-system'),
            'uploaded_to_this_item' => _x('Uploaded to this document', 'Overrides the "Uploaded to this post" phrase', 'lift-docs-system'),
            'filter_items_list'     => _x('Filter documents list', 'Screen reader text for the filter links', 'lift-docs-system'),
            'items_list_navigation' => _x('Documents list navigation', 'Screen reader text for the pagination', 'lift-docs-system'),
            'items_list'            => _x('Documents list', 'Screen reader text for the items list', 'lift-docs-system'),
        );
        
        // Check if secure links are enabled to modify rewrite
        $secure_links_enabled = LIFT_Docs_Settings::get_setting('enable_secure_links', false);
        $rewrite_setting = $secure_links_enabled ? false : array('slug' => 'documents');
        
        $document_args = array(
            'labels'             => $document_labels,
            'public'             => true,
            'publicly_queryable' => !$secure_links_enabled, // Hide from public queries if secure
            'show_ui'            => true,
            'show_in_menu'       => false, // We'll add it to our custom menu
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'query_var'          => true,
            'rewrite'            => $rewrite_setting,
            'capability_type'    => 'post',
            'has_archive'        => !$secure_links_enabled, // Hide archive if secure
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => array('title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author'),
            'show_in_rest'       => true,
            'rest_base'          => 'documents',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
        
        register_post_type('lift_document', $document_args);
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Document Categories
        $category_labels = array(
            'name'              => _x('Document Categories', 'taxonomy general name', 'lift-docs-system'),
            'singular_name'     => _x('Document Category', 'taxonomy singular name', 'lift-docs-system'),
            'search_items'      => __('Search Categories', 'lift-docs-system'),
            'all_items'         => __('All Categories', 'lift-docs-system'),
            'parent_item'       => __('Parent Category', 'lift-docs-system'),
            'parent_item_colon' => __('Parent Category:', 'lift-docs-system'),
            'edit_item'         => __('Edit Category', 'lift-docs-system'),
            'update_item'       => __('Update Category', 'lift-docs-system'),
            'add_new_item'      => __('Add New Category', 'lift-docs-system'),
            'new_item_name'     => __('New Category Name', 'lift-docs-system'),
            'menu_name'         => __('Categories', 'lift-docs-system'),
        );
        
        $category_args = array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'document-category'),
            'show_in_rest'      => true,
            'rest_base'         => 'document-categories',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
        );
        
        register_taxonomy('lift_doc_category', array('lift_document'), $category_args);
        
        // Document Tags
        $tag_labels = array(
            'name'                       => _x('Document Tags', 'taxonomy general name', 'lift-docs-system'),
            'singular_name'              => _x('Document Tag', 'taxonomy singular name', 'lift-docs-system'),
            'search_items'               => __('Search Tags', 'lift-docs-system'),
            'popular_items'              => __('Popular Tags', 'lift-docs-system'),
            'all_items'                  => __('All Tags', 'lift-docs-system'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Tag', 'lift-docs-system'),
            'update_item'                => __('Update Tag', 'lift-docs-system'),
            'add_new_item'               => __('Add New Tag', 'lift-docs-system'),
            'new_item_name'              => __('New Tag Name', 'lift-docs-system'),
            'separate_items_with_commas' => __('Separate tags with commas', 'lift-docs-system'),
            'add_or_remove_items'        => __('Add or remove tags', 'lift-docs-system'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'lift-docs-system'),
            'not_found'                  => __('No tags found.', 'lift-docs-system'),
            'menu_name'                  => __('Tags', 'lift-docs-system'),
        );
        
        $tag_args = array(
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'document-tag'),
            'show_in_rest'          => true,
            'rest_base'             => 'document-tags',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
        );
        
        register_taxonomy('lift_doc_tag', array('lift_document'), $tag_args);
    }
    
    /**
     * Track document views
     */
    public function track_document_view() {
        if (!is_singular('lift_document')) {
            return;
        }
        
        global $post;
        
        // Check if analytics is enabled
        $settings = get_option('lift_docs_settings', array());
        if (empty($settings['enable_analytics'])) {
            return;
        }
        
        // Don't track admin views
        if (current_user_can('edit_posts')) {
            return;
        }
        
        // Don't track bot views
        if ($this->is_bot()) {
            return;
        }
        
        // Track the view
        $this->record_analytics_event($post->ID, 'view');
        
        // Update view count
        $current_views = get_post_meta($post->ID, '_lift_doc_views', true);
        $current_views = $current_views ? intval($current_views) : 0;
        update_post_meta($post->ID, '_lift_doc_views', $current_views + 1);
    }
    
    /**
     * Check if the current visitor is a bot
     */
    private function is_bot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $bots = array(
            'googlebot',
            'bingbot',
            'slurp',
            'crawler',
            'spider',
            'bot',
            'facebookexternalhit'
        );
        
        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Record analytics event
     */
    private function record_analytics_event($document_id, $action) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $user_id = get_current_user_id();
        $user_id = $user_id ? $user_id : null;
        
        $ip_address = $this->get_user_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
                'user_id' => $user_id,
                'action' => $action,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'timestamp' => current_time('mysql')
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
