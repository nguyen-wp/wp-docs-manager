<?php
/**
 * Demo Interface Tab - Standalone Test
 * 
 * This creates a quick demo to show Interface tab functionality
 * without needing WordPress admin context.
 */

// Simulate WordPress environment for demo
if (!function_exists('__')) {
    function __($text, $domain = '') { return $text; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8'); }
}
if (!function_exists('esc_url')) {
    function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_textarea')) {
    function esc_textarea($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 Interface Tab Demo - LIFT Docs System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f0f1;
            color: #1d2327;
            line-height: 1.6;
        }
        
        .wrap {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #1976d2;
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 3px solid #1976d2;
            padding-bottom: 15px;
        }
        
        .nav-tab-wrapper {
            margin: 25px 0;
            border-bottom: 1px solid #ccd0d4;
        }
        
        .nav-tab {
            display: inline-block;
            padding: 12px 20px;
            text-decoration: none;
            color: #646970;
            background: #f6f7f7;
            border: 1px solid #ccd0d4;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 6px 6px 0 0;
            transition: all 0.2s ease;
        }
        
        .nav-tab:hover {
            background: #f0f0f1;
            color: #1976d2;
        }
        
        .nav-tab-active {
            background: white !important;
            color: #1976d2 !important;
            font-weight: 600;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
        }
        
        .tab-content {
            padding: 30px 0;
        }
        
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .form-table th {
            text-align: left;
            padding: 20px 10px 20px 0;
            width: 200px;
            vertical-align: top;
            font-weight: 600;
        }
        
        .form-table td {
            padding: 20px 10px;
            vertical-align: top;
        }
        
        .form-table tr {
            border-bottom: 1px solid #f0f0f1;
        }
        
        input[type="text"], input[type="number"], textarea {
            width: 100%;
            max-width: 500px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .button {
            padding: 10px 20px;
            border: 1px solid #2271b1;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            transition: all 0.2s ease;
        }
        
        .button:hover {
            background: #135e96;
            border-color: #135e96;
        }
        
        .button-secondary {
            background: white;
            color: #2271b1;
        }
        
        .button-secondary:hover {
            background: #f6f7f7;
        }
        
        .button-link-delete {
            background: none;
            border: none;
            color: #d63638;
            text-decoration: none;
            padding: 5px 10px;
        }
        
        .button-link-delete:hover {
            color: #135e96;
            text-decoration: underline;
        }
        
        .description {
            margin-top: 8px;
            color: #646970;
            font-style: italic;
            font-size: 13px;
        }
        
        .interface-header {
            background: linear-gradient(135deg, #1976d2, #42a5f5);
            color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .interface-header h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .logo-preview {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .logo-placeholder {
            width: 300px;
            height: 150px;
            border: 2px dashed #ccc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #999;
            border-radius: 4px;
            background: #f9f9f9;
            font-size: 16px;
        }
        
        .success-message {
            background: #d1ecf1;
            border: 1px solid #b8daff;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #1976d2;
        }
        
        .feature-card h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>🎨 Interface Tab Demo - LIFT Docs System</h1>
        
        <div class="success-message">
            ✅ <strong>Interface Tab Successfully Implemented!</strong> This demo shows the new Interface tab for customizing login page appearance.
        </div>
        
        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab">⚙️ General</a>
            <a href="#security" class="nav-tab">🔒 Security</a>
            <a href="#display" class="nav-tab">🖥️ Display</a>
            <a href="#interface" class="nav-tab nav-tab-active">🎨 Interface</a>
        </h2>
        
        <div class="tab-content">
            <div class="interface-header">
                <h3>🎨 Tùy chỉnh giao diện đăng nhập</h3>
                <p>Customize the appearance and branding of your document login page. These settings control how the login page looks to your users.</p>
                <p><strong>Applies to:</strong> /document-login/, /document-dashboard/, secure document pages, and access denied pages.</p>
            </div>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h4>📤 Logo Upload</h4>
                    <p>Upload a custom logo to display on the login page. Supports all common image formats (JPG, PNG, GIF, SVG).</p>
                </div>
                <div class="feature-card">
                    <h4>📏 Logo Size Control</h4>
                    <p>Adjust the maximum width of your logo display (50-500px). Height automatically adjusts to maintain aspect ratio.</p>
                </div>
                <div class="feature-card">
                    <h4>📝 Custom Titles</h4>
                    <p>Set a custom title for your login page to match your brand and improve user experience.</p>
                </div>
                <div class="feature-card">
                    <h4>💬 Welcome Message</h4>
                    <p>Add a custom description or welcome message to guide users and provide context.</p>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="lift_docs_logo_upload">📤 Login Page Logo</label>
                            </th>
                            <td>
                                <div class="lift-interface-logo-container">
                                    <input type="hidden" name="lift_docs_logo_upload" id="lift_docs_logo_upload" value="">
                                    
                                    <div class="logo-preview">
                                        <div class="logo-placeholder" id="interface-logo-preview">
                                            <span>📷 No logo uploaded</span>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="button button-secondary" id="interface-upload-logo-btn">📤 Upload Logo</button>
                                    <button type="button" class="button button-link-delete" id="interface-remove-logo-btn" style="display: none;">🗑️ Remove</button>
                                    <p class="description">Upload a logo image to display on the login page. Recommended size: 300x150px or smaller.</p>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lift_docs_custom_logo_width">📏 Logo Width</label>
                            </th>
                            <td>
                                <input type="number" name="lift_docs_custom_logo_width" value="200" min="50" max="500" style="width: 120px;">
                                <span> px</span>
                                <p class="description">Maximum width for the logo display (50-500px). Height will be automatically adjusted.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lift_docs_login_title">📝 Login Page Title</label>
                            </th>
                            <td>
                                <input type="text" name="lift_docs_login_title" value="" placeholder="Document Access Portal">
                                <p class="description">Custom title to display on the login page. Leave empty to use default.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lift_docs_login_description">💬 Welcome Message</label>
                            </th>
                            <td>
                                <textarea name="lift_docs_login_description" rows="3" placeholder="Please log in to access your documents."></textarea>
                                <p class="description">Custom description text to display below the title on the login page.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="💾 Save Interface Settings">
                </p>
            </form>
        </div>
        
        <hr style="margin: 40px 0; border: none; border-top: 1px solid #ddd;">
        
        <h2>🔧 Implementation Status</h2>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">
            <h3 style="color: #1976d2; margin-bottom: 15px;">✅ Successfully Implemented:</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>✅ Interface tab added to admin settings navigation</li>
                <li>✅ Interface settings section registered with WordPress Settings API</li>
                <li>✅ Logo upload functionality with media library integration</li>
                <li>✅ Logo width control (50-500px range)</li>
                <li>✅ Custom login title input field</li>
                <li>✅ Custom welcome message textarea</li>
                <li>✅ Interface section callback with descriptive header</li>
                <li>✅ JavaScript for media uploader and logo management</li>
                <li>✅ Proper settings validation and sanitization</li>
                <li>✅ Responsive design and user-friendly interface</li>
            </ul>
        </div>
        
        <div style="background: #e8f4fd; padding: 20px; border-radius: 6px; border-left: 4px solid #1976d2;">
            <h3 style="color: #1976d2; margin-bottom: 10px;">📍 Access Interface Tab:</h3>
            <p><strong>WordPress Admin:</strong> /wp-admin/admin.php?page=lift-docs-settings&tab=interface</p>
            <p><strong>Features:</strong> Logo upload, size control, custom titles, welcome messages</p>
            <p><strong>Applies to:</strong> Document login pages, dashboard, secure document views</p>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #646970;">
            <p>🎯 <strong>Interface Tab Demo</strong> | LIFT Docs System v1.0 | <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
    
    <script>
        // Demo JavaScript for interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎨 Interface Tab Demo Loaded');
            
            // Simulate tab switching
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all tabs
                    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('nav-tab-active');
                    
                    console.log('📍 Switched to tab:', this.textContent);
                });
            });
            
            // Simulate logo upload
            document.getElementById('interface-upload-logo-btn')?.addEventListener('click', function() {
                alert('🖼️ In real WordPress admin, this would open the Media Library for logo selection.');
                console.log('📤 Logo upload button clicked');
            });
            
            // Simulate logo removal
            document.getElementById('interface-remove-logo-btn')?.addEventListener('click', function() {
                document.getElementById('interface-logo-preview').innerHTML = '<span>📷 No logo uploaded</span>';
                this.style.display = 'none';
                console.log('🗑️ Logo removed');
            });
        });
    </script>
</body>
</html>
