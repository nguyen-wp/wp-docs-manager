<?php
/**
 * Frontend Login System for Lift Documents
 * 
 * Handles custom login page and user authentication
 */

if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Frontend_Login {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_nopriv_docs_login', array($this, 'handle_ajax_login'));
        add_action('wp_ajax_docs_login', array($this, 'handle_ajax_login'));
        add_action('wp_ajax_nopriv_docs_logout', array($this, 'handle_ajax_logout'));
        add_action('wp_ajax_docs_logout', array($this, 'handle_ajax_logout'));
        add_action('wp_ajax_load_document_content', array($this, 'handle_ajax_load_document_content'));
        add_action('wp_ajax_nopriv_load_document_content', array($this, 'handle_ajax_load_document_content'));
        
        // Register shortcodes for regular pages
        add_shortcode('docs_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('docs_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('document_form', array($this, 'document_form_shortcode'));
        
        // Handle form display
        add_action('template_redirect', array($this, 'handle_form_display'));
        add_action('wp_loaded', array($this, 'check_dashboard_access'), 10);
        add_action('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add rewrite rule for document forms
        add_rewrite_rule('^document-form/?$', 'index.php?document_form=1', 'top');
        
        // Flush rewrite rules if needed
        $this->maybe_flush_rewrite_rules();
    }
    
    /**
     * Maybe flush rewrite rules
     */
    private function maybe_flush_rewrite_rules() {
        $version = get_option('lift_docs_form_rewrite_version');
        if ($version !== LIFT_DOCS_VERSION) {
            flush_rewrite_rules();
            update_option('lift_docs_form_rewrite_version', LIFT_DOCS_VERSION);
        }
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'document_form';
        return $vars;
    }
    
    /**
     * Check if current user has document access
     */
    private function user_has_docs_access() {
        return current_user_can('view_lift_documents') || 
               in_array('documents_user', wp_get_current_user()->roles);
    }
    
    /**
     * Handle form display and dashboard redirect
     */
    public function handle_form_display() {
        // Handle document form display
        if (get_query_var('document_form')) {
            $this->display_form_page();
            exit;
        }
        
        // Handle dashboard redirect for non-logged users
        global $post;
        if ($post && $post->post_name === 'document-dashboard') {
            if (!is_user_logged_in() || !$this->user_has_docs_access()) {
                $login_url = $this->get_login_url();
                wp_redirect($login_url);
                exit;
            }
        }
    }
    
    /**
     * Check dashboard access early
     */
    public function check_dashboard_access() {
        // Only check on frontend, not admin
        if (is_admin()) {
            return;
        }
        
        // Check if we're on the dashboard page
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/document-dashboard') !== false) {
            if (!is_user_logged_in() || !$this->user_has_docs_access()) {
                $login_url = $this->get_login_url();
                wp_redirect($login_url);
                exit;
            }
        }
    }
    
    /**
     * Display form page
     */
    private function display_form_page() {
        // Check if user is logged in
        if (!is_user_logged_in() || !$this->user_has_docs_access()) {
            wp_redirect(wp_login_url(home_url('/document-form/')));
            exit;
        }
        
        $document_id = intval($_GET['document_id'] ?? 0);
        $form_id = intval($_GET['form_id'] ?? 0);
        
        if (!$document_id || !$form_id) {
            wp_die(__('Invalid parameters.', 'lift-docs-system'), __('Error', 'lift-docs-system'));
        }
        
        // Verify user has access to the document
        if (!$this->user_can_view_document($document_id)) {
            wp_die(__('You do not have access to this document.', 'lift-docs-system'), __('Access Denied', 'lift-docs-system'));
        }
        
        // Verify form is assigned to document
        $assigned_forms = get_post_meta($document_id, '_lift_doc_assigned_forms', true);
        if (!is_array($assigned_forms) || !in_array($form_id, $assigned_forms)) {
            wp_die(__('This form is not assigned to the document.', 'lift-docs-system'), __('Access Denied', 'lift-docs-system'));
        }
        
        // Get form data
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $forms_table WHERE id = %d AND status = 'active'",
            $form_id
        ));

        if (!$form) {
            wp_die(__('Form not found.', 'lift-docs-system'), __('Error', 'lift-docs-system'));
        }

        // Get document data
        $document = get_post($document_id);
        if (!$document || $document->post_type !== 'lift_document') {
            wp_die(__('Document not found.', 'lift-docs-system'), __('Error', 'lift-docs-system'));
        }

        // Check if user has already submitted this form for this document
        $current_user_id = get_current_user_id();
        $existing_submission = null;
        $is_edit_mode = false;
        
        if ($current_user_id > 0) {
            // Get LIFT_Forms instance to check for existing submission
            $lift_forms = new LIFT_Forms();
            $existing_submission = $lift_forms->get_user_submission($current_user_id, $form_id, $document_id);
            $is_edit_mode = !empty($existing_submission);
        }

        // Render form page
        $this->render_form_page($document, $form, $existing_submission, $is_edit_mode);
    }
    
    /**
     * Render form page
     */
    private function render_form_page($document, $form, $existing_submission = null, $is_edit_mode = false) {
        $current_user = wp_get_current_user();
        $form_fields = json_decode($form->form_fields, true);
        
        // Parse existing form data if in edit mode
        $existing_data = array();
        if ($is_edit_mode && $existing_submission) {
            $existing_data = json_decode($existing_submission->form_data, true);
        }
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($form->name); ?> - <?php echo esc_html($document->post_title); ?></title>
            <?php wp_head(); ?>
            <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                background: #f1f1f1;
                margin: 0;
                padding: 20px;
            }
            .form-container {
                max-width: 800px;
                margin: 0 auto;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            }
            .form-header {
                border-bottom: 1px solid #e1e1e1;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .form-header h1 {
                margin: 0 0 10px 0;
                color: #23282d;
            }
            .form-header .document-info {
                color: #666;
                font-size: 14px;
            }
            .form-field {
                margin-bottom: 20px;
            }
            .form-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #23282d;
            }
            .form-field input,
            .form-field textarea,
            .form-field select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .form-field textarea {
                height: 100px;
                resize: vertical;
            }
            .form-actions {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e1e1e1;
                text-align: right;
            }
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                font-size: 14px;
                margin-left: 10px;
            }
            .btn-primary {
                background: #0073aa;
                color: #fff;
            }
            .btn-secondary {
                background: #f1f1f1;
                color: #333;
            }
            .btn:hover {
                opacity: 0.9;
            }
            .required {
                color: #d63384;
            }
            </style>
        </head>
        <body>
            <div class="form-container">
                <div class="form-header">
                    <h1><?php echo esc_html($form->name); ?></h1>
                    <div class="document-info">
                        <?php printf(__('Related to document: %s', 'lift-docs-system'), '<strong>' . esc_html($document->post_title) . '</strong>'); ?>
                    </div>
                    <?php if ($is_edit_mode): ?>
                        <div class="edit-mode-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 12px; border-radius: 4px; margin: 15px 0; color: #856404;">
                            <strong><?php _e('Edit Mode:', 'lift-docs-system'); ?></strong> 
                            <?php _e('You have already submitted this form. You can edit your submission below.', 'lift-docs-system'); ?>
                            <br><small><?php printf(__('Originally submitted: %s', 'lift-docs-system'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($existing_submission->submitted_at))); ?></small>
                        </div>
                    <?php endif; ?>
                    <?php if ($form->description): ?>
                        <p><?php echo esc_html($form->description); ?></p>
                    <?php endif; ?>
                </div>
                
                <form id="document-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                    <input type="hidden" name="action" value="lift_forms_submit">
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">
                    <input type="hidden" name="document_id" value="<?php echo esc_attr($document->ID); ?>">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('lift_forms_submit_nonce'); ?>">
                    <?php if ($is_edit_mode && $existing_submission): ?>
                        <input type="hidden" name="submission_id" value="<?php echo esc_attr($existing_submission->id); ?>">
                        <input type="hidden" name="is_edit" value="1">
                    <?php endif; ?>
                    
                    <?php if (!empty($form_fields) && is_array($form_fields)): ?>
                        <?php foreach ($form_fields as $field): ?>
                            <div class="form-field">
                                <label for="field_<?php echo esc_attr($field['id']); ?>">
                                    <?php echo esc_html($field['label']); ?>
                                    <?php if (isset($field['required']) && $field['required']): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php $this->render_form_field($field, $existing_data); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('This form has no fields configured.', 'lift-docs-system'); ?></p>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <a href="<?php echo home_url('/document-dashboard/'); ?>" class="btn btn-secondary">
                            <?php _e('Back to Dashboard', 'lift-docs-system'); ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit_mode ? __('Update Submission', 'lift-docs-system') : __('Submit Form', 'lift-docs-system'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#document-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var formData = $(this).serialize();
                    
                    $.post($(this).attr('action'), formData, function(response) {
                        if (response.success) {
                            var message = '<?php echo $is_edit_mode ? __('Form updated successfully!', 'lift-docs-system') : __('Form submitted successfully!', 'lift-docs-system'); ?>';
                            alert(message);
                            
                            // Redirect to dashboard if redirect URL is provided, otherwise go back
                            if (response.data && response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.href = '<?php echo home_url('/document-dashboard/'); ?>';
                            }
                        } else {
                            alert('<?php _e('Error submitting form: ', 'lift-docs-system'); ?>' + (response.data || '<?php _e('Unknown error', 'lift-docs-system'); ?>'));
                        }
                    }).fail(function() {
                        alert('<?php _e('Network error. Please try again.', 'lift-docs-system'); ?>');
                    });
                });
            });
            </script>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render form field
     */
    private function render_form_field($field, $existing_data = array()) {
        $field_id = 'field_' . esc_attr($field['id']);
        $field_name = 'form_fields[' . esc_attr($field['id']) . ']';
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        
        // Get existing value for this field
        $field_value = isset($existing_data[$field['id']]) ? $existing_data[$field['id']] : '';
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'number':
                ?>
                <input type="<?php echo esc_attr($field['type']); ?>" 
                       id="<?php echo $field_id; ?>" 
                       name="<?php echo $field_name; ?>" 
                       value="<?php echo esc_attr($field_value); ?>"
                       placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                       <?php echo $required; ?>>
                <?php
                break;
                
            case 'textarea':
                ?>
                <textarea id="<?php echo $field_id; ?>" 
                          name="<?php echo $field_name; ?>" 
                          placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                          <?php echo $required; ?>><?php echo esc_textarea($field_value); ?></textarea>
                <?php
                break;
                
            case 'select':
                ?>
                <select id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" <?php echo $required; ?>>
                    <option value=""><?php _e('Please select...', 'lift-docs-system'); ?></option>
                    <?php if (isset($field['options']) && is_array($field['options'])): ?>
                        <?php foreach ($field['options'] as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, $option); ?>><?php echo esc_html($option); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php
                break;
                
            case 'checkbox':
                ?>
                <label>
                    <input type="checkbox" 
                           id="<?php echo $field_id; ?>" 
                           name="<?php echo $field_name; ?>" 
                           value="1"
                           <?php echo $required; ?>>
                    <?php echo esc_html($field['label']); ?>
                </label>
                <?php
                break;
                
            case 'radio':
                if (isset($field['options']) && is_array($field['options'])):
                    foreach ($field['options'] as $index => $option):
                        ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="radio" 
                                   name="<?php echo $field_name; ?>" 
                                   value="<?php echo esc_attr($option); ?>"
                                   <?php echo $required; ?>>
                            <?php echo esc_html($option); ?>
                        </label>
                        <?php
                    endforeach;
                endif;
                break;
                
            default:
                ?>
                <input type="text" 
                       id="<?php echo $field_id; ?>" 
                       name="<?php echo $field_name; ?>" 
                       placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                       <?php echo $required; ?>>
                <?php
                break;
        }
    }
    
    /**
     * Display login page
     */
    private function display_login_page() {
        // Get custom settings from Interface tab (prioritize new settings)
        $interface_logo_id = get_option('lift_docs_logo_upload', '');
        $interface_logo_width = get_option('lift_docs_custom_logo_width', '200');
        $interface_title = get_option('lift_docs_login_title', '');
        $interface_description = get_option('lift_docs_login_description', '');
        
        // Fallback to old settings if new ones are not set
        $logo_id = !empty($interface_logo_id) ? $interface_logo_id : get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $logo_width = !empty($interface_logo_width) ? $interface_logo_width . 'px' : '200px';
        
        // Debug: Log logo values for troubleshooting
        error_log('LIFT Docs Login Page Debug:');
        error_log('- Interface logo ID: ' . $interface_logo_id);
        error_log('- Main logo ID: ' . get_option('lift_docs_login_logo', ''));
        error_log('- Final logo ID used: ' . $logo_id);
        error_log('- Logo URL: ' . $logo_url);
        
        // Use Interface tab title/description if set, otherwise use defaults
        $display_title = !empty($interface_title) ? $interface_title : __('Document Access Portal', 'lift-docs-system');
        $display_description = !empty($interface_description) ? $interface_description : __('Please log in to access your documents', 'lift-docs-system');
        
        // Get color settings (keep existing)
        $bg_color = get_option('lift_docs_login_bg_color', '#f0f4f8');
        $form_bg = get_option('lift_docs_login_form_bg', '#ffffff');
        $btn_color = get_option('lift_docs_login_btn_color', '#1976d2');
        $input_color = get_option('lift_docs_login_input_color', '#e0e0e0');
        $text_color = get_option('lift_docs_login_text_color', '#333333');
        
        // Function to adjust brightness for gradient colors
        function adjustBrightness($color, $percent) {
            $color = str_replace('#', '', $color);
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
            
            $r = max(0, min(255, $r + ($r * $percent / 100)));
            $g = max(0, min(255, $g + ($g * $percent / 100)));
            $b = max(0, min(255, $b + ($b * $percent / 100)));
            
            return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
        }
        
        // Simple HTML without theme header/footer - Clean standalone login page
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Document Login', 'lift-docs-system'); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                /* Reset and hide all theme elements for standalone login page */
                * {
                    box-sizing: border-box;
                }
                
                html {
                    margin: 0 !important;
                    padding: 0 !important;
                    height: 100%;
                }
                
                body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background-color: <?php echo esc_attr($bg_color); ?>;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    min-height: 100vh;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow-x: hidden;
                    background: linear-gradient(135deg, <?php echo esc_attr($bg_color); ?> 0%, <?php echo esc_attr(adjustBrightness($bg_color, -10)); ?> 100%);
                }
                
                /* Hide WordPress admin bar completely */
                #wpadminbar {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    margin: 0 !important;
                }
                
                /* Hide ALL theme elements aggressively */
                body > *:not(.lift-simple-login-container),
                header, footer, main, aside, section, article,
                .header, .footer, .main, .content, .container, .wrapper,
                nav, .nav, .navigation, .menu, .menubar,
                .sidebar, .widget, .widget-area,
                .site-header, .site-footer, .site-content, .site-main,
                .page-header, .page-footer, .page-content,
                .entry-header, .entry-footer, .entry-content,
                .post-header, .post-footer, .post-content,
                [class*="header"], [class*="footer"], [class*="nav"], 
                [class*="menu"], [class*="sidebar"], [class*="widget"],
                [id*="header"], [id*="footer"], [id*="nav"], 
                [id*="menu"], [id*="sidebar"], [id*="widget"] {
                    display: none !important;
                    visibility: hidden !important;
                    position: absolute !important;
                    left: -9999px !important;
                    top: -9999px !important;
                    width: 0 !important;
                    height: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                
                /* Hide other common theme elements */
                .back-to-top, #back-to-top, .scroll-to-top,
                [class*="back-to-top"], [id*="back-to-top"],
                [class*="scroll-top"], [id*="scroll-top"],
                .breadcrumb, .breadcrumbs, [class*="breadcrumb"],
                .social, .social-links, [class*="social"],
                .search-form, .searchform, [class*="search"],
                .comments, .comment, [class*="comment"] {
                    display: none !important;
                    visibility: hidden !important;
                }
                
                /* Enhanced form container styling */
                .lift-simple-login-container {
                    width: 100%;
                    max-width: 420px;
                    margin: 20px;
                    position: relative;
                    z-index: 9999;
                }
                
                .lift-login-logo {
                    text-align: center;
                    margin-bottom: 40px;
                    padding: 15px 0;
                }
                
                .lift-login-logo img {
                    max-width: <?php echo esc_attr($logo_width); ?>;
                    height: auto;
                    border-radius: 12px;
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                    /* No animation */
                }
                
                .lift-login-logo img:hover {
                    /* No hover animation */
                }
                
                .lift-login-form-wrapper {
                    background: <?php echo esc_attr($form_bg); ?>;
                    padding: 50px 40px;
                    border-radius: 20px;
                    box-shadow: 
                        0 10px 30px rgba(0, 0, 0, 0.15),
                        0 1px 8px rgba(0, 0, 0, 0.1);
                    backdrop-filter: blur(10px);
                    position: relative;
                    overflow: hidden;
                }
                
                .lift-login-form-wrapper::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, <?php echo esc_attr($btn_color); ?>, <?php echo esc_attr(adjustBrightness($btn_color, 20)); ?>);
                }
                
                .lift-login-title {
                    text-align: center;
                    margin: 0 0 20px 0;
                    font-size: 32px;
                    font-weight: 700;
                    color: <?php echo esc_attr($text_color); ?>;
                    letter-spacing: -0.5px;
                }
                
                .lift-login-description {
                    text-align: center;
                    margin-bottom: 35px;
                    color: <?php echo esc_attr($text_color); ?>;
                    opacity: 0.7;
                    font-size: 16px;
                    line-height: 1.6;
                    font-weight: 400;
                }
                
                /* Enhanced form field styling */
                .lift-form-group {
                    margin-bottom: 25px;
                    position: relative;
                }
                
                .lift-form-group label {
                    display: block;
                    margin-bottom: 10px;
                    font-weight: 600;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-size: 14px;
                    letter-spacing: 0.3px;
                }
                
                .lift-form-group input[type="text"],
                .lift-form-group input[type="password"] {
                    width: 100%;
                    padding: 16px 20px;
                    border: 2px solid <?php echo esc_attr($input_color); ?>;
                    border-radius: 12px;
                    font-size: 16px;
                    background: #fff;
                    color: <?php echo esc_attr($text_color); ?>;
                    box-sizing: border-box;
                    /* No animation */
                    font-weight: 500;
                }
                
                .lift-form-group input[type="text"]:focus,
                .lift-form-group input[type="password"]:focus {
                    outline: none;
                    border-color: <?php echo esc_attr($btn_color); ?>;
                    box-shadow: 0 0 0 3px <?php echo esc_attr($btn_color); ?>20;
                    /* No transform animation */
                }
                
                .lift-form-group input[type="text"]::placeholder,
                .lift-form-group input[type="password"]::placeholder {
                    color: #aaa;
                    font-weight: 400;
                }
                
                .password-field-wrapper {
                    position: relative;
                }
                
                .toggle-password {
                    position: absolute;
                    right: 16px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    cursor: pointer;
                    color: #666;
                    padding: 8px;
                    border-radius: 6px;
                    /* No animation */
                }
                
                .toggle-password:hover {
                    background: rgba(0, 0, 0, 0.05);
                    color: <?php echo esc_attr($btn_color); ?>;
                }
                
                .form-hint {
                    font-size: 13px;
                    color: #888;
                    margin-top: 6px;
                    display: block;
                    font-weight: 400;
                    line-height: 1.4;
                }
                
                /* Enhanced checkbox styling */
                .checkbox-group {
                    display: flex;
                    align-items: center;
                    margin-bottom: 30px;
                }
                
                .checkbox-label {
                    display: flex;
                    align-items: center;
                    cursor: pointer;
                    margin: 0;
                    font-size: 14px;
                    color: <?php echo esc_attr($text_color); ?>;
                    user-select: none;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }
                
                .checkbox-label:hover {
                    color: <?php echo esc_attr($btn_color); ?>;
                }
                
                .checkbox-label input[type="checkbox"] {
                    appearance: none;
                    width: 22px;
                    height: 22px;
                    border: 2px solid <?php echo esc_attr($input_color); ?>;
                    border-radius: 8px;
                    background: #fff;
                    margin-right: 14px;
                    position: relative;
                    cursor: pointer;
                    flex-shrink: 0;
                    /* No animation */
                }
                
                .checkbox-label input[type="checkbox"]:focus {
                    outline: none;
                    box-shadow: 0 0 0 3px <?php echo esc_attr($btn_color); ?>20;
                }
                
                .checkbox-label input[type="checkbox"]:checked {
                    background: <?php echo esc_attr($btn_color); ?>;
                    border-color: <?php echo esc_attr($btn_color); ?>;
                    /* No scale animation */
                }
                
                .checkbox-label input[type="checkbox"]:checked::after {
                    content: '✓';
                    color: white;
                    font-size: 16px;
                    font-weight: bold;
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    /* No animation */
                }
                
                /* Checkmark animation removed */
                
                .checkbox-label:hover input[type="checkbox"]:not(:checked) {
                    border-color: <?php echo esc_attr($btn_color); ?>;
                    background: #f8f9fa;
                }
                
                /* Enhanced button styling */
                .lift-login-btn {
                    width: 100%;
                    padding: 18px 24px;
                    background: linear-gradient(135deg, <?php echo esc_attr($btn_color); ?> 0%, <?php echo esc_attr(adjustBrightness($btn_color, -10)); ?> 100%);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    font-size: 16px;
                    font-weight: 700;
                    cursor: pointer;
                    position: relative;
                    letter-spacing: 0.5px;
                    /* No animation */
                    box-shadow: 0 4px 12px <?php echo esc_attr($btn_color); ?>40;
                }
                
                .lift-login-btn:hover {
                    /* No hover animation */
                    box-shadow: 0 8px 20px <?php echo esc_attr($btn_color); ?>60;
                }
                
                .lift-login-btn:active {
                    /* No active animation */
                    box-shadow: 0 2px 8px <?php echo esc_attr($btn_color); ?>40;
                }
                
                .lift-login-btn:disabled {
                    opacity: 0.7;
                    cursor: not-allowed;
                    /* No transform */
                    box-shadow: 0 2px 8px <?php echo esc_attr($btn_color); ?>30;
                }
                
                .btn-spinner {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                }
                
                .spinner {
                    width: 18px;
                    height: 18px;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    border-top: 2px solid white;
                    border-radius: 50%;
                    /* No spinner animation */
                }
                
                /* Spinner animation removed */
                
                /* Enhanced form messages - Login specific styles */
                .login-error {
                    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
                    color: #c62828;
                    padding: 16px 20px;
                    border-radius: 12px;
                    border-left: 4px solid #c62828;
                    margin-bottom: 15px;
                    font-weight: 500;
                    box-shadow: 0 2px 8px rgba(198, 40, 40, 0.1);
                }
                
                .login-success {
                    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
                    color: #2e7d32;
                    padding: 16px 20px;
                    border-radius: 12px;
                    border-left: 4px solid #2e7d32;
                    margin-bottom: 15px;
                    font-weight: 500;
                    box-shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
                }
                
                .login-help {
                    text-align: center;
                    margin-top: 35px;
                    font-size: 14px;
                    color: #888;
                }
                
                .login-help a {
                    color: <?php echo esc_attr($btn_color); ?>;
                    text-decoration: none;
                    font-weight: 600;
                    /* No animation */
                }
                
                .login-help a:hover {
                    color: <?php echo esc_attr(adjustBrightness($btn_color, -15)); ?>;
                    text-decoration: underline;
                }
                
                /* Responsive Design */
                @media (max-width: 768px) {
                    body {
                        padding: 10px;
                        align-items: flex-start;
                        padding-top: 40px;
                    }
                    
                    .lift-simple-login-container {
                        max-width: 100%;
                        margin: 0;
                        width: 100%;
                    }
                    
                    .lift-login-form-wrapper {
                        padding: 35px 25px;
                        border-radius: 16px;
                    }
                    
                    .lift-login-title {
                        font-size: 28px;
                    }
                    
                    .lift-form-group input[type="text"],
                    .lift-form-group input[type="password"] {
                        padding: 14px 16px;
                        font-size: 16px; /* Prevent zoom on iOS */
                    }
                    
                    .lift-login-btn {
                        padding: 16px 20px;
                        font-size: 16px;
                    }
                }
                
                @media (max-width: 480px) {
                    .lift-login-form-wrapper {
                        padding: 30px 20px;
                        margin: 0 10px;
                    }
                    
                    .lift-login-title {
                        font-size: 24px;
                    }
                    
                    .lift-login-description {
                        font-size: 14px;
                    }
                    
                    .lift-form-group label {
                        font-size: 13px;
                    }
                }
                
                /* Dark mode support */
                @media (prefers-color-scheme: dark) {
                    body {
                        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                    }
                    
                    .lift-login-form-wrapper {
                        background: rgba(30, 30, 30, 0.95);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                    }
                    
                    .lift-form-group input[type="text"],
                    .lift-form-group input[type="password"] {
                        background: rgba(255, 255, 255, 0.05);
                        border-color: rgba(255, 255, 255, 0.2);
                        color: #fff;
                    }
                    
                    .lift-form-group input[type="text"]::placeholder,
                    .lift-form-group input[type="password"]::placeholder {
                        color: rgba(255, 255, 255, 0.5);
                    }
                }
            </style>
        </head>
        <body>
            <div class="lift-simple-login-container">
                <!-- Logo Debug: <?php echo 'ID=' . $logo_id . ', URL=' . $logo_url . ', Time=' . time(); ?> -->
                <?php if ($logo_url): ?>
                <div class="lift-login-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
                </div>
                <?php else: ?>
                <!-- No logo: ID empty or URL failed -->
                <?php endif; ?>
                
                <div class="lift-login-form-wrapper">
                    <h1 class="lift-login-title"><?php echo esc_html($display_title); ?></h1>
                    <?php if (!empty($display_description)): ?>
                    <p class="lift-login-description" style="text-align: center; margin-bottom: 25px; color: <?php echo esc_attr($text_color); ?>; opacity: 0.8;"><?php echo esc_html($display_description); ?></p>
                    <?php endif; ?>
                    
                    <form id="lift-docs-login-form" class="lift-docs-login-form">
                        <?php wp_nonce_field('docs_login_nonce', 'docs_login_nonce'); ?>
                        
                        <div class="lift-form-group">
                            <label for="docs_username"><?php _e('Username, Email or User Code', 'lift-docs-system'); ?></label>
                            <input type="text" id="docs_username" name="username" 
                                   placeholder="<?php _e('Enter username, email or user code...', 'lift-docs-system'); ?>" 
                                   required autocomplete="username">
                            <small class="form-hint"><?php _e('You can use your username, email address, or your unique user code', 'lift-docs-system'); ?></small>
                        </div>
                        
                        <div class="lift-form-group">
                            <label for="docs_password"><?php _e('Password', 'lift-docs-system'); ?></label>
                            <div class="password-field-wrapper">
                                <input type="password" id="docs_password" name="password" 
                                       placeholder="<?php _e('Enter your password...', 'lift-docs-system'); ?>" 
                                       required autocomplete="current-password">
                                <button type="button" class="toggle-password" tabindex="-1">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="lift-form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="docs_remember" name="remember" value="1">
                                <?php _e('Remember me', 'lift-docs-system'); ?>
                            </label>
                        </div>
                        
                        <div class="lift-form-group">
                            <button type="submit" class="lift-login-btn">
                                <span class="btn-text"><?php _e('Sign In', 'lift-docs-system'); ?></span>
                                <span class="btn-spinner" style="display: none;">
                                    <span class="spinner"></span>
                                    <?php _e('Signing in...', 'lift-docs-system'); ?>
                                </span>
                            </button>
                        </div>
                        
                        <div class="lift-form-messages">
                            <div class="login-error" style="display: none;"></div>
                            <div class="login-success" style="display: none;"></div>
                        </div>
                    </form>
                    
                    <div class="login-help">
                        <a href="<?php echo wp_lostpassword_url(); ?>">
                            <?php _e('Forgot your password?', 'lift-docs-system'); ?>
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
     * Display dashboard page
     */
    private function display_dashboard_page() {
        // Get theme header
        get_header();
        
        $current_user = wp_get_current_user();
        $user_code = get_user_meta($current_user->ID, 'lift_docs_user_code', true);
        
        // Get user's assigned documents
        $user_documents = $this->get_user_documents($current_user->ID);
        
        ?>
        <div class="lift-docs-dashboard-container">
            <div class="lift-docs-dashboard-wrapper">
                <!-- Dashboard Header -->
                <div class="lift-docs-dashboard-header">
                    <div class="dashboard-user-info">
                        <h1><?php printf(__('Welcome, %s', 'lift-docs-system'), esc_html($current_user->display_name)); ?></h1>
                        <div class="user-meta">
                            <span class="user-email"><?php echo esc_html($current_user->user_email); ?></span>
                            <?php if ($user_code): ?>
                                <span class="user-code"><?php _e('Code:', 'lift-docs-system'); ?> <strong><?php echo esc_html($user_code); ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dashboard-actions">
                        <button type="button" id="docs-logout-btn" class="logout-btn">
                            <span class="dashicons dashicons-exit"></span>
                            <?php _e('Logout', 'lift-docs-system'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Dashboard Content -->
                <div class="lift-docs-dashboard-content">
                    <!-- Quick Stats -->
                    <div class="dashboard-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-media-document"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo count($user_documents); ?></h3>
                                <p><?php _e('Documents', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $this->get_user_download_count($current_user->ID); ?></h3>
                                <p><?php _e('Downloads', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $this->get_user_view_count($current_user->ID); ?></h3>
                                <p><?php _e('Views', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo date_i18n('M d', strtotime($current_user->user_registered)); ?></h3>
                                <p><?php _e('Member Since', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Library -->
                    <div class="documents-section">
                        <div class="section-header">
                            <h2><?php _e('Your Document Library', 'lift-docs-system'); ?></h2>
                        </div>
                        
                        <?php if (!empty($user_documents)): ?>
                            <div class="documents-list" id="documents-list">
                                <?php foreach ($user_documents as $document): ?>
                                    <?php $this->render_document_card($document, $current_user->ID); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-documents">
                                <div class="no-documents-icon">
                                    <span class="dashicons dashicons-portfolio"></span>
                                </div>
                                <h3><?php _e('No Documents Available', 'lift-docs-system'); ?></h3>
                                <p><?php _e('You don\'t have access to any documents yet. Contact your administrator for access.', 'lift-docs-system'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Document Modal -->
        <div id="lift-document-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2 id="modal-document-title"><?php _e('Document Details', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>
                <div class="lift-modal-body">
                    <div id="modal-document-content"></div>
                </div>
            </div>
        </div>
        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>
        
        <?php
        // Get theme footer
        get_footer();
    }
    
    /**
     * Get documents assigned to user
     */
    private function get_user_documents($user_id) {
        // Get all published documents
        $all_documents = get_posts(array(
            'post_type' => 'lift_document',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $user_documents = array();
        
        foreach ($all_documents as $document) {
            $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
            
            // If no specific assignments, only admin and editor can see
            if (empty($assigned_users) || !is_array($assigned_users)) {
                // Only admin and editor can see unassigned documents
                if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_lift_documents')) {
                    $user_documents[] = $document;
                }
            } 
            // Check if user is specifically assigned
            else if (in_array($user_id, $assigned_users)) {
                $user_documents[] = $document;
            }
        }
        
        return $user_documents;
    }
    
    /**
     * Get user download count
     */
    private function get_user_download_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND action = 'download'",
            $user_id
        ));
        
        return $count ? $count : 0;
    }
    
    /**
     * Get user view count
     */
    private function get_user_view_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND action = 'view'",
            $user_id
        ));
        
        return $count ? $count : 0;
    }
    
    /**
     * Check if user can view document
     */
    private function user_can_view_document($document_id) {
        // Get current user
        $current_user_id = get_current_user_id();
        
        // Admin and editors can always view
        if (user_can($current_user_id, 'manage_options') || user_can($current_user_id, 'edit_lift_documents')) {
            return true;
        }
        
        // Check if document is assigned to current user
        $assigned_users = get_post_meta($document_id, '_lift_doc_assigned_users', true);
        
        if (empty($assigned_users) || !is_array($assigned_users)) {
            // Unassigned documents - only admin/editor can view
            return false;
        }
        
        // Check if current user is in assigned list
        return in_array($current_user_id, $assigned_users);
    }
    
    /**
     * Render document card
     */
    private function render_document_card($document, $user_id) {
        $file_urls = get_post_meta($document->ID, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            $file_urls = array(get_post_meta($document->ID, '_lift_doc_file_url', true));
        }
        $file_urls = array_filter($file_urls);
        
        $file_count = count($file_urls);
        $views = get_post_meta($document->ID, '_lift_doc_views', true);
        $downloads = get_post_meta($document->ID, '_lift_doc_downloads', true);
        
        // Check if user has downloaded this document
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        $user_downloaded = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE document_id = %d AND user_id = %d AND action = 'download'",
            $document->ID, $user_id
        ));
        
        ?>
        <div class="document-card" data-document-id="<?php echo $document->ID; ?>">
            <div class="document-card-header-meta">
                <div class="document-header-info">
                    <h3 class="document-title"><?php echo esc_html($document->post_title); ?></h3>
                    <div class="document-badges">
                        <?php if ($file_count > 1): ?>
                            <span class="badge files-badge"><?php echo $file_count; ?> <?php _e('files', 'lift-docs-system'); ?></span>
                        <?php endif; ?>
                        <?php if ($user_downloaded): ?>
                            <span class="badge downloaded-badge"><?php _e('Downloaded', 'lift-docs-system'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="document-meta">
                    <span class="document-date">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo get_the_date('M j, Y', $document->ID); ?>
                    </span>
                    <span class="document-stats">
                        <span class="views">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php echo $views ? $views : 0; ?>
                        </span>
                        <span class="downloads">
                            <span class="dashicons dashicons-download"></span>
                            <?php echo $downloads ? $downloads : 0; ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="document-card-content">
                <?php if ($document->post_excerpt): ?>
                    <p class="document-excerpt"><?php echo esc_html($document->post_excerpt); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($document->post_content)): ?>
                    <div class="document-content-preview">
                        <button type="button" class="btn btn-content-preview" data-document-id="<?php echo $document->ID; ?>">
                            <span class="dashicons dashicons-text-page"></span>
                            <?php _e('View Content', 'lift-docs-system'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="document-card-actions">
                <?php 
                // Show View URL link
                if ($this->user_can_view_document($document->ID)) {
                    $view_text = __('View Document', 'lift-docs-system');
                    if (LIFT_Docs_Settings::get_setting('enable_secure_links', false)) {
                        $view_url = LIFT_Docs_Settings::generate_secure_link($document->ID);
                    } else {
                        $view_url = get_permalink($document->ID);
                    }
                    ?>
                    <a href="<?php echo esc_url($view_url); ?>" class="btn btn-primary" target="_blank">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo $view_text; ?>
                    </a>
                    <?php
                }
                
                // Show assigned form links
                $assigned_forms = get_post_meta($document->ID, '_lift_doc_assigned_forms', true);
                if (!empty($assigned_forms) && is_array($assigned_forms)) {
                    global $wpdb;
                    $forms_table = $wpdb->prefix . 'lift_forms';
                    $current_user_id = get_current_user_id();
                    
                    foreach ($assigned_forms as $form_id) {
                        $form = $wpdb->get_row($wpdb->prepare(
                            "SELECT id, name FROM $forms_table WHERE id = %d AND status = 'active'",
                            $form_id
                        ));
                        
                        if ($form) {
                            $form_url = add_query_arg(array(
                                'document_id' => $document->ID,
                                'form_id' => $form->id
                            ), home_url('/document-form/'));
                            
                            // Check if user has already submitted this form for this document
                            $has_submitted = false;
                            $button_text = $form->name;
                            $button_class = 'btn btn-secondary';
                            
                            if ($current_user_id > 0) {
                                $lift_forms = new LIFT_Forms();
                                $has_submitted = $lift_forms->user_has_submitted_form($current_user_id, $form->id, $document->ID);
                                
                                if ($has_submitted) {
                                    $button_text = sprintf(__('Edit %s', 'lift-docs-system'), $form->name);
                                    $button_class = 'btn btn-primary';
                                }
                            }
                            ?>
                            <a href="<?php echo esc_url($form_url); ?>" class="<?php echo esc_attr($button_class); ?>" target="_blank">
                                <?php echo esc_html($button_text); ?>
                                <?php if ($has_submitted): ?>
                                    <span class="dashicons dashicons-edit" style="margin-left: 5px;"></span>
                                <?php endif; ?>
                            </a>
                            <?php
                        }
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX login
     */
    public function handle_ajax_login() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'docs_login_nonce')) {
            wp_send_json_error(__('Security check failed', 'lift-docs-system'));
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(__('Please fill in all required fields.', 'lift-docs-system'));
        }
        
        // Try to find user by username, email, or user code
        $user = $this->find_user_by_login($username);
        
        if (!$user) {
            wp_send_json_error(__('User not found. Please check your credentials.', 'lift-docs-system'));
        }
        
        // Use wp_signon like WordPress login
        $credentials = array(
            'user_login'    => $user->user_login,
            'user_password' => $password,
            'remember'      => $remember
        );
        
        $user_signon = wp_signon($credentials, false);
        
        if (is_wp_error($user_signon)) {
            wp_send_json_error($user_signon->get_error_message());
        }
        
        // Check if user has document access after successful login
        if (!in_array('documents_user', $user_signon->roles) && !user_can($user_signon->ID, 'view_lift_documents')) {
            wp_logout(); // Logout if no access
            wp_send_json_error(__('You do not have permission to access documents.', 'lift-docs-system'));
        }
        
        // Log the login
        $this->log_user_login($user_signon->ID);
        
        // Determine redirect URL
        $redirect_url = '';
        if (!empty($_POST['redirect_to'])) {
            $redirect_url = esc_url($_POST['redirect_to']);
        } else {
            $redirect_url = $this->get_dashboard_url();
        }
        
        wp_send_json_success(array(
            'redirect_url' => $redirect_url,
            'message' => sprintf(__('Welcome, %s!', 'lift-docs-system'), $user->display_name)
        ));
    }
    
    /**
     * Handle AJAX logout
     */
    public function handle_ajax_logout() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            wp_logout();
            $this->log_user_logout($user_id);
        }
        
        wp_send_json_success(array(
            'redirect_url' => $this->get_login_url(),
            'message' => __('You have been logged out successfully.', 'lift-docs-system')
        ));
    }
    
    /**
     * Handle AJAX load document content
     */
    public function handle_ajax_load_document_content() {
        // Check if user is logged in and has access
        if (!is_user_logged_in() || !$this->user_has_docs_access()) {
            wp_send_json_error(__('Access denied.', 'lift-docs-system'));
        }
        
        $document_id = intval($_POST['document_id'] ?? 0);
        
        if (!$document_id) {
            wp_send_json_error(__('Invalid document ID.', 'lift-docs-system'));
        }
        
        // Check if user can view this document
        if (!$this->user_can_view_document($document_id)) {
            wp_send_json_error(__('You do not have permission to view this document.', 'lift-docs-system'));
        }
        
        $document = get_post($document_id);
        
        if (!$document || $document->post_type !== 'lift_document') {
            wp_send_json_error(__('Document not found.', 'lift-docs-system'));
        }
        
        // Process the content (apply filters like do_shortcode, wpautop, etc.)
        $content = apply_filters('the_content', $document->post_content);
        
        wp_send_json_success(array(
            'title' => $document->post_title,
            'content' => $content
        ));
    }
    
    /**
     * Find user by username, email, or user code
     */
    private function find_user_by_login($login) {
        // Try username first
        $user = get_user_by('login', $login);
        if ($user) return $user;
        
        // Try email
        $user = get_user_by('email', $login);
        if ($user) return $user;
        
        // Try user code
        $users = get_users(array(
            'meta_key' => 'lift_docs_user_code',
            'meta_value' => $login,
            'meta_compare' => '=',
            'number' => 1
        ));
        
        return !empty($users) ? $users[0] : false;
    }
    
    /**
     * Public method for debugging - find user by login
     */
    public function debug_find_user($login) {
        return $this->find_user_by_login($login);
    }
    
    /**
     * Log user login
     */
    private function log_user_login($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'document_id' => 0,
                'action' => 'login',
                'timestamp' => current_time('mysql'),
                'ip_address' => $this->get_client_ip()
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log user logout
     */
    private function log_user_logout($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'document_id' => 0,
                'action' => 'logout',
                'timestamp' => current_time('mysql'),
                'ip_address' => $this->get_client_ip()
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Load on docs pages OR when shortcodes are present
        $load_scripts = false;
        
        // Check for URL-based pages
        if (get_query_var('docs_login') || get_query_var('docs_dashboard')) {
            $load_scripts = true;
        }
        
        // Check for shortcodes in current page content
        global $post;
        if ($post && (has_shortcode($post->post_content, 'docs_login_form') || has_shortcode($post->post_content, 'docs_dashboard'))) {
            $load_scripts = true;
        }
        
        if (!$load_scripts) {
            return;
        }
        
        wp_enqueue_script('lift-docs-frontend-login', plugin_dir_url(__FILE__) . '../assets/js/frontend-login.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('lift-docs-frontend-login', plugin_dir_url(__FILE__) . '../assets/css/frontend-login.css', array(), '1.0.0');
        
        // Localize script
        wp_localize_script('lift-docs-frontend-login', 'liftDocsLogin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docs_login_nonce'),
            'dashboard_url' => $this->get_dashboard_url(),
            'login_url' => $this->get_login_url(),
            'strings' => array(
                'loginError' => __('Login failed. Please try again.', 'lift-docs-system'),
                'loginSuccess' => __('Login successful! Redirecting...', 'lift-docs-system'),
                'logoutSuccess' => __('Logged out successfully!', 'lift-docs-system'),
                'requiredField' => __('This field is required.', 'lift-docs-system'),
                'invalidEmail' => __('Please enter a valid email address.', 'lift-docs-system'),
                'signingIn' => __('Signing in...', 'lift-docs-system'),
                'signIn' => __('Sign In', 'lift-docs-system')
            )
        ));
    }
    
    /**
     * Get login URL (from page created during activation)
     */
    private function get_login_url() {
        $login_page_id = get_option('lift_docs_login_page_id');
        if ($login_page_id && get_post($login_page_id)) {
            return get_permalink($login_page_id);
        }
        // Fallback: try to find page by slug
        $page = get_page_by_path('document-login');
        if ($page) {
            return get_permalink($page->ID);
        }
        return home_url('/document-login/');
    }
    
    /**
     * Get dashboard URL (from page created during activation)
     */
    private function get_dashboard_url() {
        $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
        if ($dashboard_page_id && get_post($dashboard_page_id)) {
            return get_permalink($dashboard_page_id);
        }
        // Fallback: try to find page by slug
        $page = get_page_by_path('document-dashboard');
        if ($page) {
            return get_permalink($page->ID);
        }
        return home_url('/document-dashboard/');
    }
    
    /**
     * Login form shortcode
     */
    public function login_form_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'redirect_to' => '' // Custom redirect URL after login
        ), $atts);
        
        // Check if user is already logged in
        if (is_user_logged_in() && $this->user_has_docs_access()) {
            $redirect_url = !empty($atts['redirect_to']) ? $atts['redirect_to'] : $this->get_dashboard_url();
            return '<div class="docs-already-logged-in">
                <p>' . sprintf(__('You are already logged in. <a href="%s">Go to Dashboard</a>', 'lift-docs-system'), $redirect_url) . '</p>
            </div>';
        }
        
        // Get custom settings from Interface tab (prioritize new settings)
        $interface_logo_id = get_option('lift_docs_logo_upload', '');
        $interface_logo_width = get_option('lift_docs_custom_logo_width', '200');
        $interface_title = get_option('lift_docs_login_title', '');
        $interface_description = get_option('lift_docs_login_description', '');
        
        // Fallback to old settings if new ones are not set
        $logo_id = !empty($interface_logo_id) ? $interface_logo_id : get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $logo_width = !empty($interface_logo_width) ? $interface_logo_width . 'px' : '200px';
        
        // Use Interface tab title/description if set, otherwise use defaults
        $display_title = !empty($interface_title) ? $interface_title : __('Documents Login', 'lift-docs-system');
        $display_description = !empty($interface_description) ? $interface_description : __('Access your personal document library', 'lift-docs-system');
        
        // Get color settings (keep existing)
        $bg_color = get_option('lift_docs_login_bg_color', '#f0f4f8');
        $form_bg = get_option('lift_docs_login_form_bg', '#ffffff');
        $btn_color = get_option('lift_docs_login_btn_color', '#1976d2');
        $input_color = get_option('lift_docs_login_input_color', '#e0e0e0');
        $text_color = get_option('lift_docs_login_text_color', '#333333');
        
        // Store redirect URL for AJAX handler
        if (!empty($atts['redirect_to'])) {
            set_transient('docs_login_redirect_' . session_id(), $atts['redirect_to'], 300); // 5 minutes
        }
        
        ob_start();
        ?>
        <style>
            /* Hide back-to-top button for shortcode version too */
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
            
            .lift-docs-login-container.shortcode-version {
                background-color: <?php echo esc_attr($bg_color); ?>;
                padding: 40px 20px;
                border-radius: 12px;
                margin: 20px 0;
            }
            
            .lift-docs-login-container.shortcode-version .lift-login-logo {
                text-align: center;
                margin-bottom: 25px;
                padding: 15px 0;
            }
            
            .lift-docs-login-container.shortcode-version .lift-login-logo img {
                max-width: <?php echo esc_attr($logo_width); ?>;
                height: auto;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            
            .lift-docs-login-container.shortcode-version .lift-docs-login-form-container {
                background: <?php echo esc_attr($form_bg); ?>;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                max-width: 400px;
                margin: 0 auto;
            }
            
            .lift-docs-login-container.shortcode-version .lift-docs-login-header h2 {
                text-align: center;
                margin: 0 0 20px 0;
                color: <?php echo esc_attr($text_color); ?>;
                font-size: 24px;
            }
            
            .lift-docs-login-container.shortcode-version .description {
                text-align: center;
                color: <?php echo esc_attr($text_color); ?>;
                margin-bottom: 25px;
                opacity: 0.8;
            }
            
            .lift-docs-login-container.shortcode-version .lift-form-group {
                margin-bottom: 20px;
            }
            
            .lift-docs-login-container.shortcode-version .lift-form-group label {
                display: block;
                margin-bottom: 6px;
                font-weight: 500;
                color: <?php echo esc_attr($text_color); ?>;
            }
            
            .lift-docs-login-container.shortcode-version .lift-form-group input[type="text"],
            .lift-docs-login-container.shortcode-version .lift-form-group input[type="password"] {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid <?php echo esc_attr($input_color); ?>;
                border-radius: 6px;
                font-size: 14px;
                background: #fff;
                color: <?php echo esc_attr($text_color); ?>;
                box-sizing: border-box;
                transition: none; /* Remove transition animation */
            }
            
            .lift-docs-login-container.shortcode-version .lift-form-group input:focus {
                outline: none;
                border-color: <?php echo esc_attr($btn_color); ?>;
            }
            
            .lift-docs-login-container.shortcode-version .lift-login-btn {
                width: 100%;
                padding: 12px;
                background: <?php echo esc_attr($btn_color); ?>;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            }
            
            .lift-docs-login-container.shortcode-version .lift-login-btn:hover {
                opacity: 0.9;
            }
            
            .lift-docs-login-container.shortcode-version .form-hint {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
                display: block;
            }
            
            .lift-docs-login-container.shortcode-version .checkbox-group {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .lift-docs-login-container.shortcode-version .checkbox-label {
                display: flex;
                align-items: center;
                cursor: pointer;
                margin: 0;
                font-size: 14px;
                color: <?php echo esc_attr($text_color); ?>;
                user-select: none;
            }
            
            .lift-docs-login-container.shortcode-version .checkbox-label input[type="checkbox"] {
                appearance: none;
                width: 18px;
                height: 18px;
                border: 2px solid <?php echo esc_attr($input_color); ?>;
                border-radius: 4px;
                background: #fff;
                margin-right: 8px;
                position: relative;
                cursor: pointer;
            }
            
            .lift-docs-login-container.shortcode-version .checkbox-label input[type="checkbox"]:checked {
                background: <?php echo esc_attr($btn_color); ?>;
                border-color: <?php echo esc_attr($btn_color); ?>;
            }
            
            .lift-docs-login-container.shortcode-version .checkbox-label input[type="checkbox"]:checked::after {
                content: '✓';
                color: white;
                font-size: 12px;
                font-weight: bold;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            
            .lift-docs-login-container.shortcode-version .login-help {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
            }
            
            .lift-docs-login-container.shortcode-version .login-help a {
                color: <?php echo esc_attr($btn_color); ?>;
                text-decoration: none;
            }
            
            .lift-docs-login-container.shortcode-version .login-error {
                background: #ffebee;
                color: #c62828;
                padding: 10px;
                border-radius: 4px;
                border-left: 3px solid #c62828;
                margin-bottom: 10px;
            }
            
            .lift-docs-login-container.shortcode-version .login-success {
                background: #e8f5e8;
                color: #2e7d32;
                padding: 10px;
                border-radius: 4px;
                border-left: 3px solid #2e7d32;
                margin-bottom: 10px;
            }
        </style>
        
        <div class="lift-docs-login-container shortcode-version">
            <!-- Shortcode Logo Debug: <?php echo 'ID=' . $logo_id . ', URL=' . $logo_url . ', Time=' . time(); ?> -->
            <?php if ($logo_url): ?>
            <div class="lift-login-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
            </div>
            <?php else: ?>
            <!-- No logo in shortcode: ID empty or URL failed -->
            <?php endif; ?>
            
            <div class="lift-docs-login-form-container">
                <div class="lift-docs-login-header">
                    <h2><?php echo esc_html($display_title); ?></h2>
                    <?php if (!empty($display_description)): ?>
                    <p class="description"><?php echo esc_html($display_description); ?></p>
                    <?php endif; ?>
                </div>
                
                <form id="lift-docs-login-form" class="lift-docs-login-form">
                    <?php wp_nonce_field('docs_login_nonce', 'docs_login_nonce'); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($atts['redirect_to']); ?>">
                    
                    <div class="lift-form-group">
                        <label for="docs_username"><?php _e('Username, Email or User Code', 'lift-docs-system'); ?></label>
                        <input type="text" id="docs_username" name="username" 
                               placeholder="<?php _e('Enter username, email or user code...', 'lift-docs-system'); ?>" 
                               required autocomplete="username">
                        <small class="form-hint"><?php _e('You can use your username, email address, or your unique user code', 'lift-docs-system'); ?></small>
                    </div>
                    
                    <div class="lift-form-group">
                        <label for="docs_password"><?php _e('Password', 'lift-docs-system'); ?></label>
                        <div class="password-field-wrapper">
                            <input type="password" id="docs_password" name="password" 
                                   placeholder="<?php _e('Enter your password...', 'lift-docs-system'); ?>" 
                                   required autocomplete="current-password">
                            <button type="button" class="toggle-password" tabindex="-1">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="lift-form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="docs_remember" name="remember" value="1">
                            <?php _e('Remember me', 'lift-docs-system'); ?>
                        </label>
                    </div>
                    
                    <div class="lift-form-group">
                        <button type="submit" class="lift-login-btn">
                            <span class="btn-text"><?php _e('Sign In', 'lift-docs-system'); ?></span>
                            <span class="btn-spinner" style="display: none;">
                                <span class="spinner"></span>
                                <?php _e('Signing in...', 'lift-docs-system'); ?>
                            </span>
                        </button>
                    </div>
                    
                    <div class="lift-form-messages">
                        <div class="login-error" style="display: none;"></div>
                        <div class="login-success" style="display: none;"></div>
                    </div>
                </form>
                
                <div class="login-help">
                    <a href="<?php echo wp_lostpassword_url(); ?>">
                        <?php _e('Forgot your password?', 'lift-docs-system'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Dashboard shortcode
     */
    public function dashboard_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_search' => 'true',
            'show_activity' => 'true',
            'documents_per_page' => 12
        ), $atts);
        
        // Check if user is logged in and has access
        if (!is_user_logged_in() || !$this->user_has_docs_access()) {
            // If this is not an AJAX request, redirect to login page
            if (!wp_doing_ajax() && !defined('DOING_AJAX')) {
                $login_url = $this->get_login_url();
                wp_redirect($login_url);
                exit;
            }
            
            // For AJAX requests or other contexts, show login message
            $login_url = $this->get_login_url();
            return '<div class="docs-login-required">
                <p>' . sprintf(__('Please <a href="%s">login</a> to access your document dashboard.', 'lift-docs-system'), $login_url) . '</p>
            </div>';
        }
        
        $current_user = wp_get_current_user();
        $user_code = get_user_meta($current_user->ID, 'lift_docs_user_code', true);
        $user_documents = $this->get_user_documents($current_user->ID);
        
        ob_start();
        ?>
        <div class="lift-docs-dashboard-container shortcode-version">
            <div class="lift-docs-dashboard-wrapper">
                <!-- Dashboard Header -->
                <div class="lift-docs-dashboard-header">
                    <div class="dashboard-user-info">
                        <h2><?php printf(__('Welcome, %s', 'lift-docs-system'), esc_html($current_user->display_name)); ?></h2>
                        <div class="user-meta">
                            <span class="user-email"><?php echo esc_html($current_user->user_email); ?></span>
                            <?php if ($user_code): ?>
                                <span class="user-code"><?php _e('Code:', 'lift-docs-system'); ?> <strong><?php echo esc_html($user_code); ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dashboard-actions">
                        <button type="button" id="docs-logout-btn" class="logout-btn">
                            <span class="dashicons dashicons-exit"></span>
                            <?php _e('Logout', 'lift-docs-system'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Dashboard Content -->
                <div class="lift-docs-dashboard-content">
                    <?php if ($atts['show_stats'] === 'true'): ?>
                    <!-- Quick Stats -->
                    <div class="dashboard-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-media-document"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo count($user_documents); ?></h3>
                                <p><?php _e('Documents', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $this->get_user_download_count($current_user->ID); ?></h3>
                                <p><?php _e('Downloads', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $this->get_user_view_count($current_user->ID); ?></h3>
                                <p><?php _e('Views', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo date_i18n('M d', strtotime($current_user->user_registered)); ?></h3>
                                <p><?php _e('Member Since', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Document Library -->
                    <div class="documents-section">
                        <div class="section-header">
                            <h3><?php _e('Your Document Library', 'lift-docs-system'); ?></h3>
                        </div>
                        
                        <?php if (!empty($user_documents)): ?>
                            <div class="documents-list" id="documents-list">
                                <?php 
                                $documents_to_show = array_slice($user_documents, 0, intval($atts['documents_per_page']));
                                foreach ($documents_to_show as $document): 
                                ?>
                                    <?php $this->render_document_card($document, $current_user->ID); ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($user_documents) > intval($atts['documents_per_page'])): ?>
                            <div class="load-more-container">
                                <button type="button" id="load-more-docs" class="btn btn-secondary">
                                    <?php _e('Load More Documents', 'lift-docs-system'); ?>
                                </button>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-documents">
                                <div class="no-documents-icon">
                                    <span class="dashicons dashicons-portfolio"></span>
                                </div>
                                <h4><?php _e('No Documents Available', 'lift-docs-system'); ?></h4>
                                <p><?php _e('You don\'t have access to any documents yet. Contact your administrator for access.', 'lift-docs-system'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Document Modal -->
        <div id="lift-document-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h3 id="modal-document-title"><?php _e('Document Details', 'lift-docs-system'); ?></h3>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>
                <div class="lift-modal-body">
                    <div id="modal-document-content"></div>
                </div>
            </div>
        </div>
        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Document form shortcode
     */
    public function document_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'document_id' => 0,
            'form_id' => 0
        ), $atts);
        
        $document_id = intval($atts['document_id']);
        $form_id = intval($atts['form_id']);
        
        if (!$document_id || !$form_id) {
            return '<div class="error"><p>' . __('Document ID and Form ID are required.', 'lift-docs-system') . '</p></div>';
        }
        
        // Check if user is logged in
        if (!is_user_logged_in() || !$this->user_has_docs_access()) {
            $login_url = $this->get_login_url();
            return '<div class="docs-login-required">
                <p>' . sprintf(__('Please <a href="%s">login</a> to access this form.', 'lift-docs-system'), $login_url) . '</p>
            </div>';
        }
        
        // Verify user has access to the document
        if (!$this->user_can_view_document($document_id)) {
            return '<div class="error"><p>' . __('You do not have access to this document.', 'lift-docs-system') . '</p></div>';
        }
        
        // Verify form is assigned to document
        $assigned_forms = get_post_meta($document_id, '_lift_doc_assigned_forms', true);
        if (!is_array($assigned_forms) || !in_array($form_id, $assigned_forms)) {
            return '<div class="error"><p>' . __('This form is not assigned to the document.', 'lift-docs-system') . '</p></div>';
        }
        
        // Get form data
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $forms_table WHERE id = %d AND status = 'active'",
            $form_id
        ));
        
        if (!$form) {
            return '<div class="error"><p>' . __('Form not found.', 'lift-docs-system') . '</p></div>';
        }
        
        // Get document data
        $document = get_post($document_id);
        if (!$document || $document->post_type !== 'lift_document') {
            return '<div class="error"><p>' . __('Document not found.', 'lift-docs-system') . '</p></div>';
        }
        
        $form_fields = json_decode($form->form_fields, true);
        
        ob_start();
        ?>
        <div class="document-form-container">
            <div class="form-header">
                <h3><?php echo esc_html($form->name); ?></h3>
                <div class="document-info">
                    <?php printf(__('Related to document: %s', 'lift-docs-system'), '<strong>' . esc_html($document->post_title) . '</strong>'); ?>
                </div>
                <?php if ($form->description): ?>
                    <p class="form-description"><?php echo esc_html($form->description); ?></p>
                <?php endif; ?>
            </div>
            
            <form id="document-form-shortcode" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                <input type="hidden" name="action" value="lift_forms_submit">
                <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">
                <input type="hidden" name="document_id" value="<?php echo esc_attr($document->ID); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('lift_forms_submit_nonce'); ?>">
                
                <div id="form-messages"></div>
                
                <?php if (!empty($form_fields) && is_array($form_fields)): ?>
                    <?php foreach ($form_fields as $field): ?>
                        <div class="form-field">
                            <label for="field_<?php echo esc_attr($field['id']); ?>">
                                <?php echo esc_html($field['label']); ?>
                                <?php if (isset($field['required']) && $field['required']): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php $this->render_form_field($field); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('This form has no fields configured.', 'lift-docs-system'); ?></p>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php _e('Submit Form', 'lift-docs-system'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#document-form-shortcode').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $messages = $('#form-messages');
                var $button = $form.find('button[type="submit"]');
                
                $button.prop('disabled', true).text('<?php _e('Submitting...', 'lift-docs-system'); ?>');
                $messages.html('');
                
                var formData = $form.serialize();
                
                $.post($form.attr('action'), formData, function(response) {
                    if (response.success) {
                        $messages.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        $form[0].reset();
                    } else {
                        $messages.html('<div class="notice notice-error"><p>' + (response.data || '<?php _e('Unknown error', 'lift-docs-system'); ?>') + '</p></div>');
                    }
                }).fail(function() {
                    $messages.html('<div class="notice notice-error"><p><?php _e('Network error. Please try again.', 'lift-docs-system'); ?></p></div>');
                }).always(function() {
                    $button.prop('disabled', false).text('<?php _e('Submit Form', 'lift-docs-system'); ?>');
                });
            });
        });
        </script>
        
        <style>
        .document-form-container {
            max-width: 600px;
            margin: 20px 0;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .form-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .form-header h3 {
            margin: 0 0 10px 0;
        }
        .document-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .form-description {
            color: #666;
            font-style: italic;
        }
        .form-field {
            margin-bottom: 15px;
        }
        .form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-field input,
        .form-field textarea,
        .form-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-field textarea {
            height: 80px;
            resize: vertical;
        }
        .required {
            color: #d63384;
        }
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        .notice {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .notice-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .notice-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Create default pages on plugin activation
     */
    public function create_default_pages() {
        // Create login page
        $login_page_id = get_option('lift_docs_login_page_id');
        if (!$login_page_id || !get_post($login_page_id)) {
            $login_page = array(
                'post_title' => __('Document Login', 'lift-docs-system'),
                'post_content' => '[docs_login_form]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_slug' => 'document-login'
            );
            
            $login_page_id = wp_insert_post($login_page);
            if ($login_page_id) {
                update_option('lift_docs_login_page_id', $login_page_id);
            }
        }
        
        // Create dashboard page
        $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
        if (!$dashboard_page_id || !get_post($dashboard_page_id)) {
            $dashboard_page = array(
                'post_title' => __('Document Dashboard', 'lift-docs-system'),
                'post_content' => '[docs_dashboard]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_slug' => 'document-dashboard'
            );
            
            $dashboard_page_id = wp_insert_post($dashboard_page);
            if ($dashboard_page_id) {
                update_option('lift_docs_dashboard_page_id', $dashboard_page_id);
            }
        }
        
        // Set flag that pages have been created
        update_option('lift_docs_default_pages_created', true);
    }
}

// Initialize
new LIFT_Docs_Frontend_Login();
