<?php
require_once '../../../wp-load.php';

// Test với document ID 40
$document_id = 40;
$document = get_post($document_id);

if (!$document) {
    echo "Document not found";
    exit;
}

echo "<h2>Layout Comparison Test</h2>";
echo "<p>Document: " . esc_html($document->post_title) . " (ID: {$document_id})</p>";

// Generate secure links
$token = LIFT_Docs_Settings::generate_secure_link($document_id);
$clean_url = home_url('/lift-docs/secure/?lift_secure=' . urlencode($token));

echo "<h3>Test Links:</h3>";
echo "<ul>";
echo "<li><a href='" . esc_url($clean_url) . "' target='_blank'>🎨 Clean Layout (New)</a></li>";
echo "</ul>";

echo "<h3>Check Document Data:</h3>";

// Check document content
echo "<p><strong>Document Content:</strong></p>";
if ($document->post_content) {
    echo "<div style='background: #f9f9f9; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
    echo "<p>" . wp_trim_words($document->post_content, 50) . "</p>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>❌ No content</p>";
}

// Check file URLs
$file_urls = get_post_meta($document_id, '_lift_doc_file_urls', true);
if (empty($file_urls)) {
    $single_file_url = get_post_meta($document_id, '_lift_doc_file_url', true);
    if ($single_file_url) {
        $file_urls = array($single_file_url);
    }
}

echo "<p><strong>File URLs:</strong></p>";
if (!empty($file_urls)) {
    echo "<ul>";
    foreach ($file_urls as $index => $url) {
        echo "<li>File " . ($index + 1) . ": " . esc_html(basename($url)) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ No files attached</p>";
}

// Check categories
$categories = get_the_terms($document_id, 'lift_doc_category');
echo "<p><strong>Categories:</strong></p>";
if ($categories && !is_wp_error($categories)) {
    foreach ($categories as $cat) {
        echo "<span style='background: #e3f2fd; padding: 2px 6px; border-radius: 3px; margin: 2px;'>" . esc_html($cat->name) . "</span> ";
    }
} else {
    echo "<p>No categories</p>";
}

// Check settings
echo "<h3>Global Layout Settings:</h3>";
$secure_links = new LIFT_Docs_Secure_Links();
$settings = $secure_links->debug_get_layout_settings();
echo "<ul>";
foreach ($settings as $key => $value) {
    $status = $value ? '✅' : '❌';
    echo "<li>{$status} {$key}: " . ($value ? 'true' : 'false') . "</li>";
}
echo "</ul>";

// Force use themed layout for comparison
echo "<h3>Switch to Themed Layout:</h3>";
echo "<p>Add này vào functions.php để test themed layout:</p>";
echo "<code>add_filter('lift_docs_secure_use_clean_layout', '__return_false');</code>";
?>
