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
            user_ip varchar(45),
            user_agent text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'unread',
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_forms);
        dbDelta($sql_submissions);
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
     * Enqueue admin scripts
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
        
        // Enqueue jQuery first - ensure it's loaded
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        
        // Enqueue jQuery UI scripts with proper dependencies
        wp_enqueue_script('jquery-ui-sortable', false, array('jquery', 'jquery-ui-core'));
        wp_enqueue_script('jquery-ui-draggable', false, array('jquery', 'jquery-ui-core'));
        wp_enqueue_script('jquery-ui-droppable', false, array('jquery', 'jquery-ui-core'));
        
        // Enqueue jQuery UI CSS
        wp_enqueue_style('jquery-ui-core');
        wp_enqueue_style('jquery-ui-theme');
        
        // Add jQuery UI theme from CDN as fallback
        wp_enqueue_style(
            'jquery-ui-theme-ui-lightness',
            'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css',
            array(),
            '1.13.2'
        );
        
        wp_enqueue_script(
            'lift-forms-builder',
            plugin_dir_url(__FILE__) . '../assets/js/forms-builder.js',
            array('jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            '1.1.0', // Enhanced sortable functionality
            true
        );
        
        // Enqueue enhanced forms builder for better error handling
        wp_enqueue_script(
            'lift-forms-builder-enhanced',
            plugin_dir_url(__FILE__) . '../assets/js/forms-builder-enhanced.js',
            array('lift-forms-builder'),
            '1.0.0',
            true
        );
        
        // Enqueue form builder fix for field sync issues
        wp_enqueue_script(
            'lift-forms-builder-fix',
            plugin_dir_url(__FILE__) . '../assets/js/forms-builder-fix.js',
            array('lift-forms-builder-enhanced'),
            '1.0.0',
            true
        );
        
        // Enqueue test tools in debug mode (disabled - working correctly)
        // if (defined('WP_DEBUG') && WP_DEBUG) {
        //     wp_enqueue_script(
        //         'lift-forms-builder-test',
        //         plugin_dir_url(__FILE__) . '../assets/js/forms-builder-test.js',
        //         array('lift-forms-builder-fix'),
        //         '1.0.0',
        //         true
        //     );
        // }
        
        // Enqueue ultimate debugger (disabled - working correctly)
        // wp_enqueue_script(
        //     'lift-forms-builder-ultimate-debug',
        //     plugin_dir_url(__FILE__) . '../assets/js/forms-builder-ultimate-debug.js',
        //     array('lift-forms-builder-fix'),
        //     '1.0.0',
        //     true
        // );
        
        wp_enqueue_style(
            'lift-forms-admin',
            plugin_dir_url(__FILE__) . '../assets/css/forms-admin.css',
            array(),
            '2.1.0' // Enhanced sortable functionality
        );
        
        wp_localize_script('lift-forms-builder', 'liftForms', array(
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
                        <div class="empty-icon">
                            <span class="dashicons dashicons-forms"></span>
                        </div>
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
     * Form builder page
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
                            <textarea id="form-description" placeholder="<?php _e('Enter form description...', 'lift-docs-system'); ?>"><?php echo $form ? esc_textarea($form->description) : ''; ?></textarea>
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
                
                <div class="form-builder-content">
                    <!-- Field Palette -->
                    <div class="field-palette">
                        <h3><?php _e('Form Fields', 'lift-docs-system'); ?></h3>
                        <div class="field-categories">
                            <div class="field-category">
                                <h4><?php _e('Basic Fields', 'lift-docs-system'); ?></h4>
                                <div class="field-items">
                                    <div class="field-item" data-type="text">
                                        <span class="dashicons dashicons-edit"></span>
                                        <span><?php _e('Text Input', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="textarea">
                                        <span class="dashicons dashicons-text-page"></span>
                                        <span><?php _e('Textarea', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="email">
                                        <span class="dashicons dashicons-email-alt"></span>
                                        <span><?php _e('Email', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="number">
                                        <span class="dashicons dashicons-calculator"></span>
                                        <span><?php _e('Number', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="date">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span><?php _e('Date', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="file">
                                        <span class="dashicons dashicons-paperclip"></span>
                                        <span><?php _e('File Upload', 'lift-docs-system'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="field-category">
                                <h4><?php _e('Choice Fields', 'lift-docs-system'); ?></h4>
                                <div class="field-items">
                                    <div class="field-item" data-type="select">
                                        <span class="dashicons dashicons-menu-alt"></span>
                                        <span><?php _e('Dropdown', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="radio">
                                        <span class="dashicons dashicons-marker"></span>
                                        <span><?php _e('Radio Buttons', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="checkbox">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <span><?php _e('Checkboxes', 'lift-docs-system'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="field-category">
                                <h4><?php _e('Layout', 'lift-docs-system'); ?></h4>
                                <div class="field-items">
                                    <div class="field-item" data-type="section">
                                        <span class="dashicons dashicons-grid-view"></span>
                                        <span><?php _e('Section', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="column">
                                        <span class="dashicons dashicons-columns"></span>
                                        <span><?php _e('Columns', 'lift-docs-system'); ?></span>
                                    </div>
                                    <div class="field-item" data-type="html">
                                        <span class="dashicons dashicons-editor-code"></span>
                                        <span><?php _e('HTML Block', 'lift-docs-system'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Canvas -->
                    <div class="form-canvas">
                        <div class="canvas-header">
                            <h3><?php _e('Form Builder', 'lift-docs-system'); ?></h3>
                            <div class="canvas-tools">
                                <button type="button" id="clear-form" class="button">
                                    <?php _e('Clear All', 'lift-docs-system'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="canvas-content" id="form-canvas">
                            <div class="canvas-placeholder">
                                <span class="dashicons dashicons-forms"></span>
                                <p><?php _e('Drag fields from the left panel to build your form', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Field Settings Panel -->
                    <div class="field-settings-panel" id="field-settings-panel">
                        <div class="panel-header">
                            <h3><?php _e('Field Settings', 'lift-docs-system'); ?></h3>
                            <button type="button" class="panel-close">&times;</button>
                        </div>
                        <div class="panel-content" id="field-settings-content">
                            <p><?php _e('Select a field to edit its properties', 'lift-docs-system'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Preview Modal -->
        <div id="form-preview-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2><?php _e('Form Preview', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>
                <div class="lift-modal-body">
                    <div id="form-preview-content"></div>
                </div>
            </div>
        </div>
        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>
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
        
        // Get forms for filter
        $forms = $wpdb->get_results("SELECT id, name FROM $forms_table ORDER BY name");
        
        // Build query
        $where = '1=1';
        $params = array();
        
        if ($form_id) {
            $where .= ' AND s.form_id = %d';
            $params[] = $form_id;
        }
        
        // Execute query with or without prepare based on params
        if (!empty($params)) {
            $submissions = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, f.name as form_name 
                 FROM $submissions_table s 
                 LEFT JOIN $forms_table f ON s.form_id = f.id 
                 WHERE $where 
                 ORDER BY s.submitted_at DESC",
                $params
            ));
        } else {
            $submissions = $wpdb->get_results(
                "SELECT s.*, f.name as form_name 
                 FROM $submissions_table s 
                 LEFT JOIN $forms_table f ON s.form_id = f.id 
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
                </form>
            </div>
            
            <?php if (empty($submissions)): ?>
                <div class="lift-empty-state">
                    <span class="dashicons dashicons-email-alt"></span>
                    <h2><?php _e('No Submissions Yet', 'lift-docs-system'); ?></h2>
                    <p><?php _e('Form submissions will appear here once customers start filling out your forms.', 'lift-docs-system'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Form', 'lift-docs-system'); ?></th>
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
        <?php
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
        $form_data = $_POST['form_data'];
        
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
        
        // Save submission
        $submissions_table = $wpdb->prefix . 'lift_form_submissions';
        $result = $wpdb->insert(
            $submissions_table,
            array(
                'form_id' => $form_id,
                'form_data' => json_encode($processed_data),
                'user_ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'submitted_at' => current_time('mysql')
            )
        );
        
        if ($result !== false) {
            // Send notification email if configured
            $this->send_submission_notification($form, $processed_data);
            
            wp_send_json_success(__('Form submitted successfully!', 'lift-docs-system'));
        } else {
            wp_send_json_error(__('Failed to submit form', 'lift-docs-system'));
        }
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
