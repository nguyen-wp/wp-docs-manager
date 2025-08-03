<?php
/**
 * LIFT Forms Main Class
 *
 * Handles form creation, management and rendering
 */

if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Forms {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));

        // Only add admin scripts when in admin
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // Add body class for document forms to apply dashboard-style
        add_filter('body_class', array($this, 'add_form_body_class'));

        // AJAX handlers
        add_action('wp_ajax_lift_forms_save', array($this, 'ajax_save_form'));
        add_action('wp_ajax_lift_forms_get', array($this, 'ajax_get_form'));
        add_action('wp_ajax_lift_forms_delete', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_lift_forms_submit', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_lift_forms_submit', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_lift_forms_get_submission', array($this, 'ajax_get_submission'));
        add_action('wp_ajax_lift_forms_update_status', array($this, 'ajax_update_form_status'));
        add_action('wp_ajax_lift_forms_update_submission_status', array($this, 'ajax_update_submission_status'));

        // Import/Export AJAX handlers
        add_action('wp_ajax_lift_forms_import', array($this, 'ajax_import_form'));
        add_action('wp_ajax_lift_forms_export', array($this, 'ajax_export_form'));
        add_action('wp_ajax_lift_forms_export_all', array($this, 'ajax_export_all_forms'));
        add_action('wp_ajax_lift_forms_get_templates', array($this, 'ajax_get_templates'));
        add_action('wp_ajax_lift_forms_load_template', array($this, 'ajax_load_template'));

        // BPMN.io form builder AJAX actions
        add_action('wp_ajax_lift_form_builder_save', array($this, 'ajax_save_form_schema'));
        add_action('wp_ajax_lift_form_builder_load', array($this, 'ajax_load_form_schema'));
        add_action('wp_ajax_lift_form_builder_preview', array($this, 'ajax_preview_form'));

        // File upload and signature AJAX handlers
        add_action('wp_ajax_lift_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_nopriv_lift_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_lift_save_signature', array($this, 'ajax_save_signature'));
        add_action('wp_ajax_nopriv_lift_save_signature', array($this, 'ajax_save_signature'));

        // Register shortcode
        add_shortcode('lift_form', array($this, 'render_form_shortcode'));
    }

    /**
     * Initialize
     */
    public function init() {
        $this->create_tables();
        $this->register_post_type();
        $this->setup_capabilities();
        $this->create_upload_directories();

        // Force check for missing columns on every init
        $this->maybe_add_user_id_column();
    }

    /**
     * Setup capabilities for LIFT Forms
     */
    private function setup_capabilities() {
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('edit_lift_documents');
            $admin_role->add_cap('edit_others_lift_documents');
            $admin_role->add_cap('publish_lift_documents');
            $admin_role->add_cap('read_private_lift_documents');
            $admin_role->add_cap('delete_lift_documents');
        }

        // Add capabilities to editor role if exists
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('edit_lift_documents');
            $editor_role->add_cap('edit_others_lift_documents');
            $editor_role->add_cap('publish_lift_documents');
            $editor_role->add_cap('read_private_lift_documents');
        }
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Forms table
        $forms_table = $wpdb->prefix . 'lift_forms';
        $sql_forms = "CREATE TABLE $forms_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            form_fields longtext,
            settings longtext,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Form submissions table
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        $sql_submissions = "CREATE TABLE $submissions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            form_data longtext,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            user_ip varchar(45),
            user_agent text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'unread',
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY user_id (user_id),
            KEY submitted_at (submitted_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Log table creation
        $forms_result = dbDelta($sql_forms);
        $submissions_result = dbDelta($sql_submissions);

        // Verify tables were created
        $forms_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'");
        $submissions_exists = $wpdb->get_var("SHOW TABLES LIKE '$submissions_table'");

        // Add user_id column if it doesn't exist (for existing installations)
        $this->maybe_add_user_id_column();
    }

    /**
     * Add user_id column if it doesn't exist
     */
    private function maybe_add_user_id_column() {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';

        // Check if user_id column exists - Using proper wpdb::prepare() with SQL query and placeholder
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$submissions_table} LIKE %s",
            'user_id'
        ));

        if (empty($column_exists)) {
            // Add user_id column
            $wpdb->query("ALTER TABLE {$submissions_table} ADD COLUMN user_id bigint(20) UNSIGNED DEFAULT NULL AFTER form_data");
            $wpdb->query("ALTER TABLE {$submissions_table} ADD INDEX user_id (user_id)");
        }

        // Check if updated_at column exists - Using proper wpdb::prepare() with SQL query and placeholder
        $updated_at_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$submissions_table} LIKE %s",
            'updated_at'
        ));

        if (empty($updated_at_exists)) {
            // Add updated_at column
            $wpdb->query("ALTER TABLE {$submissions_table} ADD COLUMN updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER submitted_at");
        }
    }

    /**
     * Register custom post type for form entries
     */
    private function register_post_type() {
        register_post_type('lift_form_entry', array(
            'labels' => array(
                'name' => __('Form Entries', 'lift-docs-system'),
                'singular_name' => __('Form Entry', 'lift-docs-system'),
            ),
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'custom-fields'),
        ));
    }

    /**
     * Enqueue admin scripts - Minimal version
     */
    public function enqueue_admin_scripts($hook) {
        // Check if we're on any LIFT Forms admin page
        $is_lift_forms_page = false;

        if (strpos($hook, 'lift-forms') !== false) {
            $is_lift_forms_page = true;
        }

        if (isset($_GET['page']) && strpos($_GET['page'], 'lift-forms') !== false) {
            $is_lift_forms_page = true;
        }

        // Also check for LIFT document admin pages that contain forms
        if (strpos($hook, 'lift_document') !== false) {
            $is_lift_forms_page = true;
        }

        if (!$is_lift_forms_page) {
            return;
        }

        // Only enqueue jQuery for basic functionality
        wp_enqueue_script('jquery');

        // Enqueue Dashicons for admin interface
        wp_enqueue_style('dashicons');

        // Enqueue WordPress editor for form builder
        if (isset($_GET['page']) && $_GET['page'] === 'lift-forms-builder') {
            wp_enqueue_editor();
        }

        // Minimal admin JavaScript
        wp_enqueue_script(
            'lift-forms-minimal-admin',
            plugin_dir_url(__FILE__) . '../assets/js/minimal-admin.js',
            array('jquery'),
            '3.0.0',
            true
        );

        // Admin styles - include both main admin and forms admin CSS
        wp_enqueue_style(
            'lift-forms-admin',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(),
            '3.0.0'
        );

        wp_enqueue_style(
            'lift-forms-forms-admin',
            plugin_dir_url(__FILE__) . '../assets/css/forms-admin.css',
            array('lift-forms-admin'),
            '3.0.0'
        );

        // Import/Export styles
        wp_enqueue_style(
            'lift-forms-import-export',
            plugin_dir_url(__FILE__) . '../assets/css/forms-import-export.css',
            array('lift-forms-forms-admin'),
            '3.0.0'
        );

        wp_enqueue_style(
            'lift-forms-admin-modal',
            plugin_dir_url(__FILE__) . '../assets/css/admin-modal.css',
            array('lift-forms-forms-admin'),
            '3.0.0'
        );

        // Minimal admin styles for form builder header
        wp_enqueue_style(
            'lift-forms-minimal-admin',
            plugin_dir_url(__FILE__) . '../assets/css/minimal-admin.css',
            array('lift-forms-admin-modal'),
            '3.0.0'
        );

        // BPMN.io form builder styles
        wp_enqueue_style(
            'lift-forms-form-builder-bpmn',
            plugin_dir_url(__FILE__) . '../assets/css/form-builder-bpmn.css',
            array('lift-forms-minimal-admin'),
            '3.0.0'
        );

        // Import/Export styles for template loader
        wp_enqueue_style(
            'lift-forms-import-export',
            plugin_dir_url(__FILE__) . '../assets/css/forms-import-export.css',
            array('lift-forms-form-builder-bpmn'),
            '3.0.0'
        );

        // Simple form builder JavaScript (no external dependencies)
        wp_enqueue_script(
            'lift-forms-form-builder-bpmn',
            plugin_dir_url(__FILE__) . '../assets/js/form-builder-bpmn.js',
            array('jquery'),
            '3.0.0',
            true
        );

        // Minimal localization for AJAX
        wp_localize_script('lift-forms-minimal-admin', 'liftForms', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lift_forms_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this form?', 'lift-docs-system'),
                'saving' => __('Saving...', 'lift-docs-system'),
                'saved' => __('Form saved successfully!', 'lift-docs-system'),
                'error' => __('An error occurred. Please try again.', 'lift-docs-system'),
            )
        ));

        // BPMN.io form builder localization
        wp_localize_script('lift-forms-form-builder-bpmn', 'liftFormBuilder', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lift_form_builder_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__),
            'strings' => array(
                'loading' => __('Loading form builder...', 'lift-docs-system'),
                'error' => __('Error loading form builder', 'lift-docs-system'),
                'saved' => __('Form saved successfully!', 'lift-docs-system'),
                'preview' => __('Form Preview', 'lift-docs-system'),
                'close' => __('Close', 'lift-docs-system'),
                'saving' => __('Saving...', 'lift-docs-system'),
                'confirmDelete' => __('Are you sure you want to delete this field?', 'lift-docs-system'),
            )
        ));
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'lift-forms-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/forms-frontend.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // File upload and signature functionality
        wp_enqueue_script(
            'lift-file-upload-signature',
            plugin_dir_url(__FILE__) . '../assets/js/file-upload-signature.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'lift-forms-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/forms-frontend.css',
            array(),
            '1.0.0'
        );

        // Enqueue secure frontend styles for document forms to match dashboard style
        wp_enqueue_style(
            'lift-docs-secure-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/secure-frontend.css',
            array(),
            '1.0.0'
        );

        wp_localize_script('lift-forms-frontend', 'liftFormsFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lift_forms_submit_nonce'),
            'strings' => array(
                'submitting' => __('Submitting...', 'lift-docs-system'),
                'submitted' => __('Form submitted successfully!', 'lift-docs-system'),
                'error' => __('An error occurred. Please try again.', 'lift-docs-system'),
                'requiredField' => __('This field is required.', 'lift-docs-system'),
                'invalidEmail' => __('Please enter a valid email address.', 'lift-docs-system'),
            )
        ));

        // Localize for file upload and signature script
        wp_localize_script('lift-file-upload-signature', 'liftFormsFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lift_forms_submit_nonce'),
            'uploadDir' => wp_upload_dir()['url'],
            'maxFileSize' => 5 * 1024 * 1024, // 5MB
            'allowedTypes' => array('image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'strings' => array(
                'uploadError' => __('Error uploading file', 'lift-docs-system'),
                'signatureError' => __('Error saving signature', 'lift-docs-system'),
                'fileTooLarge' => __('File is too large. Maximum size is 5MB.', 'lift-docs-system'),
                'invalidFileType' => __('Invalid file type. Please select a supported format.', 'lift-docs-system'),
                'signatureSaved' => __('Signature saved successfully!', 'lift-docs-system'),
                'fileUploaded' => __('File uploaded successfully!', 'lift-docs-system'),
            )
        ));
    }

    /**
     * Add body class for form pages to apply dashboard-style
     */
    public function add_form_body_class($classes) {
        global $post;

        // Check if current page/post contains a lift_form shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'lift_form')) {
            $classes[] = 'document-form-page';
        }

        return $classes;
    }

    /**
     * Admin page - Forms list
     */
    public function admin_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';

        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] !== '-1' && isset($_POST['forms']) && is_array($_POST['forms'])) {
            if (wp_verify_nonce($_POST['_wpnonce'], 'bulk_action_forms')) {
                $form_ids = array_map('intval', $_POST['forms']);
                $bulk_action = sanitize_text_field($_POST['action']);
                $affected_count = 0;

                switch ($bulk_action) {
                    case 'bulk_delete':
                        foreach ($form_ids as $form_id) {
                            $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d form deleted successfully.', '%d forms deleted successfully.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;

                    case 'bulk_activate':
                        foreach ($form_ids as $form_id) {
                            $wpdb->update($forms_table, array('status' => 'active'), array('id' => $form_id), array('%s'), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d form activated successfully.', '%d forms activated successfully.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;

                    case 'bulk_deactivate':
                        foreach ($form_ids as $form_id) {
                            $wpdb->update($forms_table, array('status' => 'inactive'), array('id' => $form_id), array('%s'), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d form deactivated successfully.', '%d forms deactivated successfully.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;

                    case 'bulk_draft':
                        foreach ($form_ids as $form_id) {
                            $wpdb->update($forms_table, array('status' => 'draft'), array('id' => $form_id), array('%s'), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d form moved to draft successfully.', '%d forms moved to draft successfully.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;
                }
            }
        }

        // Handle status update
        if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['form_id'])) {
            if (wp_verify_nonce($_POST['_wpnonce'], 'update_form_status')) {
                $form_id = intval($_POST['form_id']);
                $new_status = sanitize_text_field($_POST['status']);

                // Validate status
                $valid_statuses = array('active', 'inactive', 'draft');
                if (in_array($new_status, $valid_statuses)) {
                    $wpdb->update(
                        $forms_table,
                        array('status' => $new_status),
                        array('id' => $form_id),
                        array('%s'),
                        array('%d')
                    );
                    echo '<div class="notice notice-success"><p>' . __('Form status updated successfully.', 'lift-docs-system') . '</p></div>';
                }
            }
        }

        // Handle form deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $form_id = intval($_GET['id']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_form_' . $form_id)) {
                $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));
                echo '<div class="notice notice-success"><p>' . __('Form deleted successfully.', 'lift-docs-system') . '</p></div>';
            }
        }

        // Get search and filter parameters
        $search_name = isset($_GET['search_name']) ? sanitize_text_field($_GET['search_name']) : '';
        $search_description = isset($_GET['search_description']) ? sanitize_text_field($_GET['search_description']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        // Build query with filters
        $where_conditions = array();
        $params = array();

        if (!empty($search_name)) {
            $where_conditions[] = "name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search_name) . '%';
        }

        if (!empty($search_description)) {
            $where_conditions[] = "description LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search_description) . '%';
        }

        if (!empty($status_filter)) {
            $where_conditions[] = "status = %s";
            $params[] = $status_filter;
        }

        if (!empty($date_from)) {
            $where_conditions[] = "DATE(created_at) >= %s";
            $params[] = $date_from;
        }

        if (!empty($date_to)) {
            $where_conditions[] = "DATE(created_at) <= %s";
            $params[] = $date_to;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        if (!empty($params)) {
            $query = $wpdb->prepare("SELECT * FROM $forms_table $where_clause ORDER BY created_at DESC", ...$params);
        } else {
            $query = "SELECT * FROM $forms_table $where_clause ORDER BY created_at DESC";
        }

        $forms = $wpdb->get_results($query);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('LIFT Forms', 'lift-docs-system'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=lift-forms-builder'); ?>" class="page-title-action">
                <?php _e('Add New Form', 'lift-docs-system'); ?>
            </a>
            
            <!-- Import/Export Section -->
            <div class="lift-forms-import-export">
                <button type="button" class="page-title-action lift-import-btn" id="lift-import-form-btn">
                    <?php _e('Import Form', 'lift-docs-system'); ?>
                </button>
                <button type="button" class="page-title-action lift-export-all-btn" id="lift-export-all-forms-btn">
                    <?php _e('Export All Forms', 'lift-docs-system'); ?>
                </button>
            </div>

            <!-- Search and Filter Form -->
            <div class="lift-forms-search-form" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
                <h3 style="margin-top: 0;"><?php _e('Search & Filter Forms', 'lift-docs-system'); ?></h3>
                <form method="get" action="">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if (!in_array($key, ['search_name', 'search_description', 'status_filter', 'date_from', 'date_to'])): ?>
                            <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="search_name" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Form Name', 'lift-docs-system'); ?>
                            </label>
                            <input type="text" 
                                   id="search_name" 
                                   name="search_name" 
                                   value="<?php echo esc_attr($search_name); ?>" 
                                   placeholder="<?php _e('Search by form name', 'lift-docs-system'); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        </div>
                        
                        <div>
                            <label for="search_description" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Description', 'lift-docs-system'); ?>
                            </label>
                            <input type="text" 
                                   id="search_description" 
                                   name="search_description" 
                                   value="<?php echo esc_attr($search_description); ?>" 
                                   placeholder="<?php _e('Search by description', 'lift-docs-system'); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        </div>
                        
                        <div>
                            <label for="status_filter" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Status', 'lift-docs-system'); ?>
                            </label>
                            <select id="status_filter" 
                                    name="status_filter" 
                                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value=""><?php _e('All Statuses', 'lift-docs-system'); ?></option>
                                <option value="active" <?php selected($status_filter, 'active'); ?>>
                                    <?php _e('Active', 'lift-docs-system'); ?>
                                </option>
                                <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>
                                    <?php _e('Inactive', 'lift-docs-system'); ?>
                                </option>
                                <option value="draft" <?php selected($status_filter, 'draft'); ?>>
                                    <?php _e('Draft', 'lift-docs-system'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_from" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Date From', 'lift-docs-system'); ?>
                            </label>
                            <input type="date" 
                                   id="date_from" 
                                   name="date_from" 
                                   value="<?php echo esc_attr($date_from); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        </div>
                        
                        <div>
                            <label for="date_to" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Date To', 'lift-docs-system'); ?>
                            </label>
                            <input type="date" 
                                   id="date_to" 
                                   name="date_to" 
                                   value="<?php echo esc_attr($date_to); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;" class="search-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
                            <?php _e('Search Forms', 'lift-docs-system'); ?>
                        </button>
                        
                        <?php if (!empty($search_name) || !empty($search_description) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <a href="<?php echo admin_url('admin.php?page=lift-forms'); ?>" class="button">
                            <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
                            <?php _e('Clear Filters', 'lift-docs-system'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <div style="margin-left: auto; color: #666;">
                            <?php printf(__('Found %d forms', 'lift-docs-system'), count($forms)); ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="lift-forms-overview">
                <div class="lift-forms-stats">
                    <div class="stat-box">
                        <h3><?php echo count($forms); ?></h3>
                        <p><?php _e('Found Forms', 'lift-docs-system'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo $this->get_total_submissions(); ?></h3>
                        <p><?php _e('Total Submissions', 'lift-docs-system'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo $this->get_unread_submissions(); ?></h3>
                        <p><?php _e('Unread Submissions', 'lift-docs-system'); ?></p>
                    </div>
                </div>

                <?php if (empty($forms)): ?>
                    <div class="lift-forms-empty">
                        <h2><?php _e('No Forms Found', 'lift-docs-system'); ?></h2>
                        <p><?php _e('No forms match your search criteria. Try adjusting your filters or create a new form.', 'lift-docs-system'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=lift-forms-builder'); ?>" class="button button-primary button-large">
                            <?php _e('Create New Form', 'lift-docs-system'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Bulk Actions Form -->
                    <form method="post" id="forms-bulk-action-form">
                        <?php wp_nonce_field('bulk_action_forms'); ?>
                        
                        <!-- Bulk Actions Top -->
                        <div class="tablenav top">
                            <div class="alignleft actions bulkactions">
                                <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'lift-docs-system'); ?></label>
                                <select name="action" id="bulk-action-selector-top">
                                    <option value="-1"><?php _e('Bulk Actions', 'lift-docs-system'); ?></option>
                                    <option value="bulk_delete"><?php _e('Delete', 'lift-docs-system'); ?></option>
                                    <option value="bulk_activate"><?php _e('Activate', 'lift-docs-system'); ?></option>
                                    <option value="bulk_deactivate"><?php _e('Deactivate', 'lift-docs-system'); ?></option>
                                    <option value="bulk_draft"><?php _e('Move to Draft', 'lift-docs-system'); ?></option>
                                </select>
                                <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'lift-docs-system'); ?>">
                            </div>
                        </div>

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <td id="cb" class="manage-column column-cb check-column">
                                        <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'lift-docs-system'); ?></label>
                                        <input id="cb-select-all-1" type="checkbox" />
                                    </td>
                                    <th><?php _e('Form Name', 'lift-docs-system'); ?></th>
                                    <th><?php _e('Description', 'lift-docs-system'); ?></th>
                                    <th><?php _e('Submissions', 'lift-docs-system'); ?></th>
                                    <th><?php _e('Status', 'lift-docs-system'); ?></th>
                                    <th><?php _e('Created', 'lift-docs-system'); ?></th>
                                    <th><?php _e('Actions', 'lift-docs-system'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" name="forms[]" value="<?php echo $form->id; ?>" />
                                        </th>
                                        <td>
                                            <strong><?php echo esc_html($form->name); ?></strong>
                                            <div class="row-actions">
                                                <span class="edit">
                                                    <a href="<?php echo admin_url('admin.php?page=lift-forms-builder&id=' . $form->id); ?>">
                                                        <?php _e('Edit', 'lift-docs-system'); ?>
                                                    </a>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($form->description); ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=lift-forms-submissions&form_id=' . $form->id); ?>">
                                                <strong style="color: #0073aa; font-size: 16px;">
                                                    <?php echo $this->get_form_submissions_count($form->id); ?>
                                                </strong>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="status-container">
                                                <select class="form-status-select small-text" data-form-id="<?php echo $form->id; ?>">
                                                    <option value="active" <?php selected($form->status, 'active'); ?>><?php _e('Active', 'lift-docs-system'); ?></option>
                                                    <option value="inactive" <?php selected($form->status, 'inactive'); ?>><?php _e('Inactive', 'lift-docs-system'); ?></option>
                                                    <option value="draft" <?php selected($form->status, 'draft'); ?>><?php _e('Draft', 'lift-docs-system'); ?></option>
                                                </select>
                                                <div class="status-spinner" style="display: none;">
                                                    <span class="spinner is-active" style="float: none; margin: 0;"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($form->created_at)); ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=lift-forms-builder&id=' . $form->id); ?>" class="button">
                                                <?php _e('Edit', 'lift-docs-system'); ?>
                                            </a>
                                            <button type="button" class="button lift-export-single-btn" data-form-id="<?php echo $form->id; ?>" data-form-name="<?php echo esc_attr($form->name); ?>">
                                                <?php _e('Export', 'lift-docs-system'); ?>
                                            </button>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lift-forms&action=delete&id=' . $form->id), 'delete_form_' . $form->id); ?>"
                                               class="button button-link-delete"
                                               onclick="return confirm('<?php _e('Are you sure you want to delete this form?', 'lift-docs-system'); ?>')">
                                                <?php _e('Delete', 'lift-docs-system'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Bulk Actions Bottom -->
                        <div class="tablenav bottom">
                            <div class="alignleft actions bulkactions">
                                <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'lift-docs-system'); ?></label>
                                <select name="action" id="bulk-action-selector-bottom">
                                    <option value="-1"><?php _e('Bulk Actions', 'lift-docs-system'); ?></option>
                                    <option value="bulk_delete"><?php _e('Delete', 'lift-docs-system'); ?></option>
                                    <option value="bulk_activate"><?php _e('Activate', 'lift-docs-system'); ?></option>
                                    <option value="bulk_deactivate"><?php _e('Deactivate', 'lift-docs-system'); ?></option>
                                    <option value="bulk_draft"><?php _e('Move to Draft', 'lift-docs-system'); ?></option>
                                </select>
                                <input type="submit" id="doaction2" class="button action" value="<?php _e('Apply', 'lift-docs-system'); ?>">
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Import Form Modal -->
        <div id="lift-import-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2><?php _e('Import Form', 'lift-docs-system'); ?></h2>
                    <span class="lift-modal-close">&times;</span>
                </div>
                <div class="lift-modal-body">
                    <form id="lift-import-form" enctype="multipart/form-data">
                        <div class="lift-form-field">
                            <label for="lift-import-file"><?php _e('Select JSON File', 'lift-docs-system'); ?></label>
                            <input type="file" id="lift-import-file" name="import_file" accept=".json" required>
                            <p class="description"><?php _e('Upload a JSON file exported from LIFT Forms', 'lift-docs-system'); ?></p>
                        </div>
                        <div class="lift-form-field">
                            <label for="lift-import-name"><?php _e('Form Name (Optional)', 'lift-docs-system'); ?></label>
                            <input type="text" id="lift-import-name" name="form_name" placeholder="<?php _e('Leave empty to use original name', 'lift-docs-system'); ?>">
                        </div>
                        <div class="lift-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Import Form', 'lift-docs-system'); ?>
                            </button>
                            <button type="button" class="button lift-modal-cancel">
                                <?php _e('Cancel', 'lift-docs-system'); ?>
                            </button>
                        </div>
                        <div id="lift-import-progress" style="display: none;">
                            <div class="lift-progress-bar">
                                <div class="lift-progress-fill"></div>
                            </div>
                            <p><?php _e('Importing form...', 'lift-docs-system'); ?></p>
                        </div>
                        <div id="lift-import-result" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Import/Export functionality
            var importModal = $('#lift-import-modal');

            // Import button click
            $('#lift-import-form-btn').on('click', function() {
                importModal.show();
            });

            // Close modal
            $('.lift-modal-close, .lift-modal-cancel').on('click', function() {
                importModal.hide();
                resetImportForm();
            });

            // Click outside modal to close
            $(window).on('click', function(e) {
                if (e.target === importModal[0]) {
                    importModal.hide();
                    resetImportForm();
                }
            });

            // Reset import form
            function resetImportForm() {
                $('#lift-import-form')[0].reset();
                $('#lift-import-progress, #lift-import-result').hide();
            }

            // Handle import form submission
            $('#lift-import-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'lift_forms_import');
                formData.append('nonce', '<?php echo wp_create_nonce('lift_forms_import_nonce'); ?>');

                if ($('#lift-import-file')[0].files.length === 0) {
                    alert('Please select a JSON file first');
                    return;
                }

                $('#lift-import-progress').show();
                $('#lift-import-result').hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('LIFT Forms Import: Server response:', response);
                        $('#lift-import-progress').hide();
                        if (response.success) {
                            $('#lift-import-result')
                                .html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>')
                                .show();
                            
                            // Reload page after 2 seconds
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            console.error('LIFT Forms Import: Error:', response.data);
                            $('#lift-import-result')
                                .html('<div class="notice notice-error"><p>' + response.data + '</p></div>')
                                .show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('LIFT Forms Import: AJAX error:', xhr.responseText);
                        $('#lift-import-progress').hide();
                        $('#lift-import-result')
                            .html('<div class="notice notice-error"><p><?php _e('An error occurred during import', 'lift-docs-system'); ?></p></div>')
                            .show();
                    }
                });
            });

            // Export single form
            $('.lift-export-single-btn').on('click', function() {
                var formId = $(this).data('form-id');
                var formName = $(this).data('form-name');
                
                var link = document.createElement('a');
                link.href = ajaxurl + '?action=lift_forms_export&form_id=' + formId + '&nonce=' + '<?php echo wp_create_nonce('lift_forms_export_nonce'); ?>';
                link.download = 'lift-form-' + formName.toLowerCase().replace(/[^a-z0-9]/g, '-') + '.json';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Export all forms
            $('#lift-export-all-forms-btn').on('click', function() {
                var link = document.createElement('a');
                link.href = ajaxurl + '?action=lift_forms_export_all&nonce=' + '<?php echo wp_create_nonce('lift_forms_export_nonce'); ?>';
                link.download = 'lift-forms-backup-' + new Date().toISOString().slice(0,10) + '.json';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // Handle form status updates
            $('.form-status-select').on('change', function() {
                var $select = $(this);
                var $spinner = $select.siblings('.status-spinner');
                var formId = $select.data('form-id');
                var newStatus = $select.val();
                var originalStatus = $select.data('original-status') || $select.val();

                // Store original status
                if (!$select.data('original-status')) {
                    $select.data('original-status', originalStatus);
                }

                // Show spinner
                $spinner.show();
                $select.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lift_forms_update_status',
                        form_id: formId,
                        status: newStatus,
                        nonce: '<?php echo wp_create_nonce('lift_forms_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                .insertAfter('.wrap h1')
                                .delay(3000)
                                .fadeOut();

                            // Update stored original status
                            $select.data('original-status', response.data.status);
                        } else {
                            alert('Error: ' + response.data);
                            // Revert select to original value
                            $select.val(originalStatus);
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred while updating the form status.', 'lift-docs-system'); ?>');
                        // Revert select to original value
                        $select.val(originalStatus);
                    },
                    complete: function() {
                        // Hide spinner and re-enable select
                        $spinner.hide();
                        $select.prop('disabled', false);
                    }
                });
            });

            // Bulk Actions Functionality
            var $bulkForm = $('#forms-bulk-action-form');
            var $checkboxes = $bulkForm.find('input[type="checkbox"][name="forms[]"]');
            var $selectAllTop = $('#cb-select-all-1');
            var $bulkActionSelectors = $('#bulk-action-selector-top, #bulk-action-selector-bottom');

            // Select All functionality
            $selectAllTop.on('change', function() {
                var isChecked = $(this).is(':checked');
                $checkboxes.prop('checked', isChecked);
                updateBulkActionState();
            });

            // Individual checkbox change
            $checkboxes.on('change', function() {
                var totalCheckboxes = $checkboxes.length;
                var checkedCheckboxes = $checkboxes.filter(':checked').length;
                
                $selectAllTop.prop('checked', checkedCheckboxes === totalCheckboxes);
                $selectAllTop.prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
                
                updateBulkActionState();
            });

            // Update bulk action button state
            function updateBulkActionState() {
                var hasSelection = $checkboxes.filter(':checked').length > 0;
                $bulkForm.find('.button.action').prop('disabled', !hasSelection);
            }

            // Handle bulk action form submission
            $bulkForm.on('submit', function(e) {
                var selectedAction = $bulkActionSelectors.filter(function() { 
                    return $(this).closest('.bulkactions').find('.button.action:focus').length > 0 || 
                           $(this).val() !== '-1'; 
                }).first().val();

                if (selectedAction === '-1' || selectedAction === '') {
                    e.preventDefault();
                    alert('<?php _e("Please select a bulk action.", "lift-docs-system"); ?>');
                    return false;
                }

                var checkedCount = $checkboxes.filter(':checked').length;
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('<?php _e("Please select at least one form.", "lift-docs-system"); ?>');
                    return false;
                }

                // Confirmation for delete action
                if (selectedAction === 'bulk_delete') {
                    var confirmMessage = '<?php printf(__("You are about to permanently delete %s forms. This action cannot be undone. Are you sure?", "lift-docs-system"), "' + checkedCount + '"); ?>';
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return false;
                    }
                }

                // Set the action for whichever button was clicked
                $(this).find('select[name="action"]').val(selectedAction);
            });

            // Enhanced Search Functionality
            var searchTimeout;
            var $searchForm = $('.lift-forms-search-form form');
            var $searchInputs = $searchForm.find('input[type="text"], input[type="date"], select');
            var $searchButton = $searchForm.find('button[type="submit"]');
            
            if ($searchButton.length > 0) {
                var originalButtonText = $searchButton.html();

                // Auto-search functionality with debouncing
                $searchInputs.on('input change', function() {
                    clearTimeout(searchTimeout);
                    
                    // Visual feedback
                    $searchButton.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> <?php _e("Searching...", "lift-docs-system"); ?>');
                    
                    searchTimeout = setTimeout(function() {
                        // Check if there are any search terms
                        var hasSearchTerms = false;
                        $searchInputs.each(function() {
                            if ($(this).val().trim() !== '') {
                                hasSearchTerms = true;
                                return false;
                            }
                        });
                        
                        if (hasSearchTerms) {
                            // Submit form automatically after delay
                            $searchForm.submit();
                        } else {
                            // Reset button state
                            $searchButton.prop('disabled', false).html(originalButtonText);
                        }
                    }, 1000); // 1 second delay for debouncing
                });

                // Highlight search terms in table if any active search
                function highlightSearchTerms() {
                    var searchTerms = [];
                    
                    // Collect all search terms
                    $searchInputs.filter('input[type="text"]').each(function() {
                        var term = $(this).val().trim();
                        if (term !== '') {
                            searchTerms.push(term);
                        }
                    });
                    
                    if (searchTerms.length > 0) {
                        // Remove existing highlights first
                        $('.wp-list-table .search-highlight').each(function() {
                            var $this = $(this);
                            $this.replaceWith($this.text());
                        });
                        
                        // Add new highlights - but only to text nodes, not inside HTML tags
                        $('.wp-list-table tbody td').each(function() {
                            var $cell = $(this);
                            
                            // Skip cells with complex HTML (like action buttons)
                            if ($cell.find('a, button, select').length > 0) {
                                return;
                            }
                            
                            // Only highlight simple text content
                            $cell.contents().filter(function() {
                                return this.nodeType === 3; // Text node
                            }).each(function() {
                                var textNode = this;
                                var text = textNode.textContent;
                                var highlightedText = text;
                                
                                searchTerms.forEach(function(term) {
                                    var regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                                    highlightedText = highlightedText.replace(regex, '<span class="search-highlight">$1</span>');
                                });
                                
                                if (highlightedText !== text) {
                                    $(textNode).replaceWith(highlightedText);
                                }
                            });
                        });
                    }
                }
                
                // Apply highlighting on page load if there are search terms
                var hasActiveSearch = false;
                $searchInputs.each(function() {
                    if ($(this).val().trim() !== '') {
                        hasActiveSearch = true;
                        return false;
                    }
                });
                
                if (hasActiveSearch) {
                    setTimeout(highlightSearchTerms, 100); // Small delay to ensure DOM is ready
                }
            }

            // Initialize bulk action state
            updateBulkActionState();
        });
        </script>

        <style type="text/css">
        /* Forms Search Form Styles */
        .lift-forms-search-form {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }

        .lift-forms-search-form h3 {
            margin-top: 0;
            color: #0073aa;
            font-size: 18px;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .lift-forms-search-form input[type="text"],
        .lift-forms-search-form input[type="date"],
        .lift-forms-search-form select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .lift-forms-search-form input[type="text"]:focus,
        .lift-forms-search-form input[type="date"]:focus,
        .lift-forms-search-form select:focus {
            border-color: #0073aa;
            box-shadow: 0 0 4px rgba(0, 115, 170, 0.3);
            outline: none;
        }

        .lift-forms-search-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .lift-forms-search-form .search-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .lift-forms-search-form .search-results-count {
            margin-left: auto;
            color: #666;
            font-style: italic;
            font-size: 13px;
        }

        /* Bulk Actions Styles */
        .tablenav .bulkactions select {
            margin-right: 5px;
        }

        .tablenav .bulkactions .button.action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Checkbox column styling */
        .wp-list-table th.check-column,
        .wp-list-table td.check-column {
            width: 2.2em;
            padding: 6px 0 25px;
            vertical-align: top;
        }

        .wp-list-table tbody th.check-column {
            padding: 9px 0 22px;
        }

        .wp-list-table .check-column input[type="checkbox"] {
            margin: 0;
        }

        /* Enhanced table appearance for search results */
        .wp-list-table.striped > tbody > :nth-child(odd) {
            background-color: #fafafa;
        }

        .wp-list-table tr:hover {
            background-color: #f0f8ff;
        }

        /* Search highlight effect */
        .search-highlight {
            background-color: #ffeb3b;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: bold;
        }

        /* Rotation animation for search loading */
        @keyframes rotation {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Status select styling */
        .status-container {
            position: relative;
            display: inline-block;
        }

        .form-status-select {
            min-width: 80px;
        }

        .status-spinner {
            position: absolute;
            top: 50%;
            right: 5px;
            transform: translateY(-50%);
        }

        /* Responsive Design */
        @media (max-width: 782px) {
            .lift-forms-search-form > form > div:first-child {
                grid-template-columns: 1fr;
            }
            
            .lift-forms-search-form .search-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .lift-forms-search-form .search-results-count {
                margin-left: 0;
                text-align: center;
                order: -1;
                margin-bottom: 10px;
            }

            .tablenav .bulkactions {
                margin-bottom: 10px;
            }

            .tablenav .bulkactions select,
            .tablenav .bulkactions .button {
                margin-bottom: 5px;
            }
        }

        /* Import/Export button styling */
        .lift-forms-import-export {
            display: inline-block;
            margin-left: 10px;
        }

        .lift-forms-import-export .page-title-action {
            margin-left: 5px;
        }
        </style>
        <?php
    }

    /**
     * Form builder page - Minimal version with header only
     */
    public function form_builder_page() {
        $form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $form = null;

        if ($form_id) {
            global $wpdb;
            $forms_table = $wpdb->prefix . 'lift_forms';
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));
            
            // Parse settings to extract header and footer if form exists
            if ($form && !empty($form->settings)) {
                $settings_data = json_decode($form->settings, true);
                if (is_array($settings_data)) {
                    $form->form_header = $settings_data['form_header'] ?? '';
                    $form->form_footer = $settings_data['form_footer'] ?? '';
                } else {
                    $form->form_header = '';
                    $form->form_footer = '';
                }
            } elseif ($form) {
                // Ensure properties exist even if settings is empty
                $form->form_header = '';
                $form->form_footer = '';
            }
        }

        // Show success message if form was just created
        if (isset($_GET['created']) && $_GET['created'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Form created successfully! You can now edit your form below.', 'lift-docs-system') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php echo $form ? __('Edit Form', 'lift-docs-system') : __('Create New Form', 'lift-docs-system'); ?></h1>

            <div class="lift-form-builder">
                <div class="form-builder-header">
                    <div class="form-basic-settings">
                        <input type="hidden" id="form-id" value="<?php echo $form ? $form->id : 0; ?>">
                        <div class="setting-group">
                            <input type="text"
                                   id="form-name"
                                   value="<?php echo $form ? esc_attr($form->name) : ''; ?>"
                                   placeholder="<?php _e('Enter form name (minimum 3 characters)...', 'lift-docs-system'); ?>"
                                   title="<?php _e('Form name must be at least 3 characters and contain only letters, numbers, spaces, and basic punctuation', 'lift-docs-system'); ?>"
                                   maxlength="255">
                        </div>
                        <div class="setting-group">
                            <input id="form-description"
                                   placeholder="<?php _e('Enter form description (optional)...', 'lift-docs-system'); ?>"
                                   value="<?php echo $form ? esc_attr($form->description) : ''; ?>"
                                   type="text"
                                   title="<?php _e('Brief description of what this form is for', 'lift-docs-system'); ?>">
                        </div>
                        <div class="form-actions">
                            <button type="button" id="save-form" class="button button-primary">
                                <?php _e('Save Form', 'lift-docs-system'); ?>
                            </button>
                            <?php if (!$form): // Only show for new forms ?>
                            <button type="button" id="load-template-btn" class="button button-secondary">
                                <span class="dashicons dashicons-upload"></span>
                                <?php _e('Load from Template', 'lift-docs-system'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Form Builder Content Area - BPMN.io Form Builder -->
                <div class="form-builder-content">
                    <!-- Tab Navigation -->
                    <div class="form-builder-tabs">
                        <button type="button" class="tab-button active" data-tab="form-builder">
                            <span class="dashicons dashicons-editor-table"></span>
                            <?php _e('Form Builder', 'lift-docs-system'); ?>
                        </button>
                        <button type="button" class="tab-button" data-tab="header-footer">
                            <span class="dashicons dashicons-editor-code"></span>
                            <?php _e('Header & Footer', 'lift-docs-system'); ?>
                        </button>
                    </div>

                    <!-- Tab Content: Form Builder -->
                    <div id="tab-form-builder" class="tab-content active">
                        <div id="form-builder-container">
                            <div class="loading-message">
                                <div class="spinner"></div>
                                <p><?php _e('Loading form builder...', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: Header & Footer Editors -->
                    <div id="tab-header-footer" class="tab-content">
                        <div class="header-footer-editors">
                            <div class="editor-section">
                                <h3>
                                    <span class="dashicons dashicons-editor-alignleft"></span>
                                    <?php _e('Form Header', 'lift-docs-system'); ?>
                                </h3>
                                <p class="description"><?php _e('Content that appears at the top of your form', 'lift-docs-system'); ?></p>
                                <div id="header-editor-container">
                                    <?php 
                                    $form_header_content = $form ? $form->form_header : '';
                                    wp_editor($form_header_content, 'form_header', array(
                                        'textarea_name' => 'form_header',
                                        'textarea_rows' => 8,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'dfw' => false,
                                        'tinymce' => array(
                                            'toolbar1' => 'formatselect,fontselect,fontsizeselect,|,bold,italic,underline,strikethrough,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,bullist,numlist,blockquote,|,outdent,indent,|,link,unlink,|,charmap,hr,|,undo,redo,|,pastetext,removeformat,|,fullscreen,wp_more',
                                            'toolbar2' => '',
                                            'toolbar3' => '',
                                            'height' => 200,
                                            'menubar' => true,
                                            'branding' => false,
                                            'statusbar' => true,
                                            'resize' => true,
                                            'wordpress_adv_hidden' => false
                                        ),
                                        'quicktags' => array(
                                            'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
                                        )
                                    )); 
                                    ?>
                                </div>
                            </div>

                            <div class="editor-section">
                                <h3>
                                    <span class="dashicons dashicons-editor-alignright"></span>
                                    <?php _e('Form Footer', 'lift-docs-system'); ?>
                                </h3>
                                <p class="description"><?php _e('Content that appears at the bottom of your form', 'lift-docs-system'); ?></p>
                                <div id="footer-editor-container">
                                    <?php 
                                    $form_footer_content = $form ? $form->form_footer : '';
                                    wp_editor($form_footer_content, 'form_footer', array(
                                        'textarea_name' => 'form_footer',
                                        'textarea_rows' => 8,
                                        'media_buttons' => true,
                                        'teeny' => false,
                                        'dfw' => false,
                                        'tinymce' => array(
                                            'toolbar1' => 'formatselect,fontselect,fontsizeselect,|,bold,italic,underline,strikethrough,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,bullist,numlist,blockquote,|,outdent,indent,|,link,unlink,|,charmap,hr,|,undo,redo,|,pastetext,removeformat,|,fullscreen,wp_more',
                                            'toolbar2' => '',
                                            'toolbar3' => '',
                                            'height' => 200,
                                            'menubar' => true,
                                            'branding' => false,
                                            'statusbar' => true,
                                            'resize' => true,
                                            'wordpress_adv_hidden' => false
                                        ),
                                        'quicktags' => array(
                                            'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
                                        )
                                    )); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Forms page script initialized with PHP wp_editor');
            
            // Tab switching functionality
            $('.tab-button').on('click', function() {
                var targetTab = $(this).data('tab');
                
                // Remove active class from all tabs and content
                $('.tab-button').removeClass('active');
                $('.tab-content').removeClass('active');
                
                // Add active class to clicked tab and corresponding content
                $(this).addClass('active');
                $('#tab-' + targetTab).addClass('active');
                
                // If switching to header-footer tab, just log for now
                if (targetTab === 'header-footer') {
                    console.log('Switched to Header & Footer tab');
                    
                    // Simple check for editors without calling methods that might not exist
                    setTimeout(function() {
                        if (typeof tinymce !== 'undefined') {
                            var headerEditor = tinymce.get('form_header');
                            var footerEditor = tinymce.get('form_footer');
                            
                            if (headerEditor) {
                                console.log('Header editor found and ready');
                            }
                            if (footerEditor) {
                                console.log('Footer editor found and ready');
                            }
                        }
                    }, 300);
                }
            });
            
            // Initialize form builder and handle editor movement when ready
            $(window).on('load', function() {
                // Wait for WordPress editors to be fully loaded
                setTimeout(function() {
                    if ($('#form_header').length && $('#form_footer').length) {
                        console.log('WordPress PHP editors found and ready');
                        
                        // Check if editors are initialized
                        if (typeof tinymce !== 'undefined') {
                            var headerEditor = tinymce.get('form_header');
                            var footerEditor = tinymce.get('form_footer');
                            
                            if (headerEditor && footerEditor) {
                                console.log('TinyMCE editors are initialized and ready');
                            } else {
                                console.log('TinyMCE editors not yet initialized, waiting...');
                                // Wait a bit more for TinyMCE initialization
                                setTimeout(function() {
                                    console.log('Secondary check for TinyMCE initialization complete');
                                }, 2000);
                            }
                        }
                    } else {
                        console.log('WordPress PHP editors not found yet');
                    }
                }, 1500);
            });
        });
        </script>

        <!-- Template Loader Modal -->
        <div id="template-loader-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2><?php _e('Load Form Template', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>
                <div class="lift-modal-body">
                    <!-- Template Tabs -->
                    <div class="template-tabs">
                        <button type="button" class="template-tab-btn active" data-tab="preset-templates">
                            <span class="dashicons dashicons-portfolio"></span>
                            <?php _e('Built-in Templates', 'lift-docs-system'); ?>
                        </button>
                        <button type="button" class="template-tab-btn" data-tab="upload-template">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Upload Template', 'lift-docs-system'); ?>
                        </button>
                    </div>

                    <!-- Preset Templates Tab -->
                    <div id="preset-templates" class="template-tab-content active">
                        <div class="preset-templates-grid">
                            <div class="template-loading">
                                <span class="dashicons dashicons-update spin"></span>
                                <?php _e('Loading templates...', 'lift-docs-system'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Template Tab -->
                    <div id="upload-template" class="template-tab-content">
                        <form id="template-loader-form" enctype="multipart/form-data">
                            <div class="lift-form-field">
                                <label for="template-file"><?php _e('Select Template File', 'lift-docs-system'); ?></label>
                                <input type="file" id="template-file" name="template_file" accept=".json" required>
                                <p class="description"><?php _e('Upload a JSON template file exported from LIFT Forms', 'lift-docs-system'); ?></p>
                            </div>
                            <div class="lift-form-field">
                                <label>
                                    <input type="checkbox" id="load-template-name" checked>
                                    <?php _e('Use template form name', 'lift-docs-system'); ?>
                                </label>
                                <p class="description"><?php _e('If unchecked, you can set a custom name for this form', 'lift-docs-system'); ?></p>
                            </div>
                            <div class="lift-form-actions">
                                <button type="submit" class="button button-primary">
                                    <?php _e('Load Template', 'lift-docs-system'); ?>
                                </button>
                                <button type="button" class="button lift-modal-cancel">
                                    <?php _e('Cancel', 'lift-docs-system'); ?>
                                </button>
                            </div>
                            <div id="template-load-progress" style="display: none;">
                                <div class="lift-progress-bar">
                                    <div class="lift-progress-fill"></div>
                                </div>
                                <p><?php _e('Loading template...', 'lift-docs-system'); ?></p>
                            </div>
                            <div id="template-load-result" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Template Loader functionality
            $('#load-template-btn').on('click', function() {
                $('#template-loader-modal').show();
                loadPresetTemplates();
            });

            // Tab switching
            $('.template-tab-btn').on('click', function() {
                var targetTab = $(this).data('tab');
                
                // Switch tab buttons
                $('.template-tab-btn').removeClass('active');
                $(this).addClass('active');
                
                // Switch tab content
                $('.template-tab-content').removeClass('active');
                $('#' + targetTab).addClass('active');
            });

            // Load preset templates from server
            function loadPresetTemplates() {
                $('.preset-templates-grid').html('<div class="template-loading"><span class="dashicons dashicons-update spin"></span><?php _e('Loading templates...', 'lift-docs-system'); ?></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lift_forms_get_templates',
                        nonce: '<?php echo wp_create_nonce('lift_forms_templates_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayPresetTemplates(response.data);
                        } else {
                            $('.preset-templates-grid').html('<div class="template-error"><p><?php _e('Error loading templates: ', 'lift-docs-system'); ?>' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('.preset-templates-grid').html('<div class="template-error"><p><?php _e('Error loading templates from server', 'lift-docs-system'); ?></p></div>');
                    }
                });
            }

            // Display preset templates grid
            function displayPresetTemplates(templates) {
                var html = '';
                
                if (templates.length === 0) {
                    html = '<div class="no-templates"><p><?php _e('No templates found in templates directory', 'lift-docs-system'); ?></p></div>';
                } else {
                    templates.forEach(function(template) {
                        var headerBadge = template.has_header ? '<span class="template-badge header-badge" title="<?php _e('Has Header', 'lift-docs-system'); ?>">H</span>' : '';
                        var footerBadge = template.has_footer ? '<span class="template-badge footer-badge" title="<?php _e('Has Footer', 'lift-docs-system'); ?>">F</span>' : '';
                        
                        html += '<div class="template-card" data-filename="' + template.filename + '">';
                        html += '<div class="template-card-header">';
                        html += '<h4>' + template.name + '</h4>';
                        html += '<div class="template-badges">' + headerBadge + footerBadge + '</div>';
                        html += '</div>';
                        html += '<div class="template-card-body">';
                        html += '<p class="template-description">' + (template.description || '<?php _e('No description available', 'lift-docs-system'); ?>') + '</p>';
                        html += '<div class="template-meta">';
                        html += '<span class="fields-count"><span class="dashicons dashicons-editor-table"></span> ' + template.fields_count + ' <?php _e('fields', 'lift-docs-system'); ?></span>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="template-card-actions">';
                        html += '<button type="button" class="button button-primary select-template-btn" data-filename="' + template.filename + '"><?php _e('Use Template', 'lift-docs-system'); ?></button>';
                        html += '</div>';
                        html += '</div>';
                    });
                }
                
                $('.preset-templates-grid').html(html);
            }

            // Handle template selection - immediately load template
            $(document).on('click', '.select-template-btn', function() {
                var filename = $(this).data('filename');
                
                // Show loading state
                $(this).text('<?php _e('Loading...', 'lift-docs-system'); ?>').prop('disabled', true);
                
                // Load template directly
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lift_forms_load_template',
                        filename: filename,
                        nonce: '<?php echo wp_create_nonce('lift_forms_templates_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Load template data into form builder
                            loadTemplateData(response.data, true); // Always use template name
                            
                            // Close modal immediately
                            $('#template-loader-modal').hide();
                            resetTemplateModal();
                        } else {
                            alert('<?php _e('Error loading template: ', 'lift-docs-system'); ?>' + response.data);
                            $(this).text('<?php _e('Use Template', 'lift-docs-system'); ?>').prop('disabled', false);
                        }
                    }.bind(this),
                    error: function() {
                        alert('<?php _e('Error loading template from server', 'lift-docs-system'); ?>');
                        $(this).text('<?php _e('Use Template', 'lift-docs-system'); ?>').prop('disabled', false);
                    }.bind(this)
                });
            });

            // Close modal events
            $('.lift-modal-close, .lift-modal-cancel').on('click', function() {
                $('#template-loader-modal').hide();
                resetTemplateModal();
            });

            // Close modal events
            $('.lift-modal-close, .lift-modal-cancel').on('click', function() {
                $('#template-loader-modal').hide();
                resetTemplateModal();
            });

            // Close modal when clicking outside
            $('#template-loader-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                    resetTemplateModal();
                }
            });

            // Reset modal state
            function resetTemplateModal() {
                $('#template-loader-form')[0].reset();
                $('#template-load-progress').hide();
                $('#template-load-result').hide();
                $('.template-card').removeClass('selected');
                
                // Switch back to preset templates tab
                $('.template-tab-btn').removeClass('active');
                $('.template-tab-btn[data-tab="preset-templates"]').addClass('active');
                $('.template-tab-content').removeClass('active');
                $('#preset-templates').addClass('active');
            }

            // Handle file upload template loading (Upload Template tab)
            $('#template-loader-form').on('submit', function(e) {
                e.preventDefault();
                
                var fileInput = $('#template-file')[0];
                var useTemplateName = $('#use-template-name').is(':checked');
                
                if (!fileInput.files.length) {
                    alert('<?php _e('Please select a template file', 'lift-docs-system'); ?>');
                    return;
                }
                
                var file = fileInput.files[0];
                if (file.type !== 'application/json' && !file.name.toLowerCase().endsWith('.json')) {
                    alert('<?php _e('Please select a valid JSON file', 'lift-docs-system'); ?>');
                    return;
                }
                
                $('#template-load-progress').show();
                $('#template-load-result').hide();
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var templateData = JSON.parse(e.target.result);
                        
                        // Validate template structure
                        if (!validateTemplateStructure(templateData)) {
                            throw new Error('<?php _e('Invalid template structure', 'lift-docs-system'); ?>');
                        }
                        
                        loadTemplateData(templateData, useTemplateName);
                        
                        $('#template-load-progress').hide();
                        $('#template-load-result').html('<div class="notice notice-success"><p><?php _e('Template loaded successfully!', 'lift-docs-system'); ?></p></div>').show();
                        
                        setTimeout(function() {
                            $('#template-loader-modal').hide();
                            resetTemplateModal();
                        }, 1500);
                        
                    } catch (error) {
                        $('#template-load-progress').hide();
                        $('#template-load-result').html('<div class="notice notice-error"><p><?php _e('Error loading template: ', 'lift-docs-system'); ?>' + error.message + '</p></div>').show();
                    }
                };
                
                reader.readAsText(file);
            });

            // Validate template structure
            function validateTemplateStructure(templateData) {
                return templateData && 
                       Array.isArray(templateData.fields) && 
                       templateData.fields.length > 0;
            }

            // Load template data into form builder
            function loadTemplateData(templateData, useTemplateName) {
                console.log('Loading template data:', templateData);
                
                // Set form name if requested
                if (useTemplateName && templateData.name) {
                    $('#form-name').val(templateData.name);
                }
                
                // Set form description if exists
                if (templateData.description) {
                    $('#form-description').val(templateData.description);
                }
                
                // Load header if exists
                if (templateData.form_header) {
                    $('#form_header').val(templateData.form_header);
                    if (typeof tinymce !== 'undefined' && tinymce.get('form_header')) {
                        setTimeout(function() {
                            tinymce.get('form_header').setContent(templateData.form_header);
                        }, 500);
                    }
                }
                
                // Load footer if exists
                if (templateData.form_footer) {
                    $('#form_footer').val(templateData.form_footer);
                    if (typeof tinymce !== 'undefined' && tinymce.get('form_footer')) {
                        setTimeout(function() {
                            tinymce.get('form_footer').setContent(templateData.form_footer);
                        }, 500);
                    }
                }
                
                // Load form fields and layout into form builder
                if (templateData.fields && Array.isArray(templateData.fields)) {
                    // Try to use existing form builder integration first
                    if (window.formBuilder && typeof window.formBuilder.loadTemplate === 'function') {
                        // Use the form builder's method to load template data
                        window.formBuilder.loadTemplate(templateData);
                        console.log('Template loaded via formBuilder.loadTemplate');
                    } else if (typeof rebuildFormBuilderWithData === 'function') {
                        // Use our custom function
                        rebuildFormBuilderWithData(templateData.fields);
                        console.log('Template loaded via rebuildFormBuilderWithData');
                    } else {
                        // Fallback: wait for form builder to load then set data
                        let attempts = 0;
                        const maxAttempts = 10;
                        
                        const checkFormBuilder = function() {
                            attempts++;
                            if (window.formBuilder && typeof window.formBuilder.loadTemplate === 'function') {
                                window.formBuilder.loadTemplate(templateData);
                                console.log('Template loaded into form builder after ' + attempts + ' attempts');
                            } else if (attempts < maxAttempts) {
                                setTimeout(checkFormBuilder, 500);
                            } else {
                                console.error('Form builder not found after ' + maxAttempts + ' attempts');
                                // Try to reload form fields directly
                                rebuildFormBuilderWithData(templateData.fields);
                            }
                        };
                        
                        setTimeout(checkFormBuilder, 100);
                    }
                }
                
                console.log('Template loading completed:', templateData.name);
            }

            // Rebuild form builder with template data
            function rebuildFormBuilderWithData(fields) {
                // Clear existing fields
                $('#form-builder').empty();
                
                // Add each field from template
                fields.forEach(function(fieldData, index) {
                    var fieldElement = createFieldElementFromData(fieldData, index + 1);
                    $('#form-builder').append(fieldElement);
                });
                
                // Update field counter if it exists
                if (typeof fieldCounter !== 'undefined') {
                    fieldCounter = fields.length;
                }
                
                // Reinitialize sortable and other interactions if the function exists
                if (typeof initializeFormBuilder === 'function') {
                    initializeFormBuilder();
                }
            }

            // Create field element from template data
            function createFieldElementFromData(fieldData, fieldNumber) {
                var fieldId = 'field_' + fieldNumber;
                var fieldElement = $('<div class="form-field" data-field-type="' + fieldData.type + '" data-field-id="' + fieldId + '">');
                
                // Field header
                var fieldHeader = $('<div class="field-header">');
                fieldHeader.append('<span class="field-type-label">' + fieldData.type.toUpperCase() + '</span>');
                fieldHeader.append('<div class="field-actions">');
                fieldHeader.find('.field-actions').append('<button type="button" class="edit-field-btn button button-small"><?php _e('Edit', 'lift-docs-system'); ?></button>');
                fieldHeader.find('.field-actions').append('<button type="button" class="delete-field-btn button button-small"><?php _e('Delete', 'lift-docs-system'); ?></button>');
                fieldHeader.find('.field-actions').append('</div>');
                fieldElement.append(fieldHeader);
                
                // Field preview
                var fieldPreview = $('<div class="field-preview">');
                fieldPreview.append('<label>' + fieldData.label + (fieldData.required ? ' <span class="required">*</span>' : '') + '</label>');
                
                // Create preview based on field type
                switch(fieldData.type) {
                    case 'text':
                    case 'email':
                    case 'tel':
                        fieldPreview.append('<input type="' + fieldData.type + '" placeholder="' + (fieldData.placeholder || '') + '" disabled>');
                        break;
                    case 'textarea':
                        fieldPreview.append('<textarea placeholder="' + (fieldData.placeholder || '') + '" disabled></textarea>');
                        break;
                    case 'select':
                        var select = $('<select disabled>');
                        if (fieldData.options && Array.isArray(fieldData.options)) {
                            fieldData.options.forEach(function(option) {
                                select.append('<option value="' + option.value + '">' + option.label + '</option>');
                            });
                        }
                        fieldPreview.append(select);
                        break;
                    case 'radio':
                    case 'checkbox':
                        if (fieldData.options && Array.isArray(fieldData.options)) {
                            fieldData.options.forEach(function(option, optIndex) {
                                var input = $('<div class="option-item">');
                                input.append('<input type="' + fieldData.type + '" name="' + fieldId + '" value="' + option.value + '" disabled>');
                                input.append('<label>' + option.label + '</label>');
                                fieldPreview.append(input);
                            });
                        }
                        break;
                    case 'file':
                        fieldPreview.append('<input type="file" disabled>');
                        break;
                }
                
                fieldElement.append(fieldPreview);
                
                // Store field data
                fieldElement.data('field-data', fieldData);
                
                return fieldElement;
            }
        });
        </script>
        <?php
    }

    /**
     * Submissions page
     */
    public function submissions_page() {

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        $forms_table = $wpdb->prefix . 'lift_forms';

        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] !== '-1' && isset($_POST['submissions']) && is_array($_POST['submissions'])) {
            if (wp_verify_nonce($_POST['_wpnonce'], 'bulk_action_submissions')) {
                $submission_ids = array_map('intval', $_POST['submissions']);
                $bulk_action = sanitize_text_field($_POST['action']);
                $affected_count = 0;

                switch ($bulk_action) {
                    case 'bulk_delete':
                        foreach ($submission_ids as $submission_id) {
                            $wpdb->delete($submissions_table, array('id' => $submission_id), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d submission deleted successfully.', '%d submissions deleted successfully.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;

                    case 'bulk_mark_read':
                        foreach ($submission_ids as $submission_id) {
                            $wpdb->update($submissions_table, array('status' => 'read'), array('id' => $submission_id), array('%s'), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d submission marked as read.', '%d submissions marked as read.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;

                    case 'bulk_mark_unread':
                        foreach ($submission_ids as $submission_id) {
                            $wpdb->update($submissions_table, array('status' => 'unread'), array('id' => $submission_id), array('%s'), array('%d'));
                            $affected_count++;
                        }
                        echo '<div class="notice notice-success"><p>' . 
                             sprintf(_n('%d submission marked as unread.', '%d submissions marked as unread.', $affected_count, 'lift-docs-system'), $affected_count) . 
                             '</p></div>';
                        break;
                }
            }
        }

        // Get search and filter parameters
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $document_id = isset($_GET['document_id']) ? intval($_GET['document_id']) : 0;
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        // Get forms for filter
        $forms = $wpdb->get_results("SELECT id, name FROM $forms_table ORDER BY name");
        
        // Build query with enhanced filters
        $where = '1=1';
        $params = array();

        if ($form_id) {
            $where .= ' AND form_id = %d';
            $params[] = $form_id;
        }

        if ($document_id) {
            $where .= ' AND form_data LIKE %s';
            $params[] = '%"_document_id":' . $document_id . '%';
        }

        if ($status_filter) {
            $where .= ' AND status = %s';
            $params[] = $status_filter;
        }

        if (!empty($date_from)) {
            $where .= ' AND DATE(submitted_at) >= %s';
            $params[] = $date_from;
        }

        if (!empty($date_to)) {
            $where .= ' AND DATE(submitted_at) <= %s';
            $params[] = $date_to;
        }

        // Build and execute query
        if (!empty($params)) {
            $query = $wpdb->prepare(
                "SELECT * FROM $submissions_table WHERE $where ORDER BY submitted_at DESC",
                $params
            );
        } else {
            $query = "SELECT * FROM $submissions_table WHERE $where ORDER BY submitted_at DESC";
        }

        $submissions = $wpdb->get_results($query);

        // Check for database errors
        if ($wpdb->last_error) {
            // If there's an error, use empty array
            $submissions = array();
        }
        // Manually add form names and user info for all submissions
        $forms_table = $wpdb->prefix . 'lift_forms';
        foreach ($submissions as &$submission) {
            // Get form name
            if ($submission->form_id) {
                $form = $wpdb->get_row($wpdb->prepare("SELECT name FROM $forms_table WHERE id = %d", $submission->form_id));
                $submission->form_name = $form ? $form->name : __('Unknown Form', 'lift-docs-system');
            } else {
                $submission->form_name = __('Unknown Form', 'lift-docs-system');
            }

            // Get user info
            if ($submission->user_id) {
                $user = get_user_by('id', $submission->user_id);
                if ($user) {
                    $submission->user_name = $user->display_name;
                    $submission->user_email = $user->user_email;
                } else {
                    $submission->user_name = __('Unknown User', 'lift-docs-system');
                    $submission->user_email = '';
                }
            } else {
                $submission->user_name = __('Guest', 'lift-docs-system');
                $submission->user_email = '';
            }
        }

        if (!empty($submissions)) {
        }

        // Also check directly from database
        $direct_count = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");

        if ($direct_count > 0) {
            $direct_sample = $wpdb->get_row("SELECT * FROM $submissions_table LIMIT 1");
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Form Submissions', 'lift-docs-system'); ?></h1>

            <!-- Enhanced Search and Filter Form -->
            <div class="lift-submissions-search-form" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
                <h3 style="margin-top: 0;"><?php _e('Search & Filter Submissions', 'lift-docs-system'); ?></h3>
                <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="page" value="lift-forms-submissions">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="form_id" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Form', 'lift-docs-system'); ?>
                            </label>
                            <select name="form_id" id="form_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value=""><?php _e('All Forms', 'lift-docs-system'); ?></option>
                                <?php foreach ($forms as $form): ?>
                                    <option value="<?php echo $form->id; ?>" <?php selected($form_id, $form->id); ?>>
                                        <?php echo esc_html($form->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="status_filter" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Status', 'lift-docs-system'); ?>
                            </label>
                            <select name="status_filter" id="status_filter" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value=""><?php _e('All Statuses', 'lift-docs-system'); ?></option>
                                <option value="unread" <?php selected($status_filter, 'unread'); ?>><?php _e('Unread', 'lift-docs-system'); ?></option>
                                <option value="read" <?php selected($status_filter, 'read'); ?>><?php _e('Read', 'lift-docs-system'); ?></option>
                            </select>
                        </div>



                        <div>
                            <label for="date_from" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Date From', 'lift-docs-system'); ?>
                            </label>
                            <input type="date" 
                                   id="date_from" 
                                   name="date_from" 
                                   value="<?php echo esc_attr($date_from); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        </div>

                        <div>
                            <label for="date_to" style="display: block; margin-bottom: 5px; font-weight: 600;">
                                <?php _e('Date To', 'lift-docs-system'); ?>
                            </label>
                            <input type="date" 
                                   id="date_to" 
                                   name="date_to" 
                                   value="<?php echo esc_attr($date_to); ?>"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;" class="search-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
                            <?php _e('Search Submissions', 'lift-docs-system'); ?>
                        </button>
                        
                        <?php if (!empty($form_id) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <a href="<?php echo admin_url('admin.php?page=lift-forms-submissions'); ?>" class="button">
                            <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
                            <?php _e('Clear Filters', 'lift-docs-system'); ?>
                        </a>
                        <?php endif; ?>
                        
                        <div style="margin-left: auto; color: #666;">
                            <?php printf(__('Found %d submissions', 'lift-docs-system'), count($submissions)); ?>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="lift-empty-state">
                    <h2><?php _e('No Submissions Found', 'lift-docs-system'); ?></h2>
                    <p><?php _e('No submissions match your search criteria. Try adjusting your filters.', 'lift-docs-system'); ?></p>
                </div>
            <?php else: ?>
                <!-- Bulk Actions Form -->
                <form method="post" id="submissions-bulk-action-form">
                    <?php wp_nonce_field('bulk_action_submissions'); ?>
                    
                    <!-- Bulk Actions Top -->
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'lift-docs-system'); ?></label>
                            <select name="action" id="bulk-action-selector-top">
                                <option value="-1"><?php _e('Bulk Actions', 'lift-docs-system'); ?></option>
                                <option value="bulk_delete"><?php _e('Delete', 'lift-docs-system'); ?></option>
                                <option value="bulk_mark_read"><?php _e('Mark as Read', 'lift-docs-system'); ?></option>
                                <option value="bulk_mark_unread"><?php _e('Mark as Unread', 'lift-docs-system'); ?></option>
                            </select>
                            <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'lift-docs-system'); ?>">
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column">
                                    <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'lift-docs-system'); ?></label>
                                    <input id="cb-select-all-1" type="checkbox" />
                                </td>
                                <th><?php _e('Form', 'lift-docs-system'); ?></th>
                                <th><?php _e('Document', 'lift-docs-system'); ?></th>
                                <th><?php _e('Submitted By', 'lift-docs-system'); ?></th>
                                <th><?php _e('Submitted', 'lift-docs-system'); ?></th>
                                <th><?php _e('Status', 'lift-docs-system'); ?></th>
                                <th><?php _e('IP Address', 'lift-docs-system'); ?></th>
                                <th><?php _e('Actions', 'lift-docs-system'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <?php
                                // Get document info from form_data
                                $form_data = json_decode($submission->form_data, true);
                                $document_id = isset($form_data['_document_id']) ? intval($form_data['_document_id']) : 0;
                                $document_title = '';

                                if ($document_id) {
                                    $document_post = get_post($document_id);
                                    $document_title = $document_post ? $document_post->post_title : '';
                                }

                                ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="submissions[]" value="<?php echo $submission->id; ?>" />
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($submission->form_name ? $submission->form_name : __('Unknown Form', 'lift-docs-system')); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($document_title): ?>
                                            <a href="<?php echo admin_url('post.php?post=' . $document_id . '&action=edit'); ?>" target="_blank">
                                                <?php echo esc_html($document_title); ?>
                                            </a>
                                            <br><small>ID: <?php echo $document_id; ?></small>
                                        <?php else: ?>
                                            <span class="no-document">
                                                <?php _e('No Document', 'lift-docs-system'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($submission->user_id && $submission->user_name): ?>
                                            <div class="user-info">
                                                <strong><?php echo esc_html($submission->user_name); ?></strong>
                                                <br><small class="user-email"><?php echo esc_html($submission->user_email); ?></small>
                                                <br><small class="user-id">ID: <?php echo $submission->user_id; ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="guest-user">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <?php _e('Guest User', 'lift-docs-system'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submitted_at)); ?></td>
                                    <td>
                                        <span class="status status-<?php echo esc_attr($submission->status); ?>">
                                            <?php echo ucfirst($submission->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($submission->user_ip); ?></td>
                                    <td>
                                        <button type="button" class="button view-submission" data-id="<?php echo $submission->id; ?>">
                                            <?php _e('View', 'lift-docs-system'); ?>
                                        </button>
                                        <?php if ($submission->user_id): ?>
                                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $submission->user_id); ?>" class="button" target="_blank">
                                                <?php _e('View User', 'lift-docs-system'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Bulk Actions Bottom -->
                    <div class="tablenav bottom">
                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'lift-docs-system'); ?></label>
                            <select name="action" id="bulk-action-selector-bottom">
                                <option value="-1"><?php _e('Bulk Actions', 'lift-docs-system'); ?></option>
                                <option value="bulk_delete"><?php _e('Delete', 'lift-docs-system'); ?></option>
                                <option value="bulk_mark_read"><?php _e('Mark as Read', 'lift-docs-system'); ?></option>
                                <option value="bulk_mark_unread"><?php _e('Mark as Unread', 'lift-docs-system'); ?></option>
                            </select>
                            <input type="submit" id="doaction2" class="button action" value="<?php _e('Apply', 'lift-docs-system'); ?>">
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Submission Detail Modal - LIFT Documents Style -->
        <div id="submission-detail-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2 id="lift-modal-title"><?php _e('Submission Details', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close" onclick="closeLiftModal()">&times;</button>
                </div>

                <div class="lift-modal-body" id="submission-detail-content">
                    <!-- Content will be loaded via AJAX with WordPress table structure -->
                    <div class="submission-loading" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #0073aa;"></i><br><br>
                        <?php _e('Loading submission details...', 'lift-docs-system'); ?>
                    </div>
                </div>

                <div class="lift-modal-footer">
                    <button type="button" class="button button-primary" onclick="closeLiftModal()">
                        <?php _e('Close', 'lift-docs-system'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>

        <style>
        /* Document Details Modal Styles - Imported from admin-modal.css */
        .lift-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lift-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999998;
        }
        .lift-modal-content {
            background: #fff;
            border-radius: 12px;
            border: none;
            max-width: 1400px;
            width: 98%;
            max-height: 95vh;
            overflow: hidden;
            position: relative;
            z-index: 999999;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        .lift-modal-header {
            padding: 30px 40px 25px;
            border-bottom: 1px solid #e8eaed;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px 12px 0 0;
            position: relative;
        }

        .lift-modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #1a2332;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lift-modal-close {
            font-size: 20px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0073aa;
            cursor: pointer;
            padding: 0;
            width: 36px;
            height: 36px;
            transition: all 0.3s ease;
            font-weight: bold;
            background: transparent;
            border: none;
        }

        .lift-modal-close:hover {
            color: #dc3232;
        }

        .lift-modal-body {
            padding: 0;
            max-height: calc(95vh - 180px);
            overflow-y: auto;
            background: #fafbfc;
        }

        .lift-modal-footer {
            padding: 25px 40px;
            border-top: 1px solid #e8eaed;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            text-align: right;
            border-radius: 0 0 12px 12px;
        }

        /* Content Sections */
        .modal-section {
            margin-bottom: 35px;
            padding: 25px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e8eaed;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .modal-section:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .modal-section:last-child {
            margin-bottom: 0;
        }

        .modal-section h3 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            color: #1a2332;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 5px;
        }

        /* Submission specific styles */
        .submission-loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .submission-loading i {
            margin-bottom: 15px;
        }

        /* Submission Details Container */
        .submission-details-container {
            padding: 20px;
        }

        .submission-meta {
            margin-bottom: 30px;
        }

        .submission-data h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #23282d;
        }

        .user-info-detail {
            line-height: 1.6;
        }

        .user-info-detail strong {
            color: #0073aa;
            font-weight: 600;
        }

        .guest-user-detail {
            color: #666;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-field-value,
        .signature-field-value {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f6f7f7;
            border-radius: 4px;
        }

        .file-actions,
        .signature-actions {
            display: flex;
            gap: 10px;
        }

        /* User info styles */
        .user-info {
            line-height: 1.4;
        }
        .user-info strong {
            color: #0073aa;
            font-weight: 600;
        }
        .user-email {
            color: #666;
            font-style: italic;
        }
        .user-id {
            color: #999;
            font-family: 'Courier New', monospace;
        }
        .guest-user {
            color: #999;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .guest-user .dashicons {
            width: 16px;
            height: 16px;
            font-size: 16px;
        }
        .wp-list-table .column-actions {
            width: 120px;
        }
        .wp-list-table .button-small {
            font-size: 11px;
            padding: 3px 8px;
            height: auto;
            line-height: 1.2;
            margin-left: 5px;
        }
        .status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-unread {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-read {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-draft {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-container select {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 3px;
            border: 1px solid #ddd;
            min-width: 90px;
        }
        .status-spinner {
            display: inline-block;
        }
        .form-status-select:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .no-document {
            color: #999;
            font-style: italic;
        }

        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }
        </style>

        <script>
        // Global function for closing modal
        function closeLiftModal() {
            jQuery('#submission-detail-modal').hide();
            jQuery('#lift-modal-backdrop').hide();
            jQuery('body').removeClass('modal-open');
        }

        jQuery(document).ready(function($) {
            // View submission handler
            $('.view-submission').on('click', function() {
                var submissionId = $(this).data('id');

                // Show modal and loading state
                $('#submission-detail-content').html('<div class="submission-loading" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #0073aa;"></i><br><br><?php _e('Loading submission details...', 'lift-docs-system'); ?></div>');
                $('#submission-detail-modal').show();
                $('#lift-modal-backdrop').show();
                $('body').addClass('modal-open');

                // Make AJAX request
                $.post(ajaxurl, {
                    action: 'lift_forms_get_submission',
                    submission_id: submissionId,
                    nonce: '<?php echo wp_create_nonce('lift_forms_get_submission'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#submission-detail-content').html(response.data);
                    } else {
                        $('#submission-detail-content').html('<div class="notice notice-error"><p>' + (response.data || '<?php _e('Error loading submission', 'lift-docs-system'); ?>') + '</p></div>');
                    }
                }).fail(function() {
                    $('#submission-detail-content').html('<div class="notice notice-error"><p><?php _e('Network error occurred while loading submission details.', 'lift-docs-system'); ?></p></div>');
                });
            });

            // Close modal on backdrop click
            $(document).on('click', '#lift-modal-backdrop', function() {
                closeLiftModal();
            });

            // Close modal on ESC key
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27 && $('#submission-detail-modal').is(':visible')) {
                    closeLiftModal();
                }
            });

            // Bulk Actions Functionality for Submissions
            var $bulkForm = $('#submissions-bulk-action-form');
            if ($bulkForm.length > 0) {
                var $checkboxes = $bulkForm.find('input[type="checkbox"][name="submissions[]"]');
                var $selectAllTop = $('#cb-select-all-1');
                var $bulkActionSelectors = $('#bulk-action-selector-top, #bulk-action-selector-bottom');

                // Select All functionality
                $selectAllTop.on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $checkboxes.prop('checked', isChecked);
                    updateBulkActionState();
                });

                // Individual checkbox change
                $checkboxes.on('change', function() {
                    var totalCheckboxes = $checkboxes.length;
                    var checkedCheckboxes = $checkboxes.filter(':checked').length;
                    
                    $selectAllTop.prop('checked', checkedCheckboxes === totalCheckboxes);
                    $selectAllTop.prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
                    
                    updateBulkActionState();
                });

                // Update bulk action button state
                function updateBulkActionState() {
                    var hasSelection = $checkboxes.filter(':checked').length > 0;
                    $bulkForm.find('.button.action').prop('disabled', !hasSelection);
                }

                // Handle bulk action form submission
                $bulkForm.on('submit', function(e) {
                    var selectedAction = $bulkActionSelectors.filter(function() { 
                        return $(this).closest('.bulkactions').find('.button.action:focus').length > 0 || 
                               $(this).val() !== '-1'; 
                    }).first().val();

                    if (selectedAction === '-1' || selectedAction === '') {
                        e.preventDefault();
                        alert('<?php _e("Please select a bulk action.", "lift-docs-system"); ?>');
                        return false;
                    }

                    var checkedCount = $checkboxes.filter(':checked').length;
                    if (checkedCount === 0) {
                        e.preventDefault();
                        alert('<?php _e("Please select at least one submission.", "lift-docs-system"); ?>');
                        return false;
                    }

                    // Confirmation for delete action
                    if (selectedAction === 'bulk_delete') {
                        var confirmMessage = '<?php printf(__("You are about to permanently delete %s submissions. This action cannot be undone. Are you sure?", "lift-docs-system"), "' + checkedCount + '"); ?>';
                        if (!confirm(confirmMessage)) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    // Set the action for whichever button was clicked
                    $(this).find('select[name="action"]').val(selectedAction);
                });

                // Initialize bulk action state
                updateBulkActionState();
            }

            // Enhanced Search Functionality for Submissions
            var searchTimeout;
            var $searchForm = $('.lift-submissions-search-form form');
            var $searchInputs = $searchForm.find('input[type="text"], input[type="date"], select');
            var $searchButton = $searchForm.find('button[type="submit"]');
            
            if ($searchButton.length > 0) {
                var originalButtonText = $searchButton.html();

                // Auto-search functionality with debouncing
                $searchInputs.on('input change', function() {
                    clearTimeout(searchTimeout);
                    
                    // Visual feedback
                    $searchButton.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> <?php _e("Searching...", "lift-docs-system"); ?>');
                    
                    searchTimeout = setTimeout(function() {
                        // Check if there are any search terms
                        var hasSearchTerms = false;
                        $searchInputs.each(function() {
                            if ($(this).val().trim() !== '') {
                                hasSearchTerms = true;
                                return false;
                            }
                        });
                        
                        if (hasSearchTerms) {
                            // Submit form automatically after delay
                            $searchForm.submit();
                        } else {
                            // Reset button state
                            $searchButton.prop('disabled', false).html(originalButtonText);
                        }
                    }, 1000); // 1 second delay for debouncing
                });

                // Highlight search terms in table if any active search
                function highlightSearchTerms() {
                    var searchTerms = [];
                    
                    // Collect all search terms
                    $searchInputs.filter('input[type="text"]').each(function() {
                        var term = $(this).val().trim();
                        if (term !== '') {
                            searchTerms.push(term);
                        }
                    });
                    
                    if (searchTerms.length > 0) {
                        // Remove existing highlights first
                        $('.wp-list-table .search-highlight').each(function() {
                            var $this = $(this);
                            $this.replaceWith($this.text());
                        });
                        
                        // Add new highlights - but only to text nodes, not inside HTML tags
                        $('.wp-list-table tbody td').each(function() {
                            var $cell = $(this);
                            
                            // Skip cells with complex HTML (like action buttons)
                            if ($cell.find('a, button, select, input').length > 0) {
                                return;
                            }
                            
                            // Only highlight simple text content
                            $cell.contents().filter(function() {
                                return this.nodeType === 3; // Text node
                            }).each(function() {
                                var textNode = this;
                                var text = textNode.textContent;
                                var highlightedText = text;
                                
                                searchTerms.forEach(function(term) {
                                    var regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                                    highlightedText = highlightedText.replace(regex, '<span class="search-highlight">$1</span>');
                                });
                                
                                if (highlightedText !== text) {
                                    $(textNode).replaceWith(highlightedText);
                                }
                            });
                        });
                    }
                }
                
                // Apply highlighting on page load if there are search terms
                var hasActiveSearch = false;
                $searchInputs.each(function() {
                    if ($(this).val().trim() !== '') {
                        hasActiveSearch = true;
                        return false;
                    }
                });
                
                if (hasActiveSearch) {
                    setTimeout(highlightSearchTerms, 100); // Small delay to ensure DOM is ready
                }
            }
        });
        </script>

        <style type="text/css">
        /* Submissions Search Form Styles */
        .lift-submissions-search-form {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }

        .lift-submissions-search-form h3 {
            margin-top: 0;
            color: #0073aa;
            font-size: 18px;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .lift-submissions-search-form input[type="text"],
        .lift-submissions-search-form input[type="date"],
        .lift-submissions-search-form select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .lift-submissions-search-form input[type="text"]:focus,
        .lift-submissions-search-form input[type="date"]:focus,
        .lift-submissions-search-form select:focus {
            border-color: #0073aa;
            box-shadow: 0 0 4px rgba(0, 115, 170, 0.3);
            outline: none;
        }

        .lift-submissions-search-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .lift-submissions-search-form .search-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Bulk Actions Styles for Submissions */
        .tablenav .bulkactions select {
            margin-right: 5px;
        }

        .tablenav .bulkactions .button.action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Enhanced table appearance for submissions */
        .wp-list-table.striped > tbody > :nth-child(odd) {
            background-color: #fafafa;
        }

        .wp-list-table tr:hover {
            background-color: #f0f8ff;
        }

        /* Search highlight effect */
        .search-highlight {
            background-color: #ffeb3b;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: bold;
        }

        /* Status styling */
        .status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-unread {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-read {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* User info styling */
        .user-info {
            line-height: 1.4;
        }

        .user-info strong {
            color: #0073aa;
            font-weight: 600;
        }

        .user-email {
            color: #666;
            font-style: italic;
        }

        .user-id {
            color: #999;
            font-family: 'Courier New', monospace;
        }

        .guest-user {
            color: #999;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .guest-user .dashicons {
            width: 16px;
            height: 16px;
            font-size: 16px;
        }

        .no-document {
            color: #999;
            font-style: italic;
        }

        /* Rotation animation for search loading */
        @keyframes rotation {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 782px) {
            .lift-submissions-search-form > form > div:first-child {
                grid-template-columns: 1fr;
            }
            
            .lift-submissions-search-form .search-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .lift-submissions-search-form .search-results-count {
                margin-left: 0;
                text-align: center;
                order: -1;
                margin-bottom: 10px;
            }

            .tablenav .bulkactions {
                margin-bottom: 10px;
            }

            .tablenav .bulkactions select,
            .tablenav .bulkactions .button {
                margin-bottom: 5px;
            }

            .wp-list-table .column-actions {
                width: auto;
            }
        }
        </style>
        <?php
    }

    /**
     * Check if user has already submitted a form for a specific document
     */
    public function user_has_submitted_form($user_id, $form_id, $document_id = null) {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';

        $sql = "SELECT id FROM $submissions_table WHERE form_id = %d AND user_id = %d";
        $params = array($form_id, $user_id);

        // If document_id is provided, check for document-specific submission
        if ($document_id) {
            // Use LIKE for safer JSON search - compatible with more MySQL versions
            $sql .= " AND form_data LIKE %s";
            $params[] = '%"_document_id":' . $document_id . '%';
        }

        $sql .= " LIMIT 1";

        $existing = $wpdb->get_var($wpdb->prepare($sql, $params));

        return !empty($existing);
    }

    /**
     * Get user's existing submission for a form and document
     */
    public function get_user_submission($user_id, $form_id, $document_id = null) {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';

        $sql = "SELECT * FROM $submissions_table WHERE form_id = %d AND user_id = %d";
        $params = array($form_id, $user_id);

        if ($document_id) {
            // Use LIKE for safer JSON search - compatible with more MySQL versions
            $sql .= " AND form_data LIKE %s";
            $params[] = '%"_document_id":' . $document_id . '%';
        }

        $sql .= " ORDER BY submitted_at DESC LIMIT 1";

        return $wpdb->get_row($wpdb->prepare($sql, $params));
    }

    /**
     * AJAX get form
     */
    public function ajax_get_form() {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'lift_forms_nonce') && !wp_verify_nonce($nonce, 'lift_form_builder_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id']);

        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'lift-docs-system'));
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));

        if (!$form) {
            wp_send_json_error(__('Form not found', 'lift-docs-system'));
        }
        // Clean form_fields if it contains invalid characters
        if (!empty($form->form_fields)) {
            $form->form_fields = trim($form->form_fields);

            // Test if it's valid JSON
            $test_decode = json_decode($form->form_fields, true);
            if (json_last_error() !== JSON_ERROR_NONE) {

                // Try to fix common issues
                $fixed_json = $form->form_fields;

                // Remove problematic characters
                $fixed_json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fixed_json);

                // Try to fix malformed JSON (common issues)
                $fixed_json = preg_replace('/,\s*}/', '}', $fixed_json); // Remove trailing commas
                $fixed_json = preg_replace('/,\s*]/', ']', $fixed_json); // Remove trailing commas

                // Test again
                $test_decode = json_decode($fixed_json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $form->form_fields = $fixed_json;
                } else {
                    // If still broken, set to empty array
                    $form->form_fields = '[]';
                }
            }
        }

        // Parse settings to extract header and footer
        $settings_data = array();
        $form_header = '';
        $form_footer = '';
        
        if (!empty($form->settings)) {
            $settings_data = json_decode($form->settings, true);
            if (is_array($settings_data)) {
                $form_header = $settings_data['form_header'] ?? '';
                $form_footer = $settings_data['form_footer'] ?? '';
            }
        }
        
        // Add header and footer to form object
        $form->form_header = $form_header;
        $form->form_footer = $form_footer;

        wp_send_json_success($form);
    }

    /**
     * AJAX save form
     */
    public function ajax_save_form() {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'lift_forms_nonce') && !wp_verify_nonce($nonce, 'lift_form_builder_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $fields = $_POST['fields'] ?? ''; // JSON data
        $settings = $_POST['settings'] ?? '{}'; // JSON data
        
        // Handle form header and footer content
        $form_header = wp_kses_post($_POST['form_header'] ?? '');
        $form_footer = wp_kses_post($_POST['form_footer'] ?? '');

        // Ensure fields is a string
        if (!is_string($fields)) {
            $fields = '';
        }

        // Ensure settings is a string, then decode and add header/footer
        if (!is_string($settings)) {
            $settings = '{}';
        }
        
        // Parse settings and add header/footer
        $settings_data = json_decode($settings, true);
        if (!is_array($settings_data)) {
            $settings_data = array();
        }
        
        // Add form header and footer to settings
        $settings_data['form_header'] = $form_header;
        $settings_data['form_footer'] = $form_footer;
        
        // Re-encode settings
        $settings = json_encode($settings_data);

        // Enhanced form name validation
        if (empty($name)) {
            wp_send_json_error(__('Form name is required', 'lift-docs-system'));
        }

        // Check minimum length
        if (strlen($name) < 3) {
            wp_send_json_error(__('Form name must be at least 3 characters long', 'lift-docs-system'));
        }

        // Check maximum length
        if (strlen($name) > 255) {
            wp_send_json_error(__('Form name is too long (maximum 255 characters)', 'lift-docs-system'));
        }

        // Check for valid characters
        // if (!preg_match('/^[a-zA-Z0-9\s\-_.()]+$/', $name)) {
        //     wp_send_json_error(__('Form name contains invalid characters. Please use only letters, numbers, spaces, and basic punctuation', 'lift-docs-system'));
        // }

        // Check for duplicate form names (excluding current form if editing)
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';

        $duplicate_check_sql = "SELECT id FROM $forms_table WHERE name = %s";
        $duplicate_params = array($name);

        if ($form_id) {
            $duplicate_check_sql .= " AND id != %d";
            $duplicate_params[] = $form_id;
        }

        $existing_form = $wpdb->get_var($wpdb->prepare($duplicate_check_sql, $duplicate_params));
        if ($existing_form) {
            wp_send_json_error(__('A form with this name already exists. Please choose a different name', 'lift-docs-system'));
        }

        // Validate fields data - handle new hierarchical structure
        if (empty($fields) || $fields === '[]' || $fields === 'null' || $fields === 'undefined') {
            // For new forms (no form_id), allow saving with empty fields initially
            if (empty($form_id)) {
                $fields = '[]'; // Ensure it's a valid empty array
            } else {
                wp_send_json_error(__('Form must have at least one field', 'lift-docs-system'));
            }
        }

        // Enhanced JSON cleaning and validation
        $fields = trim($fields);
        // Remove BOM if present
        if (substr($fields, 0, 3) === "\xEF\xBB\xBF") {
            $fields = substr($fields, 3);
        }

        // Remove any non-printable characters except newlines and tabs
        $fields = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '', $fields);

        // Clean up common JSON issues
        if (!empty($fields)) {
            // Fix trailing commas in objects
            $fields = preg_replace('/,\s*}/', '}', $fields);
            // Fix trailing commas in arrays
            $fields = preg_replace('/,\s*]/', ']', $fields);
            // Fix multiple consecutive commas
            $fields = preg_replace('/,+/', ',', $fields);
            // Fix missing quotes around property names (basic fix)
            $fields = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $fields);
        }
        // Test JSON validity with better error reporting
        $fields_array = json_decode($fields, true);
        $json_error = json_last_error();

        if ($json_error !== JSON_ERROR_NONE) {
            $json_error_msg = json_last_error_msg();

            // Try to identify the specific issue
            $error_details = '';
            switch ($json_error) {
                case JSON_ERROR_SYNTAX:
                    $error_details = 'Syntax error in JSON. Check for missing quotes, commas, or brackets.';
                    break;
                case JSON_ERROR_UTF8:
                    $error_details = 'Invalid UTF-8 characters in JSON.';
                    break;
                case JSON_ERROR_DEPTH:
                    $error_details = 'JSON is too deeply nested.';
                    break;
                default:
                    $error_details = 'Unknown JSON error.';
            }

            wp_send_json_error(__('Invalid fields data format: ' . $json_error_msg . ' - ' . $error_details, 'lift-docs-system'));
        }

        if (empty($fields_array) || !is_array($fields_array)) {
            // For new forms, allow empty fields initially
            if (empty($form_id) && (empty($fields_array) || $fields_array === [])) {
                $fields_array = []; // Ensure it's a proper empty array
            } else {
                wp_send_json_error(__('Fields data is empty or invalid', 'lift-docs-system'));
            }
        }

        // Handle both old flat array structure and new hierarchical structure
        if (!empty($fields_array)) {
            $is_valid_data = false;

            if (isset($fields_array[0]) && is_array($fields_array[0])) {
                // Old flat array structure - each element is a field
                $is_valid_data = true;
            } elseif (isset($fields_array['type'])) {
                // New hierarchical structure with 'type' property
                $is_valid_data = true;
            } elseif (isset($fields_array['fields']) || isset($fields_array['layout'])) {
                // New structure with fields/layout properties
                $is_valid_data = true;
            }

            if (!$is_valid_data) {
                wp_send_json_error(__('Fields data structure is invalid', 'lift-docs-system'));
            }
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';

        $data = array(
            'name' => $name,
            'description' => $description,
            'form_fields' => $fields,
            'settings' => $settings,
            'created_by' => get_current_user_id()
        );

        if ($form_id) {
            unset($data['created_by']); // Don't update creator
            $result = $wpdb->update($forms_table, $data, array('id' => $form_id));
            $saved_id = $form_id;
        } else {
            $result = $wpdb->insert($forms_table, $data);
            $saved_id = $wpdb->insert_id;
        }

        if ($result !== false) {
            wp_send_json_success(array(
                'form_id' => $saved_id,
                'message' => __('Form saved successfully!', 'lift-docs-system')
            ));
        } else {
            wp_send_json_error(__('Failed to save form', 'lift-docs-system'));
        }
    }

    /**
     * AJAX delete form
     */
    public function ajax_delete_form() {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'lift_forms_nonce') && !wp_verify_nonce($nonce, 'lift_form_builder_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id']);

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';

        // Delete submissions first
        $wpdb->delete($submissions_table, array('form_id' => $form_id), array('%d'));

        // Delete form
        $result = $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));

        if ($result !== false) {
            wp_send_json_success(__('Form deleted successfully!', 'lift-docs-system'));
        } else {
            wp_send_json_error(__('Failed to delete form', 'lift-docs-system'));
        }
    }

    /**
     * AJAX submit form
     */
    public function ajax_submit_form() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_submit_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id']);
        $document_id = intval($_POST['document_id'] ?? 0);
        $form_data = $_POST['form_fields'] ?? $_POST['form_data'] ?? array();
        $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] == '1';
        $is_admin_edit = isset($_POST['is_admin_edit']) && $_POST['is_admin_edit'] == '1';
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $original_user_id = intval($_POST['original_user_id'] ?? 0);
        if (!$form_id) {
            wp_send_json_error(__('Invalid form', 'lift-docs-system'));
        }

        // Get form configuration
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));

        if (!$form) {
            wp_send_json_error(__('Form not found', 'lift-docs-system'));
        }

        // If document_id is provided, verify access
        if ($document_id) {
            $current_user_id = get_current_user_id();

            // Check if document is archived - block all access including admins
            $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
            if ($is_archived === '1' || $is_archived === 1) {
                wp_send_json_error(__('This document has been archived and is no longer accessible', 'lift-docs-system'));
            }

            // Check if user has access to the document (only admin or assigned users)
            if (!current_user_can('manage_options')) {
                // Check if document is assigned to current user
                $assigned_users = get_post_meta($document_id, '_lift_doc_assigned_users', true);

                if (empty($assigned_users) || !is_array($assigned_users) || !in_array($current_user_id, $assigned_users)) {
                    wp_send_json_error(__('You do not have permission to access this document', 'lift-docs-system'));
                }
            }

            // Check document status - only allow submission/editing if status is 'pending' (unless admin edit)
            $document_status = get_post_meta($document_id, '_lift_doc_status', true);
            if (empty($document_status)) {
                $document_status = 'pending';
            }

            if ($document_status !== 'pending' && !$is_admin_edit) {
                $status_messages = array(
                    'processing' => __('Cannot submit form - document is currently being processed', 'lift-docs-system'),
                    'done' => __('Cannot submit form - document has been completed', 'lift-docs-system'),
                    'cancelled' => __('Cannot submit form - document has been cancelled', 'lift-docs-system')
                );
                $message = isset($status_messages[$document_status]) ? $status_messages[$document_status] : __('Cannot submit form - document status does not allow editing', 'lift-docs-system');
                wp_send_json_error($message);
            }

            // Verify form is assigned to document
            $assigned_forms = get_post_meta($document_id, '_lift_doc_assigned_forms', true);
            if (!is_array($assigned_forms) || !in_array($form_id, $assigned_forms)) {
                wp_send_json_error(__('This form is not assigned to the document', 'lift-docs-system'));
            }
        }

        // Validate form data
        $fields = json_decode($form->form_fields, true);
        $validation_errors = $this->validate_form_submission($form_data, $fields);

        if (!empty($validation_errors)) {
            wp_send_json_error(array(
                'message' => __('Please correct the errors below', 'lift-docs-system'),
                'errors' => $validation_errors
            ));
        }

        // Process file uploads
        $processed_data = $this->process_form_uploads($form_data);

        // Add additional context if from document
        if ($document_id) {
            $processed_data['_document_id'] = $document_id;
            $processed_data['_document_title'] = get_the_title($document_id);
            $processed_data['_submitted_by'] = wp_get_current_user()->display_name;
            $processed_data['_user_id'] = get_current_user_id();
        }

        // Get current user ID (for both logged in and guest users)
        $current_user_id = get_current_user_id();
        if ($current_user_id === 0) {
            $current_user_id = null; // Store as NULL for guest users
        }

        // Define submissions table
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';

        // If editing, validate that submission exists and user has permission
        if (($is_edit || $is_admin_edit) && $submission_id) {
            if ($current_user_id === null && !$is_admin_edit) {
                // Guest users cannot edit submissions (unless admin edit)
                wp_send_json_error(__('You must be logged in to edit submissions', 'lift-docs-system'));
            }

            if ($is_admin_edit) {
                // Admin edit: verify admin permission and submission exists
                if (!current_user_can('manage_options')) {
                    wp_send_json_error(__('You do not have admin permission', 'lift-docs-system'));
                }

                $existing_submission = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$submissions_table} WHERE id = %d",
                    $submission_id
                ));

                if (!$existing_submission) {
                    wp_send_json_error(__('Submission not found', 'lift-docs-system'));
                }

            } else {
                // Regular edit: verify ownership
                $existing_submission = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$submissions_table} WHERE id = %d AND user_id = %d",
                    $submission_id,
                    $current_user_id
                ));

                if (!$existing_submission) {
                    wp_send_json_error(__('You do not have permission to edit this submission', 'lift-docs-system'));
                }

            }
        }

        $submission_data = array(
            'form_id' => $form_id,
            'form_data' => json_encode($processed_data),
            'user_id' => $is_admin_edit ? $original_user_id : $current_user_id, // Keep original user for admin edit
            'user_ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        // Add admin edit metadata
        if ($is_admin_edit) {
            $processed_data['_admin_edited_by'] = wp_get_current_user()->display_name;
            $processed_data['_admin_edited_at'] = current_time('mysql');
            $submission_data['form_data'] = json_encode($processed_data);
        }

        if (($is_edit || $is_admin_edit) && $submission_id) {
            // Update existing submission
            $submission_data['updated_at'] = current_time('mysql');
            // Build format array dynamically based on actual data
            $formats = array();
            foreach ($submission_data as $key => $value) {
                switch ($key) {
                    case 'form_id':
                    case 'user_id':
                        $formats[] = '%d';
                        break;
                    case 'form_data':
                    case 'user_ip':
                    case 'user_agent':
                    case 'updated_at':
                        $formats[] = '%s';
                        break;
                    default:
                        $formats[] = '%s';
                }
            }
            // Use simpler WHERE clause - just ID since we already validated ownership above
            $result = $wpdb->update(
                $submissions_table,
                $submission_data,
                array('id' => $submission_id),
                $formats, // dynamic format array
                array('%d') // format for where (id)
            );

            // Log any database errors
            if ($result === false) {
                wp_send_json_error(__('Database error: ' . $wpdb->last_error, 'lift-docs-system'));
            } else if ($result === 0) {
                // Check if submission still exists
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$submissions_table} WHERE id = %d", $submission_id));
                if (!$exists) {
                    wp_send_json_error(__('Submission no longer exists', 'lift-docs-system'));
                } else {
                }
            } else {
            }

            $success_message = $is_admin_edit ? __('Submission updated by admin successfully!', 'lift-docs-system') : __('Form updated successfully!', 'lift-docs-system');
        } else {
            // Insert new submission
            $submission_data['submitted_at'] = current_time('mysql');
            $result = $wpdb->insert($submissions_table, $submission_data);
            $success_message = __('Form submitted successfully!', 'lift-docs-system');
        }

        if ($result !== false && ($result > 0 || !($is_edit || $is_admin_edit))) {
            // Send notification email if configured (only for new submissions, not edits)
            if (!$is_edit && !$is_admin_edit) {
                $this->send_submission_notification($form, $processed_data);
            }

            // If submission is from a document, return redirect URL
            $response_data = array('message' => $success_message);
            if ($document_id) {
                $response_data['redirect_url'] = home_url('/document-dashboard/');
            }

            wp_send_json_success($response_data);
        } else {
            if (($is_edit || $is_admin_edit) && $result === 0) {
                wp_send_json_error(__('No changes were made to the submission', 'lift-docs-system'));
            } else {
                wp_send_json_error(($is_edit || $is_admin_edit) ? __('Failed to update form', 'lift-docs-system') : __('Failed to submit form', 'lift-docs-system'));
            }
        }
    }

    /**
     * Check if a file URL is an image
     */
    private function is_image_file($url) {
        if (empty($url)) {
            return false;
        }

        $file_extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg');

        return in_array(strtolower($file_extension), $image_extensions);
    }

    /**
     * AJAX get submission details
     */
    public function ajax_get_submission() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_get_submission')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'lift-docs-system'));
        }

        $submission_id = intval($_POST['submission_id']);

        if (!$submission_id) {
            wp_send_json_error(__('Invalid submission ID', 'lift-docs-system'));
        }

        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        $forms_table = $wpdb->prefix . 'lift_forms';

        // Get submission with user data and form definition
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, f.name as form_name, f.form_fields, u.display_name as user_name, u.user_email as user_email, u.user_login as user_login
             FROM $submissions_table s
             LEFT JOIN $forms_table f ON s.form_id = f.id
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE s.id = %d",
            $submission_id
        ));

        if (!$submission) {
            wp_send_json_error(__('Submission not found', 'lift-docs-system'));
        }

        // Parse form data
        $form_data = json_decode($submission->form_data, true);
        if (!$form_data) {
            $form_data = array();
        }

        // Parse form definition to get field labels and types
        $form_fields = array();
        $field_map = array(); // Map field ID to field info

        if (!empty($submission->form_fields)) {
            $parsed_form_data = json_decode($submission->form_fields, true);

            if (is_array($parsed_form_data)) {
                // Check if it's the new hierarchical structure with layout
                if (isset($parsed_form_data['layout']) && isset($parsed_form_data['layout']['rows'])) {
                    // New structure - extract fields
                    foreach ($parsed_form_data['layout']['rows'] as $row) {
                        if (isset($row['columns'])) {
                            foreach ($row['columns'] as $column) {
                                if (isset($column['fields'])) {
                                    foreach ($column['fields'] as $field) {
                                        if (isset($field['id'])) {
                                            $field_map[$field['id']] = $field;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif (isset($parsed_form_data[0]) && is_array($parsed_form_data[0])) {
                    // Direct array of fields
                    foreach ($parsed_form_data as $field) {
                        if (isset($field['id'])) {
                            $field_map[$field['id']] = $field;
                        }
                    }
                } else {
                    // Handle simple field structure - use field name as key
                    foreach ($parsed_form_data as $key => $field) {
                        if (is_array($field) && isset($field['name'])) {
                            $field_map[$field['name']] = $field;
                        } elseif (is_array($field) && isset($field['id'])) {
                            $field_map[$field['id']] = $field;
                        }
                    }
                }
            }
        }

        // Also try to match form data keys with field map keys for better field matching
        if (!empty($field_map) && !empty($form_data)) {
            $matched_fields = array();
            foreach ($form_data as $data_key => $data_value) {
                // Skip system fields
                if (strpos($data_key, '_') === 0) continue;
                
                // Try to find matching field in field_map
                foreach ($field_map as $field_key => $field_info) {
                    $field_name = $field_info['name'] ?? $field_info['id'] ?? $field_key;
                    if ($data_key === $field_name || $data_key === $field_key) {
                        $matched_fields[$data_key] = $field_info;
                        break;
                    }
                }
            }
            
            // If we found matches, update field_map to use data keys
            if (!empty($matched_fields)) {
                $field_map = $matched_fields;
            }
        }

        // Build HTML output with simple WordPress structure
        ob_start();
        ?>
        <div class="submission-details-container">
            <div class="submission-meta">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Form:', 'lift-docs-system'); ?></th>
                        <td><strong><?php echo esc_html($submission->form_name); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Submitted:', 'lift-docs-system'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submitted_at)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status:', 'lift-docs-system'); ?></th>
                        <td>
                            <span class="status status-<?php echo esc_attr($submission->status); ?>">
                                <?php echo ucfirst($submission->status); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Submitted By:', 'lift-docs-system'); ?></th>
                        <td>
                            <?php if ($submission->user_id && $submission->user_name): ?>
                                <div class="user-info-detail">
                                    <strong><?php echo esc_html($submission->user_name); ?></strong><br>
                                    <small><?php _e('Email:', 'lift-docs-system'); ?> <?php echo esc_html($submission->user_email); ?></small><br>
                                    <small><?php _e('Username:', 'lift-docs-system'); ?> <?php echo esc_html($submission->user_login); ?></small><br>
                                    <small><?php _e('User ID:', 'lift-docs-system'); ?> <?php echo $submission->user_id; ?></small><br>
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $submission->user_id); ?>" target="_blank" class="button">
                                        <?php _e('View User Profile', 'lift-docs-system'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="guest-user-detail">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php _e('Guest User (Not logged in)', 'lift-docs-system'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('IP Address:', 'lift-docs-system'); ?></th>
                        <td><?php echo esc_html($submission->user_ip); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('User Agent:', 'lift-docs-system'); ?></th>
                        <td><small><?php echo esc_html($submission->user_agent); ?></small></td>
                    </tr>
                </table>
            </div>

            <?php if (isset($form_data['_document_id'])): ?>
            <div class="submission-context">
                <h3><?php _e('Document Context', 'lift-docs-system'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Related Document:', 'lift-docs-system'); ?></th>
                        <td>
                            <strong><?php echo esc_html($form_data['_document_title'] ?? 'Unknown'); ?></strong><br>
                            <small><?php _e('Document ID:', 'lift-docs-system'); ?> <?php echo intval($form_data['_document_id']); ?></small><br>
                            <a href="<?php echo admin_url('post.php?post=' . intval($form_data['_document_id']) . '&action=edit'); ?>" target="_blank" class="button">
                                <?php _e('Edit Document', 'lift-docs-system'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>

            <!-- Form Fields Data -->
            <?php if (!empty($form_data)): ?>
            <div class="submission-fields">
                <h3><?php _e('Form Data', 'lift-docs-system'); ?></h3>
                <table class="form-table">
                    <?php foreach ($form_data as $key => $value): ?>
                        <?php
                        // Skip hidden system fields
                        if (strpos($key, '_') === 0) {
                            continue;
                        }
                        
                        // Skip empty values
                        if (empty($value)) {
                            continue;
                        }
                        
                        // Determine field type based on key/value patterns
                        $is_file = (strpos($key, '_url') !== false) || (is_string($value) && (strpos($value, '/uploads/') !== false || strpos($value, '/wp-content/') !== false));
                        $is_signature = (is_string($value) && strpos($value, '/signatures/') !== false);
                        
                        // Try to get field label from form definition first
                        $field_label = $key;
                        $field_found_in_definition = false;
                        
                        // Remove _url suffix for file fields when searching
                        $search_key = $key;
                        if (strpos($search_key, '_url') !== false) {
                            $search_key = str_replace('_url', '', $search_key);
                        }
                        
                        // Look for field definition by key or ID
                        if (!empty($field_map)) {
                            foreach ($field_map as $field_id => $field_info) {
                                // Check if this field matches by ID, name, or key
                                $field_name = $field_info['name'] ?? '';
                                $field_title = $field_info['title'] ?? $field_info['label'] ?? '';
                                
                                if ($search_key === $field_id || 
                                    $search_key === $field_name || 
                                    $key === $field_id || 
                                    $key === $field_name) {
                                    
                                    // Use title/label from form definition if available
                                    if (!empty($field_title)) {
                                        $field_label = $field_title;
                                        $field_found_in_definition = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // If no field definition found, create a readable label from the key
                        if (!$field_found_in_definition) {
                            // Remove _url suffix for file fields
                            if (strpos($field_label, '_url') !== false) {
                                $field_label = str_replace('_url', '', $field_label);
                            }
                            
                            // Convert underscores to spaces and capitalize words
                            $field_label = str_replace('_', ' ', $field_label);
                            $field_label = str_replace('-', ' ', $field_label);
                            $field_label = ucwords($field_label);
                            
                            // Handle common field name patterns
                            $field_label = str_replace('Fullname', 'Full Name', $field_label);
                            $field_label = str_replace('Firstname', 'First Name', $field_label);
                            $field_label = str_replace('Lastname', 'Last Name', $field_label);
                            $field_label = str_replace('Phonenumber', 'Phone Number', $field_label);
                            $field_label = str_replace('Dateofbirth', 'Date of Birth', $field_label);
                            $field_label = str_replace('Dob', 'Date of Birth', $field_label);
                            $field_label = str_replace('Id', 'ID', $field_label);
                            $field_label = str_replace('Url', 'URL', $field_label);
                            
                            // If field name still looks like a generated ID, provide fallback based on content type
                            if (preg_match('/^Field\s*\d+/', $field_label) || preg_match('/^\d+/', $field_label) || strlen($field_label) > 30) {
                                if ($is_signature) {
                                    $field_label = __('Digital Signature', 'lift-docs-system');
                                } elseif ($is_file) {
                                    $field_label = __('File Upload', 'lift-docs-system');
                                } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                    $field_label = __('Email Address', 'lift-docs-system');
                                } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                                    $field_label = __('Website URL', 'lift-docs-system');
                                } elseif (is_array($value)) {
                                    $field_label = __('Multiple Choice', 'lift-docs-system');
                                } elseif (strlen($value) > 100) {
                                    $field_label = __('Long Text', 'lift-docs-system');
                                } elseif ($value === 'on' || $value === '1' || $value === true || $value === 'true' || $value === 'off' || $value === '0' || $value === false || $value === 'false') {
                                    $field_label = __('Checkbox', 'lift-docs-system');
                                } else {
                                    $field_label = __('Text Input', 'lift-docs-system');
                                }
                            }
                        }
                        ?>
                        <tr>
                            <th><?php echo esc_html($field_label); ?>:</th>
                            <td>
                                <?php if ($is_signature): ?>
                                    <div class="signature-field-value">
                                        <div class="signature-preview">
                                            <img src="<?php echo esc_url($value); ?>" alt="<?php echo esc_attr($field_label); ?>" style="max-width: 400px; max-height: 200px; border: 2px solid #ddd; border-radius: 8px; background: white;" />
                                        </div>
                                        <div class="signature-actions">
                                            <a href="<?php echo esc_url($value); ?>" target="_blank" class="button button-secondary">
                                                <span class="dashicons dashicons-visibility"></span> <?php _e('View Signature', 'lift-docs-system'); ?>
                                            </a>
                                            <a href="<?php echo esc_url($value); ?>" download class="button button-secondary">
                                                <span class="dashicons dashicons-download"></span> <?php _e('Download', 'lift-docs-system'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php elseif ($is_file): ?>
                                    <?php
                                    $file_name = basename($value);
                                    $file_extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                                    $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
                                    ?>
                                    <div class="file-field-value">
                                        <?php if (in_array($file_extension, $image_extensions)): ?>
                                            <div class="image-preview">
                                                <img src="<?php echo esc_url($value); ?>" alt="<?php echo esc_attr($file_name); ?>" style="max-width: 300px; max-height: 300px; border-radius: 8px;" />
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="file-info">
                                            <span class="dashicons dashicons-media-default"></span>
                                            <span><?php echo esc_html($file_name); ?></span>
                                        </div>
                                        
                                        <div class="file-actions">
                                            <a href="<?php echo esc_url($value); ?>" target="_blank" class="button button-secondary">
                                                <span class="dashicons dashicons-visibility"></span> <?php _e('View File', 'lift-docs-system'); ?>
                                            </a>
                                            <a href="<?php echo esc_url($value); ?>" download class="button button-secondary">
                                                <span class="dashicons dashicons-download"></span> <?php _e('Download', 'lift-docs-system'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php elseif (is_array($value)): ?>
                                    <?php echo esc_html(implode(', ', $value)); ?>
                                <?php elseif (filter_var($value, FILTER_VALIDATE_EMAIL)): ?>
                                    <a href="mailto:<?php echo esc_attr($value); ?>"><?php echo esc_html($value); ?></a>
                                <?php elseif (filter_var($value, FILTER_VALIDATE_URL)): ?>
                                    <a href="<?php echo esc_url($value); ?>" target="_blank"><?php echo esc_html($value); ?></a>
                                <?php elseif (strlen($value) > 100): ?>
                                    <div style="white-space: pre-wrap; background: #f9f9f9; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                                        <?php echo esc_html($value); ?>
                                    </div>
                                <?php elseif ($value === 'on' || $value === '1' || $value === true || $value === 'true'): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php _e('Yes', 'lift-docs-system'); ?>
                                <?php elseif ($value === 'off' || $value === '0' || $value === false || $value === 'false'): ?>
                                    <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> <?php _e('No', 'lift-docs-system'); ?>
                                <?php else: ?>
                                    <?php echo esc_html($value); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .submission-details-container {
            max-width: 100%;
        }
        .submission-details-container h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            color: #23282d;
            font-size: 16px;
        }
        .user-info-detail strong {
            color: #0073aa;
        }
        .guest-user-detail {
            color: #666;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .guest-user-detail .dashicons {
            width: 16px;
            height: 16px;
            font-size: 16px;
        }
        .form-table th {
            width: 150px;
            vertical-align: top;
            font-weight: 600;
            color: #23282d;
        }
        .form-table td {
            word-break: break-word;
        }
        
        /* File and Signature Field Styles */
        .file-field-value, .signature-field-value {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .image-preview, .signature-preview {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            text-align: center;
        }
        .image-preview img, .signature-preview img {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 6px;
        }
        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-weight: 500;
        }
        .file-info .dashicons {
            color: #666;
            width: 18px;
            height: 18px;
            font-size: 18px;
        }
        .file-actions, .signature-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .file-actions .button, .signature-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            padding: 6px 12px;
        }
        .file-actions .dashicons, .signature-actions .dashicons {
            width: 16px;
            height: 16px;
            font-size: 16px;
        }
        
        /* Textarea display */
        .submission-details-container textarea {
            width: 100%;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            resize: none;
        }
        
        /* Checkbox display */
        .submission-details-container .dashicons-yes-alt,
        .submission-details-container .dashicons-dismiss {
            width: 18px;
            height: 18px;
            font-size: 18px;
        }
        
        /* Status indicators */
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-read {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-processing {
            background: #cce7ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-table th {
                width: 120px;
                font-size: 14px;
            }
            .file-actions, .signature-actions {
                flex-direction: column;
            }
            .image-preview img, .signature-preview img {
                max-width: 100% !important;
                height: auto !important;
            }
        }
        </style>
        <?php

        $html = ob_get_clean();

        // Mark as read
        $wpdb->update(
            $submissions_table,
            array('status' => 'read'),
            array('id' => $submission_id),
            array('%s'),
            array('%d')
        );

        wp_send_json_success($html);
    }

    /**
     * Render form shortcode
     */
    public function render_form_shortcode($atts) {
        if (isset($_GET['admin_view'])) {
            echo '<script>document.body.style.paddingTop = "60px";</script>';
        }

        $atts = shortcode_atts(array(
            'id' => 0,
            'title' => 'true'
        ), $atts);

        $form_id = intval($atts['id']);
        if (!$form_id) {
            return '<p>' . __('Form ID is required', 'lift-docs-system') . '</p>';
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d AND status = 'active'", $form_id));

        if (!$form) {
            return '<p>' . __('Form not found or inactive', 'lift-docs-system') . '</p>';
        }

        $fields = json_decode($form->form_fields, true);
        if (empty($fields)) {
            return '<p>' . __('This form has no fields configured', 'lift-docs-system') . '</p>';
        }

        // Parse form settings for header and footer
        $form_header = '';
        $form_footer = '';
        if (!empty($form->settings)) {
            $settings = json_decode($form->settings, true);
            if (is_array($settings)) {
                $form_header = $settings['form_header'] ?? '';
                $form_footer = $settings['form_footer'] ?? '';
            }
        }

        // Debug: Check if header/footer exist
        if (isset($_GET['debug']) && current_user_can('manage_options')) {
            echo '<!-- Debug: Form settings: ' . esc_html($form->settings) . ' -->';
            echo '<!-- Debug: Form header: ' . esc_html($form_header) . ' -->';
            echo '<!-- Debug: Form footer: ' . esc_html($form_footer) . ' -->';
        }

        // Check if admin is viewing with submission data
        $submission_data = array();
        $submission_info = null;
        $is_admin_view = isset($_GET['admin_view']) && $_GET['admin_view'] == '1' && current_user_can('manage_options');

        if (isset($_GET['admin_view']) && $_GET['admin_view'] == '1') {
        }

        if ($is_admin_view) {
        }

        if ($is_admin_view && isset($_GET['submission_id'])) {
            $submission_id = intval($_GET['submission_id']);

            if ($submission_id) {
                $submissions_table = $wpdb->prefix . 'lift_form_submissions';
                $submission_info = $wpdb->get_row($wpdb->prepare(
                    "SELECT s.*, u.display_name as user_name, u.user_email as user_email
                     FROM $submissions_table s
                     LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                     WHERE s.id = %d",
                    $submission_id
                ));
                if ($submission_info) {
                    $submission_data = json_decode($submission_info->form_data, true);
                    if (!is_array($submission_data)) {
                        $submission_data = array();
                    }
                } else {
                }
            }
        }

        ob_start();
        ?>
        <div class="document-form-wrapper">
            <?php if ($atts['title'] === 'true'): ?>
                <div class="document-form-title">
                    <div class="title-grid">
                        <!-- Left Column: Form Info -->
                        <div class="form-info-column">
                            <h1><?php echo esc_html($form->name); ?></h1>
                            <?php if ($form->description): ?>
                                <div class="form-description">
                                    <span class="info-label"><?php _e('Description:', 'lift-docs-system'); ?></span>
                                    <span class="info-value"><?php echo esc_html($form->description); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Column: Additional Info (can be extended) -->
                        <div class="status-info-column">
                            <?php if ($is_admin_view && $submission_info): ?>
                                <div class="admin-view-notice" style="background: #e3f2fd; border: 1px solid #0073aa; border-radius: 4px; padding: 10px; margin-bottom: 10px;">
                                    <strong style="color: #0073aa;"><?php _e('Admin View - Viewing Submitted Data', 'lift-docs-system'); ?></strong><br>
                                    <small>
                                        <?php _e('Submitted by:', 'lift-docs-system'); ?> <?php echo esc_html($submission_info->user_name ?: __('Guest', 'lift-docs-system')); ?><br>
                                        <?php _e('Submitted on:', 'lift-docs-system'); ?> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission_info->submitted_at)); ?>
                                    </small>
                                </div>
                            <?php elseif ($is_admin_view): ?>
                                <div class="admin-view-notice" style="background: #fff3cd; border: 1px solid #f39c12; border-radius: 4px; padding: 10px; margin-bottom: 10px;">
                                    <strong style="color: #856404;"><?php _e('Admin View - No Submission Data', 'lift-docs-system'); ?></strong><br>
                                    <small><?php _e('This form has not been submitted yet', 'lift-docs-system'); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-content-section">
                <div class="lift-form-container" data-form-id="<?php echo $form_id; ?>">
                    <?php if ($is_admin_view): ?>
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                            <small>
                                Admin View: <?php echo $is_admin_view ? 'Yes' : 'No'; ?><br>
                                Submission ID: <?php echo isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 'None'; ?><br>
                                Form ID: <?php echo $form_id; ?><br>
                                Submission Info Found: <?php echo $submission_info ? 'Yes' : 'No'; ?><br>
                                Submission Data Count: <?php echo count($submission_data); ?><br>
                                Current User Can Manage Options: <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?><br>
                                <?php if (!empty($submission_data)): ?>
                                    <strong>Submission Data:</strong><br>
                                    <div style="font-size: 11px; max-height: 200px; overflow-y: auto; background: white; padding: 5px; border: 1px solid #ddd;">
                                        <?php foreach ($submission_data as $key => $value): ?>
                                            <div><strong><?php echo esc_html($key); ?>:</strong> <?php echo esc_html(is_array($value) ? implode(', ', $value) : $value); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($submission_info): ?>
                                    <strong>Raw Form Data:</strong><br>
                                    <pre style="font-size: 11px; max-height: 100px; overflow-y: auto; background: white; padding: 5px;"><?php echo htmlspecialchars($submission_info->form_data); ?></pre>
                                <?php endif; ?>
                            </small>
                        </div>

                        <!-- Admin view - show read-only form with submitted data -->
                        <div class="lift-form admin-readonly-form">
                            <?php if (!empty($form_header)): ?>
                                <div class="form-header-content">
                                    <?php echo wp_kses_post($form_header); ?>
                                </div>
                            <?php endif; ?>

                            <?php echo $this->render_form_fields($fields, $submission_data, true); ?>

                            <?php if (!empty($form_footer)): ?>
                                <div class="form-footer-content">
                                    <?php echo wp_kses_post($form_footer); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($submission_info): ?>
                                <div class="admin-form-actions" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                                    <p><strong><?php _e('Form Actions:', 'lift-docs-system'); ?></strong></p>
                                    <a href="<?php echo remove_query_arg(array('admin_view', 'submission_id')); ?>"
                                       class="button" style="margin-right: 10px;">
                                        <?php _e('View Empty Form', 'lift-docs-system'); ?>
                                    </a>
                                    <button type="button" onclick="window.print()" class="button">
                                        <?php _e('Print Submission', 'lift-docs-system'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Normal user view - interactive form -->
                        <form class="lift-form" id="lift-form-<?php echo $form_id; ?>">
                            <?php wp_nonce_field('lift_forms_submit_nonce', 'lift_forms_nonce'); ?>
                            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">

                            <?php if (!empty($form_header)): ?>
                                <div class="form-header-content">
                                    <?php echo wp_kses_post($form_header); ?>
                                </div>
                            <?php endif; ?>

                            <?php echo $this->render_form_fields($fields); ?>

                            <?php if (!empty($form_footer)): ?>
                                <div class="form-footer-content">
                                    <?php echo wp_kses_post($form_footer); ?>
                                </div>
                            <?php endif; ?>

                            <div class="lift-form-submit">
                                <button type="submit" class="lift-form-submit-btn btn button-primary">
                                    <span class="btn-text"><?php _e('Submit Form', 'lift-docs-system'); ?></span>
                                    <span class="btn-spinner" style="display: none;">
                                        <span class="spinner"></span>
                                        <?php _e('Submitting...', 'lift-docs-system'); ?>
                                    </span>
                                </button>
                            </div>

                            <div class="lift-form-messages">
                                <div class="form-error" style="display: none;"></div>
                                <div class="form-success" style="display: none;"></div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper methods
     */
    private function get_total_submissions() {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        return $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
    }

    private function get_unread_submissions() {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        return $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status = 'unread'");
    }

    private function get_form_submissions_count($form_id) {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE form_id = %d", $form_id));
    }

    private function render_form_fields($fields, $submission_data = array(), $readonly = false) {
        $html = '';

        if ($readonly) {
        }

        // Check if fields is in new layout structure
        if (isset($fields['layout']) && isset($fields['layout']['rows'])) {
            // New hierarchical structure - extract fields from layout
            foreach ($fields['layout']['rows'] as $row) {
                if (isset($row['columns'])) {
                    foreach ($row['columns'] as $column) {
                        if (isset($column['fields'])) {
                            foreach ($column['fields'] as $field) {
                                $html .= $this->render_single_field($field, $submission_data, $readonly);
                            }
                        }
                    }
                }
            }
        } else {
            // Old structure or direct array of fields
            foreach ($fields as $field) {
                $html .= $this->render_single_field($field, $submission_data, $readonly);
            }
        }

        return $html;
    }

    private function render_single_field($field, $submission_data = array(), $readonly = false) {
        $type = $field['type'] ?? 'text';
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        $required_asterisk = $required ? ' <span class="required">*</span>' : '';
        $field_name = $field['name'] ?? '';
        $field_value = $submission_data[$field_name] ?? '';

        if ($readonly) {
        }

        // Add readonly class and attribute for admin view
        $readonly_class = $readonly ? ' readonly-field' : '';
        $readonly_attr = $readonly ? ' readonly disabled' : '';

        $html = '<div class="lift-form-field lift-field-' . esc_attr($type) . $readonly_class . '">';

        switch ($type) {
            case 'text':
            case 'email':
            case 'number':
            case 'date':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                if ($readonly && !empty($field_value)) {
                    $html .= '<div class="readonly-value">' . esc_html($field_value) . '</div>';
                    $html .= '<input type="hidden" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field_value) . '">';
                } else {
                    $html .= '<input type="' . esc_attr($type) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="form-control" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" value="' . esc_attr($field_value) . '" ' . $required . $readonly_attr . '>';
                }
                break;

            case 'textarea':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                if ($readonly && !empty($field_value)) {
                    $html .= '<div class="readonly-value textarea-readonly">' . nl2br(esc_html($field_value)) . '</div>';
                    $html .= '<input type="hidden" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field_value) . '">';
                } else {
                    $html .= '<textarea id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="form-control" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" ' . $required . $readonly_attr . '>' . esc_textarea($field_value) . '</textarea>';
                }
                break;

            case 'select':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                if ($readonly && !empty($field_value)) {
                    // Find and display the selected option label
                    $selected_label = $field_value;
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $option) {
                            if ($option['value'] === $field_value) {
                                $selected_label = $option['label'];
                                break;
                            }
                        }
                    }
                    $html .= '<div class="readonly-value">' . esc_html($selected_label) . '</div>';
                    $html .= '<input type="hidden" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field_value) . '">';
                } else {
                    $html .= '<select id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="form-control" ' . $required . $readonly_attr . '>';
                    $html .= '<option value="">' . __('Please select...', 'lift-docs-system') . '</option>';
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $option) {
                            $selected = ($option['value'] === $field_value) ? ' selected' : '';
                            $html .= '<option value="' . esc_attr($option['value']) . '"' . $selected . '>' . esc_html($option['label']) . '</option>';
                        }
                    }
                    $html .= '</select>';
                }
                break;

            case 'radio':
                $html .= '<fieldset class="radio-group">';
                $html .= '<legend>' . esc_html($field['label']) . $required_asterisk . '</legend>';
                if ($readonly && !empty($field_value)) {
                    // Find and display the selected option label
                    $selected_label = $field_value;
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $option) {
                            if ($option['value'] === $field_value) {
                                $selected_label = $option['label'];
                                break;
                            }
                        }
                    }
                    $html .= '<div class="readonly-value">' . esc_html($selected_label) . '</div>';
                    $html .= '<input type="hidden" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field_value) . '">';
                } else {
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $i => $option) {
                            $checked = ($option['value'] === $field_value) ? ' checked' : '';
                            $html .= '<div class="radio-option">';
                            $html .= '<input type="radio" id="' . esc_attr($field['id'] . '_' . $i) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($option['value']) . '" class="form-control"' . $checked . ' ' . $required . $readonly_attr . '>';
                            $html .= '<label for="' . esc_attr($field['id'] . '_' . $i) . '">' . esc_html($option['label']) . '</label>';
                            $html .= '</div>';
                        }
                    }
                }
                $html .= '</fieldset>';
                break;

            case 'checkbox':
                $html .= '<fieldset class="checkbox-group">';
                $html .= '<legend>' . esc_html($field['label']) . $required_asterisk . '</legend>';
                if ($readonly && !empty($field_value)) {
                    // Handle checkbox array values
                    $selected_values = is_array($field_value) ? $field_value : array($field_value);
                    $selected_labels = array();
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $option) {
                            if (in_array($option['value'], $selected_values)) {
                                $selected_labels[] = $option['label'];
                            }
                        }
                    }
                    $html .= '<div class="readonly-value">' . esc_html(implode(', ', $selected_labels)) . '</div>';
                    foreach ($selected_values as $value) {
                        $html .= '<input type="hidden" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($value) . '">';
                    }
                } else {
                    $selected_values = is_array($field_value) ? $field_value : ($field_value ? array($field_value) : array());
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $i => $option) {
                            $checked = in_array($option['value'], $selected_values) ? ' checked' : '';
                            $html .= '<div class="checkbox-field">';
                            $html .= '<input type="checkbox" id="' . esc_attr($field['id'] . '_' . $i) . '" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($option['value']) . '" class="form-control"' . $checked . $readonly_attr . '>';
                            $html .= '<label for="' . esc_attr($field['id'] . '_' . $i) . '">' . esc_html($option['label']) . '</label>';
                            $html .= '</div>';
                        }
                    }
                }
                $html .= '</fieldset>';
                break;

            case 'file':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                if ($readonly) {
                    $file_url = $submission_data[$field_name . '_url'] ?? '';
                    if (!empty($file_url)) {
                        $file_name = basename(parse_url($file_url, PHP_URL_PATH));
                        $html .= '<div class="readonly-value file-readonly">';
                        $html .= '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a>';
                        $html .= '</div>';
                    } else {
                        $html .= '<div class="readonly-value">' . __('No file uploaded', 'lift-docs-system') . '</div>';
                    }
                } else {
                    $accept = !empty($field['accept']) ? ' accept="' . esc_attr($field['accept']) . '"' : ' accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx"';
                    $html .= '<input type="file" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="form-control" ' . $required . $accept . $readonly_attr . '>';
                }
                break;

            case 'signature':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                if ($readonly && !empty($field_value)) {
                    $html .= '<div class="readonly-value signature-readonly">';
                    $html .= '<img src="' . esc_url($field_value) . '" alt="' . __('Digital Signature', 'lift-docs-system') . '" style="max-width: 200px; border: 1px solid #ddd; padding: 5px;">';
                    $html .= '</div>';
                } else {
                    $html .= '<input type="hidden" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="form-control" value="' . esc_attr($field_value) . '" ' . $required . '>';
                }
                break;

            case 'html':
                $html .= wp_kses_post($field['content'] ?? '');
                break;
        }

        if (!empty($field['description'])) {
            $html .= '<small class="field-description">' . esc_html($field['description']) . '</small>';
        }

        $html .= '</div>';

        return $html;
    }

    private function validate_form_submission($form_data, $fields) {
        $errors = array();

        foreach ($fields as $field) {
            $field_name = $field['name'] ?? '';
            $field_type = $field['type'] ?? 'text';
            $field_value = $form_data[$field_name] ?? '';
            $is_required = isset($field['required']) && $field['required'];

            // Check required fields
            if ($is_required && empty($field_value)) {
                // For file uploads, also check for the _url version
                if ($field_type === 'file') {
                    $file_url_value = $form_data[$field_name . '_url'] ?? '';
                    if (empty($file_url_value)) {
                        $errors[$field_name] = sprintf(__('%s is required', 'lift-docs-system'), $field['label'] ?? $field_name);
                        continue;
                    }
                } else {
                    $errors[$field_name] = sprintf(__('%s is required', 'lift-docs-system'), $field['label'] ?? $field_name);
                    continue;
                }
            }

            // Skip validation if field is empty and not required
            if (empty($field_value)) {
                continue;
            }

            // Type-specific validation
            switch ($field_type) {
                case 'email':
                    if (!is_email($field_value)) {
                        $errors[$field_name] = __('Please enter a valid email address', 'lift-docs-system');
                    }
                    break;

                case 'number':
                    if (!is_numeric($field_value)) {
                        $errors[$field_name] = __('Please enter a valid number', 'lift-docs-system');
                    }
                    break;

                case 'date':
                    if (!strtotime($field_value)) {
                        $errors[$field_name] = __('Please enter a valid date', 'lift-docs-system');
                    }
                    break;

                case 'signature':
                    // Validate signature URL format
                    if (!empty($field_value) && strpos($field_value, '/signatures/') === false) {
                        $errors[$field_name] = __('Invalid signature format', 'lift-docs-system');
                    }
                    break;

                case 'file':
                    // Validate file URL if present
                    $file_url = $form_data[$field_name . '_url'] ?? '';
                    if (!empty($file_url) && !filter_var($file_url, FILTER_VALIDATE_URL)) {
                        $errors[$field_name] = __('Invalid file upload', 'lift-docs-system');
                    }
                    break;
            }
        }

        return $errors;
    }

    private function process_form_uploads($form_data) {
        $processed_data = array();

        foreach ($form_data as $key => $value) {
            // Handle file upload URLs (these come from AJAX uploads)
            if (strpos($key, '_url') !== false && !empty($value)) {
                // File was uploaded via AJAX, URL is already stored
                $processed_data[$key] = sanitize_url($value);

                // Also store the original field name without _url suffix
                $original_key = str_replace('_url', '', $key);
                $processed_data[$original_key] = basename($value);
            }
            // Handle signature data (already processed via AJAX)
            elseif (strpos($value, '/signatures/') !== false) {
                // Signature URL from AJAX save
                $processed_data[$key] = sanitize_url($value);
            }
            // Handle regular form data
            else {
                if (is_array($value)) {
                    $processed_data[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $processed_data[$key] = sanitize_text_field($value);
                }
            }
        }

        return $processed_data;
    }

    private function send_submission_notification($form, $data) {
        // Send email notification to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New submission for form: %s', 'lift-docs-system'), $form->name);

        $message = __('A new form submission has been received.', 'lift-docs-system') . "\n\n";
        $message .= __('Form:', 'lift-docs-system') . ' ' . $form->name . "\n";
        $message .= __('Submitted at:', 'lift-docs-system') . ' ' . current_time('mysql') . "\n\n";

        $message .= __('Data:', 'lift-docs-system') . "\n";
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $message .= $key . ': ' . $value . "\n";
        }

        wp_mail($admin_email, $subject, $message);
    }

    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
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

    /**
     * AJAX handler for file uploads
     */
    public function ajax_upload_file() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_submit_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['file'])) {
            wp_send_json_error(__('No file uploaded', 'lift-docs-system'));
        }

        $file = $_FILES['file'];

        // Basic validation
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_types = array(
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        if ($file['size'] > $max_size) {
            wp_send_json_error(__('File is too large. Maximum size is 5MB.', 'lift-docs-system'));
        }

        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type.', 'lift-docs-system'));
        }

        // Handle the upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => array($this, 'unique_filename_callback')
        );

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_send_json_error($uploaded_file['error']);
        }

        // Return success with file URL
        wp_send_json_success(array(
            'url' => $uploaded_file['url'],
            'file' => $uploaded_file['file'],
            'type' => $uploaded_file['type']
        ));
    }

    /**
     * AJAX handler for saving signatures
     */
    public function ajax_save_signature() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_submit_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        if (!isset($_POST['signature']) || !isset($_POST['field_id'])) {
            wp_send_json_error(__('Missing signature data', 'lift-docs-system'));
        }

        $signature_data = $_POST['signature'];
        $field_id = sanitize_text_field($_POST['field_id']);

        // Validate base64 data
        if (strpos($signature_data, 'data:image/png;base64,') !== 0) {
            wp_send_json_error(__('Invalid signature format', 'lift-docs-system'));
        }

        // Remove the data URL prefix
        $image_data = str_replace('data:image/png;base64,', '', $signature_data);
        $image_data = str_replace(' ', '+', $image_data);
        $decoded_data = base64_decode($image_data);

        if ($decoded_data === false) {
            wp_send_json_error(__('Invalid signature data', 'lift-docs-system'));
        }

        // Create signature directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $signature_dir = $upload_dir['basedir'] . '/signatures';

        if (!file_exists($signature_dir)) {
            wp_mkdir_p($signature_dir);
        }

        // Generate MD5 hash for filename
        $hash = md5($field_id . current_time('timestamp') . get_current_user_id());
        $filename = $hash . '.png';
        $file_path = $signature_dir . '/' . $filename;

        // Save the file
        if (file_put_contents($file_path, $decoded_data) === false) {
            wp_send_json_error(__('Failed to save signature', 'lift-docs-system'));
        }

        // Return success with file URL
        $file_url = $upload_dir['baseurl'] . '/signatures/' . $filename;

        wp_send_json_success(array(
            'url' => $file_url,
            'path' => $file_path,
            'hash' => $hash
        ));
    }

    /**
     * Custom filename callback for uploads
     */
    public function unique_filename_callback($dir, $name, $ext) {
        // Generate MD5 hash for filename
        $hash = md5($name . current_time('timestamp') . get_current_user_id());
        return $hash . $ext;
    }

    /**
     * Create uploads directory structure
     */
    private function create_upload_directories() {
        $upload_dir = wp_upload_dir();

        // Create signatures directory
        $signature_dir = $upload_dir['basedir'] . '/signatures';
        if (!file_exists($signature_dir)) {
            wp_mkdir_p($signature_dir);

            // Add .htaccess to protect direct access
            $htaccess_content = "# Protect signature files\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "# Allow only through WordPress\n";
            file_put_contents($signature_dir . '/.htaccess', $htaccess_content);
        }

        // Create form uploads directory
        $form_uploads_dir = $upload_dir['basedir'] . '/form-uploads';
        if (!file_exists($form_uploads_dir)) {
            wp_mkdir_p($form_uploads_dir);
        }
    }

    /**
     * AJAX: Save form schema for BPMN.io form builder
     */
    public function ajax_save_form_schema() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_form_builder_nonce')) {
            wp_die(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('edit_lift_documents')) {
            wp_die(__('Insufficient permissions', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_description = sanitize_textarea_field($_POST['form_description']);
        $form_schema = wp_unslash($_POST['form_schema']); // JSON schema from BPMN.io

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';

        if ($form_id > 0) {
            // Update existing form
            $result = $wpdb->update(
                $forms_table,
                array(
                    'name' => $form_name,
                    'description' => $form_description,
                    'form_data' => $form_schema,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $form_id),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new form
            $result = $wpdb->insert(
                $forms_table,
                array(
                    'name' => $form_name,
                    'description' => $form_description,
                    'form_data' => $form_schema,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                $form_id = $wpdb->insert_id;
            }
        }

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Form saved successfully!', 'lift-docs-system'),
                'form_id' => $form_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error saving form', 'lift-docs-system')
            ));
        }
    }

    /**
     * AJAX: Load form schema for BPMN.io form builder
     */
    public function ajax_load_form_schema() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_form_builder_nonce')) {
            wp_die(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('edit_lift_documents')) {
            wp_die(__('Insufficient permissions', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id']);

        if ($form_id <= 0) {
            wp_send_json_success(array(
                'schema' => null,
                'message' => __('No form schema to load', 'lift-docs-system')
            ));
            return;
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));

        if ($form) {
            wp_send_json_success(array(
                'schema' => $form->form_data,
                'name' => $form->name,
                'description' => $form->description
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Form not found', 'lift-docs-system')
            ));
        }
    }

    /**
     * AJAX: Preview form
     */
    public function ajax_preview_form() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_form_builder_nonce')) {
            wp_die(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('edit_lift_documents')) {
            wp_die(__('Insufficient permissions', 'lift-docs-system'));
        }

        $form_schema = wp_unslash($_POST['form_schema']);

        // Return the schema for preview rendering in JavaScript
        wp_send_json_success(array(
            'schema' => $form_schema,
            'message' => __('Form preview ready', 'lift-docs-system')
        ));
    }

    /**
     * AJAX: Update form status
     */
    public function ajax_update_form_status() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_admin_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        $form_id = intval($_POST['form_id']);
        $new_status = sanitize_text_field($_POST['status']);

        // Validate status
        $valid_statuses = array('active', 'inactive', 'draft');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(__('Invalid status', 'lift-docs-system'));
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';

        // Check if form exists
        $form = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM $forms_table WHERE id = %d", $form_id));
        if (!$form) {
            wp_send_json_error(__('Form not found', 'lift-docs-system'));
        }

        // Update status
        $result = $wpdb->update(
            $forms_table,
            array('status' => $new_status),
            array('id' => $form_id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to update form status', 'lift-docs-system'));
        }

        // Get status label and color
        $status_labels = array(
            'active' => __('Active', 'lift-docs-system'),
            'inactive' => __('Inactive', 'lift-docs-system'),
            'draft' => __('Draft', 'lift-docs-system')
        );

        $status_colors = array(
            'active' => '#d4edda',
            'inactive' => '#f8d7da',
            'draft' => '#fff3cd'
        );

        wp_send_json_success(array(
            'status' => $new_status,
            'label' => $status_labels[$new_status],
            'color' => $status_colors[$new_status],
            'message' => sprintf(__('Form "%s" status updated to %s', 'lift-docs-system'), $form->name, $status_labels[$new_status])
        ));
    }

    /**
     * AJAX: Update submission status
     */
    public function ajax_update_submission_status() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_admin_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        $submission_id = intval($_POST['submission_id']);
        $new_status = sanitize_text_field($_POST['status']);

        // Validate status
        $valid_statuses = array('read', 'unread');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(__('Invalid status', 'lift-docs-system'));
        }

        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';

        // Check if submission exists
        $submission = $wpdb->get_row($wpdb->prepare("SELECT id FROM $submissions_table WHERE id = %d", $submission_id));
        if (!$submission) {
            wp_send_json_error(__('Submission not found', 'lift-docs-system'));
        }

        // Update status
        $result = $wpdb->update(
            $submissions_table,
            array('status' => $new_status),
            array('id' => $submission_id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to update submission status', 'lift-docs-system'));
        }

        // Get status label
        $status_labels = array(
            'read' => __('Read', 'lift-docs-system'),
            'unread' => __('Unread', 'lift-docs-system')
        );

        wp_send_json_success(array(
            'status' => $new_status,
            'label' => $status_labels[$new_status],
            'message' => sprintf(__('Submission status updated to %s', 'lift-docs-system'), $status_labels[$new_status])
        ));
    }

    /**
     * AJAX handler for importing forms from JSON
     */
    public function ajax_import_form() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_import_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('LIFT Forms Import: Permission check failed');
            wp_send_json_error(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No file uploaded or upload error', 'lift-docs-system'));
        }

        $file = $_FILES['import_file'];
        
        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['type'] !== 'application/json' && $file_extension !== 'json') {
            wp_send_json_error(__('Please upload a valid JSON file', 'lift-docs-system'));
        }

        // Read and parse JSON
        $json_content = file_get_contents($file['tmp_name']);
        error_log('LIFT Forms Import: Raw JSON length: ' . strlen($json_content));
        error_log('LIFT Forms Import: First 200 chars: ' . substr($json_content, 0, 200));
        
        $form_data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('LIFT Forms Import: JSON decode error: ' . json_last_error_msg());
            wp_send_json_error(__('Invalid JSON format: ', 'lift-docs-system') . json_last_error_msg());
        }

        error_log('LIFT Forms Import: Decoded data keys: ' . implode(', ', array_keys($form_data)));

        // Validate form structure
        $validation_result = $this->validate_form_import_data($form_data);
        if (!$validation_result['valid']) {
            wp_send_json_error(__('Invalid form structure: ', 'lift-docs-system') . $validation_result['error']);
        }

        // Check if it's multiple forms or single form
        if (isset($form_data['forms']) && is_array($form_data['forms'])) {
            // Multiple forms import
            $import_results = $this->import_multiple_forms_from_data($form_data);
            
            if ($import_results['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(__('%d forms imported successfully!', 'lift-docs-system'), $import_results['imported_count']),
                    'imported_count' => $import_results['imported_count'],
                    'failed_count' => $import_results['failed_count']
                ));
            } else {
                wp_send_json_error($import_results['error']);
            }
        } else {
            // Single form import
            // Use custom name if provided
            $custom_name = sanitize_text_field($_POST['form_name']);
            if (!empty($custom_name)) {
                $form_data['name'] = $custom_name;
            }

            $import_result = $this->import_form_from_data($form_data);
            
            if ($import_result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(__('Form "%s" imported successfully!', 'lift-docs-system'), $form_data['name']),
                    'form_id' => $import_result['form_id']
                ));
            } else {
                wp_send_json_error($import_result['error']);
            }
        }
    }

    /**
     * AJAX handler for exporting single form
     */
    public function ajax_export_form() {
        // Check nonce
        if (!wp_verify_nonce($_GET['nonce'], 'lift_forms_export_nonce')) {
            wp_die(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        $form_id = intval($_GET['form_id']);
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));
        
        if (!$form) {
            wp_die(__('Form not found', 'lift-docs-system'));
        }

        // Prepare export data
        $form_fields_data = json_decode($form->form_fields, true);
        $form_settings_data = json_decode($form->settings, true);
        
        // Extract layout and fields from form_fields data
        $layout_data = null;
        $fields_data = null;
        
        if (is_array($form_fields_data)) {
            if (isset($form_fields_data['layout'])) {
                $layout_data = $form_fields_data['layout'];
            }
            if (isset($form_fields_data['fields'])) {
                $fields_data = $form_fields_data['fields'];
            } else {
                // If no separate fields, use the entire form_fields as fields
                $fields_data = $form_fields_data;
            }
        }
        
        // Extract header and footer from settings
        $form_header = '';
        $form_footer = '';
        if (is_array($form_settings_data)) {
            $form_header = $form_settings_data['form_header'] ?? '';
            $form_footer = $form_settings_data['form_footer'] ?? '';
        }
        
        // If layout is null, create a simple default structure
        if ($layout_data === null && $fields_data !== null) {
            $layout_data = array(
                'rows' => array(
                    array(
                        'id' => 'row_1',
                        'type' => 'row',
                        'columns' => array(
                            array(
                                'id' => 'col_1_1',
                                'width' => 12,
                                'fields' => array_keys($fields_data)
                            )
                        )
                    )
                )
            );
        }
        
        $export_data = array(
            'name' => $form->name,
            'description' => $form->description,
            'layout' => $layout_data,
            'fields' => $fields_data,
            'form_header' => $form_header,
            'form_footer' => $form_footer,
            'export_info' => array(
                'exported_at' => current_time('mysql'),
                'exported_by' => wp_get_current_user()->display_name,
                'plugin_version' => '1.0.0',
                'wp_version' => get_bloginfo('version')
            )
        );
        
        // Debug final export data
        error_log('LIFT Forms Export: Final export data keys: ' . implode(', ', array_keys($export_data)));
        error_log('LIFT Forms Export: Layout null check: ' . ($export_data['layout'] === null ? 'NULL' : 'NOT NULL'));
        error_log('LIFT Forms Export: Fields null check: ' . ($export_data['fields'] === null ? 'NULL' : 'NOT NULL'));
        error_log('LIFT Forms Export: Header content length: ' . strlen($export_data['form_header']));
        error_log('LIFT Forms Export: Footer content length: ' . strlen($export_data['form_footer']));

        // Set headers for download
        $filename = 'lift-form-' . sanitize_file_name($form->name) . '-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX handler for exporting all forms
     */
    public function ajax_export_all_forms() {
        // Check nonce
        if (!wp_verify_nonce($_GET['nonce'], 'lift_forms_export_nonce')) {
            wp_die(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY created_at DESC");
        
        if (empty($forms)) {
            wp_die(__('No forms found', 'lift-docs-system'));
        }

        // Prepare export data
        $export_data = array(
            'forms' => array(),
            'export_info' => array(
                'exported_at' => current_time('mysql'),
                'exported_by' => wp_get_current_user()->display_name,
                'plugin_version' => '1.0.0',
                'wp_version' => get_bloginfo('version'),
                'total_forms' => count($forms)
            )
        );

        foreach ($forms as $form) {
            $form_fields_data = json_decode($form->form_fields, true);
            $form_settings_data = json_decode($form->settings, true);
            
            // Extract layout and fields from form_fields data
            $layout_data = null;
            $fields_data = null;
            
            if (is_array($form_fields_data)) {
                if (isset($form_fields_data['layout'])) {
                    $layout_data = $form_fields_data['layout'];
                }
                if (isset($form_fields_data['fields'])) {
                    $fields_data = $form_fields_data['fields'];
                } else {
                    // If no separate fields, use the entire form_fields as fields
                    $fields_data = $form_fields_data;
                }
            }
            
            // Extract header and footer from settings
            $form_header = '';
            $form_footer = '';
            if (is_array($form_settings_data)) {
                $form_header = $form_settings_data['form_header'] ?? '';
                $form_footer = $form_settings_data['form_footer'] ?? '';
            }
            
            // If layout is null, create a simple default structure
            if ($layout_data === null && $fields_data !== null) {
                $layout_data = array(
                    'rows' => array(
                        array(
                            'id' => 'row_1',
                            'type' => 'row',
                            'columns' => array(
                                array(
                                    'id' => 'col_1_1',
                                    'width' => 12,
                                    'fields' => array_keys($fields_data)
                                )
                            )
                        )
                    )
                );
            }
            
            $export_data['forms'][] = array(
                'name' => $form->name,
                'description' => $form->description,
                'layout' => $layout_data,
                'fields' => $fields_data,
                'form_header' => $form_header,
                'form_footer' => $form_footer,
                'status' => $form->status,
                'created_at' => $form->created_at
            );
        }

        // Set headers for download
        $filename = 'lift-forms-backup-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX handler for getting available templates  
     */
    public function ajax_get_templates() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_templates_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        $templates_dir = plugin_dir_path(__FILE__) . '../templates/forms/';
        $templates = array();

        if (is_dir($templates_dir)) {
            $files = glob($templates_dir . '*.json');
            
            foreach ($files as $file) {
                if (is_readable($file)) {
                    $content = file_get_contents($file);
                    $template_data = json_decode($content, true);
                    
                    if ($template_data && isset($template_data['name'])) {
                        $templates[] = array(
                            'filename' => basename($file),
                            'name' => $template_data['name'],
                            'description' => $template_data['description'] ?? '',
                            'fields_count' => is_array($template_data['fields']) ? count($template_data['fields']) : 0,
                            'has_header' => !empty($template_data['form_header']),
                            'has_footer' => !empty($template_data['form_footer']),
                            'export_info' => $template_data['export_info'] ?? array()
                        );
                    }
                }
            }
        }

        wp_send_json_success($templates);
    }

    /**
     * AJAX handler for loading a specific template
     */
    public function ajax_load_template() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_templates_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sorry, you are not allowed to access this page.', 'lift-docs-system'));
        }

        $filename = sanitize_file_name($_POST['filename']);
        $templates_dir = plugin_dir_path(__FILE__) . '../templates/forms/';
        $file_path = $templates_dir . $filename;

        // Security check - ensure file is in templates directory and is .json
        if (!file_exists($file_path) || !is_readable($file_path) || pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
            wp_send_json_error(__('Template file not found or not accessible', 'lift-docs-system'));
        }

        // Read and validate template
        $content = file_get_contents($file_path);
        $template_data = json_decode($content, true);

        if (!$template_data) {
            wp_send_json_error(__('Invalid template format', 'lift-docs-system'));
        }

        // Validate required fields
        if (!isset($template_data['name']) || !isset($template_data['fields'])) {
            wp_send_json_error(__('Template missing required fields (name, fields)', 'lift-docs-system'));
        }

        wp_send_json_success($template_data);
    }

    /**
     * Validate form import data structure
     */
    private function validate_form_import_data($data) {
        // Check if data is valid
        if (!is_array($data) || empty($data)) {
            return array('valid' => false, 'error' => __('Invalid data format', 'lift-docs-system'));
        }
        
        // Check if it's a single form or multiple forms backup
        if (isset($data['forms']) && is_array($data['forms'])) {
            // Validate multiple forms structure
            if (empty($data['forms'])) {
                return array('valid' => false, 'error' => __('No forms found in backup file', 'lift-docs-system'));
            }
            
            // Validate each form in the backup
            foreach ($data['forms'] as $index => $form) {
                $form_validation = $this->validate_single_form_data($form);
                if (!$form_validation['valid']) {
                    return array('valid' => false, 'error' => sprintf(__('Form #%d validation failed: %s', 'lift-docs-system'), $index + 1, $form_validation['error']));
                }
            }
            
            return array('valid' => true);
        } else {
            // Single form validation
            return $this->validate_single_form_data($data);
        }
    }

    /**
     * Validate single form data structure
     */
    private function validate_single_form_data($data) {
        // Required fields for single form
        $required_fields = array('name', 'layout', 'fields');
        
        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $data)) {
                return array('valid' => false, 'error' => sprintf(__('Missing required field: %s. Available fields: %s', 'lift-docs-system'), $field, implode(', ', array_keys($data))));
            }
        }

        // Validate layout structure
        if (!is_array($data['layout'])) {
            return array('valid' => false, 'error' => __('Invalid layout structure - layout must be an array', 'lift-docs-system'));
        }
        
        if (!isset($data['layout']['rows']) && !array_key_exists('rows', $data['layout'])) {
            return array('valid' => false, 'error' => __('Invalid layout structure - layout must contain rows array', 'lift-docs-system'));
        }

        // Validate fields structure
        if (!is_array($data['fields'])) {
            return array('valid' => false, 'error' => __('Invalid fields structure - fields must be an array', 'lift-docs-system'));
        }

        return array('valid' => true);
    }

    /**
     * Import form from validated data
     */
    private function import_form_from_data($data) {
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';

        // Check if form name already exists
        $existing_form = $wpdb->get_var($wpdb->prepare("SELECT id FROM $forms_table WHERE name = %s", $data['name']));
        
        if ($existing_form) {
            // Add timestamp to make name unique
            $data['name'] .= ' (Imported ' . date('Y-m-d H:i:s') . ')';
        }

        // Combine layout and fields into form_fields for database storage
        $form_fields_data = array();
        
        if (isset($data['layout'])) {
            $form_fields_data['layout'] = $data['layout'];
        }
        
        if (isset($data['fields'])) {
            $form_fields_data['fields'] = $data['fields'];
        }

        // Prepare settings data with header and footer
        $settings_data = array();
        if (isset($data['form_header'])) {
            $settings_data['form_header'] = $data['form_header'];
        }
        if (isset($data['form_footer'])) {
            $settings_data['form_footer'] = $data['form_footer'];
        }

        // Prepare data for insertion
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'form_fields' => json_encode($form_fields_data),
            'settings' => json_encode($settings_data),
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'draft',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Insert form
        $result = $wpdb->insert($forms_table, $insert_data);

        if ($result === false) {
            return array('success' => false, 'error' => __('Failed to insert form into database', 'lift-docs-system') . ': ' . $wpdb->last_error);
        }

        error_log('LIFT Forms Import: Successfully inserted form with ID: ' . $wpdb->insert_id);
        return array('success' => true, 'form_id' => $wpdb->insert_id);
    }

    /**
     * Import multiple forms from validated data
     */
    private function import_multiple_forms_from_data($data) {
        $imported_count = 0;
        $failed_count = 0;
        $errors = array();

        foreach ($data['forms'] as $form_data) {
            $result = $this->import_form_from_data($form_data);
            
            if ($result['success']) {
                $imported_count++;
            } else {
                $failed_count++;
                $errors[] = sprintf(__('Failed to import form "%s": %s', 'lift-docs-system'), $form_data['name'], $result['error']);
            }
        }

        if ($imported_count > 0) {
            return array(
                'success' => true, 
                'imported_count' => $imported_count,
                'failed_count' => $failed_count,
                'errors' => $errors
            );
        } else {
            return array(
                'success' => false, 
                'error' => __('No forms could be imported', 'lift-docs-system') . ': ' . implode('; ', $errors)
            );
        }
    }
}

// Initialize LIFT Forms
new LIFT_Forms();
