<?php
/**
 * Create Test Document User
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔧 Create Test Document User</h2>\n";

// Check if documents_user role exists
$roles = wp_roles();
if (!isset($roles->roles['documents_user'])) {
    echo "<p style='color: orange;'>⚠️ 'documents_user' role doesn't exist. Creating it...</p>\n";
    
    // Create the role
    add_role('documents_user', 'Documents User', array(
        'read' => true,
        'view_lift_documents' => true
    ));
    
    echo "<p style='color: green;'>✅ Created 'documents_user' role</p>\n";
} else {
    echo "<p style='color: green;'>✅ 'documents_user' role already exists</p>\n";
}

// Check if test user exists
$existing_user = get_user_by('login', 'testdocs');

if ($existing_user) {
    echo "<p style='color: blue;'>ℹ️ Test user already exists:</p>\n";
    echo "<ul>\n";
    echo "<li><strong>Username:</strong> " . $existing_user->user_login . "</li>\n";
    echo "<li><strong>Email:</strong> " . $existing_user->user_email . "</li>\n";
    echo "<li><strong>Roles:</strong> " . implode(', ', $existing_user->roles) . "</li>\n";
    $user_code = get_user_meta($existing_user->ID, 'lift_docs_user_code', true);
    echo "<li><strong>User Code:</strong> " . ($user_code ?: 'Not set') . "</li>\n";
    echo "</ul>\n";
    
    // Update role if needed
    if (!in_array('documents_user', $existing_user->roles)) {
        $existing_user->set_role('documents_user');
        echo "<p style='color: green;'>✅ Updated user role to 'documents_user'</p>\n";
    }
    
    // Set user code if not exists
    if (!$user_code) {
        update_user_meta($existing_user->ID, 'lift_docs_user_code', 'TEST123');
        echo "<p style='color: green;'>✅ Set user code to 'TEST123'</p>\n";
    }
    
} else {
    echo "<p style='color: orange;'>⚠️ Test user doesn't exist. Creating...</p>\n";
    
    // Create test user
    $user_data = array(
        'user_login' => 'testdocs',
        'user_pass' => 'password123',
        'user_email' => 'test@docs.local',
        'first_name' => 'Test',
        'last_name' => 'User',
        'display_name' => 'Test Document User',
        'role' => 'documents_user'
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        echo "<p style='color: red;'>❌ Error creating user: " . $user_id->get_error_message() . "</p>\n";
    } else {
        // Set user code
        update_user_meta($user_id, 'lift_docs_user_code', 'TEST123');
        
        echo "<p style='color: green;'>✅ Created test user successfully!</p>\n";
        echo "<ul>\n";
        echo "<li><strong>Username:</strong> testdocs</li>\n";
        echo "<li><strong>Password:</strong> password123</li>\n";
        echo "<li><strong>Email:</strong> test@docs.local</li>\n";
        echo "<li><strong>User Code:</strong> TEST123</li>\n";
        echo "<li><strong>Role:</strong> documents_user</li>\n";
        echo "</ul>\n";
    }
}

// Create second test user with different method
$existing_user2 = get_user_by('login', 'docuser');

if (!$existing_user2) {
    echo "<h3>Creating second test user...</h3>\n";
    
    $user_data2 = array(
        'user_login' => 'docuser',
        'user_pass' => 'docs123',
        'user_email' => 'docuser@example.com',
        'first_name' => 'Document',
        'last_name' => 'User',
        'display_name' => 'Document User',
        'role' => 'documents_user'
    );
    
    $user_id2 = wp_insert_user($user_data2);
    
    if (!is_wp_error($user_id2)) {
        update_user_meta($user_id2, 'lift_docs_user_code', 'DOC456');
        echo "<p style='color: green;'>✅ Created second test user!</p>\n";
        echo "<ul>\n";
        echo "<li><strong>Username:</strong> docuser</li>\n";
        echo "<li><strong>Password:</strong> docs123</li>\n";
        echo "<li><strong>Email:</strong> docuser@example.com</li>\n";
        echo "<li><strong>User Code:</strong> DOC456</li>\n";
        echo "</ul>\n";
    }
}

echo "<hr>\n";
echo "<h3>Test Login Credentials:</h3>\n";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 4px; border-left: 4px solid #1976d2;'>\n";
echo "<p><strong>You can now test login with any of these methods:</strong></p>\n";
echo "<h4>User 1:</h4>\n";
echo "<ul>\n";
echo "<li>Username: <code>testdocs</code> | Password: <code>password123</code></li>\n";
echo "<li>Email: <code>test@docs.local</code> | Password: <code>password123</code></li>\n";
echo "<li>User Code: <code>TEST123</code> | Password: <code>password123</code></li>\n";
echo "</ul>\n";
echo "<h4>User 2:</h4>\n";
echo "<ul>\n";
echo "<li>Username: <code>docuser</code> | Password: <code>docs123</code></li>\n";
echo "<li>Email: <code>docuser@example.com</code> | Password: <code>docs123</code></li>\n";
echo "<li>User Code: <code>DOC456</code> | Password: <code>docs123</code></li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<h3>Test Login Pages:</h3>\n";
echo "<ul>\n";
echo "<li><a href='" . home_url('/wp-content/plugins/wp-docs-manager/test-improved-login.php') . "' target='_blank'>Improved Login Page</a></li>\n";
echo "<li><a href='" . home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php') . "' target='_blank'>Emergency Login Page</a></li>\n";
echo "<li><a href='" . home_url('/docs-login') . "' target='_blank'>Official Login Page (/docs-login)</a></li>\n";
echo "</ul>\n";
?>
