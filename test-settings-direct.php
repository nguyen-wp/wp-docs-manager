<?php
/**
 * Simple Settings Page Test
 * Add this to wp-admin to test settings page directly
 */

// Test if we can access settings page directly
echo "<h2>🧪 LIFT Docs Settings Test</h2>";

// Check if we're in admin
if (!is_admin()) {
    echo "<p>❌ Not in admin area. <a href='" . admin_url() . "'>Go to Admin</a></p>";
    return;
}

// Try to create settings instance
if (class_exists('LIFT_Docs_Settings')) {
    echo "<p>✅ Settings class exists</p>";
    
    try {
        $settings = LIFT_Docs_Settings::get_instance();
        echo "<p>✅ Settings instance created</p>";
        
        // Try to call settings page method
        if (method_exists($settings, 'settings_page')) {
            echo "<p>✅ settings_page method exists</p>";
            echo "<hr>";
            echo "<h3>📄 Settings Page Output:</h3>";
            echo "<div style='border: 2px solid #0073aa; padding: 20px; background: #f1f1f1;'>";
            
            // Capture output
            ob_start();
            $settings->settings_page();
            $output = ob_get_clean();
            
            echo $output;
            echo "</div>";
            
        } else {
            echo "<p>❌ settings_page method missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>❌ LIFT_Docs_Settings class not found</p>";
    echo "<p>Include path check:</p>";
    $settings_file = LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-settings.php';
    if (file_exists($settings_file)) {
        echo "<p>✅ Settings file exists at: " . $settings_file . "</p>";
    } else {
        echo "<p>❌ Settings file missing at: " . $settings_file . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>To access real settings page:</strong></p>";
echo "<p><a href='" . admin_url('admin.php?page=lift-docs-settings') . "' class='button button-primary'>🔧 Go to LIFT Docs Settings</a></p>";
?>
