<?php
/**
 * Test Interface Tab in Admin Settings
 * 
 * This test verifies the new Interface tab functionality in the admin settings.
 * It checks that the tab appears correctly and contains the expected settings.
 * 
 * URL to test: /wp-admin/admin.php?page=lift-docs-settings&tab=interface
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

echo "<h1>🎨 Interface Tab Testing</h1>";

// Test 1: Check if we can access the interface tab
echo "<h2>Test 1: Interface Tab URL Access</h2>";

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
echo "<p><strong>Current Tab:</strong> " . esc_html($current_tab) . "</p>";

if ($current_tab === 'interface') {
    echo "✅ Interface tab is accessible<br>";
} else {
    echo "❌ Interface tab not currently active<br>";
    echo "📝 Visit: <a href='" . admin_url('admin.php?page=lift-docs-settings&tab=interface') . "'>Interface Tab</a><br>";
}

// Test 2: Check if settings are registered
echo "<h2>Test 2: Settings Registration Check</h2>";

$settings_groups = [
    'lift_docs_settings_interface' => 'Interface settings group',
    'lift_docs_logo_upload' => 'Logo upload setting',
    'lift_docs_custom_logo_width' => 'Custom logo width setting',
    'lift_docs_login_title' => 'Login title setting',
    'lift_docs_login_description' => 'Login description setting'
];

foreach ($settings_groups as $setting => $description) {
    $value = get_option($setting);
    if ($value !== false || has_action("sanitize_option_{$setting}")) {
        echo "✅ $description is registered<br>";
    } else {
        echo "❌ $description may not be registered<br>";
    }
}

// Test 3: Display current interface settings values
echo "<h2>Test 3: Current Interface Settings Values</h2>";

$interface_settings = [
    'lift_docs_logo_upload' => 'Logo Upload URL',
    'lift_docs_custom_logo_width' => 'Custom Logo Width',
    'lift_docs_login_title' => 'Login Page Title',
    'lift_docs_login_description' => 'Login Page Description'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th style='padding: 10px; background: #f0f0f0;'>Setting</th><th style='padding: 10px; background: #f0f0f0;'>Value</th></tr>";

foreach ($interface_settings as $setting => $label) {
    $value = get_option($setting, '');
    $display_value = empty($value) ? '<em>Not set</em>' : esc_html($value);
    echo "<tr><td style='padding: 8px;'>$label</td><td style='padding: 8px;'>$display_value</td></tr>";
}
echo "</table>";

// Test 4: Check if LIFT_Docs_Settings class exists and has interface methods
echo "<h2>Test 4: Settings Class Methods Check</h2>";

if (class_exists('LIFT_Docs_Settings')) {
    echo "✅ LIFT_Docs_Settings class exists<br>";
    
    $settings_instance = new LIFT_Docs_Settings();
    
    $required_methods = [
        'interface_section_callback' => 'Interface section callback method',
        'logo_upload_callback' => 'Logo upload callback method',
        'custom_logo_width_callback' => 'Custom logo width callback method',
        'login_title_callback' => 'Login title callback method',
        'login_description_callback' => 'Login description callback method'
    ];
    
    foreach ($required_methods as $method => $description) {
        if (method_exists($settings_instance, $method)) {
            echo "✅ $description exists<br>";
        } else {
            echo "❌ $description missing<br>";
        }
    }
} else {
    echo "❌ LIFT_Docs_Settings class not found<br>";
}

// Test 5: Interface Tab Navigation Test
echo "<h2>Test 5: Tab Navigation Links</h2>";

$tabs = [
    'general' => 'General',
    'security' => 'Security', 
    'display' => 'Display',
    'interface' => 'Interface'
];

echo "<div style='margin: 20px 0;'>";
foreach ($tabs as $tab_key => $tab_name) {
    $url = admin_url('admin.php?page=lift-docs-settings&tab=' . $tab_key);
    $active = ($current_tab === $tab_key) ? ' style="background: #1976d2; color: white; padding: 10px 15px; text-decoration: none; margin-right: 5px; border-radius: 4px;"' : ' style="background: #f0f0f0; color: #333; padding: 10px 15px; text-decoration: none; margin-right: 5px; border-radius: 4px;"';
    echo "<a href='$url'$active>$tab_name</a>";
}
echo "</div>";

// Test 6: Media Uploader Script Check
echo "<h2>Test 6: Media Uploader Dependencies</h2>";

global $wp_scripts;
if (is_object($wp_scripts)) {
    $enqueued_scripts = array_keys($wp_scripts->queue);
    
    if (in_array('media-upload', $enqueued_scripts) || in_array('wp-media', $enqueued_scripts)) {
        echo "✅ Media uploader scripts are enqueued<br>";
    } else {
        echo "❌ Media uploader scripts may not be enqueued<br>";
        echo "📝 Make sure to enqueue 'media-upload' and 'wp-media' scripts on settings page<br>";
    }
} else {
    echo "❌ Cannot check script dependencies<br>";
}

echo "<hr>";
echo "<h2>🔧 Debug Information</h2>";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
echo "<p><strong>Plugin Active:</strong> " . (class_exists('LIFT_Docs_Settings') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Current User Can Manage Options:</strong> " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Test File:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

?>

<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    h1 { color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
    h2 { color: #333; background: #f9f9f9; padding: 10px; border-left: 4px solid #1976d2; }
    p { line-height: 1.6; }
    table { margin: 10px 0; }
    hr { margin: 30px 0; border: none; border-top: 1px solid #ddd; }
</style>
