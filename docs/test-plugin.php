<?php
// Test file to check for errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate WordPress environment
define('ABSPATH', '/Users/nguyenpham/Source Code/demo/');
define('WP_DEBUG', true);

// Include the main plugin file
include_once 'lift-docs-system.php';

echo "Plugin loaded successfully!\n";

// Test class instances
try {
    $plugin = LIFT_Docs_System::get_instance();
    echo "Main plugin class: OK\n";
    
    if (class_exists('LIFT_Docs_Layout')) {
        echo "Layout class: OK\n";
    } else {
        echo "Layout class: MISSING\n";
    }
    
    if (class_exists('LIFT_Docs_Settings')) {
        echo "Settings class: OK\n";
    } else {
        echo "Settings class: MISSING\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
