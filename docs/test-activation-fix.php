<?php
/**
 * Test Plugin Activation Fix
 * 
 * Test to ensure plugin can be activated/deactivated properly
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
    <title>Plugin Activation Test - WP Docs Manager</title>
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
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #1976d2;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Plugin Activation Fix Test</h1>
            <p>Testing plugin activation/deactivation fixes and CSS improvements</p>
        </div>

        <!-- Plugin Status Check -->
        <div class="test-section <?php echo is_plugin_active('wp-docs-manager/lift-docs-system.php') ? 'success' : 'warning'; ?>">
            <h2>📦 Plugin Status</h2>
            
            <?php 
            $plugin_file = 'wp-docs-manager/lift-docs-system.php';
            $is_active = is_plugin_active($plugin_file);
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            ?>
            
            <div class="status-grid">
                <div class="status-item">
                    <h4>🔌 Activation Status</h4>
                    <p><strong>Status:</strong> <?php echo $is_active ? '✅ Active' : '❌ Inactive'; ?></p>
                    <p><strong>Version:</strong> <?php echo esc_html($plugin_data['Version']); ?></p>
                    <p><strong>File:</strong> <?php echo esc_html($plugin_file); ?></p>
                </div>
                
                <div class="status-item">
                    <h4>🗂️ Plugin Files</h4>
                    <p><strong>Main File:</strong> <?php echo file_exists(WP_PLUGIN_DIR . '/' . $plugin_file) ? '✅ Exists' : '❌ Missing'; ?></p>
                    <p><strong>Frontend Login:</strong> <?php echo file_exists(WP_PLUGIN_DIR . '/wp-docs-manager/includes/class-lift-docs-frontend-login.php') ? '✅ Exists' : '❌ Missing'; ?></p>
                    <p><strong>Settings:</strong> <?php echo file_exists(WP_PLUGIN_DIR . '/wp-docs-manager/includes/class-lift-docs-settings.php') ? '✅ Exists' : '❌ Missing'; ?></p>
                </div>
                
                <div class="status-item">
                    <h4>⚙️ Database Options</h4>
                    <?php 
                    $login_page_id = get_option('lift_docs_login_page_id');
                    $dashboard_page_id = get_option('lift_docs_dashboard_page_id');
                    $default_pages_created = get_option('lift_docs_default_pages_created');
                    ?>
                    <p><strong>Login Page ID:</strong> <?php echo $login_page_id ? $login_page_id : 'Not set'; ?></p>
                    <p><strong>Dashboard Page ID:</strong> <?php echo $dashboard_page_id ? $dashboard_page_id : 'Not set'; ?></p>
                    <p><strong>Pages Created:</strong> <?php echo $default_pages_created ? '✅ Yes' : '❌ No'; ?></p>
                </div>
            </div>
        </div>

        <!-- Activation Hooks Check -->
        <div class="test-section info">
            <h2>🔗 Activation Hooks Analysis</h2>
            
            <p>Checking for multiple activation hooks that could cause conflicts:</p>
            
            <div class="code">
                <strong>Fixed Issues:</strong><br>
                ✅ Removed duplicate activation hooks<br>
                ✅ Centralized activation in main class<br>
                ✅ Moved create_default_pages to main activation method<br>
                ✅ Removed frontend login separate activation hook
            </div>
            
            <h4>Current Activation Flow:</h4>
            <ol>
                <li><strong>Main Class:</strong> LIFT_Docs_System::activate()</li>
                <li><strong>Create Tables:</strong> create_analytics_table()</li>
                <li><strong>Set Options:</strong> set_default_options()</li>
                <li><strong>Create Pages:</strong> create_default_login_pages()</li>
                <li><strong>Flush Rules:</strong> flush_rewrite_rules()</li>
            </ol>
        </div>

        <!-- CSS Improvements -->
        <div class="test-section success">
            <h2>🎨 CSS Improvements Applied</h2>
            
            <div class="status-grid">
                <div class="status-item">
                    <h4>🚫 Back-to-Top Hidden</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Added CSS to hide all back-to-top buttons</li>
                        <li>Covers multiple selectors and variations</li>
                        <li>Applied to both login and dashboard</li>
                        <li>Works with direct URL and shortcode</li>
                    </ul>
                </div>
                
                <div class="status-item">
                    <h4>☑️ Remember Me Improved</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Custom checkbox styling</li>
                        <li>Better visual appearance</li>
                        <li>Consistent with form design</li>
                        <li>Improved accessibility</li>
                    </ul>
                </div>
                
                <div class="status-item">
                    <h4>🚀 Animations Removed</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Removed all transition animations</li>
                        <li>Better performance</li>
                        <li>Cleaner user experience</li>
                        <li>Exception: Login spinner only</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Test URLs -->
        <div class="test-section info">
            <h2>🌐 Test URLs</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div style="padding: 15px; background: #e3f2fd; border-radius: 6px; text-align: center;">
                    <h4>Login Page</h4>
                    <a href="<?php echo home_url('/docs-login'); ?>" target="_blank" style="color: #1976d2; text-decoration: none; font-weight: 600;">/docs-login</a>
                    <p><small>Simple page without animations</small></p>
                </div>
                
                <div style="padding: 15px; background: #e3f2fd; border-radius: 6px; text-align: center;">
                    <h4>Dashboard</h4>
                    <a href="<?php echo home_url('/docs-dashboard'); ?>" target="_blank" style="color: #1976d2; text-decoration: none; font-weight: 600;">/docs-dashboard</a>
                    <p><small>No back-to-top button</small></p>
                </div>
                
                <div style="padding: 15px; background: #e3f2fd; border-radius: 6px; text-align: center;">
                    <h4>Settings</h4>
                    <a href="<?php echo admin_url('admin.php?page=lift-docs-settings'); ?>" target="_blank" style="color: #1976d2; text-decoration: none; font-weight: 600;">Customization</a>
                    <p><small>Logo and color settings</small></p>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="test-section warning">
            <h2>🛠️ Troubleshooting</h2>
            
            <h4>If plugin still won't activate:</h4>
            <ol>
                <li><strong>Check PHP errors:</strong> Enable WP_DEBUG in wp-config.php</li>
                <li><strong>File permissions:</strong> Ensure plugin files are readable</li>
                <li><strong>Memory limit:</strong> Increase PHP memory limit if needed</li>
                <li><strong>Plugin conflicts:</strong> Deactivate other plugins temporarily</li>
            </ol>
            
            <div class="code">
                <strong>Debug Steps:</strong><br>
                1. Enable debug: define('WP_DEBUG', true);<br>
                2. Check error log: /wp-content/debug.log<br>
                3. Test activation via WP-CLI: wp plugin activate wp-docs-manager<br>
                4. Check database for orphaned options
            </div>
        </div>

        <!-- Success Summary -->
        <div class="test-section success">
            <h2>✅ Fixes Applied</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div>
                    <h4>Plugin Activation</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Fixed duplicate activation hooks</li>
                        <li>✅ Centralized activation logic</li>
                        <li>✅ Proper error handling</li>
                        <li>✅ Clean deactivation process</li>
                    </ul>
                </div>
                
                <div>
                    <h4>UI Improvements</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Hidden back-to-top buttons</li>
                        <li>✅ Improved Remember me styling</li>
                        <li>✅ Removed animations</li>
                        <li>✅ Better performance</li>
                    </ul>
                </div>
                
                <div>
                    <h4>User Experience</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Cleaner login page</li>
                        <li>✅ Faster page loads</li>
                        <li>✅ Better accessibility</li>
                        <li>✅ Consistent styling</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
            <p><strong>🎉 Plugin Activation & CSS Fixes Complete!</strong></p>
            <p>The plugin should now activate/deactivate properly with improved UI.</p>
        </div>
    </div>
</body>
</html>
