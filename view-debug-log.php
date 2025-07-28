<?php
/**
 * Debug log viewer cho LIFT Docs
 * Truy cập: your-site.com/wp-content/plugins/wp-docs-manager/view-debug-log.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please login as admin first.');
}

$log_file = WP_CONTENT_DIR . '/lift-docs-debug.log';

echo "<h1>LIFT Docs Debug Log</h1>";

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    if (file_exists($log_file)) {
        unlink($log_file);
        echo "<p style='color: green;'>Log file cleared!</p>";
    }
}

echo "<p><a href='?clear=1' onclick='return confirm(\"Are you sure you want to clear the log?\")'>Clear Log</a></p>";

if (!file_exists($log_file)) {
    echo "<p>No debug log file found yet. Try accessing a secure link first.</p>";
    echo "<p>Log file path: $log_file</p>";
} else {
    $log_content = file_get_contents($log_file);
    $log_size = filesize($log_file);
    
    echo "<p>Log file size: " . number_format($log_size) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($log_file)) . "</p>";
    
    if (empty($log_content)) {
        echo "<p>Log file is empty.</p>";
    } else {
        echo "<h2>Log Content (newest first):</h2>";
        echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; max-height: 500px; overflow-y: auto; font-family: monospace; white-space: pre-wrap;'>";
        
        // Reverse log lines to show newest first
        $lines = explode("\n", trim($log_content));
        $lines = array_reverse($lines);
        
        foreach ($lines as $line) {
            if (!empty($line)) {
                // Color code different types of messages
                if (strpos($line, 'track_document_view') !== false) {
                    echo "<span style='color: blue;'>$line</span>\n";
                } elseif (strpos($line, 'track_document_download') !== false) {
                    echo "<span style='color: green;'>$line</span>\n";
                } elseif (strpos($line, 'handle_secure') !== false) {
                    echo "<span style='color: orange;'>$line</span>\n";
                } elseif (strpos($line, 'error') !== false || strpos($line, 'failed') !== false) {
                    echo "<span style='color: red;'>$line</span>\n";
                } elseif (strpos($line, 'success') !== false) {
                    echo "<span style='color: darkgreen; font-weight: bold;'>$line</span>\n";
                } else {
                    echo "$line\n";
                }
            }
        }
        
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><strong>Testing Instructions:</strong></p>";
echo "<ol>";
echo "<li>Generate a secure link from a document in admin</li>";
echo "<li>Access the secure link in a new tab</li>";
echo "<li>Refresh this page to see debug logs</li>";
echo "<li>Check if tracking methods are being called</li>";
echo "</ol>";

echo "<p><a href='" . admin_url('edit.php?post_type=lift_document') . "'>Go to Documents</a></p>";
echo "<p><a href='test-tracking.php'>Run Tracking Test</a></p>";
?>

<script>
// Auto-refresh every 10 seconds
setTimeout(function() {
    location.reload();
}, 10000);
</script>
