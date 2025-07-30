<?php
/**
 * Layout management for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Layout {

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
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_custom_layout'));
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Hide admin bar for secure pages
        add_action('wp', array($this, 'maybe_hide_admin_bar_for_secure'));
    }

    /**
     * Add rewrite rules for custom layout page
     */
    public function add_rewrite_rules() {
        // Check if function exists
        if (!function_exists('add_rewrite_rule')) {
            return;
        }

        add_rewrite_rule(
            '^document-files/view/([^/]+)/?$',
            'index.php?lift_custom_view=1&lift_doc_id=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^document-files/download/?$',
            'index.php?lift_custom_download=1',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        if (!is_array($vars)) {
            $vars = array();
        }

        $vars[] = 'lift_custom_view';
        $vars[] = 'lift_doc_id';
        $vars[] = 'lift_custom_download';
        $vars[] = 'lift_secure';
        return $vars;
    }

    /**
     * Handle custom layout display
     */
    public function handle_custom_layout() {
        global $wp_query;

        // Handle custom view layout
        if (get_query_var('lift_custom_view')) {
            $this->display_custom_view_layout();
            exit;
        }

        // Handle custom download
        if (get_query_var('lift_custom_download')) {
            $this->handle_secure_download();
            exit;
        }
    }

    /**
     * Display custom view layout
     */
    private function display_custom_view_layout() {
        $doc_id = get_query_var('lift_doc_id');

        if (!$doc_id) {
            wp_die(__('Document not found.', 'lift-docs-system'));
        }

        $document = get_post($doc_id);
        if (!$document || $document->post_type !== 'lift_document') {
            wp_die(__('Document not found.', 'lift-docs-system'));
        }

        // Check access permissions
        if (!$this->check_document_access($doc_id)) {
            wp_die(__('Access denied.', 'lift-docs-system'));
        }

        // Get layout settings
        $layout_settings = $this->get_layout_settings($doc_id);

        // Increment view count
        $this->increment_view_count($doc_id);

        // Disable admin bar for secure pages
        add_filter('show_admin_bar', '__return_false');

        // Display the layout
        $this->render_custom_layout($document, $layout_settings);
    }

    /**
     * Handle secure download
     */
    public function handle_secure_download() {
        if (!isset($_GET['lift_secure'])) {
            wp_die('Access denied: No secure token provided');
        }

        $token = sanitize_text_field(urldecode($_GET['lift_secure']));

        // Use primary verification method (same as generate_secure_download_link)
        $verification = LIFT_Docs_Settings::verify_secure_link(urldecode($token));

        if (!$verification || !isset($verification['document_id'])) {
            wp_die('Access denied: Invalid or expired token');
        }

        $document_id = $verification['document_id'];

        $post = get_post($document_id);

        if (!$post || $post->post_type !== 'lift_document') {
            wp_die('Access denied: Document not found');
        }

        // Check if user has permission to download document
        $frontend = LIFT_Docs_Frontend::get_instance();
        if (!$frontend || !method_exists($frontend, 'can_user_download_document')) {
            // Fallback to basic permission check
            if (LIFT_Docs_Settings::get_setting('require_login_to_download', false) && !is_user_logged_in()) {
                wp_die('Access denied: You need to log in to download this document');
            }
        } else {
            // Use the proper permission checking method via reflection
            $reflection = new ReflectionClass($frontend);
            $method = $reflection->getMethod('can_user_download_document');
            $method->setAccessible(true);

            if (!$method->invoke($frontend, $document_id)) {
                wp_die('Access denied: You do not have permission to download this document');
            }
        }

        // Get file index from verification data
        $file_index = isset($verification['file_index']) ? intval($verification['file_index']) : 0;

        // Handle multiple files - get all file URLs
        $file_urls = get_post_meta($document_id, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            // Check for legacy single file URL
            $legacy_url = get_post_meta($document_id, '_lift_doc_file_url', true);
            if ($legacy_url) {
                $file_urls = array($legacy_url);
            } else {
                $file_urls = array();
            }
        }

        // Filter out empty URLs
        $file_urls = array_filter($file_urls);

        if (empty($file_urls)) {
            wp_die('Access denied: No files attached to this document');
        }

        // Check if file index exists
        if (!isset($file_urls[$file_index])) {
            wp_die('Access denied: File index not found');
        }

        $file_url = $file_urls[$file_index];

        if (!$file_url) {
            wp_die('Access denied: File URL is empty');
        }

        // For external URLs, redirect to the file
        if (filter_var($file_url, FILTER_VALIDATE_URL)) {
            // Get proper filename for the specific file
            $file_name = basename(parse_url($file_url, PHP_URL_PATH));
            $document_title = $file_name ?: $post->post_title;

            // Check if it's a local file URL
            $upload_dir = wp_upload_dir();
            if (strpos($file_url, $upload_dir['baseurl']) === 0) {
                // Local file - serve securely
                $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
                if (file_exists($file_path)) {
                    $this->serve_local_file($file_path, $document_title);
                    exit;
                }
            }

            // External file or local file not found - redirect
            wp_redirect($file_url);
            exit;
        }

        wp_die('Access denied: Invalid file URL');
    }

    /**
     * Process file download
     */
    private function process_file_download($doc_id) {
        // Check if user can download
        if (LIFT_Docs_Settings::get_setting('require_login_to_download', false) && !is_user_logged_in()) {
            wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
            exit;
        }

        $file_url = get_post_meta($doc_id, '_lift_doc_file_url', true);

        if (!$file_url) {
            wp_die(__('File not found.', 'lift-docs-system'));
        }

        // Track download
        $this->track_download($doc_id);

        // Redirect to file
        wp_redirect($file_url);
        exit;
    }

    /**
     * Track document download
     */
    private function track_download($doc_id) {
        // Update download count
        $current_downloads = get_post_meta($doc_id, '_lift_doc_downloads', true);
        $current_downloads = $current_downloads ? intval($current_downloads) : 0;
        update_post_meta($doc_id, '_lift_doc_downloads', $current_downloads + 1);

        // Record analytics event
        $this->record_analytics_event($doc_id, 'download');
    }

    /**
     * Record analytics event
     */
    private function record_analytics_event($doc_id, $action) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'lift_docs_analytics';

        $user_id = get_current_user_id();
        $user_id = $user_id ? $user_id : null;

        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $doc_id,
                'user_id' => $user_id,
                'action' => $action,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Check document access
     */
    private function check_document_access($doc_id) {
        // Check if document is private
        $is_private = get_post_meta($doc_id, '_lift_doc_private', true);
        if ($is_private && !current_user_can('edit_posts')) {
            return false;
        }

        // Check password protection
        $is_password_protected = get_post_meta($doc_id, '_lift_doc_password_protected', true);
        if ($is_password_protected) {
            $doc_password = get_post_meta($doc_id, '_lift_doc_password', true);
            $entered_password = $_POST['lift_doc_password'] ?? $_SESSION['lift_doc_' . $doc_id] ?? '';

            if ($doc_password && $entered_password !== $doc_password) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get layout settings for document
     */
    private function get_layout_settings($doc_id) {
        // Get global settings from options
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
     * Render custom layout
     */
    private function render_custom_layout($document, $settings) {
        // Start output buffering
        ob_start();

        // Remove admin bar CSS bump
        remove_action('wp_head', '_admin_bar_bump_cb');

        // Disable admin bar for this page
        add_filter('show_admin_bar', '__return_false');

        // Get header
        get_header();

        // Enqueue shared CSS and JS
        wp_enqueue_style('lift-docs-secure-frontend', plugin_dir_url(dirname(__FILE__)) . 'assets/css/secure-frontend.css', array(), LIFT_DOCS_VERSION);
        wp_enqueue_script('lift-docs-secure-frontend', plugin_dir_url(dirname(__FILE__)) . 'assets/js/secure-frontend.js', array('jquery'), LIFT_DOCS_VERSION, true);

        // Add body class
        add_filter('body_class', function($classes) {
            $classes[] = 'lift-secure-page';
            return $classes;
        });

        ?>
        <div class="lift-docs-custom-layout <?php echo esc_attr($settings['layout_style']); ?>">
            <div class="container">

                <?php if ($settings['show_secure_access_notice']): ?>
                <div class="secure-access-notice">
                    <div class="notice-content">
                        <span class="dashicons dashicons-lock"></span>
                        <span><?php _e('This document is accessed via secure link', 'lift-docs-system'); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($settings['show_document_header']): ?>
                <div class="document-header">
                    <h1 class="document-title"><?php echo esc_html($document->post_title); ?></h1>

                    <?php if ($settings['show_document_meta']): ?>
                    <div class="document-meta">
                        <div class="meta-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span><?php echo get_the_date('', $document->ID); ?></span>
                        </div>

                        <?php
                        $categories = get_the_terms($document->ID, 'lift_doc_category');
                        if ($categories && !is_wp_error($categories)):
                        ?>
                        <div class="meta-item">
                            <span class="dashicons dashicons-category"></span>
                            <span><?php echo esc_html($categories[0]->name); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php
                        $file_size = get_post_meta($document->ID, '_lift_doc_file_size', true);
                        if ($file_size):
                        ?>
                        <div class="meta-item">
                            <span class="dashicons dashicons-media-document"></span>
                            <span><?php echo size_format($file_size); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($settings['show_document_description'] && $document->post_content): ?>
                <div class="document-description">
                    <?php echo apply_filters('the_content', $document->post_content); ?>
                </div>
                <?php endif; ?>

                <?php if ($settings['show_download_button']): ?>
                <div class="document-actions">
                    <?php
                    // Handle multiple files - get all file URLs
                    $file_urls = get_post_meta($document->ID, '_lift_doc_file_urls', true);
                    if (empty($file_urls)) {
                        // Check for legacy single file URL
                        $legacy_url = get_post_meta($document->ID, '_lift_doc_file_url', true);
                        if ($legacy_url) {
                            $file_urls = array($legacy_url);
                        } else {
                            $file_urls = array();
                        }
                    }

                    // Filter out empty URLs
                    $file_urls = array_filter($file_urls);

                    if (!empty($file_urls)):
                    ?>

                    <div class="download-section">
                        <h3 class="download-section-title">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Download Files', 'lift-docs-system'); ?>
                            <span class="files-count">(<?php echo count($file_urls); ?> <?php echo count($file_urls) === 1 ? __('file', 'lift-docs-system') : __('files', 'lift-docs-system'); ?>)</span>
                        </h3>

                        <div class="files-list">
                            <?php foreach ($file_urls as $index => $file_url):
                                $file_name = basename(parse_url($file_url, PHP_URL_PATH));
                                $download_url = LIFT_Docs_Settings::generate_secure_download_link($document->ID, 0, $index);

                                // Get file icon based on extension
                                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                $file_icon = $this->get_file_icon($file_extension);
                            ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <span class="file-icon"><?php echo $file_icon; ?></span>
                                    <span class="file-name"><?php echo esc_html($file_name ?: sprintf(__('File %d', 'lift-docs-system'), $index + 1)); ?></span>
                                </div>
                                <a href="<?php echo esc_url($download_url); ?>" class="button button-primary lift-download-btn">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Download', 'lift-docs-system'); ?>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($settings['show_related_docs']): ?>
                <div class="related-documents">
                    <?php echo $this->get_related_documents($document->ID); ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <?php

        // Get footer
        get_footer();

        // Output the content
        echo ob_get_clean();
    }

    /**
     * Get related documents
     */
    private function get_related_documents($doc_id) {
        $categories = get_the_terms($doc_id, 'lift_doc_category');

        if (!$categories || is_wp_error($categories)) {
            return '';
        }

        $related_docs = get_posts(array(
            'post_type' => 'lift_document',
            'posts_per_page' => 3,
            'post__not_in' => array($doc_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'lift_doc_category',
                    'field' => 'term_id',
                    'terms' => $categories[0]->term_id
                )
            )
        ));

        if (empty($related_docs)) {
            return '';
        }

        $output = '<h3>' . __('Related Documents', 'lift-docs-system') . '</h3>';
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

        return $output;
    }

    /**
     * Increment view count
     */
    private function increment_view_count($doc_id) {
        $views = get_post_meta($doc_id, '_lift_doc_views', true);
        $views = $views ? intval($views) + 1 : 1;
        update_post_meta($doc_id, '_lift_doc_views', $views);

        // Log analytics to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';

        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $doc_id,
                'user_id' => get_current_user_id(),
                'action' => 'view',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Generate custom Attached files
     */
    public static function generate_custom_view_url($doc_id) {
        return home_url('/document-files/view/' . $doc_id . '/');
    }

    /**
     * Generate secure download URL
     */
    public static function generate_secure_download_url($doc_id) {
        $secure_token = LIFT_Docs_Settings::generate_secure_token($doc_id, 'download');
        return home_url('/document-files/download/?lift_secure=' . $secure_token);
    }

    /**
     * Get file icon based on file extension
     */
    private function get_file_icon($extension) {
        $icons = array(
            // Images
            'jpg' => '<i class="fas fa-image"></i>', 'jpeg' => '<i class="fas fa-image"></i>', 'png' => '<i class="fas fa-image"></i>', 'gif' => '<i class="fas fa-image"></i>', 'webp' => '<i class="fas fa-image"></i>', 'svg' => '<i class="fas fa-image"></i>',
            // Videos
            'mp4' => '<i class="fas fa-video"></i>', 'avi' => '<i class="fas fa-video"></i>', 'mov' => '<i class="fas fa-video"></i>', 'wmv' => '<i class="fas fa-video"></i>', 'flv' => '<i class="fas fa-video"></i>', 'webm' => '<i class="fas fa-video"></i>',
            // Audio
            'mp3' => '<i class="fas fa-music"></i>', 'wav' => '<i class="fas fa-music"></i>', 'ogg' => '<i class="fas fa-music"></i>', 'flac' => '<i class="fas fa-music"></i>', 'aac' => '<i class="fas fa-music"></i>',
            // Documents
            'pdf' => '<i class="fas fa-file-pdf"></i>',
            'doc' => '<i class="fas fa-file-word"></i>', 'docx' => '<i class="fas fa-file-word"></i>',
            'xls' => '<i class="fas fa-file-excel"></i>', 'xlsx' => '<i class="fas fa-file-excel"></i>',
            'ppt' => '<i class="fas fa-file-powerpoint"></i>', 'pptx' => '<i class="fas fa-file-powerpoint"></i>',
            // Archives
            'zip' => '<i class="fas fa-file-archive"></i>', 'rar' => '<i class="fas fa-file-archive"></i>', '7z' => '<i class="fas fa-file-archive"></i>', 'tar' => '<i class="fas fa-file-archive"></i>', 'gz' => '<i class="fas fa-file-archive"></i>'
        );

        return $icons[strtolower($extension)] ?? '<i class="fas fa-file"></i>';
    }

    /**
     * Serve local file securely
     */
    private function serve_local_file($file_path, $document_title) {
        // Clean filename for download
        $clean_filename = sanitize_file_name($document_title);
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

        if ($file_extension) {
            $clean_filename .= '.' . $file_extension;
        }

        $mime_type = wp_check_filetype($file_path)['type'] ?: 'application/octet-stream';
        $file_size = filesize($file_path);

        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $clean_filename . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: 0');
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
     * Maybe hide admin bar for secure pages
     */
    public function maybe_hide_admin_bar_for_secure() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Check if this is a secure link page
        if ((strpos($request_uri, '/document-files/secure/') !== false ||
             strpos($request_uri, '/document-files/view/') !== false) &&
            isset($_GET['lift_secure'])) {
            add_filter('show_admin_bar', '__return_false');
            remove_action('wp_head', '_admin_bar_bump_cb');
        }
    }
}
