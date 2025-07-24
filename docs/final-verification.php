<?php
/**
 * FINAL VERIFICATION TOOL - Test toàn bộ system
 * Kiểm tra mọi thứ từ database đến JavaScript
 */

if (!defined('ABSPATH')) {
    exit;
}

// Test direct từ admin
add_action('admin_menu', 'lift_final_verification_menu');

function lift_final_verification_menu() {
    add_submenu_page(
        'lift-docs-settings',
        'Final Verification',
        '🔧 Final Test',
        'manage_options',
        'lift-final-verification',
        'lift_final_verification_page'
    );
}

function lift_final_verification_page() {
    echo '<div class="wrap">';
    echo '<h1>🔧 LIFT Forms Final Verification</h1>';
    
    if (isset($_POST['run_test'])) {
        echo '<div class="notice notice-info"><p><strong>🔄 Running comprehensive test...</strong></p></div>';
        
        // Test 1: Database
        echo '<h3>📊 Database Test</h3>';
        global $wpdb;
        $forms_table = $wpdb->prefix . 'lift_forms';
        
        $table_check = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'");
        if ($table_check === $forms_table) {
            echo '<p style="color: green;">✅ Forms table exists</p>';
            
            $form_count = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");
            echo '<p>📊 Current forms count: ' . $form_count . '</p>';
            
            // Create test form directly in database
            $test_form_data = array(
                'name' => 'Final Verification Test Form ' . date('Y-m-d H:i:s'),
                'description' => 'This form was created by the final verification tool',
                'form_fields' => json_encode(array(
                    array(
                        'id' => 'verify_test_field_' . time(),
                        'name' => 'verification_field',
                        'type' => 'text',
                        'label' => 'Verification Test Field',
                        'placeholder' => 'This field was created by verification tool',
                        'required' => false,
                        'description' => 'Test field created automatically'
                    ),
                    array(
                        'id' => 'verify_email_field_' . time(),
                        'name' => 'verification_email',
                        'type' => 'email',
                        'label' => 'Email Field Test',
                        'placeholder' => 'test@example.com',
                        'required' => true,
                        'description' => 'Required email field'
                    )
                )),
                'settings' => '{}',
                'status' => 'active',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
            
            $insert_result = $wpdb->insert($forms_table, $test_form_data);
            
            if ($insert_result !== false) {
                $new_form_id = $wpdb->insert_id;
                echo '<p style="color: green;">✅ Successfully created test form with ID: ' . $new_form_id . '</p>';
                
                // Verify the form can be retrieved
                $retrieved_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $new_form_id));
                
                if ($retrieved_form) {
                    echo '<p style="color: green;">✅ Test form can be retrieved from database</p>';
                    
                    // Test JSON decode
                    $fields_test = json_decode($retrieved_form->form_fields, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($fields_test) && count($fields_test) > 0) {
                        echo '<p style="color: green;">✅ Form fields JSON is valid with ' . count($fields_test) . ' fields</p>';
                        echo '<pre style="background: #f0f0f0; padding: 10px; max-width: 600px; overflow: auto;">';
                        echo esc_html(json_encode($fields_test, JSON_PRETTY_PRINT));
                        echo '</pre>';
                    } else {
                        echo '<p style="color: red;">❌ Form fields JSON is invalid: ' . json_last_error_msg() . '</p>';
                    }
                } else {
                    echo '<p style="color: red;">❌ Could not retrieve test form from database</p>';
                }
            } else {
                echo '<p style="color: red;">❌ Failed to create test form: ' . $wpdb->last_error . '</p>';
            }
            
        } else {
            echo '<p style="color: red;">❌ Forms table does not exist!</p>';
        }
        
        // Test 2: AJAX Handler
        echo '<h3>🌐 AJAX Handler Test</h3>';
        
        if (class_exists('LIFT_Forms')) {
            echo '<p style="color: green;">✅ LIFT_Forms class exists</p>';
            
            // Test AJAX save directly
            $test_ajax_data = array(
                'action' => 'lift_forms_save',
                'nonce' => wp_create_nonce('lift_forms_nonce'),
                'form_id' => 0,
                'name' => 'AJAX Test Form ' . time(),
                'description' => 'Created by AJAX test',
                'fields' => json_encode(array(
                    array(
                        'id' => 'ajax_test_field_' . time(),
                        'name' => 'ajax_test_text',
                        'type' => 'text',
                        'label' => 'AJAX Test Field',
                        'placeholder' => 'Test field from AJAX',
                        'required' => false
                    )
                )),
                'settings' => '{}'
            );
            
            // Backup current POST data
            $backup_post = $_POST;
            $_POST = $test_ajax_data;
            
            // Capture AJAX output
            ob_start();
            try {
                $lift_forms = new LIFT_Forms();
                $lift_forms->ajax_save_form();
                $ajax_output = ob_get_clean();
                
                if (strpos($ajax_output, '"success":true') !== false) {
                    echo '<p style="color: green;">✅ AJAX save handler working correctly</p>';
                    echo '<pre style="background: #f0f8ff; padding: 10px;">AJAX Response: ' . esc_html($ajax_output) . '</pre>';
                } else {
                    echo '<p style="color: red;">❌ AJAX save handler returned error</p>';
                    echo '<pre style="background: #fff0f0; padding: 10px;">AJAX Response: ' . esc_html($ajax_output) . '</pre>';
                }
            } catch (Exception $e) {
                ob_end_clean();
                echo '<p style="color: red;">❌ AJAX handler threw exception: ' . esc_html($e->getMessage()) . '</p>';
            }
            
            // Restore POST data
            $_POST = $backup_post;
            
        } else {
            echo '<p style="color: red;">❌ LIFT_Forms class not found!</p>';
        }
        
        // Test 3: JavaScript Files
        echo '<h3>📝 JavaScript Files Test</h3>';
        
        $js_files = array(
            'forms-builder.js' => LIFT_DOCS_PLUGIN_DIR . 'assets/js/forms-builder.js',
            'forms-builder-enhanced.js' => LIFT_DOCS_PLUGIN_DIR . 'assets/js/forms-builder-enhanced.js',
            'forms-builder-fix.js' => LIFT_DOCS_PLUGIN_DIR . 'assets/js/forms-builder-fix.js',
            'forms-builder-test.js' => LIFT_DOCS_PLUGIN_DIR . 'assets/js/forms-builder-test.js',
            'forms-builder-ultimate-debug.js' => LIFT_DOCS_PLUGIN_DIR . 'assets/js/forms-builder-ultimate-debug.js'
        );
        
        foreach ($js_files as $name => $path) {
            if (file_exists($path)) {
                $size = filesize($path);
                echo '<p style="color: green;">✅ ' . $name . ' exists (' . number_format($size) . ' bytes)</p>';
            } else {
                echo '<p style="color: red;">❌ ' . $name . ' missing</p>';
            }
        }
        
        // Test 4: Plugin Integration
        echo '<h3>🔌 Plugin Integration Test</h3>';
        
        if (has_action('wp_ajax_lift_forms_save')) {
            echo '<p style="color: green;">✅ AJAX action lift_forms_save is registered</p>';
        } else {
            echo '<p style="color: red;">❌ AJAX action lift_forms_save is NOT registered</p>';
        }
        
        if (shortcode_exists('lift_form')) {
            echo '<p style="color: green;">✅ Shortcode [lift_form] is registered</p>';
        } else {
            echo '<p style="color: red;">❌ Shortcode [lift_form] is NOT registered</p>';
        }
        
        // Test 5: Debug Tools
        echo '<h3>🐛 Debug Tools Test</h3>';
        
        if (file_exists(LIFT_DOCS_PLUGIN_DIR . 'emergency-ajax-debug.php')) {
            echo '<p style="color: green;">✅ Emergency AJAX debug tool is loaded</p>';
        } else {
            echo '<p style="color: red;">❌ Emergency AJAX debug tool missing</p>';
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<p style="color: green;">✅ WP_DEBUG is enabled</p>';
        } else {
            echo '<p style="color: orange;">⚠️ WP_DEBUG is disabled (some debug features may not work)</p>';
        }
        
        if (ini_get('log_errors')) {
            echo '<p style="color: green;">✅ PHP error logging is enabled</p>';
        } else {
            echo '<p style="color: orange;">⚠️ PHP error logging is disabled</p>';
        }
        
        echo '<div class="notice notice-success" style="margin-top: 20px;"><p><strong>🎉 Verification complete!</strong></p></div>';
    }
    
    ?>
    <div class="card">
        <h2>🔧 Comprehensive System Test</h2>
        <p>Test này sẽ kiểm tra:</p>
        <ul>
            <li>✅ Database connectivity và table structure</li>
            <li>✅ AJAX handlers và data processing</li>
            <li>✅ JavaScript files và dependencies</li>
            <li>✅ Plugin integration và hooks</li>
            <li>✅ Debug tools và logging</li>
        </ul>
        
        <form method="post">
            <?php wp_nonce_field('lift_verification_test'); ?>
            <p>
                <input type="hidden" name="run_test" value="1">
                <input type="submit" class="button button-primary button-large" value="🚀 Run Full System Test">
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2>📋 Next Steps After Test</h2>
        <ol>
            <li><strong>Nếu test PASS:</strong> Thử tạo form mới và xem debug panel</li>
            <li><strong>Nếu test FAIL:</strong> Check các error messages trên</li>
            <li><strong>Debug Panel:</strong> Sẽ xuất hiện tự động khi mở Form Builder</li>
            <li><strong>Browser Console:</strong> Mở F12 để xem JavaScript logs</li>
            <li><strong>PHP Error Log:</strong> Check hosting control panel hoặc wp-content/debug.log</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>🔗 Quick Links</h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=lift-forms'); ?>" class="button">📋 Forms List</a>
            <a href="<?php echo admin_url('admin.php?page=lift-forms-builder'); ?>" class="button">➕ Create New Form</a>
            <a href="<?php echo admin_url('admin.php?page=lift-debug-ajax'); ?>" class="button">🐛 Debug Tools</a>
        </p>
    </div>
    
    <?php
    echo '</div>';
}
?>
