<?php
/**
 * AJAX Save Debug Tool
 * Debug chính xác vấn đề với form save
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add enhanced logging to AJAX save process
add_action('wp_ajax_lift_forms_save', 'lift_debug_ajax_save_detailed', 1);

function lift_debug_ajax_save_detailed() {
    // Log everything for debugging
    error_log('=== LIFT FORMS SAVE DEBUG START ===');
    error_log('POST data: ' . print_r($_POST ?? [], true));
    
    // Check if required fields exist - with null safety
    $required_fields = ['nonce', 'form_id', 'name', 'fields'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            error_log("MISSING FIELD: $field");
        } else {
            $value = $_POST[$field] ?? '';
            // Ensure value is string for logging
            $value = is_string($value) ? $value : print_r($value, true);
            error_log("$field: " . $value);
        }
    }
    
    // Detailed field analysis - with null safety
    if (isset($_POST['fields']) && !empty($_POST['fields'])) {
        $fields = $_POST['fields'];
        // Ensure fields is string
        if (is_string($fields)) {
            error_log('Fields raw: ' . $fields);
            error_log('Fields length: ' . strlen($fields));
            error_log('Fields type: ' . gettype($fields));
            
            // Test JSON decode
            $decoded = json_decode($fields, true);
            error_log('JSON decode error: ' . json_last_error_msg());
            error_log('Decoded result: ' . print_r($decoded ?? [], true));
            
            if (is_array($decoded)) {
                error_log('Decoded array count: ' . count($decoded));
                foreach ($decoded as $i => $field) {
                    error_log("Field $i: " . print_r($field ?? [], true));
                }
            }
        } else {
            error_log('Fields is not a string: ' . print_r($fields, true));
        }
    } else {
        error_log('No fields data in POST or fields is empty');
    }
    
    error_log('=== LIFT FORMS SAVE DEBUG END ===');
}

// Add admin menu for testing
add_action('admin_menu', 'lift_debug_save_menu');

function lift_debug_save_menu() {
    add_submenu_page(
        null,
        'LIFT Save Debug',
        'LIFT Save Debug',
        'manage_options',
        'lift-save-debug',
        'lift_save_debug_page'
    );
}

function lift_save_debug_page() {
    if (isset($_POST['test_save'])) {
        // Test manual save
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $test_fields = json_encode([
            [
                'id' => 'test_field_' . time(),
                'name' => 'test_field',
                'type' => 'text',
                'label' => 'Test Field',
                'placeholder' => 'Test placeholder',
                'required' => false,
                'description' => 'Test description',
                'order' => 0
            ]
        ]);
        
        $data = array(
            'name' => 'Manual Test Form ' . date('H:i:s'),
            'description' => 'Created by debug tool',
            'form_fields' => $test_fields,
            'settings' => '{}',
            'status' => 'active',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($forms_table, $data);
        
        if ($result !== false) {
            $insert_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>✅ Manual save successful! Form ID: ' . $insert_id . '</p></div>';
            
            // Verify the save
            $saved_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $insert_id));
            if ($saved_form) {
                echo '<div class="notice notice-info"><p>📋 Saved form data:</p>';
                echo '<pre>' . print_r($saved_form, true) . '</pre></div>';
                
                // Test JSON decode
                $saved_fields = json_decode($saved_form->form_fields, true);
                if ($saved_fields) {
                    echo '<div class="notice notice-success"><p>✅ JSON decode successful!</p>';
                    echo '<pre>' . print_r($saved_fields, true) . '</pre></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ JSON decode failed: ' . json_last_error_msg() . '</p></div>';
                }
            }
        } else {
            echo '<div class="notice notice-error"><p>❌ Manual save failed: ' . $wpdb->last_error . '</p></div>';
        }
    }
    
    if (isset($_POST['test_ajax'])) {
        // Test AJAX call simulation
        echo '<div class="notice notice-info"><p>🧪 Testing AJAX call...</p></div>';
        
        // Simulate AJAX data
        $test_data = [
            'action' => 'lift_forms_save',
            'nonce' => wp_create_nonce('lift_forms_nonce'),
            'form_id' => 0,
            'name' => 'AJAX Test Form ' . date('H:i:s'),
            'description' => 'Created by AJAX test',
            'fields' => json_encode([
                [
                    'id' => 'ajax_test_field_' . time(),
                    'name' => 'ajax_test_field',
                    'type' => 'text',
                    'label' => 'AJAX Test Field',
                    'placeholder' => 'Enter text here',
                    'required' => false,
                    'description' => 'Test field from AJAX',
                    'order' => 0
                ]
            ]),
            'settings' => '{}'
        ];
        
        // Set up $_POST
        $backup_post = $_POST;
        $_POST = $test_data;
        
        // Capture output
        ob_start();
        $lift_forms = new LIFT_Forms();
        $lift_forms->ajax_save_form();
        $output = ob_get_clean();
        
        // Restore $_POST
        $_POST = $backup_post;
        
        // Fix null output issue
        $output = $output ?: '';
        
        echo '<div class="notice notice-info"><p>📤 AJAX Response:</p>';
        echo '<pre>' . esc_html($output) . '</pre></div>';
        
        // Check if it was successful - with null safety
        if (!empty($output) && strpos($output, '"success":true') !== false) {
            echo '<div class="notice notice-success"><p>✅ AJAX test successful!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ AJAX test failed!</p></div>';
            if (empty($output)) {
                echo '<div class="notice notice-warning"><p>⚠️ Empty response - check error log</p></div>';
            }
        }
    }
    
    // Show recent forms
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    $recent_forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY created_at DESC LIMIT 5");
    
    ?>
    <div class="wrap">
        <h1>🐛 LIFT Forms Save Debug</h1>
        
        <div class="card">
            <h2>Manual Tests</h2>
            <form method="post">
                <p>
                    <input type="submit" name="test_save" value="🧪 Test Manual Save" class="button button-primary">
                    <span class="description">Test database insert directly</span>
                </p>
            </form>
            
            <form method="post">
                <p>
                    <input type="submit" name="test_ajax" value="🌐 Test AJAX Save" class="button button-primary">
                    <span class="description">Test AJAX handler directly</span>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Recent Forms</h2>
            <?php if ($recent_forms): ?>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Fields</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_forms as $form): ?>
                            <tr>
                                <td><?php echo $form->id; ?></td>
                                <td><?php echo esc_html($form->name); ?></td>
                                <td>
                                    <?php 
                                    $form_fields = $form->form_fields ?? '';
                                    // Ensure we have a string
                                    $form_fields = is_string($form_fields) ? $form_fields : '';
                                    
                                    if (!empty($form_fields)) {
                                        $fields = json_decode($form_fields, true);
                                        if ($fields && is_array($fields)) {
                                            echo count($fields) . ' fields';
                                            echo '<br><small>' . esc_html(substr($form_fields, 0, 100)) . '...</small>';
                                        } else {
                                            echo '<span style="color: red;">JSON Error: ' . json_last_error_msg() . '</span>';
                                            echo '<br><small>' . esc_html($form_fields) . '</small>';
                                        }
                                    } else {
                                        echo '<span style="color: gray;">(Empty fields)</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $form->created_at; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No forms found.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Debug Instructions</h2>
            <ol>
                <li><strong>Check Error Log:</strong> Look at PHP error log for detailed AJAX debug info</li>
                <li><strong>Test Manual Save:</strong> Use button above to test database directly</li>
                <li><strong>Test AJAX Save:</strong> Use button above to test AJAX handler</li>
                <li><strong>Try Form Builder:</strong> Go to Form Builder and monitor browser console</li>
                <li><strong>Check Network Tab:</strong> Monitor AJAX calls in browser developer tools</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Live AJAX Monitor</h2>
            <p>Open browser console and run this command to monitor AJAX calls:</p>
            <code>
                // Monitor AJAX calls<br>
                $(document).ajaxSend(function(event, xhr, settings) {<br>
                &nbsp;&nbsp;if (settings.data && settings.data.includes('lift_forms_save')) {<br>
                &nbsp;&nbsp;&nbsp;&nbsp;console.log('AJAX Send:', settings.data);<br>
                &nbsp;&nbsp;}<br>
                });<br><br>
                
                $(document).ajaxComplete(function(event, xhr, settings) {<br>
                &nbsp;&nbsp;if (settings.data && settings.data.includes('lift_forms_save')) {<br>
                &nbsp;&nbsp;&nbsp;&nbsp;console.log('AJAX Response:', xhr.responseText);<br>
                &nbsp;&nbsp;}<br>
                });
            </code>
        </div>
    </div>
    <?php
}
?>
