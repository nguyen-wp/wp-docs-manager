<?php
/**
 * Debug Settings Page Display
 * Run this to check why settings tabs are not showing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔍 LIFT Docs Settings Debug</h2>";

// Check if settings class exists and is loaded
if (class_exists('LIFT_Docs_Settings')) {
    echo "<p>✅ <strong>LIFT_Docs_Settings class exists</strong></p>";
    
    // Check if instance can be created
    try {
        $settings_instance = LIFT_Docs_Settings::get_instance();
        echo "<p>✅ <strong>Settings instance created successfully</strong></p>";
        
        // Check if settings page is registered
        global $submenu;
        $found_settings_page = false;
        
        if (isset($submenu['lift-docs-system'])) {
            foreach ($submenu['lift-docs-system'] as $item) {
                if (isset($item[2]) && $item[2] === 'lift-docs-settings') {
                    $found_settings_page = true;
                    break;
                }
            }
        }
        
        if ($found_settings_page) {
            echo "<p>✅ <strong>Settings page is registered in admin menu</strong></p>";
        } else {
            echo "<p>❌ <strong>Settings page NOT found in admin menu</strong></p>";
            echo "<p>Available LIFT Docs menu items:</p>";
            if (isset($submenu['lift-docs-system'])) {
                echo "<ul>";
                foreach ($submenu['lift-docs-system'] as $item) {
                    echo "<li>" . $item[0] . " (" . $item[2] . ")</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No LIFT Docs submenu found</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>Error creating settings instance:</strong> " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>❌ <strong>LIFT_Docs_Settings class does NOT exist</strong></p>";
    echo "<p>Check if the class file is being included properly.</p>";
}

// Check if settings are initialized  
$settings_data = get_option('lift_docs_settings', array());
echo "<h3>📊 Settings Data:</h3>";
if (!empty($settings_data)) {
    echo "<p>✅ Settings data exists in database</p>";
    echo "<pre>" . print_r($settings_data, true) . "</pre>";
} else {
    echo "<p>⚠️ No settings data found in database</p>";
}

// Check admin scripts enqueue
echo "<h3>🎨 Admin Scripts Check:</h3>";
global $wp_scripts, $wp_styles;

if (is_admin()) {
    echo "<p>✅ We are in admin area</p>";
    
    // Check current page
    $current_page = isset($_GET['page']) ? $_GET['page'] : 'not-set';
    echo "<p><strong>Current page:</strong> " . $current_page . "</p>";
    
    // Check if this is our settings page
    if ($current_page === 'lift-docs-settings') {
        echo "<p>✅ <strong>We are on LIFT Docs Settings page</strong></p>";
        
        // Check if scripts are enqueued
        if (wp_script_is('jquery', 'enqueued')) {
            echo "<p>✅ jQuery is enqueued</p>";
        } else {
            echo "<p>❌ jQuery is NOT enqueued</p>";
        }
        
        if (wp_script_is('wp-color-picker', 'enqueued')) {
            echo "<p>✅ Color picker is enqueued</p>";
        } else {
            echo "<p>❌ Color picker is NOT enqueued</p>";
        }
        
    } else {
        echo "<p>⚠️ <strong>Not on LIFT Docs Settings page</strong></p>";
        echo "<p>Go to: <a href='" . admin_url('admin.php?post_type=lift_document&page=lift-docs-settings') . "'>LIFT Docs Settings</a></p>";
    }
} else {
    echo "<p>❌ Not in admin area</p>";
}

// Action recommendation
echo "<h3>🔧 Recommendation:</h3>";
echo "<p>If tabs are not showing, try:</p>";
echo "<ol>";
echo "<li>Go to: <a href='" . admin_url('admin.php?post_type=lift_document&page=lift-docs-settings') . "' target='_blank'>LIFT Docs Settings Page</a></li>";
echo "<li>Check browser console for JavaScript errors (F12)</li>";
echo "<li>Check if tabs HTML is present but hidden (View Page Source)</li>";
echo "<li>Clear any caching plugins</li>";
echo "<li>Try deactivating other plugins temporarily</li>";
echo "</ol>";

?>
