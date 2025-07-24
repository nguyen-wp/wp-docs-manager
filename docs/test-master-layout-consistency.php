<?php
require_once '../../../wp-load.php';

echo "<h2>Testing Master Layout Consistency</h2>";

echo "<h3>1. Valid Secure Document (Clean Layout)</h3>";
$document_id = 40;
$token = LIFT_Docs_Settings::generate_secure_link($document_id);
$valid_url = home_url('/lift-docs/secure/?lift_secure=' . urlencode($token));
echo "<p><a href='" . esc_url($valid_url) . "' target='_blank'>📄 Valid Document (Should show file list)</a></p>";

echo "<h3>2. Invalid/Expired Token (Access Denied)</h3>";
$invalid_url = home_url('/lift-docs/secure/?lift_secure=invalid_token_123');
echo "<p><a href='" . esc_url($invalid_url) . "' target='_blank'>🔒 Invalid Token (Should show Access Denied)</a></p>";

echo "<h3>3. Login Page for Comparison</h3>";
$login_url = home_url('/document-login/');
echo "<p><a href='" . esc_url($login_url) . "' target='_blank'>🔐 Document Login (Master Layout)</a></p>";

echo "<h3>Layout Consistency Features:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Same background color</strong> từ login page settings</li>";
echo "<li>✅ <strong>Same container styling</strong> với card design</li>";
echo "<li>✅ <strong>Same font family</strong> và typography</li>";
echo "<li>✅ <strong>Same logo display</strong> nếu có</li>";
echo "<li>✅ <strong>Same button styling</strong> cho actions</li>";
echo "<li>✅ <strong>Same responsive behavior</strong> trên mobile</li>";
echo "<li>✅ <strong>Same color scheme</strong> consistent branding</li>";
echo "</ul>";

echo "<h3>Access Denied Page Features:</h3>";
echo "<ul>";
echo "<li>🔒 Centered layout với proper error icon</li>";
echo "<li>📝 Clear error message và explanation</li>";
echo "<li>🔗 Action buttons: Login và Return Home</li>";
echo "<li>📋 Helpful troubleshooting suggestions</li>";
echo "<li>🎨 Consistent styling với master layout</li>";
echo "</ul>";
?>
