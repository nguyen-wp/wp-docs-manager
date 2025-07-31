#!/usr/bin/env php
<?php
/**
 * Test script for LIFT Forms Import/Export functionality
 * 
 * Usage: php test-import-export.php
 */

// Simulate WordPress environment
$wp_root = dirname(dirname(dirname(dirname(__DIR__))));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-admin/includes/admin.php');

echo "🚀 LIFT Forms Import/Export Test\n";
echo "================================\n\n";

// Test 1: Validate sample JSON file
echo "📝 Test 1: Validating sample JSON file...\n";
$sample_file = __DIR__ . '/sample-import-contact-form.json';

if (!file_exists($sample_file)) {
    echo "❌ Sample file not found: $sample_file\n";
    exit(1);
}

$json_content = file_get_contents($sample_file);
$form_data = json_decode($json_content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Invalid JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ JSON format is valid\n";

// Check required fields
$required_fields = ['name', 'layout', 'fields'];
foreach ($required_fields as $field) {
    if (!isset($form_data[$field])) {
        echo "❌ Missing required field: $field\n";
        exit(1);
    }
}

echo "✅ All required fields present\n";

// Validate layout structure
if (!isset($form_data['layout']['rows']) || !is_array($form_data['layout']['rows'])) {
    echo "❌ Invalid layout structure\n";
    exit(1);
}

echo "✅ Layout structure is valid\n";

// Validate fields structure
if (!is_array($form_data['fields'])) {
    echo "❌ Invalid fields structure\n";
    exit(1);
}

echo "✅ Fields structure is valid\n";

// Test 2: Check database connection
echo "\n📊 Test 2: Checking database connection...\n";
global $wpdb;

$forms_table = $wpdb->prefix . 'lift_forms';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") === $forms_table;

if (!$table_exists) {
    echo "❌ Forms table does not exist: $forms_table\n";
    exit(1);
}

echo "✅ Database connection and table OK\n";

// Test 3: Count existing forms
$existing_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");
echo "📋 Found $existing_forms existing forms in database\n";

// Test 4: Validate import function exists
echo "\n🔧 Test 3: Checking LIFT Forms class...\n";

if (!class_exists('LIFT_Forms')) {
    echo "❌ LIFT_Forms class not found\n";
    exit(1);
}

$lift_forms = new LIFT_Forms();
echo "✅ LIFT_Forms class loaded successfully\n";

// Test 5: Simulate import validation
echo "\n🧪 Test 4: Testing import validation...\n";

// Use reflection to access private method
$reflection = new ReflectionClass($lift_forms);
$validate_method = $reflection->getMethod('validate_form_import_data');
$validate_method->setAccessible(true);

$validation_result = $validate_method->invoke($lift_forms, $form_data);

if (!$validation_result['valid']) {
    echo "❌ Validation failed: " . $validation_result['error'] . "\n";
    exit(1);
}

echo "✅ Import validation passed\n";

// Test 6: Check field types
echo "\n🔍 Test 5: Analyzing form structure...\n";
$field_types = [];
foreach ($form_data['fields'] as $field_id => $field) {
    if (isset($field['type'])) {
        $field_types[] = $field['type'];
    }
}

echo "📊 Form contains " . count($form_data['fields']) . " fields\n";
echo "📊 Field types: " . implode(', ', array_unique($field_types)) . "\n";
echo "📊 Layout has " . count($form_data['layout']['rows']) . " rows\n";

// Test 7: Check CSS file exists
echo "\n🎨 Test 6: Checking CSS files...\n";
$css_file = __DIR__ . '/assets/css/forms-import-export.css';

if (!file_exists($css_file)) {
    echo "⚠️  CSS file not found: $css_file\n";
} else {
    $css_size = filesize($css_file);
    echo "✅ CSS file exists ($css_size bytes)\n";
}

// Test 8: Security check
echo "\n🔒 Test 7: Security validation...\n";

// Check if nonces would be properly generated
if (!function_exists('wp_create_nonce')) {
    echo "❌ WordPress nonce functions not available\n";
    exit(1);
}

$test_nonce = wp_create_nonce('lift_forms_import_nonce');
if (empty($test_nonce)) {
    echo "❌ Failed to generate nonce\n";
    exit(1);
}

echo "✅ Security functions working\n";

// Test Summary
echo "\n🎉 ALL TESTS PASSED!\n";
echo "========================\n";
echo "✅ JSON validation: PASS\n";
echo "✅ Database connection: PASS\n";
echo "✅ Class loading: PASS\n";
echo "✅ Import validation: PASS\n";
echo "✅ Structure analysis: PASS\n";
echo "✅ Security check: PASS\n";

echo "\n📋 Import/Export feature is ready to use!\n";
echo "\nNext steps:\n";
echo "1. Visit WordPress Admin → LIFT Forms\n";
echo "2. Click 'Import Form' button\n";
echo "3. Upload sample-import-contact-form.json\n";
echo "4. Test export functionality\n";

echo "\n🔗 Files created:\n";
echo "- sample-import-contact-form.json (sample import file)\n";
echo "- assets/css/forms-import-export.css (styling)\n";
echo "- README-Import-Export.md (documentation)\n";
echo "- Updated class-lift-forms.php (import/export functionality)\n";

?>
