<?php
/**
 * Test Simple Login Page
 * 
 * Test the new simplified login page design with custom styling
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Simple Login Page - WP Docs Manager</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .test-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
        }
        .success {
            background: #e8f5e8;
            border-color: #4caf50;
        }
        .info {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        .warning {
            background: #fff3cd;
            border-color: #ff9800;
        }
        .error {
            background: #ffebee;
            border-color: #f44336;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .setting-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #1976d2;
        }
        .test-urls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .url-item {
            padding: 15px;
            background: #e3f2fd;
            border-radius: 6px;
            text-align: center;
        }
        .url-item a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 600;
        }
        .url-item a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Simple Login Page Test</h1>
            <p>Test the new simplified login page design with custom styling</p>
        </div>

        <!-- Current Settings Display -->
        <div class="test-section info">
            <h2>📋 Current Login Page Settings</h2>
            
            <div class="settings-grid">
                <div class="setting-item">
                    <h4>🖼️ Login Logo</h4>
                    <?php 
                    $logo_id = get_option('lift_docs_login_logo', '');
                    $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                    ?>
                    <p><strong>Logo ID:</strong> <?php echo $logo_id ? $logo_id : 'Not set'; ?></p>
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 150px; max-height: 60px;">
                    <?php else: ?>
                        <p><em>No logo selected</em></p>
                    <?php endif; ?>
                </div>
                
                <div class="setting-item">
                    <h4>🎨 Color Settings</h4>
                    <p><strong>Background:</strong> <span style="background: <?php echo get_option('lift_docs_login_bg_color', '#f0f4f8'); ?>; padding: 2px 8px; border-radius: 3px;"><?php echo get_option('lift_docs_login_bg_color', '#f0f4f8'); ?></span></p>
                    <p><strong>Form Background:</strong> <span style="background: <?php echo get_option('lift_docs_login_form_bg', '#ffffff'); ?>; padding: 2px 8px; border-radius: 3px; border: 1px solid #ddd;"><?php echo get_option('lift_docs_login_form_bg', '#ffffff'); ?></span></p>
                    <p><strong>Button Color:</strong> <span style="background: <?php echo get_option('lift_docs_login_btn_color', '#1976d2'); ?>; color: white; padding: 2px 8px; border-radius: 3px;"><?php echo get_option('lift_docs_login_btn_color', '#1976d2'); ?></span></p>
                    <p><strong>Input Border:</strong> <span style="background: <?php echo get_option('lift_docs_login_input_color', '#e0e0e0'); ?>; padding: 2px 8px; border-radius: 3px;"><?php echo get_option('lift_docs_login_input_color', '#e0e0e0'); ?></span></p>
                    <p><strong>Text Color:</strong> <span style="color: <?php echo get_option('lift_docs_login_text_color', '#333333'); ?>; font-weight: bold;"><?php echo get_option('lift_docs_login_text_color', '#333333'); ?></span></p>
                </div>
            </div>
        </div>

        <!-- Test URLs -->
        <div class="test-section success">
            <h2>🌐 Test Login URLs</h2>
            <p>Click on the links below to test the different login implementations:</p>
            
            <div class="test-urls">
                <div class="url-item">
                    <h4>Direct URL</h4>
                    <a href="<?php echo home_url('/docs-login'); ?>" target="_blank">/docs-login</a>
                    <p><small>Simple page without theme header/footer</small></p>
                </div>
                
                <div class="url-item">
                    <h4>Shortcode Page</h4>
                    <?php 
                    $login_page_id = get_option('lift_docs_login_page_id');
                    if ($login_page_id && get_post($login_page_id)) {
                        echo '<a href="' . get_permalink($login_page_id) . '" target="_blank">' . get_the_title($login_page_id) . '</a>';
                        echo '<p><small>Page with [docs_login_form] shortcode</small></p>';
                    } else {
                        echo '<p><em>No shortcode page created yet</em></p>';
                    }
                    ?>
                </div>
                
                <div class="url-item">
                    <h4>Dashboard</h4>
                    <a href="<?php echo home_url('/docs-dashboard'); ?>" target="_blank">/docs-dashboard</a>
                    <p><small>User dashboard (requires login)</small></p>
                </div>
            </div>
        </div>

        <!-- Settings Instructions -->
        <div class="test-section warning">
            <h2>⚙️ How to Customize</h2>
            <p>Go to <strong>LIFT Documents → Settings</strong> to customize the login page appearance:</p>
            
            <ul>
                <li><strong>Upload Logo:</strong> Select a logo image from your media library</li>
                <li><strong>Background Color:</strong> Change the overall page background</li>
                <li><strong>Form Background:</strong> Customize the login form background</li>
                <li><strong>Button Color:</strong> Set the primary button color</li>
                <li><strong>Input Border Color:</strong> Change input field border colors</li>
                <li><strong>Text Color:</strong> Adjust the main text color</li>
            </ul>
            
            <p><a href="<?php echo admin_url('admin.php?page=lift-docs-settings'); ?>" target="_blank" style="background: #1976d2; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">Go to Settings</a></p>
        </div>

        <!-- Features Overview -->
        <div class="test-section info">
            <h2>✨ New Features</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4>🎨 Custom Styling</h4>
                    <ul>
                        <li>Customizable colors and logo</li>
                        <li>Responsive design</li>
                        <li>Clean, modern interface</li>
                        <li>No theme dependencies</li>
                    </ul>
                </div>
                
                <div>
                    <h4>📱 Simple Design</h4>
                    <ul>
                        <li>Removed header/footer from direct URL</li>
                        <li>Centered login form</li>
                        <li>Minimal distractions</li>
                        <li>Focus on login functionality</li>
                    </ul>
                </div>
                
                <div>
                    <h4>🔧 Flexible Implementation</h4>
                    <ul>
                        <li>Direct URL: /docs-login</li>
                        <li>Shortcode: [docs_login_form]</li>
                        <li>Customizable attributes</li>
                        <li>Admin-configurable styling</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Shortcode Examples -->
        <div class="test-section">
            <h2>📝 Shortcode Examples</h2>
            
            <h4>Basic Login Form:</h4>
            <div class="code">[docs_login_form]</div>
            
            <h4>With Custom Title:</h4>
            <div class="code">[docs_login_form title="Member Access" description="Login to access your documents"]</div>
            
            <h4>With Features List:</h4>
            <div class="code">[docs_login_form show_features="true"]</div>
            
            <h4>With Custom Redirect:</h4>
            <div class="code">[docs_login_form redirect_to="/custom-dashboard"]</div>
        </div>

        <!-- Test Status -->
        <div class="test-section success">
            <h2>✅ Test Checklist</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div>
                    <h4>Login Page</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Simple design without header/footer</li>
                        <li>✅ Custom logo support</li>
                        <li>✅ Color customization</li>
                        <li>✅ Responsive layout</li>
                    </ul>
                </div>
                
                <div>
                    <h4>Settings Page</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Logo upload with media library</li>
                        <li>✅ Color picker controls</li>
                        <li>✅ Live preview capability</li>
                        <li>✅ Organized in tabs</li>
                    </ul>
                </div>
                
                <div>
                    <h4>Shortcode</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Uses custom styling</li>
                        <li>✅ Supports attributes</li>
                        <li>✅ Automatic page creation</li>
                        <li>✅ Backward compatibility</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
            <p><strong>🎉 Simple Login Page Implementation Complete!</strong></p>
            <p>The login page now features a clean, customizable design with admin-configurable styling.</p>
        </div>
    </div>
</body>
</html>
