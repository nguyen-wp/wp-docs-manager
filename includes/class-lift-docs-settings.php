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
        
        add_settings_field(
            'enable_secure_links',
            __('Enable Secure Links', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'enable_secure_links', 'description' => __('Documents can only be accessed via encrypted secure links', 'lift-docs-system'))
        );
        
        add_settings_field(
            'hide_from_sitemap',
            __('Hide from Sitemap', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'hide_from_sitemap', 'description' => __('Exclude documents from XML sitemaps and search engines', 'lift-docs-system'))
        );
        
        add_settings_field(
            'secure_link_expiry',
            __('Secure Link Expiry (hours)', 'lift-docs-system'),
            array($this, 'number_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'secure_link_expiry', 'description' => __('How long secure links remain valid (0 = never expire)', 'lift-docs-system'), 'min' => 0, 'max' => 8760)
        );
        
        add_settings_field(
            'encryption_key',
            __('Encryption Key', 'lift-docs-system'),
            array($this, 'encryption_key_field_callback'),
            'lift-docs-settings',
            'lift_docs_security_section',
            array('field' => 'encryption_key', 'description' => __('Key used for encrypting secure links (auto-generated)', 'lift-docs-system'))
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
     * Encryption key field callback
     */
    public function encryption_key_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        // Auto-generate key if empty
        if (empty($value)) {
            $value = $this->generate_encryption_key();
            $settings[$field] = $value;
            update_option('lift_docs_settings', $settings);
        }
        
        echo '<input type="text" name="lift_docs_settings[' . $field . ']" value="' . esc_attr($value) . '" class="regular-text" readonly />';
        echo '<button type="button" class="button" onclick="generateNewKey()">' . __('Generate New Key', 'lift-docs-system') . '</button>';
        
        if ($description) {
            echo '<p class="description">' . $description . '</p>';
        }
        
        echo '<p class="description" style="color: red;"><strong>' . __('Warning: Changing this key will invalidate all existing secure links!', 'lift-docs-system') . '</strong></p>';
        
        ?>
        <script>
        function generateNewKey() {
            if (confirm('<?php _e("This will invalidate all existing secure links. Continue?", "lift-docs-system"); ?>')) {
                var newKey = generateRandomKey(32);
                document.querySelector('input[name="lift_docs_settings[encryption_key]"]').value = newKey;
            }
        }
        
        function generateRandomKey(length) {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var result = '';
            for (var i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
        </script>
        <?php
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
            'require_login_to_download',
            'enable_secure_links',
            'hide_from_sitemap'
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
        
        if (isset($input['secure_link_expiry'])) {
            $validated['secure_link_expiry'] = max(0, min(8760, intval($input['secure_link_expiry'])));
        }
        
        // Text fields
        if (isset($input['allowed_file_types'])) {
            $validated['allowed_file_types'] = sanitize_text_field($input['allowed_file_types']);
        }
        
        if (isset($input['encryption_key'])) {
            $validated['encryption_key'] = sanitize_text_field($input['encryption_key']);
        }
        
        // Check if secure links setting changed - flush rewrite rules if so
        $current_settings = get_option('lift_docs_settings', array());
        $current_secure_links = isset($current_settings['enable_secure_links']) ? $current_settings['enable_secure_links'] : false;
        $new_secure_links = isset($validated['enable_secure_links']) ? $validated['enable_secure_links'] : false;
        
        if ($current_secure_links !== $new_secure_links) {
            // Schedule rewrite rules flush
            add_action('shutdown', 'flush_rewrite_rules');
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
    
    /**
     * Generate encryption key
     */
    private function generate_encryption_key() {
        return wp_generate_password(32, false);
    }
    
    /**
     * Generate secure link for document
     */
    public static function generate_secure_link($document_id, $expiry_hours = null) {
        if (!self::get_setting('enable_secure_links', false)) {
            return get_permalink($document_id);
        }
        
        $encryption_key = self::get_setting('encryption_key', '');
        if (empty($encryption_key)) {
            return get_permalink($document_id);
        }
        
        $expiry_hours = $expiry_hours ?? self::get_setting('secure_link_expiry', 24);
        $expires = $expiry_hours > 0 ? time() + ($expiry_hours * 3600) : 0;
        
        $data = array(
            'document_id' => $document_id,
            'expires' => $expires,
            'timestamp' => time()
        );
        
        $token = self::encrypt_data($data, $encryption_key);
        
        return add_query_arg(array(
            'lift_secure' => urlencode($token)
        ), home_url('/lift-docs/secure/'));
    }
    
    /**
     * Generate secure download link for document
     */
    public static function generate_secure_download_link($document_id, $expiry_hours = 1) {
        if (!self::get_setting('enable_secure_links', false)) {
            return get_post_meta($document_id, '_lift_doc_file_url', true);
        }
        
        $encryption_key = self::get_setting('encryption_key', '');
        if (empty($encryption_key)) {
            return get_post_meta($document_id, '_lift_doc_file_url', true);
        }
        
        $expires = $expiry_hours > 0 ? time() + ($expiry_hours * 3600) : 0;
        
        $data = array(
            'document_id' => $document_id,
            'expires' => $expires,
            'timestamp' => time(),
            'type' => 'download'
        );
        
        $token = self::encrypt_data($data, $encryption_key);
        
        return add_query_arg(array(
            'lift_secure' => urlencode($token)
        ), home_url('/lift-docs/download/'));
    }
    
    /**
     * Verify secure link
     */
    public static function verify_secure_link($token) {
        if (!self::get_setting('enable_secure_links', false)) {
            return false;
        }
        
        $encryption_key = self::get_setting('encryption_key', '');
        if (empty($encryption_key)) {
            return false;
        }
        
        $data = self::decrypt_data($token, $encryption_key);
        
        if (!$data || !isset($data['document_id'])) {
            return false;
        }
        
        // Check expiry
        if ($data['expires'] > 0 && time() > $data['expires']) {
            return false;
        }
        
        return $data['document_id'];
    }
    
    /**
     * Encrypt data
     */
    private static function encrypt_data($data, $key) {
        $json = json_encode($data);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    private static function decrypt_data($token, $key) {
        $data = base64_decode($token);
        if ($data === false) {
            return false;
        }
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        
        if ($decrypted === false) {
            return false;
        }
        
        return json_decode($decrypted, true);
    }
}
