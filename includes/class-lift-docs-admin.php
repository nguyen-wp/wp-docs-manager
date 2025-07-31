<?php
/**
 * Admin functionality for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Admin {

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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_admin'));
        add_action('admin_init', array($this, 'block_restricted_admin_access')); // Block admin access
        add_action('template_redirect', array($this, 'check_frontend_access'), 1); // Check frontend redirects
        add_filter('manage_lift_document_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_lift_document_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_footer', array($this, 'add_document_details_modal'));

        // Filter to exclude archived documents from default admin list
        add_action('pre_get_posts', array($this, 'exclude_archived_documents_from_admin'));

        // Add admin notice for archived documents view
        add_action('admin_notices', array($this, 'show_archived_documents_notice'));

        // Add bulk actions for archive/unarchive
        add_filter('bulk_actions-edit-lift_document', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-lift_document', array($this, 'handle_bulk_actions'), 10, 3);

        // Clean up old layout settings on first load
        add_action('admin_init', array($this, 'cleanup_old_layout_settings'), 5);

        // Create custom roles and capabilities
        add_action('init', array($this, 'create_document_roles'));

        // User management hooks
        add_action('show_user_profile', array($this, 'add_user_code_field'));
        add_action('edit_user_profile', array($this, 'add_user_code_field'));
        add_action('personal_options_update', array($this, 'save_user_code_field'));
        add_action('edit_user_profile_update', array($this, 'save_user_code_field'));
        add_action('user_register', array($this, 'generate_user_code_on_register'));
        add_action('set_user_role', array($this, 'generate_user_code_on_role_change'), 10, 3);

        // Add user code column to users list
        add_filter('manage_users_columns', array($this, 'add_user_code_column'));
        add_filter('manage_users_custom_column', array($this, 'show_user_code_column'), 10, 3);

        // AJAX handlers
        add_action('wp_ajax_generate_user_code', array($this, 'ajax_generate_user_code'));
        add_action('wp_ajax_get_admin_document_details', array($this, 'ajax_get_admin_document_details'));
        add_action('wp_ajax_update_document_status', array($this, 'ajax_update_document_status'));

        // Add script for users list
        add_action('admin_footer', array($this, 'add_users_list_script'));

        // Add filter for assigned users in documents list
        add_action('restrict_manage_posts', array($this, 'add_assigned_user_filter'));
        add_filter('parse_query', array($this, 'filter_documents_by_assigned_user'));

        // AJAX handlers for user search
        add_action('wp_ajax_search_document_users', array($this, 'ajax_search_document_users'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('LIFT Docs System', 'lift-docs-system'),
            __('LIFT Docs', 'lift-docs-system'),
            'manage_options',
            'edit.php?post_type=lift_document',
            '',
            'dashicons-media-document',
            30
        );

        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Add New Document', 'lift-docs-system'),
            __('Add New', 'lift-docs-system'),
            'manage_options',
            'post-new.php?post_type=lift_document'
        );

        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Categories', 'lift-docs-system'),
            __('Categories', 'lift-docs-system'),
            'manage_options',
            'edit-tags.php?taxonomy=lift_doc_category&post_type=lift_document'
        );

        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Archived Documents', 'lift-docs-system'),
            __('Archived', 'lift-docs-system'),
            'manage_options',
            'edit.php?post_type=lift_document&archived=1'
        );
        // LIFT Forms submenu
        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('LIFT Forms', 'lift-docs-system'),
            __('Forms', 'lift-docs-system'),
            'manage_options',
            'lift-forms',
            array($this, 'forms_admin_page')
        );

        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Form Builder', 'lift-docs-system'),
            __('Form Builder', 'lift-docs-system'),
            'manage_options',
            'lift-forms-builder',
            array($this, 'forms_builder_page')
        );

        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Form Submissions', 'lift-docs-system'),
            __('Submissions', 'lift-docs-system'),
            'manage_options',
            'lift-forms-submissions',
            array($this, 'forms_submissions_page')
        );

           add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Document Users', 'lift-docs-system'),
            __('Document Users', 'lift-docs-system'),
            'manage_options',
            'lift-docs-users',
            array($this, 'users_page')
        );

          add_submenu_page(
            'edit.php?post_type=lift_document',
            __('Settings', 'lift-docs-system'),
            __('Settings', 'lift-docs-system'),
            'manage_options',
            'lift-docs-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Initialize admin
     */
    public function init_admin() {
        // Additional admin initialization if needed
    }

    /**
     * Block admin access for documents_user and subscriber with smart redirect
     */
    public function block_restricted_admin_access() {
        // Only run in admin area
        if (!is_admin() || wp_doing_ajax()) {
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }

        $current_user = wp_get_current_user();
        $blocked_roles = array('documents_user', 'subscriber');
        $user_roles = $current_user->roles;

        // Check if user has any of the blocked roles
        $has_blocked_role = false;
        foreach ($blocked_roles as $blocked_role) {
            if (in_array($blocked_role, $user_roles)) {
                $has_blocked_role = true;
                break;
            }
        }

        if ($has_blocked_role) {
            global $pagenow;

            // Allow admin-ajax.php and admin-post.php
            if ($pagenow === 'admin-ajax.php' || $pagenow === 'admin-post.php') {
                return;
            }

            // Block all other admin pages including profile.php
            // If user is documents_user, redirect to Document Dashboard instead of logging out
            if (in_array('documents_user', $user_roles)) {
                $dashboard_url = home_url('/document-dashboard/');
                wp_safe_redirect($dashboard_url);
                exit;
            } else {
                // For subscriber and other restricted roles, redirect to home
                wp_safe_redirect(home_url());
                exit;
            }
        }
    }

    /**
     * Check frontend access and handle redirects for logged-in restricted users
     */
    public function check_frontend_access() {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';

        // Check if user is logged in and trying to access document-login page
        if (is_user_logged_in()) {
            // Redirect logged-in users from document-login to document-dashboard
            if (strpos($current_url, '/document-login/') !== false ||
                (is_page() && get_post_field('post_name') === 'document-login')) {
                $dashboard_url = home_url('/document-dashboard/');
                wp_safe_redirect($dashboard_url);
                exit;
            }
        }

        // Check if user is logged in for other restrictions
        if (!is_user_logged_in()) {
            return;
        }

        $current_user = wp_get_current_user();
        $blocked_roles = array('documents_user', 'subscriber');
        $user_roles = $current_user->roles;

        // Check if user has any of the blocked roles
        $has_blocked_role = false;
        foreach ($blocked_roles as $blocked_role) {
            if (in_array($blocked_role, $user_roles)) {
                $has_blocked_role = true;
                break;
            }
        }

        if ($has_blocked_role) {
            // Check if trying to access wp-login.php via frontend
            if (strpos($current_url, '/wp-login.php') !== false) {
                // If user is documents_user, redirect to Document Dashboard
                if (in_array('documents_user', $user_roles)) {
                    $dashboard_url = home_url('/document-dashboard/');
                    wp_safe_redirect($dashboard_url);
                    exit;
                } else {
                    // For other restricted roles, redirect to home
                    wp_safe_redirect(home_url());
                    exit;
                }
            }
        }
    }

    /**
     * Document Users management page
     */
    public function users_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Document Users Management', 'lift-docs-system'); ?></h1>

            <div class="lift-docs-users-management">
                <div class="users-with-documents-role">
                    <h2><?php _e('Users with Documents Access', 'lift-docs-system'); ?></h2>
                    <?php $this->display_documents_users(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (class_exists('LIFT_Docs_Settings')) {
            $settings = LIFT_Docs_Settings::get_instance();
            if (method_exists($settings, 'settings_page')) {
                $settings->settings_page();
            } else {
                echo '<div class="wrap"><h1>Settings</h1><p>Settings page not available.</p></div>';
            }
        } else {
            echo '<div class="wrap"><h1>Settings</h1><p>Settings class not found.</p></div>';
        }
    }

    /**
     * Set custom columns for documents list
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['category'] = __('Category', 'lift-docs-system');
        $new_columns['status'] = __('Status', 'lift-docs-system');
        $new_columns['assignments'] = __('Assigned Users', 'lift-docs-system');
        $new_columns['date'] = $columns['date'];
        $new_columns['document_details'] = __('Details', 'lift-docs-system');

        return $new_columns;
    }

    /**
     * Exclude archived documents from admin list by default
     */
    public function exclude_archived_documents_from_admin($query) {
        // Only apply to admin area
        if (!is_admin()) {
            return;
        }

        // Only apply to main query for lift_document post type
        if (!$query->is_main_query() || $query->get('post_type') !== 'lift_document') {
            return;
        }

        // Don't apply if specifically viewing archived documents
        if (isset($_GET['archived']) && $_GET['archived'] === '1') {
            // Show only archived documents
            $query->set('meta_query', array(
                array(
                    'key' => '_lift_doc_archived',
                    'value' => '1',
                    'compare' => '='
                )
            ));
            return;
        }

        // Exclude archived documents by default
        $meta_query = $query->get('meta_query') ?: array();
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_lift_doc_archived',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_lift_doc_archived',
                'value' => '1',
                'compare' => '!='
            )
        );

        $query->set('meta_query', $meta_query);
    }

    /**
     * Show notice when viewing archived documents or after bulk actions
     */
    public function show_archived_documents_notice() {
        $current_screen = get_current_screen();

        // Only show on document list page
        if (!$current_screen || $current_screen->id !== 'edit-lift_document') {
            return;
        }

        // Show bulk action results
        if (isset($_GET['archived_bulk'])) {
            $count = intval($_GET['archived_bulk']);
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <span class="dashicons dashicons-archive" style="vertical-align: middle; margin-right: 5px;"></span>
                    <strong><?php printf(_n('%d document archived.', '%d documents archived.', $count, 'lift-docs-system'), $count); ?></strong>
                </p>
            </div>
            <?php
        }

        if (isset($_GET['unarchived_bulk'])) {
            $count = intval($_GET['unarchived_bulk']);
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <span class="dashicons dashicons-yes-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                    <strong><?php printf(_n('%d document unarchived.', '%d documents unarchived.', $count, 'lift-docs-system'), $count); ?></strong>
                </p>
            </div>
            <?php
        }

        // Only show when viewing archived documents
        if (isset($_GET['archived']) && $_GET['archived'] === '1') {
            ?>
            <div class="notice notice-info">
                <p>
                    <span class="dashicons dashicons-archive" style="vertical-align: middle; margin-right: 5px;"></span>
                    <strong><?php _e('You are viewing archived documents.', 'lift-docs-system'); ?></strong>
                    <?php _e('These documents are hidden from the default list and frontend pages.', 'lift-docs-system'); ?>
                    <a href="<?php echo admin_url('edit.php?post_type=lift_document'); ?>" style="margin-left: 10px;">
                        <?php _e('View active documents', 'lift-docs-system'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add bulk actions for archive/unarchive
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['archive_documents'] = __('Archive', 'lift-docs-system');
        $bulk_actions['unarchive_documents'] = __('Unarchive', 'lift-docs-system');
        return $bulk_actions;
    }

    /**
     * Handle bulk actions for archive/unarchive
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'archive_documents') {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_lift_doc_archived', '1');
            }
            $redirect_to = add_query_arg('archived_bulk', count($post_ids), $redirect_to);
        } elseif ($doaction === 'unarchive_documents') {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_lift_doc_archived', '0');
            }
            $redirect_to = add_query_arg('unarchived_bulk', count($post_ids), $redirect_to);
        }
        return $redirect_to;
    }

    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'title':
                // Add archive indicator to title
                $is_archived = get_post_meta($post_id, '_lift_doc_archived', true);
                if ($is_archived === '1' || $is_archived === 1) {
                    echo '<span class="dashicons dashicons-archive" style="color: #e74c3c; margin-right: 5px;" title="' . __('Archived Document', 'lift-docs-system') . '"></span>';
                }
                break;

            case 'category':
                $terms = get_the_terms($post_id, 'lift_doc_category');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = array();
                    foreach ($terms as $term) {
                        $term_names[] = $term->name;
                    }
                    echo implode(', ', $term_names);
                } else {
                    echo '—';
                }
                break;

            case 'status':
                $this->render_status_column($post_id);
                break;

            case 'assignments':
                $this->render_assignments_column($post_id);
                break;

            case 'document_details':
                $this->render_document_details_button($post_id);
                break;
        }
    }

    /**
     * Render status column with dropdown
     */
    private function render_status_column($post_id) {
        $current_status = get_post_meta($post_id, '_lift_doc_status', true);
        if (empty($current_status)) {
            $current_status = 'pending';
        }

        $status_options = array(
            'pending' => __('Pending', 'lift-docs-system'),
            'processing' => __('Processing', 'lift-docs-system'),
            'done' => __('Done', 'lift-docs-system'),
            'cancelled' => __('Cancelled', 'lift-docs-system')
        );

        $status_colors = array(
            'pending' => '#f39c12',
            'processing' => '#3498db',
            'done' => '#27ae60',
            'cancelled' => '#e74c3c'
        );

        ?>
        <select class="lift-status-dropdown" data-post-id="<?php echo esc_attr($post_id); ?>" style="padding: 4px 8px; border-radius: 4px; border: 1px solid #ddd; background-color: <?php echo esc_attr($status_colors[$current_status]); ?>; color: white; font-weight: 500;">
            <?php foreach ($status_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?> data-color="<?php echo esc_attr($status_colors[$value]); ?>">
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render document details button with modal data
     */
    private function render_document_details_button($post_id) {
        // Get multiple files
        $file_urls = get_post_meta($post_id, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            // Check for legacy single file URL
            $legacy_url = get_post_meta($post_id, '_lift_doc_file_url', true);
            if ($legacy_url) {
                $file_urls = array($legacy_url);
            } else {
                $file_urls = array();
            }
        }

        // Filter out empty URLs
        $file_urls = array_filter($file_urls);

        // Collect all data
        $view_url = '';
        $view_label = '';

        // Get frontend instance to check permissions
        $frontend = LIFT_Docs_Frontend::get_instance();

        // Check if user can view document before generating Attached files
        $can_view = false;
        if ($frontend && method_exists($frontend, 'can_user_view_document')) {
            $reflection = new ReflectionClass($frontend);
            $method = $reflection->getMethod('can_user_view_document');
            $method->setAccessible(true);
            $can_view = $method->invoke($frontend, $post_id);
        } else {
            // Fallback check
            $can_view = !LIFT_Docs_Settings::get_setting('require_login_to_view', false) || is_user_logged_in();
        }

        if ($can_view) {
            if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                $view_url = LIFT_Docs_Settings::generate_secure_link($post_id);
                $view_label = __('Secure Attached files', 'lift-docs-system');
            } else {
                $view_url = get_permalink($post_id);
                $view_label = __('Attached files', 'lift-docs-system');
            }
        } else {
            $view_url = wp_login_url(get_permalink($post_id));
            $view_label = __('Login Required for View', 'lift-docs-system');
        }

        // Check if user can download before generating download URLs
        $can_download = false;
        if ($frontend && method_exists($frontend, 'can_user_download_document')) {
            $reflection = new ReflectionClass($frontend);
            $method = $reflection->getMethod('can_user_download_document');
            $method->setAccessible(true);
            $can_download = $method->invoke($frontend, $post_id);
        } else {
            // Fallback check
            $can_download = !LIFT_Docs_Settings::get_setting('require_login_to_download', false) || is_user_logged_in();
        }

        // Generate multiple download URLs and secure URLs
        $download_urls = array();
        $online_view_urls = array();
        $secure_download_urls = array();

        foreach ($file_urls as $index => $file_url) {
            if (!$file_url) continue;

            $file_name = basename(parse_url($file_url, PHP_URL_PATH));

            if ($can_download) {
                // Regular download URL with file index
                $download_urls[] = array(
                    'url' => add_query_arg(array(
                        'lift_download' => $post_id,
                        'file_index' => $index,
                        'nonce' => wp_create_nonce('lift_download_' . $post_id)
                    ), home_url()),
                    'name' => $file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1),
                    'index' => $index
                );

                // Online Attached files with file index
                $online_view_urls[] = array(
                    'url' => add_query_arg(array(
                        'lift_view_online' => $post_id,
                        'file_index' => $index,
                        'nonce' => wp_create_nonce('lift_view_online_' . $post_id)
                    ), home_url()),
                    'name' => $file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1),
                    'index' => $index
                );

                // Secure download URL if enabled
                if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                    $secure_download_urls[] = array(
                        'url' => LIFT_Docs_Settings::generate_secure_download_link($post_id, 0, $index),
                        'name' => $file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1),
                        'index' => $index
                    );
                }
            } else {
                // Login required URLs
                $login_url = wp_login_url(get_permalink($post_id));
                $download_urls[] = array(
                    'url' => $login_url,
                    'name' => $file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1),
                    'index' => $index
                );
                $online_view_urls[] = array(
                    'url' => $login_url,
                    'name' => $file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1),
                    'index' => $index
                );
                $secure_download_urls[] = array(
                    'url' => $login_url,
                    'name' => $file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1),
                    'index' => $index
                );
            }
        }

        $views = get_post_meta($post_id, '_lift_doc_views', true);
        $downloads = get_post_meta($post_id, '_lift_doc_downloads', true);

        ?>
        <button type="button" class="button button-primary lift-details-btn"
                data-post-id="<?php echo esc_attr($post_id); ?>"
                data-view-url="<?php echo esc_attr($view_url); ?>"
                data-view-label="<?php echo esc_attr($view_label); ?>"
                data-download-urls="<?php echo esc_attr(json_encode($download_urls)); ?>"
                data-online-view-urls="<?php echo esc_attr(json_encode($online_view_urls)); ?>"
                data-secure-download-urls="<?php echo esc_attr(json_encode($secure_download_urls)); ?>"
                data-views="<?php echo esc_attr($views ? number_format($views) : '0'); ?>"
                data-downloads="<?php echo esc_attr($downloads ? number_format($downloads) : '0'); ?>"
                data-can-view="<?php echo esc_attr($can_view ? 'true' : 'false'); ?>"
                data-can-download="<?php echo esc_attr($can_download ? 'true' : 'false'); ?>"
                data-files-count="<?php echo esc_attr(count($file_urls)); ?>">
            <?php _e('View Details', 'lift-docs-system'); ?>
        </button>
        <?php
    }

    /**
     * Render assignments column content
     */
    private function render_assignments_column($post_id) {
        $assigned_users = get_post_meta($post_id, '_lift_doc_assigned_users', true);

        if (empty($assigned_users) || !is_array($assigned_users)) {
            echo '<span style="color: #d63638; font-weight: 500;">' . __('Admin & Editor Only', 'lift-docs-system') . '</span>';
            return;
        }

        $user_count = count($assigned_users);
        $total_document_users = count(get_users(array('role' => 'documents_user')));

        if ($user_count === 0) {
            echo '<span style="color: #d63638;">' . __('No Access', 'lift-docs-system') . '</span>';
        } elseif ($user_count === $total_document_users) {
            echo '<span style="color: #007cba; font-weight: 500;">' . __('All Document Users Assigned', 'lift-docs-system') . '</span>';
        } else {
            $user_names = array();
            $max_display = 3;

            for ($i = 0; $i < min($user_count, $max_display); $i++) {
                $user = get_user_by('id', $assigned_users[$i]);
                if ($user) {
                    $user_names[] = $user->display_name;
                }
            }

            if ($user_count > $max_display) {
                $remaining = $user_count - $max_display;
                echo '<span style="color: #135e96; font-weight: 500;">';
                echo esc_html(implode(', ', $user_names));
                echo ' <small style="color: #666;">+' . $remaining . ' ' . __('more', 'lift-docs-system') . '</small>';
                echo '</span>';
            } else {
                echo '<span style="color: #135e96; font-weight: 500;">' . esc_html(implode(', ', $user_names)) . '</span>';
            }

            // echo '<br><small style="color: #666;">' . sprintf(__('%d of %d users', 'lift-docs-system'), $user_count, $total_document_users) . '</small>';
        }
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'lift-docs-details',
            __('Document Details & Secure Links', 'lift-docs-system'),
            array($this, 'document_details_meta_box'),
            'lift_document',
            'normal',
            'high'
        );

        add_meta_box(
            'lift-docs-forms',
            __('Assigned Forms', 'lift-docs-system'),
            array($this, 'document_forms_meta_box'),
            'lift_document',
            'side',
            'high'
        );

        add_meta_box(
            'lift-docs-assignments',
            __('Document Access Assignment', 'lift-docs-system'),
            array($this, 'document_assignments_meta_box'),
            'lift_document',
            'side',
            'high'
        );

        add_meta_box(
            'lift-docs-archive',
            __('Archive Settings', 'lift-docs-system'),
            array($this, 'document_archive_meta_box'),
            'lift_document',
            'side',
            'low'
        );
    }

    /**
     * Document details meta box
     */
    public function document_details_meta_box($post) {
        wp_nonce_field('lift_docs_meta_box', 'lift_docs_meta_box_nonce');

        // Handle multiple files
        $file_urls = get_post_meta($post->ID, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            // Check for legacy single file URL
            $legacy_url = get_post_meta($post->ID, '_lift_doc_file_url', true);
            if ($legacy_url) {
                $file_urls = array($legacy_url);
            } else {
                $file_urls = array();
            }
        }

        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Attached files', 'lift-docs-system'); ?></label></th>
                <td>
                    <div id="lift_doc_files_container">
                        <?php if (empty($file_urls)): ?>
                            <div class="file-input-row" data-index="0">
                                <input type="url" name="lift_doc_file_urls[]" value="" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button button-danger remove-file-button button-large" style="display: none;"><?php _e('<i class="fas fa-times"></i> Remove', 'lift-docs-system'); ?></button>
                                <!-- <span class="file-size-display"></span> -->
                            </div>
                        <?php else: ?>
                            <?php foreach ($file_urls as $index => $url): ?>
                            <div class="file-input-row" data-index="<?php echo $index; ?>">
                                <input type="url" name="lift_doc_file_urls[]" value="<?php echo esc_attr($url); ?>" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button button-danger remove-file-button button-large" <?php echo count($file_urls) <= 1 ? 'style="display: none;"' : ''; ?>><i class="fas fa-times"></i> <?php _e('Remove', 'lift-docs-system'); ?></button>
                                <!-- <span class="file-size-display">
                                    <?php if ($url): ?>
                                        <span style="color: #0073aa; font-weight: 500;">
                                            <i class="fas fa-file"></i> <?php echo basename(parse_url($url, PHP_URL_PATH)); ?>
                                        </span>
                                    <?php endif; ?>
                                </span> -->
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 15px;">
                        <button type="button" class="button button-secondary" id="add_file_button">
                            <?php _e('Add Another File', 'lift-docs-system'); ?>
                        </button>
                        <button type="button" class="button" id="clear_all_files" style="margin-left: 10px;">
                            <?php _e('Clear All', 'lift-docs-system'); ?>
                        </button>
                    </div>

                    <p class="description">
                        <?php _e('You can add multiple files of any type. Each file will have its own secure download link. Supported: Documents (PDF, DOC, XLS), Images (JPG, PNG), Videos (MP4, AVI), Audio (MP3, WAV), Archives (ZIP, RAR) and more.', 'lift-docs-system'); ?>
                    </p>
                </td>
            </tr>

            <?php
            // Include Secure Links section if enabled
            if (class_exists('LIFT_Docs_Settings') && LIFT_Docs_Settings::get_setting('enable_secure_links', false)):
                $secure_link = LIFT_Docs_Settings::generate_secure_link($post->ID);
            ?>
            <tr>
                <th colspan="2" style="padding-top: 25px; border-top: 1px solid #ddd;">
                    <h3 style="margin: 0; color: #23282d;"><i class="fas fa-lock"></i> <?php _e('Secure Links', 'lift-docs-system'); ?></h3>
                </th>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e('Current Secure Link:', 'lift-docs-system'); ?></label>
                </th>
                <td>
                    <textarea readonly class="large-text code" rows="3" onclick="this.select()"><?php echo esc_textarea($secure_link); ?></textarea>
                    <p class="description">
                        <?php _e('This link provides secure access to view the document and all its files.', 'lift-docs-system'); ?>
                    </p>
                    <button type="button" class="button" onclick="copySecureLink(this)">
                        <?php _e('Copy Secure Link', 'lift-docs-system'); ?>
                    </button>
                </td>
            </tr>

            <?php if (!empty($file_urls) && !empty(array_filter($file_urls))): ?>
            <tr>
                <th scope="row">
                    <label><?php _e('Secure Download Links:', 'lift-docs-system'); ?></label>
                </th>
                <td>
                    <?php if (count(array_filter($file_urls)) === 1): ?>
                        <?php
                        $download_link = LIFT_Docs_Settings::generate_secure_download_link($post->ID, 0);
                        ?>
                        <textarea readonly class="large-text code" rows="2" onclick="this.select()"><?php echo esc_textarea($download_link); ?></textarea>
                        <p class="description"><?php _e('Direct secure download link (never expires)', 'lift-docs-system'); ?></p>
                        <button type="button" class="button" onclick="copyDownloadLink(this)">
                            <?php _e('Copy Download Link', 'lift-docs-system'); ?>
                        </button>
                    <?php else: ?>
                        <div class="multiple-download-links">
                            <?php foreach (array_filter($file_urls) as $index => $url): ?>
                                <?php
                                $file_name = basename(parse_url($url, PHP_URL_PATH));
                                $download_link = LIFT_Docs_Settings::generate_secure_download_link($post->ID, 0, $index);
                                ?>
                                <div class="download-link-item" style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                                    <strong><?php echo esc_html($file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1)); ?></strong>
                                    <textarea readonly class="large-text code" rows="2" onclick="this.select()"><?php echo esc_textarea($download_link); ?></textarea>
                                    <button type="button" class="button" onclick="copyDownloadLink(this)" style="margin-top: 5px;">
                                        <?php printf(__('Copy Link %d', 'lift-docs-system'), $index + 1); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php _e('Each file has its own secure download link (never expires)', 'lift-docs-system'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: ?>
            <tr>
                <th scope="row">
                    <label><?php _e('Secure Download Links:', 'lift-docs-system'); ?></label>
                </th>
                <td>
                    <p class="description" style="color: #999; font-style: italic;">
                        <?php _e('Add file URLs above to generate secure download links.', 'lift-docs-system'); ?>
                    </p>
                </td>
            </tr>
            <?php endif; ?>

            <?php elseif (class_exists('LIFT_Docs_Settings')): ?>
            <tr>
                <th colspan="2" style="padding-top: 25px; border-top: 1px solid #ddd;">
                    <h3 style="margin: 0; color: #666;"><i class="fas fa-lock"></i> <?php _e('Secure Links', 'lift-docs-system'); ?></h3>
                </th>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secure Links Status:', 'lift-docs-system'); ?></th>
                <td>
                    <p class="description" style="color: #d63638; font-style: italic;">
                        <?php _e('Secure links are disabled. Enable them in LIFT Docs settings to generate secure links.', 'lift-docs-system'); ?>
                    </p>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let fileIndex = <?php echo count($file_urls); ?>;

            // Ensure wp.media is available
            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                // WordPress Media Library is available

                // Add new file input
                $('#add_file_button').click(function() {
                    const container = $('#lift_doc_files_container');
                    const newRow = $(`
                        <div class="file-input-row" data-index="${fileIndex}">
                            <input type="url" name="lift_doc_file_urls[]" value="" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                            <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Upload', 'lift-docs-system'); ?></button>
                            <button type="button" class="button button-danger remove-file-button button-large"><?php _e('<i class="fas fa-times"></i> Remove', 'lift-docs-system'); ?></button>
                            <span class="file-size-display"></span>
                        </div>
                    `);
                    container.append(newRow);
                    fileIndex++;
                    updateRemoveButtons();
                });

                // Remove file input
                $(document).on('click', '.remove-file-button', function() {
                    $(this).closest('.file-input-row').remove();
                    updateRemoveButtons();
                });

                // Update remove buttons visibility
                function updateRemoveButtons() {
                    const rows = $('.file-input-row');
                    if (rows.length <= 1) {
                        $('.remove-file-button').hide();
                    } else {
                        $('.remove-file-button').show();
                    }
                }

                // WordPress Media Library Upload Handler
                $(document).on('click', '.upload-file-button', function(e) {
                    e.preventDefault();

                    const button = $(this);
                    const input = button.siblings('.file-url-input');
                    const sizeDisplay = button.siblings('.file-size-display');

                    // Create new media uploader for each button click
                    const mediaUploader = wp.media({
                        title: '<?php _e('Select Document File', 'lift-docs-system'); ?>',
                        button: {
                            text: '<?php _e('Use This File', 'lift-docs-system'); ?>'
                        },
                        multiple: false,
                        library: {
                            // Accept all file types that WordPress allows
                            type: [] // Empty array means all file types
                        }
                    });

                    // When file is selected
                    mediaUploader.on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();

                        // Set file URL
                        input.val(attachment.url);

                        // Display file info
                        const fileName = attachment.filename || attachment.title;
                        const fileSize = attachment.filesizeHumanReadable || formatFileSize(attachment.filesize || 0);
                        const fileType = attachment.subtype || attachment.type || 'file';

                        // Choose appropriate icon based on file type
                        let fileIcon = '<i class="fas fa-file"></i>'; // Default document icon
                        if (attachment.type === 'image') {
                            fileIcon = '<i class="fas fa-image"></i>';
                        } else if (attachment.type === 'video') {
                            fileIcon = '<i class="fas fa-video"></i>';
                        } else if (attachment.type === 'audio') {
                            fileIcon = '<i class="fas fa-music"></i>';
                        } else if (fileType.includes('pdf')) {
                            fileIcon = '<i class="fas fa-file-pdf"></i>';
                        } else if (fileType.includes('word') || fileType.includes('doc')) {
                            fileIcon = '<i class="fas fa-file-word"></i>';
                        } else if (fileType.includes('excel') || fileType.includes('sheet')) {
                            fileIcon = '<i class="fas fa-file-excel"></i>';
                        } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                            fileIcon = '<i class="fas fa-file-powerpoint"></i>';
                        } else if (fileType.includes('zip') || fileType.includes('rar')) {
                            fileIcon = '<i class="fas fa-file-archive"></i>';
                        }

                        sizeDisplay.html(`
                            <span style="color: #0073aa; font-weight: 500;">
                                ${fileIcon} ${fileName} (${fileSize})
                            </span>
                        `);

                        // Add uploaded class for visual feedback
                        button.closest('.file-input-row').addClass('uploaded');
                        setTimeout(function() {
                            button.closest('.file-input-row').removeClass('uploaded');
                        }, 2000);

                        // Show success feedback
                        const originalText = button.text();
                        button.html('<i class="fas fa-check"></i> <?php _e('Uploaded', 'lift-docs-system'); ?>');
                        setTimeout(function() {
                            button.text(originalText);
                        }, 2000);
                    });

                    // Open media uploader
                    mediaUploader.open();
                });

                // Format file size
                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                // Initialize remove buttons
                updateRemoveButtons();

                // Clear all files button
                $('#clear_all_files').click(function() {
                    if (confirm('<?php _e('Are you sure you want to remove all files?', 'lift-docs-system'); ?>')) {
                        $('#lift_doc_files_container').empty();
                        // Add one empty row
                        const newRow = $(`
                            <div class="file-input-row" data-index="0">
                                <input type="url" name="lift_doc_file_urls[]" value="" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button button-danger remove-file-button button-large" style="display: none;"><i class="fas fa-times"></i> <?php _e('Remove', 'lift-docs-system'); ?></button>
                                <span class="file-size-display"></span>
                            </div>
                        `);
                        $('#lift_doc_files_container').append(newRow);
                        fileIndex = 1;
                        updateRemoveButtons();
                    }
                });

            } else {
                // Fallback if Media Library is not available

                // Add new file input
                $('#add_file_button').click(function() {
                    const container = $('#lift_doc_files_container');
                    const newRow = $(`
                        <div class="file-input-row" data-index="${fileIndex}">
                            <input type="url" name="lift_doc_file_urls[]" value="" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL', 'lift-docs-system'); ?>" />
                            <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Browse', 'lift-docs-system'); ?></button>
                            <button type="button" class="button button-danger remove-file-button button-large"><?php _e('<i class="fas fa-times"></i> Remove', 'lift-docs-system'); ?></button>
                            <span class="file-size-display"></span>
                        </div>
                    `);
                    container.append(newRow);
                    fileIndex++;
                    updateRemoveButtons();
                });

                // Remove file input
                $(document).on('click', '.remove-file-button', function() {
                    $(this).closest('.file-input-row').remove();
                    updateRemoveButtons();
                });

                // Update remove buttons visibility
                function updateRemoveButtons() {
                    const rows = $('.file-input-row');
                    if (rows.length <= 1) {
                        $('.remove-file-button').hide();
                    } else {
                        $('.remove-file-button').show();
                    }
                }

                // Fallback file selection
                $(document).on('click', '.upload-file-button', function() {
                    const button = $(this);
                    const input = button.siblings('.file-url-input');
                    const sizeDisplay = button.siblings('.file-size-display');

                    const fileInput = $('<input type="file" style="display: none;" />');
                    $('body').append(fileInput);
                    fileInput.click();

                    fileInput.change(function() {
                        const file = this.files[0];
                        if (file) {
                            // Show file info even in fallback mode
                            const fileName = file.name;
                            const fileSize = formatFileSize(file.size);

                            // Choose appropriate icon based on file type
                            let fileIcon = '<i class="fas fa-file"></i>'; // Default document icon
                            const fileType = file.type.toLowerCase();
                            if (fileType.startsWith('image/')) {
                                fileIcon = '<i class="fas fa-image"></i>';
                            } else if (fileType.startsWith('video/')) {
                                fileIcon = '<i class="fas fa-video"></i>';
                            } else if (fileType.startsWith('audio/')) {
                                fileIcon = '<i class="fas fa-music"></i>';
                            } else if (fileType.includes('pdf')) {
                                fileIcon = '<i class="fas fa-file-pdf"></i>';
                            } else if (fileType.includes('word') || fileType.includes('document')) {
                                fileIcon = '<i class="fas fa-file-word"></i>';
                            } else if (fileType.includes('excel') || fileType.includes('sheet')) {
                                fileIcon = '<i class="fas fa-file-excel"></i>';
                            } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                                fileIcon = '<i class="fas fa-file-powerpoint"></i>';
                            } else if (fileType.includes('zip') || fileType.includes('rar')) {
                                fileIcon = '<i class="fas fa-file-archive"></i>';
                            }

                            sizeDisplay.html(`
                                <span style="color: #ff9800; font-weight: 500;">
                                    ${fileIcon} ${fileName} (${fileSize}) - <?php _e('Not uploaded to media library', 'lift-docs-system'); ?>
                                </span>
                            `);

                            alert('<?php _e('Please upload this file to your media library first, then paste the URL here. Or drag and drop the file into the WordPress media uploader.', 'lift-docs-system'); ?>');
                        }
                        fileInput.remove();
                    });
                });

                // Initialize remove buttons
                updateRemoveButtons();
            }
        });

        function copySecureLink(button) {
            var row = button.closest('tr');
            var textarea = row.querySelector('textarea');
            textarea.select();
            document.execCommand('copy');

            var originalText = button.textContent;
            button.textContent = '<?php _e("Copied!", "lift-docs-system"); ?>';
            setTimeout(function() {
                button.textContent = originalText;
            }, 2000);
        }

        function copyDownloadLink(button) {
            var row = button.closest('tr, .download-link-item');
            var textarea = row.querySelector('textarea');
            if (textarea) {
                textarea.select();
                document.execCommand('copy');

                var originalText = button.textContent;
                button.textContent = '<?php _e("Copied!", "lift-docs-system"); ?>';
                setTimeout(function() {
                    button.textContent = originalText;
                }, 2000);
            }
        }
        </script>

        <style type="text/css">

        /* File Size Display */
        .file-size-display {
            min-width: 150px;
            font-size: 12px;
            color: #666;
            padding: 6px 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            display: none;
        }

        .file-size-display:empty {
            background: transparent;
            border: none;
            min-width: 0;
            padding: 0;
        }

        .file-size-display span[style*="color: #0073aa"] {
            background: #e3f2fd;
            color: #0d47a1 !important;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #90caf9;
            font-weight: 500;
            display: inline-block;
        }

        .file-size-display span[style*="color: #ff9800"] {
            background: #fff3e0;
            color: #e65100 !important;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ffb74d;
            font-weight: 500;
            display: inline-block;
        }

        .file-input-row {
            margin-bottom: 10px;
        }

        /* File Row Number Badge */
        /* row.file-input-::before {
            content: attr(data-index);
            position: absolute;
            top: -8px;
            left: 12px;
            background: #0073aa;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 12px;
            display: none;
        } */

        /* .file-input-row:nth-child(n+2)::before {
            display: block;
        } */

        /* Action Buttons Container */
        .file-actions-container {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e1e1e1;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        /* Upload Success Animation */
        .file-input-row.uploaded {
            background: #d4edda;
            border-color: #46b450;
        }

        /* Empty State */
        #lift_doc_files_container:empty::before {
            content: "<?php _e('No files added yet. Click "Add Another File" below to get started.', 'lift-docs-system'); ?>";
            display: block;
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px 20px;
            background: #f8f9fa;
            border: 2px dashed #ced4da;
            border-radius: 8px;
            font-size: 14px;
        }

        /* Loading States */
        .file-input-row.uploading {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .file-input-row {
                flex-wrap: wrap;
            }

            .file-input-row .file-url-input {
                min-width: 200px;
                flex: 1 1 auto;
            }
        }

        @media (max-width: 768px) {
            .file-input-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 16px;
            }

            .file-input-row .file-url-input {
                min-width: auto;
                width: 100%;
            }

            .file-input-row .upload-file-button,
            .file-input-row .remove-file-button {
                width: 100%;
                justify-content: center;
                min-width: auto;
            }

            .file-actions-container {
                flex-direction: column;
                align-items: stretch;
            }

            /* #add_file_button,
            #clear_all_files {
                width: 100%;
                justify-content: center;
            } */

            .file-size-display {
                min-width: auto;
                text-align: center;
            }
        }

        /* Focus Management */
        .file-input-row:focus-within {
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
        }

        /* Responsive Design Media Queries */
        @media (max-width: 768px) {
            .file-input-row button {
                width: 100%;
            }

            #add_file_button,
            #clear_all_files {
                width: 100%;
                margin-top: 10px;
            }
        }
        </style>
        <?php
    }

    /**
     * Document assignments meta box
     */
    public function document_assignments_meta_box($post) {
        wp_nonce_field('lift_docs_assignments_meta_box', 'lift_docs_assignments_meta_box_nonce');

        // Get assigned users
        $assigned_users = get_post_meta($post->ID, '_lift_doc_assigned_users', true);
        if (!is_array($assigned_users)) {
            $assigned_users = array();
        }

        // Get all users with Documents User role
        $document_users = get_users(array(
            'role' => 'documents_user',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        // Prepare user data for JavaScript to prevent undefined values
        $users_for_js = array();
        foreach ($document_users as $user) {
            $users_for_js[] = array(
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'lift_docs_user_code' => get_user_meta($user->ID, 'lift_docs_user_code', true)
            );
        }

        ?>
        <div class="document-assignments">
            <p><strong><?php _e('Assign Document Access', 'lift-docs-system'); ?></strong></p>

            <?php if (empty($document_users)): ?>
                <p class="notice notice-warning inline">
                    <?php printf(
                        __('No Document Users found. %sCreate Document Users%s first.', 'lift-docs-system'),
                        '<a href="' . admin_url('admin.php?page=lift-docs-users') . '">',
                        '</a>'
                    ); ?>
                </p>
            <?php else: ?>
                <!-- Selected Users Display -->
                <div class="selected-users-container" style="margin-bottom: 15px;">
                    <label><strong><?php _e('Selected Users:', 'lift-docs-system'); ?></strong></label>
                    <div class="selected-users-list" style="min-height: 40px; border: 1px solid #ddd; border-radius: 3px; padding: 8px; background: #f9f9f9;">
                        <?php if (!empty($assigned_users)): ?>
                            <?php foreach ($assigned_users as $user_id): ?>
                                <?php $user = get_user_by('id', $user_id); ?>
                                <?php if ($user): ?>
                                    <?php $user_code = get_user_meta($user_id, 'lift_docs_user_code', true); ?>
                                    <span class="selected-user-tag" data-user-id="<?php echo esc_attr($user_id); ?>" style="display: inline-block; background: #0073aa; color: #fff; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">
                                        <?php echo esc_html($user->display_name); ?>
                                        <?php if ($user_code): ?>
                                            <small style="opacity: 0.8; margin-left: 4px; font-family: monospace;">(<?php echo esc_html($user_code); ?>)</small>
                                        <?php endif; ?>
                                        <span class="remove-user" style="margin-left: 5px; cursor: pointer; font-weight: bold;">&times;</span>
                                        <input type="hidden" name="lift_doc_assigned_users[]" value="<?php echo esc_attr($user_id); ?>">
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <span class="no-users-selected" style="color: #666; font-style: italic; <?php echo !empty($assigned_users) ? 'display: none;' : ''; ?>">
                            <?php _e('No users selected (only Admin and Editor will have access)', 'lift-docs-system'); ?>
                        </span>
                    </div>
                </div>                <!-- Search and Add Users -->
                <div class="add-users-container">
                    <label for="user-search-input"><strong><?php _e('Add Users:', 'lift-docs-system'); ?></strong></label>
                    <input type="text" id="user-search-input" placeholder="<?php _e('Search users by name, email, or user code...', 'lift-docs-system'); ?>" style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px;">

                    <div class="users-search-results" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px; background: #fff; display: none;">
                        <?php foreach ($document_users as $user): ?>
                            <?php $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true); ?>
                            <div class="user-search-item"
                                 data-user-id="<?php echo esc_attr($user->ID); ?>"
                                 data-user-name="<?php echo esc_attr(strtolower($user->display_name)); ?>"
                                 data-user-email="<?php echo esc_attr(strtolower($user->user_email)); ?>"
                                 data-user-code="<?php echo esc_attr(strtolower($user_code)); ?>"
                                 style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; <?php echo in_array($user->ID, $assigned_users) ? 'display: none;' : ''; ?>">
                                <div style="font-weight: 500; display: flex; justify-content: space-between; align-items: center;">
                                    <span><?php echo esc_html($user->display_name); ?></span>
                                    <?php if ($user_code): ?>
                                        <span style="background: #f0f0f0; color: #333; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 11px; font-weight: normal;">
                                            <?php echo esc_html($user_code); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; color: #666;"><?php echo esc_html($user->user_email); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <button type="button" id="select-all-users" class="button button-secondary" style="margin-right: 10px;">
                        <?php _e('Select All', 'lift-docs-system'); ?>
                    </button>
                    <button type="button" id="clear-all-users" class="button button-secondary">
                        <?php _e('Clear All', 'lift-docs-system'); ?>
                    </button>
                </div>

                <p class="description" style="margin-top: 10px;">
                    <span id="users-count">
                        <?php printf(
                            __('Total Document Users: %d | Selected: %d', 'lift-docs-system'),
                            count($document_users),
                            count($assigned_users)
                        ); ?>
                    </span>
                </p>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var allUsers = <?php echo json_encode($users_for_js); ?>;
            var totalUsers = allUsers.length;

            // Search functionality
            $('#user-search-input').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var $results = $('.users-search-results');
                var $items = $('.user-search-item');

                if (searchTerm.length > 0) {
                    $results.show();

                    $items.each(function() {
                        var $item = $(this);
                        var userName = $item.data('user-name');
                        var userEmail = $item.data('user-email');
                        var userCode = $item.data('user-code');

                        if (userName.includes(searchTerm) ||
                            userEmail.includes(searchTerm) ||
                            (userCode && userCode.includes(searchTerm))) {
                            $item.show();
                        } else {
                            $item.hide();
                        }
                    });
                } else {
                    $results.hide();
                }
            });

            // Hide search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.add-users-container').length) {
                    $('.users-search-results').hide();
                }
            });

            // Add user when clicked
            $(document).on('click', '.user-search-item', function() {
                var $item = $(this);
                var userId = $item.data('user-id');
                var userName = $item.find('div:first span:first').text();
                var userCode = $item.data('user-code');

                addUser(userId, userName, userCode);
                $item.hide();
                $('#user-search-input').val('');
                $('.users-search-results').hide();
            });

            // Remove user when X is clicked
            $(document).on('click', '.remove-user', function() {
                var $tag = $(this).closest('.selected-user-tag');
                var userId = $tag.data('user-id');

                removeUser(userId);
            });

            // Select all users
            $('#select-all-users').on('click', function() {
                allUsers.forEach(function(user) {
                    // Validate user object before processing
                    if (user && user.ID && user.display_name && !isUserSelected(user.ID)) {
                        var userCode = user.lift_docs_user_code || '';
                        addUser(user.ID, user.display_name, userCode);
                    }
                });
                updateSearchResults();
            });

            // Clear all users
            $('#clear-all-users').on('click', function() {
                $('.selected-user-tag').remove();
                $('.user-search-item').show();
                updateNoUsersMessage();
                updateUsersCount();
            });

            function addUser(userId, userName, userCode) {
                // Validate input parameters to prevent undefined values
                if (!userId || !userName) {
                    console.warn('addUser called with invalid parameters:', userId, userName, userCode);
                    return;
                }

                if (isUserSelected(userId)) {
                    return;
                }

                var userCodeDisplay = userCode ? '<small style="opacity: 0.8; margin-left: 4px; font-family: monospace;">(' + userCode + ')</small>' : '';

                var userTag = $('<span class="selected-user-tag" data-user-id="' + userId + '" style="display: inline-block; background: #0073aa; color: #fff; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">' +
                    userName + userCodeDisplay +
                    '<span class="remove-user" style="margin-left: 5px; cursor: pointer; font-weight: bold;">&times;</span>' +
                    '<input type="hidden" name="lift_doc_assigned_users[]" value="' + userId + '">' +
                    '</span>');

                $('.selected-users-list').append(userTag);
                updateNoUsersMessage();
                updateUsersCount();
            }

            function removeUser(userId) {
                $('.selected-user-tag[data-user-id="' + userId + '"]').remove();
                $('.user-search-item[data-user-id="' + userId + '"]').show();
                updateNoUsersMessage();
                updateUsersCount();
            }

            function isUserSelected(userId) {
                return $('.selected-user-tag[data-user-id="' + userId + '"]').length > 0;
            }

            function updateNoUsersMessage() {
                var selectedCount = $('.selected-user-tag').length;
                if (selectedCount > 0) {
                    $('.no-users-selected').hide();
                } else {
                    $('.no-users-selected').show();
                }
            }

            function updateUsersCount() {
                var selectedCount = $('.selected-user-tag').length;
                $('#users-count').text('<?php _e('Total Document Users:', 'lift-docs-system'); ?> ' + totalUsers + ' | <?php _e('Selected:', 'lift-docs-system'); ?> ' + selectedCount);
            }

            function updateSearchResults() {
                $('.user-search-item').each(function() {
                    var userId = $(this).data('user-id');
                    if (isUserSelected(userId)) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            }

            // Initialize
            updateNoUsersMessage();
            updateUsersCount();
            updateSearchResults();
        });
        </script>

        <style>
        .user-search-item:hover {
            background-color: #f0f8ff;
        }

        .selected-user-tag {
            /* No animation */
        }

        /* Animation removed */

        .remove-user:hover {
            background-color: rgba(255,255,255,0.2);
            border-radius: 50%;
        }

        #user-search-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 2px rgba(0, 115, 170, 0.8);
            outline: none;
        }

        .users-search-results {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        </style>
        <?php
    }

    /**
     * Document forms meta box
     */
    public function document_forms_meta_box($post) {
        wp_nonce_field('lift_docs_forms_meta_box', 'lift_docs_forms_meta_box_nonce');

        // Get assigned forms
        $assigned_forms = get_post_meta($post->ID, '_lift_doc_assigned_forms', true);
        if (!is_array($assigned_forms)) {
            $assigned_forms = array();
        }

        // Get all available forms
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $available_forms = $wpdb->get_results("SELECT id, name, description FROM $forms_table WHERE status = 'active' ORDER BY name ASC");

        ?>
        <div class="document-forms">
            <p><strong><?php _e('Assign Forms to Document', 'lift-docs-system'); ?></strong></p>
            <?php if (empty($available_forms)): ?>
                <p class="notice notice-warning inline">
                    <?php printf(
                        __('No forms available. %sCreate forms%s first to assign them to this document.', 'lift-docs-system'),
                        '<a href="' . admin_url('admin.php?page=lift-forms-builder') . '">',
                        '</a>'
                    ); ?>
                </p>
            <?php else: ?>
                <!-- Selected Forms Display -->
                <div class="selected-forms-container" style="margin-bottom: 15px;">
                    <label><strong><?php _e('Selected Forms:', 'lift-docs-system'); ?></strong></label>
                    <div class="selected-forms-list" style="min-height: 40px; border: 1px solid #ddd; border-radius: 3px; padding: 8px; background: #f9f9fa;">
                        <span class="no-forms-selected" style="color: #666; font-style: italic;"><?php _e('No forms selected', 'lift-docs-system'); ?></span>
                    </div>
                </div>

                <!-- Search and Add Forms -->
                <div class="add-forms-container">
                    <label for="form-search-input"><strong><?php _e('Add Forms:', 'lift-docs-system'); ?></strong></label>
                    <input type="text" id="form-search-input" placeholder="<?php _e('Search forms by name or description...', 'lift-docs-system'); ?>" style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px;">

                    <div class="forms-search-results" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px; background: #fff; display: none;">
                        <!-- Search results will be populated here -->
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <button type="button" id="select-all-forms" class="button button-secondary" style="margin-right: 10px;">
                        <?php _e('Select All', 'lift-docs-system'); ?>
                    </button>
                    <button type="button" id="clear-all-forms" class="button button-secondary">
                        <?php _e('Clear All', 'lift-docs-system'); ?>
                    </button>
                </div>

                <p class="description" style="margin-top: 10px;">
                    <span id="forms-count">
                        <?php printf(
                            __('Total Forms: %d | Selected: %d', 'lift-docs-system'),
                            count($available_forms),
                            count($assigned_forms)
                        ); ?>
                    </span>
                </p>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var allForms = <?php echo json_encode($available_forms); ?>;
            var totalForms = allForms.length;
            var selectedForms = <?php echo json_encode($assigned_forms); ?>;

            // Initialize selected forms display
            updateSelectedFormsDisplay();
            updateFormsCount();

            // Search functionality
            $('#form-search-input').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var results = $('.forms-search-results');

                if (searchTerm.length === 0) {
                    results.hide();
                    return;
                }

                var filteredForms = allForms.filter(function(form) {
                    return !isFormSelected(form.id) &&
                           (form.name.toLowerCase().includes(searchTerm) ||
                            (form.description && form.description.toLowerCase().includes(searchTerm)));
                });

                if (filteredForms.length > 0) {
                    var html = '';
                    filteredForms.forEach(function(form) {
                        html += '<div class="form-search-item" data-form-id="' + form.id + '" style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer;" title="Click to select this form">';
                        html += '<div style="font-weight: 500; display: flex; justify-content: space-between; align-items: center;">';
                        html += '<span>' + form.name + '</span>';
                        html += '</div>';
                        if (form.description) {
                            html += '<div style="font-size: 12px; color: #666;">' + form.description + '</div>';
                        }
                        html += '</div>';
                    });
                    results.html(html).show();
                } else {
                    results.html('<div style="padding: 10px; color: #666; font-style: italic;">' +
                                '<?php _e('No forms found or all forms already selected', 'lift-docs-system'); ?></div>').show();
                }
            });

            // Hide search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.add-forms-container').length) {
                    $('.forms-search-results').hide();
                }
            });

            // Add form when clicked
            $(document).on('click', '.form-search-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var formId = parseInt($(this).data('form-id'));
                var formData = allForms.find(function(f) { return f.id == formId; });

                if (formData && !isFormSelected(formId)) {
                    selectedForms.push(formId);
                    updateSelectedFormsDisplay();
                    updateFormsCount();
                    $('#form-search-input').val('');
                    $('.forms-search-results').hide();
                }
            });

            // Remove form when X is clicked
            $(document).on('click', '.remove-form', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var formId = parseInt($(this).data('form-id'));
                selectedForms = selectedForms.filter(function(id) {
                    return id != formId;
                });
                updateSelectedFormsDisplay();
                updateFormsCount();
            });

            // Select all forms
            $('#select-all-forms').on('click', function() {
                allForms.forEach(function(form) {
                    if (!isFormSelected(form.id)) {
                        addForm(form.id, form.name, form.description);
                    }
                });
                updateSelectedFormsDisplay();
                updateFormsCount();
            });

            // Clear all forms
            $('#clear-all-forms').on('click', function() {
                selectedForms = [];
                updateSelectedFormsDisplay();
                updateFormsCount();
            });

            function addForm(formId, formName, formDescription) {
                formId = parseInt(formId);
                if (!isFormSelected(formId)) {
                    selectedForms.push(formId);
                    updateSelectedFormsDisplay();
                    updateFormsCount();
                }
            }

            function removeForm(formId) {
                formId = parseInt(formId);
                selectedForms = selectedForms.filter(function(id) {
                    return parseInt(id) !== formId;
                });
                updateSelectedFormsDisplay();
                updateFormsCount();
            }

            function isFormSelected(formId) {
                return selectedForms.some(function(id) {
                    return id == formId;
                });
            }

            function updateSelectedFormsDisplay() {
                var container = $('.selected-forms-list');
                var noFormsMessage = $('.no-forms-selected');

                // Clear existing tags and hidden inputs
                container.find('.selected-form-tag').remove();

                if (selectedForms.length === 0) {
                    noFormsMessage.show();
                } else {
                    noFormsMessage.hide();

                    selectedForms.forEach(function(formId) {
                        var formData = allForms.find(function(f) { return f.id == formId; });
                        if (formData) {
                            var tagHtml = '<span class="selected-form-tag" data-form-id="' + formId + '" style="display: inline-block; background: #0073aa; color: #fff; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">';
                            tagHtml += formData.name;
                            tagHtml += '<span class="remove-form" data-form-id="' + formId + '" style="margin-left: 5px; cursor: pointer; font-weight: bold; user-select: none;" title="Remove this form">&times;</span>';
                            tagHtml += '<input type="hidden" name="lift_doc_assigned_forms[]" value="' + formId + '">';
                            tagHtml += '</span>';
                            container.append(tagHtml);
                        }
                    });
                }
            }

            function updateFormsCount() {
                $('#forms-count').html('<?php _e('Total Forms:', 'lift-docs-system'); ?> ' + totalForms + ' | <?php _e('Selected:', 'lift-docs-system'); ?> ' + selectedForms.length);
            }
        });
        </script>

        <style>
        .form-search-item {
            transition: background-color 0.2s ease;
        }

        .form-search-item:hover {
            background: #f0f0f1 !important;
        }

        .form-search-item:active {
            background: #e0e0e0 !important;
        }

        .remove-form {
            transition: color 0.2s ease;
        }

        .remove-form:hover {
            color: #dc3232 !important;
            transform: scale(1.2);
        }

        #form-search-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
        }

        .forms-search-results {
            border: 1px solid #ddd;
            border-radius: 3px;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            position: relative;
            z-index: 1000;
        }

        .add-forms-container {
            position: relative;
        }

        .selected-forms-container {
            margin-bottom: 15px;
        }

        .selected-forms-list {
            min-height: 40px;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px;
            background: #f9f9f9;
            line-height: 1.4;
        }

        .no-forms-selected {
            color: #666;
            font-style: italic;
            display: block;
        }

        .selected-form-tag {
            display: inline-block;
            background: #0073aa;
            color: #fff;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 3px;
            font-size: 12px;
            cursor: default;
        }

        .selected-form-tag .remove-form {
            margin-left: 5px;
            cursor: pointer;
            font-weight: bold;
            user-select: none;
        }
        }

        .no-forms-selected {
            color: #666;
            font-style: italic;
            display: block;
        }
        </style>
        <?php
    }

    /**
     * Archive settings meta box
     */
    public function document_archive_meta_box($post) {
        wp_nonce_field('lift_docs_archive_meta_box', 'lift_docs_archive_meta_box_nonce');

        $is_archived = get_post_meta($post->ID, '_lift_doc_archived', true);
        $is_archived = ($is_archived === '1' || $is_archived === 1);
        ?>
        <div style="padding: 10px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox"
                       name="_lift_doc_archived"
                       value="1"
                       <?php checked($is_archived); ?>
                       style="transform: scale(1.2);">
                <span style="font-weight: 500; color: <?php echo $is_archived ? '#e74c3c' : '#27ae60'; ?>;">
                    <?php if ($is_archived): ?>
                        <span class="dashicons dashicons-archive" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php _e('This document is archived', 'lift-docs-system'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-yes-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php _e('This document is active', 'lift-docs-system'); ?>
                    <?php endif; ?>
                </span>
            </label>

            <div style="margin-top: 10px; padding: 10px; background: #f7f7f7; border-left: 4px solid #0073aa; font-size: 13px;">
                <strong><?php _e('Note:', 'lift-docs-system'); ?></strong>
                <?php _e('Archived documents will not appear in the default document list or frontend pages. They can only be accessed directly or viewed in the Archived Documents section.', 'lift-docs-system'); ?>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('input[name="_lift_doc_archived"]').on('change', function() {
                    var $this = $(this);
                    var $span = $this.next('span');
                    var $icon = $span.find('.dashicons');

                    if ($this.is(':checked')) {
                        $span.css('color', '#e74c3c');
                        $icon.removeClass('dashicons-yes-alt').addClass('dashicons-archive');
                        $span.find('text').text('<?php _e('This document is archived', 'lift-docs-system'); ?>');
                    } else {
                        $span.css('color', '#27ae60');
                        $icon.removeClass('dashicons-archive').addClass('dashicons-yes-alt');
                        $span.find('text').text('<?php _e('This document is active', 'lift-docs-system'); ?>');
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check document details nonce
        if (isset($_POST['lift_docs_meta_box_nonce']) && wp_verify_nonce($_POST['lift_docs_meta_box_nonce'], 'lift_docs_meta_box')) {
            if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
                if (current_user_can('edit_post', $post_id)) {
                    // Handle multiple file URLs
                    if (isset($_POST['lift_doc_file_urls']) && is_array($_POST['lift_doc_file_urls'])) {
                        $file_urls = array_filter(array_map('sanitize_url', $_POST['lift_doc_file_urls']));

                        // Save multiple URLs
                        update_post_meta($post_id, '_lift_doc_file_urls', $file_urls);

                        // For backward compatibility, also save first URL as single file URL
                        if (!empty($file_urls)) {
                            update_post_meta($post_id, '_lift_doc_file_url', $file_urls[0]);
                        } else {
                            delete_post_meta($post_id, '_lift_doc_file_url');
                        }
                    } else {
                        delete_post_meta($post_id, '_lift_doc_file_urls');
                        delete_post_meta($post_id, '_lift_doc_file_url');
                    }
                }
            }
        }

        // Check document assignments nonce
        if (isset($_POST['lift_docs_assignments_meta_box_nonce']) && wp_verify_nonce($_POST['lift_docs_assignments_meta_box_nonce'], 'lift_docs_assignments_meta_box')) {
            if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
                if (current_user_can('edit_post', $post_id)) {
                    // Handle assigned users
                    if (isset($_POST['lift_doc_assigned_users']) && is_array($_POST['lift_doc_assigned_users'])) {
                        $assigned_users = array_map('intval', $_POST['lift_doc_assigned_users']);

                        // Validate that all assigned users have the documents_user role
                        $valid_users = array();
                        foreach ($assigned_users as $user_id) {
                            $user = get_user_by('id', $user_id);
                            if ($user && in_array('documents_user', $user->roles)) {
                                $valid_users[] = $user_id;
                            }
                        }

                        update_post_meta($post_id, '_lift_doc_assigned_users', $valid_users);
                    } else {
                        delete_post_meta($post_id, '_lift_doc_assigned_users');
                    }
                }
            }
        }

        // Check document forms nonce
        if (isset($_POST['lift_docs_forms_meta_box_nonce']) && wp_verify_nonce($_POST['lift_docs_forms_meta_box_nonce'], 'lift_docs_forms_meta_box')) {
            if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
                if (current_user_can('edit_post', $post_id)) {
                    // Handle assigned forms
                    if (isset($_POST['lift_doc_assigned_forms']) && is_array($_POST['lift_doc_assigned_forms'])) {
                        $assigned_forms = array_map('intval', $_POST['lift_doc_assigned_forms']);

                        // Validate that all assigned forms exist
                        $valid_forms = array();
                        global $wpdb;
                        $forms_table = $wpdb->prefix . 'lift_forms';

                        foreach ($assigned_forms as $form_id) {
                            $form_exists = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $forms_table WHERE id = %d AND status = 'active'",
                                $form_id
                            ));

                            if ($form_exists) {
                                $valid_forms[] = $form_id;
                            }
                        }

                        update_post_meta($post_id, '_lift_doc_assigned_forms', $valid_forms);
                    } else {
                        delete_post_meta($post_id, '_lift_doc_assigned_forms');
                    }
                }
            }
        }

        // Check document archive nonce
        if (isset($_POST['lift_docs_archive_meta_box_nonce']) && wp_verify_nonce($_POST['lift_docs_archive_meta_box_nonce'], 'lift_docs_archive_meta_box')) {
            if (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) {
                if (current_user_can('edit_post', $post_id)) {
                    // Handle archive status
                    if (isset($_POST['_lift_doc_archived']) && $_POST['_lift_doc_archived'] === '1') {
                        update_post_meta($post_id, '_lift_doc_archived', '1');
                    } else {
                        update_post_meta($post_id, '_lift_doc_archived', '0');
                    }
                }
            }
        }
    }

    /**
     * Clean up old layout settings meta (since they're now global)
     */
    public function cleanup_old_layout_settings() {
        // Only run once
        if (get_option('lift_docs_layout_cleanup_done', false)) {
            return;
        }

        global $wpdb;

        // Remove all _lift_doc_layout_settings meta
        $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => '_lift_doc_layout_settings'),
            array('%s')
        );

        // Mark cleanup as done
        update_option('lift_docs_layout_cleanup_done', true);
    }

    /**
     * Create custom document roles and capabilities
     */
    public function create_document_roles() {
        // Only run once
        if (get_option('lift_docs_roles_created', false)) {
            return;
        }

        // Create Documents role with specific capabilities
        $documents_capabilities = array(
            'read' => true,
            'view_lift_documents' => true,
            'download_lift_documents' => true,
            'read_lift_document' => true,
            'read_private_lift_documents' => true,
        );

        add_role(
            'documents_user',
            __('Documents User', 'lift-docs-system'),
            $documents_capabilities
        );

        // Add document capabilities to existing roles

        // Editor role - can manage documents
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('view_lift_documents');
            $editor->add_cap('download_lift_documents');
            $editor->add_cap('read_lift_document');
            $editor->add_cap('read_private_lift_documents');
            $editor->add_cap('edit_lift_documents');
            $editor->add_cap('edit_lift_document');
            $editor->add_cap('edit_others_lift_documents');
            $editor->add_cap('edit_published_lift_documents');
            $editor->add_cap('publish_lift_documents');
            $editor->add_cap('delete_lift_documents');
            $editor->add_cap('delete_lift_document');
            $editor->add_cap('delete_others_lift_documents');
            $editor->add_cap('delete_published_lift_documents');
            $editor->add_cap('manage_lift_doc_categories');
            $editor->add_cap('edit_lift_doc_categories');
            $editor->add_cap('delete_lift_doc_categories');
            $editor->add_cap('assign_lift_doc_categories');
        }

        // Administrator role - full access
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('view_lift_documents');
            $admin->add_cap('download_lift_documents');
            $admin->add_cap('read_lift_document');
            $admin->add_cap('read_private_lift_documents');
            $admin->add_cap('edit_lift_documents');
            $admin->add_cap('edit_lift_document');
            $admin->add_cap('edit_others_lift_documents');
            $admin->add_cap('edit_published_lift_documents');
            $admin->add_cap('edit_private_lift_documents');
            $admin->add_cap('publish_lift_documents');
            $admin->add_cap('delete_lift_documents');
            $admin->add_cap('delete_lift_document');
            $admin->add_cap('delete_others_lift_documents');
            $admin->add_cap('delete_published_lift_documents');
            $admin->add_cap('delete_private_lift_documents');
            $admin->add_cap('manage_lift_doc_categories');
            $admin->add_cap('edit_lift_doc_categories');
            $admin->add_cap('delete_lift_doc_categories');
            $admin->add_cap('assign_lift_doc_categories');
        }

        // Mark roles as created
        update_option('lift_docs_roles_created', true);
    }

    /**
     * Get Views
     */
    private function get_total_views() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';

        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name WHERE action = 'view'"
        );

        return $total ? $total : 0;
    }

    /**
     * Display recent documents
     */
    private function display_recent_documents() {
        $recent_docs = get_posts(array(
            'post_type' => 'lift_document',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ));

        if ($recent_docs) {
            echo '<ul>';
            foreach ($recent_docs as $doc) {
                echo '<li>';
                echo '<a href="' . get_edit_post_link($doc->ID) . '">' . esc_html($doc->post_title) . '</a>';
                echo ' - ' . get_the_date('', $doc->ID);
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No documents found.', 'lift-docs-system') . '</p>';
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $pagenow, $post_type;

        // Enqueue Font Awesome for all admin pages of this plugin
        wp_enqueue_style('font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');

        // Enqueue LIFT Forms scripts on forms pages
        if (strpos($hook, 'lift-forms') !== false && class_exists('LIFT_Forms')) {
            $lift_forms = new LIFT_Forms();
            $lift_forms->enqueue_admin_scripts($hook);
        }

        // Load on document edit/add pages for media uploader
        if (($pagenow == 'post.php' || $pagenow == 'post-new.php') && $post_type == 'lift_document') {
            // Enqueue WordPress Media Library
            wp_enqueue_media();
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
        }

        // Only load modal scripts on document list page
        if ('edit.php' !== $hook || !isset($_GET['post_type']) || $_GET['post_type'] !== 'lift_document') {
            return;
        }

        // Enqueue admin styles for document list
        wp_enqueue_style('lift-docs-admin', plugin_dir_url(__FILE__) . '../assets/css/admin.css', array(), '1.0.0');

        wp_enqueue_script('lift-docs-admin-modal', plugin_dir_url(__FILE__) . '../assets/js/admin-modal.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('lift-docs-admin-modal', plugin_dir_url(__FILE__) . '../assets/css/admin-modal.css', array(), '1.0.0');

        wp_localize_script('lift-docs-admin-modal', 'liftDocsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('get_admin_document_details'),
            'statusNonce' => wp_create_nonce('update_document_status'),
            'strings' => array(
                'documentDetails' => __('Document Details', 'lift-docs-system'),
                'viewDetails' => __('Document Details', 'lift-docs-system'),
                'secureView' => __('Secure View', 'lift-docs-system'),
                'downloadUrl' => __('Download URL', 'lift-docs-system'),
                'secureDownloadUrl' => __('Secure Download URL', 'lift-docs-system'),
                'shortcode' => __('Shortcode', 'lift-docs-system'),
                'views' => __('Views', 'lift-docs-system'),
                'downloads' => __('Downloads', 'lift-docs-system'),
                'fileSize' => __('File Size', 'lift-docs-system'),
                'copyToClipboard' => __('Copy to clipboard', 'lift-docs-system'),
                'copied' => __('Copied!', 'lift-docs-system'),
                'preview' => __('Preview', 'lift-docs-system'),
                'viewOnline' => __('View Online', 'lift-docs-system'),
                'close' => __('Close', 'lift-docs-system'),
                'statusUpdated' => __('Status updated successfully', 'lift-docs-system'),
                'statusUpdateError' => __('Error updating status', 'lift-docs-system')
            )
        ));

        // Add inline script for status dropdown functionality
        wp_add_inline_script('lift-docs-admin-modal', '
            jQuery(document).ready(function($) {
                // Handle status dropdown change
                $(document).on("change", ".lift-status-dropdown", function() {
                    var $dropdown = $(this);
                    var postId = $dropdown.data("post-id");
                    var newStatus = $dropdown.val();
                    var oldColor = $dropdown.css("background-color");
                    var newColor = $dropdown.find("option:selected").data("color");

                    // Update dropdown color immediately
                    $dropdown.css("background-color", newColor);

                    // Show loading state
                    $dropdown.prop("disabled", true);

                    $.ajax({
                        url: liftDocsAdmin.ajaxUrl,
                        type: "POST",
                        data: {
                            action: "update_document_status",
                            document_id: postId,
                            status: newStatus,
                            nonce: liftDocsAdmin.statusNonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                var successMsg = $("<div class=\"notice notice-success is-dismissible\" style=\"position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 300px;\"><p>" + liftDocsAdmin.strings.statusUpdated + "</p></div>");
                                $("body").append(successMsg);

                                // Auto-dismiss after 3 seconds
                                setTimeout(function() {
                                    successMsg.fadeOut().remove();
                                }, 3000);
                            } else {
                                // Revert on error
                                $dropdown.css("background-color", oldColor);
                                alert(response.data || liftDocsAdmin.strings.statusUpdateError);
                            }
                        },
                        error: function() {
                            // Revert on error
                            $dropdown.css("background-color", oldColor);
                            alert(liftDocsAdmin.strings.statusUpdateError);
                        },
                        complete: function() {
                            $dropdown.prop("disabled", false);
                        }
                    });
                });
            });
        ');
    }

    /**
     * Add document details modal to admin footer
     */
    public function add_document_details_modal() {
        $current_screen = get_current_screen();

        // Only add modal on document list page
        if (!$current_screen || $current_screen->id !== 'edit-lift_document') {
            return;
        }
        ?>

        <!-- Document Details Modal -->
        <div id="lift-document-details-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2 id="lift-modal-title"><?php _e('Document Details', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>

                <div class="lift-modal-body" id="lift-modal-body">
                    <!-- Content will be loaded via AJAX -->
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #0073aa;"></i><br><br>
                        Loading document details...
                    </div>
                </div>

                <div class="lift-modal-footer">
                    <button type="button" class="button button-primary" onclick="jQuery('#lift-document-details-modal').hide(); jQuery('#lift-modal-backdrop').hide();">
                        <?php _e('Close', 'lift-docs-system'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>

        <!-- Submission Details Modal (WordPress Style) -->
        <div id="submission-detail-modal-from-doc" class="wp-core-ui" style="display: none;">
            <div class="media-modal wp-core-ui">
                <button type="button" class="media-modal-close" onclick="closeSubmissionModalFromDoc()">
                    ×
                </button>
                <div class="media-modal-content">
                    <div class="media-frame mode-select wp-core-ui">
                        <div class="media-frame-title">
                            <h1><?php _e('Submission Details', 'lift-docs-system'); ?></h1>
                        </div>
                        <div class="media-frame-content">
                            <div id="submission-detail-content-from-doc">
                                <div class="submission-loading">
                                    <div class="spinner is-active"></div>
                                    <p><?php _e('Loading submission details...', 'lift-docs-system'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="media-frame-toolbar">
                            <div class="media-toolbar">
                                <div class="media-toolbar-secondary">
                                    <button type="button" class="button" onclick="closeSubmissionModalFromDoc()">
                                        <?php _e('Close', 'lift-docs-system'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="media-modal-backdrop"></div>
        </div>

        <?php
    }

    /**
     * Handle user role update
     */
    private function handle_user_role_update() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to manage users.', 'lift-docs-system'));
        }

        if (!isset($_POST['user_id']) || !isset($_POST['new_role'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Invalid request.', 'lift-docs-system') . '</p></div>';
            });
            return;
        }

        $user_id = intval($_POST['user_id']);
        $new_role = sanitize_text_field($_POST['new_role']);

        $user = get_user_by('id', $user_id);
        if (!$user) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('User not found.', 'lift-docs-system') . '</p></div>';
            });
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'update_user_role_' . $user_id)) {
            wp_die(__('Security check failed.', 'lift-docs-system'));
        }

        $user->set_role($new_role);

        add_action('admin_notices', function() use ($user) {
            echo '<div class="notice notice-success"><p>' .
                 sprintf(__('User %s role updated successfully.', 'lift-docs-system'), $user->display_name) .
                 '</p></div>';
        });
    }

    /**
     * Display users summary
     */
    private function display_users_summary() {
        $users_by_role = array();

        // Get all users with document-related capabilities
        $document_users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'documents_user',
                    'compare' => 'LIKE'
                )
            )
        ));

        $admin_users = get_users(array('role' => 'administrator'));
        $editor_users = get_users(array('role' => 'editor'));

        ?>
        <div class="users-stats">
            <div class="stat-item">
                <h3><?php _e('Documents Users', 'lift-docs-system'); ?></h3>
                <p class="stat-number"><?php echo count($document_users); ?></p>
            </div>
            <div class="stat-item">
                <h3><?php _e('Administrators', 'lift-docs-system'); ?></h3>
                <p class="stat-number"><?php echo count($admin_users); ?></p>
            </div>
            <div class="stat-item">
                <h3><?php _e('Editors', 'lift-docs-system'); ?></h3>
                <p class="stat-number"><?php echo count($editor_users); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Display users with documents role
     */
    private function display_documents_users() {
        $document_users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'documents_user',
                    'compare' => 'LIKE'
                )
            )
        ));

        if (empty($document_users)) {
            echo '<p>' . __('No users with Documents role found.', 'lift-docs-system') . '</p>';
            return;
        }

        if (empty($document_users)) {
            echo '<p>' . __('No users with Documents role found.', 'lift-docs-system') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'lift-docs-system'); ?></th>
                    <th><?php _e('Email', 'lift-docs-system'); ?></th>
                    <th><?php _e('User Code', 'lift-docs-system'); ?></th>
                    <th><?php _e('Assigned Documents', 'lift-docs-system'); ?></th>
                    <th><?php _e('Registration Date', 'lift-docs-system'); ?></th>
                    <th><?php _e('Actions', 'lift-docs-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($document_users as $user): ?>
                <?php
                $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);

                // Count assigned documents for this user
                $assigned_docs = get_posts(array(
                    'post_type' => 'lift_document',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_lift_doc_assigned_users',
                            'value' => sprintf(':%d;', $user->ID),
                            'compare' => 'LIKE'
                        )
                    ),
                    'fields' => 'ids'
                ));
                $assigned_count = count($assigned_docs);
                ?>
                <tr id="user-row-<?php echo $user->ID; ?>">
                    <td>
                        <strong><?php echo esc_html($user->display_name); ?></strong><br>
                        <span class="description"><?php echo esc_html($user->user_login); ?></span>
                    </td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td>
                        <div id="user-code-cell-<?php echo $user->ID; ?>">
                            <?php if ($user_code): ?>
                                <strong style="color: #0073aa; font-family: monospace;"><?php echo esc_html($user_code); ?></strong><br>
                                <button type="button" class="button button-secondary generate-user-code-btn-mgmt"
                                        data-user-id="<?php echo $user->ID; ?>"
                                        style="margin-top: 5px; font-size: 11px;">
                                    <?php _e('Generate New Code', 'lift-docs-system'); ?>
                                </button>
                            <?php else: ?>
                                <span style="color: #d63638; font-style: italic;"><?php _e('No Code', 'lift-docs-system'); ?></span><br>
                                <button type="button" class="button button-primary generate-user-code-btn-mgmt"
                                        data-user-id="<?php echo $user->ID; ?>"
                                        style="margin-top: 5px; font-size: 11px;">
                                    <?php _e('Generate Code', 'lift-docs-system'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="text-align: center;">
                            <?php if ($assigned_count > 0): ?>
                                <strong style="color: #0073aa; font-size: 18px;"><?php echo $assigned_count; ?></strong>
                                <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                    <?php echo _n('document', 'documents', $assigned_count, 'lift-docs-system'); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #d63638; font-style: italic;">
                                    <?php _e('No documents', 'lift-docs-system'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                    <td>
                        <div class="user-actions" style="display: flex; flex-direction: column; gap: 5px;">
                            <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button">
                                <?php _e('Edit User', 'lift-docs-system'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=lift_document&assigned_user=' . $user->ID); ?>"
                               class="button button-primary"
                               style="background: #0073aa; border-color: #0073aa; color: white;"
                               title="<?php echo sprintf(__('View all %d documents assigned to %s', 'lift-docs-system'), $assigned_count, $user->display_name); ?>">
                                <span class="dashicons dashicons-text-page" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;"></span>
                                <?php echo sprintf(__('View Documents (%d)', 'lift-docs-system'), $assigned_count); ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle Generate User Code button click in Document Users Management
            $(document).on('click', '.generate-user-code-btn-mgmt', function(e) {
                e.preventDefault();

                var $button = $(this);
                var userId = $button.data('user-id');
                var $cell = $('#user-code-cell-' + userId);
                var isRegenerate = $button.hasClass('button-secondary');
                var originalText = $button.text();

                // Confirm if regenerating existing code
                if (isRegenerate) {
                    var confirmMessage = '<?php _e("Are you sure you want to generate a new User Code? This will replace the existing code and may affect document access.", "lift-docs-system"); ?>';
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                }

                // Disable button and show loading
                $button.prop('disabled', true).text('<?php _e("Generating...", "lift-docs-system"); ?>');

                // AJAX request to generate code
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_user_code',
                        user_id: userId,
                        nonce: '<?php echo wp_create_nonce('generate_user_code'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.code) {
                            // Update the cell with new code and regenerate button
                            var newHtml = '<strong style="color: #0073aa; font-family: monospace;">' + response.data.code + '</strong><br>' +
                                         '<button type="button" class="button button-secondary generate-user-code-btn-mgmt" ' +
                                         'data-user-id="' + userId + '" ' +
                                         'style="margin-top: 5px; font-size: 11px;"><?php _e("Generate New Code", "lift-docs-system"); ?></button>';

                            $cell.html(newHtml);

                            // Show success message
                            var message = isRegenerate ?
                                '<?php _e("User Code regenerated successfully!", "lift-docs-system"); ?>' :
                                '<?php _e("User Code generated successfully!", "lift-docs-system"); ?>';
                            var successMsg = $('<div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 300px;"><p>' + message + '</p></div>');
                            $('body').append(successMsg);

                            // Auto-dismiss success message after 3 seconds
                            setTimeout(function() {
                                successMsg.hide().remove();
                            }, 3000);

                        } else {
                            // Show error message
                            var errorMsg = response.data || '<?php _e("Error generating User Code. Please try again.", "lift-docs-system"); ?>';
                            alert(errorMsg);
                            $button.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show detailed error message
                        var errorMessage = '<?php _e("Error generating User Code. Please try again.", "lift-docs-system"); ?> Status: ' + status;
                        alert(errorMessage);
                        $button.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>

        <style type="text/css">
        .generate-user-code-btn-mgmt {
            font-size: 11px !important;
            padding: 2px 8px !important;
            height: auto !important;
            line-height: 1.2 !important;
        }

        /* Primary button for users without code */
        .generate-user-code-btn-mgmt.button-primary {
            background: #00a32a !important;
            border-color: #00a32a !important;
            color: #fff !important;
        }

        .generate-user-code-btn-mgmt.button-primary:hover {
            background: #008a20 !important;
            border-color: #008a20 !important;
        }

        /* Secondary button for users with existing code */
        .generate-user-code-btn-mgmt.button-secondary {
            background: #f39c12 !important;
            border-color: #f39c12 !important;
            color: #fff !important;
        }

        .generate-user-code-btn-mgmt.button-secondary:hover {
            background: #e67e22 !important;
            border-color: #e67e22 !important;
        }

        .generate-user-code-btn-mgmt:disabled {
            background: #ddd !important;
            border-color: #ddd !important;
            color: #999 !important;
            cursor: not-allowed !important;
        }
        </style>
        <?php
    }

    /**
     * Display user role form
     */
    private function display_user_role_form() {
        // Get all users except those who already have documents access
        $all_users = get_users();
        $available_users = array();

        foreach ($all_users as $user) {
            if (!user_can($user->ID, 'view_lift_documents') && !in_array('documents_user', $user->roles)) {
                $available_users[] = $user;
            }
        }

        if (empty($available_users)) {
            echo '<p>' . __('All users already have document access or higher roles.', 'lift-docs-system') . '</p>';
            return;
        }

        ?>
        <form method="post">
            <?php wp_nonce_field('update_user_role_bulk'); ?>
            <input type="hidden" name="action" value="update_user_role">
            <input type="hidden" name="new_role" value="documents_user">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="user_id"><?php _e('Select User', 'lift-docs-system'); ?></label>
                    </th>
                    <td>
                        <select name="user_id" id="user_id" required>
                            <option value=""><?php _e('-- Select User --', 'lift-docs-system'); ?></option>
                            <?php foreach ($available_users as $user): ?>
                            <option value="<?php echo $user->ID; ?>">
                                <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Grant document access to the selected user.', 'lift-docs-system'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Grant Document Access', 'lift-docs-system')); ?>
        </form>

        <div style="margin-top: 30px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa;">
            <h4><?php _e('Documents User Role Capabilities:', 'lift-docs-system'); ?></h4>
            <ul>
                <li><?php _e('View all published documents', 'lift-docs-system'); ?></li>
                <li><?php _e('Download Attached files', 'lift-docs-system'); ?></li>
                <li><?php _e('Access secure document links', 'lift-docs-system'); ?></li>
                <li><?php _e('View document analytics (own activity)', 'lift-docs-system'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Generate unique user code
     */
    public function generate_unique_user_code() {
        $attempts = 0;
        $max_attempts = 50;

        do {
            // Generate random code: 6-8 characters, alphanumeric
            $length = rand(6, 8);
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }

            // Check if code already exists
            $existing_user = get_users(array(
                'meta_key' => 'lift_docs_user_code',
                'meta_value' => $code,
                'meta_compare' => '='
            ));

            $attempts++;

        } while (!empty($existing_user) && $attempts < $max_attempts);

        return $code;
    }

    /**
     * Add user code field to user profile (removed - managed only in users list)
     */
    public function add_user_code_field($user) {
        // User Code management removed from user profile
        // Now handled only in Users list page
        return;
    }

    /**
     * Save user code field
     */
    public function save_user_code_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (isset($_POST['lift_docs_user_code'])) {
            $user_code = sanitize_text_field($_POST['lift_docs_user_code']);

            // Validate code format (6-8 alphanumeric characters)
            if (preg_match('/^[A-Z0-9]{6,8}$/', $user_code)) {
                // Check for duplicates (excluding current user)
                $existing_user = get_users(array(
                    'meta_key' => 'lift_docs_user_code',
                    'meta_value' => $user_code,
                    'meta_compare' => '=',
                    'exclude' => array($user_id)
                ));

                if (empty($existing_user)) {
                    update_user_meta($user_id, 'lift_docs_user_code', $user_code);
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('User code already exists. Please choose a different code.', 'lift-docs-system') . '</p></div>';
                    });
                }
            } else if (!empty($user_code)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __('Invalid user code format. Use 6-8 alphanumeric characters only.', 'lift-docs-system') . '</p></div>';
                });
            }
        }
    }

    /**
     * Generate user code when user registers
     */
    public function generate_user_code_on_register($user_id) {
        $user = get_user_by('id', $user_id);
        if ($user && in_array('documents_user', $user->roles)) {
            $this->generate_and_save_user_code($user_id);
        }
    }

    /**
     * Generate user code when role changes to documents_user
     */
    public function generate_user_code_on_role_change($user_id, $role, $old_roles) {
        if ($role === 'documents_user') {
            $existing_code = get_user_meta($user_id, 'lift_docs_user_code', true);
            if (empty($existing_code)) {
                $this->generate_and_save_user_code($user_id);
            }
        }
    }

    /**
     * Generate and save user code
     */
    private function generate_and_save_user_code($user_id) {
        $user_code = $this->generate_unique_user_code();
        update_user_meta($user_id, 'lift_docs_user_code', $user_code);
    }

    /**
     * Add user code column to users list
     */
    public function add_user_code_column($columns) {
        $columns['lift_docs_user_code'] = __('User Code', 'lift-docs-system');
        return $columns;
    }

    /**
     * Show user code in users list column
     */
    public function show_user_code_column($value, $column_name, $user_id) {
        if ($column_name === 'lift_docs_user_code') {
            $user = get_user_by('id', $user_id);
            if ($user && in_array('documents_user', $user->roles)) {
                $user_code = get_user_meta($user_id, 'lift_docs_user_code', true);
                $nonce = wp_create_nonce('generate_user_code');

                if ($user_code) {
                    // User has a code - show code with regenerate button
                    return '<div id="user-code-cell-' . $user_id . '">' .
                           '<strong style="color: #0073aa; font-family: monospace;">' . esc_html($user_code) . '</strong><br>' .
                           '<button type="button" class="button button-secondary generate-user-code-btn-list" ' .
                           'data-user-id="' . $user_id . '" data-nonce="' . $nonce . '" ' .
                           'style="margin-top: 5px; font-size: 11px;">Generate New Code</button>' .
                           '</div>';
                } else {
                    // User has no code - show generate button
                    return '<div id="user-code-cell-' . $user_id . '">' .
                           '<span style="color: #d63638; font-style: italic;">No Code</span><br>' .
                           '<button type="button" class="button button-primary generate-user-code-btn-list" ' .
                           'data-user-id="' . $user_id . '" data-nonce="' . $nonce . '" ' .
                           'style="margin-top: 5px; font-size: 11px;">Generate Code</button>' .
                           '</div>';
                }
            } else {
                return '—';
            }
        }
        return $value;
    }

    /**
     * AJAX handler for generating user code
     */
    public function ajax_generate_user_code() {
        // Check for required parameters
        if (!isset($_POST['user_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Invalid request - missing parameters');
        }

        $user_id = intval($_POST['user_id']);
        $nonce = $_POST['nonce'];

        // Verify nonce - only support users list context now
        if (!wp_verify_nonce($nonce, 'generate_user_code')) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('edit_users')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Check if user exists and has documents_user role
        $user = get_user_by('id', $user_id);
        if (!$user || !in_array('documents_user', $user->roles)) {
            wp_send_json_error('Invalid user or user does not have Documents User role');
        }

        // Generate new code (overwrite existing if any)
        $user_code = $this->generate_unique_user_code();
        update_user_meta($user_id, 'lift_docs_user_code', $user_code);

        wp_send_json_success(array('code' => $user_code, 'message' => 'User code generated successfully'));
    }

    /**
     * AJAX handler for searching document users
     */
    public function ajax_search_document_users() {
        // Clear any output that might interfere with JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Get request data from either POST or GET
        $request_data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

        // Check if we have the basic required data
        if (!isset($request_data['action']) || $request_data['action'] !== 'search_document_users') {
            wp_send_json_error('Invalid action');
        }

        // Check nonce
        $nonce_valid = false;
        $nonce_received = isset($request_data['nonce']) ? $request_data['nonce'] : '';

        if (!empty($nonce_received)) {
            $nonce_valid = wp_verify_nonce($nonce_received, 'search_document_users');
        }

        if (!$nonce_valid) {
            wp_send_json_error('Security check failed');
        }

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search = isset($request_data['search']) ? sanitize_text_field($request_data['search']) : '';
        $page = isset($request_data['page']) ? intval($request_data['page']) : 1;
        $per_page = 20; // Number of results per page

        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'documents_user',
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page
        );

        // Add search functionality
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('display_name', 'user_login', 'user_email');
        }

        $users = get_users($args);
        $results = array();

        foreach ($users as $user) {
            $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);

            $results[] = array(
                'id' => $user->ID,
                'text' => $user->display_name . ' (' . $user->user_login . ')',
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'user_code' => $user_code ? $user_code : ''
            );
        }

        // Check if there are more results
        $total_args = $args;
        unset($total_args['number']);
        unset($total_args['offset']);
        $total_users = get_users($total_args);
        $more = count($total_users) > ($page * $per_page);

        wp_send_json_success(array(
            'results' => $results,
            'more' => $more,
            'total' => count($total_users)
        ));
    }

    /**
     * Add JavaScript for users list generate code buttons
     */
    public function add_users_list_script() {
        $current_screen = get_current_screen();

        // Only add script on users list page
        if (!$current_screen || $current_screen->id !== 'users') {
            return;
        }
        ?>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle Generate User Code button click in users list
            $(document).on('click', '.generate-user-code-btn-list', function(e) {
                e.preventDefault();

                var $button = $(this);
                var userId = $button.data('user-id');
                var nonce = $button.data('nonce');
                var $cell = $('#user-code-cell-' + userId);
                var isRegenerate = $button.hasClass('button-secondary');
                var originalText = $button.text();

                // Confirm if regenerating existing code
                if (isRegenerate) {
                    var confirmMessage = '<?php _e("Are you sure you want to generate a new User Code? This will replace the existing code and may affect document access.", "lift-docs-system"); ?>';
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                }

                // Disable button and show loading
                $button.prop('disabled', true).text('<?php _e("Generating...", "lift-docs-system"); ?>');

                // AJAX request to generate code
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_user_code',
                        user_id: userId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.code) {
                            // Update the cell with new code and regenerate button
                            var newHtml = '<strong style="color: #0073aa; font-family: monospace;">' + response.data.code + '</strong><br>' +
                                         '<button type="button" class="button button-secondary generate-user-code-btn-list" ' +
                                         'data-user-id="' + userId + '" data-nonce="' + nonce + '" ' +
                                         'style="margin-top: 5px; font-size: 11px;"><?php _e("Generate New Code", "lift-docs-system"); ?></button>';

                            $cell.html(newHtml);

                            // Show success message
                            var message = isRegenerate ?
                                '<?php _e("User Code regenerated successfully!", "lift-docs-system"); ?>' :
                                '<?php _e("User Code generated successfully!", "lift-docs-system"); ?>';
                            var successMsg = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0; position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 300px;"><p>' + message + '</p></div>');
                            $('body').append(successMsg);

                            // Auto-dismiss success message after 3 seconds
                            setTimeout(function() {
                                successMsg.hide().remove();
                            }, 3000);

                        } else {
                            // Show error message
                            var errorMsg = response.data || '<?php _e("Error generating User Code. Please try again.", "lift-docs-system"); ?>';
                            alert(errorMsg);
                            $button.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show detailed error message
                        var errorMessage = '<?php _e("Error generating User Code. Please try again.", "lift-docs-system"); ?> Status: ' + status;
                        alert(errorMessage);
                        $button.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>

        <style type="text/css">
        .generate-user-code-btn-list {
            font-size: 11px !important;
            padding: 2px 8px !important;
            height: auto !important;
            line-height: 1.2 !important;
        }

        /* Primary button for users without code */
        .generate-user-code-btn-list.button-primary {
            background: #00a32a !important;
            border-color: #00a32a !important;
            color: #fff !important;
        }

        .generate-user-code-btn-list.button-primary:hover {
            background: #008a20 !important;
            border-color: #008a20 !important;
        }

        /* Secondary button for users with existing code */
        .generate-user-code-btn-list.button-secondary {
            background: #f39c12 !important;
            border-color: #f39c12 !important;
            color: #fff !important;
        }

        .generate-user-code-btn-list.button-secondary:hover {
            background: #e67e22 !important;
            border-color: #e67e22 !important;
        }

        .generate-user-code-btn-list:disabled {
            background: #ddd !important;
            border-color: #ddd !important;
            color: #999 !important;
            cursor: not-allowed !important;
        }
        </style>

        <?php
    }

    /**
     * LIFT Forms admin page
     */
    public function forms_admin_page() {
        // Check if LIFT Forms class exists
        if (class_exists('LIFT_Forms')) {
            $lift_forms = new LIFT_Forms();
            $lift_forms->admin_page();
        } else {
            echo '<div class="wrap"><h1>LIFT Forms</h1><p>LIFT Forms class not found. Please check if the forms module is properly loaded.</p></div>';
        }
    }

    /**
     * LIFT Forms builder page
     */
    public function forms_builder_page() {
        // Check if LIFT Forms class exists
        if (class_exists('LIFT_Forms')) {
            $lift_forms = new LIFT_Forms();
            $lift_forms->form_builder_page();
        } else {
            echo '<div class="wrap"><h1>Form Builder</h1><p>LIFT Forms class not found. Please check if the forms module is properly loaded.</p></div>';
        }
    }

    /**
     * LIFT Forms submissions page
     */
    public function forms_submissions_page() {
        // Check if LIFT Forms class exists
        if (class_exists('LIFT_Forms')) {
            $lift_forms = new LIFT_Forms();
            $lift_forms->submissions_page();
        } else {
            echo '<div class="wrap"><h1>Form Submissions</h1><p>LIFT Forms class not found. Please check if the forms module is properly loaded.</p></div>';
        }
    }

    /**
     * Get admin document details for modal (AJAX handler)
     */
    public function ajax_get_admin_document_details() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'get_admin_document_details')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check user permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_lift_documents')) {
            wp_send_json_error(__('Access denied', 'lift-docs-system'));
        }

        $document_id = intval($_POST['document_id']);
        $document = get_post($document_id);

        if (!$document || $document->post_type !== 'lift_document') {
            wp_send_json_error(__('Document not found', 'lift-docs-system'));
        }

        // Get document data
        $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
        $assigned_users = is_array($assigned_users) ? $assigned_users : array();

        $assigned_forms = get_post_meta($document->ID, '_lift_doc_assigned_forms', true);
        $assigned_forms = is_array($assigned_forms) ? $assigned_forms : array();

        $views = get_post_meta($document->ID, '_lift_doc_views', true);
        $downloads = get_post_meta($document->ID, '_lift_doc_downloads', true);

        // Get document status
        $current_status = get_post_meta($document->ID, '_lift_doc_status', true);
        if (empty($current_status)) {
            $current_status = 'pending';
        }

        // Get file URLs
        $file_urls = get_post_meta($document->ID, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            $file_urls = array(get_post_meta($document->ID, '_lift_doc_file_url', true));
        }
        $file_urls = array_filter($file_urls);

        // Generate Attached files
        if (class_exists('LIFT_Docs_Settings') && LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
            $view_url = LIFT_Docs_Settings::generate_secure_link($document->ID);
        } else {
            $view_url = get_permalink($document->ID);
        }

        // Get user details
        $user_details = array();
        if (!empty($assigned_users)) {
            foreach ($assigned_users as $user_id) {
                $user = get_user_by('ID', $user_id);
                if ($user) {
                    $user_details[] = array(
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                        'code' => get_user_meta($user_id, 'lift_docs_user_code', true)
                    );
                }
            }
        }

        // Get form details with submission information
        $form_details = array();
        if (!empty($assigned_forms)) {
            global $wpdb;
            $forms_table = $wpdb->prefix . 'lift_forms';
            $submissions_table = $wpdb->prefix . 'lift_form_submissions';

            foreach ($assigned_forms as $form_id) {
                $form = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, name, description FROM $forms_table WHERE id = %d AND status = 'active'",
                    $form_id
                ));
                if ($form) {
                    // Check for submissions for this document and form (all users)
                    $submission = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, submitted_at, user_id FROM $submissions_table
                         WHERE form_id = %d AND form_data LIKE %s
                         ORDER BY submitted_at DESC LIMIT 1",
                        $form_id,
                        '%"_document_id":' . $document_id . '%'
                    ));

                    $form_details[] = array(
                        'id' => $form->id,
                        'name' => $form->name,
                        'description' => $form->description,
                        'has_submission' => !empty($submission),
                        'submission_id' => $submission ? $submission->id : null,
                        'submitted_at' => $submission ? $submission->submitted_at : null,
                        'submitted_by_user_id' => $submission ? $submission->user_id : null
                    );
                }
            }
        }

        ob_start();
        ?>
        <div class="modal-body-grid">
            <div class="modal-column-left">
                <div class="modal-section">
                    <div class="modal-info-grid">
                        <div class="modal-stat">
                            <div class="number"><?php echo $views ? $views : 0; ?></div>
                            <div class="label"><?php _e('Views', 'lift-docs-system'); ?></div>
                        </div>
                        <div class="modal-stat">
                            <div class="number"><?php echo $downloads ? $downloads : 0; ?></div>
                            <div class="label"><?php _e('Downloads', 'lift-docs-system'); ?></div>
                        </div>
                        <div class="modal-stat">
                            <div class="number"><?php echo count($assigned_users); ?></div>
                            <div class="label"><?php _e('Users', 'lift-docs-system'); ?></div>
                        </div>
                        <div class="modal-stat">
                            <div class="number"><?php echo count($file_urls); ?></div>
                            <div class="label"><?php _e('Files', 'lift-docs-system'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-section">
                    <h3><?php _e('Created', 'lift-docs-system'); ?></h3>
                    <p style="margin: 0; color: #646970; font-size: 12px;">
                        <?php echo get_the_date('M j, Y g:i A', $document->ID); ?> -
                        <?php _e('by', 'lift-docs-system'); ?> <?php echo get_the_author_meta('display_name', $document->post_author); ?>
                    </p>
                </div>
                <div class="modal-section">
                    <h3><?php _e('Attached files', 'lift-docs-system'); ?></h3>
                    <div class="view-url-box">
                        <a href="<?php echo esc_url($view_url); ?>" target="_blank">
                            <?php echo esc_html($view_url); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="modal-column-right">
                <?php if (!empty($file_urls)): ?>
                <div class="modal-section">
                    <h3><?php _e('Files', 'lift-docs-system'); ?> (<?php echo count($file_urls); ?>)</h3>
                    <div class="files-grid">
                        <?php foreach ($file_urls as $index => $file_url): ?>
                            <?php if (!empty($file_url)): ?>
                                <div class="file-item">
                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank">
                                        <?php echo esc_html(basename($file_url)); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- <?php if (!empty($user_details)): ?>
                <div class="modal-section">
                    <h3><?php _e('View Documents', 'lift-docs-system'); ?> (<?php echo count($user_details); ?>)</h3>
                    <div class="assigned-users-grid">
                        <?php foreach ($user_details as $user_info): ?>
                            <div class="user-item">
                                <div class="user-info">
                                    <strong><?php echo esc_html($user_info['name']); ?></strong>
                                    <div class="user-email"><?php echo esc_html($user_info['email']); ?></div>
                                </div>
                                <?php if (!empty($user_info['code'])): ?>
                                    <div class="user-code-badge">
                                        <?php echo esc_html($user_info['code']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?> -->

                <?php if (!empty($form_details)): ?>
                <div class="modal-section">
                    <h3><?php _e('Forms', 'lift-docs-system'); ?> (<?php echo count($form_details); ?>)</h3>
                    <div class="assigned-forms-grid">
                        <?php foreach ($form_details as $form_info): ?>
                            <div class="fpgroup">
                                <div class="form-info">
                                    <strong><?php echo esc_html($form_info['name']); ?></strong>
                                    <?php if ($form_info['has_submission']): ?>
                                        <div class="submission-info">
                                            <div class="submission-date">
                                                <span><?php echo date('M j', strtotime($form_info['submitted_at'])); ?></span>
                                                <?php if ($form_info['submitted_by_user_id']): ?>
                                                    <?php $submit_user = get_user_by('id', $form_info['submitted_by_user_id']); ?>
                                                    <?php if ($submit_user): ?>
                                                        <span class="submission-user">
                                                            <?php _e('by', 'lift-docs-system'); ?> <?php echo esc_html($submit_user->display_name); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="submission-user">
                                                        <?php _e('by Guest', 'lift-docs-system'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-item">
                                    <div class="form-actions">
                                        <?php
                                        // Build view form URL with submission data for admin
                                        $view_form_url = home_url('/document-form/' . $document_id . '/' . $form_info['id'] . '/');
                                        if ($form_info['has_submission'] && $form_info['submission_id']) {
                                            $view_form_url .= '?admin_view=1&submission_id=' . $form_info['submission_id'];
                                        } else {
                                            $view_form_url .= '?admin_view=1';
                                        }

                                        // Build edit form URL for admin
                                        $edit_form_url = home_url('/document-form/' . $document_id . '/' . $form_info['id'] . '/');
                                        if ($form_info['has_submission'] && $form_info['submission_id']) {
                                            $edit_form_url .= '?admin_edit=1&submission_id=' . $form_info['submission_id'];
                                        }
                                        ?>
                                        <a href="<?php echo esc_url($view_form_url); ?>"
                                        class="button button-secondary"
                                        target="_blank">
                                            <?php
                                            if ($form_info['has_submission']) {
                                                _e('View Submission', 'lift-docs-system');
                                            } else {
                                                _e('View Form', 'lift-docs-system');
                                            }
                                            ?>
                                        </a>
                                        <?php if ($form_info['has_submission'] && $form_info['submission_id']): ?>
                                            <a href="<?php echo esc_url($edit_form_url); ?>"
                                            class="button"
                                            target="_blank">
                                                <?php _e('Edit Submission', 'lift-docs-system'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($form_info['has_submission']): ?>
                                            <button type="button"
                                                    class="button view-submission-btn"
                                                    data-submission-id="<?php echo esc_attr($form_info['submission_id']); ?>"
                                                    data-nonce="<?php echo wp_create_nonce('lift_forms_get_submission'); ?>">
                                                <?php _e('View Data', 'lift-docs-system'); ?>
                                            </button>
                                        <?php else: ?>
                                            <div class="form-status">
                                                <span class="status-badge status-pending"><?php _e('No Submit', 'lift-docs-system'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal-section">
                    <h3><?php _e('Status', 'lift-docs-system'); ?></h3>
                    <?php
                    $status_options = array(
                        'pending' => __('Pending', 'lift-docs-system'),
                        'processing' => __('Processing', 'lift-docs-system'),
                        'done' => __('Done', 'lift-docs-system'),
                        'cancelled' => __('Cancelled', 'lift-docs-system')
                    );

                    $status_colors = array(
                        'pending' => '#f39c12',
                        'processing' => '#3498db',
                        'done' => '#27ae60',
                        'cancelled' => '#e74c3c'
                    );
                    ?>
                    <select class="lift-status-dropdown" data-post-id="<?php echo esc_attr($document->ID); ?>" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ddd; background-color: <?php echo esc_attr($status_colors[$current_status]); ?>; color: white; font-weight: 500; font-size: 13px;">
                        <?php foreach ($status_options as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?> data-color="<?php echo esc_attr($status_colors[$value]); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
        <?php
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content
        ));
    }

    /**
     * AJAX handler for updating document status
     */
    public function ajax_update_document_status() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'update_document_status')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }

        // Check user permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_lift_documents')) {
            wp_send_json_error(__('Access denied', 'lift-docs-system'));
        }

        $document_id = intval($_POST['document_id']);
        $new_status = sanitize_text_field($_POST['status']);

        // Validate status
        $valid_statuses = array('pending', 'processing', 'done', 'cancelled');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(__('Invalid status', 'lift-docs-system'));
        }

        $document = get_post($document_id);
        if (!$document || $document->post_type !== 'lift_document') {
            wp_send_json_error(__('Document not found', 'lift-docs-system'));
        }

        // Update status
        update_post_meta($document_id, '_lift_doc_status', $new_status);

        // Get status label and color
        $status_options = array(
            'pending' => __('Pending', 'lift-docs-system'),
            'processing' => __('Processing', 'lift-docs-system'),
            'done' => __('Done', 'lift-docs-system'),
            'cancelled' => __('Cancelled', 'lift-docs-system')
        );

        $status_colors = array(
            'pending' => '#f39c12',
            'processing' => '#3498db',
            'done' => '#27ae60',
            'cancelled' => '#e74c3c'
        );

        wp_send_json_success(array(
            'status' => $new_status,
            'label' => $status_options[$new_status],
            'color' => $status_colors[$new_status]
        ));
    }

    /**
     * Add assigned user filter dropdown to documents list
     */
    public function add_assigned_user_filter($post_type) {
        if ($post_type != 'lift_document') {
            return;
        }

        $selected_user = isset($_GET['assigned_user']) ? $_GET['assigned_user'] : '';
        $selected_user_data = null;

        // If a user is selected, get their data for initial display
        if ($selected_user) {
            $user = get_user_by('id', $selected_user);
            if ($user && in_array('documents_user', $user->roles)) {
                $selected_user_data = array(
                    'id' => $user->ID,
                    'text' => $user->display_name . ' (' . $user->user_login . ')'
                );
            }
        }

        ?>
        <select name="assigned_user" id="assigned_user_filter" class="lift-user-select2" style="min-width: 200px;">
            <option value=""><?php _e('All Assigned Users', 'lift-docs-system'); ?></option>
            <?php if ($selected_user_data): ?>
                <option value="<?php echo $selected_user_data['id']; ?>" selected>
                    <?php echo esc_html($selected_user_data['text']); ?>
                </option>
            <?php endif; ?>
        </select>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof $.fn.select2 === 'undefined') {
                // Load Select2 if not available
                $('head').append('<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />');
                $.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function() {
                    initUserSelect2();
                });
            } else {
                initUserSelect2();
            }

            function initUserSelect2() {
                var searchNonce = '<?php echo wp_create_nonce('search_document_users'); ?>';

                $('#assigned_user_filter').select2({
                    placeholder: '<?php _e('Search and select user...', 'lift-docs-system'); ?>',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                action: 'search_document_users',
                                search: params.term || '',
                                page: params.page || 1,
                                nonce: searchNonce
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;

                            // Handle error responses
                            if (!data.success) {
                                return {
                                    results: [],
                                    pagination: { more: false }
                                };
                            }

                            return {
                                results: data.data.results || [],
                                pagination: {
                                    more: data.data.more || false
                                }
                            };
                        },
                        error: function(xhr, status, error) {
                            return { results: [], pagination: { more: false } };
                        },
                        cache: true
                    },
                    templateResult: function(user) {
                        if (user.loading) return user.text;

                        var markup = '<div class="select2-result-user">';
                        markup += '<div class="select2-result-user__name">' + user.display_name + '</div>';
                        if (user.user_login) {
                            markup += '<div class="select2-result-user__login">@' + user.user_login + '</div>';
                        }
                        if (user.user_code) {
                            markup += '<div class="select2-result-user__code">Code: ' + user.user_code + '</div>';
                        }
                        markup += '</div>';

                        return markup;
                    },
                    templateSelection: function(user) {
                        return user.display_name || user.text;
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    }
                });

                // Auto-submit form when selection changes
                $('#assigned_user_filter').on('select2:select select2:clear', function() {
                    $(this).closest('form').submit();
                });
            }
        });
        </script>

        <style type="text/css">
        .select2-result-user__name {
            font-weight: bold;
            color: #0073aa;
        }

        .select2-result-user__login {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .select2-result-user__code {
            font-size: 11px;
            color: #999;
            font-family: monospace;
            margin-top: 2px;
        }

        .select2-container {
            vertical-align: middle;
        }

        .select2-container .select2-selection--single {
            height: 30px;
            line-height: 28px;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            padding-left: 8px;
            padding-right: 20px;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 28px;
            right: 3px;
        }

        .select2-dropdown {
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 8px;
        }

        .select2-results__option {
            padding: 8px 12px;
        }

        .select2-results__option--highlighted {
            background-color: #0073aa !important;
            color: white !important;
        }

        .select2-results__option--highlighted .select2-result-user__name,
        .select2-results__option--highlighted .select2-result-user__login,
        .select2-results__option--highlighted .select2-result-user__code {
            color: white !important;
        }
        </style>
        <?php
    }

    /**
     * Filter documents by assigned user
     */
    public function filter_documents_by_assigned_user($query) {
        global $pagenow;

        if (!is_admin() || $pagenow != 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] != 'lift_document') {
            return;
        }

        // Check for both assigned_user and lift_docs_user_filter parameters
        $user_id = 0;
        if (isset($_GET['assigned_user']) && !empty($_GET['assigned_user'])) {
            $user_id = intval($_GET['assigned_user']);
        } elseif (isset($_GET['lift_docs_user_filter']) && !empty($_GET['lift_docs_user_filter'])) {
            $user_id = intval($_GET['lift_docs_user_filter']);
        }

        if ($user_id > 0) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_lift_doc_assigned_users',
                    'value' => sprintf(':%d;', $user_id),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_lift_doc_users',
                    'value' => '"' . $user_id . '"',
                    'compare' => 'LIKE'
                )
            );

            $query->set('meta_query', $meta_query);
        }
    }
}
