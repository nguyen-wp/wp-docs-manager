<?php
/**
 * Frontend functionality for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Frontend {

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
        add_filter('the_content', array($this, 'enhance_document_content'));
        add_action('wp_head', array($this, 'add_document_meta'));
        add_shortcode('lift_document_search', array($this, 'document_search_shortcode'));
        add_shortcode('lift_document_categories', array($this, 'document_categories_shortcode'));
        add_action('init', array($this, 'handle_document_download'));
        add_action('init', array($this, 'handle_document_view_online'));
        add_filter('document_class', array($this, 'add_document_classes'));
        add_action('wp_footer', array($this, 'add_document_tracking'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Enqueue Font Awesome for frontend
        wp_enqueue_style('font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
    }

    /**
     * Enhance document content
     */
    public function enhance_document_content($content) {
        if (!is_singular('lift_document')) {
            return $content;
        }

        global $post;

        $enhanced_content = '';

        // Check if user has permission to view
        if (!$this->can_user_view_document($post->ID)) {
            return '<div class="lift-docs-restricted">' .
                   '<p>' . __('You need to log in to view this document.', 'lift-docs-system') . '</p>' .
                   '<p><a href="' . wp_login_url(get_permalink()) . '">' . __('Log in', 'lift-docs-system') . '</a></p>' .
                   '</div>';
        }

        // Add document meta
        if (LIFT_Docs_Settings::get_setting('show_document_meta', true)) {
            $enhanced_content .= $this->get_document_meta($post->ID);
        }

        // Add original content
        $enhanced_content .= $content;

        // Add document actions
        $enhanced_content .= $this->get_document_actions($post->ID);

        // Add related documents
        $enhanced_content .= $this->get_related_documents($post->ID);

        return $enhanced_content;
    }

    /**
     * Check if user can view document
     */
    private function can_user_view_document($post_id) {
        // Use the centralized permission checking from settings
        return LIFT_Docs_Settings::user_can_view_document($post_id);
    }

    /**
     * Check if user can download document
     */
    private function can_user_download_document($post_id) {
        // Use the centralized permission checking from settings
        return LIFT_Docs_Settings::user_can_download_document($post_id);
    }

    /**
     * Get document meta information
     */
    private function get_document_meta($post_id) {
        $post = get_post($post_id);
        $views = get_post_meta($post_id, '_lift_doc_views', true);
        $downloads = get_post_meta($post_id, '_lift_doc_downloads', true);
        $file_size = get_post_meta($post_id, '_lift_doc_file_size', true);

        $meta_html = '<div class="lift-docs-meta">';

        // Author and date
        $meta_html .= '<div class="meta-item">';
        $meta_html .= '<span class="meta-label">' . __('Published:', 'lift-docs-system') . '</span> ';
        $meta_html .= '<span class="meta-value">' . get_the_date('', $post) . ' ' . __('by', 'lift-docs-system') . ' ' . get_the_author_meta('display_name', $post->post_author) . '</span>';
        $meta_html .= '</div>';

        // Categories
        $categories = get_the_terms($post_id, 'lift_doc_category');
        if ($categories && !is_wp_error($categories)) {
            $meta_html .= '<div class="meta-item">';
            $meta_html .= '<span class="meta-label">' . __('Category:', 'lift-docs-system') . '</span> ';
            $category_links = array();
            foreach ($categories as $category) {
                $category_links[] = '<a href="' . get_term_link($category) . '">' . $category->name . '</a>';
            }
            $meta_html .= '<span class="meta-value">' . implode(', ', $category_links) . '</span>';
            $meta_html .= '</div>';
        }

        // Tags
        $tags = get_the_terms($post_id, 'lift_doc_tag');
        if ($tags && !is_wp_error($tags)) {
            $meta_html .= '<div class="meta-item">';
            $meta_html .= '<span class="meta-label">' . __('Tags:', 'lift-docs-system') . '</span> ';
            $tag_links = array();
            foreach ($tags as $tag) {
                $tag_links[] = '<a href="' . get_term_link($tag) . '">' . $tag->name . '</a>';
            }
            $meta_html .= '<span class="meta-value">' . implode(', ', $tag_links) . '</span>';
            $meta_html .= '</div>';
        }

        // File size
        if ($file_size) {
            $meta_html .= '<div class="meta-item">';
            $meta_html .= '<span class="meta-label">' . __('File Size:', 'lift-docs-system') . '</span> ';
            $meta_html .= '<span class="meta-value">' . size_format($file_size) . '</span>';
            $meta_html .= '</div>';
        }

        // Views count
        if (LIFT_Docs_Settings::get_setting('show_view_count', true) && $views) {
            $meta_html .= '<div class="meta-item">';
            $meta_html .= '<span class="meta-label">' . __('Views:', 'lift-docs-system') . '</span> ';
            $meta_html .= '<span class="meta-value">' . number_format($views) . '</span>';
            $meta_html .= '</div>';
        }

        // Downloads count
        if ($downloads) {
            $meta_html .= '<div class="meta-item">';
            $meta_html .= '<span class="meta-label">' . __('Downloads:', 'lift-docs-system') . '</span> ';
            $meta_html .= '<span class="meta-value">' . number_format($downloads) . '</span>';
            $meta_html .= '</div>';
        }

        $meta_html .= '</div>';

        return $meta_html;
    }

    /**
     * Get document actions (download, share, etc.)
     */
    private function get_document_actions($post_id) {
        $file_url = get_post_meta($post_id, '_lift_doc_file_url', true);

        if (!$file_url) {
            return '';
        }

        $actions_html = '<div class="lift-docs-actions">';

        // Download button
        if (LIFT_Docs_Settings::get_setting('show_download_button', true)) {
            // Check if user can download
            if ($this->can_user_download_document($post_id)) {
                $download_url = add_query_arg(array(
                    'lift_download' => $post_id,
                    'nonce' => wp_create_nonce('lift_download_' . $post_id)
                ), home_url());

                $actions_html .= '<a href="' . esc_url($download_url) . '" class="lift-docs-download-btn button">';
                $actions_html .= '<span class="dashicons dashicons-download"></span> ';
                $actions_html .= __('Download Document', 'lift-docs-system');
                $actions_html .= '</a>';
            } else {
                // Show login required message
                $actions_html .= '<div class="lift-docs-login-required">';
                $actions_html .= '<p>' . __('You need to log in to download this document.', 'lift-docs-system') . '</p>';
                $actions_html .= '<a href="' . wp_login_url(get_permalink($post_id)) . '" class="button button-primary">';
                $actions_html .= __('Log in to Download', 'lift-docs-system');
                $actions_html .= '</a>';
                $actions_html .= '</div>';
            }
        }

        // Share button
        $actions_html .= '<button class="lift-docs-share-btn button" data-url="' . get_permalink($post_id) . '">';
        $actions_html .= '<span class="dashicons dashicons-share"></span> ';
        $actions_html .= __('Share', 'lift-docs-system');
        $actions_html .= '</button>';

        $actions_html .= '</div>';

        return $actions_html;
    }

    /**
     * Get related documents
     */
    private function get_related_documents($post_id) {
        $categories = get_the_terms($post_id, 'lift_doc_category');

        if (!$categories || is_wp_error($categories)) {
            return '';
        }

        $category_ids = array();
        foreach ($categories as $category) {
            $category_ids[] = $category->term_id;
        }

        $related_docs = get_posts(array(
            'post_type' => 'lift_document',
            'posts_per_page' => 5,
            'post__not_in' => array($post_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'lift_doc_category',
                    'field' => 'term_id',
                    'terms' => $category_ids,
                )
            ),
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
        ));

        if (empty($related_docs)) {
            return '';
        }

        $related_html = '<div class="lift-docs-related">';
        $related_html .= '<h3>' . __('Related Documents', 'lift-docs-system') . '</h3>';
        $related_html .= '<ul>';

        foreach ($related_docs as $doc) {
            $related_html .= '<li>';
            $related_html .= '<a href="' . get_permalink($doc->ID) . '">' . esc_html($doc->post_title) . '</a>';
            $related_html .= '</li>';
        }

        $related_html .= '</ul>';
        $related_html .= '</div>';

        return $related_html;
    }

    /**
     * Format file size
     */
    private function format_file_size($size) {
        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }

    /**
     * Document search shortcode
     */
    public function document_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search documents...', 'lift-docs-system')
        ), $atts);

        $output = '<form class="lift-docs-search" method="get" action="' . home_url() . '">';
        $output .= '<input type="hidden" name="post_type" value="lift_document" />';
        $output .= '<input type="search" name="s" placeholder="' . esc_attr($atts['placeholder']) . '" value="' . get_search_query() . '" />';
        $output .= '<button type="submit">' . __('Search', 'lift-docs-system') . '</button>';
        $output .= '</form>';

        return $output;
    }

    /**
     * Document categories shortcode
     */
    public function document_categories_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_count' => 'true',
            'hide_empty' => 'true'
        ), $atts);

        $categories = get_terms(array(
            'taxonomy' => 'lift_doc_category',
            'hide_empty' => $atts['hide_empty'] === 'true'
        ));

        if (empty($categories) || is_wp_error($categories)) {
            return '<p>' . __('No categories found.', 'lift-docs-system') . '</p>';
        }

        $output = '<ul class="lift-docs-categories">';

        foreach ($categories as $category) {
            $output .= '<li>';
            $output .= '<a href="' . get_term_link($category) . '">' . esc_html($category->name) . '</a>';

            if ($atts['show_count'] === 'true') {
                $output .= ' (' . $category->count . ')';
            }

            $output .= '</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * Handle document download
     */
    public function handle_document_download() {
        if (!isset($_GET['lift_download']) || !isset($_GET['nonce'])) {
            return;
        }

        $document_id = intval($_GET['lift_download']);
        $nonce = $_GET['nonce'];
        $file_index = isset($_GET['file_index']) ? intval($_GET['file_index']) : 0;

        if (!wp_verify_nonce($nonce, 'lift_download_' . $document_id)) {
            wp_die(__('Security check failed.', 'lift-docs-system'));
        }

        // Check if user can download
        if (!$this->can_user_download_document($document_id)) {
            wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
            exit;
        }

        // Get file URL based on file index
        $file_url = $this->get_file_url_by_index($document_id, $file_index);

        if (!$file_url) {
            wp_die(__('File not found.', 'lift-docs-system'));
        }

        // Track download
        $this->track_download($document_id);

        // Redirect to file
        wp_redirect($file_url);
        exit;
    }

    /**
     * Handle document view online
     */
    public function handle_document_view_online() {
        if (!isset($_GET['lift_view_online']) || !isset($_GET['nonce'])) {
            return;
        }

        $document_id = intval($_GET['lift_view_online']);
        $nonce = $_GET['nonce'];
        $file_index = isset($_GET['file_index']) ? intval($_GET['file_index']) : 0;

        if (!wp_verify_nonce($nonce, 'lift_view_online_' . $document_id)) {
            wp_die(__('Security check failed.', 'lift-docs-system'));
        }

        // Check if user can view online (same permission as download)
        if (!$this->can_user_download_document($document_id)) {
            wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
            exit;
        }

        // Get file URL based on file index
        $file_url = $this->get_file_url_by_index($document_id, $file_index);

        if (!$file_url) {
            wp_die(__('File not found.', 'lift-docs-system'));
        }

        // Track view (as separate from page views)
        $this->track_online_view($document_id);

        // For PDFs and other viewable documents, open directly
        // For other file types, still redirect to download
        $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
        $viewable_extensions = array('pdf', 'txt', 'html', 'htm', 'jpg', 'jpeg', 'png', 'gif', 'svg');

        if (in_array($file_extension, $viewable_extensions)) {
            // Open file directly in browser for viewing
            wp_redirect($file_url);
            exit;
        } else {
            // For non-viewable files, redirect to download
            wp_redirect(add_query_arg(array(
                'lift_download' => $document_id,
                'nonce' => wp_create_nonce('lift_download_' . $document_id)
            ), home_url()));
            exit;
        }
    }

    /**
     * Track document online view
     */
    private function track_online_view($document_id) {
        // Record analytics event if enabled
        if (LIFT_Docs_Settings::get_setting('enable_analytics', true)) {
            $this->record_analytics_event($document_id, 'view_online');
        }
    }

    /**
     * Track document download
     */
    private function track_download($document_id) {
        // Update download count
        $current_downloads = get_post_meta($document_id, '_lift_doc_downloads', true);
        $current_downloads = $current_downloads ? intval($current_downloads) : 0;
        update_post_meta($document_id, '_lift_doc_downloads', $current_downloads + 1);

        // Record analytics event if enabled
        if (LIFT_Docs_Settings::get_setting('enable_analytics', true)) {
            $this->record_analytics_event($document_id, 'download');
        }
    }

    /**
     * Record analytics event
     */
    private function record_analytics_event($document_id, $action) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'lift_docs_analytics';

        $user_id = get_current_user_id();
        $user_id = $user_id ? $user_id : null;

        $wpdb->insert(
            $table_name,
            array(
                'document_id' => $document_id,
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
     * Add document meta to head
     */
    public function add_document_meta() {
        if (!is_singular('lift_document')) {
            return;
        }

        global $post;

        echo '<meta property="og:type" content="article" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($post->post_title) . '" />' . "\n";

        if ($post->post_excerpt) {
            echo '<meta property="og:description" content="' . esc_attr($post->post_excerpt) . '" />' . "\n";
        }

        echo '<meta property="og:url" content="' . get_permalink() . '" />' . "\n";

        if (has_post_thumbnail()) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
            echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '" />' . "\n";
        }
    }

    /**
     * Add document classes
     */
    public function add_document_classes($classes) {
        if (is_singular('lift_document')) {
            $classes[] = 'lift-document-single';

            global $post;
            $is_featured = get_post_meta($post->ID, '_lift_doc_featured', true);
            if ($is_featured) {
                $classes[] = 'lift-document-featured';
            }
        }

        return $classes;
    }

    /**
     * Add document tracking script
     */
    public function add_document_tracking() {
        if (!is_singular('lift_document') || !LIFT_Docs_Settings::get_setting('enable_analytics', true)) {
            return;
        }

        global $post;

        ?>
        <script>
        jQuery(document).ready(function($) {
            // Track time spent on page
            var startTime = new Date().getTime();

            $(window).on('beforeunload', function() {
                var timeSpent = Math.round((new Date().getTime() - startTime) / 1000);

                if (timeSpent > 10) { // Only track if user spent more than 10 seconds
                    $.ajax({
                        url: lift_docs_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'lift_track_time',
                            document_id: <?php echo $post->ID; ?>,
                            time_spent: timeSpent,
                            nonce: lift_docs_ajax.nonce
                        },
                        async: false
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Get file URL by index - supports multiple files
     */
    private function get_file_url_by_index($document_id, $file_index = 0) {
        // Try to get multiple files first
        $file_urls = get_post_meta($document_id, '_lift_doc_file_urls', true);

        if (!empty($file_urls) && is_array($file_urls)) {
            // Filter out empty URLs
            $file_urls = array_filter($file_urls);

            if (isset($file_urls[$file_index])) {
                return $file_urls[$file_index];
            }

            // If requested index doesn't exist, return first file
            if (!empty($file_urls)) {
                return reset($file_urls);
            }
        }

        // Fallback to legacy single file URL
        $legacy_url = get_post_meta($document_id, '_lift_doc_file_url', true);

        if ($legacy_url && $file_index === 0) {
            return $legacy_url;
        }

        return false;
    }
}
