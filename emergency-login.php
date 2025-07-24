<?php
/**
 * Emergency Frontend Login Page
 * Direct implementation without rewrite rules
 */

// Include WordPress
require_once('../../../wp-config.php');

// Get header
get_header();

// Check if this is a login attempt
if ($_POST && isset($_POST['docs_login_submit'])) {
    $username = sanitize_text_field($_POST['docs_username']);
    $password = $_POST['docs_password'];
    
    if (!empty($username) && !empty($password)) {
        // Try to authenticate
        $user = wp_authenticate($username, $password);
        
        if (!is_wp_error($user)) {
            // Check if user has document access
            if (in_array('documents_user', $user->roles) || user_can($user->ID, 'view_lift_documents')) {
                // Log the user in
                wp_clear_auth_cookie();
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, true);
                
                // Redirect to a simple dashboard
                wp_redirect(home_url('/wp-content/plugins/wp-docs-manager/emergency-dashboard.php'));
                exit;
            } else {
                $error_message = "You don't have permission to access documents.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Check if already logged in
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if (in_array('documents_user', $current_user->roles) || current_user_can('view_lift_documents')) {
        wp_redirect(home_url('/wp-content/plugins/wp-docs-manager/emergency-dashboard.php'));
        exit;
    }
}
?>

<style>
.docs-login-container {
    max-width: 400px;
    margin: 50px auto;
    padding: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.docs-login-form .form-group {
    margin-bottom: 20px;
}

.docs-login-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.docs-login-form input[type="text"],
.docs-login-form input[type="password"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.docs-login-form input[type="submit"] {
    width: 100%;
    padding: 12px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

.docs-login-form input[type="submit"]:hover {
    background: #005a87;
}

.error-message {
    background: #ffebe8;
    color: #c62828;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 3px solid #c62828;
}

.info-box {
    background: #e3f2fd;
    color: #1976d2;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 3px solid #1976d2;
}
</style>

<div class="docs-login-container">
    <h2>Document Login</h2>
    
    <?php if (isset($error_message)): ?>
    <div class="error-message">
        <?php echo esc_html($error_message); ?>
    </div>
    <?php endif; ?>
    
    <div class="info-box">
        <strong>Emergency Login Page</strong><br>
        This is a temporary login page while we fix the /docs-login/ URL issue.
    </div>
    
    <form method="post" class="docs-login-form">
        <div class="form-group">
            <label for="docs_username">Username, Email, or User Code:</label>
            <input type="text" id="docs_username" name="docs_username" required 
                   placeholder="Enter your username, email, or user code" 
                   value="<?php echo isset($_POST['docs_username']) ? esc_attr($_POST['docs_username']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="docs_password">Password:</label>
            <input type="password" id="docs_password" name="docs_password" required 
                   placeholder="Enter your password">
        </div>
        
        <div class="form-group">
            <input type="submit" name="docs_login_submit" value="Login">
        </div>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        <a href="<?php echo home_url(); ?>">← Back to Home</a>
    </p>
</div>

<?php
get_footer();
?>
