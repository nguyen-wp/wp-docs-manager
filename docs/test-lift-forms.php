<?php
/**
 * LIFT Forms Test File
 * 
 * This file tests the LIFT Forms functionality
 * Include this in your WordPress setup to test forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test LIFT Forms Installation
 */
function test_lift_forms_installation() {
    echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    echo '<h2>🧪 LIFT Forms Installation Test</h2>';
    
    // Test 1: Check if class exists
    if (class_exists('LIFT_Forms')) {
        echo '<p>✅ LIFT_Forms class loaded successfully</p>';
    } else {
        echo '<p>❌ LIFT_Forms class not found</p>';
    }
    
    // Test 2: Check database tables
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    $submissions_table = $wpdb->prefix . 'lift_form_submissions';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table) {
        echo '<p>✅ Forms table exists</p>';
    } else {
        echo '<p>❌ Forms table missing</p>';
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table) {
        echo '<p>✅ Submissions table exists</p>';
    } else {
        echo '<p>❌ Submissions table missing</p>';
    }
    
    // Test 3: Check admin pages
    if (is_admin()) {
        echo '<p>✅ Admin interface available</p>';
        echo '<p>📋 <a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms') . '">View Forms</a></p>';
        echo '<p>🚀 <a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-builder') . '">Form Builder</a></p>';
        echo '<p>📊 <a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-submissions') . '">Submissions</a></p>';
    }
    
    // Test 4: Check shortcode
    if (shortcode_exists('lift_form')) {
        echo '<p>✅ Shortcode [lift_form] registered</p>';
    } else {
        echo '<p>❌ Shortcode not registered</p>';
    }
    
    // Test 5: Check AJAX actions
    $ajax_actions = ['lift_forms_save', 'lift_forms_submit', 'lift_forms_delete'];
    foreach ($ajax_actions as $action) {
        if (has_action("wp_ajax_$action") || has_action("wp_ajax_nopriv_$action")) {
            echo "<p>✅ AJAX action '$action' registered</p>";
        } else {
            echo "<p>❌ AJAX action '$action' not registered</p>";
        }
    }
    
    echo '</div>';
}

/**
 * Create Sample Form for Testing
 */
function create_sample_lift_form() {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    
    // Check if sample form already exists
    $existing = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table WHERE name = 'Sample Contact Form'");
    
    if ($existing > 0) {
        echo '<p>ℹ️ Sample form already exists</p>';
        return;
    }
    
    // Sample form data
    $sample_fields = [
        [
            'id' => 'field_1',
            'name' => 'contact_name',
            'type' => 'text',
            'label' => 'Full Name',
            'placeholder' => 'Enter your full name',
            'required' => true,
            'description' => 'Please enter your first and last name'
        ],
        [
            'id' => 'field_2',
            'name' => 'contact_email',
            'type' => 'email',
            'label' => 'Email Address',
            'placeholder' => 'Enter your email',
            'required' => true,
            'description' => 'We will use this to respond to your message'
        ],
        [
            'id' => 'field_3',
            'name' => 'contact_subject',
            'type' => 'select',
            'label' => 'Subject',
            'required' => true,
            'options' => [
                ['label' => 'General Inquiry', 'value' => 'general'],
                ['label' => 'Technical Support', 'value' => 'support'],
                ['label' => 'Sales Question', 'value' => 'sales'],
                ['label' => 'Other', 'value' => 'other']
            ]
        ],
        [
            'id' => 'field_4',
            'name' => 'contact_message',
            'type' => 'textarea',
            'label' => 'Message',
            'placeholder' => 'Enter your message here...',
            'required' => true,
            'rows' => 5,
            'description' => 'Please provide as much detail as possible'
        ],
        [
            'id' => 'field_5',
            'name' => 'contact_phone',
            'type' => 'text',
            'label' => 'Phone Number (Optional)',
            'placeholder' => 'Enter your phone number',
            'required' => false,
            'description' => 'Optional - for urgent matters'
        ]
    ];
    
    $result = $wpdb->insert(
        $forms_table,
        [
            'name' => 'Sample Contact Form',
            'description' => 'A sample contact form created for testing LIFT Forms functionality',
            'form_fields' => json_encode($sample_fields),
            'settings' => json_encode(['email_notifications' => true]),
            'status' => 'active',
            'created_by' => get_current_user_id()
        ]
    );
    
    if ($result !== false) {
        $form_id = $wpdb->insert_id;
        echo '<p>✅ Sample form created successfully! ID: ' . $form_id . '</p>';
        echo '<p>📝 Shortcode: <code>[lift_form id="' . $form_id . '"]</code></p>';
        echo '<p>🎨 <a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-builder&id=' . $form_id) . '">Edit Form</a></p>';
    } else {
        echo '<p>❌ Failed to create sample form</p>';
    }
}

