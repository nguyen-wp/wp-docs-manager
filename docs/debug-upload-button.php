<?php
/**
 * Test Upload Button Functionality
 * 
 * This script helps debug the upload logo button issue
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

echo "<h1>🔧 Upload Button Debug Test</h1>";

// Test 1: Check if we're on the right page
echo "<h2>Test 1: Page Context Check</h2>";

$current_page = isset($_GET['page']) ? $_GET['page'] : '';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

echo "<p><strong>Current Page:</strong> " . esc_html($current_page) . "</p>";
echo "<p><strong>Current Tab:</strong> " . esc_html($current_tab) . "</p>";

if ($current_page === 'lift-docs-settings') {
    echo "✅ We're on the LIFT Docs settings page<br>";
} else {
    echo "❌ Not on LIFT Docs settings page<br>";
    echo "📝 Visit: <a href='" . admin_url('admin.php?page=lift-docs-settings') . "'>LIFT Docs Settings</a><br>";
}

// Test 2: Check if jQuery and WordPress media scripts are loaded
echo "<h2>Test 2: Script Dependencies</h2>";

global $wp_scripts;
if (is_object($wp_scripts)) {
    $enqueued_scripts = array_keys($wp_scripts->queue);
    
    $required_scripts = ['jquery', 'media-upload', 'wp-media', 'wp-color-picker'];
    
    foreach ($required_scripts as $script) {
        if (in_array($script, $enqueued_scripts)) {
            echo "✅ $script is enqueued<br>";
        } else {
            echo "❌ $script may not be enqueued<br>";
        }
    }
} else {
    echo "❌ Cannot check script dependencies<br>";
}

// Test 3: Check if upload button exists in DOM
echo "<h2>Test 3: Upload Button Elements</h2>";
?>

<div style="border: 2px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;">
    <h3>🧪 Test Upload Button</h3>
    
    <!-- Test upload button exactly like in settings -->
    <div class="lift-logo-upload-container">
        <input type="hidden" name="lift_docs_login_logo" id="lift_docs_login_logo" value="">
        
        <div class="logo-preview" style="margin-bottom: 10px;">
            <div id="logo-preview-img" style="width: 200px; height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;">No logo selected</div>
        </div>
        
        <button type="button" class="button" id="upload-logo-btn">Select Logo</button>
        <button type="button" class="button" id="remove-logo-btn" style="display: none;">Remove Logo</button>
    </div>
    
    <div style="margin-top: 15px;">
        <button type="button" class="button button-primary" id="test-click-btn">Test Click Handler</button>
        <button type="button" class="button button-secondary" id="debug-console-btn">Debug Console</button>
    </div>
</div>

<script type="text/javascript">
console.log('🔧 Upload Button Debug Script Loading...');

jQuery(document).ready(function($) {
    console.log('✅ jQuery ready, testing upload button...');
    
    // Check if elements exist
    var uploadBtn = $('#upload-logo-btn');
    var removeBtn = $('#remove-logo-btn');
    var logoInput = $('#lift_docs_login_logo');
    var logoPreview = $('#logo-preview-img');
    
    console.log('Upload button found:', uploadBtn.length > 0);
    console.log('Remove button found:', removeBtn.length > 0);
    console.log('Logo input found:', logoInput.length > 0);
    console.log('Logo preview found:', logoPreview.length > 0);
    
    // Check if wp.media exists
    if (typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
        console.log('✅ wp.media is available');
    } else {
        console.log('❌ wp.media is NOT available');
        alert('❌ WordPress Media Library is not loaded. Make sure wp_enqueue_media() is called.');
    }
    
    // Test click handler
    $('#test-click-btn').on('click', function() {
        console.log('🧪 Test button clicked');
        alert('Test button working! Now testing upload button...');
        
        // Trigger upload button
        $('#upload-logo-btn').trigger('click');
    });
    
    // Debug console button
    $('#debug-console-btn').on('click', function() {
        console.log('🔍 Debug Info:');
        console.log('- wp object:', typeof wp);
        console.log('- wp.media:', typeof wp.media);
        console.log('- jQuery version:', $.fn.jquery);
        console.log('- Upload button element:', uploadBtn[0]);
        console.log('- Upload button events:', $._data(uploadBtn[0], 'events'));
    });
    
    // Add manual upload button handler for testing
    $(document).on('click', '#upload-logo-btn', function(e) {
        e.preventDefault();
        console.log('🖼️ Upload logo button clicked - DEBUG VERSION');
        
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('❌ WordPress Media Library not loaded!\n\nMake sure you\'re on the admin settings page and wp_enqueue_media() is called.');
            return;
        }
        
        var mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Logo - DEBUG TEST',
            button: { text: 'Choose Logo' },
            multiple: false,
            library: { type: 'image' }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            console.log('✅ Logo selected:', attachment);
            
            $('#lift_docs_login_logo').val(attachment.id);
            $('#logo-preview-img').html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;">');
            $('#remove-logo-btn').show();
            
            alert('✅ Logo selected successfully!\nID: ' + attachment.id + '\nURL: ' + attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Remove button handler
    $(document).on('click', '#remove-logo-btn', function(e) {
        e.preventDefault();
        console.log('🗑️ Remove logo button clicked');
        
        $('#lift_docs_login_logo').val('');
        $('#logo-preview-img').html('<div style="width: 200px; height: 100px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;">No logo selected</div>');
        $(this).hide();
        
        alert('🗑️ Logo removed successfully!');
    });
});
</script>

<style>
    /* Remove all animations for testing */
    *, *::before, *::after {
        transition: none !important;
        animation: none !important;
        -webkit-transition: none !important;
        -webkit-animation: none !important;
    }
    
    .button {
        margin-right: 10px;
    }
    
    .lift-logo-upload-container {
        background: white;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
</style>

<?php
echo "<h2>🔧 Debug Instructions</h2>";
echo "<ol>";
echo "<li>1. Click 'Test Click Handler' to verify JavaScript is working</li>";
echo "<li>2. Click 'Debug Console' to see technical details in browser console</li>";
echo "<li>3. Click 'Select Logo' to test the upload functionality</li>";
echo "<li>4. Check browser console (F12) for detailed error messages</li>";
echo "</ol>";

echo "<h2>📋 Troubleshooting Checklist</h2>";
echo "<ul>";
echo "<li>✅ Make sure you're logged in as an admin</li>";
echo "<li>✅ Ensure you're on the LIFT Docs settings page</li>";
echo "<li>✅ Check that wp_enqueue_media() is called</li>";
echo "<li>✅ Verify jQuery is loaded</li>";
echo "<li>✅ Look for JavaScript errors in browser console</li>";
echo "</ul>";

echo "<p><strong>Test File:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
