<?php
// Test script for file info processing
echo "=== LIFT Docs File Info Test ===\n\n";

// Test URLs
$test_urls = array(
    'https://example.com/files/document.pdf',
    'https://example.com/uploads/2024/report.docx',
    'https://example.com/path/file%20with%20spaces.xlsx',
    'https://example.com/media/image.jpg?v=123',
    'https://example.com/download/',
    'https://example.com/path/file_without_extension',
    ''
);

foreach ($test_urls as $index => $url) {
    echo "Test " . ($index + 1) . ": $url\n";
    echo "----------------------------------------\n";
    
    // Parse URL to get clean filename
    $parsed_url = parse_url($url);
    $file_path = isset($parsed_url['path']) ? $parsed_url['path'] : $url;
    $file_name = basename($file_path);
    
    echo "Parsed path: $file_path\n";
    echo "Raw filename: $file_name\n";
    
    // Handle case where filename might be empty or problematic
    if (empty($file_name) || $file_name === '/' || $file_name === '.' || strlen(trim($file_name)) === 0) {
        $file_name = 'document_file_' . ($index + 1);
        echo "Using fallback filename: $file_name\n";
    }
    
    // Clean filename but preserve original if sanitization makes it empty
    $original_name = $file_name;
    $file_name = sanitize_file_name($file_name);
    
    // If sanitization resulted in empty string, use original or fallback
    if (empty($file_name)) {
        $file_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
        if (empty($file_name)) {
            $file_name = 'document_file_' . ($index + 1);
        }
    }
    
    // Get extension
    $file_extension = strtoupper(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // If still no extension, try from original URL
    if (empty($file_extension)) {
        $url_extension = strtoupper(pathinfo($url, PATHINFO_EXTENSION));
        $file_extension = !empty($url_extension) ? $url_extension : 'FILE';
    }
    
    echo "Final filename: '$file_name' (length: " . strlen($file_name) . ")\n";
    echo "Final extension: '$file_extension' (length: " . strlen($file_extension) . ")\n";
    echo "Empty check: Name empty? " . (empty($file_name) ? 'YES' : 'NO') . ", Ext empty? " . (empty($file_extension) ? 'YES' : 'NO') . "\n";
    echo "\n";
}

// Simple sanitize_file_name function for testing
function sanitize_file_name($filename) {
    // Basic sanitization
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}
?>
