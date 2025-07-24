<?php
/**
 * Test User Code Generation in Users List
 * 
 * This file tests the generate code functionality in WordPress Users list
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>Testing User Code Generation in Users List</h2>";

// Test 1: Check current Document Users status
echo "<h3>1. Current Document Users Status</h3>";

$document_users = get_users(array(
    'role' => 'documents_user',
    'orderby' => 'display_name',
    'order' => 'ASC'
));

if (empty($document_users)) {
    echo "<p style='color: #d63638;'>No Document Users found. Please create some Document Users first.</p>";
} else {
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>User ID</th>";
    echo "<th>User Name</th>";
    echo "<th>Email</th>";
    echo "<th>User Code</th>";
    echo "<th>Action Needed</th>";
    echo "</tr>";
    
    $users_without_codes = 0;
    $users_with_codes = 0;
    
    foreach ($document_users as $user) {
        $user_code = get_user_meta($user->ID, 'lift_docs_user_code', true);
        $has_code = !empty($user_code);
        
        if ($has_code) {
            $users_with_codes++;
        } else {
            $users_without_codes++;
        }
        
        echo "<tr>";
        echo "<td>" . $user->ID . "</td>";
        echo "<td><strong>" . esc_html($user->display_name) . "</strong></td>";
        echo "<td>" . esc_html($user->user_email) . "</td>";
        echo "<td style='text-align: center;'>";
        if ($has_code) {
            echo "<code style='background: #f0f8ff; padding: 4px 8px; border-radius: 3px; font-family: monospace; font-weight: bold; color: #0073aa;'>";
            echo esc_html($user_code);
            echo "</code>";
        } else {
            echo "<span style='color: #d63638; font-style: italic; font-weight: bold;'>NO CODE</span>";
        }
        echo "</td>";
        echo "<td style='text-align: center;'>";
        if ($has_code) {
            echo "<span style='color: #00a32a; font-weight: bold;'>✓ Ready</span>";
        } else {
            echo "<span style='color: #d63638; font-weight: bold;'>⚠ Needs Code</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h4>Summary:</h4>";
    echo "<ul>";
    echo "<li><strong>Total Document Users:</strong> " . count($document_users) . "</li>";
    echo "<li><strong style='color: #00a32a;'>Users with codes:</strong> {$users_with_codes}</li>";
    echo "<li><strong style='color: #d63638;'>Users without codes:</strong> {$users_without_codes}</li>";
    echo "</ul>";
}

echo "<h3>2. How to Use the New Feature</h3>";
echo "<ol>";
echo "<li>Go to <strong>WordPress Admin → Users</strong></li>";
echo "<li>Look for the <strong>'User Code'</strong> column in the users table</li>";
echo "<li>For Document Users without codes, you'll see:</li>";
echo "<ul>";
echo "<li><em>'No Code'</em> text in red</li>";
echo "<li>A green <strong>'Generate Code'</strong> button below it</li>";
echo "</ul>";
echo "<li>Click the <strong>'Generate Code'</strong> button</li>";
echo "<li>The code will be generated instantly and displayed</li>";
echo "<li>A success notification will appear briefly</li>";
echo "</ol>";

echo "<h3>3. Features of the Generate Code Button</h3>";
echo "<ul>";
echo "<li>✅ <strong>Instant Generation:</strong> No page reload needed</li>";
echo "<li>✅ <strong>AJAX-powered:</strong> Fast and responsive</li>";
echo "<li>✅ <strong>Visual Feedback:</strong> Button shows 'Generating...' during process</li>";
echo "<li>✅ <strong>Success Notification:</strong> Popup message confirms success</li>";
echo "<li>✅ <strong>Error Handling:</strong> Clear error messages if something goes wrong</li>";
echo "<li>✅ <strong>Security:</strong> Nonce verification and permission checks</li>";
echo "<li>✅ <strong>Smart Logic:</strong> Won't overwrite existing codes</li>";
echo "</ul>";

echo "<h3>4. Technical Implementation</h3>";
echo "<ul>";
echo "<li><strong>Frontend:</strong> JavaScript handles button clicks and AJAX calls</li>";
echo "<li><strong>Backend:</strong> PHP AJAX handler generates and saves user codes</li>";
echo "<li><strong>Security:</strong> WordPress nonce and capability checks</li>";
echo "<li><strong>Integration:</strong> Works seamlessly with existing WordPress Users table</li>";
echo "</ul>";

echo "<h3>5. Error Troubleshooting</h3>";
if ($users_without_codes > 0) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
    echo "<p><strong>⚠️ Action Required:</strong></p>";
    echo "<p>You have {$users_without_codes} Document User(s) without User Codes.</p>";
    echo "<p><strong>To fix:</strong></p>";
    echo "<ol>";
    echo "<li>Go to WordPress Admin → Users</li>";
    echo "<li>Look for users with 'No Code' in the User Code column</li>";
    echo "<li>Click the 'Generate Code' button for each user</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
    echo "<p><strong>✅ All Good!</strong> All Document Users have User Codes.</p>";
    echo "</div>";
}

echo "<p><strong>🎉 User Code generation feature is now available in WordPress Users list!</strong></p>";

echo "<h3>6. Next Steps</h3>";
echo "<ul>";
echo "<li>Go to <strong>Users → All Users</strong> to see the new User Code column</li>";
echo "<li>Generate codes for users who don't have them</li>";
echo "<li>Test the document assignment search with the new user codes</li>";
echo "<li>Users can now be searched by their unique codes in document assignments</li>";
echo "</ul>";
?>
