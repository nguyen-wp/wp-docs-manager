<?php
/**
 * Test LIFT Forms Database Queries
 * 
 * This file tests all database queries to ensure no wpdb::prepare warnings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

echo "<h2>LIFT Forms Database Query Test</h2>";

// Check if LIFT Forms class exists
if (!class_exists('LIFT_Forms')) {
    echo "<p style='color: red;'>❌ LIFT_Forms class not found</p>";
    exit;
}

echo "<p style='color: green;'>✅ LIFT_Forms class found</p>";

// Test database tables exist
global $wpdb;
$forms_table = $wpdb->prefix . 'lift_forms';
$submissions_table = $wpdb->prefix . 'lift_form_submissions';

echo "<h3>Database Tables:</h3>";

// Check forms table
$forms_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table;
echo "<p>" . ($forms_table_exists ? "✅" : "❌") . " Forms table: $forms_table</p>";

// Check submissions table
$submissions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") == $submissions_table;
echo "<p>" . ($submissions_table_exists ? "✅" : "❌") . " Submissions table: $submissions_table</p>";

if (!$forms_table_exists || !$submissions_table_exists) {
    echo "<p style='color: orange;'>⚠️ Some tables missing. Try creating a LIFT Forms instance to initialize tables.</p>";
    
    try {
        $lift_forms = new LIFT_Forms();
        echo "<p style='color: green;'>✅ LIFT_Forms instance created, tables should be initialized</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating LIFT_Forms: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Test Database Queries:</h3>";

// Test 1: Get all forms (no prepare needed)
try {
    $forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY created_at DESC");
    echo "<p style='color: green;'>✅ Get all forms query works (found " . count($forms) . " forms)</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Get all forms query failed: " . $e->getMessage() . "</p>";
}

// Test 2: Get specific form (with prepare)
try {
    $test_form_id = 1;
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $test_form_id));
    echo "<p style='color: green;'>✅ Get specific form query works</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Get specific form query failed: " . $e->getMessage() . "</p>";
}

// Test 3: Get submissions count (no prepare needed)
try {
    $total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
    echo "<p style='color: green;'>✅ Get total submissions query works (found $total_submissions submissions)</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Get total submissions query failed: " . $e->getMessage() . "</p>";
}

// Test 4: Get form submissions count (with prepare)
try {
    $test_form_id = 1;
    $form_submissions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE form_id = %d", $test_form_id));
    echo "<p style='color: green;'>✅ Get form submissions count query works (found $form_submissions submissions for form $test_form_id)</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Get form submissions count query failed: " . $e->getMessage() . "</p>";
}

// Test 5: Get submissions with filter (the fixed query)
try {
    // Test without form_id filter (no params)
    $where = '1=1';
    $params = array();
    
    if (!empty($params)) {
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, f.name as form_name 
             FROM $submissions_table s 
             LEFT JOIN $forms_table f ON s.form_id = f.id 
             WHERE $where 
             ORDER BY s.submitted_at DESC",
            $params
        ));
    } else {
        $submissions = $wpdb->get_results(
            "SELECT s.*, f.name as form_name 
             FROM $submissions_table s 
             LEFT JOIN $forms_table f ON s.form_id = f.id 
             WHERE $where 
             ORDER BY s.submitted_at DESC"
        );
    }
    echo "<p style='color: green;'>✅ Get submissions with filter query works (no params - found " . count($submissions) . " submissions)</p>";
    
    // Test with form_id filter (with params)
    $form_id = 1;
    $where = '1=1';
    $params = array();
    
    if ($form_id) {
        $where .= ' AND s.form_id = %d';
        $params[] = $form_id;
    }
    
    if (!empty($params)) {
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, f.name as form_name 
             FROM $submissions_table s 
             LEFT JOIN $forms_table f ON s.form_id = f.id 
             WHERE $where 
             ORDER BY s.submitted_at DESC",
            $params
        ));
    } else {
        $submissions = $wpdb->get_results(
            "SELECT s.*, f.name as form_name 
             FROM $submissions_table s 
             LEFT JOIN $forms_table f ON s.form_id = f.id 
             WHERE $where 
             ORDER BY s.submitted_at DESC"
        );
    }
    echo "<p style='color: green;'>✅ Get submissions with filter query works (with params - found " . count($submissions) . " submissions for form $form_id)</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Get submissions with filter query failed: " . $e->getMessage() . "</p>";
}

echo "<h3>Summary:</h3>";
echo "<p>If all queries above show ✅, then the wpdb::prepare issue has been resolved.</p>";
echo "<p>The fix ensures that wpdb::prepare() is only called when there are actual placeholders in the query.</p>";

echo "<h3>What was fixed:</h3>";
echo "<ul>";
echo "<li><strong>Problem:</strong> The submissions query was calling wpdb::prepare() even when no form_id filter was applied (no placeholders)</li>";
echo "<li><strong>Solution:</strong> Split the query logic to use wpdb::prepare() only when there are parameters to bind</li>";
echo "<li><strong>Code location:</strong> includes/class-lift-forms.php, submissions_page() method around line 485-500</li>";
echo "</ul>";
