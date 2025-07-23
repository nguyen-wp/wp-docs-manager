<?php
/**
 * Settings page for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Settings {
    
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
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'lift-docs-system',
            __('LIFT Docs Settings', 'lift-docs-system'),
            __('Settings', 'lift-docs-system'),
            'manage_options',
            'lift-docs-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'lift_docs_settings_group',
            'lift_docs_settings',
            array($this, 'validate_settings')
        );
        
        // General Settings Section
        add_settings_section(
            'lift_docs_general_section',
            __('General Settings', 'lift-docs-system'),
            array($this, 'general_section_callback'),
            'lift-docs-settings'
        );
        
        // Display Settings Section
        add_settings_section(
            'lift_docs_display_section',
            __('Display Settings', 'lift-docs-system'),
            array($this, 'display_section_callback'),
            'lift-docs-settings'
        );
        
        // Security Settings Section
        add_settings_section(
            'lift_docs_security_section',
            __('Security Settings', 'lift-docs-system'),
            array($this, 'security_section_callback'),
            'lift-docs-settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings fields
        add_settings_field(
            'enable_analytics',
            __('Enable Analytics', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_general_section',
            array('field' => 'enable_analytics', 'description' => __('Track document views and downloads', 'lift-docs-system'))
        );
        
        add_settings_field(
            'enable_comments',
            __('Enable Comments', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_general_section',
            array('field' => 'enable_comments', 'description' => __('Allow comments on documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'enable_search',
            __('Enable Search', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_general_section',
            array('field' => 'enable_search', 'description' => __('Enable document search functionality', 'lift-docs-system'))
        );
        
        add_settings_field(
            'enable_categories',
            __('Enable Categories', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_general_section',
            array('field' => 'enable_categories', 'description' => __('Enable document categories', 'lift-docs-system'))
        );
        
        add_settings_field(
            'enable_tags',
            __('Enable Tags', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_general_section',
            array('field' => 'enable_tags', 'description' => __('Enable document tags', 'lift-docs-system'))
        );
        
        // Display settings fields
        add_settings_field(
            'documents_per_page',
            __('Documents Per Page', 'lift-docs-system'),
            array($this, 'number_field_callback'),
            'lift-docs-settings',
            'lift_docs_display_section',
            array('field' => 'documents_per_page', 'description' => __('Number of documents to display per page', 'lift-docs-system'), 'min' => 1, 'max' => 100)
        );
        
        add_settings_field(
            'show_document_meta',
            __('Show Document Meta', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_display_section',
            array('field' => 'show_document_meta', 'description' => __('Display document metadata (date, author, etc.)', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_download_button',
            __('Show Download Button', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_display_section',
            array('field' => 'show_download_button', 'description' => __('Display download button for documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_view_count',
            __('Show View Count', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_display_section',
            array('field' => 'show_view_count', 'description' => __('Display view count for documents', 'lift-docs-system'))
        );
        
        // Security settings fields
        add_settings_field(
            'require_login_to_view',
            __('Require Login to View', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'require_login_to_view', 'description' => __('Require users to be logged in to view documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'require_login_to_download',
            __('Require Login to Download', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'require_login_to_download', 'description' => __('Require users to be logged in to download documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'allowed_file_types',
            __('Allowed File Types', 'lift-docs-system'),
            array($this, 'text_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'allowed_file_types', 'description' => __('Comma-separated list of allowed file extensions (e.g., pdf,doc,docx)', 'lift-docs-system'))
        );
        
        add_settings_field(
            'max_file_size',
            __('Max File Size (MB)', 'lift-docs-system'),
            array($this, 'number_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'max_file_size', 'description' => __('Maximum file size allowed for uploads', 'lift-docs-system'), 'min' => 1, 'max' => 1024)
        );
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('LIFT Docs System Settings', 'lift-docs-system'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lift_docs_settings_group');
                do_settings_sections('lift-docs-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for the LIFT Docs System.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Display section callback
     */
    public function display_section_callback() {
        echo '<p>' . __('Configure how documents are displayed on the frontend.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Security section callback
     */
    public function security_section_callback() {
        echo '<p>' . __('Configure security and access control settings.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $checked = isset($settings[$field]) && $settings[$field] ? 'checked' : '';
        
        echo '<label>';
        echo '<input type="checkbox" name="lift_docs_settings[' . $field . ']" value="1" ' . $checked . ' />';
        echo ' ' . $description;
        echo '</label>';
    }
    
    /**
     * Number field callback
     */
    public function number_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $min = $args['min'] ?? '';
        $max = $args['max'] ?? '';
        
        echo '<input type="number" name="lift_docs_settings[' . $field . ']" value="' . esc_attr($value) . '"';
        if ($min !== '') echo ' min="' . $min . '"';
        if ($max !== '') echo ' max="' . $max . '"';
        echo ' class="small-text" />';
        
        if ($description) {
            echo '<p class="description">' . $description . '</p>';
        }
    }
    
    /**
     * Text field callback
     */
    public function text_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        echo '<input type="text" name="lift_docs_settings[' . $field . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        
        if ($description) {
            echo '<p class="description">' . $description . '</p>';
        }
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Boolean fields
        $boolean_fields = array(
            'enable_analytics',
            'enable_comments',
            'enable_search',
            'enable_categories',
            'enable_tags',
            'show_document_meta',
            'show_download_button',
            'show_view_count',
            'require_login_to_view',
            'require_login_to_download'
        );
        
        foreach ($boolean_fields as $field) {
            $validated[$field] = isset($input[$field]) && $input[$field] ? true : false;
        }
        
        // Number fields
        if (isset($input['documents_per_page'])) {
            $validated['documents_per_page'] = max(1, min(100, intval($input['documents_per_page'])));
        }
        
        if (isset($input['max_file_size'])) {
            $validated['max_file_size'] = max(1, min(1024, intval($input['max_file_size'])));
        }
        
        // Text fields
        if (isset($input['allowed_file_types'])) {
            $validated['allowed_file_types'] = sanitize_text_field($input['allowed_file_types']);
        }
        
        return $validated;
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = null) {
        $settings = get_option('lift_docs_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}
