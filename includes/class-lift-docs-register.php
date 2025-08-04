<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * LIFT Docs Registration Handler
 *
 * Handles user registration for Documents User role with email notifications
 */
class LIFT_Docs_Register {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Add rewrite rules for registration page
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_register_page'));

        // AJAX handlers for registration
        add_action('wp_ajax_nopriv_lift_docs_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_lift_docs_register_user', array($this, 'ajax_register_user'));
        
        // AJAX handlers for validation
        add_action('wp_ajax_nopriv_lift_docs_check_username', array($this, 'ajax_check_username'));
        add_action('wp_ajax_lift_docs_check_username', array($this, 'ajax_check_username'));
        add_action('wp_ajax_nopriv_lift_docs_check_email', array($this, 'ajax_check_email'));
        add_action('wp_ajax_lift_docs_check_email', array($this, 'ajax_check_email'));
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'lift_register_page';
        return $vars;
    }

    /**
     * Add custom rewrite rules for registration page
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^document-register/?$', 'index.php?lift_register_page=1', 'top');
        add_rewrite_tag('%lift_register_page%', '([0-9]+)');
    }

    /**
     * Handle registration page requests
     */
    public function handle_register_page() {
        $is_register_request = get_query_var('lift_register_page') ||
                              (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/document-register/') !== false);

        if (!$is_register_request) {
            return;
        }

        // Check if registration is enabled
        if (!get_option('lift_docs_enable_registration', true)) {
            $this->display_registration_disabled_page();
            exit;
        }

        // If user is already logged in, redirect to dashboard
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if (in_array('documents_user', $current_user->roles) || current_user_can('manage_options')) {
                $dashboard_url = home_url('/document-dashboard/');
                wp_safe_redirect($dashboard_url);
                exit;
            }
        }

        // Display registration page
        $this->display_register_page();
        exit;
    }

    /**
     * Display registration disabled page
     */
    private function display_registration_disabled_page() {
        // Get custom colors from login page settings for consistency
        $bg_color = get_option('lift_docs_login_bg_color', '#f0f4f8');
        $container_bg = get_option('lift_docs_login_form_bg', '#ffffff');
        $text_color = get_option('lift_docs_login_text_color', '#333333');
        $btn_color = get_option('lift_docs_login_btn_color', '#1976d2');

        $logo_id = get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('Registration Disabled', 'lift-docs-system'); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: <?php echo esc_attr($bg_color); ?>;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .lift-register-container {
                    width: 100%;
                    max-width: 500px;
                    margin: 20px;
                }

                .lift-register-logo {
                    text-align: center;
                    margin-bottom: 30px;
                }

                .lift-register-logo img {
                    max-width: 200px;
                    max-height: 80px;
                    height: auto;
                }

                .lift-register-wrapper {
                    background: <?php echo esc_attr($container_bg); ?>;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    text-align: center;
                }

                .disabled-icon {
                    font-size: 64px;
                    color: #dc3545;
                    margin-bottom: 20px;
                }

                .disabled-title {
                    font-size: 28px;
                    font-weight: 600;
                    color: #dc3545;
                    margin: 0 0 20px 0;
                }

                .disabled-message {
                    font-size: 16px;
                    color: #666;
                    margin-bottom: 30px;
                    line-height: 1.5;
                }

                .login-link {
                    display: inline-block;
                    background-color: <?php echo esc_attr($btn_color); ?>;
                    color: white;
                    text-decoration: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 500;
                    transition: opacity 0.3s ease;
                }

                .login-link:hover {
                    opacity: 0.9;
                }
            </style>
        </head>
        <body>
            <div class="lift-register-container">

                <?php if ($logo_url): ?>
                <div class="lift-register-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
                </div>
                <?php endif; ?>

                <div class="lift-register-wrapper">
                    <div class="disabled-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>

                    <h1 class="disabled-title"><?php _e('Registration Disabled', 'lift-docs-system'); ?></h1>

                    <p class="disabled-message">
                        <?php _e('Public registration is currently disabled. Please contact the administrator for assistance with accessing documents.', 'lift-docs-system'); ?>
                    </p>

                    <a href="<?php echo home_url('/document-login/'); ?>" class="login-link">
                        <?php _e('Back to Login', 'lift-docs-system'); ?>
                    </a>
                </div>
            </div>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Display registration page
     */
    private function display_register_page() {
        // Get custom colors from login page settings for consistency
        $bg_color = get_option('lift_docs_login_bg_color', '#f0f4f8');
        $container_bg = get_option('lift_docs_login_form_bg', '#ffffff');
        $text_color = get_option('lift_docs_login_text_color', '#333333');
        $input_color = get_option('lift_docs_login_input_color', '#ffffff');
        $btn_color = get_option('lift_docs_login_btn_color', '#1976d2');

        $logo_id = get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

        $page_title = get_option('lift_docs_register_page_title', __('Create Account', 'lift-docs-system'));
        $page_description = get_option('lift_docs_register_page_description', __('Register for document access', 'lift-docs-system'));

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($page_title); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: <?php echo esc_attr($bg_color); ?>;
                    color: <?php echo esc_attr($text_color); ?>;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .lift-register-container {
                    width: 100%;
                    max-width: 500px;
                    margin: 20px;
                }

                .lift-register-logo {
                    text-align: center;
                    margin-bottom: 30px;
                }

                .lift-register-logo img {
                    max-width: 200px;
                    max-height: 80px;
                    height: auto;
                }

                .lift-register-wrapper {
                    background: <?php echo esc_attr($container_bg); ?>;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }

                .lift-register-header {
                    text-align: center;
                    margin-bottom: 30px;
                }

                .lift-register-header h1 {
                    margin: 0 0 10px 0;
                    font-size: 32px;
                    font-weight: 600;
                    color: <?php echo esc_attr($text_color); ?>;
                }

                .lift-register-header p {
                    margin: 0;
                    color: #666;
                    font-size: 16px;
                }

                .register-form {
                    margin-bottom: 20px;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 500;
                    color: <?php echo esc_attr($text_color); ?>;
                }

                .form-group input {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e1e5e9;
                    border-radius: 8px;
                    font-size: 16px;
                    color: <?php echo esc_attr($text_color); ?>;
                    background-color: <?php echo esc_attr($input_color); ?>;
                    transition: border-color 0.3s ease;
                    box-sizing: border-box;
                }

                .form-group input:focus {
                    outline: none;
                    border-color: <?php echo esc_attr($btn_color); ?>;
                    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
                }

                .form-group input.error {
                    border-color: #dc3545;
                }

                .form-group .error-message {
                    color: #dc3545;
                    font-size: 14px;
                    margin-top: 5px;
                    display: none;
                }

                .register-button {
                    width: 100%;
                    padding: 14px;
                    background-color: <?php echo esc_attr($btn_color); ?>;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }

                .register-button:hover {
                    opacity: 0.9;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
                }

                .register-button:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                    box-shadow: none;
                }

                .register-button .spinner {
                    display: none;
                    width: 20px;
                    height: 20px;
                    border: 2px solid #ffffff;
                    border-radius: 50%;
                    border-top-color: transparent;
                    animation: spin 1s ease-in-out infinite;
                    margin-right: 10px;
                }

                .register-button.loading .spinner {
                    display: inline-block;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }

                .register-links {
                    text-align: center;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #e1e5e9;
                }

                .register-links a {
                    color: <?php echo esc_attr($btn_color); ?>;
                    text-decoration: none;
                    font-weight: 500;
                }

                .register-links a:hover {
                    text-decoration: underline;
                }

                .success-message {
                    display: none;
                    padding: 15px;
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    border-radius: 8px;
                    color: #155724;
                    margin-bottom: 20px;
                    position: relative;
                }

                .success-message.redirecting::after {
                    content: '';
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 20px;
                    height: 20px;
                    border: 2px solid #155724;
                    border-radius: 50%;
                    border-top-color: transparent;
                    animation: spin 1s ease-in-out infinite;
                }

                .error-message-global {
                    display: none;
                    padding: 15px;
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    border-radius: 8px;
                    color: #721c24;
                    margin-bottom: 20px;
                }

                .password-requirements {
                    font-size: 14px;
                    color: #666;
                    margin-top: 5px;
                    line-height: 1.4;
                }

                .requirement {
                    display: block;
                    margin-bottom: 2px;
                }

                .requirement.valid {
                    color: #28a745;
                }

                .requirement.invalid {
                    color: #dc3545;
                }

                @media (max-width: 768px) {
                    .lift-register-container {
                        margin: 10px;
                    }

                    .lift-register-wrapper {
                        padding: 30px 20px;
                    }

                    .lift-register-header h1 {
                        font-size: 28px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="lift-register-container">

                <?php if ($logo_url): ?>
                <div class="lift-register-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
                </div>
                <?php endif; ?>

                <div class="lift-register-wrapper">
                    <div class="lift-register-header">
                        <h1><?php echo esc_html($page_title); ?></h1>
                        <p><?php echo esc_html($page_description); ?></p>
                    </div>

                    <div class="success-message" id="success-message"></div>
                    <div class="error-message-global" id="error-message-global"></div>

                    <form class="register-form" id="register-form">
                        <?php wp_nonce_field('lift_docs_register', 'register_nonce'); ?>
                        
                        <div class="form-group">
                            <label for="first_name"><?php _e('First Name', 'lift-docs-system'); ?> <span style="color: #dc3545;">*</span></label>
                            <input type="text" id="first_name" name="first_name" required>
                            <div class="error-message" id="first_name_error"></div>
                        </div>

                        <div class="form-group">
                            <label for="last_name"><?php _e('Last Name', 'lift-docs-system'); ?> <span style="color: #dc3545;">*</span></label>
                            <input type="text" id="last_name" name="last_name" required>
                            <div class="error-message" id="last_name_error"></div>
                        </div>

                        <div class="form-group">
                            <label for="email"><?php _e('Email Address', 'lift-docs-system'); ?> <span style="color: #dc3545;">*</span></label>
                            <input type="email" id="email" name="email" required>
                            <div class="error-message" id="email_error"></div>
                        </div>

                        <div class="form-group">
                            <label for="username"><?php _e('Username', 'lift-docs-system'); ?> <span style="color: #dc3545;">*</span></label>
                            <input type="text" id="username" name="username" required>
                            <div class="error-message" id="username_error"></div>
                        </div>

                        <div class="form-group">
                            <label for="password"><?php _e('Password', 'lift-docs-system'); ?> <span style="color: #dc3545;">*</span></label>
                            <input type="password" id="password" name="password" required>
                            <div class="password-requirements">
                                <span class="requirement" id="length-req"><?php _e('At least 8 characters', 'lift-docs-system'); ?></span>
                                <span class="requirement" id="uppercase-req"><?php _e('At least one uppercase letter', 'lift-docs-system'); ?></span>
                                <span class="requirement" id="lowercase-req"><?php _e('At least one lowercase letter', 'lift-docs-system'); ?></span>
                                <span class="requirement" id="number-req"><?php _e('At least one number', 'lift-docs-system'); ?></span>
                            </div>
                            <div class="error-message" id="password_error"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><?php _e('Confirm Password', 'lift-docs-system'); ?> <span style="color: #dc3545;">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <div class="error-message" id="confirm_password_error"></div>
                        </div>

                        <button type="submit" class="register-button" id="register-submit">
                            <span class="spinner"></span>
                            <?php _e('Create Account', 'lift-docs-system'); ?>
                        </button>
                    </form>

                    <div class="register-links">
                        <p><?php _e('Already have an account?', 'lift-docs-system'); ?> 
                           <a href="<?php echo home_url('/document-login/'); ?>"><?php _e('Sign In', 'lift-docs-system'); ?></a>
                        </p>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('register-form');
                const submitButton = document.getElementById('register-submit');
                const passwordInput = document.getElementById('password');
                const confirmPasswordInput = document.getElementById('confirm_password');

                // Password validation requirements
                const requirements = {
                    length: document.getElementById('length-req'),
                    uppercase: document.getElementById('uppercase-req'),
                    lowercase: document.getElementById('lowercase-req'),
                    number: document.getElementById('number-req')
                };

                // Real-time password validation
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    
                    // Check length
                    if (password.length >= 8) {
                        requirements.length.classList.add('valid');
                        requirements.length.classList.remove('invalid');
                    } else {
                        requirements.length.classList.add('invalid');
                        requirements.length.classList.remove('valid');
                    }

                    // Check uppercase
                    if (/[A-Z]/.test(password)) {
                        requirements.uppercase.classList.add('valid');
                        requirements.uppercase.classList.remove('invalid');
                    } else {
                        requirements.uppercase.classList.add('invalid');
                        requirements.uppercase.classList.remove('valid');
                    }

                    // Check lowercase
                    if (/[a-z]/.test(password)) {
                        requirements.lowercase.classList.add('valid');
                        requirements.lowercase.classList.remove('invalid');
                    } else {
                        requirements.lowercase.classList.add('invalid');
                        requirements.lowercase.classList.remove('valid');
                    }

                    // Check number
                    if (/\d/.test(password)) {
                        requirements.number.classList.add('valid');
                        requirements.number.classList.remove('invalid');
                    } else {
                        requirements.number.classList.add('invalid');
                        requirements.number.classList.remove('valid');
                    }
                });

                // Real-time username validation
                const usernameInput = document.getElementById('username');
                let usernameTimeout;
                
                usernameInput.addEventListener('input', function() {
                    const username = this.value.trim();
                    const errorDiv = document.getElementById('username_error');
                    
                    // Clear previous timeout
                    clearTimeout(usernameTimeout);
                    
                    if (username.length < 3) {
                        return;
                    }
                    
                    // Set timeout to avoid too many requests
                    usernameTimeout = setTimeout(() => {
                        checkUsername(username);
                    }, 500);
                });

                // Real-time email validation
                const emailInput = document.getElementById('email');
                let emailTimeout;
                
                emailInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    
                    if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        checkEmail(email);
                    }
                });

                // Check username availability
                function checkUsername(username) {
                    const formData = new FormData();
                    formData.append('action', 'lift_docs_check_username');
                    formData.append('username', username);

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const input = document.getElementById('username');
                        const errorDiv = document.getElementById('username_error');
                        
                        if (data.success) {
                            input.classList.remove('error');
                            errorDiv.style.display = 'none';
                        } else {
                            input.classList.add('error');
                            errorDiv.textContent = data.data.message;
                            errorDiv.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        // Username check error occurred
                    });
                }

                // Check email availability
                function checkEmail(email) {
                    const formData = new FormData();
                    formData.append('action', 'lift_docs_check_email');
                    formData.append('email', email);

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const input = document.getElementById('email');
                        const errorDiv = document.getElementById('email_error');
                        
                        if (data.success) {
                            input.classList.remove('error');
                            errorDiv.style.display = 'none';
                        } else {
                            input.classList.add('error');
                            errorDiv.textContent = data.data.message;
                            errorDiv.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        // Email check error occurred
                    });
                }

                // Confirm password validation
                confirmPasswordInput.addEventListener('input', function() {
                    const password = passwordInput.value;
                    const confirmPassword = this.value;
                    const errorDiv = document.getElementById('confirm_password_error');

                    if (confirmPassword && password !== confirmPassword) {
                        this.classList.add('error');
                        errorDiv.textContent = '<?php _e('Passwords do not match', 'lift-docs-system'); ?>';
                        errorDiv.style.display = 'block';
                    } else {
                        this.classList.remove('error');
                        errorDiv.style.display = 'none';
                    }
                });

                // Form submission
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Clear previous errors
                    clearErrors();

                    // Validate form
                    if (!validateForm()) {
                        return;
                    }

                    // Show loading state
                    submitButton.disabled = true;
                    submitButton.classList.add('loading');

                    // Prepare form data
                    const formData = new FormData(form);
                    formData.append('action', 'lift_docs_register_user');

                    // Submit via AJAX
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess(data.data.message);
                            form.reset();
                            clearPasswordRequirements();
                            
                            // Add redirecting class for spinner
                            document.getElementById('success-message').classList.add('redirecting');
                            
                            // Redirect to login page after 2 seconds
                            setTimeout(function() {
                                window.location.href = '<?php echo home_url('/document-login/'); ?>';
                            }, 2000);
                        } else {
                            if (data.data && data.data.field_errors) {
                                showFieldErrors(data.data.field_errors);
                            }
                            showError(data.data.message || '<?php _e('Registration failed. Please try again.', 'lift-docs-system'); ?>');
                        }
                    })
                    .catch(error => {
                        // Network error occurred
                        showError('<?php _e('Network error. Please try again.', 'lift-docs-system'); ?>');
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.classList.remove('loading');
                    });
                });

                function validateForm() {
                    let isValid = true;
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;

                    // Password strength validation
                    if (password.length < 8 || 
                        !/[A-Z]/.test(password) || 
                        !/[a-z]/.test(password) || 
                        !/\d/.test(password)) {
                        showFieldError('password', '<?php _e('Password does not meet requirements', 'lift-docs-system'); ?>');
                        isValid = false;
                    }

                    // Password confirmation
                    if (password !== confirmPassword) {
                        showFieldError('confirm_password', '<?php _e('Passwords do not match', 'lift-docs-system'); ?>');
                        isValid = false;
                    }

                    return isValid;
                }

                function clearErrors() {
                    document.querySelectorAll('.error-message').forEach(el => {
                        el.style.display = 'none';
                    });
                    document.querySelectorAll('input.error').forEach(el => {
                        el.classList.remove('error');
                    });
                    document.getElementById('error-message-global').style.display = 'none';
                    document.getElementById('success-message').style.display = 'none';
                }

                function showFieldError(field, message) {
                    const input = document.getElementById(field);
                    const errorDiv = document.getElementById(field + '_error');
                    
                    input.classList.add('error');
                    errorDiv.textContent = message;
                    errorDiv.style.display = 'block';
                }

                function showFieldErrors(errors) {
                    for (const [field, message] of Object.entries(errors)) {
                        showFieldError(field, message);
                    }
                }

                function showError(message) {
                    const errorDiv = document.getElementById('error-message-global');
                    errorDiv.textContent = message;
                    errorDiv.style.display = 'block';
                }

                function showSuccess(message) {
                    const successDiv = document.getElementById('success-message');
                    successDiv.textContent = message;
                    successDiv.style.display = 'block';
                }

                function clearPasswordRequirements() {
                    Object.values(requirements).forEach(req => {
                        req.classList.remove('valid', 'invalid');
                    });
                }
            });
            </script>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * AJAX handler for user registration
     */
    public function ajax_register_user() {
        // Check if registration is enabled
        if (!get_option('lift_docs_enable_registration', true)) {
            wp_send_json_error(array('message' => __('Registration is currently disabled.', 'lift-docs-system')));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['register_nonce'], 'lift_docs_register')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'lift-docs-system')));
        }

        // Sanitize input data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        $errors = array();
        $field_errors = array();

        // Required fields
        if (empty($first_name)) {
            $field_errors['first_name'] = __('First name is required.', 'lift-docs-system');
        }

        if (empty($last_name)) {
            $field_errors['last_name'] = __('Last name is required.', 'lift-docs-system');
        }

        if (empty($email)) {
            $field_errors['email'] = __('Email is required.', 'lift-docs-system');
        } elseif (!is_email($email)) {
            $field_errors['email'] = __('Please enter a valid email address.', 'lift-docs-system');
        } elseif (email_exists($email)) {
            $field_errors['email'] = __('An account with this email already exists.', 'lift-docs-system');
        }

        if (empty($username)) {
            $field_errors['username'] = __('Username is required.', 'lift-docs-system');
        } elseif (username_exists($username)) {
            $field_errors['username'] = __('This username is already taken.', 'lift-docs-system');
        } elseif (!validate_username($username)) {
            $field_errors['username'] = __('Username contains invalid characters.', 'lift-docs-system');
        }

        if (empty($password)) {
            $field_errors['password'] = __('Password is required.', 'lift-docs-system');
        } elseif (strlen($password) < 8) {
            $field_errors['password'] = __('Password must be at least 8 characters long.', 'lift-docs-system');
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $field_errors['password'] = __('Password must contain at least one uppercase letter.', 'lift-docs-system');
        } elseif (!preg_match('/[a-z]/', $password)) {
            $field_errors['password'] = __('Password must contain at least one lowercase letter.', 'lift-docs-system');
        } elseif (!preg_match('/\d/', $password)) {
            $field_errors['password'] = __('Password must contain at least one number.', 'lift-docs-system');
        }

        if ($password !== $confirm_password) {
            $field_errors['confirm_password'] = __('Passwords do not match.', 'lift-docs-system');
        }

        // If there are validation errors, return them
        if (!empty($field_errors)) {
            wp_send_json_error(array(
                'message' => __('Please correct the errors below.', 'lift-docs-system'),
                'field_errors' => $field_errors
            ));
        }

        // Create user
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'documents_user'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Generate user code
        $user_code = $this->generate_unique_user_code();
        update_user_meta($user_id, 'lift_docs_user_code', $user_code);

        // Send welcome email to user
        $this->send_welcome_email($user_id, $user_code);

        // Send notification email to admin
        $this->send_admin_notification_email($user_id, $user_code);

        wp_send_json_success(array(
            'message' => __('Account created successfully! Please check your email for login information. You will be redirected to the login page...', 'lift-docs-system'),
            'user_id' => $user_id
        ));
    }

    /**
     * Generate unique user code
     */
    private function generate_unique_user_code() {
        $attempts = 0;
        $max_attempts = 50;

        do {
            // Generate random code: 6-8 characters, alphanumeric
            $length = rand(6, 8);
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $code = '';
            
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }

            // Check if code already exists
            $existing_users = get_users(array(
                'meta_key' => 'lift_docs_user_code',
                'meta_value' => $code,
                'fields' => 'ID'
            ));

            $attempts++;

        } while (!empty($existing_users) && $attempts < $max_attempts);

        return $code;
    }

    /**
     * Send welcome email to new user
     */
    private function send_welcome_email($user_id, $user_code) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $site_name = get_bloginfo('name');
        $login_url = home_url('/document-login/');
        $dashboard_url = home_url('/document-dashboard/');

        // Email subject
        $subject = sprintf(__('Welcome to %s - Your Document Access Account', 'lift-docs-system'), $site_name);

        // Email content
        $message = $this->get_welcome_email_template(array(
            'user_name' => $user->display_name,
            'first_name' => $user->first_name,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'user_code' => $user_code,
            'login_url' => $login_url,
            'dashboard_url' => $dashboard_url,
            'site_name' => $site_name
        ));

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
        );

        // Send email
        $sent = wp_mail($user->user_email, $subject, $message, $headers);

        return $sent;
    }

    /**
     * Send notification email to admin about new user registration
     */
    private function send_admin_notification_email($user_id, $user_code) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Get notification email (use custom or default admin email)
        $notification_email = get_option('lift_docs_registration_notification_email', '');
        if (empty($notification_email)) {
            $notification_email = get_option('admin_email');
        }

        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=lift-docs-users');
        $user_edit_url = admin_url('user-edit.php?user_id=' . $user_id);

        // Email subject
        $subject = sprintf(__('[%s] New Document User Registration', 'lift-docs-system'), $site_name);

        // Email content
        $message = $this->get_admin_notification_email_template(array(
            'user_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'user_code' => $user_code,
            'registration_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user->user_registered)),
            'admin_url' => $admin_url,
            'user_edit_url' => $user_edit_url,
            'site_name' => $site_name
        ));

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
        );

        // Send email
        $sent = wp_mail($notification_email, $subject, $message, $headers);

        return $sent;
    }

    /**
     * Get admin notification email template
     */
    private function get_admin_notification_email_template($data) {
        $logo_id = get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $primary_color = get_option('lift_docs_login_btn_color', '#1976d2');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($data['site_name']); ?> - New User Registration</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            
                            <!-- Header -->
                            <tr>
                                <td style="background-color: <?php echo esc_attr($primary_color); ?>; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                                    <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($data['site_name']); ?>" style="max-width: 150px; height: auto; margin-bottom: 15px;">
                                    <?php endif; ?>
                                    <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">
                                        <?php _e('New User Registration', 'lift-docs-system'); ?>
                                    </h1>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #333333; margin: 0 0 20px 0; font-size: 24px;">
                                        <?php _e('New Document User Registered', 'lift-docs-system'); ?>
                                    </h2>
                                    
                                    <p style="color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                                        <?php _e('A new user has registered for document access on your website. Here are the details:', 'lift-docs-system'); ?>
                                    </p>

                                    <!-- User Details Box -->
                                    <div style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 25px; margin: 25px 0;">
                                        <h3 style="color: #333333; margin: 0 0 15px 0; font-size: 18px;">
                                            <?php _e('User Information:', 'lift-docs-system'); ?>
                                        </h3>
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Full Name:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['user_name']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Username:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['username']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Email:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['email']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('User Code:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333; font-family: monospace; font-size: 18px; font-weight: 700; background-color: #fff3cd; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                                                    <?php echo esc_html($data['user_code']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Registration Date:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['registration_date']); ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <p style="color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                                        <?php _e('The user has been automatically assigned the "Documents User" role and can now access assigned documents. You may want to assign specific documents to this user or modify their permissions as needed.', 'lift-docs-system'); ?>
                                    </p>

                                    <!-- Action Buttons -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                        <tr>
                                            <td align="center">
                                                <a href="<?php echo esc_url($data['user_edit_url']); ?>" 
                                                   style="display: inline-block; background-color: <?php echo esc_attr($primary_color); ?>; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; margin-right: 10px;">
                                                    <?php _e('Edit User', 'lift-docs-system'); ?>
                                                </a>
                                                <a href="<?php echo esc_url($data['admin_url']); ?>" 
                                                   style="display: inline-block; background-color: #6c757d; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600;">
                                                    <?php _e('Manage Users', 'lift-docs-system'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Quick Actions -->
                                    <div style="background-color: #e3f2fd; border-left: 4px solid <?php echo esc_attr($primary_color); ?>; padding: 20px; margin: 25px 0;">
                                        <h4 style="color: #333333; margin: 0 0 15px 0; font-size: 16px;">
                                            <?php _e('Next Steps:', 'lift-docs-system'); ?>
                                        </h4>
                                        <ul style="color: #666666; margin: 0; padding-left: 20px;">
                                            <li style="margin-bottom: 8px;"><?php _e('Review the user\'s information and verify their identity if needed', 'lift-docs-system'); ?></li>
                                            <li style="margin-bottom: 8px;"><?php _e('Assign appropriate documents to this user', 'lift-docs-system'); ?></li>
                                            <li style="margin-bottom: 8px;"><?php _e('Notify relevant team members about the new user', 'lift-docs-system'); ?></li>
                                            <li style="margin-bottom: 8px;"><?php _e('Update user permissions if necessary', 'lift-docs-system'); ?></li>
                                        </ul>
                                    </div>

                                    <p style="color: #666666; font-size: 14px; line-height: 1.6; margin-top: 30px;">
                                        <?php _e('This is an automated notification sent when a new user registers for document access.', 'lift-docs-system'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e9ecef;">
                                    <p style="color: #999999; font-size: 14px; margin: 0;">
                                        © <?php echo date('Y'); ?> <?php echo esc_html($data['site_name']); ?>. <?php _e('All rights reserved.', 'lift-docs-system'); ?>
                                    </p>
                                    <p style="color: #999999; font-size: 12px; margin: 10px 0 0 0;">
                                        <?php _e('This is an automated message. You can change notification settings in the admin panel.', 'lift-docs-system'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get welcome email template
     */
    private function get_welcome_email_template($data) {
        $logo_id = get_option('lift_docs_login_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $primary_color = get_option('lift_docs_login_btn_color', '#1976d2');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($data['site_name']); ?> - Welcome</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            
                            <!-- Header -->
                            <tr>
                                <td style="background-color: <?php echo esc_attr($primary_color); ?>; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                                    <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($data['site_name']); ?>" style="max-width: 150px; height: auto; margin-bottom: 15px;">
                                    <?php endif; ?>
                                    <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">
                                        <?php printf(__('Welcome to %s!', 'lift-docs-system'), esc_html($data['site_name'])); ?>
                                    </h1>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #333333; margin: 0 0 20px 0; font-size: 24px;">
                                        <?php printf(__('Hi %s,', 'lift-docs-system'), esc_html($data['first_name'])); ?>
                                    </h2>
                                    
                                    <p style="color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                                        <?php _e('Your document access account has been created successfully! You now have access to our secure document management system.', 'lift-docs-system'); ?>
                                    </p>

                                    <!-- Account Details Box -->
                                    <div style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 25px; margin: 25px 0;">
                                        <h3 style="color: #333333; margin: 0 0 15px 0; font-size: 18px;">
                                            <?php _e('Your Account Details:', 'lift-docs-system'); ?>
                                        </h3>
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Full Name:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['user_name']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Username:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['username']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('Email:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333;">
                                                    <?php echo esc_html($data['email']); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-weight: 600;">
                                                    <?php _e('User Code:', 'lift-docs-system'); ?>
                                                </td>
                                                <td style="padding: 8px 0; color: #333333; font-family: monospace; font-size: 18px; font-weight: 700; background-color: #e3f2fd; padding: 5px 10px; border-radius: 4px; display: inline-block;">
                                                    <?php echo esc_html($data['user_code']); ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <p style="color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                                        <?php _e('Your <strong>User Code</strong> is a unique identifier that may be required for document access verification. Please keep it safe and accessible.', 'lift-docs-system'); ?>
                                    </p>

                                    <!-- Action Buttons -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                        <tr>
                                            <td align="center">
                                                <a href="<?php echo esc_url($data['login_url']); ?>" 
                                                   style="display: inline-block; background-color: <?php echo esc_attr($primary_color); ?>; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; margin-right: 10px;">
                                                    <?php _e('Sign In Now', 'lift-docs-system'); ?>
                                                </a>
                                                <a href="<?php echo esc_url($data['dashboard_url']); ?>" 
                                                   style="display: inline-block; background-color: #6c757d; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600;">
                                                    <?php _e('View Dashboard', 'lift-docs-system'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Features List -->
                                    <div style="background-color: #f8f9fa; border-left: 4px solid <?php echo esc_attr($primary_color); ?>; padding: 20px; margin: 25px 0;">
                                        <h4 style="color: #333333; margin: 0 0 15px 0; font-size: 16px;">
                                            <?php _e('What you can do with your account:', 'lift-docs-system'); ?>
                                        </h4>
                                        <ul style="color: #666666; margin: 0; padding-left: 20px;">
                                            <li style="margin-bottom: 8px;"><?php _e('Access and view assigned documents', 'lift-docs-system'); ?></li>
                                            <li style="margin-bottom: 8px;"><?php _e('Download authorized files securely', 'lift-docs-system'); ?></li>
                                            <li style="margin-bottom: 8px;"><?php _e('Fill out and submit document forms', 'lift-docs-system'); ?></li>
                                            <li style="margin-bottom: 8px;"><?php _e('Track your document activity', 'lift-docs-system'); ?></li>
                                        </ul>
                                    </div>

                                    <p style="color: #666666; font-size: 14px; line-height: 1.6; margin-top: 30px;">
                                        <?php _e('If you have any questions or need assistance, please contact our support team.', 'lift-docs-system'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e9ecef;">
                                    <p style="color: #999999; font-size: 14px; margin: 0;">
                                        © <?php echo date('Y'); ?> <?php echo esc_html($data['site_name']); ?>. <?php _e('All rights reserved.', 'lift-docs-system'); ?>
                                    </p>
                                    <p style="color: #999999; font-size: 12px; margin: 10px 0 0 0;">
                                        <?php _e('This is an automated message. Please do not reply to this email.', 'lift-docs-system'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for username availability check
     */
    public function ajax_check_username() {
        $username = sanitize_user($_POST['username'] ?? '');
        
        if (empty($username)) {
            wp_send_json_error(array('message' => __('Username is required.', 'lift-docs-system')));
        }

        if (!validate_username($username)) {
            wp_send_json_error(array('message' => __('Username contains invalid characters.', 'lift-docs-system')));
        }

        if (username_exists($username)) {
            wp_send_json_error(array('message' => __('This username is already taken.', 'lift-docs-system')));
        }

        wp_send_json_success(array('message' => __('Username is available.', 'lift-docs-system')));
    }

    /**
     * AJAX handler for email availability check
     */
    public function ajax_check_email() {
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            wp_send_json_error(array('message' => __('Email is required.', 'lift-docs-system')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'lift-docs-system')));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => __('An account with this email already exists.', 'lift-docs-system')));
        }

        wp_send_json_success(array('message' => __('Email is available.', 'lift-docs-system')));
    }
}

// Initialize the registration system
LIFT_Docs_Register::get_instance();
