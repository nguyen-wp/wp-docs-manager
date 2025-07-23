<?php
/**
 * Test file for secure links functionality
 * 
 * This file demonstrates how the secure links work with the new URL format:
 * /lift-docs/secure/?lift_secure=encrypted_token
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

/**
 * Test secure link generation
 */
function test_lift_secure_link_generation($document_id = 1) {
    // Check if LIFT Docs is active
    if (!class_exists('LIFT_Docs_Settings')) {
        return 'LIFT Docs System not found';
    }
    
    // Generate secure link
    $secure_link = LIFT_Docs_Settings::generate_secure_link($document_id, 24);
    
    return array(
        'document_id' => $document_id,
        'secure_url' => $secure_link,
        'format' => 'Expected format: /lift-docs/secure/?lift_secure=encrypted_token',
        'expiry' => '24 hours from generation'
    );
}

/**
 * Test URL parsing
 */
function test_lift_secure_url_parsing($url) {
    $parsed_url = parse_url($url);
    $query_params = array();
    
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
    }
    
    return array(
        'url' => $url,
        'path' => $parsed_url['path'] ?? '',
        'token' => $query_params['lift_secure'] ?? '',
        'is_valid_format' => (
            ($parsed_url['path'] ?? '') === '/lift-docs/secure/' && 
            !empty($query_params['lift_secure'])
        )
    );
}

/**
 * Test rewrite rules
 */
function test_lift_rewrite_rules() {
    global $wp_rewrite;
    
    $rules = get_option('rewrite_rules');
    $lift_rules = array();
    
    foreach ($rules as $pattern => $rewrite) {
        if (strpos($pattern, 'lift-docs') !== false || strpos($rewrite, 'lift_secure') !== false) {
            $lift_rules[$pattern] = $rewrite;
        }
    }
    
    return array(
        'total_rules' => count($rules),
        'lift_rules' => $lift_rules,
        'expected_rule' => '^lift-docs/secure/?$ => index.php?lift_secure_page=1'
    );
}

// Example usage (uncomment to test):
/*
if (is_admin() && current_user_can('administrator')) {
    add_action('admin_notices', function() {
        $test_result = test_lift_secure_link_generation(1);
        echo '<div class="notice notice-info"><pre>' . print_r($test_result, true) . '</pre></div>';
        
        $url_test = test_lift_secure_url_parsing($test_result['secure_url']);
        echo '<div class="notice notice-info"><pre>' . print_r($url_test, true) . '</pre></div>';
    });
}
*/
