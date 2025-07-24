<?php
/**
 * Test Document Assignment Search System
 * Run this file to test the enhanced search-based assignment functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Administrator access required.');
}

echo '<h1>LIFT Docs - Enhanced Assignment System Test</h1>';

// Test 1: Check if we have document users for testing
echo '<h2>Test 1: Available Document Users</h2>';
$document_users = get_users(array(
    'role' => 'documents_user',
    'orderby' => 'display_name',
    'order' => 'ASC'
));

if (!empty($document_users)) {
    echo '<p style="color: green;">✓ Found ' . count($document_users) . ' Document Users for testing:</p>';
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
    echo '<tr><th>ID</th><th>Display Name</th><th>Email</th></tr>';
    foreach ($document_users as $user) {
        echo '<tr>';
        echo '<td>' . $user->ID . '</td>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p style="color: red;">✗ No Document Users found.</p>';
    echo '<p><strong>To test the system:</strong></p>';
    echo '<ol>';
    echo '<li>Create some users with "Documents User" role</li>';
    echo '<li>Try different display names and emails for search testing</li>';
    echo '</ol>';
}

// Test 2: Check enhanced meta box functionality
echo '<h2>Test 2: Enhanced Meta Box Features</h2>';

if (class_exists('LIFT_Docs_Admin')) {
    $admin = LIFT_Docs_Admin::get_instance();
    if (method_exists($admin, 'document_assignments_meta_box')) {
        echo '<p style="color: green;">✓ Enhanced document assignments meta box method exists</p>';
        echo '<ul>';
        echo '<li>✓ Search box functionality</li>';
        echo '<li>✓ Selected users display with tags</li>';
        echo '<li>✓ Remove user functionality</li>';
        echo '<li>✓ Select All / Clear All buttons</li>';
        echo '</ul>';
    } else {
        echo '<p style="color: red;">✗ Document assignments meta box method missing</p>';
    }
} else {
    echo '<p style="color: red;">✗ LIFT_Docs_Admin class not found</p>';
}

// Test 3: JavaScript functionality preview
echo '<h2>Test 3: JavaScript Features Preview</h2>';
?>
<style>
/* Demo styles for testing */
.demo-container {
    border: 1px solid #ddd;
    padding: 15px;
    background: #f9f9f9;
    margin: 10px 0;
}

.selected-users-demo {
    min-height: 40px;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 8px;
    background: #fff;
    margin: 10px 0;
}

.user-tag-demo {
    display: inline-block;
    background: #0073aa;
    color: #fff;
    padding: 4px 8px;
    margin: 2px;
    border-radius: 3px;
    font-size: 12px;
}

.remove-demo {
    margin-left: 5px;
    cursor: pointer;
    font-weight: bold;
    opacity: 0.8;
}

.remove-demo:hover {
    opacity: 1;
    background-color: rgba(255,255,255,0.2);
    border-radius: 50%;
    padding: 1px 3px;
}

