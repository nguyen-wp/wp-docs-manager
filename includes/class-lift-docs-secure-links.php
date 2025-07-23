<?php
/**
 * Secure Links handler for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Secure_Links {
    
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
        // Add custom rewrite rules and query vars
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_secure_access'));
        
        // Block direct access to documents
        add_action('template_redirect', array($this, 'block_direct_access'));
        
        // Remove from sitemap
        add_filter('wp_sitemaps_posts_query_args', array($this, 'exclude_from_sitemap'), 10, 2);
        add_filter('wpseo_sitemap_exclude_post_type', array($this, 'exclude_from_yoast_sitemap'), 10, 2);
        
        // Block search engines
        add_action('wp_head', array($this, 'add_noindex_meta'));
        add_filter('robots_txt', array($this, 'add_robots_rules'));
        
        // Modify document permalinks in admin
        add_filter('get_sample_permalink_html', array($this, 'modify_permalink_display'), 10, 5);
        
        // Add meta box for secure links
        add_action('add_meta_boxes', array($this, 'add_secure_link_meta_box'));
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'lift_secure_page';
        $vars[] = 'lift_secure';
        return $vars;
    }
    
    /**
     * Add custom rewrite rules for secure access
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^lift-docs/secure/?$', 'index.php?lift_secure_page=1', 'top');
        add_rewrite_tag('%lift_secure_page%', '([0-9]+)');
    }
    
    /**
     * Handle secure access requests
     */
    public function handle_secure_access() {
        if (!get_query_var('lift_secure_page')) {
            return;
        }
        
        $token = $_GET['lift_secure'] ?? '';
        
        if (empty($token)) {
            $this->show_access_denied('Missing security token');
            return;
        }
        
        $document_id = LIFT_Docs_Settings::verify_secure_link($token);
        
        if (!$document_id) {
            $this->show_access_denied('Invalid or expired security token');
            return;
        }
        
        // Check if document exists and is published
        $document = get_post($document_id);
        
        if (!$document || $document->post_type !== 'lift_document' || $document->post_status !== 'publish') {
            $this->show_access_denied('Document not found');
            return;
        }
        
        // Set secure access session
        session_start();
        $_SESSION['lift_secure_access_' . $document_id] = time();
        
        // Display document content directly instead of redirecting
        $this->display_secure_document($document);
        exit;
    }
    
    /**
     * Display secure document content
     */
    private function display_secure_document($document) {
        // Set global post data
        global $post;
        $post = $document;
        setup_postdata($post);
        
        // Get document meta
        $file_url = get_post_meta($document->ID, '_lift_doc_file_url', true);
        $file_size = get_post_meta($document->ID, '_lift_doc_file_size', true);
        $download_count = get_post_meta($document->ID, '_lift_doc_download_count', true);
        
        get_header();
        ?>
        <div class="lift-docs-secure-document">
            <div class="container">
                <div class="secure-access-notice">
                    <span class="security-badge"><?php _e('🔒 Secure Access', 'lift-docs-system'); ?></span>
                    <p><?php _e('You are viewing this document via a secure encrypted link.', 'lift-docs-system'); ?></p>
                </div>
                
                <article class="document-content">
                    <header class="document-header">
                        <h1><?php echo esc_html($document->post_title); ?></h1>
                        
                        <div class="document-meta">
                            <span class="publish-date">
                                <?php _e('Published:', 'lift-docs-system'); ?> 
                                <?php echo get_the_date('F j, Y', $document); ?>
                            </span>
                            
                            <?php if ($file_size): ?>
                            <span class="file-size">
                                <?php _e('Size:', 'lift-docs-system'); ?> 
                                <?php echo size_format($file_size); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($download_count): ?>
                            <span class="download-count">
                                <?php _e('Downloads:', 'lift-docs-system'); ?> 
                                <?php echo number_format($download_count); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </header>
                    
                    <div class="document-body">
                        <?php if ($file_url): ?>
                        <div class="document-download">
                            <a href="<?php echo esc_url($file_url); ?>" class="download-button" target="_blank">
                                <?php _e('📄 Download Document', 'lift-docs-system'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($document->post_content): ?>
                        <div class="document-description">
                            <?php echo apply_filters('the_content', $document->post_content); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Get document categories
                        $categories = get_the_terms($document->ID, 'lift_doc_category');
                        if ($categories && !is_wp_error($categories)):
                        ?>
                        <div class="document-categories">
                            <strong><?php _e('Categories:', 'lift-docs-system'); ?></strong>
                            <?php foreach ($categories as $category): ?>
                                <span class="category-tag"><?php echo esc_html($category->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Get document tags
                        $tags = get_the_terms($document->ID, 'lift_doc_tag');
                        if ($tags && !is_wp_error($tags)):
                        ?>
                        <div class="document-tags">
                            <strong><?php _e('Tags:', 'lift-docs-system'); ?></strong>
                            <?php foreach ($tags as $tag): ?>
                                <span class="tag-label"><?php echo esc_html($tag->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </div>
        
        <style>
        .lift-docs-secure-document {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .secure-access-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .security-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .document-header h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2.2em;
        }
        
        .document-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .document-meta span {
            display: flex;
            align-items: center;
        }
        
        .document-download {
            margin: 25px 0;
            text-align: center;
        }
        
        .download-button {
            background: #0073aa;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .download-button:hover {
            background: #005a87;
            color: white;
        }
        
        .document-description {
            line-height: 1.6;
            margin: 25px 0;
        }
        
        .document-categories,
        .document-tags {
            margin: 20px 0;
        }
        
        .category-tag,
        .tag-label {
            background: #f1f1f1;
            padding: 4px 12px;
            border-radius: 15px;
            margin: 0 5px 5px 0;
            display: inline-block;
            font-size: 12px;
            color: #666;
        }
        
        .category-tag {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .tag-label {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        @media (max-width: 768px) {
            .document-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .document-header h1 {
                font-size: 1.8em;
            }
        }
        </style>
        <?php
        get_footer();
        
        // Track document view
        $this->track_document_view($document->ID);
        
        wp_reset_postdata();
    }
    
    /**
     * Block direct access to documents
     */
    public function block_direct_access() {
        if (!is_singular('lift_document')) {
            return;
        }
        
        // Skip if secure links are disabled
        if (!LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
            return;
        }
        
        // Allow admin access
        if (current_user_can('edit_posts')) {
            return;
        }
        
        // Check for secure access session
        session_start();
        $document_id = get_the_ID();
        $session_key = 'lift_secure_access_' . $document_id;
        
        if (isset($_SESSION[$session_key])) {
            // Check if session is still valid (1 hour)
            if (time() - $_SESSION[$session_key] < 3600) {
                return; // Allow access
            } else {
                unset($_SESSION[$session_key]); // Clean expired session
            }
        }
        
        // Block access
        $this->show_access_denied('Direct access to this document is not allowed');
    }
    
    /**
     * Show access denied page
     */
    private function show_access_denied($message = '') {
        status_header(403);
        
        get_header();
        ?>
        <div class="lift-docs-access-denied">
            <div class="container">
                <h1><?php _e('Access Denied', 'lift-docs-system'); ?></h1>
                <p><?php echo esc_html($message ?: __('You do not have permission to access this document.', 'lift-docs-system')); ?></p>
                <p>
                    <a href="<?php echo home_url(); ?>" class="button">
                        <?php _e('Return to Homepage', 'lift-docs-system'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <style>
        .lift-docs-access-denied {
            padding: 60px 0;
            text-align: center;
        }
        
        .lift-docs-access-denied h1 {
            color: #dc3232;
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .lift-docs-access-denied p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }
        
        .lift-docs-access-denied .button {
            background: #0073aa;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }
        </style>
        <?php
        get_footer();
        exit;
    }
    
    /**
     * Exclude documents from WordPress sitemap
     */
    public function exclude_from_sitemap($args, $post_type) {
        if ($post_type === 'lift_document' && LIFT_Docs_Settings::get_setting('hide_from_sitemap', false)) {
            $args['meta_query'] = array(
                array(
                    'key' => '_lift_doc_exclude_sitemap',
                    'value' => '1',
                    'compare' => '!='
                )
            );
        }
        
        return $args;
    }
    
    /**
     * Exclude from Yoast SEO sitemap
     */
    public function exclude_from_yoast_sitemap($excluded, $post_type) {
        if ($post_type === 'lift_document' && LIFT_Docs_Settings::get_setting('hide_from_sitemap', false)) {
            return true;
        }
        
        return $excluded;
    }
    
    /**
     * Add noindex meta for documents
     */
    public function add_noindex_meta() {
        if (is_singular('lift_document') && LIFT_Docs_Settings::get_setting('hide_from_sitemap', false)) {
            echo '<meta name="robots" content="noindex, nofollow, nosnippet, noarchive" />' . "\n";
        }
    }
    
    /**
     * Add robots.txt rules
     */
    public function add_robots_rules($output) {
        if (LIFT_Docs_Settings::get_setting('hide_from_sitemap', false)) {
            $output .= "\n# LIFT Docs System - Block document access\n";
            $output .= "Disallow: /documents/\n";
            $output .= "Disallow: /document-category/\n";
            $output .= "Disallow: /document-tag/\n";
            $output .= "Disallow: /lift-docs/\n";
        }
        
        return $output;
    }
    
    /**
     * Modify permalink display in admin
     */
    public function modify_permalink_display($return, $post_id, $new_title, $new_slug, $post) {
        if ($post->post_type !== 'lift_document') {
            return $return;
        }
        
        if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
            $secure_link = LIFT_Docs_Settings::generate_secure_link($post_id);
            
            $return .= '<div class="lift-docs-secure-link" style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">';
            $return .= '<strong>' . __('Secure Link:', 'lift-docs-system') . '</strong><br>';
            $return .= '<input type="text" value="' . esc_attr($secure_link) . '" readonly class="regular-text" onclick="this.select()" />';
            $return .= '<button type="button" class="button" onclick="copySecureLink(this)" style="margin-left: 5px;">' . __('Copy', 'lift-docs-system') . '</button>';
            $return .= '<p class="description">' . __('Use this secure link to share the document. Direct access to the permalink is blocked.', 'lift-docs-system') . '</p>';
            $return .= '</div>';
            
            $return .= '<script>
                function copySecureLink(button) {
                    var input = button.previousElementSibling;
                    input.select();
                    document.execCommand("copy");
                    button.textContent = "' . __('Copied!', 'lift-docs-system') . '";
                    setTimeout(function() {
                        button.textContent = "' . __('Copy', 'lift-docs-system') . '";
                    }, 2000);
                }
            </script>';
        }
        
        return $return;
    }
    
    /**
     * Add secure link meta box
     */
    public function add_secure_link_meta_box() {
        add_meta_box(
            'lift-docs-secure-links',
            __('Secure Links', 'lift-docs-system'),
            array($this, 'secure_link_meta_box_callback'),
            'lift_document',
            'side',
            'default'
        );
    }
    
    /**
     * Secure link meta box callback
     */
    public function secure_link_meta_box_callback($post) {
        if (!LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
            echo '<p>' . __('Secure links are disabled. Enable them in settings.', 'lift-docs-system') . '</p>';
            return;
        }
        
        $secure_link = LIFT_Docs_Settings::generate_secure_link($post->ID);
        $expiry_hours = LIFT_Docs_Settings::get_setting('secure_link_expiry', 24);
        
        ?>
        <div class="lift-docs-secure-link-meta">
            <p><strong><?php _e('Current Secure Link:', 'lift-docs-system'); ?></strong></p>
            <textarea readonly class="widefat" rows="3" onclick="this.select()"><?php echo esc_textarea($secure_link); ?></textarea>
            
            <p class="description">
                <?php 
                if ($expiry_hours > 0) {
                    printf(__('This link expires in %d hours.', 'lift-docs-system'), $expiry_hours);
                } else {
                    _e('This link never expires.', 'lift-docs-system');
                }
                ?>
            </p>
            
            <p>
                <button type="button" class="button" onclick="generateNewSecureLink(<?php echo $post->ID; ?>)">
                    <?php _e('Generate New Link', 'lift-docs-system'); ?>
                </button>
                
                <button type="button" class="button" onclick="copyToClipboard(this)">
                    <?php _e('Copy Link', 'lift-docs-system'); ?>
                </button>
            </p>
            
            <div class="custom-expiry" style="margin-top: 15px;">
                <label for="custom-expiry"><?php _e('Generate link with custom expiry:', 'lift-docs-system'); ?></label>
                <select id="custom-expiry">
                    <option value="1">1 <?php _e('hour', 'lift-docs-system'); ?></option>
                    <option value="6">6 <?php _e('hours', 'lift-docs-system'); ?></option>
                    <option value="24" selected>24 <?php _e('hours', 'lift-docs-system'); ?></option>
                    <option value="72">3 <?php _e('days', 'lift-docs-system'); ?></option>
                    <option value="168">1 <?php _e('week', 'lift-docs-system'); ?></option>
                    <option value="0"><?php _e('Never expire', 'lift-docs-system'); ?></option>
                </select>
                <button type="button" class="button" onclick="generateCustomLink(<?php echo $post->ID; ?>)">
                    <?php _e('Generate', 'lift-docs-system'); ?>
                </button>
            </div>
        </div>
        
        <script>
        function generateNewSecureLink(postId) {
            // This would need AJAX implementation
            location.reload();
        }
        
        function generateCustomLink(postId) {
            var expiry = document.getElementById('custom-expiry').value;
            // This would need AJAX implementation
            alert('Custom link generation requires AJAX implementation');
        }
        
        function copyToClipboard(button) {
            var textarea = button.closest('.lift-docs-secure-link-meta').querySelector('textarea');
            textarea.select();
            document.execCommand('copy');
            
            var originalText = button.textContent;
            button.textContent = '<?php _e("Copied!", "lift-docs-system"); ?>';
            setTimeout(function() {
                button.textContent = originalText;
            }, 2000);
        }
        </script>
        <?php
    }
    
    /**
     * Get secure link with custom expiry
     */
    public static function get_secure_link_with_expiry($document_id, $expiry_hours) {
        return LIFT_Docs_Settings::generate_secure_link($document_id, $expiry_hours);
    }
    
    /**
     * Track document view
     */
    private function track_document_view($document_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user_id = $user_id ? $user_id : null;
        
        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
                'user_id' => $user_id,
                'action' => 'secure_view',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Update view count
        $view_count = get_post_meta($document_id, '_lift_doc_view_count', true);
        $view_count = $view_count ? intval($view_count) : 0;
        update_post_meta($document_id, '_lift_doc_view_count', $view_count + 1);
    }
}
