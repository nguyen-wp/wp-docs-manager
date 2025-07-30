#!/usr/bin/env php
<?php
/**
 * Standalone test for import/export validation logic
 */

echo "🧪 Testing LIFT Forms Import/Export Logic\n";
echo "=========================================\n\n";

// Simulate export data structure (what would come from database)
$simulated_db_form = (object) array(
    'id' => 1,
    'name' => 'Test Form',
    'description' => 'Test description',
    'form_fields' => json_encode(array(
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
    )),
    'status' => 'active'
);

echo "📝 Step 1: Simulating export process...\n";

// Simulate export logic
$form_fields_data = json_decode($simulated_db_form->form_fields, true);

echo "✅ Form fields decoded successfully\n";
echo "📊 Form fields keys: " . implode(', ', array_keys($form_fields_data)) . "\n";

// Extract layout and fields
$layout_data = null;
$fields_data = null;

if (is_array($form_fields_data)) {
    if (isset($form_fields_data['layout'])) {
        $layout_data = $form_fields_data['layout'];
        echo "✅ Layout extracted\n";
    }
    if (isset($form_fields_data['fields'])) {
        $fields_data = $form_fields_data['fields'];
        echo "✅ Fields extracted\n";
    }
}

// Create export data
$export_data = array(
    'name' => $simulated_db_form->name,
    'description' => $simulated_db_form->description,
    'layout' => $layout_data,
    'fields' => $fields_data,
    'export_info' => array(
        'exported_at' => date('Y-m-d H:i:s'),
        'exported_by' => 'Test Script',
        'plugin_version' => '1.0.0'
    )
);

echo "✅ Export data created\n";
echo "📊 Export data keys: " . implode(', ', array_keys($export_data)) . "\n\n";

echo "📝 Step 2: Simulating import validation...\n";

// Simulate validation logic
function validate_form_import_data($data) {
    echo "🔍 Validating data...\n";
    
    // Check if data is valid
    if (!is_array($data) || empty($data)) {
        return array('valid' => false, 'error' => 'Invalid data format');
    }
    
    echo "📊 Available keys: " . implode(', ', array_keys($data)) . "\n";
    
    // Check if it's a single form or multiple forms backup
    if (isset($data['forms']) && is_array($data['forms'])) {
        return array('valid' => false, 'error' => 'Multiple forms backup detected');
    }

    // Required fields for single form
    $required_fields = array('name', 'layout', 'fields');
    
    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $data)) {
            echo "❌ Missing field '$field'\n";
            return array('valid' => false, 'error' => "Missing required field: $field");
        } else {
            echo "✅ Field '$field' found\n";
        }
    }

    // Validate layout structure
    if (!is_array($data['layout'])) {
        return array('valid' => false, 'error' => 'Invalid layout structure - layout must be an array');
    }
    
    if (!isset($data['layout']['rows']) && !array_key_exists('rows', $data['layout'])) {
        echo "❌ Layout missing rows. Layout keys: " . implode(', ', array_keys($data['layout'])) . "\n";
        return array('valid' => false, 'error' => 'Invalid layout structure - layout must contain rows array');
    } else {
        echo "✅ Layout has rows\n";
    }

    // Validate fields structure
    if (!is_array($data['fields'])) {
        return array('valid' => false, 'error' => 'Invalid fields structure - fields must be an array');
    } else {
        echo "✅ Fields is an array\n";
    }

    echo "✅ Validation passed successfully\n";
    return array('valid' => true);
}

$validation_result = validate_form_import_data($export_data);

if ($validation_result['valid']) {
    echo "\n🎉 SUCCESS: Import validation passed!\n";
    
    echo "\n📝 Step 3: Simulating import process...\n";
    
    // Simulate import data preparation
    $form_fields_data = array();
    
    if (isset($export_data['layout'])) {
        $form_fields_data['layout'] = $export_data['layout'];
    }
    
    if (isset($export_data['fields'])) {
        $form_fields_data['fields'] = $export_data['fields'];
    }

    $insert_data = array(
        'name' => $export_data['name'],
        'description' => $export_data['description'],
        'form_fields' => json_encode($form_fields_data),
        'status' => 'draft'
    );
    
    echo "✅ Import data prepared\n";
    echo "📊 Form fields JSON length: " . strlen($insert_data['form_fields']) . " bytes\n";
    
    // Verify the JSON can be decoded back
    $decoded_back = json_decode($insert_data['form_fields'], true);
    if ($decoded_back && isset($decoded_back['layout']) && isset($decoded_back['fields'])) {
        echo "✅ Roundtrip JSON encode/decode successful\n";
    } else {
        echo "❌ JSON roundtrip failed\n";
    }
    
} else {
    echo "\n❌ FAILED: " . $validation_result['error'] . "\n";
}

echo "\n📋 Test completed!\n";
?>