/**
 * Display Sample Form
 */
function display_sample_form() {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    
    $form = $wpdb->get_row("SELECT * FROM $forms_table WHERE name = 'Sample Contact Form' LIMIT 1");
    
    if ($form) {
        echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        echo '<h2>📝 Sample Form Preview</h2>';
        echo '<p>This is how your form will appear on the frontend:</p>';
        echo do_shortcode('[lift_form id="' . $form->id . '"]');
        echo '</div>';
    } else {
        echo '<p>ℹ️ No sample form found. Create one first.</p>';
    }
}

/**
 * LIFT Forms Demo Page
 */
function lift_forms_demo_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    echo '<div class="wrap">';
    echo '<h1>🚀 LIFT Forms Testing Dashboard</h1>';
    
    // Handle actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'create_sample':
                echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
                echo '<h2>🎯 Creating Sample Form</h2>';
                create_sample_lift_form();
                echo '</div>';
                break;
                
            case 'test_installation':
                test_lift_forms_installation();
                break;
                
            case 'show_sample':
                display_sample_form();
                break;
        }
    }
    
    // Main dashboard
    echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    echo '<h2>🎮 Quick Actions</h2>';
    echo '<p><a href="?page=lift-forms-test&action=test_installation" class="button button-primary">🧪 Test Installation</a></p>';
    echo '<p><a href="?page=lift-forms-test&action=create_sample" class="button button-secondary">🎯 Create Sample Form</a></p>';
    echo '<p><a href="?page=lift-forms-test&action=show_sample" class="button">📝 Show Sample Form</a></p>';
    echo '</div>';
    
    echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    echo '<h2>🔗 Quick Links</h2>';
    echo '<ul>';
    echo '<li><a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms') . '">📋 All Forms</a></li>';
    echo '<li><a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-builder') . '">🚀 Form Builder</a></li>';
    echo '<li><a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-submissions') . '">📊 Submissions</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div style="background: #fff; padding: 20px; margin: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    echo '<h2>📚 Documentation</h2>';
    echo '<p>📖 <a href="' . plugin_dir_url(__FILE__) . 'LIFT-FORMS-README.md" target="_blank">View README</a></p>';
    echo '<p>🌐 <a href="' . plugin_dir_url(__FILE__) . 'demo-lift-forms.html" target="_blank">Demo Page</a></p>';
    echo '</div>';
    
    echo '</div>';
}

// Add test page to admin menu (only for testing)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'edit.php?post_type=lift_document',
            'LIFT Forms Test',
            'Forms Test',
            'manage_options',
            'lift-forms-test',
            'lift_forms_demo_page'
        );
    });
}

// Auto-run installation test on admin_init (only once)
add_action('admin_init', function() {
    if (get_transient('lift_forms_tested')) {
        return;
    }
    
    if (class_exists('LIFT_Forms')) {
        set_transient('lift_forms_tested', true, DAY_IN_SECONDS);
        
        // Add admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>🚀 LIFT Forms:</strong> Installation successful! ';
            echo '<a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-test') . '">Test Dashboard</a> | ';
            echo '<a href="' . admin_url('edit.php?post_type=lift_document&page=lift-forms-builder') . '">Create Form</a>';
            echo '</p>';
            echo '</div>';
        });
    }
});

/**
 * Debug Helper Functions
 */
function lift_forms_debug_info() {
    if (!WP_DEBUG) return;
    
    echo '<div style="background: #f0f0f0; padding: 15px; margin: 20px; border-radius: 6px; font-family: monospace; font-size: 12px;">';
    echo '<h4>🐛 Debug Information</h4>';
    echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
    echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
    echo '<p><strong>Plugin Version:</strong> ' . (defined('LIFT_DOCS_VERSION') ? LIFT_DOCS_VERSION : 'Unknown') . '</p>';
    echo '<p><strong>Current User:</strong> ' . wp_get_current_user()->display_name . ' (ID: ' . get_current_user_id() . ')</p>';
    echo '<p><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</p>';
    echo '<p><strong>Upload Max Size:</strong> ' . ini_get('upload_max_filesize') . '</p>';
    echo '</div>';
}

// Add debug info to test page
add_action('admin_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'lift-forms-test') {
        lift_forms_debug_info();
    }
});
