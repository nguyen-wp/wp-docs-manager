<?php
/**
 * Simple Non-AJAX Login Test for demomo
 */

// Include WordPress
require_once('../../../wp-config.php');

// Handle form submission
if ($_POST && isset($_POST['login_submit'])) {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
    
    $error_message = '';
    $success_message = '';
    
    if (!empty($username) && !empty($password)) {
        echo "<h3>🔍 Login Process Debug:</h3>\n";
        
        // Step 1: Find user
        echo "<p><strong>Step 1:</strong> Finding user '$username'</p>\n";
        
        // Try username first
        $user = get_user_by('login', $username);
        if ($user) {
            echo "<p>✅ Found by username</p>\n";
        } else {
            // Try email
            $user = get_user_by('email', $username);
            if ($user) {
                echo "<p>✅ Found by email</p>\n";
            } else {
                // Try user code
                $users = get_users(array(
                    'meta_key' => 'lift_docs_user_code',
                    'meta_value' => $username,
                    'meta_compare' => '=',
                    'number' => 1
                ));
                if (!empty($users)) {
                    $user = $users[0];
                    echo "<p>✅ Found by user code</p>\n";
                } else {
                    echo "<p>❌ User not found by any method</p>\n";
                }
            }
        }
        
        if ($user) {
            echo "<p><strong>Step 2:</strong> User found - ID: {$user->ID}, Username: {$user->user_login}</p>\n";
            
            // Step 3: Check password
            echo "<p><strong>Step 3:</strong> Checking password</p>\n";
            $password_check = wp_check_password($password, $user->user_pass);
            if ($password_check) {
                echo "<p>✅ Password is correct</p>\n";
                
                // Step 4: Check document access
                echo "<p><strong>Step 4:</strong> Checking document access</p>\n";
                $has_access = in_array('documents_user', $user->roles) || user_can($user->ID, 'view_lift_documents');
                if ($has_access) {
                    echo "<p>✅ User has document access</p>\n";
                    
                    // Step 5: Login
                    echo "<p><strong>Step 5:</strong> Logging in user</p>\n";
                    $credentials = array(
                        'user_login'    => $user->user_login,
                        'user_password' => $password,
                        'remember'      => $remember
                    );
                    
                    $user_signon = wp_signon($credentials, false);
                    
                    if (is_wp_error($user_signon)) {
                        echo "<p>❌ wp_signon failed: " . $user_signon->get_error_message() . "</p>\n";
                        $error_message = $user_signon->get_error_message();
                    } else {
                        echo "<p>✅ wp_signon successful!</p>\n";
                        $success_message = "Login successful! Welcome, " . $user_signon->display_name;
                        
                        // Check if actually logged in
                        if (is_user_logged_in()) {
                            echo "<p>✅ WordPress confirms user is logged in</p>\n";
                        } else {
                            echo "<p>❌ WordPress says user is NOT logged in</p>\n";
                        }
                    }
                } else {
                    echo "<p>❌ User does NOT have document access</p>\n";
                    echo "<p>User roles: " . implode(', ', $user->roles) . "</p>\n";
                    $error_message = "You do not have permission to access documents.";
                }
            } else {
                echo "<p>❌ Password is incorrect</p>\n";
                $error_message = "Invalid password.";
            }
        } else {
            echo "<p>❌ User not found</p>\n";
            $error_message = "User not found.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

get_header();
?>

<style>
.test-container {
    max-width: 600px;
    margin: 50px auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.login-btn {
    width: 100%;
    padding: 14px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

.login-btn:hover {
    background: #005a87;
}

.message {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.error {
    background: #ffebee;
    color: #c62828;
    border-left: 4px solid #c62828;
}

.success {
    background: #e8f5e8;
    color: #2e7d32;
    border-left: 4px solid #2e7d32;
}

.checkbox-group {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.checkbox-group input {
    margin-right: 8px;
}
</style>

<div class="test-container">
    <h2>🧪 Non-AJAX Login Test for demomo</h2>
    
    <?php if (is_user_logged_in()): ?>
        <?php $current_user = wp_get_current_user(); ?>
        <div class="message success">
            ✅ You are logged in as: <strong><?php echo esc_html($current_user->display_name); ?></strong>
        </div>
        
        <p>
            <a href="<?php echo home_url('/wp-content/plugins/wp-docs-manager/emergency-dashboard.php'); ?>" class="login-btn" style="display: inline-block; text-decoration: none; width: auto; padding: 10px 20px; margin-right: 10px;">Go to Dashboard</a>
            <a href="<?php echo wp_logout_url(); ?>" class="login-btn" style="display: inline-block; text-decoration: none; width: auto; padding: 10px 20px; background: #666;">Logout</a>
        </p>
        
    <?php else: ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="message error">
            ❌ <?php echo esc_html($error_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="message success">
            ✅ <?php echo esc_html($success_message); ?>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username, Email, or User Code:</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : 'demomo'; ?>"
                       placeholder="Try: demomo">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       value="<?php echo isset($_POST['password']) ? esc_attr($_POST['password']) : 'demomo'; ?>"
                       placeholder="Try: demomo">
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" value="1" 
                       <?php echo (isset($_POST['remember']) && $_POST['remember']) ? 'checked' : ''; ?>>
                <label for="remember">Remember me</label>
            </div>
            
            <div class="form-group">
                <button type="submit" name="login_submit" class="login-btn">
                    Test Login
                </button>
            </div>
        </form>
        
    <?php endif; ?>
    
    <hr style="margin: 30px 0;">
    
    <h3>📋 User Info Check</h3>
    <?php
    $demomo_user = get_user_by('login', 'demomo');
    if ($demomo_user) {
        echo "<ul>\n";
        echo "<li><strong>ID:</strong> " . $demomo_user->ID . "</li>\n";
        echo "<li><strong>Username:</strong> " . $demomo_user->user_login . "</li>\n";
        echo "<li><strong>Email:</strong> " . $demomo_user->user_email . "</li>\n";
        echo "<li><strong>Roles:</strong> " . implode(', ', $demomo_user->roles) . "</li>\n";
        echo "<li><strong>User Code:</strong> " . get_user_meta($demomo_user->ID, 'lift_docs_user_code', true) . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p>❌ User 'demomo' not found</p>\n";
    }
    ?>
    
    <h3>🔗 Other Tests</h3>
    <ul>
        <li><a href="<?php echo home_url('/docs-login'); ?>" target="_blank">Official /docs-login page</a></li>
        <li><a href="<?php echo home_url('/wp-content/plugins/wp-docs-manager/test-improved-login.php'); ?>" target="_blank">Improved login test</a></li>
        <li><a href="<?php echo home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php'); ?>" target="_blank">Emergency login</a></li>
    </ul>
</div>

<?php
get_footer();
?>
