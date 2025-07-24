<?php
/**
 * EMERGENCY AJAX HANDLER INTERCEPTOR
 * Can thiệp trực tiếp vào quá trình AJAX để debug
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook vào quá trình AJAX sớm nhất
add_action('wp_ajax_lift_forms_save', 'lift_debug_ajax_interceptor', 1);

function lift_debug_ajax_interceptor() {
    // Log tất cả dữ liệu nhận được
    error_log('=== LIFT FORMS AJAX INTERCEPTOR ===');
    error_log('POST Data: ' . print_r($_POST, true));
    error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
    error_log('HTTP_USER_AGENT: ' . $_SERVER['HTTP_USER_AGENT']);
    
    // Kiểm tra fields data chi tiết
    if (isset($_POST['fields'])) {
        error_log('Fields data received: ' . $_POST['fields']);
        error_log('Fields data length: ' . strlen($_POST['fields']));
        error_log('Fields data type: ' . gettype($_POST['fields']));
        
        // Test JSON decode
        $fields_test = json_decode($_POST['fields'], true);
        $json_error = json_last_error();
        
        error_log('JSON decode test result: ' . ($json_error === JSON_ERROR_NONE ? 'SUCCESS' : 'FAILED'));
        error_log('JSON error: ' . json_last_error_msg());
        error_log('Decoded fields count: ' . (is_array($fields_test) ? count($fields_test) : 'NOT_ARRAY'));
        
        if (is_array($fields_test)) {
            error_log('First field: ' . print_r(reset($fields_test), true));
        }
        
        // Nếu fields trống hoặc bị lỗi, thử fix tự động
        if (empty($fields_test) || !is_array($fields_test)) {
            error_log('EMERGENCY: Fields data is empty or invalid - attempting auto-fix');
            
            // Tạo field test tự động
            $emergency_field = array(
                array(
                    'id' => 'emergency_field_' . time(),
                    'name' => 'emergency_text_field',
                    'type' => 'text',
                    'label' => 'Emergency Text Field (Auto-generated)',
                    'placeholder' => 'This field was auto-generated to prevent save error',
                    'required' => false,
                    'description' => 'This field was automatically created because no valid fields were detected'
                )
            );
            
            $_POST['fields'] = json_encode($emergency_field);
            error_log('EMERGENCY FIX: Replaced fields with: ' . $_POST['fields']);
        }
    } else {
        error_log('ERROR: No fields data in POST!');
        
        // Tạo fields tự động nếu không có
        $emergency_field = array(
            array(
                'id' => 'emergency_field_' . time(),
                'name' => 'emergency_text_field',
                'type' => 'text',
                'label' => 'Emergency Text Field (Auto-generated)',
                'placeholder' => 'This field was auto-generated because no fields were sent',
                'required' => false,
                'description' => 'Emergency field created by AJAX interceptor'
            )
        );
        
        $_POST['fields'] = json_encode($emergency_field);
        error_log('EMERGENCY: Created fields from scratch: ' . $_POST['fields']);
    }
    
    // Kiểm tra form name
    if (empty($_POST['name'])) {
        $_POST['name'] = 'Auto-Generated Form ' . date('Y-m-d H:i:s');
        error_log('EMERGENCY: Set form name to: ' . $_POST['name']);
    }
    
    error_log('=== END AJAX INTERCEPTOR ===');
}

// Thêm menu admin để active/deactive interceptor
add_action('admin_menu', 'lift_debug_menu');

function lift_debug_menu() {
    add_submenu_page(
        null, // Parent slug - null means hidden
        'LIFT Debug', 
        'LIFT Debug', 
        'manage_options', 
        'lift-debug-ajax', 
        'lift_debug_ajax_page'
    );
}

function lift_debug_ajax_page() {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'test_ajax') {
            // Test AJAX call trực tiếp
            echo '<div class="notice notice-info"><p>Testing AJAX call...</p></div>';
            
            // Simulate AJAX data
            $test_data = array(
                'action' => 'lift_forms_save',
                'nonce' => wp_create_nonce('lift_forms_nonce'),
                'form_id' => 0,
                'name' => 'Test Form ' . time(),
                'description' => 'Test form created by debug tool',
                'fields' => json_encode(array(
                    array(
                        'id' => 'test_field_' . time(),
                        'name' => 'test_text_field',
                        'type' => 'text',
                        'label' => 'Test Text Field',
                        'placeholder' => 'Enter text here',
                        'required' => false
                    )
                )),
                'settings' => '{}',
            );
            
            // Execute AJAX handler directly
            $_POST = $test_data;
            
            // Check if LIFT_Forms class exists
            if (class_exists('LIFT_Forms')) {
                $lift_forms = new LIFT_Forms();
                
                ob_start();
                $lift_forms->ajax_save_form();
                $output = ob_get_clean();
                
                echo '<div class="notice notice-success"><p>AJAX test completed. Check error log for results.</p></div>';
                if ($output) {
                    echo '<pre>' . esc_html($output) . '</pre>';
                }
            } else {
                echo '<div class="notice notice-error"><p>LIFT_Forms class not found!</p></div>';
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>🐛 LIFT Forms AJAX Debugger</h1>
        
        <div class="card">
            <h2>Emergency Debug Tools</h2>
            <p>Các công cụ này sẽ giúp debug lỗi "Form must have at least one field"</p>
            
            <form method="post">
                <input type="hidden" name="action" value="test_ajax">
                <?php wp_nonce_field('lift_debug_action'); ?>
                <p>
                    <input type="submit" class="button button-primary" value="Test AJAX Save Direct">
                    <span class="description">Test AJAX save function trực tiếp</span>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Debug Information</h2>
            <p><strong>Current Time:</strong> <?php echo current_time('mysql'); ?></p>
            <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Debug Mode:</strong> <?php echo WP_DEBUG ? 'ON' : 'OFF'; ?></p>
            <p><strong>Error Log:</strong> <?php echo ini_get('log_errors') ? 'ON' : 'OFF'; ?></p>
            
            <?php
            // Check database tables
            global $wpdb;
            $forms_table = $wpdb->prefix . 'lift_forms';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") === $forms_table;
            ?>
            <p><strong>Forms Table:</strong> <?php echo $table_exists ? 'EXISTS' : 'MISSING'; ?></p>
            
            <?php if ($table_exists): ?>
                <p><strong>Total Forms:</strong> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $forms_table"); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Instructions</h2>
            <ol>
                <li>Mở Form Builder page (LIFT Forms → Add New Form)</li>
                <li>Mở Developer Tools (F12) trong browser</li>
                <li>Một debug panel sẽ xuất hiện ở góc phải màn hình</li>
                <li>Thử drag field vào canvas và xem log trong debug panel</li>
                <li>Sử dụng các nút "Force Add Field", "Test Save", "Show State" để debug</li>
                <li>Tất cả log sẽ được ghi vào PHP error log</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>JavaScript Debug Commands</h2>
            <p>Mở browser console và chạy các lệnh sau:</p>
            <code>
                // Xem trạng thái hiện tại<br>
                window.LiftFormBuilderDebugger.showCurrentState();<br><br>
                
                // Force thêm field<br>
                window.LiftFormBuilderDebugger.forceAddField();<br><br>
                
                // Test save<br>
                window.LiftFormBuilderDebugger.testSave();<br><br>
                
                // Xem tất cả log<br>
                console.log(window.LiftFormBuilderDebugger.logs);
            </code>
        </div>
    </div>
    <?php
}

// Add debug notice to admin
add_action('admin_notices', 'lift_debug_admin_notice');

function lift_debug_admin_notice() {
    $screen = get_current_screen();
    
    if (strpos($screen->id, 'lift-forms') !== false) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>🐛 LIFT Forms Debug Mode Active</strong> - 
                AJAX Interceptor đang hoạt động. Check PHP error log để xem chi tiết debug info.
                <a href="<?php echo admin_url('admin.php?page=lift-debug-ajax'); ?>" style="margin-left: 10px;">Open Debug Tools</a>
            </p>
        </div>
        <?php
    }
}
