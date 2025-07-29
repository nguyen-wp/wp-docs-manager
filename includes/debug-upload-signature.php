<?php
/**
 * Debug page for testing file upload and signature functionality
 * Use this to verify that CSS and JS files are loading correctly
 */

// Add debug information to admin dashboard
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'lift-forms') {
        $plugin_url = plugin_dir_url(__FILE__);
        $js_file_path = $plugin_url . '../assets/js/file-upload-signature.js';
        $css_file_path = $plugin_url . '../assets/css/secure-frontend.css';
        
        echo '<div class="notice notice-info">';
        echo '<h3>🔍 LIFT Forms Debug Information</h3>';
        echo '<p><strong>Plugin URL:</strong> ' . $plugin_url . '</p>';
        echo '<p><strong>JS File:</strong> <a href="' . $js_file_path . '" target="_blank">' . $js_file_path . '</a></p>';
        echo '<p><strong>CSS File:</strong> <a href="' . $css_file_path . '" target="_blank">' . $css_file_path . '</a></p>';
        
        // Check if files exist
        $js_file_local = dirname(__FILE__) . '/../assets/js/file-upload-signature.js';
        $css_file_local = dirname(__FILE__) . '/../assets/css/secure-frontend.css';
        
        echo '<p><strong>JS File Exists:</strong> ' . (file_exists($js_file_local) ? '✅ Yes' : '❌ No') . '</p>';
        echo '<p><strong>CSS File Exists:</strong> ' . (file_exists($css_file_local) ? '✅ Yes' : '❌ No') . '</p>';
        
        // Test shortcode
        echo '<p><strong>Test Shortcode:</strong> <code>[lift_test_upload_form]</code></p>';
        echo '<p><strong>Create Test Page:</strong> Create a new page with the shortcode above to test upload and signature features.</p>';
        echo '</div>';
    }
});

// Add simple test endpoint to verify AJAX is working
add_action('wp_ajax_test_lift_functionality', 'test_lift_functionality');
add_action('wp_ajax_nopriv_test_lift_functionality', 'test_lift_functionality');

function test_lift_functionality() {
    wp_send_json_success(array(
        'message' => 'LIFT Forms AJAX is working!',
        'timestamp' => current_time('mysql'),
        'upload_dir' => wp_upload_dir(),
        'plugin_info' => array(
            'version' => LIFT_DOCS_VERSION,
            'path' => LIFT_DOCS_PLUGIN_DIR,
            'url' => LIFT_DOCS_PLUGIN_URL
        )
    ));
}

?>