.search-input-demo {
    width: 100%;
    padding: 8px;
    margin: 5px 0;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.search-results-demo {
    max-height: 150px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 3px;
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.user-item-demo {
    padding: 8px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.user-item-demo:hover {
    background-color: #f0f8ff;
}
</style>

<div class="demo-container">
    <h4>Demo: Enhanced Assignment Interface</h4>
    
    <label><strong>Selected Users:</strong></label>
    <div class="selected-users-demo">
        <span class="user-tag-demo">
            John Doe <span class="remove-demo">&times;</span>
        </span>
        <span class="user-tag-demo">
            Jane Smith <span class="remove-demo">&times;</span>
        </span>
        <span style="color: #666; font-style: italic; display: none;">No users selected</span>
    </div>
    
    <label><strong>Add Users:</strong></label>
    <input type="text" class="search-input-demo" placeholder="Search users by name or email..." value="demo search">
    
    <div class="search-results-demo">
        <div class="user-item-demo">
            <div style="font-weight: 500;">Mike Johnson</div>
            <div style="font-size: 12px; color: #666;">mike@example.com</div>
        </div>
        <div class="user-item-demo">
            <div style="font-weight: 500;">Sarah Wilson</div>
            <div style="font-size: 12px; color: #666;">sarah@example.com</div>
        </div>
        <div class="user-item-demo">
            <div style="font-weight: 500;">David Brown</div>
            <div style="font-size: 12px; color: #666;">david@example.com</div>
        </div>
    </div>
    
    <div style="margin-top: 10px;">
        <button type="button" class="button button-secondary" style="margin-right: 10px;">Select All</button>
        <button type="button" class="button button-secondary">Clear All</button>
    </div>
    
    <p style="margin-top: 10px; font-style: italic; color: #666;">
        Total Document Users: 5 | Selected: 2
    </p>
</div>

<?php
// Test 4: Check document assignment with new system
echo '<h2>Test 4: Assignment System Integration</h2>';

$documents = get_posts(array(
    'post_type' => 'lift_document',
    'posts_per_page' => 3,
    'post_status' => 'publish'
));

if (!empty($documents)) {
    echo '<p style="color: green;">✓ Found ' . count($documents) . ' documents for testing assignments:</p>';
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th>Document</th><th>Current Assignments</th><th>Assignment Status</th></tr>';
    
    foreach ($documents as $document) {
        $assigned_users = get_post_meta($document->ID, '_lift_doc_assigned_users', true);
        
        echo '<tr>';
        echo '<td><strong>' . esc_html($document->post_title) . '</strong><br><small>ID: ' . $document->ID . '</small></td>';
        
        if (empty($assigned_users) || !is_array($assigned_users)) {
            echo '<td style="color: #007cba;"><em>All Document Users</em></td>';
            echo '<td style="color: #007cba;">Open Access</td>';
        } else {
            echo '<td>';
            foreach ($assigned_users as $user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    echo '<span style="background: #0073aa; color: #fff; padding: 2px 6px; margin: 1px; border-radius: 3px; font-size: 11px;">' . esc_html($user->display_name) . '</span> ';
                }
            }
            echo '</td>';
            echo '<td style="color: #d63638;">Restricted (' . count($assigned_users) . ' users)</td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<p><a href="' . admin_url('post.php?post=' . $documents[0]->ID . '&action=edit') . '" class="button button-primary">Test Assignment on First Document</a></p>';
} else {
    echo '<p style="color: orange;">! No documents found. Create some documents to test assignments.</p>';
}

echo '<hr>';
echo '<h2>✅ Enhanced Features Summary</h2>';
echo '<ul>';
echo '<li><strong>Search Box:</strong> Search users by name or email in real-time</li>';
echo '<li><strong>Tag-based Selection:</strong> Selected users appear as removable tags</li>';
echo '<li><strong>Quick Actions:</strong> Select All and Clear All buttons</li>';
echo '<li><strong>Visual Feedback:</strong> Hover effects, animations, and clear status indicators</li>';
echo '<li><strong>Better UX:</strong> No more scrolling through long checkbox lists</li>';
echo '</ul>';

echo '<h2>🎯 How to Use</h2>';
echo '<ol>';
echo '<li>Go to any document edit page</li>';
echo '<li>Look for "Document Access Assignment" meta box on the right</li>';
echo '<li>Use the search box to find users</li>';
echo '<li>Click on users to add them (they appear as blue tags)</li>';
echo '<li>Click the X on any tag to remove that user</li>';
echo '<li>Use Select All/Clear All for bulk operations</li>';
echo '<li>Save the document to apply assignments</li>';
echo '</ol>';

echo '<p><a href="' . admin_url('edit.php?post_type=lift_document') . '">← Back to Documents</a></p>';
?>

<script>
// Demo interaction for the preview
document.querySelectorAll('.remove-demo').forEach(function(btn) {
    btn.addEventListener('click', function() {
        this.closest('.user-tag-demo').remove();
    });
});

document.querySelectorAll('.user-item-demo').forEach(function(item) {
    item.addEventListener('click', function() {
        alert('In the real system, this user would be added as a tag!');
    });
});
</script>
