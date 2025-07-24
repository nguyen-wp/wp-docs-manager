<?php
/**
 * Force plugin reinitialization
 * Access: /wp-content/plugins/wp-docs-manager/force-reinit.php
 */

// Load WordPress
require_once('../../../wp-config.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    echo "Access denied. You must be an administrator.";
    exit;
}

echo "<h2>Force Plugin Reinitialization</h2>";

// Load LIFT Forms class
require_once('includes/class-lift-forms.php');

// Create instance and force init
$lift_forms = new LIFT_Forms();
$lift_forms->init();

echo "<p>✅ Plugin reinitialized successfully!</p>";

// Check database structure
global $wpdb;
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

echo "<h3>Database Structure Check:</h3>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$submissions_table}");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column->Field}</td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>{$column->Default}</td>";
    echo "<td>{$column->Extra}</td>";
    echo "</tr>";
}
echo "</table>";

// Test if updated_at column exists
$updated_at_exists = false;
foreach ($columns as $column) {
    if ($column->Field === 'updated_at') {
        $updated_at_exists = true;
        break;
    }
}

echo "<p><strong>updated_at column exists:</strong> " . ($updated_at_exists ? "✅ Yes" : "❌ No") . "</p>";

if (!$updated_at_exists) {
    echo "<h3>Adding missing updated_at column...</h3>";
    $result = $wpdb->query("ALTER TABLE {$submissions_table} ADD COLUMN updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER submitted_at");
    if ($result !== false) {
        echo "<p>✅ updated_at column added successfully!</p>";
    } else {
        echo "<p>❌ Failed to add updated_at column: " . $wpdb->last_error . "</p>";
    }
}

echo "<h3>Recent Error Log Entries:</h3>";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    $recent_lines = array_slice($log_lines, -20); // Last 20 lines
    
    echo "<div style='background: #f0f0f0; padding: 10px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;'>";
    foreach ($recent_lines as $line) {
        if (strpos($line, 'LIFT') !== false || strpos($line, 'lift') !== false) {
            echo "<div style='color: #c00;'>" . htmlspecialchars($line) . "</div>";
        }
    }
    echo "</div>";
} else {
    echo "<p>Debug log file not found at: {$log_file}</p>";
}

?>

<style>
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
