<?php
/**
 * Settings page for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Settings {
    
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
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_lift_docs_reset_settings', array($this, 'ajax_reset_settings'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Load on our settings page and any page containing 'lift-docs'
        if (strpos($hook, 'lift-docs-settings') === false && strpos($hook, 'lift-docs') === false) {
            // Also load if we're on any admin page with lift-docs in the URL
            if (!isset($_GET['page']) || strpos($_GET['page'], 'lift-docs') === false) {
                return;
            }
        }
        
        // Enqueue Font Awesome first
        wp_enqueue_style('font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
        
        // Enqueue WordPress media scripts - ALWAYS LOAD
        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('jquery');
        
        // Enqueue alpha color picker for transparency support
        wp_enqueue_script(
            'wp-color-picker-alpha',
            plugin_dir_url(__FILE__) . '../assets/js/wp-color-picker-alpha.min.js',
            array('jquery', 'wp-color-picker'),
            '3.0.0',
            true
        );
        
        // Enqueue admin styles for color picker
        wp_enqueue_style(
            'lift-docs-admin-styles',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array('wp-color-picker'),
            '1.0.0'
        );
        
        // Enqueue enhanced settings styles
        wp_enqueue_style(
            'lift-docs-settings-enhanced',
            plugin_dir_url(__FILE__) . '../assets/css/settings-enhanced.css',
            array('lift-docs-admin-styles'),
            '1.0.0'
        );
        
        // Enqueue enhanced settings JavaScript
        wp_enqueue_script(
            'lift-docs-settings-enhanced',
            plugin_dir_url(__FILE__) . '../assets/js/settings-enhanced.js',
            array('jquery', 'wp-color-picker'),
            '1.0.0',
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('lift-docs-settings-enhanced', 'liftDocsSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'reset_nonce' => wp_create_nonce('lift_docs_reset_settings'),
            'reset_confirm' => __('Are you sure you want to reset all settings to default? This will enable all checkboxes and cannot be undone.', 'lift-docs-system'),
            'reset_success' => __('Settings Reset! Please Save Changes.', 'lift-docs-system')
        ));
        
        // Force load media scripts
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
        
        // Debug info
        error_log('LIFT Docs: Admin scripts enqueued on hook: ' . $hook);
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'lift-docs-system',
            __('LIFT Docs Settings', 'lift-docs-system'),
            __('Settings', 'lift-docs-system'),
            'manage_options',
            'lift-docs-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Initialize settings with tab-based organization
     */
    public function init_settings() {
        register_setting(
            'lift_docs_settings_group',
            'lift_docs_settings',
            array($this, 'validate_settings')
        );
        
        // Register interface settings with the main group
        register_setting('lift_docs_settings_group', 'lift_docs_logo_upload');
        register_setting('lift_docs_settings_group', 'lift_docs_custom_logo_width'); 
        register_setting('lift_docs_settings_group', 'lift_docs_login_title');
        register_setting('lift_docs_settings_group', 'lift_docs_login_description');
        
        // Register login customization settings with validation
        register_setting('lift_docs_settings_group', 'lift_docs_login_logo');
        register_setting('lift_docs_settings_group', 'lift_docs_login_bg_color', array(
            'sanitize_callback' => array($this, 'validate_bg_color')
        ));
        register_setting('lift_docs_settings_group', 'lift_docs_login_form_bg', array(
            'sanitize_callback' => array($this, 'validate_form_bg_color')
        ));
        register_setting('lift_docs_settings_group', 'lift_docs_login_btn_color', array(
            'sanitize_callback' => array($this, 'validate_btn_color')
        ));
        register_setting('lift_docs_settings_group', 'lift_docs_login_input_color', array(
            'sanitize_callback' => array($this, 'validate_input_color')
        ));
        register_setting('lift_docs_settings_group', 'lift_docs_login_text_color', array(
            'sanitize_callback' => array($this, 'validate_text_color')
        ));
        
        // General Tab Settings
        add_settings_section(
            'lift_docs_general_section',
            __('General Settings', 'lift-docs-system'),
            array($this, 'general_section_callback'),
            'lift-docs-general'
        );
        
        add_settings_field(
            'show_dashboard_stats',
            __('Show Dashboard Stats', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-general',
            'lift_docs_general_section',
            array('field' => 'show_dashboard_stats', 'description' => __('Display statistics section in Document Dashboard (Documents count, Downloads, Views, etc.)', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_document_stats',
            __('Show Document Stats', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-general',
            'lift_docs_general_section',
            array('field' => 'show_document_stats', 'description' => __('Display view and download statistics for each document in Document Dashboard', 'lift-docs-system'))
        );

        
        // Security Tab Settings
        add_settings_section(
            'lift_docs_security_section',
            __('Security & Access Control', 'lift-docs-system'),
            array($this, 'security_section_callback'),
            'lift-docs-security'
        );
        
        add_settings_field(
            'require_login_to_view',
            __('Require Login to View', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-security',
            'lift_docs_security_section',
            array('field' => 'require_login_to_view', 'description' => __('Users must be logged in to view documents', 'lift-docs-system'))
        );
        
        add_settings_field(
            'require_login_to_download',
            __('Require Login to Download', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-security',
            'lift_docs_security_section',
            array('field' => 'require_login_to_download', 'description' => __('Users must be logged in to download documents', 'lift-docs-system'))
        );
        
        // Note: Encryption key removed - now using permanent hash-based tokens
        
        // Display Tab Settings  
        add_settings_section(
            'lift_docs_display_section',
            __('Display & Layout Options', 'lift-docs-system'),
            array($this, 'display_section_callback'),
            'lift-docs-display'
        );
        

        add_settings_field(
            'show_document_header',
            __('Show Document Header', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_document_header', 'description' => __('Display document header with title and meta info', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_document_description',
            __('Show Document Description', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_document_description', 'description' => __('Display document description/excerpt', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_document_meta',
            __('Show Document Meta', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_document_meta', 'description' => __('Display document metadata (date, author, etc.)', 'lift-docs-system'))
        );
        
        add_settings_field(
            'show_download_button',
            __('Show Download Button', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_download_button', 'description' => __('Display download button for documents', 'lift-docs-system'))
        );
        

        add_settings_field(
            'show_secure_access_notice',
            __('Show Secure Access Notice', 'lift-docs-system'),
            array($this, 'checkbox_field_callback'),
            'lift-docs-display',
            'lift_docs_display_section',
            array('field' => 'show_secure_access_notice', 'description' => __('Display notice when accessing via secure link', 'lift-docs-system'))
        );
        
        // Login Page Customization Settings
        add_settings_section(
            'lift_docs_login_section',
            __('Login Page Customization', 'lift-docs-system'),
            array($this, 'login_section_callback'),
            'lift-docs-settings'
        );
        
        add_settings_field(
            'lift_docs_login_logo',
            __('Login Page Logo', 'lift-docs-system'),
            array($this, 'login_logo_callback'),
            'lift-docs-settings',
            'lift_docs_login_section'
        );
        
        add_settings_field(
            'lift_docs_login_bg_color',
            __('Background Color', 'lift-docs-system'),
            array($this, 'login_bg_color_callback'),
            'lift-docs-settings',
            'lift_docs_login_section'
        );
        
        add_settings_field(
            'lift_docs_login_form_bg',
            __('Form Background Color', 'lift-docs-system'),
            array($this, 'login_form_bg_callback'),
            'lift-docs-settings',
            'lift_docs_login_section'
        );
        
        add_settings_field(
            'lift_docs_login_btn_color',
            __('Button Color', 'lift-docs-system'),
            array($this, 'login_btn_color_callback'),
            'lift-docs-settings',
            'lift_docs_login_section'
        );
        
        add_settings_field(
            'lift_docs_login_input_color',
            __('Input Border Color', 'lift-docs-system'),
            array($this, 'login_input_color_callback'),
            'lift-docs-settings',
            'lift_docs_login_section'
        );
        
        add_settings_field(
            'lift_docs_login_text_color',
            __('Text Color', 'lift-docs-system'),
            array($this, 'login_text_color_callback'),
            'lift-docs-settings',
            'lift_docs_login_section'
        );
        
        // Interface Tab Settings (move login customization here)
        add_settings_section(
            'lift_docs_interface_section',
            __('Login Page Appearance', 'lift-docs-system'),
            array($this, 'interface_section_callback'),
            'lift-docs-interface'
        );
        
        add_settings_field(
            'lift_docs_login_logo',
            __('Login Page Logo', 'lift-docs-system'),
            array($this, 'login_logo_callback'),
            'lift-docs-interface',
            'lift_docs_interface_section'
        );
        
        add_settings_field(
            'lift_docs_login_bg_color',
            __('Background Color', 'lift-docs-system'),
            array($this, 'login_bg_color_callback'),
            'lift-docs-interface',
            'lift_docs_interface_section'
        );
        
        add_settings_field(
            'lift_docs_login_form_bg',
            __('Form Background Color', 'lift-docs-system'),
            array($this, 'login_form_bg_callback'),
            'lift-docs-interface',
            'lift_docs_interface_section'
        );
        
        add_settings_field(
            'lift_docs_login_btn_color',
            __('Button Color', 'lift-docs-system'),
            array($this, 'login_btn_color_callback'),
            'lift-docs-interface',
            'lift_docs_interface_section'
        );
        
        add_settings_field(
            'lift_docs_login_input_color',
            __('Input Border Color', 'lift-docs-system'),
            array($this, 'login_input_color_callback'),
            'lift-docs-interface',
            'lift_docs_interface_section'
        );
        
        add_settings_field(
            'lift_docs_login_text_color',
            __('Text Color', 'lift-docs-system'),
            array($this, 'login_text_color_callback'),
            'lift-docs-interface',
            'lift_docs_interface_section'
        );
    }
    
    /**
     * Settings page callback with JavaScript-based tabs
     */
    public function settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Ensure valid tab
        $valid_tabs = array('general', 'security', 'display', 'interface', 'help');
        if (!in_array($active_tab, $valid_tabs)) {
            $active_tab = 'general';
        }
        ?>
        <div class="lift-docs-settings-wrapper">
            <div class="lift-docs-settings-container">
                
                <!-- Enhanced Header -->
                <div class="lift-settings-header">
                    <h1><i class="fas fa-cogs"></i> <?php _e('LIFT Docs System Settings', 'lift-docs-system'); ?></h1>
                    <p><?php _e('Configure your document management system to match your needs and branding', 'lift-docs-system'); ?></p>
                </div>
                
                <!-- Enhanced Navigation Tabs -->
                <div class="lift-nav-tab-wrapper">
                    <a href="#general" class="lift-nav-tab nav-tab-js <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>" data-tab="general">
                        <i class="fas fa-sliders-h"></i>
                        <?php _e('General', 'lift-docs-system'); ?>
                    </a>
                    <a href="#security" class="lift-nav-tab nav-tab-js <?php echo $active_tab == 'security' ? 'nav-tab-active' : ''; ?>" data-tab="security">
                        <i class="fas fa-shield-alt"></i>
                        <?php _e('Security', 'lift-docs-system'); ?>
                    </a>
                    <a href="#display" class="lift-nav-tab nav-tab-js <?php echo $active_tab == 'display' ? 'nav-tab-active' : ''; ?>" data-tab="display">
                        <i class="fas fa-paint-brush"></i>
                        <?php _e('Display', 'lift-docs-system'); ?>
                    </a>
                    <a href="#interface" class="lift-nav-tab nav-tab-js <?php echo $active_tab == 'interface' ? 'nav-tab-active' : ''; ?>" data-tab="interface">
                        <i class="fas fa-palette"></i>
                        <?php _e('Interface', 'lift-docs-system'); ?>
                    </a>
                    <a href="#help" class="lift-nav-tab nav-tab-js <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>" data-tab="help">
                        <i class="fas fa-question-circle"></i>
                        <?php _e('Help', 'lift-docs-system'); ?>
                    </a>
                </div>
                
                <form method="post" action="options.php">
                    <?php settings_fields('lift_docs_settings_group'); ?>
                    
                    <!-- General Tab Content -->
                    <div id="general-tab" class="lift-tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
                        <?php do_settings_sections('lift-docs-general'); ?>
                        
                        <!-- Reset Settings Section -->
                        <div class="lift-reset-section">
                            <h3>
                                <i class="fas fa-undo"></i> <?php _e('Reset All Settings', 'lift-docs-system'); ?>
                            </h3>
                            <p>
                                <?php _e('Reset all plugin settings back to their default values. All checkboxes will be enabled and customizations will be lost.', 'lift-docs-system'); ?>
                            </p>
                            <button type="button" id="reset-settings-btn" class="lift-button lift-button-secondary">
                                <i class="fas fa-undo"></i> <?php _e('Reset All Settings to Default', 'lift-docs-system'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Security Tab Content -->
                    <div id="security-tab" class="lift-tab-content <?php echo $active_tab == 'security' ? 'active' : ''; ?>">
                        <?php do_settings_sections('lift-docs-security'); ?>
                    </div>
                    
                    <!-- Display Tab Content -->
                    <div id="display-tab" class="lift-tab-content <?php echo $active_tab == 'display' ? 'active' : ''; ?>">
                        
                        <?php do_settings_sections('lift-docs-display'); ?>
                    </div>
                    
                    <!-- Interface Tab Content -->
                    <div id="interface-tab" class="lift-tab-content <?php echo $active_tab == 'interface' ? 'active' : ''; ?>">
                        
                        <?php do_settings_sections('lift-docs-interface'); ?>
                    </div>
                    
                    <!-- Help Tab Content -->
                    <div id="help-tab" class="lift-tab-content <?php echo $active_tab == 'help' ? 'active' : ''; ?>">
                       
                        <?php $this->display_help_content(); ?>
                    </div>
                    
                    <div class="lift-settings-footer">
                        <?php submit_button(__('Save Changes', 'lift-docs-system'), 'primary', 'submit', false, array('class' => 'lift-button lift-button-primary')); ?>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Get current tab from URL or default to general
            var urlParams = new URLSearchParams(window.location.search);
            var currentTab = urlParams.get('tab') || 'general';
            
            
            // Function to switch to a specific tab with enhanced animations
            function switchToTab(tabName) {
                
                // Remove active class from all tabs and content
                $('.lift-nav-tab').removeClass('nav-tab-active');
                $('.lift-tab-content').removeClass('active').fadeOut(200);
                
                // Add loading state
                $('.lift-nav-tab[data-tab="' + tabName + '"]').addClass('loading');
                
                // Add active class to target tab
                setTimeout(function() {
                    $('.lift-nav-tab[data-tab="' + tabName + '"]').addClass('nav-tab-active').removeClass('loading');
                    
                    // Show target content with fade animation
                    var $targetContent = $('#' + tabName + '-tab');
                    $targetContent.fadeIn(300).addClass('active');
                }, 100);
            }
            
            // Handle tab switching with enhanced UX
            $('.nav-tab-js').on('click', function(e) {
                e.preventDefault();
                
                var $clickedTab = $(this);
                var targetTab = $clickedTab.data('tab');
                
                // Don't switch if already active
                if ($clickedTab.hasClass('nav-tab-active')) {
                    return;
                }
                
                switchToTab(targetTab);
                
                // Update URL without page reload
                var newUrl = window.location.origin + window.location.pathname + '?post_type=lift_document&page=lift-docs-settings&tab=' + targetTab;
                window.history.pushState({tab: targetTab}, '', newUrl);
            });
            
            // Handle browser back/forward
            $(window).on('popstate', function(event) {
                if (event.originalEvent.state && event.originalEvent.state.tab) {
                    switchToTab(event.originalEvent.state.tab);
                } else {
                    // Fallback to URL parameter
                    var urlParams = new URLSearchParams(window.location.search);
                    var tab = urlParams.get('tab') || 'general';
                    switchToTab(tab);
                }
            });
            
            // Initialize - hide all tabs first, then show the correct one
            $('.lift-tab-content').hide().removeClass('active');
            $('.lift-nav-tab').removeClass('nav-tab-active');
            
            // Switch to the current tab (from URL or default)
            switchToTab(currentTab);
            
            // Add smooth hover effects
            $('.lift-nav-tab').hover(
                function() {
                    if (!$(this).hasClass('nav-tab-active')) {
                        $(this).stop().animate({opacity: 0.8}, 200);
                    }
                },
                function() {
                    if (!$(this).hasClass('nav-tab-active')) {
                        $(this).stop().animate({opacity: 1}, 200);
                    }
                }
            );
            
            // Enhanced form control interactions
            $('.lift-form-control').on('focus', function() {
                $(this).closest('.lift-form-table td').addClass('focused');
            }).on('blur', function() {
                $(this).closest('.lift-form-table td').removeClass('focused');
            });
            
            // Enhanced checkbox interactions
            $('.lift-checkbox').on('change', function() {
                var $wrapper = $(this).closest('.lift-checkbox-wrapper');
                if ($(this).is(':checked')) {
                    $wrapper.addClass('checked');
                } else {
                    $wrapper.removeClass('checked');
                }
            });
            
            // Initialize checkbox states
            $('.lift-checkbox:checked').each(function() {
                $(this).closest('.lift-checkbox-wrapper').addClass('checked');
            });
            
            // Reset settings functionality
            $('#reset-settings-btn').on('click', function(e) {
                e.preventDefault();
                
                if (confirm('<?php _e('Are you sure you want to reset all settings to default? This will enable all checkboxes and cannot be undone.', 'lift-docs-system'); ?>')) {
                    var $resetBtn = $(this);
                    var originalText = $resetBtn.html();
                    
                    // Show loading state
                    $resetBtn.html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Resetting...', 'lift-docs-system'); ?>');
                    $resetBtn.prop('disabled', true);
                    
                    // Make AJAX call to reset settings
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lift_docs_reset_settings',
                            nonce: '<?php echo wp_create_nonce('lift_docs_reset_settings'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Check all checkboxes (default state)
                                $('.lift-checkbox').prop('checked', true);
                                
                                // Update visual states
                                $('.lift-checkbox-wrapper').addClass('checked');
                                
                                // Show success message
                                $resetBtn.html('<i class="fas fa-check"></i> <?php _e('Settings Reset! Please Save Changes.', 'lift-docs-system'); ?>');
                                $resetBtn.addClass('lift-button-success').removeClass('lift-button-secondary');
                                
                                // Highlight save button
                                $('.lift-button-primary').addClass('lift-button-highlight');
                                
                                // Show success notice
                                if ($('.notice-success').length === 0) {
                                    $('.lift-docs-settings-container').prepend('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                                }
                            } else {
                                alert('<?php _e('Error resetting settings. Please try again.', 'lift-docs-system'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Error resetting settings. Please try again.', 'lift-docs-system'); ?>');
                        },
                        complete: function() {
                            // Reset button state
                            $resetBtn.prop('disabled', false);
                            
                            // Reset button text after 3 seconds
                            setTimeout(function() {
                                $resetBtn.html(originalText);
                                $resetBtn.removeClass('lift-button-success').addClass('lift-button-secondary');
                                $('.lift-button-primary').removeClass('lift-button-highlight');
                            }, 3000);
                        }
                    });
                }
            });
            
            // Add form validation feedback
            $('form').on('submit', function() {
                $('.lift-button-primary').addClass('lift-loading').prop('disabled', true);
                
                // Re-enable after 3 seconds as fallback
                setTimeout(function() {
                    $('.lift-button-primary').removeClass('lift-loading').prop('disabled', false);
                }, 3000);
            });
            
        });
        </script>
        <?php
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for the LIFT Docs System.', 'lift-docs-system') . '</p>';
        echo '<p><em>' . __('For shortcode information and usage examples, please see the Help tab.', 'lift-docs-system') . '</em></p>';
    }
    
    /**
     * Security section callback
     */
    public function security_section_callback() {
        echo '<p>' . __('Configure security and access control settings for document viewing and downloading.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Display section callback
     */
    public function display_section_callback() {
        echo '<p>' . __('Control how documents are displayed on the frontend. These settings affect the user experience when viewing documents.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Interface section callback
     */
    public function interface_section_callback() {
        echo '<p>' . __('Customize the appearance and branding of your document login page and related interfaces.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Help content display
     */
    public function display_help_content() {
        $login_page_id = get_option('lift_docs_login_page_id');
        $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
        ?>
        <div class="lift-docs-help-content">
            
            <!-- Shortcode Information -->
            <div style="background: #e3f2fd;  padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h3 style="color: #1976d2; margin-top: 0;">Frontend Login & Dashboard Shortcodes</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div>
                        <h4 style="color: #1976d2; margin-bottom: 8px;">Login Form Shortcode:</h4>
                        <code style="background: #fff; padding: 8px; border-radius: 3px; display: block; font-family: monospace;">[docs_login_form]</code>
                        
                        <p style="margin: 8px 0 0; font-size: 12px; color: #555;">
                            <strong>Parameters:</strong><br>
                            • <code>title</code> - Custom title<br>
                            • <code>redirect_to</code> - Custom redirect URL<br>
                            • <code>show_features</code> - Show features list (true/false)
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="color: #1976d2; margin-bottom: 8px;">Dashboard Shortcode:</h4>
                        <code style="background: #fff; padding: 8px; border-radius: 3px; display: block; font-family: monospace;">[docs_dashboard]</code>
                        
                        <p style="margin: 8px 0 0; font-size: 12px; color: #555;">
                            <strong>Parameters:</strong><br>
                            • <code>show_stats</code> - Show statistics (true/false)<br>
                            • <code>show_search</code> - Show search box (true/false)<br>
                            • <code>show_activity</code> - Show activity (true/false)<br>
                            • <code>documents_per_page</code> - Documents per page (number)
                        </p>
                    </div>
                </div>
                
                <?php if ($login_page_id || $dashboard_page_id): ?>
                <div style="background: rgba(255, 255, 255, 0.7); padding: 12px; border-radius: 3px; margin-top: 15px;">
                    <h4 style="color: #1976d2; margin-top: 0;">📄 Auto-created Pages:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php if ($login_page_id && get_post($login_page_id)): ?>
                        <li>
                            <strong>Login Page:</strong> 
                            <a href="<?php echo get_permalink($login_page_id); ?>" target="_blank">
                                <?php echo get_the_title($login_page_id); ?>
                            </a>
                            <small>(<a href="<?php echo get_edit_post_link($login_page_id); ?>" target="_blank">Edit</a>)</small>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($dashboard_page_id && get_post($dashboard_page_id)): ?>
                        <li>
                            <strong>Dashboard Page:</strong> 
                            <a href="<?php echo get_permalink($dashboard_page_id); ?>" target="_blank">
                                <?php echo get_the_title($dashboard_page_id); ?>
                            </a>
                            <small>(<a href="<?php echo get_edit_post_link($dashboard_page_id); ?>" target="_blank">Edit</a>)</small>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
               
            </div>
            
            <!-- Interface Customization Info -->
            <div style="background: #f9f9f9; padding: 20px; border-radius: 6px;  margin-bottom: 20px;">
                <h3 style="margin-top: 0; color: #1976d2;">Interface Customization Guide</h3>
                <p>The Interface tab allows you to customize the appearance and branding of your document login page. These settings control how the login page looks to your users.</p>
                <p><strong>Applies to:</strong> /document-login/, /document-dashboard/, secure document pages, and access denied pages.</p>
                
                <h4 style="color: #1976d2;">Available Customizations:</h4>
                <ul>
                    <li><strong>Logo Upload:</strong> Upload your organization's logo</li>
                    <li><strong>Logo Width:</strong> Control logo size (50-500px)</li>
                    <li><strong>Page Title:</strong> Custom title for login page</li>
                    <li><strong>Page Description:</strong> Welcome message or instructions</li>
                    <li><strong>Color Scheme:</strong> Background, form, button, input, and text colors</li>
                    <li><strong>Alpha Transparency:</strong> All colors support transparency</li>
                </ul>
                
                <p><strong>Tip:</strong> Use the Interface tab to match your brand colors and create a professional login experience for your users.</p>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Login logo callback
     */
    public function login_logo_callback() {
        $logo_id = get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        
        echo '<div class="lift-logo-upload-container">';
        echo '<input type="hidden" name="lift_docs_login_logo" id="lift_docs_login_logo" value="' . esc_attr($logo_id) . '">';
        
        echo '<div class="logo-preview" style="margin-bottom: 10px;">';
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;" id="logo-preview-img">';
        } else {
            echo '<div id="logo-preview-img" style="width: 200px; height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;">' . __('No logo selected', 'lift-docs-system') . '</div>';
        }
        echo '</div>';
        
        echo '<button type="button" class="button" id="upload-logo-btn">' . __('Select Logo', 'lift-docs-system') . '</button>';
        if ($logo_url) {
            echo ' <button type="button" class="button" id="remove-logo-btn">' . __('Remove Logo', 'lift-docs-system') . '</button>';
        } else {
            echo ' <button type="button" class="button" id="remove-logo-btn" style="display: none;">' . __('Remove Logo', 'lift-docs-system') . '</button>';
        }
        echo '</div>';
        
        // Add JavaScript for media uploader - NO ANIMATIONS
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            
            var mediaUploader;
            var interfaceMediaUploader;
            
            // Remove all animations and transitions globally
            $('*').css({
                'transition': 'none !important',
                'animation': 'none !important',
                '-webkit-transition': 'none !important',
                '-webkit-animation': 'none !important'
            });
            
            // Color picker with alpha support
            if ($.fn.wpColorPicker) {
                $('.color-picker-alpha').wpColorPicker({
                    change: function(event, ui) {
                        // No animation on color change
                    },
                    // Enable alpha transparency
                    alpha: true,
                    // Set default alpha to 1 (fully opaque)
                    defaultColor: false,
                    // Hide the color picker on outside click
                    hide: true,
                    // Custom palettes for common transparent colors
                    palettes: [
                        'rgba(255,255,255,0)',   // Transparent white
                        'rgba(0,0,0,0)',         // Transparent black
                        'rgba(255,255,255,0.1)', // 10% white
                        'rgba(255,255,255,0.3)', // 30% white
                        'rgba(255,255,255,0.5)', // 50% white
                        'rgba(255,255,255,0.7)', // 70% white
                        'rgba(255,255,255,0.9)', // 90% white
                        'rgba(0,0,0,0.1)',       // 10% black
                        'rgba(0,0,0,0.3)',       // 30% black
                        'rgba(0,0,0,0.5)',       // 50% black
                        'rgba(0,0,0,0.7)',       // 70% black
                        'rgba(0,0,0,0.9)'        // 90% black
                    ]
                });
                
                // Also initialize regular color pickers for backwards compatibility
                $('.color-picker').wpColorPicker({
                    change: function(event, ui) {
                        // No animation on color change
                    }
                });
            }
            
            // Logo upload - Main settings
            $(document).on('click', '#upload-logo-btn', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: '<?php _e('Choose Logo', 'lift-docs-system'); ?>',
                    button: { text: '<?php _e('Choose Logo', 'lift-docs-system'); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#lift_docs_login_logo').val(attachment.id);
                    $('#logo-preview-img').html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;">');
                    $('#remove-logo-btn').show();
                });
                
                mediaUploader.open();
            });
            
            // Logo remove - Main settings
            $(document).on('click', '#remove-logo-btn', function(e) {
                e.preventDefault();
                $('#lift_docs_login_logo').val('');
                $('#logo-preview-img').html('<div style="width: 200px; height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;"><?php _e('No logo selected', 'lift-docs-system'); ?></div>');
                $(this).hide();
            });
            
            // Interface tab media uploader
            $(document).on('click', '#interface-upload-logo-btn', function(e) {
                e.preventDefault();
                
                if (interfaceMediaUploader) {
                    interfaceMediaUploader.open();
                    return;
                }
                
                interfaceMediaUploader = wp.media.frames.interface_file_frame = wp.media({
                    title: '<?php _e('Choose Logo for Interface', 'lift-docs-system'); ?>',
                    button: { text: '<?php _e('Use This Image', 'lift-docs-system'); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                interfaceMediaUploader.on('select', function() {
                    var attachment = interfaceMediaUploader.state().get('selection').first().toJSON();
                    $('#lift_docs_logo_upload').val(attachment.id);
                    $('#interface-logo-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; max-height: 150px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">');
                    $('#interface-remove-logo-btn').show();
                });
                
                interfaceMediaUploader.open();
            });
            
            // Interface logo remove
            $(document).on('click', '#interface-remove-logo-btn', function(e) {
                e.preventDefault();
                $('#lift_docs_logo_upload').val('');
                $('#interface-logo-preview').html('<div style="width: 300px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999; border-radius: 4px; background: #f9f9f9;"><span>📷 <?php _e('No logo uploaded', 'lift-docs-system'); ?></span></div>');
                $(this).hide();
            });
            
            // Remove animation from all form elements
            $('input, textarea, select, button').css({
                'transition': 'none',
                'animation': 'none'
            });
            
            // Remove hover animations
            $('*').off('mouseenter mouseleave');
        });
        </script>
        <?php
    }
    
    /**
     * Login background color callback
     */
    public function login_bg_color_callback() {
        $color = get_option('lift_docs_login_bg_color', '');
        echo '<div class="color-field-wrapper">';
        echo '<input type="text" name="lift_docs_login_bg_color" value="' . esc_attr($color) . '" class="color-picker-alpha" data-alpha="true" data-default-color="">';
        
        echo '</div>';
        echo '<p class="description">' . __('Background color for the login page (supports transparency). Leave empty for no background color.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Login form background callback
     */
    public function login_form_bg_callback() {
        $color = get_option('lift_docs_login_form_bg', '');
        echo '<div class="color-field-wrapper">';
        echo '<input type="text" name="lift_docs_login_form_bg" value="' . esc_attr($color) . '" class="color-picker-alpha" data-alpha="true" data-default-color="">';
        
        echo '</div>';
        echo '<p class="description">' . __('Background color for the login form (supports transparency). Leave empty for no background color.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Login button color callback
     */
    public function login_btn_color_callback() {
        $color = get_option('lift_docs_login_btn_color', '');
        echo '<div class="color-field-wrapper">';
        echo '<input type="text" name="lift_docs_login_btn_color" value="' . esc_attr($color) . '" class="color-picker-alpha" data-alpha="true" data-default-color="">';
        
        echo '</div>';
        echo '<p class="description">' . __('Primary button color (supports transparency). Leave empty for default theme color.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Login input color callback
     */
    public function login_input_color_callback() {
        $color = get_option('lift_docs_login_input_color', '');
        echo '<div class="color-field-wrapper">';
        echo '<input type="text" name="lift_docs_login_input_color" value="' . esc_attr($color) . '" class="color-picker-alpha" data-alpha="true" data-default-color="">';
        
        echo '</div>';
        echo '<p class="description">' . __('Border color for input fields (supports transparency). Leave empty for default color.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Login text color callback
     */
    public function login_text_color_callback() {
        $color = get_option('lift_docs_login_text_color', '');
        echo '<div class="color-field-wrapper">';
        echo '<input type="text" name="lift_docs_login_text_color" value="' . esc_attr($color) . '" class="color-picker-alpha" data-alpha="true" data-default-color="">';
        
        echo '</div>';
        echo '<p class="description">' . __('Main text color (supports transparency). Leave empty for default text color.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Logo upload callback for Interface tab
     */
    public function logo_upload_callback() {
        $logo_id = get_option('lift_docs_logo_upload', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        
        echo '<div class="lift-interface-logo-container">';
        echo '<input type="hidden" name="lift_docs_logo_upload" id="lift_docs_logo_upload" value="' . esc_attr($logo_id) . '">';
        
        echo '<div class="logo-preview" style="margin-bottom: 15px;">';
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" style="max-width: 300px; max-height: 150px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;" id="interface-logo-preview">';
        } else {
            echo '<div id="interface-logo-preview" style="width: 300px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999; border-radius: 4px; background: #f9f9f9;">';
            echo '<span>📷 ' . __('No logo uploaded', 'lift-docs-system') . '</span>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<button type="button" class="button button-secondary" id="interface-upload-logo-btn">📤 ' . __('Upload Logo', 'lift-docs-system') . '</button>';
        if ($logo_url) {
            echo ' <button type="button" class="button button-link-delete" id="interface-remove-logo-btn">🗑️ ' . __('Remove', 'lift-docs-system') . '</button>';
        } else {
            echo ' <button type="button" class="button button-link-delete" id="interface-remove-logo-btn" style="display: none;">🗑️ ' . __('Remove', 'lift-docs-system') . '</button>';
        }
        echo '<p class="description">' . __('Upload a logo image to display on the login page. Recommended size: 300x150px or smaller.', 'lift-docs-system') . '</p>';
        echo '</div>';
    }
    
    /**
     * Custom logo width callback
     */
    public function custom_logo_width_callback() {
        $width = get_option('lift_docs_custom_logo_width', '200');
        echo '<input type="number" name="lift_docs_custom_logo_width" value="' . esc_attr($width) . '" min="50" max="500" style="width: 100px;">';
        echo ' <span>px</span>';
        echo '<p class="description">' . __('Maximum width for the logo display (50-500px). Height will be automatically adjusted.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Login title callback
     */
    public function login_title_callback() {
        $title = get_option('lift_docs_login_title', '');
        echo '<input type="text" name="lift_docs_login_title" value="' . esc_attr($title) . '" style="width: 100%;" placeholder="' . __('Document Access Portal', 'lift-docs-system') . '">';
        echo '<p class="description">' . __('Custom title to display on the login page. Leave empty to use default.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Login description callback
     */
    public function login_description_callback() {
        $description = get_option('lift_docs_login_description', '');
        echo '<textarea name="lift_docs_login_description" rows="3" style="width: 100%;" placeholder="' . __('Please log in to access your documents.', 'lift-docs-system') . '">' . esc_textarea($description) . '</textarea>';
        echo '<p class="description">' . __('Custom description text to display below the title on the login page.', 'lift-docs-system') . '</p>';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $checked = isset($settings[$field]) && $settings[$field] ? 'checked' : '';
        
        echo '<div class="lift-checkbox-wrapper">';
        echo '<input type="checkbox" name="lift_docs_settings[' . $field . ']" value="1" ' . $checked . ' class="lift-checkbox" id="' . $field . '" />';
        echo '<label for="' . $field . '" class="lift-checkbox-label">' . $description . '</label>';
        echo '</div>';
    }
    
    /**
     * Number field callback
     */
    public function number_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        $min = $args['min'] ?? '';
        $max = $args['max'] ?? '';
        
        echo '<input type="number" name="lift_docs_settings[' . $field . ']" value="' . esc_attr($value) . '"';
        if ($min !== '') echo ' min="' . $min . '"';
        if ($max !== '') echo ' max="' . $max . '"';
        echo ' class="lift-form-control small" id="' . $field . '" />';
        
        if ($description) {
            echo '<p class="lift-description">' . $description . '</p>';
        }
    }
    
    /**
     * Text field callback
     */
    public function text_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        echo '<input type="text" name="lift_docs_settings[' . $field . ']" value="' . esc_attr($value) . '" class="lift-form-control" id="' . $field . '" />';
        
        if ($description) {
            echo '<p class="lift-description">' . $description . '</p>';
        }
    }
    
    /**
     * Select field callback
     */
    public function select_field_callback($args) {
        $settings = get_option('lift_docs_settings', array());
        $field = $args['field'];
        $description = $args['description'] ?? '';
        $options = $args['options'] ?? array();
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        echo '<select name="lift_docs_settings[' . $field . ']" class="lift-form-control lift-form-select" id="' . $field . '">';
        foreach ($options as $option_value => $option_label) {
            $selected = selected($value, $option_value, false);
            echo '<option value="' . esc_attr($option_value) . '"' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        
        if (!empty($description)) {
            echo '<p class="lift-description">' . $description . '</p>';
        }
    }
    
    /**
     * Encryption key field callback
     */
    /**
     * Note: Encryption key field removed - now using permanent hash-based tokens
     */
    
    /**
     * Validate settings
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Boolean fields - only keep essential ones
        $boolean_fields = array(
            'require_login_to_view',
            'require_login_to_download',
            'show_document_header',
            'show_document_description',
            'show_document_meta',
            'show_download_button',
            'show_secure_access_notice',
            'show_dashboard_stats',
            'show_document_stats'
        );
        
        foreach ($boolean_fields as $field) {
            $validated[$field] = isset($input[$field]) && $input[$field] ? true : false;
        }
        
        // Force secure links to always be enabled
        $validated['enable_secure_links'] = true;
        
        // Set default secure link expiry to 24 hours
        $validated['secure_link_expiry'] = 24;
        
        return $validated;
    }
    
    /**
     * AJAX handler for resetting all settings to default
     */
    public function ajax_reset_settings() {
        // Check nonce for security
        if (!check_ajax_referer('lift_docs_reset_settings', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Reset all boolean settings to true (default enabled state)
        $default_settings = array(
            'require_login_to_view' => true,
            'require_login_to_download' => true,
            'show_document_header' => true,
            'show_document_description' => true,
            'show_document_meta' => true,
            'show_download_button' => true,
            'show_secure_access_notice' => true,
            'show_dashboard_stats' => true,
            'show_document_stats' => true,
            'enable_secure_links' => true,
            'secure_link_expiry' => 24
        );
        
        // Update the settings
        update_option('lift_docs_settings', $default_settings);
        
        // Return success response
        wp_send_json_success(array(
            'message' => __('All settings have been reset to default values.', 'lift-docs-system')
        ));
    }
    
    /**
     * Validate and sanitize color values (supports both hex and rgba, allows empty values)
     */
    private function validate_color($color, $default = '', $allow_empty = true) {
        // Allow empty values when explicitly allowed
        if (empty($color) && $allow_empty) {
            return '';
        }
        
        // Use default if empty and not allowing empty
        if (empty($color)) {
            return $default;
        }
        
        $color = trim($color);
        
        // Check for hex color (with or without #)
        if (preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color)) {
            return '#' . ltrim($color, '#');
        }
        
        // Check for rgba color
        if (preg_match('/^rgba\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([01]?(?:\.\d+)?)\s*\)$/', $color, $matches)) {
            $r = intval($matches[1]);
            $g = intval($matches[2]);
            $b = intval($matches[3]);
            $a = floatval($matches[4]);
            
            // Validate RGB values (0-255) and alpha (0-1)
            if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255 && $a >= 0 && $a <= 1) {
                return "rgba($r, $g, $b, $a)";
            }
        }
        
        // Check for rgb color (convert to rgba with alpha 1)
        if (preg_match('/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $color, $matches)) {
            $r = intval($matches[1]);
            $g = intval($matches[2]);
            $b = intval($matches[3]);
            
            if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255) {
                return "rgba($r, $g, $b, 1)";
            }
        }
        
        // If validation fails, return default or empty based on allow_empty setting
        return $allow_empty ? '' : $default;
    }
    
    /**
     * Validate background color - allows empty values
     */
    public function validate_bg_color($input) {
        return $this->validate_color($input, '', true);
    }
    
    /**
     * Validate form background color - allows empty values
     */
    public function validate_form_bg_color($input) {
        return $this->validate_color($input, '', true);
    }
    
    /**
     * Validate button color - allows empty values
     */
    public function validate_btn_color($input) {
        return $this->validate_color($input, '', true);
    }
    
    /**
     * Validate input color - allows empty values
     */
    public function validate_input_color($input) {
        return $this->validate_color($input, '', true);
    }
    
    /**
     * Validate text color - allows empty values
     */
    public function validate_text_color($input) {
        return $this->validate_color($input, '', true);
    }
    
    /**
     * Get setting value
     */
    public static function get_setting($key, $default = null) {
        // Force secure links to always be enabled
        if ($key === 'enable_secure_links') {
            return true;
        }
        
        // Set default secure link expiry to 24 hours
        if ($key === 'secure_link_expiry') {
            return 24;
        }
        
        // Define all boolean settings that should default to true (enabled)
        $boolean_settings_default_true = array(
            'require_login_to_view',
            'require_login_to_download',
            'show_document_header',
            'show_document_description',
            'show_document_meta',
            'show_download_button',
            'show_secure_access_notice',
            'show_dashboard_stats',
            'show_document_stats'
        );
        
        // Check if this is a boolean setting that should default to true
        if (in_array($key, $boolean_settings_default_true)) {
            $settings = get_option('lift_docs_settings', array());
            return isset($settings[$key]) ? $settings[$key] : true;
        }
        
        $settings = get_option('lift_docs_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Note: Encryption methods removed - now using permanent hash-based tokens
     */
    
    /**
     * Generate permanent secure Attached files for document
     * Uses document ID + salt + creation time to create unique, permanent URL
     */
    public static function generate_secure_link($document_id, $expiry_hours = null) {
        // Get or create permanent token for this document
        $permanent_token = get_post_meta($document_id, '_lift_doc_permanent_token', true);
        
        if (empty($permanent_token)) {
            // Generate a unique, permanent token based on document data
            $post = get_post($document_id);
            if (!$post) {
                return get_permalink($document_id);
            }
            
            // Create a unique hash using document ID, creation time, and WordPress salt
            $data_to_hash = $document_id . '|' . $post->post_date . '|' . wp_salt('secure_auth');
            $permanent_token = hash('sha256', $data_to_hash);
            
            // Store the permanent token
            update_post_meta($document_id, '_lift_doc_permanent_token', $permanent_token);
        }
        
        return home_url('/document-files/secure/?lift_secure=' . $permanent_token);
    }
    
    /**
     * Generate secure download link for document
     */
    /**
     * Generate permanent secure download URL for document file
     */
    public static function generate_secure_download_link($document_id, $expiry_hours = 0, $file_index = 0) {
        // Get or create permanent token for this document
        $permanent_token = get_post_meta($document_id, '_lift_doc_permanent_token', true);
        
        if (empty($permanent_token)) {
            // Use the secure link generation to create permanent token
            self::generate_secure_link($document_id);
            $permanent_token = get_post_meta($document_id, '_lift_doc_permanent_token', true);
        }
        
        // For download links, append file index if multiple files
        $download_token = $permanent_token;
        if ($file_index > 0) {
            $download_token .= '_file_' . $file_index;
        }
        
        return home_url('/document-files/download/?lift_secure=' . $download_token);
    }
    
    /**
     * Verify secure link token (permanent tokens)
     */
    public static function verify_secure_link($token) {
        // Parse file index from token if present
        $file_index = 0;
        $base_token = $token;
        
        if (strpos($token, '_file_') !== false) {
            $parts = explode('_file_', $token);
            $base_token = $parts[0];
            $file_index = isset($parts[1]) ? intval($parts[1]) : 0;
        }
        
        // Find document with this permanent token
        global $wpdb;
        $document_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_lift_doc_permanent_token' 
             AND meta_value = %s 
             LIMIT 1",
            $base_token
        ));
        
        if (!$document_id) {
            return false;
        }
        
        // Verify document still exists and is valid
        $post = get_post($document_id);
        if (!$post || $post->post_type !== 'lift_document') {
            return false;
        }
        
        return array(
            'document_id' => $document_id,
            'file_index' => $file_index,
            'expires' => 0, // Never expires
            'timestamp' => time(),
            'type' => strpos($token, '_file_') !== false ? 'download' : 'view'
        );
    }
    
    /**
     * Check if current user can view documents
     */
    public static function current_user_can_view_documents() {
        if (!is_user_logged_in()) {
            return !self::get_setting('require_login_to_view', false);
        }
        
        return current_user_can('view_lift_documents') || 
               current_user_can('read_lift_document') || 
               current_user_can('edit_lift_documents') ||
               current_user_can('manage_options');
    }
    
    /**
     * Check if current user can download documents
     */
    public static function current_user_can_download_documents() {
        if (!is_user_logged_in()) {
            return !self::get_setting('require_login_to_download', false);
        }
        
        return current_user_can('download_lift_documents') || 
               current_user_can('edit_lift_documents') ||
               current_user_can('manage_options');
    }
    
    /**
     * Check if user can view specific document
     */
    public static function user_can_view_document($document_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Check if document is archived - block access for regular users, allow admin access
        $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            // Admin can still access archived documents
            if (!($user_id && user_can($user_id, 'manage_options'))) {
                return false;
            }
        }
        
        // Administrators and editors always have access
        if ($user_id && (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_lift_documents'))) {
            return true;
        }
        
        // Check if login is required for viewing
        if (!$user_id) {
            return !self::get_setting('require_login_to_view', false);
        }
        
        // Check if user has basic document viewing capability
        if (!user_can($user_id, 'view_lift_documents') && !user_can($user_id, 'read_lift_document')) {
            return false;
        }
        
        // Check document assignment
        return self::user_is_assigned_to_document($document_id, $user_id);
    }
    
    /**
     * Check if user can download specific document
     */
    public static function user_can_download_document($document_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Check if document is archived - block access for regular users, allow admin access
        $is_archived = get_post_meta($document_id, '_lift_doc_archived', true);
        if ($is_archived === '1' || $is_archived === 1) {
            // Admin can still download archived documents
            if (!($user_id && user_can($user_id, 'manage_options'))) {
                return false;
            }
        }
        
        // Administrators and editors always have access
        if ($user_id && (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_lift_documents'))) {
            return true;
        }
        
        // Check if login is required for downloading
        if (!$user_id) {
            return !self::get_setting('require_login_to_download', false);
        }
        
        // Check if user has basic document download capability
        if (!user_can($user_id, 'download_lift_documents')) {
            return false;
        }
        
        // Check document assignment
        return self::user_is_assigned_to_document($document_id, $user_id);
    }
    
    /**
     * Check if user is assigned to specific document
     */
    public static function user_is_assigned_to_document($document_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Get assigned users for this document
        $assigned_users = get_post_meta($document_id, '_lift_doc_assigned_users', true);
        
        // If no specific users are assigned, only admin and editor can access
        if (empty($assigned_users) || !is_array($assigned_users)) {
            return $user_id && (
                user_can($user_id, 'manage_options') ||
                user_can($user_id, 'edit_lift_documents')
            );
        }
        
        // Check if user is specifically assigned to this document
        return in_array($user_id, $assigned_users);
    }
    
    /**
     * Display shortcode information
     */
    private function display_shortcode_info() {
        $login_page_id = get_option('lift_docs_login_page_id');
        $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
        
        ?>
        <div class="lift-docs-shortcode-info" style="background: #e3f2fd;  padding: 15px; margin: 20px 0; border-radius: 4px;">
            <h4 style="color: #1976d2; margin-top: 0;"><?php _e('Frontend Login & Dashboard Shortcodes', 'lift-docs-system'); ?></h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                <div>
                    <h5 style="color: #1976d2; margin-bottom: 8px;"><?php _e('Login Form Shortcode:', 'lift-docs-system'); ?></h5>
                    <code style="background: #fff; padding: 8px; border-radius: 3px; display: block; font-family: monospace;">[docs_login_form]</code>
                    
                    <p style="margin: 8px 0 0; font-size: 12px; color: #555;">
                        <strong><?php _e('Parameters:', 'lift-docs-system'); ?></strong><br>
                        • <code>title</code> - <?php _e('Custom title', 'lift-docs-system'); ?><br>
                        • <code>redirect_to</code> - <?php _e('Custom redirect URL', 'lift-docs-system'); ?><br>
                        • <code>show_features</code> - <?php _e('Show features list (true/false)', 'lift-docs-system'); ?>
                    </p>
                </div>
                
                <div>
                    <h5 style="color: #1976d2; margin-bottom: 8px;"><?php _e('🏠 Dashboard Shortcode:', 'lift-docs-system'); ?></h5>
                    <code style="background: #fff; padding: 8px; border-radius: 3px; display: block; font-family: monospace;">[docs_dashboard]</code>
                    
                    <p style="margin: 8px 0 0; font-size: 12px; color: #555;">
                        <strong><?php _e('Parameters:', 'lift-docs-system'); ?></strong><br>
                        • <code>show_stats</code> - <?php _e('Show statistics (true/false)', 'lift-docs-system'); ?><br>
                        • <code>show_search</code> - <?php _e('Show search box (true/false)', 'lift-docs-system'); ?><br>
                        • <code>show_activity</code> - <?php _e('Show activity (true/false)', 'lift-docs-system'); ?><br>
                        • <code>documents_per_page</code> - <?php _e('Documents per page (number)', 'lift-docs-system'); ?>
                    </p>
                </div>
            </div>
            
            <?php if ($login_page_id || $dashboard_page_id): ?>
            <div style="background: rgba(255, 255, 255, 0.7); padding: 12px; border-radius: 3px; margin-top: 15px;">
                <h5 style="color: #1976d2; margin-top: 0;"><?php _e('📄 Auto-created Pages:', 'lift-docs-system'); ?></h5>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php if ($login_page_id && get_post($login_page_id)): ?>
                    <li>
                        <strong><?php _e('Login Page:', 'lift-docs-system'); ?></strong> 
                        <a href="<?php echo get_permalink($login_page_id); ?>" target="_blank">
                            <?php echo get_the_title($login_page_id); ?>
                        </a>
                        <small>(<a href="<?php echo get_edit_post_link($login_page_id); ?>" target="_blank"><?php _e('Edit', 'lift-docs-system'); ?></a>)</small>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($dashboard_page_id && get_post($dashboard_page_id)): ?>
                    <li>
                        <strong><?php _e('Dashboard Page:', 'lift-docs-system'); ?></strong> 
                        <a href="<?php echo get_permalink($dashboard_page_id); ?>" target="_blank">
                            <?php echo get_the_title($dashboard_page_id); ?>
                        </a>
                        <small>(<a href="<?php echo get_edit_post_link($dashboard_page_id); ?>" target="_blank"><?php _e('Edit', 'lift-docs-system'); ?></a>)</small>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px; padding: 10px; margin-top: 15px;">
                <h5 style="color: #856404; margin-top: 0;"><?php _e('💡 Usage Examples:', 'lift-docs-system'); ?></h5>
                <ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 12px;">
                    <li><code>[docs_login_form title="Member Login" redirect_to="/dashboard"]</code></li>
                    <li><code>[docs_dashboard show_stats="false" documents_per_page="6"]</code></li>
                    <li><strong><?php _e('Alternative URLs:', 'lift-docs-system'); ?></strong> <code>/document-login</code> & <code>/document-dashboard/</code></li>
                </ul>
            </div>
            
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px; padding: 10px; margin-top: 15px;">
                <h5 style="color: #155724; margin-top: 0;"><?php _e('Login Methods Supported:', 'lift-docs-system'); ?></h5>
                <ul style="margin: 0; padding-left: 20px; color: #155724; font-size: 12px;">
                    <li><strong><?php _e('Username:', 'lift-docs-system'); ?></strong> <?php _e('WordPress username', 'lift-docs-system'); ?></li>
                    <li><strong><?php _e('Email:', 'lift-docs-system'); ?></strong> <?php _e('User email address', 'lift-docs-system'); ?></li>
                    <li><strong><?php _e('User Code:', 'lift-docs-system'); ?></strong> <?php _e('Unique 6-8 character code', 'lift-docs-system'); ?></li>
                </ul>
            </div>
            
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px; padding: 10px; margin-top: 15px;">
                <h5 style="color: #721c24; margin-top: 0;"><?php _e('Simple Login Design:', 'lift-docs-system'); ?></h5>
                <ul style="margin: 0; padding-left: 20px; color: #721c24; font-size: 12px;">
                    <li><strong><?php _e('Clean Interface:', 'lift-docs-system'); ?></strong> <?php _e('No theme header/footer on direct URL', 'lift-docs-system'); ?></li>
                    <li><strong><?php _e('Custom Logo:', 'lift-docs-system'); ?></strong> <?php _e('Upload logo in Login Page Customization settings', 'lift-docs-system'); ?></li>
                    <li><strong><?php _e('Color Themes:', 'lift-docs-system'); ?></strong> <?php _e('Customize colors below in this settings page', 'lift-docs-system'); ?></li>
                    <li><strong><?php _e('Responsive:', 'lift-docs-system'); ?></strong> <?php _e('Works perfectly on mobile and desktop', 'lift-docs-system'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    
}
