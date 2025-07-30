<?php
// Quick test to debug JSON reading
$files = [
    'sample-import-contact-form.json',
    'simple-test-form.json'
];

foreach ($files as $filename) {
    $file = __DIR__ . '/' . $filename;
    echo "\n=== Testing file: $filename ===\n";

    if (!file_exists($file)) {
        echo "❌ File not found\n";
        continue;
    }

    $content = file_get_contents($file);
    echo "📄 File size: " . strlen($content) . " bytes\n";

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON error: " . json_last_error_msg() . "\n";
        continue;
    }

    echo "✅ JSON parsed successfully\n";
    echo "📊 Available keys: " . implode(', ', array_keys($data)) . "\n";

    // Check each required field
    $required = ['name', 'layout', 'fields'];
    foreach ($required as $field) {
        if (isset($data[$field])) {
            echo "✅ $field: present\n";
        } else {
            echo "❌ $field: missing\n";
        }
    }

    // Check layout structure
    if (isset($data['layout']['rows'])) {
        echo "✅ layout.rows: present (" . count($data['layout']['rows']) . " rows)\n";
    } else {
        echo "❌ layout.rows: missing\n";
    }

    echo "🔍 Form structure:\n";
    echo "- Name: " . $data['name'] . "\n";
    echo "- Fields count: " . count($data['fields']) . "\n";
    echo "- Rows count: " . count($data['layout']['rows']) . "\n";
}
?>
