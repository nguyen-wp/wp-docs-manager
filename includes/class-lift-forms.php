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
        
        // AJAX handlers
        add_action('wp_ajax_lift_forms_save', array($this, 'ajax_save_form'));
        add_action('wp_ajax_lift_forms_get', array($this, 'ajax_get_form'));
        add_action('wp_ajax_lift_forms_delete', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_lift_forms_submit', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_lift_forms_submit', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_lift_forms_get_submission', array($this, 'ajax_get_submission'));
        
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
        dbDelta($sql_forms);
        dbDelta($sql_submissions);
        
        // Add user_id column if it doesn't exist (for existing installations)
        $this->maybe_add_user_id_column();
    }
    
    /**
     * Add user_id column if it doesn't exist
     */
    private function maybe_add_user_id_column() {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        
        // Check if user_id column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$submissions_table} LIKE %s",
            'user_id'
        ));
        
        if (empty($column_exists)) {
            // Add user_id column
            $wpdb->query("ALTER TABLE {$submissions_table} ADD COLUMN user_id bigint(20) UNSIGNED DEFAULT NULL AFTER form_data");
            $wpdb->query("ALTER TABLE {$submissions_table} ADD INDEX user_id (user_id)");
        }
        
        // Check if updated_at column exists
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
        
        if (!$is_lift_forms_page) {
            return;
        }
        
        // Only enqueue jQuery for basic functionality
        wp_enqueue_script('jquery');
        
        // Minimal admin JavaScript
        wp_enqueue_script(
            'lift-forms-minimal-admin',
            plugin_dir_url(__FILE__) . '../assets/js/minimal-admin.js',
            array('jquery'),
            '3.0.0',
            true
        );
        
        // Basic admin styles only
        wp_enqueue_style(
            'lift-forms-minimal-admin',
            plugin_dir_url(__FILE__) . '../assets/css/minimal-admin.css',
            array(),
            '3.0.0'
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
        
        wp_enqueue_style(
            'lift-forms-frontend',
            plugin_dir_url(__FILE__) . '../assets/css/forms-frontend.css',
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
    }
    
    /**
     * Admin page - Forms list
     */
    public function admin_page() {
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        // Handle form deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $form_id = intval($_GET['id']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_form_' . $form_id)) {
                $wpdb->delete($forms_table, array('id' => $form_id), array('%d'));
                echo '<div class="notice notice-success"><p>' . __('Form deleted successfully.', 'lift-docs-system') . '</p></div>';
            }
        }
        
        $forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('LIFT Forms', 'lift-docs-system'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=lift-forms-builder'); ?>" class="page-title-action">
                <?php _e('Add New Form', 'lift-docs-system'); ?>
            </a>
            
            <div class="lift-forms-overview">
                <div class="lift-forms-stats">
                    <div class="stat-box">
                        <h3><?php echo count($forms); ?></h3>
                        <p><?php _e('Total Forms', 'lift-docs-system'); ?></p>
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
                        <h2><?php _e('No Forms Yet', 'lift-docs-system'); ?></h2>
                        <p><?php _e('Create your first form to start collecting information from customers.', 'lift-docs-system'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=lift-forms-builder'); ?>" class="button button-primary button-large">
                            <?php _e('Create Your First Form', 'lift-docs-system'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
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
                                            <?php echo $this->get_form_submissions_count($form->id); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status status-<?php echo esc_attr($form->status); ?>">
                                            <?php echo ucfirst($form->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($form->created_at)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=lift-forms-builder&id=' . $form->id); ?>" class="button button-small">
                                            <?php _e('Edit', 'lift-docs-system'); ?>
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lift-forms&action=delete&id=' . $form->id), 'delete_form_' . $form->id); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this form?', 'lift-docs-system'); ?>')">
                                            <?php _e('Delete', 'lift-docs-system'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
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
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $form ? __('Edit Form', 'lift-docs-system') : __('Create New Form', 'lift-docs-system'); ?></h1>
            
            <div class="lift-form-builder">
                <div class="form-builder-header">
                    <div class="form-basic-settings">
                        <input type="hidden" id="form-id" value="<?php echo $form ? $form->id : 0; ?>">
                        <div class="setting-group">
                            <label for="form-name"><?php _e('Form Name', 'lift-docs-system'); ?></label>
                            <input type="text" id="form-name" value="<?php echo $form ? esc_attr($form->name) : ''; ?>" placeholder="<?php _e('Enter form name...', 'lift-docs-system'); ?>">
                        </div>
                        <div class="setting-group">
                            <label for="form-description"><?php _e('Description', 'lift-docs-system'); ?></label>
                            <input id="form-description" placeholder="<?php _e('Enter form description...', 'lift-docs-system'); ?>" value="<?php echo $form ? esc_attr($form->description) : ''; ?>" type="text">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="save-form" class="button button-primary">
                            <?php _e('Save Form', 'lift-docs-system'); ?>
                        </button>
                        <button type="button" id="preview-form" class="button">
                            <?php _e('Preview', 'lift-docs-system'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Form Builder Content Area - Empty for now -->
                <div class="form-builder-content">
                    <div class="empty-builder-message">
                        <h3><?php _e('Form Builder', 'lift-docs-system'); ?></h3>
                        <p><?php _e('Form builder content has been cleared. Ready for new implementation.', 'lift-docs-system'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Submissions page
     */
    public function submissions_page() {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $user_filter = isset($_GET['user_filter']) ? sanitize_text_field($_GET['user_filter']) : '';
        
        // Get forms for filter
        $forms = $wpdb->get_results("SELECT id, name FROM $forms_table ORDER BY name");
        
        // Build query
        $where = '1=1';
        $params = array();
        
        if ($form_id) {
            $where .= ' AND s.form_id = %d';
            $params[] = $form_id;
        }
        
        if ($user_filter === 'logged_in') {
            $where .= ' AND s.user_id IS NOT NULL';
        } elseif ($user_filter === 'guest') {
            $where .= ' AND s.user_id IS NULL';
        }
        
        // Execute query with or without prepare based on params
        if (!empty($params)) {
            $submissions = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, f.name as form_name, u.display_name as user_name, u.user_email as user_email
                 FROM $submissions_table s 
                 LEFT JOIN $forms_table f ON s.form_id = f.id 
                 LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                 WHERE $where 
                 ORDER BY s.submitted_at DESC",
                $params
            ));
        } else {
            $submissions = $wpdb->get_results(
                "SELECT s.*, f.name as form_name, u.display_name as user_name, u.user_email as user_email
                 FROM $submissions_table s 
                 LEFT JOIN $forms_table f ON s.form_id = f.id 
                 LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                 WHERE $where 
                 ORDER BY s.submitted_at DESC"
            );
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Form Submissions', 'lift-docs-system'); ?></h1>
            
                        <div class="submissions-filters">
                <form method="get">
                    <input type="hidden" name="page" value="lift-forms-submissions">
                    
                    <select name="form_id" onchange="this.form.submit()">
                        <option value=""><?php _e('All Forms', 'lift-docs-system'); ?></option>
                        <?php foreach ($forms as $form): ?>
                            <option value="<?php echo $form->id; ?>" <?php selected($form_id, $form->id); ?>>
                                <?php echo esc_html($form->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="user_filter" onchange="this.form.submit()">
                        <option value=""><?php _e('All Users', 'lift-docs-system'); ?></option>
                        <option value="logged_in" <?php selected($user_filter, 'logged_in'); ?>><?php _e('Logged In Users', 'lift-docs-system'); ?></option>
                        <option value="guest" <?php selected($user_filter, 'guest'); ?>><?php _e('Guest Users', 'lift-docs-system'); ?></option>
                    </select>
                    
                    <?php if ($form_id || $user_filter): ?>
                        <a href="<?php echo admin_url('admin.php?page=lift-forms-submissions'); ?>" class="button">
                            <?php _e('Clear Filters', 'lift-docs-system'); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (empty($submissions)): ?>
                <div class="lift-empty-state">
                    <h2><?php _e('No Submissions Yet', 'lift-docs-system'); ?></h2>
                    <p><?php _e('Form submissions will appear here once customers start filling out your forms.', 'lift-docs-system'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Form', 'lift-docs-system'); ?></th>
                            <th><?php _e('Submitted By', 'lift-docs-system'); ?></th>
                            <th><?php _e('Submitted', 'lift-docs-system'); ?></th>
                            <th><?php _e('Status', 'lift-docs-system'); ?></th>
                            <th><?php _e('IP Address', 'lift-docs-system'); ?></th>
                            <th><?php _e('Actions', 'lift-docs-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($submission->form_name); ?></strong>
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
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $submission->user_id); ?>" class="button button-small" target="_blank">
                                            <?php _e('View User', 'lift-docs-system'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Submission Detail Modal -->
        <div id="submission-detail-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2><?php _e('Submission Details', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>
                <div class="lift-modal-body">
                    <div id="submission-detail-content"></div>
                </div>
            </div>
        </div>
        <div id="submission-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>
        
        <style>
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
        .submissions-filters {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .submissions-filters form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 0;
        }
        .submissions-filters select {
            min-width: 200px;
            padding: 6px 10px;
        }
        .submissions-filters .button {
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            .submissions-filters {
                flex-direction: column;
                align-items: stretch;
            }
            .submissions-filters form {
                flex-direction: column;
                gap: 10px;
            }
            .submissions-filters select {
                min-width: auto;
                width: 100%;
            }
        }
        
        /* Modal styles */
        #submission-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 999999;
        }
        
        #submission-detail-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 4px;
            z-index: 1000000;
            padding: 20px;
            width: 90%;
        }
        
        .lift-modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            line-height: 1;
        }
        
        .lift-modal-close:hover {
            color: #333;
        }
        
        @media (max-width: 768px) {
            #submission-detail-modal {
                width: 95%;
                max-height: 90vh;
                padding: 15px;
                top: 5%;
                transform: translateX(-50%);
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // View submission handler
            $('.view-submission').on('click', function() {
                var submissionId = $(this).data('id');
                
                // Show loading
                $('#submission-detail-content').html('<p><?php _e('Loading...', 'lift-docs-system'); ?></p>');
                $('#submission-detail-modal').show();
                $('#submission-modal-backdrop').show();
                
                // Make AJAX request
                $.post(ajaxurl, {
                    action: 'lift_forms_get_submission',
                    submission_id: submissionId,
                    nonce: '<?php echo wp_create_nonce('lift_forms_get_submission'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#submission-detail-content').html(response.data);
                    } else {
                        $('#submission-detail-content').html('<p class="error">' + (response.data || '<?php _e('Error loading submission', 'lift-docs-system'); ?>') + '</p>');
                    }
                }).fail(function() {
                    $('#submission-detail-content').html('<p class="error"><?php _e('Network error', 'lift-docs-system'); ?></p>');
                });
            });
            
            // Close modal handlers
            $('.lift-modal-close, #submission-modal-backdrop').on('click', function() {
                $('#submission-detail-modal').hide();
                $('#submission-modal-backdrop').hide();
            });
            
            // ESC key to close modal
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) {
                    $('#submission-detail-modal').hide();
                    $('#submission-modal-backdrop').hide();
                }
            });
        });
        </script>
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
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_nonce')) {
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
        
        // Debug log the form fields data from database
        error_log('LIFT Forms Get - Raw form_fields from DB: ' . print_r($form->form_fields, true));
        error_log('LIFT Forms Get - form_fields type: ' . gettype($form->form_fields));
        
        // Clean form_fields if it contains invalid characters
        if (!empty($form->form_fields)) {
            $form->form_fields = trim($form->form_fields);
            
            // Test if it's valid JSON
            $test_decode = json_decode($form->form_fields, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('LIFT Forms Get - Invalid JSON in DB: ' . json_last_error_msg());
                error_log('LIFT Forms Get - Problematic data: ' . $form->form_fields);
                
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
                    error_log('LIFT Forms Get - Fixed JSON successfully');
                } else {
                    // If still broken, set to empty array
                    $form->form_fields = '[]';
                    error_log('LIFT Forms Get - Could not fix JSON, using empty array');
                }
            }
        }
        
        wp_send_json_success($form);
    }
    
    /**
     * AJAX save form
     */
    public function ajax_save_form() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'lift_forms_nonce')) {
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
        
        // Ensure fields is a string
        if (!is_string($fields)) {
            $fields = '';
        }
        
        // Ensure settings is a string
        if (!is_string($settings)) {
            $settings = '{}';
        }
        
        if (empty($name)) {
            wp_send_json_error(__('Form name is required', 'lift-docs-system'));
        }
        
        // Validate fields data
        if (empty($fields) || $fields === '[]' || $fields === 'null' || $fields === 'undefined') {
            wp_send_json_error(__('Form must have at least one field', 'lift-docs-system'));
        }
        
        // Enhanced JSON cleaning and validation
        $fields = trim($fields);
        
        // Log the raw fields data for debugging
        error_log('LIFT Forms - Raw fields data: ' . $fields);
        error_log('LIFT Forms - Fields length: ' . strlen($fields));
        
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
        
        error_log('LIFT Forms - Cleaned fields data: ' . $fields);
        
        // Test JSON validity with better error reporting
        $fields_array = json_decode($fields, true);
        $json_error = json_last_error();
        
        if ($json_error !== JSON_ERROR_NONE) {
            $json_error_msg = json_last_error_msg();
            error_log('LIFT Forms - JSON Error: ' . $json_error_msg);
            error_log('LIFT Forms - JSON Error Code: ' . $json_error);
            error_log('LIFT Forms - Problematic JSON: ' . $fields);
            
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
            wp_send_json_error(__('Fields data is empty or invalid', 'lift-docs-system'));
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
        if (!wp_verify_nonce($_POST['nonce'], 'lift_forms_nonce')) {
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
        $submission_id = intval($_POST['submission_id'] ?? 0);
        
        // Debug logging
        error_log('LIFT Forms Submit - is_edit: ' . ($is_edit ? 'true' : 'false') . ', submission_id: ' . $submission_id);
        
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
            // Check if user has access to the document
            if (!current_user_can('view_lift_documents') && 
                !in_array('documents_user', wp_get_current_user()->roles)) {
                wp_send_json_error(__('Access denied', 'lift-docs-system'));
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

        // If editing, validate that submission exists and belongs to current user
        if ($is_edit && $submission_id) {
            if ($current_user_id === null) {
                // Guest users cannot edit submissions
                wp_send_json_error(__('You must be logged in to edit submissions', 'lift-docs-system'));
            }
            
            $existing_submission = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$submissions_table} WHERE id = %d AND user_id = %d",
                $submission_id,
                $current_user_id
            ));
            
            if (!$existing_submission) {
                error_log('Edit validation failed - submission not found or user mismatch. Submission ID: ' . $submission_id . ', User ID: ' . $current_user_id);
                wp_send_json_error(__('You do not have permission to edit this submission', 'lift-docs-system'));
            }
            
            error_log('Edit validation passed - User can edit submission ID: ' . $submission_id);
        }
        
        $submission_data = array(
            'form_id' => $form_id,
            'form_data' => json_encode($processed_data),
            'user_id' => $current_user_id,
            'user_ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        if ($is_edit && $submission_id) {
            // Update existing submission
            $submission_data['updated_at'] = current_time('mysql');
            
            // Debug: Log the submission data
            error_log('Update submission data: ' . print_r($submission_data, true));
            error_log('Submission ID: ' . $submission_id . ', User ID: ' . $current_user_id);
            
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
            
            error_log('Using formats: ' . print_r($formats, true));
            
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
                error_log('Database update error: ' . $wpdb->last_error);
                error_log('Last query: ' . $wpdb->last_query);
                wp_send_json_error(__('Database error: ' . $wpdb->last_error, 'lift-docs-system'));
            } else if ($result === 0) {
                error_log('Update returned 0 rows affected - possible no changes or submission not found');
                // Check if submission still exists
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$submissions_table} WHERE id = %d", $submission_id));
                if (!$exists) {
                    error_log('Submission not found in database');
                    wp_send_json_error(__('Submission no longer exists', 'lift-docs-system'));
                } else {
                    error_log('Submission exists but no changes were made');
                }
            } else {
                error_log('Update successful. Rows affected: ' . $result);
            }
            
            $success_message = __('Form updated successfully!', 'lift-docs-system');
        } else {
            // Insert new submission
            $submission_data['submitted_at'] = current_time('mysql');
            $result = $wpdb->insert($submissions_table, $submission_data);
            $success_message = __('Form submitted successfully!', 'lift-docs-system');
        }

        if ($result !== false && ($result > 0 || !$is_edit)) {
            // Send notification email if configured (only for new submissions)
            if (!$is_edit) {
                $this->send_submission_notification($form, $processed_data);
            }
            
            // If submission is from a document, return redirect URL
            $response_data = array('message' => $success_message);
            if ($document_id) {
                $response_data['redirect_url'] = home_url('/document-dashboard/');
            }
            
            wp_send_json_success($response_data);
        } else {
            if ($is_edit && $result === 0) {
                wp_send_json_error(__('No changes were made to the submission', 'lift-docs-system'));
            } else {
                wp_send_json_error($is_edit ? __('Failed to update form', 'lift-docs-system') : __('Failed to submit form', 'lift-docs-system'));
            }
        }
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
        
        // Get submission with user data
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, f.name as form_name, u.display_name as user_name, u.user_email as user_email, u.user_login as user_login
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
        
        // Build HTML output
        ob_start();
        ?>
        <div class="submission-details">
            <div class="submission-meta">
                <h3><?php _e('Submission Information', 'lift-docs-system'); ?></h3>
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
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $submission->user_id); ?>" target="_blank" class="button button-small">
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
            
            <div class="submission-data">
                <h3><?php _e('Form Data', 'lift-docs-system'); ?></h3>
                <?php if (!empty($form_data)): ?>
                    <table class="form-table">
                        <?php foreach ($form_data as $key => $value): ?>
                            <?php if (strpos($key, '_') === 0) continue; // Skip meta fields ?>
                            <tr>
                                <th><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</th>
                                <td>
                                    <?php if (is_array($value)): ?>
                                        <?php echo esc_html(implode(', ', $value)); ?>
                                    <?php else: ?>
                                        <?php echo nl2br(esc_html($value)); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p><?php _e('No form data available.', 'lift-docs-system'); ?></p>
                <?php endif; ?>
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
                            <a href="<?php echo admin_url('post.php?post=' . intval($form_data['_document_id']) . '&action=edit'); ?>" target="_blank" class="button button-small">
                                <?php _e('Edit Document', 'lift-docs-system'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .submission-details {
            max-width: 100%;
        }
        .submission-details h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .submission-details h3:first-child {
            margin-top: 0;
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
        }
        .form-table td {
            word-break: break-word;
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
        
        ob_start();
        ?>
        <div class="lift-form-container" data-form-id="<?php echo $form_id; ?>">
            <?php if ($atts['title'] === 'true'): ?>
                <div class="lift-form-header">
                    <h2><?php echo esc_html($form->name); ?></h2>
                    <?php if ($form->description): ?>
                        <p><?php echo esc_html($form->description); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form class="lift-form" id="lift-form-<?php echo $form_id; ?>">
                <?php wp_nonce_field('lift_forms_submit_nonce', 'lift_forms_nonce'); ?>
                <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                
                <?php echo $this->render_form_fields($fields); ?>
                
                <div class="lift-form-submit">
                    <button type="submit" class="lift-form-submit-btn">
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
    
    private function render_form_fields($fields) {
        $html = '';
        
        foreach ($fields as $field) {
            $html .= $this->render_single_field($field);
        }
        
        return $html;
    }
    
    private function render_single_field($field) {
        $type = $field['type'] ?? 'text';
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        $required_asterisk = $required ? ' <span class="required">*</span>' : '';
        
        $html = '<div class="lift-form-field lift-field-' . esc_attr($type) . '">';
        
        switch ($type) {
            case 'text':
            case 'email':
            case 'number':
            case 'date':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                $html .= '<input type="' . esc_attr($type) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" ' . $required . '>';
                break;
                
            case 'textarea':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                $html .= '<textarea id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" ' . $required . '></textarea>';
                break;
                
            case 'select':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                $html .= '<select id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" ' . $required . '>';
                $html .= '<option value="">' . __('Please select...', 'lift-docs-system') . '</option>';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $html .= '<option value="' . esc_attr($option['value']) . '">' . esc_html($option['label']) . '</option>';
                    }
                }
                $html .= '</select>';
                break;
                
            case 'radio':
                $html .= '<fieldset>';
                $html .= '<legend>' . esc_html($field['label']) . $required_asterisk . '</legend>';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $i => $option) {
                        $html .= '<label class="radio-label">';
                        $html .= '<input type="radio" name="' . esc_attr($field['name']) . '" value="' . esc_attr($option['value']) . '" ' . $required . '>';
                        $html .= '<span>' . esc_html($option['label']) . '</span>';
                        $html .= '</label>';
                    }
                }
                $html .= '</fieldset>';
                break;
                
            case 'checkbox':
                $html .= '<fieldset>';
                $html .= '<legend>' . esc_html($field['label']) . $required_asterisk . '</legend>';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $i => $option) {
                        $html .= '<label class="checkbox-label">';
                        $html .= '<input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($option['value']) . '">';
                        $html .= '<span>' . esc_html($option['label']) . '</span>';
                        $html .= '</label>';
                    }
                }
                $html .= '</fieldset>';
                break;
                
            case 'file':
                $html .= '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . $required_asterisk . '</label>';
                $html .= '<input type="file" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" ' . $required . '>';
                if (!empty($field['accept'])) {
                    $html .= ' accept="' . esc_attr($field['accept']) . '"';
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
            $field_value = $form_data[$field_name] ?? '';
            $is_required = isset($field['required']) && $field['required'];
            
            // Check required fields
            if ($is_required && empty($field_value)) {
                $errors[$field_name] = sprintf(__('%s is required', 'lift-docs-system'), $field['label'] ?? $field_name);
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($field_value)) {
                continue;
            }
            
            // Type-specific validation
            switch ($field['type'] ?? 'text') {
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
            }
        }
        
        return $errors;
    }
    
    private function process_form_uploads($form_data) {
        // Handle file uploads here
        // For now, return data as-is
        return $form_data;
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
}

// Initialize LIFT Forms
new LIFT_Forms();
