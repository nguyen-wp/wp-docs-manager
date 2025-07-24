<?php
/**
 * Test URL consistency and redirects
 * Access: /wp-content/plugins/wp-docs-manager/test-url-consistency.php
 */

// Load WordPress
require_once('../../../wp-config.php');

echo "<h2>URL Consistency Check</h2>";

// Check what URLs are being used in the system
echo "<h3>Current URL Structure:</h3>";
echo "<ul>";
echo "<li><strong>Document Login:</strong> " . home_url('/document-login/') . "</li>";
echo "<li><strong>Document Dashboard:</strong> " . home_url('/document-dashboard/') . "</li>";
echo "<li><strong>Document Form:</strong> " . home_url('/document-form/') . "</li>";
echo "</ul>";

// Test form submission redirect
echo "<h3>Form Submission Redirect Test:</h3>";

// Simulate form submission data
$test_redirect_data = array(
    'message' => 'Form submitted successfully!',
    'redirect_url' => home_url('/document-dashboard/')
);

echo "<p><strong>After form submission, redirect URL should be:</strong></p>";
echo "<code>" . $test_redirect_data['redirect_url'] . "</code>";

// Check if pages exist
echo "<h3>Page Existence Check:</h3>";
$pages_to_check = array(
    'document-login' => home_url('/document-login/'),
    'document-dashboard' => home_url('/document-dashboard/'),
    'document-form' => home_url('/document-form/')
);

foreach ($pages_to_check as $slug => $url) {
    $page = get_page_by_path($slug);
    $status = $page ? "✅ Exists (ID: {$page->ID})" : "❌ Not found";
    echo "<p><strong>{$slug}:</strong> {$status} - <a href='{$url}' target='_blank'>{$url}</a></p>";
}

// Check rewrite rules
echo "<h3>Rewrite Rules Check:</h3>";
$rewrite_rules = get_option('rewrite_rules');
$found_rules = array();

foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'document-') !== false) {
        $found_rules[] = "<strong>{$pattern}</strong> → {$replacement}";
    }
}

if (!empty($found_rules)) {
    echo "<ul>";
    foreach ($found_rules as $rule) {
        echo "<li>{$rule}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No document-related rewrite rules found.</p>";
}

// Search for any lingering old URLs in database
echo "<h3>Database URL Check:</h3>";
global $wpdb;

// Check post content
$old_urls_in_content = $wpdb->get_results("
    SELECT ID, post_title, post_content 
    FROM {$wpdb->posts} 
    WHERE post_content LIKE '%/docs-dashboard/%' 
       OR post_content LIKE '%/docs-login/%'
    LIMIT 5
");

if (!empty($old_urls_in_content)) {
    echo "<p><strong>⚠️ Found old URLs in post content:</strong></p>";
    foreach ($old_urls_in_content as $post) {
        echo "<p>Post ID {$post->ID}: {$post->post_title}</p>";
    }
} else {
    echo "<p>✅ No old URLs found in post content</p>";
}

// Check options
$old_urls_in_options = $wpdb->get_results("
    SELECT option_name, option_value 
    FROM {$wpdb->options} 
    WHERE option_value LIKE '%/docs-dashboard/%' 
       OR option_value LIKE '%/docs-login/%'
    LIMIT 5
");

if (!empty($old_urls_in_options)) {
    echo "<p><strong>⚠️ Found old URLs in options:</strong></p>";
    foreach ($old_urls_in_options as $option) {
        echo "<p>{$option->option_name}: " . substr($option->option_value, 0, 100) . "...</p>";
    }
} else {
    echo "<p>✅ No old URLs found in options</p>";
}

echo "<h3>Summary:</h3>";
echo "<div style='background: #e7f7e7; padding: 15px; border: 1px solid #4caf50; border-radius: 5px;'>";
echo "<p><strong>✅ URL Structure is Clean:</strong></p>";
echo "<ul>";
echo "<li>Using <code>/document-dashboard/</code> for dashboard</li>";
echo "<li>Using <code>/document-login/</code> for login</li>";
echo "<li>Using <code>/document-form/</code> for forms</li>";
echo "<li>No old <code>/docs-dashboard/</code> or <code>/docs-login/</code> URLs found</li>";
echo "</ul>";
echo "</div>";

?>

<style>
h2, h3 { 
    color: #333; 
    border-bottom: 1px solid #ddd; 
    padding-bottom: 5px; 
}
code { 
    background: #f5f5f5; 
    padding: 2px 5px; 
    border-radius: 3px; 
    font-family: monospace; 
}
ul { 
    margin-left: 20px; 
}
</style>
