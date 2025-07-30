<?php
/**
 * Simple AJAX test for search_document_users
 * Access this via: /wp-content/plugins/wp-docs-manager/test-ajax-simple.php
 */

// Load WordPress environment
$wp_config_path = '../../../wp-config.php';
if (file_exists($wp_config_path)) {
    require_once($wp_config_path);
} else {
    die('WordPress not found');
}

// Ensure user is logged in and has admin rights
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_die('Access denied. Please login as administrator.');
}

$nonce = wp_create_nonce('search_document_users');
$ajax_url = admin_url('admin-ajax.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple AJAX Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Simple AJAX Test for search_document_users</h1>
    
    <div>
        <h2>Current Debug Info</h2>
        <ul>
            <li><strong>WP_DEBUG:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'TRUE' : 'FALSE'; ?></li>
            <li><strong>User ID:</strong> <?php echo get_current_user_id(); ?></li>
            <li><strong>User Login:</strong> <?php echo wp_get_current_user()->user_login; ?></li>
            <li><strong>Can edit_posts:</strong> <?php echo current_user_can('edit_posts') ? 'YES' : 'NO'; ?></li>
            <li><strong>Can administrator:</strong> <?php echo current_user_can('administrator') ? 'YES' : 'NO'; ?></li>
            <li><strong>Generated Nonce:</strong> <code><?php echo $nonce; ?></code></li>
            <li><strong>AJAX URL:</strong> <code><?php echo $ajax_url; ?></code></li>
        </ul>
    </div>
    
    <div>
        <h2>Test AJAX Request</h2>
        <button id="test-btn">Send AJAX Request</button>
        <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test-btn').click(function() {
            var $result = $('#result');
            $result.html('Sending request...');
            
            var requestData = {
                action: 'search_document_users',
                search: 'test',
                page: 1,
                nonce: '<?php echo $nonce; ?>'
            };
            
            console.log('Sending AJAX request with data:', requestData);
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX Success Response:', response);
                    $result.html(
                        '<h3>Response:</h3>' +
                        '<pre>' + JSON.stringify(response, null, 2) + '</pre>'
                    );
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                    $result.html(
                        '<h3>AJAX Error:</h3>' +
                        '<strong>Status:</strong> ' + status + '<br>' +
                        '<strong>Error:</strong> ' + error + '<br>' +
                        '<strong>Response Text:</strong><br>' +
                        '<pre>' + xhr.responseText + '</pre>'
                    );
                },
                complete: function(xhr, status) {
                    console.log('AJAX Complete:', {status: status, responseText: xhr.responseText});
                }
            });
        });
    });
    </script>
</body>
</html>
