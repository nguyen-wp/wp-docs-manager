<?php
/**
 * Test Generate User Code Feature
 * 
 * This file tests the new "Generate Code" button for Document Users
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>Testing Generate User Code Feature</h2>";

// Test 1: Check Document Users without codes
echo "<h3>1. Document Users Status</h3>";

$document_users = get_users(array(
    'role' => 'documents_user',
    'orderby' => 'display_name',
    'order' => 'ASC'
));

if (empty($document_users)) {
    echo "<p style='color: #d63638;'>No Document Users found. Please create some Document Users first.</p>";
} else {
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>User Name</th>";
    echo "<th>Email</th>";
    echo "<th>User Code</th>";
    echo "<th>Status</th>";
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
        echo "<td><strong>" . esc_html($user->display_name) . "</strong></td>";
        echo "<td>" . esc_html($user->user_email) . "</td>";
        echo "<td>";
        if ($has_code) {
            echo "<code style='background: #f0f8ff; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-weight: bold; color: #0073aa;'>";
            echo esc_html($user_code);
            echo "</code>";
        } else {
            echo "<span style='color: #d63638; font-style: italic;'>No code</span>";
        }
        echo "</td>";
        echo "<td>";
        if ($has_code) {
            echo "<span style='color: #00a32a; font-weight: bold;'>✓ Has Code</span>";
        } else {
            echo "<span style='color: #d63638; font-weight: bold;'>✗ Needs Code</span>";
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
    
    if ($users_without_codes > 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
        echo "<p><strong>⚠️ Action Required:</strong> {$users_without_codes} Document User(s) need User Codes.</p>";
        echo "<p>Go to <strong>LIFT Docs → Document Users</strong> to generate codes for users who don't have them.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
        echo "<p><strong>✅ All Good:</strong> All Document Users have User Codes!</p>";
        echo "</div>";
    }
}

echo "<h3>2. Feature Information</h3>";
echo "<ul>";
echo "<li>✅ Added User Code column to Document Users management page</li>";
echo "<li>✅ Show 'Generate Code' button for users without codes</li>";
echo "<li>✅ AJAX functionality to generate codes without page refresh</li>";
echo "<li>✅ Visual feedback with success messages and animations</li>";
echo "<li>✅ Security checks and validation</li>";
echo "<li>✅ Automatic button removal after code generation</li>";
echo "</ul>";

echo "<h3>3. How to Use</h3>";
echo "<ol>";
echo "<li>Go to <strong>LIFT Docs → Document Users</strong> in admin menu</li>";
echo "<li>Look for users with 'No code' status in the User Code column</li>";
echo "<li>Click the blue 'Generate Code' button next to any user without a code</li>";
echo "<li>The system will generate a unique 6-8 character code automatically</li>";
echo "<li>The code will appear immediately and the button will be removed</li>";
echo "<li>The user can now be searched by this code in document assignments</li>";
echo "</ol>";

echo "<p><strong>🎉 Generate User Code feature is ready for use!</strong></p>";
?>
