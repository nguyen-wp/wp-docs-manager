<?php
/**
 * Debug Form Submit - Tìm hiểu lỗi "Invalid fields data format: Syntax error"
 */

if (!defined('ABSPATH')) {
    exit;
}

// Force debug logging
ini_set('log_errors', 1);
ini_set('error_log', ABSPATH . '/wp-content/debug.log');

function debug_form_submit_ajax() {
    if (!is_admin()) return;
    
    ?>
    <div class="wrap">
        <h1>🐛 Debug Form Submit Error</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>Problem Analysis</h2>
            <p>Lỗi <code>{"success":false,"data":"Invalid fields data format: Syntax error"}</code> thường xảy ra khi:</p>
            <ul>
                <li>❌ JSON data không đúng định dạng</li>
                <li>❌ Có ký tự đặc biệt trong dữ liệu</li>
                <li>❌ Form fields data bị corrupt</li>
                <li>❌ JavaScript serialize data không đúng</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>Kiểm tra AJAX Handlers</h2>
            <?php
            // Check AJAX handlers
            $ajax_actions = [
                'lift_forms_save' => 'Lưu form từ Form Builder',
                'lift_forms_submit' => 'Submit form từ frontend',
                'lift_forms_get' => 'Load form data', 
                'lift_forms_delete' => 'Xóa form'
            ];
            
            foreach ($ajax_actions as $action => $desc) {
                $has_action = has_action("wp_ajax_$action");
                $has_nopriv = has_action("wp_ajax_nopriv_$action");
                
                echo "<p><strong>$desc ($action):</strong> ";
                echo $has_action ? "✅ Logged-in" : "❌ Logged-in";
                echo " | ";
                echo $has_nopriv ? "✅ Non-logged" : "❌ Non-logged";
                echo "</p>";
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>Kiểm tra Database Forms</h2>
            <?php
            global $wpdb;
            $forms_table = $wpdb->prefix . 'lift_forms';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table;
            
            if (!$table_exists) {
                echo "<p>❌ Bảng $forms_table không tồn tại!</p>";
            } else {
                echo "<p>✅ Bảng $forms_table tồn tại</p>";
                
                // Get all forms and check their JSON data
                $forms = $wpdb->get_results("SELECT id, name, form_fields FROM $forms_table");
                
                if (empty($forms)) {
                    echo "<p>ℹ️ Không có form nào trong database</p>";
                } else {
                    echo "<h3>Kiểm tra JSON data trong các form:</h3>";
                    
                    foreach ($forms as $form) {
                        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc;'>";
                        echo "<h4>Form #{$form->id}: " . esc_html($form->name) . "</h4>";
                        
                        if (empty($form->form_fields)) {
                            echo "<p>❌ form_fields trống</p>";
                        } else {
                            // Test JSON decode
                            $fields = json_decode($form->form_fields, true);
                            $json_error = json_last_error();
                            
                            if ($json_error === JSON_ERROR_NONE) {
                                echo "<p>✅ JSON hợp lệ (" . count($fields) . " fields)</p>";
                            } else {
                                echo "<p>❌ JSON không hợp lệ: " . json_last_error_msg() . "</p>";
                                echo "<p><strong>Raw data (first 200 chars):</strong></p>";
                                echo "<code style='background: #f0f0f0; padding: 5px; display: block; word-break: break-all;'>";
                                echo esc_html(substr($form->form_fields, 0, 200));
                                echo "</code>";
                            }
                        }
                        echo "</div>";
                    }
                }
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>Test AJAX Request</h2>
            <p>Kiểm tra AJAX request trực tiếp:</p>
            
            <button id="test-ajax" class="button button-primary">Test AJAX Save</button>
            <button id="test-ajax-submit" class="button">Test AJAX Submit</button>
            <button id="test-empty-fields" class="button" style="background: orange; color: white;">Test Empty Fields Error</button>
            
            <div id="ajax-result" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; display: none;"></div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-ajax').click(function() {
                    $('#ajax-result').show().html('Testing AJAX Save...');
                    
                    var testData = {
                        action: 'lift_forms_save',
                        nonce: '<?php echo wp_create_nonce("lift_forms_nonce"); ?>',
                        form_id: 0,
                        name: 'Test Form',
                        description: 'Test Description',
                        fields: JSON.stringify([
                            {
                                id: 'field_1',
                                name: 'test_field',
                                type: 'text',
                                label: 'Test Field',
                                required: false
                            }
                        ]),
                        settings: JSON.stringify({})
                    };
                    
                    console.log('Sending data:', testData);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: testData,
                        success: function(response) {
                            console.log('Response:', response);
                            $('#ajax-result').html('<h4>Success Response:</h4><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                        },
                        error: function(xhr, status, error) {
                            console.log('Error:', xhr.responseText);
                            $('#ajax-result').html('<h4>Error Response:</h4><pre>' + xhr.responseText + '</pre>');
                        }
                    });
                });
                
                $('#test-ajax-submit').click(function() {
                    $('#ajax-result').show().html('Testing AJAX Submit...');
                    
                    var testData = {
                        action: 'lift_forms_submit',
                        nonce: '<?php echo wp_create_nonce("lift_forms_submit_nonce"); ?>',
                        form_id: 1,
                        form_data: {
                            test_field: 'test value'
                        }
                    };
                    
                    console.log('Sending submit data:', testData);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: testData,
                        success: function(response) {
                            console.log('Submit Response:', response);
                            $('#ajax-result').html('<h4>Submit Response:</h4><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                        },
                        error: function(xhr, status, error) {
                            console.log('Submit Error:', xhr.responseText);
                            $('#ajax-result').html('<h4>Submit Error Response:</h4><pre>' + xhr.responseText + '</pre>');
                        }
                    });
                });
                
                // Test empty fields error - reproduce the current issue
                $('#test-empty-fields').click(function() {
                    $('#ajax-result').show().html('Testing Empty Fields Error...');
                    
                    var testData = {
                        action: 'lift_forms_save',
                        nonce: '<?php echo wp_create_nonce("lift_forms_nonce"); ?>',
                        form_id: 0,
                        name: 'Test Form with Empty Fields',
                        description: 'This should trigger empty fields error',
                        fields: '[]', // Empty fields array
                        settings: JSON.stringify({})
                    };
                    
                    console.log('Sending empty fields data:', testData);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: testData,
                        success: function(response) {
                            console.log('Empty Fields Response:', response);
                            $('#ajax-result').html('<h4>Empty Fields Response:</h4><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                        },
                        error: function(xhr, status, error) {
                            console.log('Empty Fields Error:', xhr.responseText);
                            $('#ajax-result').html('<h4>Empty Fields Error Response:</h4><pre>' + xhr.responseText + '</pre>');
                        }
                    });
                });
            });
            </script>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>� Form Builder JavaScript Debug</h2>
            <p>Kiểm tra trạng thái Form Builder JavaScript:</p>
            
            <button id="check-form-builder" class="button">Check Form Builder State</button>
            <button id="inspect-canvas" class="button">Inspect Canvas Fields</button>
            
            <div id="js-debug-result" style="margin-top: 15px; padding: 10px; background: #f0f8ff; border: 1px solid #0073aa; display: none;"></div>
            
            <script>
            // Add form builder debugging functions
            window.debugFormBuilder = {
                checkFormBuilder: function() {
                    var result = {
                        formBuilderExists: typeof window.liftFormBuilder !== 'undefined',
                        formBuilderEnhancedExists: typeof window.liftFormsEnhanced !== 'undefined',
                        formData: null,
                        canvasFields: 0,
                        errors: []
                    };
                    
                    if (window.liftFormBuilder) {
                        try {
                            result.formData = window.liftFormBuilder.formData;
                            result.fieldsCount = result.formData ? result.formData.fields.length : 0;
                        } catch (e) {
                            result.errors.push('Error accessing formData: ' + e.message);
                        }
                    }
                    
                    // Check canvas
                    var canvasFields = $('#form-canvas .canvas-field');
                    result.canvasFields = canvasFields.length;
                    
                    // Check if fields have proper data
                    var fieldIds = [];
                    canvasFields.each(function() {
                        var fieldId = $(this).data('field-id');
                        if (fieldId) {
                            fieldIds.push(fieldId);
                        }
                    });
                    result.fieldIds = fieldIds;
                    
                    return result;
                },
                
                inspectCanvas: function() {
                    var canvas = $('#form-canvas');
                    var fields = canvas.find('.canvas-field');
                    
                    var result = {
                        canvasExists: canvas.length > 0,
                        fieldsInCanvas: fields.length,
                        fieldDetails: []
                    };
                    
                    fields.each(function(index) {
                        var field = $(this);
                        var fieldData = {
                            index: index,
                            id: field.data('field-id'),
                            type: field.data('field-type'),
                            html: field.html().substring(0, 100) + '...'
                        };
                        result.fieldDetails.push(fieldData);
                    });
                    
                    return result;
                }
            };
            
            // Add event handlers for debug buttons
            $(document).ready(function() {
                $('#check-form-builder').click(function() {
                    var result = window.debugFormBuilder.checkFormBuilder();
                    $('#js-debug-result').show().html(
                        '<h4>Form Builder State:</h4><pre>' + 
                        JSON.stringify(result, null, 2) + 
                        '</pre>'
                    );
                });
                
                $('#inspect-canvas').click(function() {
                    var result = window.debugFormBuilder.inspectCanvas();
                    $('#js-debug-result').show().html(
                        '<h4>Canvas Inspection:</h4><pre>' + 
                        JSON.stringify(result, null, 2) + 
                        '</pre>'
                    );
                });
            });
            </script>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h2>�🔧 Các giải pháp có thể</h2>
            <ol>
                <li><strong>Kiểm tra JavaScript Console:</strong> Mở Developer Tools → Console để xem lỗi JS</li>
                <li><strong>Kiểm tra Network Tab:</strong> Xem raw request/response data</li>
                <li><strong>Clear JSON data:</strong> Xóa và tạo lại form mới</li>
                <li><strong>Check encoding:</strong> Kiểm tra ký tự đặc biệt trong form fields</li>
                <li><strong>Enable debug:</strong> Bật WP_DEBUG để xem chi tiết lỗi</li>
            </ol>
            
            <h3>Quick Fixes:</h3>
            <p>
                <a href="<?php echo admin_url('admin.php?page=lift-forms-builder'); ?>" class="button">Go to Form Builder</a>
                <a href="<?php echo admin_url('edit.php?post_type=lift_document&page=lift-forms'); ?>" class="button">Go to Forms List</a>
            </p>
        </div>
    </div>
    <?php
}

// Add debug menu
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=lift_document',
        'Debug Form Submit',
        'Debug Submit',
        'manage_options',
        'debug-form-submit',
        'debug_form_submit_ajax'
    );
});

// Enhanced AJAX logging
add_action('wp_ajax_lift_forms_save', function() {
    error_log('LIFT Forms Save AJAX called');
    error_log('POST data: ' . print_r($_POST, true));
}, 1);

add_action('wp_ajax_lift_forms_submit', function() {
    error_log('LIFT Forms Submit AJAX called'); 
    error_log('POST data: ' . print_r($_POST, true));
}, 1);
