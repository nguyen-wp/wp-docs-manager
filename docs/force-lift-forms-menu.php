<?php
/**
 * Force Enable LIFT Forms Menu
 * Simple fix to ensure LIFT Forms menu appears
 */

if (!defined('ABSPATH')) {
    exit;
}

// Force add LIFT Forms menu immediately
add_action('admin_menu', function() {
    // Add main Forms menu under LIFT Documents
    add_submenu_page(
        'edit.php?post_type=lift_document',
        'LIFT Forms',
        'Forms',
        'manage_options',
        'lift-forms-main',
        'lift_forms_main_page'
    );
    
    // Add Form Builder
    add_submenu_page(
        'edit.php?post_type=lift_document',
        'Form Builder',
        'Form Builder',
        'manage_options', 
        'lift-forms-builder-main',
        'lift_forms_builder_page'
    );
}, 20); // Priority 20 to run after other menus

function lift_forms_main_page() {
    ?>
    <div class="wrap">
        <h1>LIFT Forms</h1>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc;">
            <h2>LIFT Forms System Status</h2>
            
            <?php
            // Check if LIFT_Forms class exists
            if (class_exists('LIFT_Forms')) {
                echo '<p style="color: green;">✓ LIFT_Forms class is loaded and available</p>';
                
                // Try to create instance and test
                try {
                    $lift_forms = new LIFT_Forms();
                    echo '<p style="color: green;">✓ LIFT_Forms can be instantiated successfully</p>';
                } catch (Exception $e) {
                    echo '<p style="color: red;">✗ Error creating LIFT_Forms instance: ' . $e->getMessage() . '</p>';
                }
            } else {
                echo '<p style="color: red;">✗ LIFT_Forms class is not loaded</p>';
                echo '<p>Please check if includes/class-lift-forms.php is properly included.</p>';
            }
            
            // Check database tables
            global $wpdb;
            $forms_table = $wpdb->prefix . 'lift_forms';
            $submissions_table = $wpdb->prefix . 'lift_form_submissions';
            
            $forms_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'");
            $submissions_exists = $wpdb->get_var("SHOW TABLES LIKE '$submissions_table'");
            
            if ($forms_exists) {
                echo '<p style="color: green;">✓ Forms table exists</p>';
            } else {
                echo '<p style="color: red;">✗ Forms table missing</p>';
            }
            
            if ($submissions_exists) {
                echo '<p style="color: green;">✓ Submissions table exists</p>';
            } else {
                echo '<p style="color: red;">✗ Submissions table missing</p>';
            }
            ?>
            
            <hr>
            
            <h3>Quick Actions</h3>
            <form method="post">
                <?php wp_nonce_field('lift_forms_actions', 'lift_nonce'); ?>
                
                <button type="submit" name="action" value="create_tables" class="button button-primary">
                    Create/Update Database Tables
                </button>
                
                <button type="submit" name="action" value="test_form" class="button button-secondary">
                    Create Test Form
                </button>
            </form>
            
            <?php
            if (isset($_POST['action']) && wp_verify_nonce($_POST['lift_nonce'], 'lift_forms_actions')) {
                if ($_POST['action'] === 'create_tables') {
                    // Force create tables
                    $charset_collate = $wpdb->get_charset_collate();
                    
                    $sql_forms = "CREATE TABLE $forms_table (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        name varchar(255) NOT NULL,
                        description text,
                        form_fields longtext,
                        settings longtext,
                        status varchar(20) DEFAULT 'active',
                        created_by bigint(20) UNSIGNED,
                        created_at datetime DEFAULT CURRENT_TIMESTAMP,
                        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                    ) $charset_collate;";
                    
                    $sql_submissions = "CREATE TABLE $submissions_table (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        form_id mediumint(9) NOT NULL,
                        form_data longtext,
                        user_ip varchar(45),
                        user_agent text,
                        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
                        status varchar(20) DEFAULT 'unread',
                        PRIMARY KEY (id),
                        KEY form_id (form_id)
                    ) $charset_collate;";
                    
                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    dbDelta($sql_forms);
                    dbDelta($sql_submissions);
                    
                    echo '<div class="notice notice-success"><p>Database tables created/updated successfully!</p></div>';
                }
                
                if ($_POST['action'] === 'test_form') {
                    // Create a test form
                    $test_form_data = json_encode([
                        [
                            'type' => 'text',
                            'id' => 'customer_name',
                            'name' => 'customer_name',
                            'label' => 'Customer Name',
                            'placeholder' => 'Enter your name',
                            'required' => true
                        ],
                        [
                            'type' => 'email',
                            'id' => 'customer_email',
                            'name' => 'customer_email', 
                            'label' => 'Email Address',
                            'placeholder' => 'Enter your email',
                            'required' => true
                        ]
                    ]);
                    
                    $result = $wpdb->insert(
                        $forms_table,
                        [
                            'name' => 'Test Contact Form',
                            'description' => 'A simple test form created automatically',
                            'form_fields' => $test_form_data,
                            'settings' => '{}',
                            'created_by' => get_current_user_id()
                        ]
                    );
                    
                    if ($result) {
                        echo '<div class="notice notice-success"><p>Test form created successfully!</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>Failed to create test form.</p></div>';
                    }
                }
            }
            ?>
            
            <hr>
            
            <h3>Direct Links</h3>
            <p>If the original LIFT Forms menu is working, try these links:</p>
            <ul>
                <li><a href="<?php echo admin_url('edit.php?post_type=lift_document&page=lift-forms'); ?>">LIFT Forms List</a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=lift_document&page=lift-forms-builder'); ?>">Form Builder</a></li>
                <li><a href="<?php echo admin_url('edit.php?post_type=lift_document&page=lift-forms-submissions'); ?>">Submissions</a></li>
            </ul>
        </div>
    </div>
    <?php
}

function lift_forms_builder_page() {
    ?>
    <div class="wrap">
        <h1>Form Builder</h1>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc;">
            <p>This is a simplified form builder page. The full LIFT Forms system should be accessible once the menu issue is resolved.</p>
            
            <h3>Form Builder Status</h3>
            <?php
            // Check if assets exist
            $plugin_url = plugin_dir_url(dirname(__FILE__));
            $assets_to_check = [
                'assets/css/forms-admin.css',
                'assets/js/forms-builder.js',
                'assets/css/forms-frontend.css',
                'assets/js/forms-frontend.js'
            ];
            
            foreach ($assets_to_check as $asset) {
                $file_path = dirname(__FILE__) . '/' . $asset;
                if (file_exists($file_path)) {
                    echo '<p style="color: green;">✓ ' . $asset . ' exists</p>';
                } else {
                    echo '<p style="color: red;">✗ ' . $asset . ' missing</p>';
                }
            }
            ?>
            
            <hr>
            
            <h3>Next Steps</h3>
            <ol>
                <li>If all checks pass, the original LIFT Forms menu should work</li>
                <li>Try refreshing the admin page or clearing any caching</li>
                <li>Check if there are any JavaScript errors in the browser console</li>
                <li>Verify that the LIFT Documents post type menu exists first</li>
            </ol>
        </div>
    </div>
    <?php
}
