<?php
/**
 * Debug script for testing secure download links
 * Add this to a WordPress page to test encryption/decryption
 */

// Test encryption/decryption functionality
function test_lift_encryption_debug() {
    if (!class_exists('LIFT_Docs_Settings')) {
        return 'LIFT Docs System not loaded';
    }
    
    // Test data
    $test_document_id = 123;
    
    echo "<h3>Testing LIFT Docs Secure Link Encryption</h3>";
    
    try {
        // Generate secure download link
        echo "<p><strong>1. Generating secure download link for document $test_document_id:</strong></p>";
        $secure_url = LIFT_Docs_Settings::generate_secure_download_link($test_document_id);
        echo "<p>Generated URL: <code>" . esc_html($secure_url) . "</code></p>";
        
        // Extract token from URL
        $parsed_url = parse_url($secure_url);
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
            $token = $query_params['lift_secure'] ?? '';
            
            if (!empty($token)) {
                echo "<p><strong>2. Extracted token:</strong></p>";
                echo "<p><code>" . esc_html($token) . "</code></p>";
                
                // Verify token
                echo "<p><strong>3. Verifying token:</strong></p>";
                $verified_id = LIFT_Docs_Settings::verify_secure_link(urldecode($token));
                
                if ($verified_id == $test_document_id) {
                    echo "<p style='color: green;'>✅ SUCCESS: Token verification passed! Verified document ID: $verified_id</p>";
                } else {
                    echo "<p style='color: red;'>❌ FAILED: Token verification failed! Expected: $test_document_id, Got: " . ($verified_id ? $verified_id : 'false') . "</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ FAILED: No token found in URL</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ FAILED: No query parameters found in URL</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR: " . esc_html($e->getMessage()) . "</p>";
    }
}

// Auto-run test if LIFT Docs is available
if (class_exists('LIFT_Docs_Settings')) {
    test_lift_encryption_debug();
} else {
    echo "<p>LIFT Docs System not found. Make sure the plugin is active.</p>";
}
?>
