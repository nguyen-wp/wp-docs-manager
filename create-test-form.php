#!/usr/bin/env php
<?php
/**
 * Create a test form in database for testing export/import
 */

// Simple WordPress bootstrap
$wp_root = dirname(dirname(dirname(dirname(__DIR__))));
if (file_exists($wp_root . '/wp-config.php')) {
    require_once($wp_root . '/wp-config.php');
    require_once($wp_root . '/wp-admin/includes/admin.php');
} else {
    echo "WordPress not found. Skipping database test.\n";
    exit(0);
}

echo "🧪 Creating test form in database...\n";

global $wpdb;
$forms_table = $wpdb->prefix . 'lift_forms';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") === $forms_table;
if (!$table_exists) {
    echo "❌ Forms table doesn't exist: $forms_table\n";
    exit(1);
}

// Create test form data
$test_form_data = array(
    'layout' => array(
        'rows' => array(
            array(
                'id' => 'row_1',
                'type' => 'row',
                'columns' => array(
                    array(
                        'id' => 'col_1_1',
                        'width' => 12,
                        'fields' => array('test_field')
                    )
                )
            )
        )
    ),
    'fields' => array(
        'test_field' => array(
            'id' => 'test_field',
            'type' => 'text',
            'label' => 'Test Field',
            'required' => false
        )
    )
);

// Insert test form
$result = $wpdb->insert(
    $forms_table,
    array(
        'name' => 'Database Test Form',
        'description' => 'Test form created by script',
        'form_fields' => json_encode($test_form_data),
        'status' => 'active',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ),
    array('%s', '%s', '%s', '%s', '%s', '%s')
);

if ($result === false) {
    echo "❌ Failed to insert test form: " . $wpdb->last_error . "\n";
    exit(1);
}

$form_id = $wpdb->insert_id;
echo "✅ Test form created with ID: $form_id\n";

// Verify the form was created
$created_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));
if ($created_form) {
    echo "✅ Form verified in database\n";
    echo "📋 Form name: " . $created_form->name . "\n";
    echo "📋 Form fields length: " . strlen($created_form->form_fields) . " bytes\n";
    
    // Parse and validate the stored data
    $stored_data = json_decode($created_form->form_fields, true);
    if ($stored_data && isset($stored_data['layout']) && isset($stored_data['fields'])) {
        echo "✅ Form data structure is valid\n";
        echo "📊 Layout rows: " . count($stored_data['layout']['rows']) . "\n";
        echo "📊 Fields count: " . count($stored_data['fields']) . "\n";
    } else {
        echo "❌ Form data structure is invalid\n";
    }
} else {
    echo "❌ Failed to verify created form\n";
}

echo "\n🎯 Now you can test export/import with this form!\n";
echo "Form ID: $form_id\n";
?>
