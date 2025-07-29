<?php
/**
 * AJAX functionality for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Ajax {
    
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
        // Public AJAX actions
        add_action('wp_ajax_lift_track_time', array($this, 'track_time_spent'));
        add_action('wp_ajax_nopriv_lift_track_time', array($this, 'track_time_spent'));
        
        add_action('wp_ajax_lift_search_documents', array($this, 'search_documents'));
        add_action('wp_ajax_nopriv_lift_search_documents', array($this, 'search_documents'));
        
        add_action('wp_ajax_lift_load_more_documents', array($this, 'load_more_documents'));
        add_action('wp_ajax_nopriv_lift_load_more_documents', array($this, 'load_more_documents'));
        
        // New AJAX actions for default theme layout
        add_action('wp_ajax_lift_get_document_download_url', array($this, 'get_document_download_url'));
        add_action('wp_ajax_nopriv_lift_get_document_download_url', array($this, 'get_document_download_url'));
        
        add_action('wp_ajax_lift_get_document_views', array($this, 'get_document_views'));
        add_action('wp_ajax_nopriv_lift_get_document_views', array($this, 'get_document_views'));
        
        add_action('wp_ajax_lift_track_document_view', array($this, 'track_document_view'));
        add_action('wp_ajax_nopriv_lift_track_document_view', array($this, 'track_document_view'));
        
        add_action('wp_ajax_lift_track_download', array($this, 'track_download'));
        add_action('wp_ajax_nopriv_lift_track_download', array($this, 'track_download'));
        
        // Admin AJAX actions
        add_action('wp_ajax_lift_admin_analytics', array($this, 'admin_analytics'));
        add_action('wp_ajax_lift_bulk_action', array($this, 'bulk_action'));
        add_action('wp_ajax_lift_upload_document', array($this, 'upload_document'));
        add_action('wp_ajax_lift_generate_secure_link', array($this, 'generate_secure_link'));
        
        // Frontend dashboard AJAX actions
        add_action('wp_ajax_get_document_details', array($this, 'get_document_details'));
        add_action('wp_ajax_track_document_action', array($this, 'track_document_action'));
        add_action('wp_ajax_refresh_dashboard_stats', array($this, 'refresh_dashboard_stats'));
    }
    
    /**
     * Track time spent on document
     */
    public function track_time_spent() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_nonce')) {
            wp_die('Security check failed');
        }
        
        $document_id = intval($_POST['document_id']);
        $time_spent = intval($_POST['time_spent']);
        
        if ($document_id && $time_spent > 0) {
            // Check if document is archived
            $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
            if ($is_archived === '1' || $is_archived === 1) {
                wp_send_json_error(__('Document has been archived and is no longer accessible', 'lift-docs-system'));
            }
            
            // Record analytics event
            global $wpdb;
            $table_name = $wpdb->prefix . 'lift_docs_analytics';
            
            $user_id = get_current_user_id();
            $user_id = $user_id ? $user_id : null;
            
            $wpdb->insert(
                $table_name,
                array(
                    'document_id' => $document_id,
                    'user_id' => $user_id,
                    'action' => 'time_spent',
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'timestamp' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s')
            );
            
            // Store time spent in post meta for aggregation
            $total_time = get_post_meta($document_id, '_lift_doc_total_time', true);
            $total_time = $total_time ? intval($total_time) : 0;
            update_post_meta($document_id, '_lift_doc_total_time', $total_time + $time_spent);
        }
        
        wp_send_json_success();
    }
    
    /**
     * Search documents via AJAX
     */
    public function search_documents() {
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $tag = sanitize_text_field($_POST['tag'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        
        $args = array(
            'post_type' => 'lift_document',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'meta_query' => array(
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
            )
        );
        
        if ($search_term) {
            $args['s'] = $search_term;
        }
        
        $tax_query = array();
        
        if ($category) {
            $tax_query[] = array(
                'taxonomy' => 'lift_doc_category',
                'field' => 'slug',
                'terms' => $category
            );
        }
        
        if ($tag) {
            $tax_query[] = array(
                'taxonomy' => 'lift_doc_tag',
                'field' => 'slug',
                'terms' => $tag
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $query = new WP_Query($args);
        
        $documents = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $doc_id = get_the_ID();
                $file_url = get_post_meta($doc_id, '_lift_doc_file_url', true);
                $file_size = get_post_meta($doc_id, '_lift_doc_file_size', true);
                $views = get_post_meta($doc_id, '_lift_doc_views', true);
                
                $categories = get_the_terms($doc_id, 'lift_doc_category');
                $category_names = array();
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        $category_names[] = $cat->name;
                    }
                }
                
                $documents[] = array(
                    'id' => $doc_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'date' => get_the_date(),
                    'author' => get_the_author(),
                    'categories' => $category_names,
                    'file_url' => $file_url,
                    'file_size' => $file_size ? size_format($file_size) : '',
                    'views' => $views ? intval($views) : 0,
                    'thumbnail' => get_the_post_thumbnail_url($doc_id, 'thumbnail')
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'documents' => $documents,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ));
    }
    
    /**
     * Load more documents for infinite scroll
     */
    public function load_more_documents() {
        $page = intval($_POST['page'] ?? 1);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $tag = sanitize_text_field($_POST['tag'] ?? '');
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $order = sanitize_text_field($_POST['order'] ?? 'DESC');
        
        $args = array(
            'post_type' => 'lift_document',
            'posts_per_page' => 6,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
            'meta_query' => array(
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
            )
        );
        
        $tax_query = array();
        
        if ($category) {
            $tax_query[] = array(
                'taxonomy' => 'lift_doc_category',
                'field' => 'slug',
                'terms' => $category
            );
        }
        
        if ($tag) {
            $tax_query[] = array(
                'taxonomy' => 'lift_doc_tag',
                'field' => 'slug',
                'terms' => $tag
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            ob_start();
            
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_document_card(get_the_ID());
            }
            
            wp_reset_postdata();
            
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html,
                'has_more' => $page < $query->max_num_pages
            ));
        } else {
            wp_send_json_error('No more documents found');
        }
    }
    
    /**
     * Render document card
     */
    private function render_document_card($doc_id) {
        $views = get_post_meta($doc_id, '_lift_doc_views', true);
        $file_size = get_post_meta($doc_id, '_lift_doc_file_size', true);
        $categories = get_the_terms($doc_id, 'lift_doc_category');
        
        ?>
        <div class="lift-doc-card" data-id="<?php echo $doc_id; ?>">
            <?php if (has_post_thumbnail($doc_id)): ?>
                <div class="doc-thumbnail">
                    <a href="<?php echo get_permalink($doc_id); ?>">
                        <?php echo get_the_post_thumbnail($doc_id, 'medium'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="doc-content">
                <h3 class="doc-title">
                    <a href="<?php echo get_permalink($doc_id); ?>"><?php echo get_the_title($doc_id); ?></a>
                </h3>
                
                <?php if (get_the_excerpt($doc_id)): ?>
                    <p class="doc-excerpt"><?php echo get_the_excerpt($doc_id); ?></p>
                <?php endif; ?>
                
                <div class="doc-meta">
                    <span class="doc-date"><?php echo get_the_date('', $doc_id); ?></span>
                    
                    <?php if ($categories && !is_wp_error($categories)): ?>
                        <span class="doc-category"><?php echo $categories[0]->name; ?></span>
                    <?php endif; ?>
                    
                    <?php if ($views): ?>
                        <span class="doc-views"><?php echo sprintf(__('%s views', 'lift-docs-system'), number_format($views)); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($file_size): ?>
                        <span class="doc-size"><?php echo size_format($file_size); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="doc-actions">
                    <a href="<?php echo get_permalink($doc_id); ?>" class="btn-view">
                        <?php _e('View Document', 'lift-docs-system'); ?>
                    </a>
                    
                    <?php 
                    $file_url = get_post_meta($doc_id, '_lift_doc_file_url', true);
                    if ($file_url): 
                        $download_url = add_query_arg(array(
                            'lift_download' => $doc_id,
                            'nonce' => wp_create_nonce('lift_download_' . $doc_id)
                        ), home_url());
                    ?>
                        <a href="<?php echo esc_url($download_url); ?>" class="btn-download">
                            <?php _e('Download', 'lift-docs-system'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin analytics AJAX
     */
    public function admin_analytics() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '7days');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $date_clause = '';
        switch ($period) {
            case '24hours':
                $date_clause = "AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case '7days':
                $date_clause = "AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $date_clause = "AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90days':
                $date_clause = "AND timestamp >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
        }
        
        // Get view stats
        $view_stats = $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as views 
             FROM $table_name 
             WHERE action = 'view' $date_clause 
             GROUP BY DATE(timestamp) 
             ORDER BY date ASC"
        );
        
        // Get download stats
        $download_stats = $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as downloads 
             FROM $table_name 
             WHERE action = 'download' $date_clause 
             GROUP BY DATE(timestamp) 
             ORDER BY date ASC"
        );
        
        // Get popular documents
        $popular_docs = $wpdb->get_results(
            "SELECT document_id, COUNT(*) as view_count 
             FROM $table_name 
             WHERE action = 'view' $date_clause 
             GROUP BY document_id 
             ORDER BY view_count DESC 
             LIMIT 10"
        );
        
        // Format popular documents with titles
        $popular_formatted = array();
        foreach ($popular_docs as $doc) {
            $post = get_post($doc->document_id);
            if ($post) {
                $popular_formatted[] = array(
                    'id' => $doc->document_id,
                    'title' => $post->post_title,
                    'views' => $doc->view_count,
                    'edit_link' => get_edit_post_link($doc->document_id)
                );
            }
        }
        
        wp_send_json_success(array(
            'view_stats' => $view_stats,
            'download_stats' => $download_stats,
            'popular_docs' => $popular_formatted
        ));
    }
    
    /**
     * Bulk actions AJAX
     */
    public function bulk_action() {
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $document_ids = array_map('intval', $_POST['document_ids']);
        
        $processed = 0;
        
        foreach ($document_ids as $doc_id) {
            switch ($action) {
                case 'feature':
                    update_post_meta($doc_id, '_lift_doc_featured', '1');
                    $processed++;
                    break;
                    
                case 'unfeature':
                    delete_post_meta($doc_id, '_lift_doc_featured');
                    $processed++;
                    break;
                    
                case 'make_private':
                    update_post_meta($doc_id, '_lift_doc_private', '1');
                    $processed++;
                    break;
                    
                case 'make_public':
                    delete_post_meta($doc_id, '_lift_doc_private');
                    $processed++;
                    break;
                    
                case 'reset_stats':
                    delete_post_meta($doc_id, '_lift_doc_views');
                    delete_post_meta($doc_id, '_lift_doc_downloads');
                    delete_post_meta($doc_id, '_lift_doc_total_time');
                    $processed++;
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Processed %d documents', 'lift-docs-system'), $processed)
        ));
    }
    
    /**
     * Upload document AJAX
     */
    public function upload_document() {
        if (!current_user_can('upload_files')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['file'];
        
        // Check file type
        $allowed_types = LIFT_Docs_Settings::get_setting('allowed_file_types', 'pdf,doc,docx,txt,xls,xlsx,ppt,pptx');
        $allowed_types = explode(',', $allowed_types);
        $allowed_types = array_map('trim', $allowed_types);
        
        $file_ext = pathinfo($uploadedfile['name'], PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            wp_send_json_error(__('File type not allowed', 'lift-docs-system'));
        }
        
        // Check file size
        $max_size = LIFT_Docs_Settings::get_setting('max_file_size', 10) * 1024 * 1024; // Convert MB to bytes
        
        if ($uploadedfile['size'] > $max_size) {
            wp_send_json_error(__('File too large', 'lift-docs-system'));
        }
        
        $upload_overrides = array('test_form' => false);
        
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'url' => $movefile['url'],
                'file' => $movefile['file'],
                'type' => $movefile['type'],
                'size' => $uploadedfile['size']
            ));
        } else {
            wp_send_json_error($movefile['error']);
        }
    }
    
    /**
     * Generate secure link for document
     */
    public function generate_secure_link() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'lift-docs-system'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'lift_secure_link_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }
        
        $document_id = intval($_POST['document_id']);
        $expiry_days = intval($_POST['expiry_days']) ?: 7;
        
        if (!$document_id || get_post_type($document_id) !== 'lift_document') {
            wp_send_json_error(__('Invalid document', 'lift-docs-system'));
        }
        
        $secure_links = LIFT_Docs_Secure_Links::get_instance();
        $secure_url = $secure_links->generate_secure_link($document_id, $expiry_days);
        
        if ($secure_url) {
            wp_send_json_success(array(
                'secure_url' => $secure_url,
                'expiry_days' => $expiry_days,
                'expires_at' => date('Y-m-d H:i:s', time() + ($expiry_days * 24 * 60 * 60))
            ));
        } else {
            wp_send_json_error(__('Failed to generate secure link', 'lift-docs-system'));
        }
    }
    
    /**
     * Get document download URL for archive display
     */
    public function get_document_download_url() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'lift_document') {
            wp_send_json_error(__('Invalid document', 'lift-docs-system'));
        }
        
        // Check if document is archived
        $is_archived = get_post_meta($post_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            wp_send_json_error(__('Document has been archived and is no longer accessible', 'lift-docs-system'));
        }
        
        $file_url = get_post_meta($post_id, '_lift_doc_file_url', true);
        
        if ($file_url && LIFT_Docs_Settings::get_setting('show_download_button', true)) {
            $download_url = add_query_arg(array(
                'lift_download' => $post_id,
                'nonce' => wp_create_nonce('lift_download_' . $post_id)
            ), home_url());
            
            wp_send_json_success(array(
                'download_url' => $download_url,
                'file_size' => size_format(get_post_meta($post_id, '_lift_doc_file_size', true))
            ));
        } else {
            wp_send_json_error(__('No download available', 'lift-docs-system'));
        }
    }
    
    /**
     * Get document view count
     */
    public function get_document_views() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'lift_document') {
            wp_send_json_error(__('Invalid document', 'lift-docs-system'));
        }
        
        // Check if document is archived
        $is_archived = get_post_meta($post_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            wp_send_json_error(__('Document has been archived and is no longer accessible', 'lift-docs-system'));
        }
        
        $views = get_post_meta($post_id, '_lift_doc_views', true);
        $views = $views ? intval($views) : 0;
        
        wp_send_json_success(array(
            'views' => $views,
            'formatted_views' => number_format($views)
        ));
    }
    
    /**
     * Track document view
     */
    public function track_document_view() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'lift_document') {
            wp_send_json_error(__('Invalid document', 'lift-docs-system'));
        }
        
        // Check if document is archived
        $is_archived = get_post_meta($post_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            wp_send_json_error(__('Document has been archived and is no longer accessible', 'lift-docs-system'));
        }
        
        // Get current view count
        $views = get_post_meta($post_id, '_lift_doc_views', true);
        $views = $views ? intval($views) : 0;
        
        // Increment view count
        $new_views = $views + 1;
        update_post_meta($post_id, '_lift_doc_views', $new_views);
        
        // Log the view
        $this->log_document_activity($post_id, 'view', array(
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql')
        ));
        
        wp_send_json_success(array(
            'views' => $new_views
        ));
    }
    
    /**
     * Track download
     */
    public function track_download() {
        if (!wp_verify_nonce($_POST['nonce'], 'lift_docs_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }
        
        $document_id = intval($_POST['document_id']);
        
        if (!$document_id || get_post_type($document_id) !== 'lift_document') {
            wp_send_json_error(__('Invalid document', 'lift-docs-system'));
        }
        
        // Check if document is archived
        $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            wp_send_json_error(__('Document has been archived and is no longer accessible', 'lift-docs-system'));
        }
        
        // Get current download count
        $downloads = get_post_meta($document_id, '_lift_doc_downloads', true);
        $downloads = $downloads ? intval($downloads) : 0;
        
        // Increment download count
        $new_downloads = $downloads + 1;
        update_post_meta($document_id, '_lift_doc_downloads', $new_downloads);
        
        // Log the download
        $this->log_document_activity($document_id, 'download', array(
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql')
        ));
        
        wp_send_json_success(array(
            'downloads' => $new_downloads
        ));
    }
    
    /**
     * Log document activity
     */
    private function log_document_activity($document_id, $action, $data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_activity';
        
        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
                'action' => $action,
                'user_id' => $data['user_id'] ?? 0,
                'ip_address' => $data['ip_address'] ?? '',
                'user_agent' => $data['user_agent'] ?? '',
                'created_at' => $data['timestamp'] ?? current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
    
    /**
     * Get document details for modal (Frontend Dashboard)
     */
    public function get_document_details() {
        if (!wp_verify_nonce($_POST['nonce'], 'docs_login_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        $document_id = intval($_POST['document_id']);
        $document = get_post($document_id);
        
        if (!$document || $document->post_type !== 'lift_document') {
            wp_send_json_error('Document not found');
        }
        
        // Check if document is archived
        $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            wp_send_json_error('Document has been archived and is no longer accessible');
        }
        
        // Check if user has access to this document
        $user_id = get_current_user_id();
        $assigned_users = get_post_meta($document_id, '_lift_doc_assigned_users', true);
        
        // If no assignments, only admin and editor can access
        if (empty($assigned_users) || !is_array($assigned_users)) {
            if (!user_can($user_id, 'manage_options') && !user_can($user_id, 'edit_lift_documents')) {
                wp_send_json_error('Access denied');
            }
        } 
        // If assignments exist, check if user is assigned
        else if (!in_array($user_id, $assigned_users)) {
            wp_send_json_error('Access denied');
        }
        
        // Get file URLs
        $file_urls = get_post_meta($document_id, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            $file_urls = array(get_post_meta($document_id, '_lift_doc_file_url', true));
        }
        $file_urls = array_filter($file_urls);
        
        // Build modal content
        $content = '<div class="document-details-modal">';
        
        if ($document->post_content) {
            $content .= '<div class="document-description">';
            $content .= '<h4>' . __('Description', 'lift-docs-system') . '</h4>';
            $content .= wpautop($document->post_content);
            $content .= '</div>';
        }
        
        $content .= '<div class="document-files">';
        $content .= '<h4>' . __('Files', 'lift-docs-system') . ' (' . count($file_urls) . ')</h4>';
        
        foreach ($file_urls as $index => $file_url) {
            $file_number = $index + 1;
            $filename = basename(parse_url($file_url, PHP_URL_PATH));
            
            $content .= '<div class="file-item">';
            $content .= '<div class="file-info">';
            $content .= '<strong>File ' . $file_number . ':</strong> ' . esc_html($filename);
            $content .= '</div>';
            $content .= '<div class="file-actions">';
            $content .= '<button type="button" class="btn btn-primary view-file-btn" data-file-url="' . esc_url($file_url) . '" data-document-id="' . $document_id . '">';
            $content .= '<span class="dashicons dashicons-visibility"></span> ' . __('View', 'lift-docs-system');
            $content .= '</button>';
            $content .= '<button type="button" class="btn btn-secondary download-file-btn" data-file-url="' . esc_url($file_url) . '" data-document-id="' . $document_id . '">';
            $content .= '<span class="dashicons dashicons-download"></span> ' . __('Download', 'lift-docs-system');
            $content .= '</button>';
            $content .= '</div>';
            $content .= '</div>';
        }
        
        $content .= '</div>';
        $content .= '</div>';
        
        wp_send_json_success(array(
            'title' => $document->post_title,
            'content' => $content
        ));
    }
    
    /**
     * Track document action (view/download) from frontend dashboard
     */
    public function track_document_action() {
        if (!wp_verify_nonce($_POST['nonce'], 'docs_login_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        $document_id = intval($_POST['document_id']);
        $action = sanitize_text_field($_POST['doc_action']);
        
        if (!$document_id || !in_array($action, array('view', 'download'))) {
            wp_send_json_error('Invalid parameters');
        }
        
        // Check document exists
        $document = get_post($document_id);
        if (!$document || $document->post_type !== 'lift_document') {
            wp_send_json_error('Document not found');
        }
        
        // Check if document is archived
        $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            wp_send_json_error('Document has been archived and is no longer accessible');
        }
        
        // Log the action
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
                'user_id' => get_current_user_id(),
                'action' => $action,
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to track action');
        }
        
        // Update document meta counts
        if ($action === 'view') {
            $views = get_post_meta($document_id, '_lift_doc_views', true);
            $views = $views ? intval($views) : 0;
            update_post_meta($document_id, '_lift_doc_views', $views + 1);
        } elseif ($action === 'download') {
            $downloads = get_post_meta($document_id, '_lift_doc_downloads', true);
            $downloads = $downloads ? intval($downloads) : 0;
            update_post_meta($document_id, '_lift_doc_downloads', $downloads + 1);
        }
        
        wp_send_json_success(array('message' => 'Action tracked successfully'));
    }
    
    /**
     * Refresh dashboard stats for current user
     */
    public function refresh_dashboard_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'docs_login_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Authentication required');
        }
        
        $user_id = get_current_user_id();
        
        // Get user's assigned documents count
        $all_documents = get_posts(array(
            'post_type' => 'lift_document',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));
        
        $user_documents_count = 0;
        foreach ($all_documents as $document) {
            $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
            
            // If no specific assignments, only admin and editor can see
            if (empty($assigned_users) || !is_array($assigned_users)) {
                if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_lift_documents')) {
                    $user_documents_count++;
                }
            } 
            // Check if user is specifically assigned
            else if (in_array($user_id, $assigned_users)) {
                $user_documents_count++;
            }
        }
        
        // Get user download count
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $download_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND action = 'download'",
            $user_id
        ));
        
        // Get user view count
        $view_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND action = 'view'",
            $user_id
        ));
        
        // Get member since date
        $user = get_user_by('id', $user_id);
        $member_since = date_i18n('M d', strtotime($user->user_registered));
        
        wp_send_json_success(array(
            'stats' => array(
                $user_documents_count,
                $download_count ? $download_count : 0,
                $view_count ? $view_count : 0,
                $member_since
            )
        ));
    }
}
