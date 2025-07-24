<?php
/**
 * Direct URL Test for docs-login
 */

// Include WordPress
require_once('../../../wp-config.php');

// Test direct access to docs-login
echo "<h2>🧪 Direct URL Test</h2>\n";

// Simulate the exact request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/docs-login/';
$_SERVER['QUERY_STRING'] = '';

// Reset WordPress query
global $wp, $wp_query;
$wp_query = new WP_Query();

// Parse the request manually
$wp->parse_request();

echo "<h3>Parse Results:</h3>\n";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>\n";
echo "<p>Matched Query Vars:</p>\n";
echo "<pre>" . print_r($wp->query_vars, true) . "</pre>\n";

// Test get_query_var
$docs_login = get_query_var('docs_login');
echo "<p>get_query_var('docs_login'): '$docs_login'</p>\n";

if ($docs_login) {
    echo "<p style='color: green;'>✅ Query var is working!</p>\n";
    
    // Now test the actual handler
    echo "<h3>Testing Handler:</h3>\n";
    
    if (class_exists('LIFT_Docs_Frontend_Login')) {
        $frontend_login = new LIFT_Docs_Frontend_Login();
        echo "<p>✅ Frontend Login class instantiated</p>\n";
        
        // Check current user
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo "<p>Current user: " . $current_user->display_name . " (ID: " . $current_user->ID . ")</p>\n";
            
            $user_roles = $current_user->roles;
            echo "<p>User roles: " . implode(', ', $user_roles) . "</p>\n";
            
            if (in_array('documents_user', $user_roles) || current_user_can('view_lift_documents')) {
                echo "<p style='color: blue;'>ℹ️ User has document access - would redirect to dashboard</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠️ User logged in but no document access</p>\n";
            }
        } else {
            echo "<p>No user logged in - would show login form</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Frontend Login class not found</p>\n";
    }
    
} else {
    echo "<p style='color: red;'>❌ Query var is not working</p>\n";
    
    // Debug rewrite rules
    echo "<h3>Debug Rewrite Rules:</h3>\n";
    $rules = get_option('rewrite_rules');
    $docs_rules = array();
    foreach ($rules as $rule => $match) {
        if (strpos($rule, 'docs') !== false) {
            $docs_rules[$rule] = $match;
        }
    }
    
    if (empty($docs_rules)) {
        echo "<p style='color: red;'>❌ No docs rewrite rules found</p>\n";
        
        // Force add rules
        echo "<h4>Force Adding Rules:</h4>\n";
        $new_rules = array(
            '^docs-login/?$' => 'index.php?docs_login=1',
            '^docs-dashboard/?$' => 'index.php?docs_dashboard=1'
        );
        
        $all_rules = array_merge($new_rules, $rules);
        update_option('rewrite_rules', $all_rules);
        
        echo "<p>✅ Added rules manually</p>\n";
        
        // Add query vars manually
        $wp->add_query_var('docs_login');
        $wp->add_query_var('docs_dashboard');
        
        echo "<p>✅ Added query vars manually</p>\n";
        
        // Test again
        $wp->parse_request();
        $docs_login_retry = get_query_var('docs_login');
        
        if ($docs_login_retry) {
            echo "<p style='color: green;'>✅ Query var is now working: '$docs_login_retry'</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Still not working after manual fix</p>\n";
        }
        
    } else {
        echo "<p>Found docs rules:</p>\n";
        echo "<pre>" . print_r($docs_rules, true) . "</pre>\n";
    }
}

echo "<hr>\n";
echo "<h3>Quick Links:</h3>\n";
echo "<ul>\n";
echo "<li><a href='" . home_url('/docs-login') . "'>Try /docs-login/</a></li>\n";
echo "<li><a href='" . home_url('/docs-dashboard') . "'>Try /docs-dashboard/</a></li>\n";
echo "<li><a href='" . admin_url('options-permalink.php') . "'>Permalink Settings</a></li>\n";
echo "</ul>\n";
?>
