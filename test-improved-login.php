<?php
/**
 * Test Document Login - Improved Version
 */

// Include WordPress
require_once('../../../wp-config.php');

// Handle form submission
if ($_POST && isset($_POST['login_submit'])) {
    // Simulate AJAX login handling
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
    
    $error_message = '';
    $success_message = '';
    
    if (!empty($username) && !empty($password)) {
        // Include the frontend login class
        require_once('class-lift-docs-frontend-login.php');
        $frontend_login = new LIFT_Docs_Frontend_Login();
        
        // Try to find user
        $user = $frontend_login->find_user_by_login($username);
        
        if ($user) {
            // Use wp_signon like WordPress
            $credentials = array(
                'user_login'    => $user->user_login,
                'user_password' => $password,
                'remember'      => $remember
            );
            
            $user_signon = wp_signon($credentials, false);
            
            if (!is_wp_error($user_signon)) {
                // Check document access
                if (in_array('documents_user', $user_signon->roles) || user_can($user_signon->ID, 'view_lift_documents')) {
                    $success_message = "Login successful! Welcome, " . $user_signon->display_name;
                    // Log the login
                    $frontend_login->log_user_login($user_signon->ID);
                } else {
                    wp_logout();
                    $error_message = "You do not have permission to access documents.";
                }
            } else {
                $error_message = $user_signon->get_error_message();
            }
        } else {
            $error_message = "User not found. Please check your credentials.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

get_header();
?>

<style>
/* Remove animations and improve Remember Me styling */
.test-login-container {
    max-width: 400px;
    margin: 50px auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.login-form {
    margin: 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 6px;
    font-size: 16px;
    background: #fff;
    color: #333;
    box-sizing: border-box;
    /* Remove all animations */
    transition: none !important;
    -webkit-transition: none !important;
    -moz-transition: none !important;
    -o-transition: none !important;
}

.form-group input:focus {
    outline: none;
    border-color: #0073aa;
    /* No animation on focus */
    transition: none !important;
}

/* Improved Remember Me styling */
.checkbox-group {
    margin-bottom: 20px;
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.checkbox-wrapper input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0 10px 0 0;
    cursor: pointer;
    /* Use native checkbox styling */
    appearance: auto;
    -webkit-appearance: checkbox;
    -moz-appearance: checkbox;
}

.checkbox-wrapper label {
    margin: 0;
    cursor: pointer;
    font-weight: normal;
    color: #666;
    font-size: 14px;
}

.login-btn {
    width: 100%;
    padding: 14px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    /* Remove animations */
    transition: none !important;
}

.login-btn:hover {
    background: #005a87;
    /* No animation on hover */
    transition: none !important;
}

.message {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.error-message {
    background: #ffebee;
    color: #c62828;
    border-left-color: #c62828;
}

.success-message {
    background: #e8f5e8;
    color: #2e7d32;
    border-left-color: #2e7d32;
}

.login-help {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #666;
}

.login-help a {
    color: #0073aa;
    text-decoration: none;
}

.status-info {
    background: #e3f2fd;
    color: #1976d2;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #1976d2;
}
</style>

<div class="test-login-container">
    <h2>Document Login - Improved Version</h2>
    
    <?php if (is_user_logged_in()): ?>
        <?php $current_user = wp_get_current_user(); ?>
        <div class="success-message">
            You are already logged in as <strong><?php echo esc_html($current_user->display_name); ?></strong>
        </div>
        
        <div class="status-info">
            <strong>User Information:</strong><br>
            Email: <?php echo esc_html($current_user->user_email); ?><br>
            Roles: <?php echo implode(', ', $current_user->roles); ?><br>
            User Code: <?php echo esc_html(get_user_meta($current_user->ID, 'lift_docs_user_code', true)); ?>
        </div>
        
        <p style="text-align: center;">
            <a href="<?php echo home_url('/wp-content/plugins/wp-docs-manager/emergency-dashboard.php'); ?>" class="login-btn" style="display: inline-block; text-decoration: none; width: auto; padding: 10px 20px; margin-right: 10px;">Go to Dashboard</a>
            <a href="<?php echo wp_logout_url(); ?>" class="login-btn" style="display: inline-block; text-decoration: none; width: auto; padding: 10px 20px; background: #666;">Logout</a>
        </p>
        
    <?php else: ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="message error-message">
            <?php echo esc_html($error_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="message success-message">
            <?php echo esc_html($success_message); ?>
        </div>
        <?php endif; ?>
        
        <form method="post" class="login-form">
            <div class="form-group">
                <label for="username">Username, Email, or User Code:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Enter your username, email, or user code"
                       value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <div class="checkbox-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remember" name="remember" value="1" 
                           <?php echo (isset($_POST['remember']) && $_POST['remember']) ? 'checked' : ''; ?>>
                    <label for="remember">Remember me</label>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" name="login_submit" class="login-btn">
                    Sign In
                </button>
            </div>
        </form>
        
        <div class="login-help">
            <a href="<?php echo wp_lostpassword_url(); ?>">Forgot your password?</a>
        </div>
        
    <?php endif; ?>
    
    <p style="text-align: center; margin-top: 20px;">
        <a href="<?php echo home_url(); ?>">← Back to Home</a>
    </p>
</div>

<script>
// Remove any existing animations from inputs
document.addEventListener('DOMContentLoaded', function() {
    var inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
    inputs.forEach(function(input) {
        input.style.transition = 'none';
        input.style.webkitTransition = 'none';
        input.style.mozTransition = 'none';
        input.style.oTransition = 'none';
    });
});
</script>

<?php
get_footer();
?>
