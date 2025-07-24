<?php
require_once '../../../wp-load.php';

// Test với document ID 40
$document_id = 40;
$document = get_post($document_id);

if (!$document) {
    echo "Document not found";
    exit;
}

echo "<h2>Generate Secure Link for Document: " . esc_html($document->post_title) . "</h2>";

// Generate secure link
$token = LIFT_Docs_Settings::generate_secure_link($document_id);
$secure_url = home_url('/lift-docs/secure/?lift_secure=' . urlencode($token));

echo "<p><strong>Secure URL:</strong></p>";
echo "<p><a href='" . esc_url($secure_url) . "' target='_blank'>" . esc_html($secure_url) . "</a></p>";

echo "<p>Click the link above để test clean layout!</p>";
?>
