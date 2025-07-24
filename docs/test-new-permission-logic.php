<?php
/**
 * Test New Permission Logic
 * Test the updated document assignment logic
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Administrator access required.');
}

echo '<h1>LIFT Docs - New Permission Logic Test</h1>';
echo '<p><strong>New Logic:</strong></p>';
echo '<ul>';
echo '<li><strong>Tài liệu không assigned:</strong> Chỉ admin và editor xem được</li>';
echo '<li><strong>Tài liệu đã assigned:</strong> Chỉ user được assigned xem được</li>';
echo '</ul>';

// Test 1: Get all documents and check assignments
echo '<h2>Test 1: Document Assignment Status</h2>';

$all_documents = get_posts(array(
    'post_type' => 'lift_document',
    'post_status' => 'publish',
    'posts_per_page' => -1
));

if (empty($all_documents)) {
    echo '<p style="color: red;">No documents found. Please create some documents first.</p>';
} else {
    echo '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd;">';
    echo '<tr style="background: #f1f1f1;">';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Document</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Assignment Status</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Who Can See</th>';
    echo '</tr>';
    
    foreach ($all_documents as $document) {
        $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
        
        echo '<tr>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;"><strong>' . esc_html($document->post_title) . '</strong><br><small>ID: ' . $document->ID . '</small></td>';
        
        if (empty($assigned_users) || !is_array($assigned_users)) {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;">No Assignment</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;"><strong>Only Admin & Editor</strong></td>';
        } else {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #007cba;">Assigned to ' . count($assigned_users) . ' user(s)</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #007cba;">';
            foreach ($assigned_users as $user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    echo '<span style="background: #0073aa; color: #fff; padding: 2px 6px; margin: 1px; border-radius: 3px; font-size: 11px;">' . esc_html($user->display_name) . '</span> ';
                }
            }
            echo '</td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
}

// Test 2: Test with different user roles
echo '<h2>Test 2: User Role Testing</h2>';

// Get test users
$admin_users = get_users(array('role' => 'administrator', 'number' => 1));
$editor_users = get_users(array('role' => 'editor', 'number' => 1));
$document_users = get_users(array('role' => 'documents_user', 'number' => 3));

$test_users = array();
if (!empty($admin_users)) $test_users[] = array('user' => $admin_users[0], 'role' => 'Administrator');
if (!empty($editor_users)) $test_users[] = array('user' => $editor_users[0], 'role' => 'Editor');
if (!empty($document_users)) {
    foreach (array_slice($document_users, 0, 2) as $user) {
        $test_users[] = array('user' => $user, 'role' => 'Documents User');
    }
}

if (!empty($test_users) && !empty($all_documents)) {
    echo '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd; margin-top: 15px;">';
    echo '<tr style="background: #f1f1f1;">';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">User</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Role</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Documents Count (New Logic)</th>';
    echo '</tr>';
    
    foreach ($test_users as $test_user_data) {
        $test_user = $test_user_data['user'];
        $role_name = $test_user_data['role'];
        
        // Simulate getting documents for this user
        $user_documents_count = 0;
        foreach ($all_documents as $document) {
            $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
            
            // Apply new logic
            if (empty($assigned_users) || !is_array($assigned_users)) {
                // Only admin and editor can see unassigned documents
                if (user_can($test_user->ID, 'manage_options') || user_can($test_user->ID, 'edit_lift_documents')) {
                    $user_documents_count++;
                }
            } else {
                // Check if user is specifically assigned
                if (in_array($test_user->ID, $assigned_users)) {
                    $user_documents_count++;
                }
            }
        }
        
        echo '<tr>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;"><strong>' . esc_html($test_user->display_name) . '</strong><br><small>' . esc_html($test_user->user_email) . '</small></td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">' . $role_name . '</td>';
        
        if ($user_documents_count > 0) {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #007cba;"><strong>' . $user_documents_count . ' documents</strong></td>';
        } else {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;"><strong>0 documents</strong></td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}

// Test 3: Test permission functions
echo '<h2>Test 3: Permission Function Tests</h2>';

if (!empty($all_documents) && !empty($test_users)) {
    $test_document = $all_documents[0];
    echo '<p><strong>Testing with document:</strong> ' . esc_html($test_document->post_title) . '</p>';
    
    $assigned_users = get_post_meta($test_document->ID, '_lift_doc_assigned_users', true);
    if (empty($assigned_users) || !is_array($assigned_users)) {
        echo '<p style="color: #d63638;">This document has <strong>NO assignments</strong> - Only admin & editor should have access</p>';
    } else {
        echo '<p style="color: #007cba;">This document is <strong>assigned to ' . count($assigned_users) . ' user(s)</strong></p>';
    }
    
    echo '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd; margin-top: 15px;">';
    echo '<tr style="background: #f1f1f1;">';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">User</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Role</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Can View Document</th>';
    echo '<th style="padding: 8px; border: 1px solid #ccc;">Can Download Document</th>';
    echo '</tr>';
    
    foreach ($test_users as $test_user_data) {
        $test_user = $test_user_data['user'];
        $role_name = $test_user_data['role'];
        
        // Test view permission
        $can_view = LIFT_Docs_Settings::user_can_view_document($test_document->ID, $test_user->ID);
        $can_download = LIFT_Docs_Settings::user_can_download_document($test_document->ID, $test_user->ID);
        
        echo '<tr>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;"><strong>' . esc_html($test_user->display_name) . '</strong></td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">' . $role_name . '</td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">' . ($can_view ? '<span style="color: #007cba;">✓ YES</span>' : '<span style="color: #d63638;">✗ NO</span>') . '</td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">' . ($can_download ? '<span style="color: #007cba;">✓ YES</span>' : '<span style="color: #d63638;">✗ NO</span>') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Test 4: Summary and recommendations
echo '<h2>Test 4: Summary & Recommendations</h2>';
echo '<div style="background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 5px; padding: 15px; margin: 15px 0;">';
echo '<h4 style="color: #0c5460; margin-top: 0;">New Permission Logic Summary:</h4>';
echo '<ul style="color: #0c5460;">';
echo '<li><strong>Unassigned Documents:</strong> Only Administrators and Editors can view/download</li>';
echo '<li><strong>Assigned Documents:</strong> Only specifically assigned users can view/download</li>';
echo '<li><strong>Document Users:</strong> Must be specifically assigned to see any documents</li>';
echo '<li><strong>Security:</strong> More restrictive - documents are hidden by default unless explicitly assigned</li>';
echo '</ul>';
echo '</div>';

echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;">';
echo '<h4 style="color: #856404; margin-top: 0;">Next Steps:</h4>';
echo '<ol style="color: #856404;">';
echo '<li>Test the frontend dashboard with different user roles</li>';
echo '<li>Assign some documents to specific users to test assignment functionality</li>';
echo '<li>Verify that unassigned documents are only visible to admin/editor</li>';
echo '<li>Test emergency dashboard access with the new logic</li>';
echo '</ol>';
echo '</div>';

echo '<p><a href="' . admin_url('edit.php?post_type=lift_document') . '" class="button button-primary">Go to Documents</a> ';
echo '<a href="' . admin_url('admin.php?page=lift-docs-users') . '" class="button">Manage Users</a></p>';
?>
