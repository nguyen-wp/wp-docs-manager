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
        add_action('template_redirect', array($this, 'handle_secure_download'));
        
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
        
        // Note: Secure Links metabox is now integrated into Document Details metabox
        // No separate metabox needed
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'lift_secure_page';
        $vars[] = 'lift_secure';
        $vars[] = 'lift_download';
        return $vars;
    }
    
    /**
     * Add custom rewrite rules for secure access
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^document-files/secure/?$', 'index.php?lift_secure_page=1', 'top');
        add_rewrite_rule('^document-files/download/?$', 'index.php?lift_download=1', 'top');
        add_rewrite_tag('%lift_secure_page%', '([0-9]+)');
        add_rewrite_tag('%lift_download%', '([0-9]+)');
    }
    
    /**
     * Handle secure access requests
     */
    public function handle_secure_access() {
        // Check both query var AND URL pattern for backwards compatibility
        $is_secure_request = get_query_var('lift_secure_page') || 
                            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/document-files/secure/') !== false);
        
        if (!$is_secure_request) {
            return;
        }
        
        $token = $_GET['lift_secure'] ?? '';
        
        if (empty($token)) {
            $this->show_access_denied('Missing security token');
            return;
        }
        
        // Decode token before verification
        $verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
        
        if (!$verification || !isset($verification['document_id'])) {
            $this->show_access_denied('Invalid or expired security token');
            return;
        }
        
        $document_id = $verification['document_id'];
        
        // Check if document exists and is published
        $document = get_post($document_id);
        
        if (!$document || $document->post_type !== 'lift_document' || $document->post_status !== 'publish') {
            $this->show_access_denied('Document not found');
            return;
        }
        
        // Check if user has permission to view document
        if (!LIFT_Docs_Settings::user_can_view_document($document_id)) {
            $this->show_access_denied('You do not have permission to view this document');
            return;
        }
        
        // Track document view BEFORE displaying content
        $this->track_document_view($document_id);
        
        // Set secure access session
        session_start();
        $_SESSION['lift_secure_access_' . $document_id] = time();
        
        // Display document content directly instead of redirecting
        $this->display_secure_document($document);
        exit;
    }
    
    /**
     * Handle secure download requests
     */
    public function handle_secure_download() {
        // Check both query var AND URL pattern for backwards compatibility
        $is_download_request = get_query_var('lift_download') || 
                              (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/document-files/download/') !== false);
        
        if (!$is_download_request) {
            return;
        }
        
        $token = $_GET['lift_secure'] ?? '';
        
        if (empty($token)) {
            status_header(403);
            die('Missing security token');
        }
        
        // Use primary verification method (decode token first)
        $verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
        
        if (!$verification || !isset($verification['document_id'])) {
            status_header(403);
            die('Invalid or expired download link. Please request a new download link.');
        }
        
        $document_id = $verification['document_id'];
        
        // Check if document exists and is published
        $document = get_post($document_id);
        
        if (!$document || $document->post_type !== 'lift_document' || $document->post_status !== 'publish') {
            status_header(404);
            die('Document not found');
        }
        
        // Check if user has permission to download document
        if (!LIFT_Docs_Settings::user_can_download_document($document_id)) {
            status_header(403);
            die('You do not have permission to download this document');
        }
        
        // Get file URL(s) based on file index
        $file_index = isset($verification['file_index']) ? intval($verification['file_index']) : 0;
        
        // Try to get multiple file URLs first
        $file_urls = get_post_meta($document_id, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            // Fallback to single file URL for backward compatibility
            $single_file_url = get_post_meta($document_id, '_lift_doc_file_url', true);
            if ($single_file_url) {
                $file_urls = array($single_file_url);
            }
        }
        
        if (empty($file_urls) || !isset($file_urls[$file_index])) {
            status_header(404);
            die('File not found');
        }
        
        $file_url = $file_urls[$file_index];
        
        if (empty($file_url)) {
            status_header(404);
            die('File not found');
        }
        
        // Track download
        $this->track_document_download($document_id);
        
        // Serve file securely
        $this->serve_secure_file($file_url, $document->post_title);
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
        
        // Get global layout settings
        $settings = $this->get_global_layout_settings();
        
        // Check if clean layout should be used (like login page)
        $use_clean_layout = apply_filters('lift_docs_secure_use_clean_layout', true);
        
        if ($use_clean_layout) {
            $this->display_clean_secure_document($document, $settings);
        } else {
            $this->display_themed_secure_document($document, $settings);
        }
        
        wp_reset_postdata();
    }
    
    /**
     * Display secure document with clean layout (no theme header/footer)
     */
    private function display_clean_secure_document($document, $settings) {
        // Get document meta - support multiple files
        $file_urls = get_post_meta($document->ID, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            // Fallback to single file for backward compatibility
            $single_file_url = get_post_meta($document->ID, '_lift_doc_file_url', true);
            if ($single_file_url) {
                $file_urls = array($single_file_url);
            }
        }

        $file_size = get_post_meta($document->ID, '_lift_doc_file_size', true);
        $download_count = get_post_meta($document->ID, '_lift_doc_download_count', true);
        
        // Get custom colors (can be added to admin settings later)
        $bg_color = get_option('lift_docs_secure_bg_color', '#f8f9fa');
        $container_bg = get_option('lift_docs_secure_container_bg', '#ffffff');
        $text_color = get_option('lift_docs_secure_text_color', '#333333');
        $accent_color = get_option('lift_docs_secure_accent_color', '#1976d2');
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($document->post_title); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    background-color: <?php echo esc_attr($bg_color); ?>;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    line-height: 1.6;
                    min-height: 100vh;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                }
                
                /* Hide unwanted elements */
                .back-to-top,
                #back-to-top,
                .scroll-to-top,
                [class*="back-to-top"],
                [id*="back-to-top"],
                [class*="scroll-top"],
                [id*="scroll-top"] {
                    display: none !important;
                    visibility: hidden !important;
                }
                
                .lift-docs-secure-container {
                    max-width: 1000px;
                    margin: 0 auto;
                    background: <?php echo esc_attr($container_bg); ?>;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    width: 100%;
                }
                
                .secure-header {
                    background: <?php echo esc_attr($accent_color); ?>;
                    color: white;
                    padding: 20px 30px;
                    text-align: center;
                }
                
                .secure-badge {
                    display: inline-block;
                    background: rgba(255, 255, 255, 0.2);
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 14px;
                    margin-bottom: 10px;
                }
                
                .document-content {
                    padding: 40px;
                }
                
                .document-title {
                    font-size: 32px;
                    font-weight: 600;
                    margin: 0 0 20px 0;
                    color: <?php echo esc_attr($text_color); ?>;
                    line-height: 1.2;
                }
                
                .document-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 20px;
                    margin-bottom: 30px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    font-size: 14px;
                }
                
                .document-meta span {
                    color: #666;
                }
                
                .document-meta strong {
                    color: <?php echo esc_attr($text_color); ?>;
                }
                
                .document-description {
                    margin-bottom: 40px;
                    font-size: 16px;
                    line-height: 1.7;
                }
                
                .document-description h1,
                .document-description h2,
                .document-description h3,
                .document-description h4,
                .document-description h5,
                .document-description h6 {
                    color: <?php echo esc_attr($text_color); ?>;
                    margin-top: 30px;
                    margin-bottom: 15px;
                }
                
                .document-description p {
                    margin-bottom: 15px;
                }
                
                .document-files h3 {
                    color: <?php echo esc_attr($text_color); ?>;
                    margin-bottom: 20px;
                    font-size: 20px;
                }
                
                .file-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    margin-bottom: 15px;
                    border: 1px solid #e9ecef;
                }
                
                .file-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    flex: 1;
                }
                
                .file-icon {
                    font-size: 28px;
                }
                
                .file-details h4 {
                    margin: 0;
                    font-size: 16px;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-weight: 600;
                }
                
                .file-details p {
                    margin: 4px 0 0 0;
                    font-size: 14px;
                    color: #666;
                }
                
                .download-actions {
                    display: flex;
                    gap: 10px;
                }
                
                .btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    text-decoration: none;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    transition: opacity 0.2s ease;
                }
                
                .btn-primary {
                    background: <?php echo esc_attr($accent_color); ?>;
                    color: white;
                }
                
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .btn:hover {
                    opacity: 0.9;
                }
                
                .download-info {
                    font-size: 14px;
                    color: #666;
                    text-align: center;
                    margin: 20px 0 0 0;
                    padding: 15px;
                    background: #e3f2fd;
                    border-radius: 6px;
                }
                
                .document-categories,
                .document-tags {
                    margin-bottom: 25px;
                }
                
                .document-categories strong,
                .document-tags strong {
                    color: <?php echo esc_attr($text_color); ?>;
                    display: block;
                    margin-bottom: 8px;
                }
                
                .category-tag,
                .tag-label {
                    display: inline-block;
                    padding: 6px 12px;
                    border-radius: 4px;
                    margin: 2px 4px 2px 0;
                    font-size: 12px;
                    font-weight: 500;
                }
                
                .category-tag {
                    background: #f0f0f0;
                    color: #333;
                }
                
                .tag-label {
                    background: #e3f2fd;
                    color: #1565c0;
                }
                
                .related-documents {
                    margin-top: 40px;
                    padding-top: 30px;
                    border-top: 1px solid #e9ecef;
                }
                
                @media (max-width: 768px) {
                    body {
                        padding: 10px;
                    }
                    
                    .document-content {
                        padding: 30px 20px;
                    }
                    
                    .secure-header {
                        padding: 15px 20px;
                    }
                    
                    .document-title {
                        font-size: 24px;
                    }
                    
                    .document-meta {
                        flex-direction: column;
                        gap: 10px;
                        padding: 15px;
                    }
                    
                    .file-item {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                        padding: 15px;
                    }
                    
                    .download-actions {
                        width: 100%;
                        justify-content: stretch;
                    }
                    
                    .btn {
                        flex: 1;
                        justify-content: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class="lift-docs-secure-container">
                
                <?php if ($settings['show_secure_access_notice']): ?>
                <div class="secure-header">
                    <div class="secure-badge"><i class="fas fa-lock"></i> Secure Access</div>
                    <p style="margin: 0; opacity: 0.9;"><?php _e('You are viewing this document via a secure encrypted link.', 'lift-docs-system'); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="document-content">
                    <?php if ($settings['show_document_header']): ?>
                    <h1 class="document-title"><?php echo esc_html($document->post_title); ?></h1>
                    
                    <?php if ($settings['show_document_meta']): ?>
                    <div class="document-meta">
                        <span>
                            <strong><?php _e('Published:', 'lift-docs-system'); ?></strong>
                            <?php echo get_the_date('F j, Y', $document); ?>
                        </span>
                        
                        <?php if ($file_size): ?>
                        <span>
                            <strong><?php _e('Size:', 'lift-docs-system'); ?></strong>
                            <?php echo size_format($file_size); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($download_count): ?>
                        <span>
                            <strong><?php _e('Downloads:', 'lift-docs-system'); ?></strong>
                            <?php echo number_format($download_count); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php
                        // Show categories in meta if enabled
                        $categories = get_the_terms($document->ID, 'lift_doc_category');
                        if ($categories && !is_wp_error($categories)):
                        ?>
                        <span>
                            <strong><?php _e('Category:', 'lift-docs-system'); ?></strong>
                            <?php echo esc_html($categories[0]->name); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_document_description'] && $document->post_content): ?>
                    <div class="document-description">
                        <?php 
                        $content = apply_filters('the_content', $document->post_content);
                        echo $content;
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_download_button'] && !empty($file_urls)): ?>
                    <div class="document-files">
                        <h3><i class="fas fa-file"></i> <?php _e('Document Files', 'lift-docs-system'); ?></h3>
                        
                        <?php foreach ($file_urls as $index => $file_url): ?>
                        <?php
                        $file_name = basename($file_url);
                        $file_extension = strtoupper(pathinfo($file_name, PATHINFO_EXTENSION));
                        $file_icon = $this->get_file_icon($file_extension);
                        
                        // Create secure download URL with file index  
                        $secure_download_url = LIFT_Docs_Settings::generate_secure_download_link($document->ID, 0, $index);
                        ?>
                        <div class="file-item">
                            <div class="file-info">
                                <span class="file-icon"><?php echo $file_icon; ?></span>
                                <div class="file-details">
                                    <h4><?php echo esc_html($file_name); ?></h4>
                                    <p><?php echo esc_html($file_extension); ?> File</p>
                                </div>
                            </div>
                            
                            <div class="download-actions">
                                <a href="<?php echo esc_url($secure_download_url); ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-download"></i> <?php _e('Download', 'lift-docs-system'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <p class="download-info" style="margin-top: 20px; font-size: 14px; color: #666;">
                            <?php _e('All downloads are secure and encrypted', 'lift-docs-system'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_document_meta']): ?>
                    <?php
                    // Show categories and tags like in themed version
                    $categories = get_the_terms($document->ID, 'lift_doc_category');
                    if ($categories && !is_wp_error($categories)):
                    ?>
                    <div class="document-categories" style="margin-bottom: 20px;">
                        <strong><?php _e('Categories:', 'lift-docs-system'); ?></strong>
                        <?php foreach ($categories as $category): ?>
                            <span class="category-tag" style="display: inline-block; background: #f0f0f0; padding: 4px 8px; border-radius: 4px; margin: 2px; font-size: 12px;"><?php echo esc_html($category->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php
                    // Show tags
                    $tags = get_the_terms($document->ID, 'lift_doc_tag');
                    if ($tags && !is_wp_error($tags)):
                    ?>
                    <div class="document-tags" style="margin-bottom: 20px;">
                        <strong><?php _e('Tags:', 'lift-docs-system'); ?></strong>
                        <?php foreach ($tags as $tag): ?>
                            <span class="tag-label" style="display: inline-block; background: #e3f2fd; padding: 4px 8px; border-radius: 4px; margin: 2px; font-size: 12px; color: #1565c0;"><?php echo esc_html($tag->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Display secure document with theme layout (original method)
     */
    private function display_themed_secure_document($document, $settings) {
        // Get document meta - support multiple files
        $file_urls = get_post_meta($document->ID, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            // Fallback to single file for backward compatibility
            $single_file_url = get_post_meta($document->ID, '_lift_doc_file_url', true);
            if ($single_file_url) {
                $file_urls = array($single_file_url);
            }
        }
        
        $file_size = get_post_meta($document->ID, '_lift_doc_file_size', true);
        $download_count = get_post_meta($document->ID, '_lift_doc_download_count', true);
        
        get_header();
        ?>
        <div class="lift-docs-secure-document <?php echo esc_attr($settings['layout_style']); ?>">
            <div class="container">
                
                <?php if ($settings['show_secure_access_notice']): ?>
                <div class="secure-access-notice">
                    <span class="security-badge"><i class="fas fa-lock"></i> <?php _e('Secure Access', 'lift-docs-system'); ?></span>
                    <p><?php _e('You are viewing this document via a secure encrypted link.', 'lift-docs-system'); ?></p>
                </div>
                <?php endif; ?>
                
                <article class="document-content">
                    <?php if ($settings['show_document_header']): ?>
                    <header class="document-header">
                        <h1><?php echo esc_html($document->post_title); ?></h1>
                        
                        <?php if ($settings['show_document_meta']): ?>
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
                            
                            <?php
                            // Show categories in meta if enabled
                            $categories = get_the_terms($document->ID, 'lift_doc_category');
                            if ($categories && !is_wp_error($categories)):
                            ?>
                            <span class="document-categories-meta">
                                <?php _e('Category:', 'lift-docs-system'); ?> 
                                <?php echo esc_html($categories[0]->name); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </header>
                    <?php endif; ?>
                    
                    <div class="document-body">
                        <?php if ($settings['show_download_button'] && !empty($file_urls)): ?>
                        <div class="document-download">
                            
                                <h3><i class="fas fa-file"></i> <?php _e('Download Files', 'lift-docs-system'); ?></h3>
                                <div class="multiple-downloads">
                                    <?php foreach ($file_urls as $index => $url): ?>
                                        <?php 
                                        $file_name = basename(parse_url($url, PHP_URL_PATH));
                                        $download_token = $_GET['lift_secure'] ?? '';
                                        
                                        // Create secure download URL with file index
                                        $secure_download_url = LIFT_Docs_Settings::generate_secure_download_link($document->ID, 0, $index);
                                        ?>
                                        <div class="download-item">
                                            <a href="<?php echo esc_url($secure_download_url); ?>" class="download-button">
                                                <?php echo esc_html($file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1)); ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="download-info">
                                    <?php _e('All downloads are secure and encrypted', 'lift-docs-system'); ?>
                                </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($settings['show_document_description'] && $document->post_content): ?>
                        <div class="document-description">
                            <?php echo apply_filters('the_content', $document->post_content); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($settings['show_document_meta']): ?>
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
                        <?php endif; ?>
                        
                        <?php if ($settings['show_related_docs']): ?>
                        <div class="related-documents">
                            <?php echo $this->get_related_documents($document->ID); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </div>
        
        <?php echo $this->get_dynamic_styles($settings); ?>
        
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
        
        // Use clean layout như login page
        $this->display_clean_access_denied($message);
        exit;
    }
    
    /**
     * Display clean access denied page (consistent với login page)
     */
    private function display_clean_access_denied($message = '') {
        // Get custom colors để consistent với login page
        $bg_color = get_option('lift_docs_login_bg_color', '#f0f4f8');
        $container_bg = get_option('lift_docs_login_form_bg', '#ffffff');
        $text_color = get_option('lift_docs_login_text_color', '#333333');
        $btn_color = get_option('lift_docs_login_btn_color', '#1976d2');
        $error_color = '#dc3545';
        
        $logo_id = get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Access Denied', 'lift-docs-system'); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: <?php echo esc_attr($bg_color); ?>;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                /* Hide unwanted elements */
                .back-to-top,
                #back-to-top,
                .scroll-to-top,
                [class*="back-to-top"],
                [id*="back-to-top"],
                [class*="scroll-top"],
                [id*="scroll-top"] {
                    display: none !important;
                    visibility: hidden !important;
                }
                
                .lift-access-denied-container {
                    width: 100%;
                    max-width: 500px;
                    margin: 20px;
                }
                
                .lift-access-denied-logo {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .lift-access-denied-logo img {
                    max-width: 200px;
                    max-height: 80px;
                    height: auto;
                }
                
                .lift-access-denied-wrapper {
                    background: <?php echo esc_attr($container_bg); ?>;
                    padding: 50px 40px;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    text-align: center;
                }
                
                .error-icon {
                    font-size: 64px;
                    color: <?php echo esc_attr($error_color); ?>;
                    margin-bottom: 20px;
                }
                
                .error-title {
                    font-size: 28px;
                    font-weight: 600;
                    color: <?php echo esc_attr($error_color); ?>;
                    margin: 0 0 20px 0;
                }
                
                .error-message {
                    font-size: 16px;
                    color: #666;
                    margin-bottom: 30px;
                    line-height: 1.5;
                }
                
                .error-actions {
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                
                .btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    text-decoration: none;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    transition: opacity 0.2s ease;
                }
                
                .btn-primary {
                    background: <?php echo esc_attr($btn_color); ?>;
                    color: white;
                }
                
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .btn:hover {
                    opacity: 0.9;
                }
                
                .error-details {
                    margin-top: 30px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border-left: 4px solid <?php echo esc_attr($error_color); ?>;
                }
                
                .error-details h4 {
                    margin: 0 0 10px 0;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-size: 16px;
                }
                
                .error-details ul {
                    margin: 0;
                    padding-left: 20px;
                    text-align: left;
                }
                
                .error-details li {
                    margin-bottom: 5px;
                    color: #666;
                    font-size: 14px;
                }
                
                @media (max-width: 768px) {
                    .lift-access-denied-container {
                        margin: 10px;
                    }
                    
                    .lift-access-denied-wrapper {
                        padding: 30px 20px;
                    }
                    
                    .error-title {
                        font-size: 24px;
                    }
                    
                    .error-actions {
                        flex-direction: column;
                    }
                    
                    .btn {
                        width: 100%;
                        justify-content: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class="lift-access-denied-container">
                
                <?php if ($logo_url): ?>
                <div class="lift-access-denied-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
                </div>
                <?php endif; ?>
                
                <div class="lift-access-denied-wrapper">
                    <div class="error-icon"><i class="fas fa-lock" style="font-size: 1.5em; color: #dc3232;"></i></div>
                    
                    <h1 class="error-title"><?php _e('Access Denied', 'lift-docs-system'); ?></h1>
                    
                    <p class="error-message">
                        <?php echo esc_html($message ?: __('You do not have permission to access this document.', 'lift-docs-system')); ?>
                    </p>
                    
                    <div class="error-actions">
                        <a href="<?php echo home_url('/document-login/'); ?>" class="btn btn-primary">
                            <i class="fas fa-lock"></i> <?php _e('Document Login', 'lift-docs-system'); ?>
                        </a>
                        <a href="<?php echo home_url(); ?>" class="btn btn-secondary">
                            <i class="fas fa-home"></i> <?php _e('Return Home', 'lift-docs-system'); ?>
                        </a>
                    </div>
                    
                </div>
            </div>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
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
            $output .= "Disallow: /document-files/secure/\n";
            $output .= "Disallow: /document-files/download/\n";
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
     * NOTE: This metabox is no longer used - secure links are now integrated into Document Details metabox
     */
    /*
    public function add_secure_link_meta_box() {
        add_meta_box(
            'lift-docs-secure-links',
            __('Secure Links', 'lift-docs-system'),
            array($this, 'secure_link_meta_box_callback'),
            'lift_document',
            'normal',
            'default'
        );
    }
    */
    
    /**
     * Secure link meta box callback
     * NOTE: This method is no longer used - secure links are now integrated into Document Details metabox
     */
    /*
    public function secure_link_meta_box_callback($post) {
        if (!LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
            echo '<p>' . __('Secure links are disabled. Enable them in settings.', 'lift-docs-system') . '</p>';
            return;
        }
        
        $secure_link = LIFT_Docs_Settings::generate_secure_link($post->ID);
        
        // Content moved to Document Details metabox
        echo '<p>' . __('Secure links are now integrated into the Document Details metabox above.', 'lift-docs-system') . '</p>';
    }
    */
    
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
        
        // Insert into analytics table
        $result = $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
                'user_id' => $user_id,
                'action' => 'view',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Update view count using the correct meta key
        $current_views = get_post_meta($document_id, '_lift_doc_views', true);
        $view_count = $current_views ? intval($current_views) : 0;
        $new_count = $view_count + 1;
        
        update_post_meta($document_id, '_lift_doc_views', $new_count);
    }
    
    /**
     * Track document download
     */
    private function track_document_download($document_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user_id = $user_id ? $user_id : null;
        
        // Insert into analytics table
        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
                'user_id' => $user_id,
                'action' => 'download',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Update download count using the correct meta key
        $current_downloads = get_post_meta($document_id, '_lift_doc_downloads', true);
        $download_count = $current_downloads ? intval($current_downloads) : 0;
        $new_count = $download_count + 1;
        
        update_post_meta($document_id, '_lift_doc_downloads', $new_count);
    }
    
    /**
     * Serve file securely
     */
    private function serve_secure_file($file_url, $filename) {
        // Clean filename for download
        $clean_filename = sanitize_file_name($filename);
        $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
        
        if ($file_extension) {
            $clean_filename .= '.' . $file_extension;
        }
        
        // Get file path
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        
        // Check if file exists locally
        if (file_exists($file_path)) {
            $this->serve_local_file($file_path, $clean_filename);
        } else {
            // File is external or not found locally - redirect with headers
            $this->serve_remote_file($file_url, $clean_filename);
        }
    }
    
    /**
     * Serve local file
     */
    private function serve_local_file($file_path, $filename) {
        $mime_type = wp_check_filetype($file_path)['type'] ?: 'application/octet-stream';
        $file_size = filesize($file_path);
        
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: 0');
        
        // Prevent direct file access disclosure
        header('X-Robots-Tag: noindex, nofollow');
        
        // Read and output file
        $handle = fopen($file_path, 'rb');
        
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }
    }
    
    /**
     * Serve remote file
     */
    private function serve_remote_file($file_url, $filename) {
        // Get file info
        $response = wp_remote_head($file_url);
        
        if (is_wp_error($response)) {
            status_header(404);
            die('File not accessible');
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $mime_type = $headers['content-type'] ?? 'application/octet-stream';
        $file_size = $headers['content-length'] ?? '';
        
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        if ($file_size) {
            header('Content-Length: ' . $file_size);
        }
        
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: 0');
        header('X-Robots-Tag: noindex, nofollow');
        
        // Stream file content
        $file_response = wp_remote_get($file_url, array(
            'timeout' => 300, // 5 minutes timeout
            'stream' => true
        ));
        
        if (is_wp_error($file_response)) {
            status_header(404);
            die('File not accessible');
        }
        
        // Output file content
        echo wp_remote_retrieve_body($file_response);
    }
    
    /**
     * Get global layout settings
     */
    private function get_global_layout_settings() {
        $global_settings = get_option('lift_docs_settings', array());
        
        $default_settings = array(
            'show_secure_access_notice' => true,
            'show_document_header' => true,
            'show_document_meta' => true,
            'show_document_description' => true,
            'show_download_button' => true,
            'show_related_docs' => true,
            'layout_style' => 'default'
        );
        
        // Use global settings instead of post meta
        $layout_settings = array();
        foreach ($default_settings as $key => $default_value) {
            $layout_settings[$key] = isset($global_settings[$key]) ? $global_settings[$key] : $default_value;
        }
        
        return $layout_settings;
    }
    
    /**
     * Public debug method to get layout settings
     */
    public function debug_get_layout_settings() {
        return $this->get_global_layout_settings();
    }
    
    /**
     * Get related documents
     */
    private function get_related_documents($doc_id) {
        $output = '';
        
        // Get documents in same category
        $categories = get_the_terms($doc_id, 'lift_doc_category');
        if ($categories && !is_wp_error($categories)) {
            $category_ids = wp_list_pluck($categories, 'term_id');
            
            $related_docs = get_posts(array(
                'post_type' => 'lift_document',
                'posts_per_page' => 5,
                'post__not_in' => array($doc_id),
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'lift_doc_category',
                        'field' => 'term_id',
                        'terms' => $category_ids
                    )
                )
            ));
            
            if ($related_docs) {
                $output .= '<div class="related-documents-section">';
                $output .= '<h3>' . __('Related Documents', 'lift-docs-system') . '</h3>';
                $output .= '<div class="related-docs-list">';
                
                foreach ($related_docs as $doc) {
                    $output .= '<div class="related-doc-item">';
                    $output .= '<h4><a href="' . get_permalink($doc->ID) . '">' . esc_html($doc->post_title) . '</a></h4>';
                    if ($doc->post_excerpt) {
                        $output .= '<p>' . esc_html($doc->post_excerpt) . '</p>';
                    }
                    $output .= '</div>';
                }
                
                $output .= '</div>';
                $output .= '</div>';
            }
        }
        
        return $output;
    }
    
    /**
     * Get dynamic styles based on layout settings
     */
    private function get_dynamic_styles($settings) {
        ob_start();
        ?>
        <style>
        .lift-docs-secure-document {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        <?php if ($settings['layout_style'] === 'minimal'): ?>
        .lift-docs-secure-document {
            max-width: 600px;
        }
        
        .document-header h1 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        
        .document-meta {
            font-size: 12px;
            gap: 15px;
        }
        <?php elseif ($settings['layout_style'] === 'detailed'): ?>
        .lift-docs-secure-document {
            max-width: 1000px;
        }
        
        .document-header h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .document-meta {
            font-size: 16px;
            gap: 25px;
            padding: 15px 0;
        }
        <?php endif; ?>
        
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
            flex-wrap: wrap;
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
        
        .download-info {
            font-size: 12px;
            color: #666;
            margin: 8px 0 0 0;
            text-align: center;
        }
        
        .multiple-downloads {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .download-item {
            background: #f8f9fa;
            border: 1px solid #e3e4e6;
            border-radius: 6px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .download-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        .download-item .download-button {
            display: block;
            width: 100%;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            color: #0073aa;
            font-weight: 500;
            border: none;
            background: transparent;
            transition: background 0.3s ease;
        }
        
        .download-item .download-button:hover {
            background: #0073aa;
            color: white;
        }
        
        .document-download h3 {
            margin: 0 0 15px 0;
            color: #23282d;
            font-size: 1.3em;
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
        
        .related-documents-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #f1f1f1;
        }
        
        .related-documents-section h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .related-doc-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .related-doc-item h4 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
        }
        
        .related-doc-item h4 a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .related-doc-item h4 a:hover {
            text-decoration: underline;
        }
        
        .related-doc-item p {
            margin: 0;
            font-size: 0.9em;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .document-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .document-header h1 {
                font-size: 1.8em;
            }
            
            .lift-docs-secure-document {
                margin: 20px auto;
                padding: 0 15px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get file icon based on extension
     */
    private function get_file_icon($extension) {
        $icons = array(
            'PDF' => '<i class="fas fa-file-pdf"></i>',
            'DOC' => '<i class="fas fa-file-word"></i>',
            'DOCX' => '<i class="fas fa-file-word"></i>',
            'XLS' => '<i class="fas fa-file-excel"></i>',
            'XLSX' => '<i class="fas fa-file-excel"></i>',
            'PPT' => '<i class="fas fa-file-powerpoint"></i>',
            'PPTX' => '<i class="fas fa-file-powerpoint"></i>',
            'TXT' => '<i class="fas fa-file-alt"></i>',
            'RTF' => '<i class="fas fa-file-alt"></i>',
            'ZIP' => '<i class="fas fa-file-archive"></i>',
            'RAR' => '<i class="fas fa-file-archive"></i>',
            '7Z' => '<i class="fas fa-file-archive"></i>',
            'JPG' => '<i class="fas fa-image"></i>',
            'JPEG' => '<i class="fas fa-image"></i>',
            'PNG' => '<i class="fas fa-image"></i>',
            'GIF' => '<i class="fas fa-image"></i>',
            'MP4' => '<i class="fas fa-video"></i>',
            'AVI' => '<i class="fas fa-video"></i>',
            'MOV' => '<i class="fas fa-video"></i>',
            'MP3' => '<i class="fas fa-music"></i>',
            'WAV' => '<i class="fas fa-music"></i>',
            'CSV' => '<i class="fas fa-file-csv"></i>',
            'XML' => '<i class="fas fa-file-code"></i>',
            'HTML' => '<i class="fas fa-file-code"></i>',
            'CSS' => '<i class="fas fa-file-code"></i>',
            'JS' => '<i class="fas fa-file-code"></i>'
        );
        
        return isset($icons[$extension]) ? $icons[$extension] : '<i class="fas fa-file"></i>';
    }
    
    /**
     * Get secure view URL for a specific file
     */
    private function get_secure_view_url($document_id, $file_index = 0) {
        $token = LIFT_Docs_Settings::generate_secure_link($document_id, 'view', $file_index);
        return home_url('/document-files/secure/?lift_secure=' . urlencode($token) . '&action=view&file=' . $file_index);
    }
    
    /**
     * Get secure download URL for a specific file
     */
    private function get_secure_download_url($document_id, $file_index = 0) {
        $token = LIFT_Docs_Settings::generate_secure_link($document_id, 'download', $file_index);
        return home_url('/document-files/secure/?lift_secure=' . urlencode($token) . '&action=download&file=' . $file_index);
    }
}
