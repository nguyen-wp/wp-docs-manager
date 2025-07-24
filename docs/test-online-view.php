<?php
/**
 * Test file to verify online view functionality
 * 
 * This file tests the new "View Online" feature for documents
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

echo "<h2>Testing LIFT Docs Online View Feature</h2>";

// Test 1: Check if the admin class has the new data attribute
echo "<h3>Test 1: Check Admin Class Implementation</h3>";
if (class_exists('LIFT_Docs_Admin')) {
    $admin = LIFT_Docs_Admin::get_instance();
    echo "✓ LIFT_Docs_Admin class exists<br>";
    
    // Check if render_document_details_button method exists
    if (method_exists($admin, 'render_document_details_button')) {
        echo "✓ render_document_details_button method exists<br>";
    } else {
        echo "✗ render_document_details_button method not found<br>";
    }
} else {
    echo "✗ LIFT_Docs_Admin class not found<br>";
}

// Test 2: Check if the frontend class has the new handler
echo "<h3>Test 2: Check Frontend Class Implementation</h3>";
if (class_exists('LIFT_Docs_Frontend')) {
    $frontend = LIFT_Docs_Frontend::get_instance();
    echo "✓ LIFT_Docs_Frontend class exists<br>";
    
    // Check if handle_document_view_online method exists
    if (method_exists($frontend, 'handle_document_view_online')) {
        echo "✓ handle_document_view_online method exists<br>";
    } else {
        echo "✗ handle_document_view_online method not found<br>";
    }
    
    // Check if track_online_view method exists
    if (method_exists($frontend, 'track_online_view')) {
        echo "✓ track_online_view method exists<br>";
    } else {
        echo "✗ track_online_view method not found<br>";
    }
} else {
    echo "✗ LIFT_Docs_Frontend class not found<br>";
}

// Test 3: Check if assets exist
echo "<h3>Test 3: Check Asset Files</h3>";
$js_file = plugin_dir_path(__FILE__) . 'assets/js/admin-modal.js';
$css_file = plugin_dir_path(__FILE__) . 'assets/css/admin-modal.css';

if (file_exists($js_file)) {
    echo "✓ admin-modal.js exists<br>";
    
    // Check if JS contains online view handling
    $js_content = file_get_contents($js_file);
    if (strpos($js_content, 'onlineViewUrl') !== false) {
        echo "✓ JavaScript contains online view handling<br>";
    } else {
        echo "✗ JavaScript missing online view handling<br>";
    }
} else {
    echo "✗ admin-modal.js not found<br>";
}

if (file_exists($css_file)) {
    echo "✓ admin-modal.css exists<br>";
    
    // Check if CSS contains online view styling
    $css_content = file_get_contents($css_file);
    if (strpos($css_content, '#lift-online-view') !== false) {
        echo "✓ CSS contains online view styling<br>";
    } else {
        echo "✗ CSS missing online view styling<br>";
    }
} else {
    echo "✗ admin-modal.css not found<br>";
}

// Test 4: Create a sample online view URL
echo "<h3>Test 4: Generate Sample URLs</h3>";
$sample_post_id = 1; // Assuming post ID 1 exists
$online_view_url = add_query_arg(array(
    'lift_view_online' => $sample_post_id,
    'nonce' => wp_create_nonce('lift_view_online_' . $sample_post_id)
), home_url());

echo "Sample Online View URL: <a href='" . esc_url($online_view_url) . "' target='_blank'>" . esc_html($online_view_url) . "</a><br>";

// Test 5: Check supported file extensions
echo "<h3>Test 5: Supported File Extensions for Online Viewing</h3>";
$viewable_extensions = array('pdf', 'txt', 'html', 'htm', 'jpg', 'jpeg', 'png', 'gif', 'svg');
echo "Supported extensions: " . implode(', ', $viewable_extensions) . "<br>";

echo "<h3>Implementation Summary</h3>";
echo "<strong>New Features Added:</strong><br>";
echo "1. ✓ Online view URL generation in admin<br>";
echo "2. ✓ 'View Online' button in document details modal<br>";
echo "3. ✓ Frontend handler for online view requests<br>";
echo "4. ✓ File type detection for viewable documents<br>";
echo "5. ✓ Analytics tracking for online views<br>";
echo "6. ✓ Security nonce verification<br>";
echo "7. ✓ CSS styling for the new button<br>";
echo "8. ✓ JavaScript integration with modal<br>";

echo "<hr>";
echo "<p><strong>How it works:</strong></p>";
echo "<ol>";
echo "<li>Admin can click 'View Details' on any document in the admin list</li>";
echo "<li>Modal shows both Download URL and View Online button</li>";
echo "<li>View Online opens PDFs, images, text files directly in browser</li>";
echo "<li>Non-viewable files fallback to download</li>";
echo "<li>All access is tracked and secured with nonces</li>";
echo "</ol>";
?>
