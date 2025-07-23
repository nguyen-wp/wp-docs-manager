<?php
/**
 * Plugin Name: LIFT Docs System
 * Plugin URI: https://liftcreations.com
 * Description: A comprehensive document management system for WordPress with advanced features.
 * Version: 1.9.0
 * Author: Nguyen Pham
 * Author URI: https://nguyenpham.pro
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lift-docs-system
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WordPress functions are available
if (!function_exists('plugin_dir_path')) {
    return;
}

// Define plugin constants
define('LIFT_DOCS_VERSION', '1.9.0');
define('LIFT_DOCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LIFT_DOCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LIFT_DOCS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main LIFT Docs System Class
 */
class LIFT_Docs_System {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Admin files
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-admin.php';
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-post-types.php';
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-settings.php';
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-frontend.php';
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-ajax.php';
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-secure-links.php';
        require_once LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-layout.php';
        
        // Initialize classes
        if (is_admin()) {
            LIFT_Docs_Admin::get_instance();
            LIFT_Docs_Settings::get_instance();
        }
        
        LIFT_Docs_Post_Types::get_instance();
        LIFT_Docs_Frontend::get_instance();
        LIFT_Docs_Ajax::get_instance();
        LIFT_Docs_Secure_Links::get_instance();
        LIFT_Docs_Layout::get_instance();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Flush rewrite rules on plugin activation
        add_action('init', array($this, 'maybe_flush_rewrite_rules'));
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('lift-docs-system', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'lift-docs-frontend',
            LIFT_DOCS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            LIFT_DOCS_VERSION
        );
        
        wp_enqueue_script(
            'lift-docs-frontend',
            LIFT_DOCS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            LIFT_DOCS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('lift-docs-frontend', 'lift_docs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lift_docs_nonce')
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        wp_enqueue_style(
            'lift-docs-admin',
            LIFT_DOCS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            LIFT_DOCS_VERSION
        );
        
        wp_enqueue_script(
            'lift-docs-admin',
            LIFT_DOCS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            LIFT_DOCS_VERSION,
            true
        );
        
        // Localize script for admin
        wp_localize_script('lift-docs-admin', 'lift_admin_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lift_docs_admin_nonce'),
            'secure_link_nonce' => wp_create_nonce('lift_secure_link_nonce')
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create tables if needed
        $this->create_analytics_table();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        $version = get_option('lift_docs_rewrite_version');
        if ($version !== LIFT_DOCS_VERSION) {
            flush_rewrite_rules();
            update_option('lift_docs_rewrite_version', LIFT_DOCS_VERSION);
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create analytics table
     */
    private function create_analytics_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            action varchar(50) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
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
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'enable_analytics' => true,
            'enable_comments' => true,
            'documents_per_page' => 10,
            'enable_search' => true,
            'enable_categories' => true,
            'enable_tags' => true,
        );
        
        add_option('lift_docs_settings', $default_options);
    }
}

// Initialize the plugin
function lift_docs_system_init() {
    return LIFT_Docs_System::get_instance();
}

// Start the plugin
lift_docs_system_init();
