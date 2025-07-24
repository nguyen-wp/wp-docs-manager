<?php
require_once '../../../wp-load.php';

echo "<h2>Testing Clean Layout for Secure Documents</h2>";

// Get a test document
$documents = get_posts(array(
    'post_type' => 'lift_document',
    'post_status' => 'publish',
    'numberposts' => 1
));

if (empty($documents)) {
    echo "<p style='color: red;'>❌ No documents found. Please create a test document first.</p>";
    exit;
}

$document = $documents[0];
echo "<p>✅ Found test document: " . esc_html($document->post_title) . " (ID: {$document->ID})</p>";

// Generate secure link
$token = LIFT_Docs_Settings::generate_secure_link($document->ID);

if (!$token) {
    echo "<p style='color: red;'>❌ Failed to generate secure token</p>";
    exit;
}

$secure_url = home_url('/lift-docs/secure/?lift_secure=' . urlencode($token));

echo "<h3>Test Links:</h3>";
echo "<ul>";
echo "<li><a href='" . esc_url($secure_url) . "' target='_blank'>🔒 Secure Document View (Clean Layout)</a></li>";
echo "</ul>";

echo "<h3>Layout Configuration:</h3>";
echo "<p>The secure document view now has two layout options:</p>";
echo "<ul>";
echo "<li><strong>Clean Layout</strong> (default): Standalone HTML như /document-login/ - không có header/footer theme</li>";
echo "<li><strong>Themed Layout</strong>: Sử dụng header/footer của theme (legacy)</li>";
echo "</ul>";

echo "<p>Clean layout provides:</p>";
echo "<ul>";
echo "<li>✅ Consistent branding với login page</li>";
echo "<li>✅ Focused document viewing experience</li>";
echo "<li>✅ Mobile responsive design</li>";
echo "<li>✅ Modern card-based UI</li>";
echo "<li>✅ No theme conflicts</li>";
echo "</ul>";

echo "<h3>Filter để switch layout:</h3>";
echo "<code>add_filter('lift_docs_secure_use_clean_layout', '__return_false'); // Use themed layout</code>";
?>
