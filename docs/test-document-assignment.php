<?php
/**
 * Test Document Assignment System
 * Run this file to test the document assignment functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Administrator access required.');
}

echo '<h1>LIFT Docs - Document Assignment System Test</h1>';

// Test 1: Check if Documents User role exists
echo '<h2>Test 1: Check Documents User Role</h2>';
$role = get_role('documents_user');
if ($role) {
    echo '<p style="color: green;">✓ Documents User role exists</p>';
    echo '<p>Capabilities: ' . implode(', ', array_keys($role->capabilities)) . '</p>';
} else {
    echo '<p style="color: red;">✗ Documents User role not found</p>';
}

// Test 2: Get document users
echo '<h2>Test 2: Document Users</h2>';
$document_users = get_users(array(
    'role' => 'documents_user',
    'orderby' => 'display_name',
    'order' => 'ASC'
));

if (!empty($document_users)) {
    echo '<p style="color: green;">✓ Found ' . count($document_users) . ' Document Users:</p>';
    echo '<ul>';
    foreach ($document_users as $user) {
        echo '<li>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color: orange;">! No Document Users found. Create some users with Documents User role first.</p>';
}

// Test 3: Check permission functions
echo '<h2>Test 3: Permission Functions</h2>';

if (class_exists('LIFT_Docs_Settings')) {
    echo '<p style="color: green;">✓ LIFT_Docs_Settings class exists</p>';
    
    // Test methods
    $methods = ['current_user_can_view_documents', 'current_user_can_download_documents', 'user_is_assigned_to_document'];
    foreach ($methods as $method) {
        if (method_exists('LIFT_Docs_Settings', $method)) {
            echo '<p style="color: green;">✓ Method ' . $method . ' exists</p>';
        } else {
            echo '<p style="color: red;">✗ Method ' . $method . ' missing</p>';
        }
    }
} else {
    echo '<p style="color: red;">✗ LIFT_Docs_Settings class not found</p>';
}

// Test 4: Check document assignment
echo '<h2>Test 4: Document Assignment Test</h2>';

// Get first document
$documents = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 1,
    'post_status' => 'publish'
));

if (!empty($documents)) {
    $document = $documents[0];
    echo '<p>Testing with document: <strong>' . esc_html($document->post_title) . '</strong> (ID: ' . $document->ID . ')</p>';
    
    // Check if document has assignments
    $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
    
    if (empty($assigned_users) || !is_array($assigned_users)) {
        echo '<p style="color: orange;">! Document has no specific user assignments (restricted to Admin and Editor only)</p>';
    } else {
        echo '<p style="color: green;">✓ Document assigned to ' . count($assigned_users) . ' users:</p>';
        echo '<ul>';
        foreach ($assigned_users as $user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                echo '<li>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</li>';
            }
        }
        echo '</ul>';
    }
    
    // Test permission for current user
    $current_user_id = get_current_user_id();
    echo '<h3>Current User Permission Test</h3>';
    
    if (LIFT_Docs_Settings::user_can_view_document($document->ID, $current_user_id)) {
        echo '<p style="color: green;">✓ Current user CAN view this document</p>';
    } else {
        echo '<p style="color: red;">✗ Current user CANNOT view this document</p>';
    }
    
    if (LIFT_Docs_Settings::user_can_download_document($document->ID, $current_user_id)) {
        echo '<p style="color: green;">✓ Current user CAN download this document</p>';
    } else {
        echo '<p style="color: red;">✗ Current user CANNOT download this document</p>';
    }
    
} else {
    echo '<p style="color: orange;">! No documents found. Create a document first to test assignments.</p>';
}

// Test 5: Meta box functionality
echo '<h2>Test 5: Admin Meta Box</h2>';

if (class_exists('LIFT_Docs_Admin')) {
    $admin = LIFT_Docs_Admin::get_instance();
    if (method_exists($admin, 'document_assignments_meta_box')) {
        echo '<p style="color: green;">✓ Document assignments meta box method exists</p>';
    } else {
        echo '<p style="color: red;">✗ Document assignments meta box method missing</p>';
    }
} else {
    echo '<p style="color: red;">✗ LIFT_Docs_Admin class not found</p>';
}

echo '<hr>';
echo '<h2>Summary</h2>';
echo '<p>If all tests pass, the document assignment system is working correctly.</p>';
echo '<p><strong>Next steps:</strong></p>';
echo '<ol>';
echo '<li>Create users with "Documents User" role</li>';
echo '<li>Edit a document and assign it to specific users</li>';
echo '<li>Test viewing/downloading from frontend with different users</li>';
echo '</ol>';

echo '<p><a href="' . admin_url('edit.php?post_type=lift_document') . '">← Back to Documents</a></p>';
?>
