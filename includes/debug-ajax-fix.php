<?php
/**
 * Debug helper for AJAX issues when WP_DEBUG is enabled
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean output buffer and suppress debug output for AJAX requests
 */
function lift_docs_clean_ajax_output() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // Clean any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start fresh output buffering for AJAX
        ob_start();
        
        // Temporarily disable error reporting for AJAX to prevent debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
}

// Hook early in the AJAX process
add_action('wp_ajax_search_document_users', 'lift_docs_clean_ajax_output', 1);

/**
 * Re-enable error reporting after AJAX response
 */
function lift_docs_restore_error_reporting() {
    if (defined('DOING_AJAX') && DOING_AJAX && defined('WP_DEBUG') && WP_DEBUG) {
        // Restore error reporting after AJAX
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
}

// Hook after AJAX response
add_action('wp_ajax_search_document_users', 'lift_docs_restore_error_reporting', 999);

/**
 * Add debug information to admin footer
 */
function lift_docs_debug_info() {
    if (current_user_can('administrator') && defined('WP_DEBUG') && WP_DEBUG) {
        ?>
        <script type="text/javascript">
        console.log('LIFT Docs Debug Info:', {
            'WP_DEBUG': <?php echo WP_DEBUG ? 'true' : 'false'; ?>,
            'DOING_AJAX': <?php echo (defined('DOING_AJAX') && DOING_AJAX) ? 'true' : 'false'; ?>,
            'ajaxurl': ajaxurl,
            'user_can_edit_posts': <?php echo current_user_can('edit_posts') ? 'true' : 'false'; ?>,
            'search_users_nonce': '<?php echo wp_create_nonce('search_document_users'); ?>'
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'lift_docs_debug_info');
