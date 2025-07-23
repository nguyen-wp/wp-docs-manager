<?php
/**
 * Debug Settings Tab Issues
 * 
 * This file helps debug why tabs are not working in the settings page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔍 Debugging Settings Tab Issues</h2>";

echo "<h3>1. Tab Navigation Analysis</h3>";

// Check current page and tab
$current_page = isset($_GET['page']) ? $_GET['page'] : 'N/A';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'N/A';

echo "<ul>";
echo "<li><strong>Current Page:</strong> " . esc_html($current_page) . "</li>";
echo "<li><strong>Current Tab:</strong> " . esc_html($current_tab) . "</li>";
echo "<li><strong>Current URL:</strong> " . esc_url($_SERVER['REQUEST_URI']) . "</li>";
echo "</ul>";

echo "<h3>2. Settings Registration Check</h3>";

// Check if settings are registered
global $wp_settings_sections, $wp_settings_fields;

echo "<h4>Registered Settings Sections:</h4>";
echo "<ul>";
$sections_found = false;
if (isset($wp_settings_sections)) {
    foreach (['lift-docs-general', 'lift-docs-security', 'lift-docs-display'] as $page) {
        if (isset($wp_settings_sections[$page])) {
            echo "<li>✅ <strong>$page:</strong> " . count($wp_settings_sections[$page]) . " sections</li>";
            $sections_found = true;
        } else {
            echo "<li>❌ <strong>$page:</strong> Not found</li>";
        }
    }
}
if (!$sections_found) {
    echo "<li>❌ No settings sections found. Settings may not be initialized.</li>";
}
echo "</ul>";

echo "<h4>Registered Settings Fields:</h4>";
echo "<ul>";
$fields_found = false;
if (isset($wp_settings_fields)) {
    foreach (['lift-docs-general', 'lift-docs-security', 'lift-docs-display'] as $page) {
        if (isset($wp_settings_fields[$page])) {
            $total_fields = 0;
            foreach ($wp_settings_fields[$page] as $section => $fields) {
                $total_fields += count($fields);
            }
            echo "<li>✅ <strong>$page:</strong> $total_fields fields</li>";
            $fields_found = true;
        } else {
            echo "<li>❌ <strong>$page:</strong> No fields found</li>";
        }
    }
}
if (!$fields_found) {
    echo "<li>❌ No settings fields found. Settings may not be initialized.</li>";
}
echo "</ul>";

echo "<h3>3. WordPress Menu Check</h3>";

// Check if the parent menu exists
global $menu, $submenu;

echo "<h4>Main Menu Items:</h4>";
echo "<ul>";
$lift_docs_found = false;
if (isset($menu) && is_array($menu)) {
    foreach ($menu as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'lift-docs-system') {
            echo "<li>✅ Found parent menu: " . esc_html($menu_item[0]) . "</li>";
            $lift_docs_found = true;
            break;
        }
    }
}
if (!$lift_docs_found) {
    echo "<li>❌ Parent menu 'lift-docs-system' not found</li>";
}
echo "</ul>";

echo "<h4>Submenu Items:</h4>";
echo "<ul>";
if (isset($submenu['lift-docs-system'])) {
    foreach ($submenu['lift-docs-system'] as $submenu_item) {
        echo "<li>✅ " . esc_html($submenu_item[0]) . " (" . esc_html($submenu_item[2]) . ")</li>";
    }
} else {
    echo "<li>❌ No submenu items found for 'lift-docs-system'</li>";
}
echo "</ul>";

echo "<h3>4. Settings Options Check</h3>";

// Check current settings
$settings = get_option('lift_docs_settings', array());
echo "<h4>Current Settings:</h4>";
if (!empty($settings)) {
    echo "<ul>";
    foreach ($settings as $key => $value) {
        $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo "<li><strong>$key:</strong> " . esc_html($display_value) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ No settings found in database</p>";
}

echo "<h3>5. Tab URLs Test</h3>";

echo "<h4>Test Tab URLs:</h4>";
$base_url = admin_url('admin.php?page=lift-docs-settings');
echo "<ul>";
echo "<li><a href='{$base_url}&tab=general' target='_blank'>General Tab</a></li>";
echo "<li><a href='{$base_url}&tab=security' target='_blank'>Security Tab</a></li>";
echo "<li><a href='{$base_url}&tab=display' target='_blank'>Display Tab</a></li>";
echo "</ul>";

echo "<h3>6. Common Issues & Solutions</h3>";

echo "<h4>Possible Issues:</h4>";
echo "<ul>";
echo "<li><strong>Settings not initialized:</strong> LIFT_Docs_Settings::init_settings() not called</li>";
echo "<li><strong>Parent menu missing:</strong> Main plugin menu not created</li>";
echo "<li><strong>Permission issues:</strong> User doesn't have 'manage_options' capability</li>";
echo "<li><strong>Hook timing:</strong> Settings initialized before WordPress is ready</li>";
echo "<li><strong>JavaScript errors:</strong> Blocking tab functionality</li>";
echo "</ul>";

echo "<h4>Quick Fixes:</h4>";
echo "<ol>";
echo "<li><strong>Check main plugin file:</strong> Ensure LIFT_Docs_Settings is initialized</li>";
echo "<li><strong>Verify parent menu:</strong> Make sure main plugin creates 'lift-docs-system' menu</li>";
echo "<li><strong>Test direct URLs:</strong> Use the test URLs above to check individual tabs</li>";
echo "<li><strong>Check browser console:</strong> Look for JavaScript errors</li>";
echo "<li><strong>Clear cache:</strong> If using caching plugins</li>";
echo "</ol>";

echo "<h3>7. Debug Code</h3>";
echo "<p>Add this to your main plugin file to debug:</p>";
echo "<pre><code>";
echo "add_action('admin_notices', function() {\n";
echo "    if (isset(\$_GET['page']) && \$_GET['page'] === 'lift-docs-settings') {\n";
echo "        echo '&lt;div class=\"notice notice-info\"&gt;&lt;p&gt;Settings page loaded with tab: ' . (\$_GET['tab'] ?? 'none') . '&lt;/p&gt;&lt;/div&gt;';\n";
echo "    }\n";
echo "});\n";
echo "</code></pre>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<strong>⚠️ Debug Information</strong><br>";
echo "Use this information to identify why tabs are not working in your settings page.";
echo "</div>";
?>
