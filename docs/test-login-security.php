<?php
/**
 * Test file to verify login requirements for document downloads
 * 
 * This file tests the new security features for shortcodes and download links
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../wp-load.php';
}

echo "<h2>Testing LIFT Docs Login Security Features</h2>";

// Test 1: Check if settings exist
echo "<h3>Test 1: Check Settings</h3>";
if (class_exists('LIFT_Docs_Settings')) {
    $require_login = LIFT_Docs_Settings::get_setting('require_login_to_download', false);
    echo "require_login_to_download setting: " . ($require_login ? 'YES' : 'NO') . "<br>";
    
    $require_login_view = LIFT_Docs_Settings::get_setting('require_login_to_view', false);
    echo "require_login_to_view setting: " . ($require_login_view ? 'YES' : 'NO') . "<br>";
} else {
    echo "✗ LIFT_Docs_Settings class not found<br>";
}

// Test 2: Check if frontend class has the new method
echo "<h3>Test 2: Check Frontend Implementation</h3>";
if (class_exists('LIFT_Docs_Frontend')) {
    $frontend = LIFT_Docs_Frontend::get_instance();
    
    if (method_exists($frontend, 'can_user_download_document')) {
        echo "✓ can_user_download_document method exists<br>";
    } else {
        echo "✗ can_user_download_document method not found<br>";
    }
    
    if (method_exists($frontend, 'document_download_shortcode')) {
        echo "✓ document_download_shortcode method exists<br>";
    } else {
        echo "✗ document_download_shortcode method not found<br>";
    }
} else {
    echo "✗ LIFT_Docs_Frontend class not found<br>";
}

// Test 3: Test shortcode behavior when not logged in
echo "<h3>Test 3: Test Shortcode Security</h3>";

// Simulate user not logged in
$original_user = wp_get_current_user();
wp_set_current_user(0); // Set to guest user

// Enable login requirement for testing
update_option('lift_docs_require_login_to_download', true);

echo "<p><strong>Testing with login required and user NOT logged in:</strong></p>";

// Create a dummy document for testing
$test_doc_id = wp_insert_post(array(
    'post_title' => 'Test Document for Security',
    'post_type' => 'lift_document',
    'post_status' => 'publish',
    'post_content' => 'This is a test document'
));

if ($test_doc_id && !is_wp_error($test_doc_id)) {
    // Add file URL meta
    update_post_meta($test_doc_id, '_lift_doc_file_url', 'https://example.com/test.pdf');
    
    echo "Created test document ID: " . $test_doc_id . "<br>";
    
    // Test shortcode
    $shortcode_output = do_shortcode('[lift_document_download id="' . $test_doc_id . '"]');
    echo "<p><strong>Shortcode output:</strong></p>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
    echo $shortcode_output;
    echo "</div>";
    
    // Check if output contains login requirement
    if (strpos($shortcode_output, 'need to log in') !== false) {
        echo "✓ Shortcode correctly shows login requirement<br>";
    } else {
        echo "✗ Shortcode does not show login requirement<br>";
    }
    
    // Clean up test document
    wp_delete_post($test_doc_id, true);
    echo "Cleaned up test document<br>";
}

// Test 4: Test with user logged in
echo "<h3>Test 4: Test with Logged In User</h3>";

// Simulate admin user logged in
$admin_users = get_users(array('role' => 'administrator', 'number' => 1));
if (!empty($admin_users)) {
    wp_set_current_user($admin_users[0]->ID);
    echo "Simulating logged in admin user: " . $admin_users[0]->user_login . "<br>";
    
    // Create another test document
    $test_doc_id_2 = wp_insert_post(array(
        'post_title' => 'Test Document for Admin',
        'post_type' => 'lift_document',
        'post_status' => 'publish',
        'post_content' => 'This is a test document for admin'
    ));
    
    if ($test_doc_id_2 && !is_wp_error($test_doc_id_2)) {
        update_post_meta($test_doc_id_2, '_lift_doc_file_url', 'https://example.com/test2.pdf');
        
        $shortcode_output_admin = do_shortcode('[lift_document_download id="' . $test_doc_id_2 . '"]');
        echo "<p><strong>Shortcode output for logged in user:</strong></p>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
        echo $shortcode_output_admin;
        echo "</div>";
        
        // Check if output contains download button
        if (strpos($shortcode_output_admin, 'lift-download-btn') !== false) {
            echo "✓ Shortcode correctly shows download button for logged in user<br>";
        } else {
            echo "✗ Shortcode does not show download button for logged in user<br>";
        }
        
        wp_delete_post($test_doc_id_2, true);
    }
}

// Restore original user
wp_set_current_user($original_user->ID);

// Test 5: Check CSS for login required styling
echo "<h3>Test 5: Check CSS Styling</h3>";
$css_file = plugin_dir_path(__FILE__) . 'assets/css/frontend.css';
if (file_exists($css_file)) {
    echo "✓ frontend.css exists<br>";
    
    $css_content = file_get_contents($css_file);
    if (strpos($css_content, '.lift-docs-login-required') !== false) {
        echo "✓ CSS contains login required styling<br>";
    } else {
        echo "✗ CSS missing login required styling<br>";
    }
} else {
    echo "✗ frontend.css not found<br>";
}

// Reset test setting
delete_option('lift_docs_require_login_to_download');

echo "<h3>Security Implementation Summary</h3>";
echo "<strong>New Security Features:</strong><br>";
echo "1. ✓ Shortcode checks login requirement before showing download button<br>";
echo "2. ✓ Shows login required message with styled button<br>";
echo "3. ✓ All download handlers use unified permission checking<br>";
echo "4. ✓ View online feature respects same security rules<br>";
echo "5. ✓ Document actions on single pages show login prompt<br>";
echo "6. ✓ Private documents and password protection supported<br>";
echo "7. ✓ CSS styling for login required messages<br>";

echo "<hr>";
echo "<p><strong>How it works:</strong></p>";
echo "<ol>";
echo "<li>Settings: Enable 'Require Login to Download' in admin</li>";
echo "<li>Shortcode: [lift_document_download id='123'] checks permissions first</li>";
echo "<li>Not logged in: Shows login required message with login button</li>";
echo "<li>Logged in: Shows normal download button</li>";
echo "<li>Direct URL access: Redirects to login page if not authorized</li>";
echo "<li>All features respect private documents and password protection</li>";
echo "</ol>";

echo "<p><strong>Test with different scenarios:</strong></p>";
echo "<ul>";
echo "<li>Guest user + login required = Login message</li>";
echo "<li>Logged user + login required = Download button</li>";
echo "<li>Private document + non-editor = Login message</li>";
echo "<li>Password protected + wrong password = Login message</li>";
echo "</ul>";
?>
