<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX Login - demomo</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>AJAX Login Test for demomo</h2>
    
    <form id="test-login-form">
        <p>
            <label>Username/Email/Code:</label><br>
            <input type="text" id="login_username" value="demomo" style="width: 300px;">
        </p>
        <p>
            <label>Password:</label><br>
            <input type="password" id="login_password" value="demomo" style="width: 300px;">
        </p>
        <p>
            <input type="checkbox" id="remember_me"> Remember Me
        </p>
        <p>
            <button type="submit">Login via AJAX</button>
        </p>
    </form>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#test-login-form').on('submit', function(e) {
            e.preventDefault();
            
            $('#result').html('<p>Testing login...</p>');
            
            var data = {
                action: 'docs_login',
                username: $('#login_username').val(),
                password: $('#login_password').val(),
                remember_me: $('#remember_me').is(':checked') ? '1' : '0',
                nonce: '<?php echo wp_create_nonce("docs_login_nonce"); ?>'
            };
            
            console.log('Sending data:', data);
            
            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    console.log('Success response:', response);
                    if (response.success) {
                        $('#result').html('<div style="color: green;"><h3>✅ Login Successful!</h3><p>Redirect URL: ' + response.data.redirect_url + '</p></div>');
                        // window.location.href = response.data.redirect_url;
                    } else {
                        $('#result').html('<div style="color: red;"><h3>❌ Login Failed</h3><p>' + response.data + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error response:', xhr.responseText);
                    $('#result').html('<div style="color: red;"><h3>❌ AJAX Error</h3><p>Status: ' + status + '</p><p>Error: ' + error + '</p><p>Response: ' + xhr.responseText + '</p></div>');
                }
            });
        });
    });
    </script>
    
    <?php
    require_once '../../../wp-load.php';
    ?>
</body>
</html>
