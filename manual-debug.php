<?php
/**
 * Manual rewrite rules flush và debug
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Check if current user can manage options
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    header('Content-Type: text/plain');
    echo "Access denied. Please login as admin first.\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== LIFT Docs Debug Report ===\n\n";

// 1. Check current rewrite rules
echo "1. Current Rewrite Rules (LIFT related):\n";
$rewrite_rules = get_option('rewrite_rules');
$found_lift_rules = false;
foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift') !== false || strpos($replacement, 'lift') !== false) {
        echo "   $pattern → $replacement\n";
        $found_lift_rules = true;
    }
}
if (!$found_lift_rules) {
    echo "   ❌ No LIFT rewrite rules found!\n";
}
echo "\n";

// 2. Check if class exists and is initialized
echo "2. Class Status:\n";
if (class_exists('LIFT_Docs_Secure_Links')) {
    echo "   ✅ LIFT_Docs_Secure_Links class exists\n";
    
    $instance = LIFT_Docs_Secure_Links::get_instance();
    if ($instance) {
        echo "   ✅ Instance created successfully\n";
    } else {
        echo "   ❌ Failed to create instance\n";
    }
} else {
    echo "   ❌ LIFT_Docs_Secure_Links class not found\n";
}
echo "\n";

// 3. Manually add rewrite rules and flush
echo "3. Manually adding rewrite rules:\n";
add_rewrite_rule('^lift-docs/secure/?$', 'index.php?lift_secure_page=1', 'top');
add_rewrite_rule('^lift-docs/download/?$', 'index.php?lift_download=1', 'top');
echo "   ✅ Rules added\n";

// 4. Flush rewrite rules
echo "4. Flushing rewrite rules:\n";
flush_rewrite_rules();
echo "   ✅ Rewrite rules flushed\n\n";

// 5. Check rules after flush
echo "5. Rewrite Rules After Flush (LIFT related):\n";
$rewrite_rules = get_option('rewrite_rules');
$found_lift_rules = false;
foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift') !== false || strpos($replacement, 'lift') !== false) {
        echo "   $pattern → $replacement\n";
        $found_lift_rules = true;
    }
}
if (!$found_lift_rules) {
    echo "   ❌ Still no LIFT rewrite rules found!\n";
} else {
    echo "   ✅ LIFT rewrite rules are now present!\n";
}
echo "\n";

// 6. Generate test URLs
echo "6. Test URLs:\n";
if (class_exists('LIFT_Docs_Settings')) {
    $docs = get_posts(['post_type' => 'lift_document', 'posts_per_page' => 1]);
    if (!empty($docs)) {
        $doc = $docs[0];
        $view_url = LIFT_Docs_Settings::generate_secure_link($doc->ID);
        $download_url = LIFT_Docs_Settings::generate_secure_download_link($doc->ID);
        
        echo "   Sample View URL: $view_url\n";
        echo "   Sample Download URL: $download_url\n";
    } else {
        echo "   ❌ No documents found for testing\n";
    }
} else {
    echo "   ❌ LIFT_Docs_Settings class not found\n";
}
echo "\n";

// 7. Check debug log
$log_file = WP_CONTENT_DIR . '/lift-docs-debug.log';
echo "7. Debug Log Status:\n";
if (file_exists($log_file)) {
    $log_size = filesize($log_file);
    echo "   ✅ Debug log exists ($log_size bytes)\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($log_file)) . "\n";
} else {
    echo "   ❌ Debug log not found at: $log_file\n";
}

echo "\n=== End Report ===\n";
echo "\nNext steps:\n";
echo "1. Try accessing a download URL now\n";
echo "2. Check debug log for new entries\n";
echo "3. If still not working, there may be a server configuration issue\n";
?>
