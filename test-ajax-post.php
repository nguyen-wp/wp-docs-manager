<?php
/**
 * Quick AJAX test for search_document_users with proper POST method
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
    <title>Quick AJAX POST Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Quick AJAX POST Test for search_document_users</h1>
    
    <div>
        <h2>Test Configuration</h2>
        <ul>
            <li><strong>AJAX URL:</strong> <code><?php echo $ajax_url; ?></code></li>
            <li><strong>Nonce:</strong> <code><?php echo $nonce; ?></code></li>
            <li><strong>User ID:</strong> <?php echo get_current_user_id(); ?></li>
            <li><strong>Can edit_posts:</strong> <?php echo current_user_can('edit_posts') ? 'YES' : 'NO'; ?></li>
        </ul>
    </div>
    
    <div>
        <h2>Test POST Request</h2>
        <button id="test-post-btn">Send POST Request</button>
        <div id="post-result" style="margin-top: 20px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test-post-btn').click(function() {
            var $result = $('#post-result');
            $result.html('Sending POST request...');
            
            var requestData = {
                action: 'search_document_users',
                search: 'test',
                page: 1,
                nonce: '<?php echo $nonce; ?>'
            };
            
            console.log('Sending POST request with data:', requestData);
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST', // Explicitly use POST
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    console.log('POST Success Response:', response);
                    
                    if (response.success) {
                        $result.html(
                            '<h3 style="color: green;">SUCCESS!</h3>' +
                            '<p><strong>Found:</strong> ' + (response.data.results ? response.data.results.length : 0) + ' users</p>' +
                            '<p><strong>Total:</strong> ' + (response.data.total || 0) + '</p>' +
                            '<p><strong>More:</strong> ' + (response.data.more ? 'Yes' : 'No') + '</p>' +
                            '<h4>Raw Response:</h4>' +
                            '<pre>' + JSON.stringify(response, null, 2) + '</pre>'
                        );
                    } else {
                        $result.html(
                            '<h3 style="color: red;">ERROR!</h3>' +
                            '<p><strong>Message:</strong> ' + (response.data || 'Unknown error') + '</p>' +
                            '<h4>Raw Response:</h4>' +
                            '<pre>' + JSON.stringify(response, null, 2) + '</pre>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('POST Error:', {xhr: xhr, status: status, error: error});
                    
                    $result.html(
                        '<h3 style="color: red;">AJAX REQUEST FAILED!</h3>' +
                        '<p><strong>Status:</strong> ' + status + '</p>' +
                        '<p><strong>Error:</strong> ' + error + '</p>' +
                        '<h4>Response Text:</h4>' +
                        '<pre>' + xhr.responseText + '</pre>'
                    );
                }
            });
        });
    });
    </script>
</body>
</html>
