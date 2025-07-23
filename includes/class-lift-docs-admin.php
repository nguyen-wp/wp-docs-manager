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
        $new_columns['view_url'] = __('Secure View', 'lift-docs-system');
        $new_columns['download_url'] = __('Download URL', 'lift-docs-system');
        $new_columns['shortcode'] = __('Shortcode', 'lift-docs-system');
        $new_columns['views'] = __('Views', 'lift-docs-system');
        $new_columns['file_size'] = __('File Size', 'lift-docs-system');
        $new_columns['date'] = $columns['date'];
        
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
                
            case 'view_url':
                // Use secure link for view URL if enabled, otherwise use permalink
                if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                    $view_url = LIFT_Docs_Settings::generate_secure_link($post_id);
                    $label = __('Secure View URL', 'lift-docs-system');
                } else {
                    $view_url = get_permalink($post_id);
                    $label = __('View URL', 'lift-docs-system');
                }
                
                echo '<div class="lift-url-field">';
                echo '<input type="text" value="' . esc_attr($view_url) . '" readonly onclick="this.select()" style="width: 100%; font-size: 11px;" />';
                echo '<br><small><a href="' . esc_url($view_url) . '" target="_blank">' . __('Preview', 'lift-docs-system') . '</a> | ' . $label . '</small>';
                echo '</div>';
                break;
                
            case 'download_url':
                $file_url = get_post_meta($post_id, '_lift_doc_file_url', true);
                if ($file_url) {
                    $download_url = add_query_arg(array(
                        'lift_download' => $post_id,
                        'nonce' => wp_create_nonce('lift_download_' . $post_id)
                    ), home_url());
                    
                    echo '<div class="lift-url-field">';
                    echo '<input type="text" value="' . esc_attr($download_url) . '" readonly onclick="this.select()" style="width: 100%; font-size: 11px;" />';
                    
                    // Show secure download URL if enabled
                    if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                        $secure_download_url = LIFT_Docs_Settings::generate_secure_download_link($post_id);
                        echo '<br><input type="text" value="' . esc_attr($secure_download_url) . '" readonly onclick="this.select()" style="width: 100%; font-size: 11px; margin-top: 2px;" placeholder="' . __('Secure Download URL', 'lift-docs-system') . '" />';
                        // echo '<br><small>' . __('Secure URL (permanent)', 'lift-docs-system') . '</small>';
                    }
                    
                    echo '</div>';
                } else {
                    echo '—';
                }
                break;
                
            case 'shortcode':
                echo '<div class="lift-shortcode-field">';
                // echo '<input type="text" value="[lift_documents id=&quot;' . $post_id . '&quot;]" readonly onclick="this.select()" style="width: 100%; font-size: 11px;" placeholder="Display Shortcode" />';
                echo '<input type="text" value="[lift_document_download id=&quot;' . $post_id . '&quot;]" readonly onclick="this.select()" style="width: 100%; font-size: 11px; margin-top: 2px;" placeholder="Download Shortcode" />';
                // echo '<br><small>' . __('Document display & download shortcodes', 'lift-docs-system') . '</small>';
                echo '</div>';
                break;
                
            case 'views':
                $views = get_post_meta($post_id, '_lift_doc_views', true);
                $downloads = get_post_meta($post_id, '_lift_doc_downloads', true);
                echo '<div class="lift-stats">';
                echo '<strong>' . ($views ? number_format($views) : '0') . '</strong> ' . __('views', 'lift-docs-system');
                if ($downloads) {
                    echo '<br><strong>' . number_format($downloads) . '</strong> ' . __('downloads', 'lift-docs-system');
                }
                echo '</div>';
                break;
                
            case 'file_size':
                $file_size = get_post_meta($post_id, '_lift_doc_file_size', true);
                echo $file_size ? size_format($file_size) : '—';
                break;
        }
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
}
