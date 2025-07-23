<?php
/**
 * Quick Settings Tab Fix
 * 
 * This file contains a simple test to verify and fix tab functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🔧 Quick Settings Tab Fix & Test</h2>";

// Test if we're on the settings page
if (isset($_GET['page']) && $_GET['page'] === 'lift-docs-settings') {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    echo "<div style='background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; margin: 10px 0;'>";
    echo "<strong>✅ Settings page detected!</strong><br>";
    echo "Current tab: <strong>$current_tab</strong><br>";
    echo "Current URL: " . esc_url($_SERVER['REQUEST_URI']);
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
    echo "<strong>❌ Not on settings page</strong><br>";
    echo "Current page: " . (isset($_GET['page']) ? esc_html($_GET['page']) : 'N/A');
    echo "</div>";
}

echo "<h3>🎯 Direct Tab Test Links</h3>";
echo "<p>Click these links to test each tab:</p>";

$base_url = admin_url('admin.php?page=lift-docs-settings');
echo "<ul style='list-style: none; padding: 0;'>";
echo "<li style='margin: 10px 0;'><a href='{$base_url}&tab=general' class='button button-secondary'>📋 General Tab</a></li>";
echo "<li style='margin: 10px 0;'><a href='{$base_url}&tab=security' class='button button-secondary'>🔒 Security Tab</a></li>";
echo "<li style='margin: 10px 0;'><a href='{$base_url}&tab=display' class='button button-secondary'>🎨 Display Tab</a></li>";
echo "</ul>";

echo "<h3>⚡ Immediate Fix</h3>";
echo "<p>If tabs still don't work, try this alternative approach:</p>";

echo "<div style='background: #f0f0f1; padding: 15px; border-left: 4px solid #0073aa;'>";
echo "<strong>Alternative Tab Implementation:</strong><br>";
echo "1. Use JavaScript to show/hide tab content instead of page reloads<br>";
echo "2. All settings stay on one form<br>";
echo "3. Better user experience with instant tab switching";
echo "</div>";

// Test database settings
echo "<h3>📊 Current Settings Status</h3>";
$settings = get_option('lift_docs_settings', array());
if (!empty($settings)) {
    echo "<p>✅ Settings found in database (" . count($settings) . " options)</p>";
    echo "<details><summary>View settings</summary>";
    echo "<pre style='background: #f9f9f9; padding: 10px; overflow: auto;'>";
    foreach ($settings as $key => $value) {
        $display_value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo esc_html($key) . " = " . esc_html($display_value) . "\n";
    }
    echo "</pre></details>";
} else {
    echo "<p>❌ No settings found - this might be the issue!</p>";
    echo "<p><strong>Solution:</strong> Save settings at least once to initialize database options.</p>";
}

// JavaScript enhancement
echo "<h3>🚀 Enhanced Tab Functionality</h3>";
echo "<p>Adding enhanced JavaScript for better tab experience:</p>";

?>
<style>
.nav-tab-enhanced {
    background: #0073aa !important;
    color: white !important;
    border: 1px solid #0073aa !important;
}
.nav-tab-enhanced:hover {
    background: #005a87 !important;
    color: white !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('LIFT Docs Settings: Enhanced tab functionality loaded');
    
    // Enhance existing tabs if they exist
    $('.nav-tab').each(function() {
        var $tab = $(this);
        var href = $tab.attr('href');
        
        if (href && href.includes('tab=')) {
            console.log('Found tab:', href);
            
            // Add visual enhancement for current tab
            if (href.includes(window.location.search)) {
                $tab.addClass('nav-tab-enhanced');
            }
            
            // Add click handler for smooth transition
            $tab.on('click', function(e) {
                // Add loading state
                $tab.text($tab.text() + ' ⏳');
                
                // Let default navigation happen
                setTimeout(function() {
                    // This will run after navigation
                    $tab.text($tab.text().replace(' ⏳', ''));
                }, 100);
            });
        }
    });
    
    // If no tabs found, show debug info
    if ($('.nav-tab').length === 0) {
        console.warn('LIFT Docs Settings: No navigation tabs found on page');
        
        // Create debug notice
        $('<div class="notice notice-warning"><p><strong>Debug:</strong> Navigation tabs not found. Check if settings page is properly loaded.</p></div>')
            .insertAfter('.wrap h1');
    } else {
        console.log('LIFT Docs Settings: Found', $('.nav-tab').length, 'navigation tabs');
    }
});
</script>

<?php

echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;'>";
echo "<strong>🔍 Troubleshooting Checklist:</strong><br>";
echo "1. ✅ Check if you can access: <a href='$base_url' target='_blank'>Main Settings Page</a><br>";
echo "2. ✅ Try the direct tab links above<br>";
echo "3. ✅ Check browser console for JavaScript errors (F12)<br>";
echo "4. ✅ Verify parent menu exists in WordPress admin<br>";
echo "5. ✅ Ensure you have 'manage_options' capability<br>";
echo "6. ✅ Check if settings are saved in database";
echo "</div>";
?>
