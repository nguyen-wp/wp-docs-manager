<?php
/**
 * Test File - Field Clear Functionality
 * 
 * This file is used to test the enhanced field clearing functionality
 * for File Upload and Signature fields.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Test function to verify field clearing works correctly
function test_field_clear_functionality() {
    ?>
    <div class="wrap">
        <h1>Test Field Clear Functionality</h1>
        
        <div class="notice notice-info">
            <p><strong>Test Instructions:</strong></p>
            <ul>
                <li>1. Upload a file using the file upload field</li>
                <li>2. Draw a signature using the signature field</li>
                <li>3. Check that hidden inputs are created (use browser dev tools)</li>
                <li>4. Click remove/clear buttons</li>
                <li>5. Verify that ALL related input values are cleared</li>
            </ul>
        </div>

        <form class="lift-form" method="post">
            <div class="lift-form-field lift-field-file">
                <label for="test_file">Test File Upload</label>
                <input type="file" id="test_file" name="test_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
            </div>

            <div class="lift-form-field lift-field-signature">
                <label for="test_signature">Test Signature</label>
                <input type="hidden" id="test_signature" name="test_signature" class="form-control">
            </div>

            <button type="submit" class="button button-primary">Test Submit</button>
        </form>

        <div id="test-output">
            <h3>Debug Information</h3>
            <p>Check browser console for debugging messages and inspect form inputs using developer tools.</p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Monitor form changes for testing
            $('.lift-form').on('change', 'input', function() {
                console.log('Input changed:', $(this).attr('name'), '=', $(this).val());
            });

            // Log all inputs in the form every 2 seconds for testing
            setInterval(function() {
                console.log('=== Current Form State ===');
                $('.lift-form input').each(function() {
                    if ($(this).attr('name')) {
                        console.log($(this).attr('name') + ' (' + $(this).attr('type') + '):', $(this).val());
                    }
                });
                console.log('=== End Form State ===');
            }, 2000);
        });
        </script>
    </div>
    <?php
}

// Add admin menu for testing (only in development)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'lift-docs-settings',
            'Test Field Clear',
            'Test Field Clear',
            'manage_options',
            'test-field-clear',
            'test_field_clear_functionality'
        );
    });
}
?>
