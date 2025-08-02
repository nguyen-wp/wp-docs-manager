<?php
/**
 * CSS/JS Editor functionality for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_CSS_JS_Editor {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        
        // Add default CSS/JS if none exists
        $this->maybe_add_default_styles();
    }

    private function init_hooks() {
        // Admin menu only in admin area
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_editor_menu'), 20);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
        }
        
        // AJAX handlers (work in both admin and frontend)
        add_action('wp_ajax_save_custom_css', array($this, 'ajax_save_custom_css'));
        add_action('wp_ajax_save_custom_js', array($this, 'ajax_save_custom_js'));
        add_action('wp_ajax_reset_custom_css', array($this, 'ajax_reset_custom_css'));
        add_action('wp_ajax_reset_custom_js', array($this, 'ajax_reset_custom_js'));
        
        // Frontend output (always load with high priority)
        add_action('wp_head', array($this, 'output_custom_css'), 999);
        add_action('wp_footer', array($this, 'output_custom_js'), 999);
        
        // Add shortcode for testing
        add_shortcode('lift_css_js_test', array($this, 'css_js_test_shortcode'));
    }

    /**
     * Add CSS/JS Editor menu items
     */
    public function add_editor_menu() {
        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('CSS Editor', 'lift-docs-system'),
            __('CSS Editor', 'lift-docs-system'),
            'manage_options',
            'lift-docs-css-editor',
            array($this, 'css_editor_page')
        );

        add_submenu_page(
            'edit.php?post_type=lift_document',
            __('JS Editor', 'lift-docs-system'),
            __('JS Editor', 'lift-docs-system'),
            'manage_options',
            'lift-docs-js-editor',
            array($this, 'js_editor_page')
        );
    }

    /**
     * Enqueue editor scripts and styles
     */
    public function enqueue_editor_scripts($hook) {
        // Only load on our editor pages
        if (strpos($hook, 'lift-docs-css-editor') !== false || strpos($hook, 'lift-docs-js-editor') !== false) {
            // CodeMirror for syntax highlighting
            wp_enqueue_code_editor(array('type' => 'text/css'));
            wp_enqueue_code_editor(array('type' => 'application/javascript'));
            
            // Our custom editor styles and scripts
            wp_enqueue_style(
                'lift-docs-editor',
                LIFT_DOCS_PLUGIN_URL . 'assets/css/editor.css',
                array('code-editor'),
                LIFT_DOCS_VERSION
            );

            wp_enqueue_script(
                'lift-docs-editor',
                LIFT_DOCS_PLUGIN_URL . 'assets/js/editor.js',
                array('jquery', 'code-editor'),
                LIFT_DOCS_VERSION,
                true
            );

            wp_localize_script('lift-docs-editor', 'lift_editor_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lift_editor_nonce'),
                'save_success' => __('Code saved successfully!', 'lift-docs-system'),
                'save_error' => __('Error saving code. Please try again.', 'lift-docs-system')
            ));
        }
    }

    /**
     * CSS Editor page
     */
    public function css_editor_page() {
        $custom_css = get_option('lift_docs_custom_css', '');
        ?>
        <div class="wrap lift-docs-editor-wrap">
            <h1><?php _e('CSS Editor', 'lift-docs-system'); ?></h1>
            <p><?php _e('Add custom CSS that will be applied to your LIFT Docs System frontend.', 'lift-docs-system'); ?></p>
            
            <form id="css-editor-form" method="post">
                <?php wp_nonce_field('lift_editor_nonce', 'css_editor_nonce'); ?>
                
                <div class="editor-container">
                    <div class="editor-header">
                        <h3><?php _e('Custom CSS', 'lift-docs-system'); ?></h3>
                        <div class="editor-actions">
                            <button type="button" id="format-css" class="button"><?php _e('Format Code', 'lift-docs-system'); ?></button>
                            <button type="button" id="clear-css" class="button"><?php _e('Clear All', 'lift-docs-system'); ?></button>
                            <button type="button" id="reset-css" class="button"><?php _e('Reset to Default', 'lift-docs-system'); ?></button>
                            <button type="submit" id="save-css" class="button button-primary"><?php _e('Save CSS', 'lift-docs-system'); ?></button>
                        </div>
                    </div>
                    
                    <textarea id="custom-css-editor" name="custom_css" rows="25" cols="100"><?php echo esc_textarea($custom_css); ?></textarea>
                </div>
                
                <div class="editor-info">
                    <h4><?php _e('Tips:', 'lift-docs-system'); ?></h4>
                    <ul>
                        <li><?php _e('Use Ctrl+S (Cmd+S on Mac) to save quickly', 'lift-docs-system'); ?></li>
                        <li><?php _e('CSS will be automatically minified for better performance', 'lift-docs-system'); ?></li>
                        <li><?php _e('Target LIFT Docs elements with .lift-docs prefix', 'lift-docs-system'); ?></li>
                    </ul>
                </div>
            </form>
            
            <div id="editor-message" class="notice" style="display: none;"></div>
        </div>
        <?php
    }

    /**
     * JS Editor page
     */
    public function js_editor_page() {
        $custom_js = get_option('lift_docs_custom_js', '');
        ?>
        <div class="wrap lift-docs-editor-wrap">
            <h1><?php _e('JavaScript Editor', 'lift-docs-system'); ?></h1>
            <p><?php _e('Add custom JavaScript that will be applied to your LIFT Docs System frontend.', 'lift-docs-system'); ?></p>
            
            <form id="js-editor-form" method="post">
                <?php wp_nonce_field('lift_editor_nonce', 'js_editor_nonce'); ?>
                
                <div class="editor-container">
                    <div class="editor-header">
                        <h3><?php _e('Custom JavaScript', 'lift-docs-system'); ?></h3>
                        <div class="editor-actions">
                            <button type="button" id="format-js" class="button"><?php _e('Format Code', 'lift-docs-system'); ?></button>
                            <button type="button" id="clear-js" class="button"><?php _e('Clear All', 'lift-docs-system'); ?></button>
                            <button type="button" id="reset-js" class="button"><?php _e('Reset to Default', 'lift-docs-system'); ?></button>
                            <button type="submit" id="save-js" class="button button-primary"><?php _e('Save JavaScript', 'lift-docs-system'); ?></button>
                        </div>
                    </div>
                    
                    <textarea id="custom-js-editor" name="custom_js" rows="25" cols="100"><?php echo esc_textarea($custom_js); ?></textarea>
                </div>
                
                <div class="editor-info">
                    <h4><?php _e('Tips:', 'lift-docs-system'); ?></h4>
                    <ul>
                        <li><?php _e('Use Ctrl+S (Cmd+S on Mac) to save quickly', 'lift-docs-system'); ?></li>
                        <li><?php _e('Wrap your code in jQuery(document).ready() for DOM manipulation', 'lift-docs-system'); ?></li>
                        <li><?php _e('JavaScript will be loaded in the footer for better performance', 'lift-docs-system'); ?></li>
                        <li><?php _e('Use console.log() for debugging', 'lift-docs-system'); ?></li>
                    </ul>
                </div>
            </form>
            
            <div id="editor-message" class="notice" style="display: none;"></div>
        </div>
        <?php
    }

    /**
     * AJAX handler for saving custom CSS
     */
    public function ajax_save_custom_css() {
        check_ajax_referer('lift_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $custom_css = isset($_POST['custom_css']) ? stripslashes($_POST['custom_css']) : '';
        
        // Basic CSS validation
        $custom_css = $this->sanitize_css($custom_css);
        
        update_option('lift_docs_custom_css', $custom_css);
        
        wp_send_json_success(array(
            'message' => __('CSS saved successfully!', 'lift-docs-system')
        ));
    }

    /**
     * AJAX handler for saving custom JS
     */
    public function ajax_save_custom_js() {
        check_ajax_referer('lift_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $custom_js = isset($_POST['custom_js']) ? stripslashes($_POST['custom_js']) : '';
        
        // Basic JS validation
        $custom_js = $this->sanitize_js($custom_js);
        
        update_option('lift_docs_custom_js', $custom_js);
        
        wp_send_json_success(array(
            'message' => __('JavaScript saved successfully!', 'lift-docs-system')
        ));
    }

    /**
     * AJAX handler for resetting custom CSS to default
     */
    public function ajax_reset_custom_css() {
        check_ajax_referer('lift_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Reset to default CSS
        delete_option('lift_docs_custom_css');
        $default_css = $this->get_default_css();
        update_option('lift_docs_custom_css', $default_css);
        
        wp_send_json_success(array(
            'message' => __('CSS reset to default successfully!', 'lift-docs-system'),
            'default_css' => $default_css
        ));
    }

    /**
     * AJAX handler for resetting custom JS to default
     */
    public function ajax_reset_custom_js() {
        check_ajax_referer('lift_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Reset to default JS
        delete_option('lift_docs_custom_js');
        $default_js = $this->get_default_js();
        update_option('lift_docs_custom_js', $default_js);
        
        wp_send_json_success(array(
            'message' => __('JavaScript reset to default successfully!', 'lift-docs-system'),
            'default_js' => $default_js
        ));
    }

    /**
     * Output custom CSS in head
     */
    public function output_custom_css() {
        $custom_css = get_option('lift_docs_custom_css', '');
        if (!empty($custom_css)) {
            // Basic sanitization for CSS output
            $custom_css = $this->sanitize_css_for_output($custom_css);
            echo "\n<!-- LIFT Docs Custom CSS -->\n";
            echo '<style type="text/css" id="lift-docs-custom-css">' . "\n";
            echo $custom_css . "\n";
            echo '</style>' . "\n";
        } elseif (WP_DEBUG) {
            echo "\n<!-- LIFT Docs: No custom CSS found -->\n";
        }
    }

    /**
     * Output custom JS in footer
     */
    public function output_custom_js() {
        $custom_js = get_option('lift_docs_custom_js', '');
        if (!empty($custom_js)) {
            // Basic sanitization for JS output
            $custom_js = $this->sanitize_js_for_output($custom_js);
            echo "\n<!-- LIFT Docs Custom JavaScript -->\n";
            echo '<script type="text/javascript" id="lift-docs-custom-js">' . "\n";
            echo $custom_js . "\n";
            echo '</script>' . "\n";
        } elseif (WP_DEBUG) {
            echo "\n<!-- LIFT Docs: No custom JS found -->\n";
        }
    }

    /**
     * Sanitize CSS input
     */
    private function sanitize_css($css) {
        // Remove any potential PHP tags
        $css = preg_replace('/<\?php.*?\?>/is', '', $css);
        $css = preg_replace('/<\?.*?\?>/is', '', $css);
        
        // Remove script tags
        $css = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $css);
        
        return trim($css);
    }

    /**
     * Sanitize JavaScript input
     */
    private function sanitize_js($js) {
        // Remove any potential PHP tags
        $js = preg_replace('/<\?php.*?\?>/is', '', $js);
        $js = preg_replace('/<\?.*?\?>/is', '', $js);
        
        // Remove HTML tags
        $js = strip_tags($js);
        
        return trim($js);
    }

    /**
     * Sanitize CSS for output (lighter sanitization)
     */
    private function sanitize_css_for_output($css) {
        // Remove any potential PHP tags
        $css = preg_replace('/<\?php.*?\?>/is', '', $css);
        $css = preg_replace('/<\?.*?\?>/is', '', $css);
        
        // Remove script tags but keep CSS intact
        $css = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $css);
        
        return $css;
    }

    /**
     * Sanitize JS for output (lighter sanitization)
     */
    private function sanitize_js_for_output($js) {
        // Remove any potential PHP tags
        $js = preg_replace('/<\?php.*?\?>/is', '', $js);
        $js = preg_replace('/<\?.*?\?>/is', '', $js);
        
        // Remove HTML tags but keep JavaScript intact
        $js = preg_replace('/<(?!\/?(script|noscript)\b)[^>]*>/i', '', $js);
        
        return $js;
    }

    /**
     * Add default styles if none exist
     */
    private function maybe_add_default_styles() {
        // Only add defaults if no custom CSS/JS exists yet
        $custom_css = get_option('lift_docs_custom_css', '');
        $custom_js = get_option('lift_docs_custom_js', '');
        
        if (empty($custom_css)) {
            $default_css = $this->get_default_css();
            update_option('lift_docs_custom_css', $default_css);
        }
        
        if (empty($custom_js)) {
            $default_js = $this->get_default_js();
            update_option('lift_docs_custom_js', $default_js);
        }
    }

    /**
     * Get default CSS
     */
    private function get_default_css() {
        return '/* LIFT Docs System - Custom CSS */
/* Add your custom styles here */
';
    }

    /**
     * Get default JS
     */
    private function get_default_js() {
        return '/* LIFT Docs System - Custom JavaScript */
jQuery(document).ready(function($) {
    console.log("LIFT Docs Custom JS loaded!");
});';
    }

    /**
     * Force initialize default styles (for testing)
     */
    public function force_init_default_styles() {
        delete_option('lift_docs_custom_css');
        delete_option('lift_docs_custom_js');
        $this->maybe_add_default_styles();
    }
}
