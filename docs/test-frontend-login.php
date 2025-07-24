<?php
/**
 * Test Frontend Login System
 * 
 * This file tests the new frontend login system with /docs-login and /docs-dashboard pages
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>Testing Frontend Login System</h2>\n";

// Test 1: Check if documents users exist
$document_users = get_users(array(
    'role' => 'documents_user',
    'number' => 5
));

if (empty($document_users)) {
    echo "<p style='color: red;'>No Documents Users found. Please create some first using the Document Users Management page.</p>\n";
    exit;
}

echo "<h3>Frontend Login System Preview:</h3>\n";

// Test 2: Show URL structure
?>
<div style="border: 2px solid #667eea; padding: 20px; margin: 20px 0; background: #f8f9fa;">
    <h3>🔗 New URL Structure:</h3>
    <ul style="font-size: 16px; line-height: 1.8;">
        <li><strong>Login Page:</strong> <code><?php echo home_url('/docs-login'); ?></code></li>
        <li><strong>Dashboard Page:</strong> <code><?php echo home_url('/docs-dashboard'); ?></code></li>
        <li><strong>WordPress Admin:</strong> <code><?php echo admin_url(); ?></code> (for administrators)</li>
    </ul>
    
    <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; margin: 15px 0;">
        <h4 style="color: #1976d2; margin-top: 0;">Login Methods Supported:</h4>
        <ol style="color: #1976d2;">
            <li><strong>Username:</strong> Standard WordPress username</li>
            <li><strong>Email:</strong> User's email address</li>
            <li><strong>User Code:</strong> Unique 6-8 character code (e.g., ABC123XY)</li>
        </ol>
    </div>
</div>

<?php

// Test 3: Show available test users
echo "<h3>Available Test Users:</h3>\n";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #f1f1f1;'>\n";
echo "<th>Display Name</th><th>Username</th><th>Email</th><th>User Code</th><th>Test Login Methods</th>\n";
echo "</tr>\n";

foreach ($document_users as $user) {
    $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
    
    echo "<tr>\n";
    echo "<td><strong>" . esc_html($user->display_name) . "</strong></td>\n";
    echo "<td><code>" . esc_html($user->user_login) . "</code></td>\n";
    echo "<td><code>" . esc_html($user->user_email) . "</code></td>\n";
    
    if ($user_code) {
        echo "<td><strong style='color: #667eea; font-family: monospace; background: #f8f9fa; padding: 2px 6px; border-radius: 3px;'>" . esc_html($user_code) . "</strong></td>\n";
        echo "<td style='font-size: 12px;'>\n";
        echo "✅ Username: <code>" . esc_html($user->user_login) . "</code><br>\n";
        echo "✅ Email: <code>" . esc_html($user->user_email) . "</code><br>\n";
        echo "✅ User Code: <code>" . esc_html($user_code) . "</code>\n";
        echo "</td>\n";
    } else {
        echo "<td><span style='color: #d63638; font-style: italic;'>No Code</span></td>\n";
        echo "<td style='font-size: 12px;'>\n";
        echo "✅ Username: <code>" . esc_html($user->user_login) . "</code><br>\n";
        echo "✅ Email: <code>" . esc_html($user->user_email) . "</code><br>\n";
        echo "❌ User Code: <em>Not available</em>\n";
        echo "</td>\n";
    }
    
    echo "</tr>\n";
}

echo "</table>\n";

// Test 4: Show feature overview
?>
<div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 20px; margin: 20px 0;">
    <h3 style="color: #155724; margin-top: 0;">🎯 Frontend Login Features:</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
        <div>
            <h4 style="color: #155724;">Login Page (/docs-login):</h4>
            <ul style="color: #155724;">
                <li>Beautiful modern design</li>
                <li>Multiple login methods</li>
                <li>Password visibility toggle</li>
                <li>Remember me option</li>
                <li>Real-time validation</li>
                <li>AJAX form submission</li>
                <li>Auto-redirect if already logged in</li>
            </ul>
        </div>
        
        <div>
            <h4 style="color: #155724;">Dashboard Page (/docs-dashboard):</h4>
            <ul style="color: #155724;">
                <li>Personal document library</li>
                <li>Download & view statistics</li>
                <li>Document search & filtering</li>
                <li>Recent activity timeline</li>
                <li>Online document viewer</li>
                <li>Secure file downloads</li>
                <li>Real-time stats updates</li>
            </ul>
        </div>
    </div>
</div>

<?php

// Test 5: Authentication flow
echo "<h3>Authentication Flow:</h3>\n";
?>
<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 20px; margin: 20px 0;">
    <h4 style="color: #856404; margin-top: 0;">🔐 How Authentication Works:</h4>
    
    <ol style="color: #856404; line-height: 1.8;">
        <li><strong>User visits:</strong> <code>/docs-login</code></li>
        <li><strong>Enters credentials:</strong> Username/Email/UserCode + Password</li>
        <li><strong>System checks:</strong> User exists and has document access permissions</li>
        <li><strong>Validates password:</strong> Using WordPress authentication</li>
        <li><strong>Logs user in:</strong> Sets WordPress auth cookie</li>
        <li><strong>Redirects to:</strong> <code>/docs-dashboard</code></li>
        <li><strong>Dashboard loads:</strong> User's personal document library</li>
    </ol>
    
    <div style="background: rgba(255, 255, 255, 0.5); padding: 15px; border-radius: 5px; margin-top: 15px;">
        <strong>Security Features:</strong>
        <ul style="margin: 10px 0;">
            <li>WordPress nonce verification</li>
            <li>Role-based access control</li>
            <li>User capability checking</li>
            <li>AJAX request validation</li>
            <li>IP address logging</li>
        </ul>
    </div>
</div>

<?php

// Test 6: Check documents available
$all_documents = get_posts(array(
    'post_type' => 'lift_document',
    'post_status' => 'publish',
    'posts_per_page' => -1
));

echo "<h3>Available Documents for Testing:</h3>\n";

if (empty($all_documents)) {
    echo "<p style='color: red;'>No documents found. Please create some lift_document posts first.</p>\n";
} else {
    echo "<p style='color: #00a32a;'><strong>✅ Found " . count($all_documents) . " documents</strong> that will be available in the dashboard.</p>\n";
    
    echo "<h4>Document Assignment Logic:</h4>\n";
    echo "<ul>\n";
    echo "<li><strong>No specific assignment:</strong> Document available to ALL document users</li>\n";
    echo "<li><strong>Specific assignment:</strong> Document available only to assigned users</li>\n";
    echo "</ul>\n";
    
    // Check assignments
    $public_docs = 0;
    $assigned_docs = 0;
    
    foreach ($all_documents as $doc) {
        $assigned_users = get_post_meta($doc->ID, '_lift_doc_assigned_users', true);
        if (empty($assigned_users) || !is_array($assigned_users)) {
            $public_docs++;
        } else {
            $assigned_docs++;
        }
    }
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>\n";
    echo "<strong>Document Distribution:</strong><br>\n";
    echo "📖 Public Documents (all users): <strong>{$public_docs}</strong><br>\n";
    echo "🔒 Assigned Documents (specific users): <strong>{$assigned_docs}</strong>\n";
    echo "</div>\n";
}

// Test 7: Check database tables
echo "<h3>Database Requirements:</h3>\n";

global $wpdb;
$analytics_table = $wpdb->prefix . 'lift_docs_analytics';

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") === $analytics_table;

if ($table_exists) {
    $record_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table");
    echo "<p style='color: #00a32a;'>✅ Analytics table exists with <strong>{$record_count}</strong> records</p>\n";
} else {
    echo "<p style='color: #d63638;'>❌ Analytics table missing. Please activate the plugin to create it.</p>\n";
}

// Test 8: Rewrite rules check
echo "<h3>URL Rewrite Rules:</h3>\n";

$rewrite_rules_flushed = get_option('lift_docs_rewrite_rules_flushed');

if ($rewrite_rules_flushed) {
    echo "<p style='color: #00a32a;'>✅ Rewrite rules have been flushed and should be active</p>\n";
} else {
    echo "<p style='color: #ffc107;'>⚠️ Rewrite rules may need to be flushed. Try visiting the pages once.</p>\n";
}

// Test 9: File structure check
echo "<h3>File Structure Check:</h3>\n";

$required_files = array(
    'includes/class-lift-docs-frontend-login.php' => 'Frontend Login Class',
    'assets/js/frontend-login.js' => 'Frontend JavaScript',
    'assets/css/frontend-login.css' => 'Frontend Styles'
);

foreach ($required_files as $file => $description) {
    $file_path = LIFT_DOCS_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        echo "<p style='color: #00a32a;'>✅ {$description}: <code>{$file}</code></p>\n";
    } else {
        echo "<p style='color: #d63638;'>❌ {$description}: <code>{$file}</code> - Missing!</p>\n";
    }
}

// Test 10: Quick access links
?>
<div style="background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 5px; padding: 20px; margin: 20px 0;">
    <h3 style="color: #0c5460; margin-top: 0;">🚀 Quick Test Links:</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h4 style="color: #0c5460;">Frontend Pages:</h4>
            <ul style="color: #0c5460;">
                <li><a href="<?php echo home_url('/docs-login'); ?>" target="_blank" style="color: #667eea; font-weight: bold;">Test Login Page</a></li>
                <li><a href="<?php echo home_url('/docs-dashboard'); ?>" target="_blank" style="color: #667eea; font-weight: bold;">Test Dashboard Page</a></li>
            </ul>
        </div>
        
        <div>
            <h4 style="color: #0c5460;">Admin Pages:</h4>
            <ul style="color: #0c5460;">
                <li><a href="<?php echo admin_url('admin.php?page=lift-docs-users'); ?>" target="_blank" style="color: #667eea; font-weight: bold;">Document Users Management</a></li>
                <li><a href="<?php echo admin_url('users.php'); ?>" target="_blank" style="color: #667eea; font-weight: bold;">WordPress Users List</a></li>
            </ul>
        </div>
    </div>
    
    <div style="background: rgba(255, 255, 255, 0.7); padding: 15px; border-radius: 5px; margin-top: 15px;">
        <strong style="color: #0c5460;">💡 Testing Tips:</strong>
        <ul style="color: #0c5460; margin: 10px 0;">
            <li>Test login with different methods (username, email, user code)</li>
            <li>Check if dashboard loads correctly after login</li>
            <li>Try document viewing and downloading</li>
            <li>Test logout functionality</li>
            <li>Verify analytics tracking is working</li>
        </ul>
    </div>
</div>

<style>
table {
    font-family: Arial, sans-serif;
    font-size: 14px;
}

th {
    background: #f1f1f1;
    font-weight: bold;
    text-align: left;
}

code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 13px;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr !important;
    }
}
</style>
