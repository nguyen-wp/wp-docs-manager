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
     * Initialize settings with tab-based organization
     */
    public function init_settings() {
        register_setting(
            'lift_docs_settings_group',
            'lift_docs_settings',
            array($this, 'validate_settings')
        );
        
        // General Tab Settings
        add_settings_section(
            'lift_docs_general_section',
            __('General Settings', 'lift-docs-system'),
            array($this, 'general_section_callback'),
            'lift-docs-general'
        );
        
        add_settings_field(
            'documents_per_page',
            __('Documents Per Page', 'lift-docs-system'),
            array($this, 'number_field_callback'),
            'lift-docs-general',
            'lift_docs_general_section',
            array('field' => 'documents_per_page', 'description' => __('Number of documents to display per page', 'lift-docs-system'), 'min' => 1, 'max' => 100)
        );
        
        add_settings_field(
            'enable_categories',
            __('Enable Categories', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-general',
            'lift_docs_general_section',
            array('field' => 'enable_categories', 'description' => __('Enable document categories', 'lift-docs-system'))
        );
        
        add_settings_field(
            'enable_tags',
            __('Enable Tags', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-general',
            'lift_docs_general_section',
            array('field' => 'enable_tags', 'description' => __('Enable document tags', 'lift-docs-system'))
        );
        
        // Security Tab Settings
        add_settings_section(
            'lift_docs_security_section',
            __('Security & Access Control', 'lift-docs-system'),
            array($this, 'security_section_callback'),
            'lift-docs-security'
        );
        
        add_settings_field(
            'require_login_to_view',
            __('Require Login to View', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-security',
            'lift_docs_security_section',
            array('field' => 'require_login_to_view', 'description' => __('Users must be logged in to view documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'require_login_to_download',
            __('Require Login to Download', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-security',
            'lift_docs_security_section',
            array('field' => 'require_login_to_download', 'description' => __('Users must be logged in to download documents', 'lift-docs-system'))
        );
        
        // Note: Encryption key removed - now using permanent hash-based tokens
        
        // Display Tab Settings  
        add_settings_section(
            'lift_docs_display_section',
            __('Display & Layout Options', 'lift-docs-system'),
            array($this, 'display_section_callback'),
            'lift-docs-display'
        );
        
        add_settings_field(
            'layout_style',
            __('Layout Style', 'lift-docs-system'),
            array($this, 'select_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array(
                'field' => 'layout_style',
                'description' => __('Choose the layout style for document pages', 'lift-docs-system'),
                'options' => array(
                    'default' => __('Default', 'lift-docs-system'),
                    'minimal' => __('Minimal', 'lift-docs-system'),
                    'detailed' => __('Detailed', 'lift-docs-system')
                )
            )
        );
        
        add_settings_field(
            'show_document_header',
            __('Show Document Header', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_document_header', 'description' => __('Display document header with title and meta info', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_document_description',
            __('Show Document Description', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_document_description', 'description' => __('Display document description/excerpt', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_document_meta',
            __('Show Document Meta', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_document_meta', 'description' => __('Display document metadata (date, author, etc.)', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_download_button',
            __('Show Download Button', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_download_button', 'description' => __('Display download button for documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_related_docs',
            __('Show Related Documents', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_related_docs', 'description' => __('Display related documents section', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_secure_access_notice',
            __('Show Secure Access Notice', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_secure_access_notice', 'description' => __('Display notice when accessing via secure link', 'lift-docs-system'))
        );
    }
    
    /**
     * Settings page callback with JavaScript-based tabs
     */
    public function settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Ensure valid tab
        $valid_tabs = array('general', 'security', 'display');
        if (!in_array($active_tab, $valid_tabs)) {
            $active_tab = 'general';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('LIFT Docs System Settings', 'lift-docs-system'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-js <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>" data-tab="general">
                   <?php _e('General', 'lift-docs-system'); ?>
                </a>
                <a href="#security" class="nav-tab nav-tab-js <?php echo $active_tab == 'security' ? 'nav-tab-active' : ''; ?>" data-tab="security">
                   <?php _e('Security', 'lift-docs-system'); ?>
                </a>
                <a href="#display" class="nav-tab nav-tab-js <?php echo $active_tab == 'display' ? 'nav-tab-active' : ''; ?>" data-tab="display">
                   <?php _e('Display', 'lift-docs-system'); ?>
                </a>
            </h2>
            
            <form method="post" action="options.php">
                <?php settings_fields('lift_docs_settings_group'); ?>
                
                <!-- General Tab Content -->
                <div id="general-tab" class="tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
                    <?php do_settings_sections('lift-docs-general'); ?>
                </div>
                
                <!-- Security Tab Content -->
                <div id="security-tab" class="tab-content <?php echo $active_tab == 'security' ? 'active' : ''; ?>">
                    <?php do_settings_sections('lift-docs-security'); ?>
                </div>
                
                <!-- Display Tab Content -->
                <div id="display-tab" class="tab-content <?php echo $active_tab == 'display' ? 'active' : ''; ?>">
                    <?php do_settings_sections('lift-docs-display'); ?>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
            .nav-tab-wrapper {
                margin-bottom: 20px;
                border-bottom: 1px solid #ccd0d4;
            }
            .nav-tab {
                text-decoration: none;
                border: 1px solid #ccd0d4;
                border-bottom: none;
                background: #f1f1f1;
                color: #555;
                padding: 10px 15px;
                margin-right: 5px;
                border-radius: 3px 3px 0 0;
                transition: all 0.3s ease;
                display: inline-block;
                cursor: pointer;
            }
            .nav-tab:hover {
                background: #e8e8e8;
                color: #333;
                text-decoration: none;
            }
            .nav-tab:focus {
                outline: none;
                box-shadow: 0 0 0 1px #5b9dd9, 0 0 2px 1px rgba(30, 140, 190, 0.8);
            }
            .nav-tab-active {
                background: #fff;
                border-bottom: 1px solid #fff;
                color: #000;
                position: relative;
                top: 1px;
                font-weight: 600;
            }
            .nav-tab-active:hover {
                background: #fff;
                color: #000;
            }
            
            /* Tab Content Styling */
            .tab-content {
                display: none !important;
                opacity: 0;
                transform: translateY(10px);
                transition: opacity 0.3s ease, transform 0.3s ease;
            }
            .tab-content.active {
                display: block !important;
                opacity: 1;
                transform: translateY(0);
            }
            
            .form-table {
                margin-top: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 3px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .form-table th {
                width: 220px;
                font-weight: 600;
                background: #f9f9f9;
                border-right: 1px solid #ccd0d4;
                vertical-align: top;
                padding: 20px;
            }
            .form-table td {
                padding: 20px;
                vertical-align: top;
            }
            .form-table tr {
                border-bottom: 1px solid #eee;
            }
            .form-table tr:last-child {
                border-bottom: none;
            }
            .description {
                color: #666;
                font-style: italic;
                margin-top: 5px;
                font-size: 13px;
                line-height: 1.4;
            }
            
            /* Better styling for form fields */
            .regular-text, .small-text {
                border: 1px solid #ddd;
                border-radius: 3px;
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .regular-text:focus, .small-text:focus {
                border-color: #5b9dd9;
                box-shadow: 0 0 2px rgba(30, 140, 190, 0.8);
                outline: none;
            }
            
            /* Section headers */
            h2 {
                color: #333;
                font-size: 18px;
                margin-bottom: 10px;
            }
            
            /* Button styling */
            .button {
                border-radius: 3px;
                font-weight: 500;
            }
            
            /* Loading state */
            .nav-tab.loading {
                opacity: 0.6;
                pointer-events: none;
            }
            
            /* Smooth fade transition for active content */
            .tab-content.active {
                animation: fadeInSmooth 0.3s ease-out;
            }
            
            @keyframes fadeInSmooth {
                from { 
                    opacity: 0; 
                    transform: translateY(10px); 
                }
                to { 
                    opacity: 1; 
                    transform: translateY(0); 
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('LIFT Docs Settings: JavaScript tab functionality loaded');
            
            // Get current tab from URL or default to general
            var urlParams = new URLSearchParams(window.location.search);
            var currentTab = urlParams.get('tab') || 'general';
            
            console.log('Current tab from URL:', currentTab);
            
            // Function to switch to a specific tab
            function switchToTab(tabName) {
                console.log('Switching to tab:', tabName);
                
                // Remove active class from all tabs and content
                $('.nav-tab').removeClass('nav-tab-active');
                $('.tab-content').removeClass('active').hide();
                
                // Add active class to target tab
                $('.nav-tab-js[data-tab="' + tabName + '"]').addClass('nav-tab-active');
                
                // Show target content
                var $targetContent = $('#' + tabName + '-tab');
                $targetContent.show().addClass('active');
            }
            
            // Handle tab switching
            $('.nav-tab-js').on('click', function(e) {
                e.preventDefault();
                
                var $clickedTab = $(this);
                var targetTab = $clickedTab.data('tab');
                
                switchToTab(targetTab);
                
                // Update URL without page reload
                var newUrl = window.location.origin + window.location.pathname + '?page=lift-docs-settings&tab=' + targetTab;
                window.history.pushState({tab: targetTab}, '', newUrl);
            });
            
            // Handle browser back/forward
            $(window).on('popstate', function(event) {
                if (event.originalEvent.state && event.originalEvent.state.tab) {
                    switchToTab(event.originalEvent.state.tab);
                } else {
                    // Fallback to URL parameter
                    var urlParams = new URLSearchParams(window.location.search);
                    var tab = urlParams.get('tab') || 'general';
                    switchToTab(tab);
                }
            });
            
            // Initialize - hide all tabs first, then show the correct one
            $('.tab-content').hide().removeClass('active');
            $('.nav-tab').removeClass('nav-tab-active');
            
            // Switch to the current tab (from URL or default)
            switchToTab(currentTab);
            
            console.log('LIFT Docs Settings: Tab initialization complete for tab:', currentTab);
        });
        </script>
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
        echo '<p>' . __('Configure security and access control settings. <strong>Secure links are enabled by default for enhanced security.</strong>', 'lift-docs-system') . '</p>';
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
     * Select field callback
     */
    public function select_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $options = $args['options'] ?? array();
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        echo '<select name="lift_docs_settings[' . $field . ']" class="regular-text">';
        foreach ($options as $option_value => $option_label) {
            $selected = selected($value, $option_value, false);
            echo '<option value="' . esc_attr($option_value) . '"' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        
        if (!empty($description)) {
            echo '<p class="description">' . $description . '</p>';
        }
    }
    
    /**
     * Encryption key field callback
     */
    /**
     * Note: Encryption key field removed - now using permanent hash-based tokens
     */
    
    /**
     * Validate settings
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Boolean fields - only keep essential ones
        $boolean_fields = array(
            'enable_categories',
            'enable_tags',
            'require_login_to_view',
            'require_login_to_download',
            'show_document_header',
            'show_document_description',
            'show_document_meta',
            'show_download_button',
            'show_related_docs',
            'show_secure_access_notice'
        );
        
        foreach ($boolean_fields as $field) {
            $validated[$field] = isset($input[$field]) && $input[$field] ? true : false;
        }
        
        // Number fields
        if (isset($input['documents_per_page'])) {
            $validated['documents_per_page'] = max(1, min(100, intval($input['documents_per_page'])));
        }
        
        // Text fields
        if (isset($input['encryption_key'])) {
            $validated['encryption_key'] = sanitize_text_field($input['encryption_key']);
        }
        
        // Layout style select field
        if (isset($input['layout_style'])) {
            $allowed_styles = array('default', 'minimal', 'detailed');
            $layout_style = sanitize_text_field($input['layout_style']);
            $validated['layout_style'] = in_array($layout_style, $allowed_styles) ? $layout_style : 'default';
        }
        
        // Force secure links to always be enabled
        $validated['enable_secure_links'] = true;
        
        // Set default secure link expiry to 24 hours
        $validated['secure_link_expiry'] = 24;
        
        return $validated;
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = null) {
        // Force secure links to always be enabled
        if ($key === 'enable_secure_links') {
            return true;
        }
        
        // Set default secure link expiry to 24 hours
        if ($key === 'secure_link_expiry') {
            return 24;
        }
        
        $settings = get_option('lift_docs_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Generate encryption key
     */
    private function generate_encryption_key() {
        return wp_generate_password(32, false);
    }
    
    /**
     * Generate permanent secure view URL for document
     * Uses document ID + salt + creation time to create unique, permanent URL
     */
    public static function generate_secure_link($document_id, $expiry_hours = null) {
        // Get or create permanent token for this document
        $permanent_token = get_post_meta($document_id, '_lift_doc_permanent_token', true);
        
        if (empty($permanent_token)) {
            // Generate a unique, permanent token based on document data
            $post = get_post($document_id);
            if (!$post) {
                return get_permalink($document_id);
            }
            
            // Create a unique hash using document ID, creation time, and WordPress salt
            $data_to_hash = $document_id . '|' . $post->post_date . '|' . wp_salt('secure_auth');
            $permanent_token = hash('sha256', $data_to_hash);
            
            // Store the permanent token
            update_post_meta($document_id, '_lift_doc_permanent_token', $permanent_token);
        }
        
        return home_url('/lift-docs/secure/?lift_secure=' . $permanent_token);
    }
    
    /**
     * Generate secure download link for document
     */
    /**
     * Generate permanent secure download URL for document file
     */
    public static function generate_secure_download_link($document_id, $expiry_hours = 0, $file_index = 0) {
        // Get or create permanent token for this document
        $permanent_token = get_post_meta($document_id, '_lift_doc_permanent_token', true);
        
        if (empty($permanent_token)) {
            // Use the secure link generation to create permanent token
            self::generate_secure_link($document_id);
            $permanent_token = get_post_meta($document_id, '_lift_doc_permanent_token', true);
        }
        
        // For download links, append file index if multiple files
        $download_token = $permanent_token;
        if ($file_index > 0) {
            $download_token .= '_file_' . $file_index;
        }
        
        return home_url('/lift-docs/download/?lift_secure=' . $download_token);
    }
    
    /**
     * Verify secure link token (permanent tokens)
     */
    public static function verify_secure_link($token) {
        // Parse file index from token if present
        $file_index = 0;
        $base_token = $token;
        
        if (strpos($token, '_file_') !== false) {
            $parts = explode('_file_', $token);
            $base_token = $parts[0];
            $file_index = isset($parts[1]) ? intval($parts[1]) : 0;
        }
        
        // Find document with this permanent token
        global $wpdb;
        $document_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_lift_doc_permanent_token' 
             AND meta_value = %s 
             LIMIT 1",
            $base_token
        ));
        
        if (!$document_id) {
            return false;
        }
        
        // Verify document still exists and is valid
        $post = get_post($document_id);
        if (!$post || $post->post_type !== 'lift_document') {
            return false;
        }
        
        return array(
            'document_id' => $document_id,
            'file_index' => $file_index,
            'expires' => 0, // Never expires
            'timestamp' => time(),
            'type' => strpos($token, '_file_') !== false ? 'download' : 'view'
        );
    }
    
    /**
     * Encrypt data
     */
    private static function encrypt_data($data, $key) {
        $json = json_encode($data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LIFT Docs Debug - Encrypting data: ' . $json);
        }
        
        // Ensure key is exactly 32 bytes for AES-256-CBC
        $key = substr(hash('sha256', $key, true), 0, 32);
        
        // Use openssl_random_pseudo_bytes with fallback to random_bytes
        if (function_exists('random_bytes')) {
            $iv = random_bytes(16);
        } else {
            $iv = openssl_random_pseudo_bytes(16);
        }
        
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
        
        if ($encrypted === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LIFT Docs Debug - Encryption failed');
            }
            return false;
        }
        
        $result = base64_encode($iv . $encrypted);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LIFT Docs Debug - Encrypted token: ' . $result);
        }
        
        return $result;
    }
    
    /**
     * Decrypt data
     */
    private static function decrypt_data($token, $key) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LIFT Docs Debug - Decrypting token: ' . $token);
        }
        
        $data = base64_decode($token);
        if ($data === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LIFT Docs Debug - Base64 decode failed');
            }
            return false;
        }
        
        if (strlen($data) < 16) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LIFT Docs Debug - Data too short, length: ' . strlen($data));
            }
            return false;
        }
        
        // Ensure key is exactly 32 bytes for AES-256-CBC
        $key = substr(hash('sha256', $key, true), 0, 32);
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        
        if ($decrypted === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LIFT Docs Debug - OpenSSL decrypt failed');
            }
            return false;
        }
        
        $result = json_decode($decrypted, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LIFT Docs Debug - Decrypted data: ' . print_r($result, true));
        }
        
        return $result;
    }
    
    /**
     * Get encryption key (public access)
     */
    public static function get_encryption_key() {
        return self::get_setting('encryption_key', '');
    }
    
    /**
     * Get encryption key (internal)
     */
    private static function get_encryption_key_internal() {
        $key = self::get_setting('encryption_key', '');
        if (empty($key)) {
            // Auto-generate and save key if missing - ensure 32 bytes for AES-256
            if (function_exists('random_bytes')) {
                $key = base64_encode(random_bytes(32));
            } else {
                $key = wp_generate_password(32, false);
            }
            
            $settings = get_option('lift_docs_settings', array());
            $settings['encryption_key'] = $key;
            update_option('lift_docs_settings', $settings);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LIFT Docs Debug - Generated new encryption key in get_encryption_key: ' . $key);
            }
        }
        return $key;
    }
    
    /**
     * Generate secure token for actions
     */
    public static function generate_secure_token($doc_id, $action = 'view') {
        $key = self::get_encryption_key_internal();
        
        $data = array(
            'doc_id' => $doc_id,
            'action' => $action,
            'timestamp' => time(),
            'nonce' => wp_create_nonce('lift_secure_' . $action . '_' . $doc_id)
        );
        
        return self::encrypt_data($data, $key);
    }
    
    /**
     * Verify secure token
     */
    public static function verify_secure_token($token, $expected_action = null) {
        $key = self::get_encryption_key_internal();
        $data = self::decrypt_data($token, $key);
        
        if (!$data || !isset($data['doc_id'], $data['action'], $data['timestamp'], $data['nonce'])) {
            return false;
        }
        
        // Check if action matches
        if ($expected_action && $data['action'] !== $expected_action) {
            return false;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($data['nonce'], 'lift_secure_' . $data['action'] . '_' . $data['doc_id'])) {
            return false;
        }
        
        // Check if document exists
        $document = get_post($data['doc_id']);
        if (!$document || $document->post_type !== 'lift_document') {
            return false;
        }
        
        return $data['doc_id'];
    }
}
