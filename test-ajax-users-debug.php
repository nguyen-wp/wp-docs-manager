<?php
/**
 * Test file to debug AJAX search users functionality
 * Place this in wp-admin/ and access via /wp-admin/test-ajax-users.php
 */

// Load WordPress
require_once('./admin.php');

// Check if user is admin
if (!current_user_can('administrator')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX Search Users</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .result { padding: 10px; margin: 10px 0; background: #f9f9f9; }
        .error { background: #ffebee; color: #c62828; }
        .success { background: #e8f5e8; color: #2e7d32; }
    </style>
</head>
<body>
    <h1>Test AJAX Search Users - LIFT Docs System</h1>
    
    <div class="test-section">
        <h2>Debug Information</h2>
        <ul>
            <li><strong>WP_DEBUG:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></li>
            <li><strong>Current User:</strong> <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</li>
            <li><strong>Can edit posts:</strong> <?php echo current_user_can('edit_posts') ? 'Yes' : 'No'; ?></li>
            <li><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></li>
            <li><strong>Search Nonce:</strong> <?php echo wp_create_nonce('search_document_users'); ?></li>
        </ul>
    </div>
    
    <div class="test-section">
        <h2>Test AJAX Request</h2>
        <button id="test-ajax">Test Search Users AJAX</button>
        <div id="ajax-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>Console Output</h2>
        <p>Check browser console (F12) for detailed debug information.</p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var searchNonce = '<?php echo wp_create_nonce('search_document_users'); ?>';
        
        $('#test-ajax').click(function() {
            var $result = $('#ajax-result');
            $result.show().html('Testing...').removeClass('error success');
            
            console.log('Testing AJAX with data:', {
                action: 'search_document_users',
                search: 'test',
                page: 1,
                nonce: searchNonce
            });
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'search_document_users',
                    search: 'test',
                    page: 1,
                    nonce: searchNonce
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    
                    if (response.success) {
                        $result.addClass('success').html(
                            '<strong>Success!</strong><br>' +
                            'Found ' + (response.data.results ? response.data.results.length : 0) + ' users<br>' +
                            'Total: ' + (response.data.total || 0) + '<br>' +
                            'More: ' + (response.data.more ? 'Yes' : 'No') + '<br>' +
                            '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>'
                        );
                    } else {
                        $result.addClass('error').html(
                            '<strong>Error!</strong><br>' +
                            (response.data || 'Unknown error') + '<br>' +
                            '<pre>' + JSON.stringify(response, null, 2) + '</pre>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                    
                    $result.addClass('error').html(
                        '<strong>AJAX Request Failed!</strong><br>' +
                        'Status: ' + status + '<br>' +
                        'Error: ' + error + '<br>' +
                        'Response: <pre>' + xhr.responseText + '</pre>'
                    );
                }
            });
        });
    });
    </script>
</body>
</html>
