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
        add_filter('manage_lift_document_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_lift_document_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_footer', array($this, 'add_document_details_modal'));
        
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
        
        // Add script for users list
        add_action('admin_footer', array($this, 'add_users_list_script'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('LIFT Docs System', 'lift-docs-system'),
            __('LIFT Docs', 'lift-docs-system'),
            'manage_options',
            'lift-docs-system',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );
        
        add_submenu_page(
            'lift-docs-system',
            __('All Documents', 'lift-docs-system'),
            __('All Documents', 'lift-docs-system'),
            'manage_options',
            'edit.php?post_type=lift_document'
        );
        
        add_submenu_page(
            'lift-docs-system',
            __('Add New Document', 'lift-docs-system'),
            __('Add New', 'lift-docs-system'),
            'manage_options',
            'post-new.php?post_type=lift_document'
        );
        
        add_submenu_page(
            'lift-docs-system',
            __('Categories', 'lift-docs-system'),
            __('Categories', 'lift-docs-system'),
            'manage_options',
            'edit-tags.php?taxonomy=lift_doc_category&post_type=lift_document'
        );
        
        add_submenu_page(
            'lift-docs-system',
            __('Analytics', 'lift-docs-system'),
            __('Analytics', 'lift-docs-system'),
            'manage_options',
            'lift-docs-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'lift-docs-system',
            __('Document Users', 'lift-docs-system'),
            __('Document Users', 'lift-docs-system'),
            'manage_options',
            'lift-docs-users',
            array($this, 'users_page')
        );
    }
    
    /**
     * Initialize admin
     */
    public function init_admin() {
        // Additional admin initialization if needed
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('LIFT Docs System Dashboard', 'lift-docs-system'); ?></h1>
            
            <div class="lift-docs-dashboard">
                <div class="lift-docs-stats">
                    <div class="lift-docs-stat-box">
                        <h3><?php _e('Total Documents', 'lift-docs-system'); ?></h3>
                        <p class="stat-number"><?php echo wp_count_posts('lift_document')->publish; ?></p>
                    </div>
                    
                    <div class="lift-docs-stat-box">
                        <h3><?php _e('Categories', 'lift-docs-system'); ?></h3>
                        <p class="stat-number"><?php echo wp_count_terms('lift_doc_category'); ?></p>
                    </div>
                    
                    <div class="lift-docs-stat-box">
                        <h3><?php _e('Total Views', 'lift-docs-system'); ?></h3>
                        <p class="stat-number"><?php echo $this->get_total_views(); ?></p>
                    </div>
                </div>
                
                <div class="lift-docs-quick-actions">
                    <h2><?php _e('Quick Actions', 'lift-docs-system'); ?></h2>
                    <a href="<?php echo admin_url('post-new.php?post_type=lift_document'); ?>" class="button button-primary">
                        <?php _e('Add New Document', 'lift-docs-system'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=lift_document'); ?>" class="button">
                        <?php _e('Manage Documents', 'lift-docs-system'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=lift-docs-settings'); ?>" class="button">
                        <?php _e('Settings', 'lift-docs-system'); ?>
                    </a>
                </div>
                
                <div class="lift-docs-recent">
                    <h2><?php _e('Recent Documents', 'lift-docs-system'); ?></h2>
                    <?php $this->display_recent_documents(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Document Analytics', 'lift-docs-system'); ?></h1>
            
            <div class="lift-docs-analytics">
                <div class="analytics-summary">
                    <h2><?php _e('Summary', 'lift-docs-system'); ?></h2>
                    <?php $this->display_analytics_summary(); ?>
                </div>
                
                <div class="analytics-charts">
                    <h2><?php _e('Popular Documents', 'lift-docs-system'); ?></h2>
                    <?php $this->display_popular_documents(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Document Users management page
     */
    public function users_page() {
        // Handle user role changes
        if (isset($_POST['action']) && $_POST['action'] === 'update_user_role') {
            $this->handle_user_role_update();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Document Users Management', 'lift-docs-system'); ?></h1>
            
            <div class="lift-docs-users-management">
                <div class="users-summary">
                    <h2><?php _e('User Roles Summary', 'lift-docs-system'); ?></h2>
                    <?php $this->display_users_summary(); ?>
                </div>
                
                <div class="users-with-documents-role">
                    <h2><?php _e('Users with Documents Access', 'lift-docs-system'); ?></h2>
                    <?php $this->display_documents_users(); ?>
                </div>
                
                <div class="add-documents-user">
                    <h2><?php _e('Grant Document Access to User', 'lift-docs-system'); ?></h2>
                    <?php $this->display_user_role_form(); ?>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle Generate User Code button click
            $('.generate-user-code-btn').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var userId = $button.data('user-id');
                var userName = $button.data('user-name');
                var $codeDisplay = $('#user-code-display-' + userId);
                
                // Disable button and show loading
                $button.prop('disabled', true).text('<?php _e('Generating...', 'lift-docs-system'); ?>');
                
                // AJAX request to generate code
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_user_code',
                        user_id: userId,
                        nonce: <?php echo json_encode(wp_create_nonce('generate_user_code')); ?>
                    },
                    success: function(response) {
                        if (response.success && response.data.code) {
                            // Update the display with new code
                            $codeDisplay.html(
                                '<code style="background: #f0f8ff; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-weight: bold; color: #0073aa;">' +
                                response.data.code +
                                '</code>'
                            );
                            
                            // Remove the generate button
                            $button.remove();
                            
                            // Show success message
                            var successMsg = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p><?php _e('User Code generated successfully!', 'lift-docs-system'); ?></p></div>');
                            $('#user-row-' + userId).after(successMsg);
                            
                            // Auto-dismiss success message after 5 seconds
                            setTimeout(function() {
                                successMsg.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 5000);
                            
                        } else {
                            // Show error message
                            alert('<?php _e('Error generating User Code. Please try again.', 'lift-docs-system'); ?>');
                            $button.prop('disabled', false).text('<?php _e('Generate Code', 'lift-docs-system'); ?>');
                        }
                    },
                    error: function() {
                        // Show error message
                        alert('<?php _e('Error generating User Code. Please try again.', 'lift-docs-system'); ?>');
                        $button.prop('disabled', false).text('<?php _e('Generate Code', 'lift-docs-system'); ?>');
                    }
                });
            });
        });
        </script>
        
        <style type="text/css">
        .generate-user-code-btn {
            background: #00a32a !important;
            border-color: #00a32a !important;
            color: #fff !important;
            transition: all 0.3s ease;
        }
        
        .generate-user-code-btn:hover {
            background: #008a20 !important;
            border-color: #008a20 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .generate-user-code-btn:disabled {
            background: #ddd !important;
            border-color: #ddd !important;
            color: #999 !important;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        [id^="user-code-display-"] code {
            animation: codeAppear 0.5s ease-in-out;
        }
        
        @keyframes codeAppear {
            from {
                opacity: 0;
                transform: scale(0.8);
                background: #ffffaa;
            }
            to {
                opacity: 1;
                transform: scale(1);
                background: #f0f8ff;
            }
        }
        
        .notice.notice-success {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        </style>
        
        <?php
    }

    /**
     * Set custom columns for documents list
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['category'] = __('Category', 'lift-docs-system');
        $new_columns['assignments'] = __('Assigned Users', 'lift-docs-system');
        $new_columns['date'] = $columns['date'];
        $new_columns['view_url'] = __('View URL', 'lift-docs-system');
        $new_columns['document_details'] = __('Document Details', 'lift-docs-system');
        
        return $new_columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
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
                
            case 'assignments':
                $this->render_assignments_column($post_id);
                break;
                
            case 'view_url':
                $this->render_view_url_column($post_id);
                break;
                
            case 'document_details':
                $this->render_document_details_button($post_id);
                break;
        }
    }
    
    /**
     * Render view URL column content
     */
    private function render_view_url_column($post_id) {
        // Get frontend instance to check permissions
        $frontend = LIFT_Docs_Frontend::get_instance();
        
        // Check if user can view document before generating view URL
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
        
        $view_url = '';
        $view_label = '';
        $view_class = 'lift-url-field';
        
        if ($can_view) {
            if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                $view_url = LIFT_Docs_Settings::generate_secure_link($post_id);
                $view_label = __('Secure View URL', 'lift-docs-system');
                $view_class .= ' secure-url';
            } else {
                $view_url = get_permalink($post_id);
                $view_label = __('View URL', 'lift-docs-system');
                $view_class .= ' public-url';
            }
        } else {
            $view_url = wp_login_url(get_permalink($post_id));
            $view_label = __('Login Required', 'lift-docs-system');
            $view_class .= ' login-required';
        }
        
        ?>
        <div class="<?php echo esc_attr($view_class); ?>">
            <a href="<?php echo esc_url($view_url); ?>" class="button" target="_blank" title="<?php _e('Open in new tab', 'lift-docs-system'); ?>">
                <?php echo $can_view ? __('Preview', 'lift-docs-system') : '🔒 ' . __('Login Required', 'lift-docs-system'); ?>
            </a>
        </div>
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
        
        // Check if user can view document before generating view URL
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
                $view_label = __('Secure View URL', 'lift-docs-system');
            } else {
                $view_url = get_permalink($post_id);
                $view_label = __('View URL', 'lift-docs-system');
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
                
                // Online view URL with file index
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
        
        $shortcode = '[lift_document_download id="' . $post_id . '"]';
        
        $views = get_post_meta($post_id, '_lift_doc_views', true);
        $downloads = get_post_meta($post_id, '_lift_doc_downloads', true);
        
        $file_size = get_post_meta($post_id, '_lift_doc_file_size', true);
        
        ?>
        <button type="button" class="button button-small lift-details-btn" 
                data-post-id="<?php echo esc_attr($post_id); ?>"
                data-view-url="<?php echo esc_attr($view_url); ?>"
                data-view-label="<?php echo esc_attr($view_label); ?>"
                data-download-urls="<?php echo esc_attr(json_encode($download_urls)); ?>"
                data-online-view-urls="<?php echo esc_attr(json_encode($online_view_urls)); ?>"
                data-secure-download-urls="<?php echo esc_attr(json_encode($secure_download_urls)); ?>"
                data-shortcode="<?php echo esc_attr($shortcode); ?>"
                data-views="<?php echo esc_attr($views ? number_format($views) : '0'); ?>"
                data-downloads="<?php echo esc_attr($downloads ? number_format($downloads) : '0'); ?>"
                data-file-size="<?php echo esc_attr($file_size ? size_format($file_size) : '—'); ?>"
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
            echo '<span style="color: #007cba; font-weight: 500;">' . __('All Document Users', 'lift-docs-system') . '</span>';
            return;
        }
        
        $user_count = count($assigned_users);
        $total_document_users = count(get_users(array('role' => 'documents_user')));
        
        if ($user_count === 0) {
            echo '<span style="color: #d63638;">' . __('No Access', 'lift-docs-system') . '</span>';
        } elseif ($user_count === $total_document_users) {
            echo '<span style="color: #007cba; font-weight: 500;">' . __('All Document Users', 'lift-docs-system') . '</span>';
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
            
            echo '<br><small style="color: #666;">' . sprintf(__('%d of %d users', 'lift-docs-system'), $user_count, $total_document_users) . '</small>';
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
            'lift-docs-assignments',
            __('Document Access Assignment', 'lift-docs-system'),
            array($this, 'document_assignments_meta_box'),
            'lift_document',
            'side',
            'high'
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
                <th><label><?php _e('Document Files', 'lift-docs-system'); ?></label></th>
                <td>
                    <div id="lift_doc_files_container">
                        <?php if (empty($file_urls)): ?>
                            <div class="file-input-row" data-index="0">
                                <input type="url" name="lift_doc_file_urls[]" value="" class="file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button button-danger remove-file-button button-large" style="display: none;"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
                                <!-- <span class="file-size-display"></span> -->
                            </div>
                        <?php else: ?>
                            <?php foreach ($file_urls as $index => $url): ?>
                            <div class="file-input-row" data-index="<?php echo $index; ?>">
                                <input type="url" name="lift_doc_file_urls[]" value="<?php echo esc_attr($url); ?>" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button button-danger remove-file-button button-large" <?php echo count($file_urls) <= 1 ? 'style="display: none;"' : ''; ?>><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
                                <!-- <span class="file-size-display">
                                    <?php if ($url): ?>
                                        <span style="color: #0073aa; font-weight: 500;">
                                            📄 <?php echo basename(parse_url($url, PHP_URL_PATH)); ?>
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
                    <h3 style="margin: 0; color: #23282d;"><?php _e('🔒 Secure Links', 'lift-docs-system'); ?></h3>
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
                    <h3 style="margin: 0; color: #666;"><?php _e('🔒 Secure Links', 'lift-docs-system'); ?></h3>
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
                            <button type="button" class="button button-danger remove-file-button button-large"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
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
                        let fileIcon = '📄'; // Default document icon
                        if (attachment.type === 'image') {
                            fileIcon = '🖼️';
                        } else if (attachment.type === 'video') {
                            fileIcon = '🎥';
                        } else if (attachment.type === 'audio') {
                            fileIcon = '🎵';
                        } else if (fileType.includes('pdf')) {
                            fileIcon = '📕';
                        } else if (fileType.includes('word') || fileType.includes('doc')) {
                            fileIcon = '📘';
                        } else if (fileType.includes('excel') || fileType.includes('sheet')) {
                            fileIcon = '📗';
                        } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                            fileIcon = '📙';
                        } else if (fileType.includes('zip') || fileType.includes('rar')) {
                            fileIcon = '📦';
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
                        button.text('✅ <?php _e('Uploaded', 'lift-docs-system'); ?>');
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
                                <button type="button" class="button button-danger remove-file-button button-large" style="display: none;"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
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
                console.warn('WordPress Media Library not available. Using fallback file input.');
                
                // Add new file input
                $('#add_file_button').click(function() {
                    const container = $('#lift_doc_files_container');
                    const newRow = $(`
                        <div class="file-input-row" data-index="${fileIndex}">
                            <input type="url" name="lift_doc_file_urls[]" value="" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL', 'lift-docs-system'); ?>" />
                            <button type="button" class="button button-primary button-large upload-file-button"><?php _e('Browse', 'lift-docs-system'); ?></button>
                            <button type="button" class="button button-danger remove-file-button button-large"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
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
                            let fileIcon = '📄'; // Default document icon
                            const fileType = file.type.toLowerCase();
                            if (fileType.startsWith('image/')) {
                                fileIcon = '🖼️';
                            } else if (fileType.startsWith('video/')) {
                                fileIcon = '🎥';
                            } else if (fileType.startsWith('audio/')) {
                                fileIcon = '🎵';
                            } else if (fileType.includes('pdf')) {
                                fileIcon = '📕';
                            } else if (fileType.includes('word') || fileType.includes('document')) {
                                fileIcon = '📘';
                            } else if (fileType.includes('excel') || fileType.includes('sheet')) {
                                fileIcon = '📗';
                            } else if (fileType.includes('powerpoint') || fileType.includes('presentation')) {
                                fileIcon = '📙';
                            } else if (fileType.includes('zip') || fileType.includes('rar')) {
                                fileIcon = '📦';
                            }
                            
                            sizeDisplay.html(`
                                <span style="color: #ff9800; font-weight: 500;">
                                    ${fileIcon} ${fileName} (${fileSize}) - <?php _e('Not uploaded to media library', 'lift-docs-system'); ?>
                                </span>
                            `);
                            
                            alert('<?php _e('Please upload this file to your media library first, then paste the URL here. Or drag and drop the file into the WordPress media uploader.', 'lift-docs-system'); ?>');
                            console.log('File selected:', file.name, 'Type:', file.type, 'Size:', file.size);
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
        
        /* File Row Number Badge */
        .file-input-row::before {
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
        }
        
        .file-input-row:nth-child(n+2)::before {
            display: block;
        }
        
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
        
        // Add user codes to the user objects for JavaScript
        foreach ($document_users as $user) {
            $user->lift_docs_user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
        }
        
        ?>
        <div class="document-assignments">
            <p><strong><?php _e('Assign Document Access', 'lift-docs-system'); ?></strong></p>
            <p class="description">
                <?php _e('Search and select users who can access this document. Leave empty to allow all Document Users access.', 'lift-docs-system'); ?>
            </p>
            
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
                            <?php _e('No users selected (all Document Users will have access)', 'lift-docs-system'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Search and Add Users -->
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
            var allUsers = <?php echo json_encode($document_users); ?>;
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
                    if (!isUserSelected(user.ID)) {
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
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
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
     * Get total views
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
     * Display analytics summary
     */
    private function display_analytics_summary() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $today_views = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE action = 'view' AND DATE(timestamp) = %s",
                current_time('Y-m-d')
            )
        );
        
        $week_views = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE action = 'view' AND timestamp >= %s",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )
        );
        
        ?>
        <div class="analytics-stats">
            <div class="stat-item">
                <h3><?php _e('Today\'s Views', 'lift-docs-system'); ?></h3>
                <p class="stat-number"><?php echo $today_views; ?></p>
            </div>
            <div class="stat-item">
                <h3><?php _e('This Week\'s Views', 'lift-docs-system'); ?></h3>
                <p class="stat-number"><?php echo $week_views; ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display popular documents
     */
    private function display_popular_documents() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $popular_docs = $wpdb->get_results(
            "SELECT document_id, COUNT(*) as view_count 
             FROM $table_name 
             WHERE action = 'view' 
             GROUP BY document_id 
             ORDER BY view_count DESC 
             LIMIT 10"
        );
        
        if ($popular_docs) {
            echo '<ol>';
            foreach ($popular_docs as $doc) {
                $post = get_post($doc->document_id);
                if ($post) {
                    echo '<li>';
                    echo '<a href="' . get_edit_post_link($doc->document_id) . '">' . esc_html($post->post_title) . '</a>';
                    echo ' (' . $doc->view_count . ' ' . __('views', 'lift-docs-system') . ')';
                    echo '</li>';
                }
            }
            echo '</ol>';
        } else {
            echo '<p>' . __('No analytics data available.', 'lift-docs-system') . '</p>';
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $pagenow, $post_type;
        
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
        
        wp_enqueue_script('lift-docs-admin-modal', plugin_dir_url(__FILE__) . '../assets/js/admin-modal.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('lift-docs-admin-modal', plugin_dir_url(__FILE__) . '../assets/css/admin-modal.css', array(), '1.0.0');
        
        wp_localize_script('lift-docs-admin-modal', 'liftDocsAdmin', array(
            'strings' => array(
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
                'close' => __('Close', 'lift-docs-system')
            )
        ));
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
                
                <div class="lift-modal-body">
                    <div class="lift-detail-group">
                        <label><?php _e('View URL', 'lift-docs-system'); ?>:</label>
                        <div class="lift-input-group">
                            <input type="text" id="lift-view-url" readonly onclick="this.select()" />
                            <button type="button" class="button lift-copy-btn" data-target="#lift-view-url">
                                <?php _e('Copy', 'lift-docs-system'); ?>
                            </button>
                            <a href="#" id="lift-view-preview" class="button" target="_blank">
                                <?php _e('Preview', 'lift-docs-system'); ?>
                            </a>
                        </div>
                        <!-- <p class="description" id="lift-view-description" style="margin-top: 5px; color: #666; font-size: 12px;"></p> -->
                    </div>
                    
                    <!-- <div class="lift-detail-group">
                        <label><?php _e('Download URLs', 'lift-docs-system'); ?>:</label>
                        <div id="lift-download-urls-container">
                            <div class="lift-input-group" id="lift-single-download">
                                <input type="text" id="lift-download-url" readonly onclick="this.select()" />
                                <button type="button" class="button lift-copy-btn" data-target="#lift-download-url">
                                    <?php _e('Copy', 'lift-docs-system'); ?>
                                </button>
                                <a href="#" id="lift-online-view" class="button" target="_blank">
                                    <?php _e('View Online', 'lift-docs-system'); ?>
                                </a>
                            </div>
                            <div id="lift-multiple-downloads" style="display: none;">
                            </div>
                        </div>
                    </div> -->
                    
                    <div class="lift-detail-group" id="lift-secure-download-group" style="display: none;">
                        <label><?php _e('Secure Download URLs', 'lift-docs-system'); ?>:</label>
                        <div id="lift-secure-download-urls-container">
                            <!-- Single file fallback -->
                            <div class="lift-input-group" id="lift-single-secure-download">
                                <input type="text" id="lift-secure-download-url" readonly onclick="this.select()" />
                                <button type="button" class="button lift-copy-btn" data-target="#lift-secure-download-url">
                                    <?php _e('Copy', 'lift-docs-system'); ?>
                                </button>
                            </div>
                            <!-- Multiple files list -->
                            <div id="lift-multiple-secure-downloads" style="display: none;">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="lift-detail-group">
                        <label><?php _e('Shortcode', 'lift-docs-system'); ?>:</label>
                        <div class="lift-input-group">
                            <input type="text" id="lift-shortcode" readonly onclick="this.select()" />
                            <button type="button" class="button lift-copy-btn" data-target="#lift-shortcode">
                                <?php _e('Copy', 'lift-docs-system'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="lift-detail-group">
                        <label><?php _e('Statistics', 'lift-docs-system'); ?>:</label>
                        <div class="lift-stats-display">
                            <div class="lift-stat-item">
                                <strong id="lift-views">0</strong>
                                <span><?php _e('Views', 'lift-docs-system'); ?></span>
                            </div>
                            <div class="lift-stat-item">
                                <strong id="lift-downloads">0</strong>
                                <span><?php _e('Downloads', 'lift-docs-system'); ?></span>
                            </div>
                            <div class="lift-stat-item">
                                <strong id="lift-file-size">—</strong>
                                <span><?php _e('File Size', 'lift-docs-system'); ?></span>
                            </div>
                            <div class="lift-stat-item">
                                <strong id="lift-files-count">0</strong>
                                <span><?php _e('Files', 'lift-docs-system'); ?></span>
                            </div>
                        </div>
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
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User', 'lift-docs-system'); ?></th>
                    <th><?php _e('Email', 'lift-docs-system'); ?></th>
                    <th><?php _e('User Code', 'lift-docs-system'); ?></th>
                    <th><?php _e('Registration Date', 'lift-docs-system'); ?></th>
                    <th><?php _e('Actions', 'lift-docs-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($document_users as $user): ?>
                <?php $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true); ?>
                <tr id="user-row-<?php echo $user->ID; ?>">
                    <td>
                        <strong><?php echo esc_html($user->display_name); ?></strong><br>
                        <span class="description"><?php echo esc_html($user->user_login); ?></span>
                    </td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td>
                        <span id="user-code-display-<?php echo $user->ID; ?>">
                            <?php if ($user_code): ?>
                                <code style="background: #f0f8ff; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-weight: bold; color: #0073aa;">
                                    <?php echo esc_html($user_code); ?>
                                </code>
                            <?php else: ?>
                                <span style="color: #d63638; font-style: italic;"><?php _e('No code', 'lift-docs-system'); ?></span>
                            <?php endif; ?>
                        </span>
                        
                        <?php if (!$user_code): ?>
                            <button type="button" class="button button-small button-primary generate-user-code-btn" 
                                    data-user-id="<?php echo $user->ID; ?>" 
                                    data-user-name="<?php echo esc_attr($user->display_name); ?>"
                                    style="margin-left: 10px;">
                                <?php _e('Generate Code', 'lift-docs-system'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('update_user_role_' . $user->ID); ?>
                            <input type="hidden" name="action" value="update_user_role">
                            <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                            <select name="new_role">
                                <option value="documents_user" selected><?php _e('Documents User', 'lift-docs-system'); ?></option>
                                <option value="subscriber"><?php _e('Subscriber', 'lift-docs-system'); ?></option>
                                <option value="contributor"><?php _e('Contributor', 'lift-docs-system'); ?></option>
                                <option value="author"><?php _e('Author', 'lift-docs-system'); ?></option>
                                <option value="editor"><?php _e('Editor', 'lift-docs-system'); ?></option>
                            </select>
                            <button type="submit" class="button button-small">
                                <?php _e('Update Role', 'lift-docs-system'); ?>
                            </button>
                        </form>
                        <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button button-small">
                            <?php _e('Edit User', 'lift-docs-system'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
                <li><?php _e('Download document files', 'lift-docs-system'); ?></li>
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
                           '<button type="button" class="button button-small button-secondary generate-user-code-btn-list" ' .
                           'data-user-id="' . $user_id . '" data-nonce="' . $nonce . '" ' .
                           'style="margin-top: 5px; font-size: 11px;">Generate New Code</button>' .
                           '</div>';
                } else {
                    // User has no code - show generate button
                    return '<div id="user-code-cell-' . $user_id . '">' .
                           '<span style="color: #d63638; font-style: italic;">No Code</span><br>' .
                           '<button type="button" class="button button-small button-primary generate-user-code-btn-list" ' .
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
                    if (!confirm('Are you sure you want to generate a new code? This will replace the existing code.')) {
                        return;
                    }
                }
                
                // Disable button and show loading
                $button.prop('disabled', true).text('Generating...');
                
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
                                         '<button type="button" class="button button-small button-secondary generate-user-code-btn-list" ' +
                                         'data-user-id="' + userId + '" data-nonce="' + nonce + '" ' +
                                         'style="margin-top: 5px; font-size: 11px;">Generate New Code</button>';
                            
                            $cell.html(newHtml);
                            
                            // Show success message
                            var message = isRegenerate ? 'User Code regenerated successfully!' : 'User Code generated successfully!';
                            var successMsg = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0; position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 300px;"><p>' + message + '</p></div>');
                            $('body').append(successMsg);
                            
                            // Auto-dismiss success message after 3 seconds
                            setTimeout(function() {
                                successMsg.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 3000);
                            
                        } else {
                            // Show error message
                            var errorMsg = response.data || 'Error generating User Code. Please try again.';
                            alert(errorMsg);
                            $button.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show detailed error message
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        alert('Error generating User Code. Please try again. Status: ' + status);
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
}
