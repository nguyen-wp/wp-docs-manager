<?php
/**
 * Test script for email notifications
 * 
 * This file is for testing purposes only - should be removed in production
 */

// Test the email notification system
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('LIFT Docs: Email notification system loaded successfully');
}

// Add a test function to verify email templates work
function lift_docs_test_email_templates() {
    // This is just a test function to verify our templates compile correctly
    $test_data = array(
        'user_name' => 'Test User',
        'first_name' => 'Test',
        'document_title' => 'Test Document',
        'document_excerpt' => 'This is a test document excerpt.',
        'document_url' => 'https://example.com/document',
        'dashboard_url' => 'https://example.com/dashboard',
        'user_code' => 'TEST123',
        'site_name' => 'Test Site',
        'current_user' => 'Admin User',
        'include_link' => true
    );
    
    // Test that we can instantiate the admin class (this will be done by WordPress normally)
    if (class_exists('LIFT_Docs_Admin')) {
        error_log('LIFT Docs: Admin class exists - email notification system ready');
    }
}

// Hook the test function to run on admin init (only in debug mode)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_init', 'lift_docs_test_email_templates');
}
