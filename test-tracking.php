<?php
/**
 * Test script để kiểm tra tracking functionality
 * Chạy script này bằng cách truy cập: your-site.com/wp-content/plugins/wp-docs-manager/test-tracking.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please login as admin first.');
}

echo "<h1>LIFT Docs Tracking Test</h1>";

// Test 1: Check if analytics table exists
global $wpdb;
$table_name = $wpdb->prefix . 'lift_docs_analytics';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "<h2>1. Analytics Table Check</h2>";
echo "Table name: $table_name<br>";
echo "Table exists: " . ($table_exists ? "YES" : "NO") . "<br>";

if (!$table_exists) {
    echo "<strong style='color: red;'>ERROR: Analytics table does not exist!</strong><br>";
    echo "Run plugin activation or create the table manually.<br>";
} else {
    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "<td>{$column->Default}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Check if documents exist
echo "<h2>2. Available Documents</h2>";
$documents = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 5,
    'post_status' => 'publish'
));

if (empty($documents)) {
    echo "<strong style='color: red;'>No documents found!</strong><br>";
    echo "Create a test document first.<br>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Views</th><th>Downloads</th></tr>";
    foreach ($documents as $doc) {
        $views = get_post_meta($doc->ID, '_lift_doc_views', true) ?: 0;
        $downloads = get_post_meta($doc->ID, '_lift_doc_downloads', true) ?: 0;
        echo "<tr>";
        echo "<td>{$doc->ID}</td>";
        echo "<td>{$doc->post_title}</td>";
        echo "<td>$views</td>";
        echo "<td>$downloads</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Test manual tracking
if (!empty($documents) && $table_exists) {
    $test_doc = $documents[0];
    echo "<h2>3. Manual Tracking Test</h2>";
    echo "Testing with document: {$test_doc->post_title} (ID: {$test_doc->ID})<br>";
    
    // Get current counts
    $before_views = get_post_meta($test_doc->ID, '_lift_doc_views', true) ?: 0;
    $before_downloads = get_post_meta($test_doc->ID, '_lift_doc_downloads', true) ?: 0;
    
    echo "Before - Views: $before_views, Downloads: $before_downloads<br>";
    
    // Manual view tracking
    $user_id = get_current_user_id();
    $insert_result = $wpdb->insert(
        $table_name,
        array(
            'document_id' => $test_doc->ID,
            'user_id' => $user_id,
            'action' => 'view',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql')
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($insert_result) {
        echo "Analytics insert: SUCCESS<br>";
    } else {
        echo "Analytics insert: FAILED - " . $wpdb->last_error . "<br>";
    }
    
    // Update meta
    $new_views = $before_views + 1;
    $meta_result = update_post_meta($test_doc->ID, '_lift_doc_views', $new_views);
    
    echo "Meta update result: " . ($meta_result ? 'SUCCESS' : 'FAILED') . "<br>";
    
    // Check after
    $after_views = get_post_meta($test_doc->ID, '_lift_doc_views', true) ?: 0;
    echo "After - Views: $after_views<br>";
    
    if ($after_views > $before_views) {
        echo "<strong style='color: green;'>Manual tracking works!</strong><br>";
    } else {
        echo "<strong style='color: red;'>Manual tracking failed!</strong><br>";
    }
}

// Test 4: Check secure links settings
echo "<h2>4. Secure Links Settings</h2>";
if (class_exists('LIFT_Docs_Settings')) {
    $secure_enabled = LIFT_Docs_Settings::get_setting('enable_secure_links', false);
    echo "Secure links enabled: " . ($secure_enabled ? "YES" : "NO") . "<br>";
    
    if ($secure_enabled && !empty($documents)) {
        $test_doc = $documents[0];
        echo "Sample secure view URL: " . LIFT_Docs_Settings::generate_secure_link($test_doc->ID) . "<br>";
        echo "Sample secure download URL: " . LIFT_Docs_Settings::generate_secure_download_link($test_doc->ID) . "<br>";
    }
} else {
    echo "<strong style='color: red;'>LIFT_Docs_Settings class not found!</strong><br>";
}

// Test 5: Check rewrite rules
echo "<h2>5. Rewrite Rules Check</h2>";
$rewrite_rules = get_option('rewrite_rules');
$lift_rules_found = false;
foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'lift-docs') !== false) {
        echo "Rule: $pattern → $replacement<br>";
        $lift_rules_found = true;
    }
}

if (!$lift_rules_found) {
    echo "<strong style='color: orange;'>No LIFT Docs rewrite rules found. You may need to flush permalinks.</strong><br>";
    echo '<a href="' . admin_url('options-permalink.php') . '">Go to Permalinks Settings</a><br>';
}

echo "<br><hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If analytics table doesn't exist, activate the plugin again</li>";
echo "<li>If no documents exist, create a test document</li>";
echo "<li>If manual tracking works but secure links don't, check the debug logs</li>";
echo "<li>If rewrite rules are missing, go to Permalinks settings and click Save</li>";
echo "</ol>";

echo "<p><a href='" . admin_url('admin.php?page=lift-docs-settings') . "'>Go to LIFT Docs Settings</a></p>";
?>
