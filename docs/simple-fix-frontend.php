<?php
/**
 * Simple Direct Fix for Frontend Login
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔧 Simple Fix for /docs-login/</h2>\n";

// Check current permalink structure
$permalink_structure = get_option('permalink_structure');
echo "<p>Current permalink structure: <code>$permalink_structure</code></p>\n";

if (empty($permalink_structure)) {
    echo "<p style='color: red;'><strong>Warning:</strong> Permalinks are set to 'Plain'. You need to change to 'Post name' or another structure for custom URLs to work.</p>\n";
    echo "<p><a href='" . admin_url('options-permalink.php') . "' target='_blank'>Go to Permalink Settings</a></p>\n";
} else {
    echo "<p style='color: green;'>✅ Permalinks are properly configured.</p>\n";
}

// Force flush rewrite rules
flush_rewrite_rules();
echo "✅ Flushed rewrite rules<br>\n";

// Test URLs
echo "<h3>Test URLs:</h3>\n";
echo "<ul>\n";
echo "<li><a href='" . home_url('/docs-login') . "' target='_blank'>" . home_url('/docs-login') . "</a></li>\n";
echo "<li><a href='" . home_url('/docs-dashboard') . "' target='_blank'>" . home_url('/docs-dashboard') . "</a></li>\n";
echo "</ul>\n";

// Create a simple test to see if the class is working
if (class_exists('LIFT_Docs_Frontend_Login')) {
    echo "<p>✅ Frontend Login class exists</p>\n";
    
    // Check if hooks are registered
    global $wp_filter;
    $template_redirect_hooks = [];
    if (isset($wp_filter['template_redirect'])) {
        foreach ($wp_filter['template_redirect']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && 
                    isset($callback['function'][0]) && 
                    is_object($callback['function'][0])) {
                    $class_name = get_class($callback['function'][0]);
                    if ($class_name === 'LIFT_Docs_Frontend_Login') {
                        $template_redirect_hooks[] = $callback['function'][1];
                    }
                }
            }
        }
    }
    
    if (!empty($template_redirect_hooks)) {
        echo "<p>✅ Template redirect hooks found: " . implode(', ', $template_redirect_hooks) . "</p>\n";
    } else {
        echo "<p>❌ No template redirect hooks found for Frontend Login class</p>\n";
    }
} else {
    echo "<p>❌ Frontend Login class not found</p>\n";
}

// Check if main plugin file includes this class
$main_file = plugin_dir_path(__FILE__) . 'lift-docs-system.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    if (strpos($content, 'class-lift-docs-frontend-login.php') !== false) {
        echo "<p>✅ Frontend login class is included in main plugin file</p>\n";
    } else {
        echo "<p>❌ Frontend login class is NOT included in main plugin file</p>\n";
    }
} else {
    echo "<p>❌ Main plugin file not found</p>\n";
}

echo "<hr>\n";
echo "<h3>Manual Test Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Check if permalinks are set to 'Post name' in <a href='" . admin_url('options-permalink.php') . "' target='_blank'>Settings → Permalinks</a></li>\n";
echo "<li>If not, change to 'Post name' and save</li>\n";
echo "<li>Try the URLs above again</li>\n";
echo "<li>If still not working, deactivate and reactivate the plugin</li>\n";
echo "</ol>\n";
?>
