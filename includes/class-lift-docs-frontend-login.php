<?php
/**
 * Frontend Login System for Lift Documents
 * 
 * Handles custom login page and user authentication
 */

if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Frontend_Login {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_nopriv_docs_login', array($this, 'handle_ajax_login'));
        add_action('wp_ajax_docs_login', array($this, 'handle_ajax_login'));
        add_action('wp_ajax_nopriv_docs_logout', array($this, 'handle_ajax_logout'));
        add_action('wp_ajax_docs_logout', array($this, 'handle_ajax_logout'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_docs_login_page'));
        add_action('template_redirect', array($this, 'handle_docs_dashboard_page'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Add rewrite rules for docs-login page
        add_rewrite_rule('^docs-login/?$', 'index.php?docs_login=1', 'top');
        add_rewrite_rule('^docs-dashboard/?$', 'index.php?docs_dashboard=1', 'top');
        
        // Flush rewrite rules if needed
        if (!get_option('lift_docs_rewrite_rules_flushed')) {
            flush_rewrite_rules();
            update_option('lift_docs_rewrite_rules_flushed', true);
        }
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'docs_login';
        $vars[] = 'docs_dashboard';
        return $vars;
    }
    
    /**
     * Handle docs-login page
     */
    public function handle_docs_login_page() {
        if (get_query_var('docs_login')) {
            // Check if user is already logged in and has docs access
            if (is_user_logged_in() && $this->user_has_docs_access()) {
                wp_redirect(home_url('/docs-dashboard'));
                exit;
            }
            
            $this->display_login_page();
            exit;
        }
    }
    
    /**
     * Handle docs-dashboard page
     */
    public function handle_docs_dashboard_page() {
        if (get_query_var('docs_dashboard')) {
            // Check if user is logged in and has docs access
            if (!is_user_logged_in() || !$this->user_has_docs_access()) {
                wp_redirect(home_url('/docs-login'));
                exit;
            }
            
            $this->display_dashboard_page();
            exit;
        }
    }
    
    /**
     * Check if current user has document access
     */
    private function user_has_docs_access() {
        return current_user_can('view_lift_documents') || 
               in_array('documents_user', wp_get_current_user()->roles);
    }
    
    /**
     * Display login page
     */
    private function display_login_page() {
        // Get theme header
        get_header();
        
        ?>
        <div class="lift-docs-login-container">
            <div class="lift-docs-login-wrapper">
                <div class="lift-docs-login-header">
                    <h1><?php _e('Documents Login', 'lift-docs-system'); ?></h1>
                    <p class="description"><?php _e('Access your personal document library', 'lift-docs-system'); ?></p>
                </div>
                
                <div class="lift-docs-login-form-container">
                    <form id="lift-docs-login-form" class="lift-docs-login-form">
                        <?php wp_nonce_field('docs_login_nonce', 'docs_login_nonce'); ?>
                        
                        <div class="lift-form-group">
                            <label for="docs_username"><?php _e('Username, Email or User Code', 'lift-docs-system'); ?></label>
                            <input type="text" id="docs_username" name="username" 
                                   placeholder="<?php _e('Enter username, email or user code...', 'lift-docs-system'); ?>" 
                                   required autocomplete="username">
                            <small class="form-hint"><?php _e('You can use your username, email address, or your unique user code', 'lift-docs-system'); ?></small>
                        </div>
                        
                        <div class="lift-form-group">
                            <label for="docs_password"><?php _e('Password', 'lift-docs-system'); ?></label>
                            <div class="password-field-wrapper">
                                <input type="password" id="docs_password" name="password" 
                                       placeholder="<?php _e('Enter your password...', 'lift-docs-system'); ?>" 
                                       required autocomplete="current-password">
                                <button type="button" class="toggle-password" tabindex="-1">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="lift-form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="docs_remember" name="remember" value="1">
                                <span class="checkmark"></span>
                                <?php _e('Remember me', 'lift-docs-system'); ?>
                            </label>
                        </div>
                        
                        <div class="lift-form-group">
                            <button type="submit" class="lift-login-btn">
                                <span class="btn-text"><?php _e('Sign In', 'lift-docs-system'); ?></span>
                                <span class="btn-spinner" style="display: none;">
                                    <span class="spinner"></span>
                                    <?php _e('Signing in...', 'lift-docs-system'); ?>
                                </span>
                            </button>
                        </div>
                        
                        <div class="lift-form-messages">
                            <div class="login-error" style="display: none;"></div>
                            <div class="login-success" style="display: none;"></div>
                        </div>
                    </form>
                    
                    <div class="lift-docs-login-footer">
                        <p class="login-help">
                            <?php _e('Need help accessing your documents?', 'lift-docs-system'); ?>
                            <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password-link">
                                <?php _e('Reset Password', 'lift-docs-system'); ?>
                            </a>
                        </p>
                        
                        <?php if (get_option('users_can_register')): ?>
                        <p class="register-link">
                            <?php _e("Don't have an account?", 'lift-docs-system'); ?>
                            <a href="<?php echo wp_registration_url(); ?>">
                                <?php _e('Request Access', 'lift-docs-system'); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="lift-docs-features">
                    <h3><?php _e('What you can access:', 'lift-docs-system'); ?></h3>
                    <ul class="features-list">
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Personal document library', 'lift-docs-system'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Secure document downloads', 'lift-docs-system'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Online document viewer', 'lift-docs-system'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Document access history', 'lift-docs-system'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php
        // Get theme footer
        get_footer();
    }
    
    /**
     * Display dashboard page
     */
    private function display_dashboard_page() {
        // Get theme header
        get_header();
        
        $current_user = wp_get_current_user();
        $user_code = get_user_meta($current_user->ID, 'lift_docs_user_code', true);
        
        // Get user's assigned documents
        $user_documents = $this->get_user_documents($current_user->ID);
        
        ?>
        <div class="lift-docs-dashboard-container">
            <div class="lift-docs-dashboard-wrapper">
                <!-- Dashboard Header -->
                <div class="lift-docs-dashboard-header">
                    <div class="dashboard-user-info">
                        <h1><?php printf(__('Welcome, %s', 'lift-docs-system'), esc_html($current_user->display_name)); ?></h1>
                        <div class="user-meta">
                            <span class="user-email"><?php echo esc_html($current_user->user_email); ?></span>
                            <?php if ($user_code): ?>
                                <span class="user-code"><?php _e('Code:', 'lift-docs-system'); ?> <strong><?php echo esc_html($user_code); ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dashboard-actions">
                        <button type="button" id="docs-logout-btn" class="logout-btn">
                            <span class="dashicons dashicons-exit"></span>
                            <?php _e('Logout', 'lift-docs-system'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Dashboard Content -->
                <div class="lift-docs-dashboard-content">
                    <!-- Quick Stats -->
                    <div class="dashboard-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-media-document"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo count($user_documents); ?></h3>
                                <p><?php _e('Available Documents', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $this->get_user_download_count($current_user->ID); ?></h3>
                                <p><?php _e('Total Downloads', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $this->get_user_view_count($current_user->ID); ?></h3>
                                <p><?php _e('Total Views', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo date_i18n('M d', strtotime($current_user->user_registered)); ?></h3>
                                <p><?php _e('Member Since', 'lift-docs-system'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Library -->
                    <div class="documents-section">
                        <div class="section-header">
                            <h2><?php _e('Your Document Library', 'lift-docs-system'); ?></h2>
                            <div class="section-controls">
                                <input type="text" id="docs-search" placeholder="<?php _e('Search documents...', 'lift-docs-system'); ?>">
                                <select id="docs-filter">
                                    <option value="all"><?php _e('All Documents', 'lift-docs-system'); ?></option>
                                    <option value="recent"><?php _e('Recently Added', 'lift-docs-system'); ?></option>
                                    <option value="downloaded"><?php _e('Downloaded', 'lift-docs-system'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <?php if (!empty($user_documents)): ?>
                            <div class="documents-grid" id="documents-grid">
                                <?php foreach ($user_documents as $document): ?>
                                    <?php $this->render_document_card($document, $current_user->ID); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-documents">
                                <div class="no-documents-icon">
                                    <span class="dashicons dashicons-portfolio"></span>
                                </div>
                                <h3><?php _e('No Documents Available', 'lift-docs-system'); ?></h3>
                                <p><?php _e('You don\'t have access to any documents yet. Contact your administrator for access.', 'lift-docs-system'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="activity-section">
                        <h2><?php _e('Recent Activity', 'lift-docs-system'); ?></h2>
                        <div class="activity-list">
                            <?php $this->render_recent_activity($current_user->ID); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Document Modal -->
        <div id="lift-document-modal" class="lift-modal" style="display: none;">
            <div class="lift-modal-content">
                <div class="lift-modal-header">
                    <h2 id="modal-document-title"><?php _e('Document Details', 'lift-docs-system'); ?></h2>
                    <button type="button" class="lift-modal-close">&times;</button>
                </div>
                <div class="lift-modal-body">
                    <div id="modal-document-content"></div>
                </div>
            </div>
        </div>
        <div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>
        
        <?php
        // Get theme footer
        get_footer();
    }
    
    /**
     * Get documents assigned to user
     */
    private function get_user_documents($user_id) {
        // Get all published documents
        $all_documents = get_posts(array(
            'post_type' => 'lift_document',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $user_documents = array();
        
        foreach ($all_documents as $document) {
            $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
            
            // If no specific assignments, document is available to all document users
            if (empty($assigned_users) || !is_array($assigned_users)) {
                $user_documents[] = $document;
            } 
            // Check if user is specifically assigned
            else if (in_array($user_id, $assigned_users)) {
                $user_documents[] = $document;
            }
        }
        
        return $user_documents;
    }
    
    /**
     * Get user download count
     */
    private function get_user_download_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND action = 'download'",
            $user_id
        ));
        
        return $count ? $count : 0;
    }
    
    /**
     * Get user view count
     */
    private function get_user_view_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND action = 'view'",
            $user_id
        ));
        
        return $count ? $count : 0;
    }
    
    /**
     * Render document card
     */
    private function render_document_card($document, $user_id) {
        $file_urls = get_post_meta($document->ID, '_lift_doc_file_urls', true);
        if (empty($file_urls)) {
            $file_urls = array(get_post_meta($document->ID, '_lift_doc_file_url', true));
        }
        $file_urls = array_filter($file_urls);
        
        $file_count = count($file_urls);
        $views = get_post_meta($document->ID, '_lift_doc_views', true);
        $downloads = get_post_meta($document->ID, '_lift_doc_downloads', true);
        
        // Check if user has downloaded this document
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        $user_downloaded = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE document_id = %d AND user_id = %d AND action = 'download'",
            $document->ID, $user_id
        ));
        
        ?>
        <div class="document-card" data-document-id="<?php echo $document->ID; ?>">
            <div class="document-card-header">
                <h3 class="document-title"><?php echo esc_html($document->post_title); ?></h3>
                <div class="document-badges">
                    <?php if ($file_count > 1): ?>
                        <span class="badge files-badge"><?php echo $file_count; ?> <?php _e('files', 'lift-docs-system'); ?></span>
                    <?php endif; ?>
                    <?php if ($user_downloaded): ?>
                        <span class="badge downloaded-badge"><?php _e('Downloaded', 'lift-docs-system'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="document-card-content">
                <?php if ($document->post_excerpt): ?>
                    <p class="document-excerpt"><?php echo esc_html($document->post_excerpt); ?></p>
                <?php endif; ?>
                
                <div class="document-meta">
                    <span class="document-date">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo get_the_date('M j, Y', $document->ID); ?>
                    </span>
                    <span class="document-stats">
                        <span class="views">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php echo $views ? $views : 0; ?>
                        </span>
                        <span class="downloads">
                            <span class="dashicons dashicons-download"></span>
                            <?php echo $downloads ? $downloads : 0; ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="document-card-actions">
                <?php if ($file_count === 1): ?>
                    <button type="button" class="btn btn-primary view-document-btn" 
                            data-document-id="<?php echo $document->ID; ?>"
                            data-file-url="<?php echo esc_url($file_urls[0]); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('View', 'lift-docs-system'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary download-document-btn" 
                            data-document-id="<?php echo $document->ID; ?>"
                            data-file-url="<?php echo esc_url($file_urls[0]); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Download', 'lift-docs-system'); ?>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-primary view-details-btn" 
                            data-document-id="<?php echo $document->ID; ?>">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php _e('View All Files', 'lift-docs-system'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d 
             ORDER BY timestamp DESC 
             LIMIT 10",
            $user_id
        ));
        
        if (empty($activities)) {
            echo '<p class="no-activity">' . __('No recent activity.', 'lift-docs-system') . '</p>';
            return;
        }
        
        foreach ($activities as $activity) {
            $document = get_post($activity->document_id);
            if (!$document) continue;
            
            $action_icon = $activity->action === 'view' ? 'visibility' : 'download';
            $action_text = $activity->action === 'view' ? __('Viewed', 'lift-docs-system') : __('Downloaded', 'lift-docs-system');
            
            ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <span class="dashicons dashicons-<?php echo $action_icon; ?>"></span>
                </div>
                <div class="activity-content">
                    <p class="activity-description">
                        <strong><?php echo $action_text; ?></strong> 
                        <a href="#" class="document-link" data-document-id="<?php echo $document->ID; ?>">
                            <?php echo esc_html($document->post_title); ?>
                        </a>
                    </p>
                    <span class="activity-time"><?php echo human_time_diff(strtotime($activity->timestamp), current_time('timestamp')); ?> <?php _e('ago', 'lift-docs-system'); ?></span>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Handle AJAX login
     */
    public function handle_ajax_login() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'docs_login_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(__('Please fill in all required fields.', 'lift-docs-system'));
        }
        
        // Try to find user by username, email, or user code
        $user = $this->find_user_by_login($username);
        
        if (!$user) {
            wp_send_json_error(__('User not found. Please check your credentials.', 'lift-docs-system'));
        }
        
        // Check if user has document access
        if (!in_array('documents_user', $user->roles) && !user_can($user->ID, 'view_lift_documents')) {
            wp_send_json_error(__('You do not have permission to access documents.', 'lift-docs-system'));
        }
        
        // Authenticate user
        $auth_result = wp_authenticate($user->user_login, $password);
        
        if (is_wp_error($auth_result)) {
            wp_send_json_error(__('Invalid password. Please try again.', 'lift-docs-system'));
        }
        
        // Log the user in
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Log the login
        $this->log_user_login($user->ID);
        
        wp_send_json_success(array(
            'redirect_url' => home_url('/docs-dashboard'),
            'message' => sprintf(__('Welcome, %s!', 'lift-docs-system'), $user->display_name)
        ));
    }
    
    /**
     * Handle AJAX logout
     */
    public function handle_ajax_logout() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            wp_logout();
            $this->log_user_logout($user_id);
        }
        
        wp_send_json_success(array(
            'redirect_url' => home_url('/docs-login'),
            'message' => __('You have been logged out successfully.', 'lift-docs-system')
        ));
    }
    
    /**
     * Find user by username, email, or user code
     */
    private function find_user_by_login($login) {
        // Try username first
        $user = get_user_by('login', $login);
        if ($user) return $user;
        
        // Try email
        $user = get_user_by('email', $login);
        if ($user) return $user;
        
        // Try user code
        $users = get_users(array(
            'meta_key' => 'lift_docs_user_code',
            'meta_value' => $login,
            'meta_compare' => '=',
            'number' => 1
        ));
        
        return !empty($users) ? $users[0] : false;
    }
    
    /**
     * Log user login
     */
    private function log_user_login($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'document_id' => 0,
                'action' => 'login',
                'timestamp' => current_time('mysql'),
                'ip_address' => $this->get_client_ip()
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log user logout
     */
    private function log_user_logout($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lift_docs_analytics';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'document_id' => 0,
                'action' => 'logout',
                'timestamp' => current_time('mysql'),
                'ip_address' => $this->get_client_ip()
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on docs pages
        if (!get_query_var('docs_login') && !get_query_var('docs_dashboard')) {
            return;
        }
        
        wp_enqueue_script('lift-docs-frontend-login', plugin_dir_url(__FILE__) . '../assets/js/frontend-login.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('lift-docs-frontend-login', plugin_dir_url(__FILE__) . '../assets/css/frontend-login.css', array(), '1.0.0');
        
        // Localize script
        wp_localize_script('lift-docs-frontend-login', 'liftDocsLogin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docs_login_nonce'),
            'strings' => array(
                'loginError' => __('Login failed. Please try again.', 'lift-docs-system'),
                'loginSuccess' => __('Login successful! Redirecting...', 'lift-docs-system'),
                'logoutSuccess' => __('Logged out successfully!', 'lift-docs-system'),
                'requiredField' => __('This field is required.', 'lift-docs-system'),
                'invalidEmail' => __('Please enter a valid email address.', 'lift-docs-system'),
                'signingIn' => __('Signing in...', 'lift-docs-system'),
                'signIn' => __('Sign In', 'lift-docs-system')
            )
        ));
    }
}

// Initialize
new LIFT_Docs_Frontend_Login();
