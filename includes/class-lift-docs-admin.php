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
     * Set custom columns for documents list
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['category'] = __('Category', 'lift-docs-system');
        $new_columns['date'] = $columns['date'];
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
                
            case 'document_details':
                $this->render_document_details_button($post_id);
                break;
        }
    }
    
    /**
     * Render document details button with modal data
     */
    private function render_document_details_button($post_id) {
        // Collect all data
        $view_url = '';
        $view_label = '';
        if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
            $view_url = LIFT_Docs_Settings::generate_secure_link($post_id);
            $view_label = __('Secure View URL', 'lift-docs-system');
        } else {
            $view_url = get_permalink($post_id);
            $view_label = __('View URL', 'lift-docs-system');
        }
        
        $download_url = '';
        $secure_download_url = '';
        $file_url = get_post_meta($post_id, '_lift_doc_file_url', true);
        if ($file_url) {
            $download_url = add_query_arg(array(
                'lift_download' => $post_id,
                'nonce' => wp_create_nonce('lift_download_' . $post_id)
            ), home_url());
            
            if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                $secure_download_url = LIFT_Docs_Settings::generate_secure_download_link($post_id);
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
                data-download-url="<?php echo esc_attr($download_url); ?>"
                data-secure-download-url="<?php echo esc_attr($secure_download_url); ?>"
                data-shortcode="<?php echo esc_attr($shortcode); ?>"
                data-views="<?php echo esc_attr($views ? number_format($views) : '0'); ?>"
                data-downloads="<?php echo esc_attr($downloads ? number_format($downloads) : '0'); ?>"
                data-file-size="<?php echo esc_attr($file_size ? size_format($file_size) : '—'); ?>">
            <?php _e('View Details', 'lift-docs-system'); ?>
        </button>
        <?php
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'lift-docs-details',
            __('Document Details', 'lift-docs-system'),
            array($this, 'document_details_meta_box'),
            'lift_document',
            'normal',
            'high'
        );
        
        add_meta_box(
            'lift-docs-settings',
            __('Document Settings', 'lift-docs-system'),
            array($this, 'document_settings_meta_box'),
            'lift_document',
            'side',
            'default'
        );
    }
    
    /**
     * Document details meta box
     */
    public function document_details_meta_box($post) {
        wp_nonce_field('lift_docs_meta_box', 'lift_docs_meta_box_nonce');
        
        $file_url = get_post_meta($post->ID, '_lift_doc_file_url', true);
        $file_size = get_post_meta($post->ID, '_lift_doc_file_size', true);
        $download_count = get_post_meta($post->ID, '_lift_doc_downloads', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lift_doc_file_url"><?php _e('File URL', 'lift-docs-system'); ?></label></th>
                <td>
                    <input type="url" id="lift_doc_file_url" name="lift_doc_file_url" value="<?php echo esc_attr($file_url); ?>" class="regular-text" />
                    <button type="button" class="button" id="upload_file_button"><?php _e('Upload File', 'lift-docs-system'); ?></button>
                    <p class="description"><?php _e('Enter the URL of the document file or upload a new file.', 'lift-docs-system'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="lift_doc_file_size"><?php _e('File Size (bytes)', 'lift-docs-system'); ?></label></th>
                <td>
                    <input type="number" id="lift_doc_file_size" name="lift_doc_file_size" value="<?php echo esc_attr($file_size); ?>" class="small-text" />
                    <p class="description"><?php _e('File size in bytes (will be auto-detected for uploaded files).', 'lift-docs-system'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Download Count', 'lift-docs-system'); ?></th>
                <td>
                    <p><?php echo $download_count ? $download_count : '0'; ?> <?php _e('downloads', 'lift-docs-system'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Document settings meta box
     */
    public function document_settings_meta_box($post) {
        $featured = get_post_meta($post->ID, '_lift_doc_featured', true);
        $private_doc = get_post_meta($post->ID, '_lift_doc_private', true);
        $password_protected = get_post_meta($post->ID, '_lift_doc_password_protected', true);
        $doc_password = get_post_meta($post->ID, '_lift_doc_password', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="lift_doc_featured" value="1" <?php checked($featured, '1'); ?> />
                <?php _e('Featured Document', 'lift-docs-system'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="lift_doc_private" value="1" <?php checked($private_doc, '1'); ?> />
                <?php _e('Private Document', 'lift-docs-system'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="lift_doc_password_protected" value="1" <?php checked($password_protected, '1'); ?> />
                <?php _e('Password Protected', 'lift-docs-system'); ?>
            </label>
        </p>
        
        <p>
            <label for="lift_doc_password"><?php _e('Document Password', 'lift-docs-system'); ?></label>
            <input type="password" id="lift_doc_password" name="lift_doc_password" value="<?php echo esc_attr($doc_password); ?>" class="widefat" />
        </p>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['lift_docs_meta_box_nonce']) || !wp_verify_nonce($_POST['lift_docs_meta_box_nonce'], 'lift_docs_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'lift_doc_file_url',
            'lift_doc_file_size',
            'lift_doc_featured',
            'lift_doc_private',
            'lift_doc_password_protected',
            'lift_doc_password'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, '_' . $field);
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
        // Only load on document list page
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
                    </div>
                    
                    <div class="lift-detail-group">
                        <label><?php _e('Download URL', 'lift-docs-system'); ?>:</label>
                        <div class="lift-input-group">
                            <input type="text" id="lift-download-url" readonly onclick="this.select()" />
                            <button type="button" class="button lift-copy-btn" data-target="#lift-download-url">
                                <?php _e('Copy', 'lift-docs-system'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="lift-detail-group" id="lift-secure-download-group" style="display: none;">
                        <label><?php _e('Secure Download URL', 'lift-docs-system'); ?>:</label>
                        <div class="lift-input-group">
                            <input type="text" id="lift-secure-download-url" readonly onclick="this.select()" />
                            <button type="button" class="button lift-copy-btn" data-target="#lift-secure-download-url">
                                <?php _e('Copy', 'lift-docs-system'); ?>
                            </button>
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
                        </div>
                    </div>
                </div>
                
                <div class="lift-modal-footer">
                    <button type="button" class="button button-primary lift-modal-close">
                        <?php _e('Close', 'lift-docs-system'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>
        
        <?php
    }
}
