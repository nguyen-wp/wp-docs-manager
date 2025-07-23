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
        
        $file_size = get_post_meta($post->ID, '_lift_doc_file_size', true);
        $download_count = get_post_meta($post->ID, '_lift_doc_downloads', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Document Files', 'lift-docs-system'); ?></label></th>
                <td>
                    <div id="lift_doc_files_container">
                        <?php if (empty($file_urls)): ?>
                            <div class="file-input-row" data-index="0">
                                <input type="url" name="lift_doc_file_urls[]" value="" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button upload-file-button"><?php _e('📁 Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button remove-file-button" style="display: none;"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
                                <span class="file-size-display"></span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($file_urls as $index => $url): ?>
                            <div class="file-input-row" data-index="<?php echo $index; ?>">
                                <input type="url" name="lift_doc_file_urls[]" value="<?php echo esc_attr($url); ?>" class="regular-text file-url-input" placeholder="<?php _e('Enter file URL or click Upload', 'lift-docs-system'); ?>" />
                                <button type="button" class="button upload-file-button"><?php _e('📁 Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button remove-file-button" <?php echo count($file_urls) <= 1 ? 'style="display: none;"' : ''; ?>><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
                                <span class="file-size-display">
                                    <?php if ($url): ?>
                                        <span style="color: #0073aa; font-weight: 500;">
                                            📄 <?php echo basename(parse_url($url, PHP_URL_PATH)); ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" class="button button-secondary" id="add_file_button">
                            <span style="font-size: 14px;">➕</span> <?php _e('Add Another File', 'lift-docs-system'); ?>
                        </button>
                        <button type="button" class="button" id="clear_all_files" style="margin-left: 10px;">
                            <span style="font-size: 12px;">🗑️</span> <?php _e('Clear All', 'lift-docs-system'); ?>
                        </button>
                    </div>
                    
                    <p class="description">
                        <?php _e('You can add multiple files of any type. Each file will have its own secure download link. Supported: Documents (PDF, DOC, XLS), Images (JPG, PNG), Videos (MP4, AVI), Audio (MP3, WAV), Archives (ZIP, RAR) and more.', 'lift-docs-system'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th><label for="lift_doc_file_size"><?php _e('Total File Size (bytes)', 'lift-docs-system'); ?></label></th>
                <td>
                    <input type="number" id="lift_doc_file_size" name="lift_doc_file_size" value="<?php echo esc_attr($file_size); ?>" class="small-text" readonly />
                    <p class="description"><?php _e('Total size of all files (auto-calculated for uploaded files).', 'lift-docs-system'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><?php _e('Download Count', 'lift-docs-system'); ?></th>
                <td>
                    <p><?php echo $download_count ? $download_count : '0'; ?> <?php _e('downloads', 'lift-docs-system'); ?></p>
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
                            <button type="button" class="button upload-file-button"><?php _e('📁 Upload', 'lift-docs-system'); ?></button>
                            <button type="button" class="button remove-file-button"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
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
                        
                        // Update total file size if needed
                        updateTotalFileSize();
                        
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
                
                // Update total file size calculation
                function updateTotalFileSize() {
                    // This would need to be implemented with AJAX to get actual file sizes
                    // For now, we'll leave the manual input
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
                                <button type="button" class="button upload-file-button"><?php _e('📁 Upload', 'lift-docs-system'); ?></button>
                                <button type="button" class="button remove-file-button" style="display: none;"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
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
                            <button type="button" class="button upload-file-button"><?php _e('📁 Browse', 'lift-docs-system'); ?></button>
                            <button type="button" class="button remove-file-button"><?php _e('✖ Remove', 'lift-docs-system'); ?></button>
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
        .file-input-row {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .file-input-row:hover {
            border-color: #007cba;
            box-shadow: 0 0 0 1px rgba(0, 124, 186, 0.1);
        }
        
        .file-input-row .file-url-input {
            flex: 1;
            min-width: 300px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .file-input-row .file-url-input:focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px rgba(0, 124, 186, 0.25);
            outline: none;
        }
        
        .file-input-row .upload-file-button {
            background: #007cba;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .file-input-row .upload-file-button:hover {
            background: #005a87;
        }
        
        .file-input-row .remove-file-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .file-input-row .remove-file-button:hover {
            background: #c82333;
        }
        
        .file-size-display {
            color: #666;
            font-size: 12px;
            font-style: italic;
            min-width: 120px;
        }
        
        #add_file_button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        #add_file_button:hover {
            background: #218838;
        }
        
        #clear_all_files {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background-color 0.2s ease;
        }
        
        #clear_all_files:hover {
            background: #545b62;
        }
        
        .download-link-item {
            border-left: 4px solid #0073aa;
        }
        
        .download-link-item strong {
            display: block;
            margin-bottom: 5px;
            color: #23282d;
        }
        
        .multiple-download-links .download-link-item:last-child {
            margin-bottom: 0;
        }
        
        /* File upload animations */
        .file-input-row.uploading {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        
        .file-input-row.uploaded {
            background: #e8f5e8;
            border-color: #28a745;
            animation: uploadSuccess 0.5s ease-in-out;
        }
        
        @keyframes uploadSuccess {
            0% { background: #e8f5e8; }
            50% { background: #d4edda; }
            100% { background: #e8f5e8; }
        }
        
        /* File type specific styling */
        .file-size-display span[style*="color: #ff9800"] {
            background: #fff3e0;
            padding: 4px 8px;
            border-radius: 3px;
            border: 1px solid #ffb74d;
        }
        
        .file-size-display span[style*="color: #0073aa"] {
            background: #e3f2fd;
            padding: 4px 8px;
            border-radius: 3px;
            border: 1px solid #90caf9;
        }
        
        /* Enhanced file type icons */
        .file-input-row .file-size-display {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Improved button states */
        .file-input-row .upload-file-button:active {
            transform: translateY(1px);
        }
        
        .file-input-row .remove-file-button:active {
            transform: translateY(1px);
        }
        
        /* File counter for multiple files */
        .file-input-row:before {
            content: attr(data-index);
            position: absolute;
            top: -8px;
            left: 8px;
            background: #007cba;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
            display: none;
        }
        
        .file-input-row:nth-child(n+2):before {
            display: block;
        }
        
        /* Empty state styling */
        #lift_doc_files_container:empty:before {
            content: "<?php _e('No files added yet. Click Upload or Add Another File to get started.', 'lift-docs-system'); ?>";
            display: block;
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 30px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .file-input-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .file-input-row .file-url-input {
                min-width: auto;
            }
            
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
        
        // Save file size
        if (isset($_POST['lift_doc_file_size'])) {
            update_post_meta($post_id, '_lift_doc_file_size', intval($_POST['lift_doc_file_size']));
        } else {
            delete_post_meta($post_id, '_lift_doc_file_size');
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
                        <p class="description" id="lift-view-description" style="margin-top: 5px; color: #666; font-size: 12px;"></p>
                    </div>
                    
                    <div class="lift-detail-group">
                        <label><?php _e('Download URLs', 'lift-docs-system'); ?>:</label>
                        <div id="lift-download-urls-container">
                            <!-- Single file fallback -->
                            <div class="lift-input-group" id="lift-single-download">
                                <input type="text" id="lift-download-url" readonly onclick="this.select()" />
                                <button type="button" class="button lift-copy-btn" data-target="#lift-download-url">
                                    <?php _e('Copy', 'lift-docs-system'); ?>
                                </button>
                                <a href="#" id="lift-online-view" class="button" target="_blank">
                                    <?php _e('View Online', 'lift-docs-system'); ?>
                                </a>
                            </div>
                            <!-- Multiple files list -->
                            <div id="lift-multiple-downloads" style="display: none;">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                    
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
}
