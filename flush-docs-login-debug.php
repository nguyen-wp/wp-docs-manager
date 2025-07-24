<?php
/**
 * Debug Frontend Login System - Flush và Test
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h2>🔧 Debug Frontend Login System</h2>\n";

// Step 1: Force flush rewrite rules
echo "<h3>Step 1: Flush Rewrite Rules</h3>\n";
flush_rewrite_rules();
delete_option('lift_docs_rewrite_rules_flushed');
update_option('lift_docs_rewrite_rules_flushed', true);
echo "✅ Rewrite rules flushed successfully<br>\n";

// Step 2: Verify rewrite rules
echo "<h3>Step 2: Verify Rewrite Rules</h3>\n";
$rules = get_option('rewrite_rules');
$docs_rules = [];
foreach ($rules as $rule => $match) {
    if (strpos($rule, 'docs') !== false) {
        $docs_rules[$rule] = $match;
    }
}

if (!empty($docs_rules)) {
    echo "✅ Found docs rewrite rules:<br>\n";
    foreach ($docs_rules as $rule => $match) {
        echo "- <code>$rule</code> => <code>$match</code><br>\n";
    }
} else {
    echo "❌ No docs rewrite rules found<br>\n";
}

// Step 3: Test query vars
echo "<h3>Step 3: Test Query Vars</h3>\n";
global $wp;
$public_vars = $wp->public_query_vars;

if (in_array('docs_login', $public_vars)) {
    echo "✅ 'docs_login' is in public query vars<br>\n";
} else {
    echo "❌ 'docs_login' NOT in public query vars<br>\n";
}

if (in_array('docs_dashboard', $public_vars)) {
    echo "✅ 'docs_dashboard' is in public query vars<br>\n";
} else {
    echo "❌ 'docs_dashboard' NOT in public query vars<br>\n";
}

// Step 4: Simulate URL test
echo "<h3>Step 4: Simulate URL Access</h3>\n";

// Test docs-login
$_GET = [];
$_POST = [];
$_REQUEST = [];
$GLOBALS['wp_query'] = new WP_Query();

// Simulate rewrite
$test_url = '/docs-login/';
$matched = false;
foreach ($docs_rules as $rule => $match) {
    if (preg_match('#^' . $rule . '$#', trim($test_url, '/'), $matches)) {
        echo "✅ URL '$test_url' matches rule: <code>$rule</code><br>\n";
        echo "   Query string: <code>$match</code><br>\n";
        $matched = true;
        
        // Parse the match
        if ($match === 'index.php?docs_login=1') {
            $_GET['docs_login'] = '1';
            $GLOBALS['wp_query']->set('docs_login', '1');
            echo "   ✅ Query var set: docs_login = 1<br>\n";
        }
        break;
    }
}

if (!$matched) {
    echo "❌ URL '$test_url' did not match any rules<br>\n";
}

// Step 5: Test frontend login class
echo "<h3>Step 5: Test Frontend Login Class</h3>\n";

if (class_exists('LIFT_Docs_Frontend_Login')) {
    echo "✅ LIFT_Docs_Frontend_Login class exists<br>\n";
    
    // Test method exists
    if (method_exists('LIFT_Docs_Frontend_Login', 'handle_docs_login_page')) {
        echo "✅ handle_docs_login_page method exists<br>\n";
    } else {
        echo "❌ handle_docs_login_page method NOT found<br>\n";
    }
} else {
    echo "❌ LIFT_Docs_Frontend_Login class NOT found<br>\n";
}

// Step 6: Test get_query_var
echo "<h3>Step 6: Test Query Var Function</h3>\n";
$docs_login_var = get_query_var('docs_login');
if ($docs_login_var) {
    echo "✅ get_query_var('docs_login') returns: '$docs_login_var'<br>\n";
} else {
    echo "❌ get_query_var('docs_login') is empty<br>\n";
}

// Step 7: Test actual URL
echo "<h3>Step 7: Test URLs</h3>\n";
$login_url = home_url('/docs-login');
$dashboard_url = home_url('/docs-dashboard');

echo "🔗 <strong>Login URL:</strong> <a href='$login_url' target='_blank'>$login_url</a><br>\n";
echo "🔗 <strong>Dashboard URL:</strong> <a href='$dashboard_url' target='_blank'>$dashboard_url</a><br>\n";

// Step 8: Check hooks
echo "<h3>Step 8: Check WordPress Hooks</h3>\n";

// List actions for template_redirect
global $wp_filter;
if (isset($wp_filter['template_redirect'])) {
    echo "✅ template_redirect hooks found:<br>\n";
    foreach ($wp_filter['template_redirect']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && 
                isset($callback['function'][0]) && 
                is_object($callback['function'][0]) && 
                get_class($callback['function'][0]) === 'LIFT_Docs_Frontend_Login') {
                echo "   - Priority $priority: " . get_class($callback['function'][0]) . "::" . $callback['function'][1] . "<br>\n";
            }
        }
    }
} else {
    echo "❌ No template_redirect hooks found<br>\n";
}

echo "<h3>✅ Debugging Complete</h3>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>Visit the login URL above to test</li>\n";
echo "<li>Check if the page loads correctly</li>\n";
echo "<li>Try logging in with a documents_user account</li>\n";
echo "</ol>\n";
?>
