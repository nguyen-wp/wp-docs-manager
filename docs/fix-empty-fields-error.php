<?php
/**
 * Fix Empty Fields Error - "Form must have at least one field"
 * Sửa lỗi khi Form Builder không gửi fields data đúng cách
 */

if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Empty_Fields_Fix {
    
    public function __construct() {
        // Hook vào Form Builder để debug và fix
        add_action('admin_enqueue_scripts', array($this, 'enqueue_debug_scripts'));
        add_action('admin_menu', array($this, 'add_debug_page'));
        
        // Enhanced AJAX logging cho empty fields issue
        add_action('wp_ajax_lift_forms_save', array($this, 'debug_form_save'), 1);
    }
    
    /**
     * Debug form save data
     */
    public function debug_form_save() {
        error_log('=== LIFT Forms Save Debug ===');
        error_log('Raw POST data: ' . print_r($_POST, true));
        
        if (isset($_POST['fields'])) {
            $fields = $_POST['fields'];
            error_log('Fields data type: ' . gettype($fields));
            error_log('Fields data: ' . $fields);
            error_log('Fields length: ' . strlen($fields));
            
            // Test JSON decode
            if (is_string($fields)) {
                $decoded = json_decode($fields, true);
                error_log('JSON decode success: ' . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO'));
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON error: ' . json_last_error_msg());
                } else {
                    error_log('Decoded fields count: ' . (is_array($decoded) ? count($decoded) : 'NOT_ARRAY'));
                }
            }
        } else {
            error_log('No fields in POST data!');
        }
        
        error_log('=== End LIFT Forms Save Debug ===');
    }
    
    /**
     * Enqueue debug scripts for form builder pages
     */
    public function enqueue_debug_scripts($hook) {
        // Only on form builder pages
        if (strpos($hook, 'lift-forms') === false) {
            return;
        }
        
        wp_add_inline_script('jquery', $this->get_debug_javascript());
    }
    
    /**
     * Get debug JavaScript code
     */
    private function get_debug_javascript() {
        return '
        jQuery(document).ready(function($) {
            console.log("LIFT Forms Debug Script Loaded");
            
            // Monitor form builder state
            window.liftFormsDebug = {
                monitorFormData: function() {
                    if (typeof window.liftFormBuilder !== "undefined") {
                        console.log("Form Builder Data:", window.liftFormBuilder.formData);
                        console.log("Fields Count:", window.liftFormBuilder.formData.fields.length);
                        
                        // Check canvas fields
                        var canvasFields = $("#form-canvas .canvas-field");
                        console.log("Canvas Fields Count:", canvasFields.length);
                        
                        // Check if fields have IDs
                        canvasFields.each(function(index) {
                            var fieldId = $(this).data("field-id");
                            var fieldType = $(this).data("field-type");
                            console.log("Canvas Field " + index + ":", {id: fieldId, type: fieldType});
                        });
                    } else {
                        console.log("Form Builder not found");
                    }
                },
                
                interceptSave: function() {
                    // Override save function to debug
                    if (typeof window.liftFormBuilder !== "undefined" && window.liftFormBuilder.saveForm) {
                        var originalSave = window.liftFormBuilder.saveForm;
                        
                        window.liftFormBuilder.saveForm = function() {
                            console.log("=== SAVE INTERCEPTED ===");
                            console.log("Form Data before save:", this.formData);
                            console.log("Fields count:", this.formData.fields.length);
                            
                            // Check if cleanFormData exists
                            if (this.cleanFormData) {
                                var cleanData = this.cleanFormData();
                                console.log("Clean Form Data:", cleanData);
                                console.log("Clean Fields count:", cleanData.fields.length);
                                
                                // Test JSON stringify
                                try {
                                    var jsonFields = JSON.stringify(cleanData.fields);
                                    console.log("JSON stringify success, length:", jsonFields.length);
                                    console.log("JSON fields:", jsonFields);
                                } catch (e) {
                                    console.error("JSON stringify error:", e);
                                }
                            }
                            
                            console.log("=== CALLING ORIGINAL SAVE ===");
                            return originalSave.call(this);
                        };
                    }
                }
            };
            
            // Auto-start monitoring
            setTimeout(function() {
                window.liftFormsDebug.monitorFormData();
                window.liftFormsDebug.interceptSave();
            }, 1000);
            
            // Monitor canvas changes
            if ($("#form-canvas").length) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === "childList") {
                            console.log("Canvas changed, rechecking...");
                            setTimeout(function() {
                                window.liftFormsDebug.monitorFormData();
                            }, 500);
                        }
                    });
                });
                
                observer.observe(document.getElementById("form-canvas"), {
                    childList: true,
                    subtree: true
                });
            }
        });
        ';
    }
    
    /**
     * Add debug page
     */
    public function add_debug_page() {
        add_submenu_page(
            'edit.php?post_type=lift_document',
            'Fix Empty Fields',
            'Fix Empty Fields',
            'manage_options',
            'fix-empty-fields',
            array($this, 'debug_page')
        );
    }
    
    /**
     * Debug page content
     */
    public function debug_page() {
        ?>
        <div class="wrap">
            <h1>🔧 Fix Empty Fields Error</h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                <h2>Problem: "Form must have at least one field"</h2>
                <p>Lỗi này xảy ra khi:</p>
                <ul>
                    <li>❌ Form Builder JavaScript không cập nhật formData.fields</li>
                    <li>❌ Fields bị mất trong quá trình serialize</li>
                    <li>❌ AJAX request không gửi fields data</li>
                    <li>❌ JSON.stringify() thất bại</li>
                </ul>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                <h2>Debug Form Builder State</h2>
                <p>Kiểm tra trạng thái hiện tại của Form Builder:</p>
                
                <button id="check-formbuilder" class="button button-primary">Check Form Builder</button>
                <button id="simulate-add-field" class="button">Simulate Add Field</button>
                <button id="test-save-process" class="button">Test Save Process</button>
                
                <div id="debug-output" style="margin-top: 15px; padding: 10px; background: #f0f8ff; border: 1px solid #0073aa; display: none;"></div>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                <h2>Manual Field Data Injection</h2>
                <p>Thêm field data thủ công vào Form Builder:</p>
                
                <button id="inject-test-field" class="button button-secondary">Inject Test Field</button>
                <button id="clear-form-data" class="button">Clear Form Data</button>
                
                <div id="injection-result" style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; display: none;"></div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#check-formbuilder').click(function() {
                    var output = '';
                    
                    // Check if form builder exists
                    if (typeof window.liftFormBuilder !== 'undefined') {
                        output += '<h4>✅ Form Builder Found</h4>';
                        output += '<p><strong>Fields count:</strong> ' + (window.liftFormBuilder.formData.fields ? window.liftFormBuilder.formData.fields.length : 'undefined') + '</p>';
                        output += '<p><strong>Form Data:</strong></p>';
                        output += '<pre>' + JSON.stringify(window.liftFormBuilder.formData, null, 2) + '</pre>';
                        
                        // Check canvas
                        var canvasFields = $('#form-canvas .canvas-field').length;
                        output += '<p><strong>Canvas Fields:</strong> ' + canvasFields + '</p>';
                        
                    } else {
                        output += '<h4>❌ Form Builder Not Found</h4>';
                        output += '<p>Window object available: ' + Object.keys(window).filter(k => k.includes('lift')).join(', ') + '</p>';
                    }
                    
                    $('#debug-output').show().html(output);
                });
                
                $('#simulate-add-field').click(function() {
                    if (typeof window.liftFormBuilder !== 'undefined') {
                        // Simulate adding a field
                        var testField = {
                            id: 'test_field_' + Date.now(),
                            name: 'test_field',
                            type: 'text',
                            label: 'Test Field',
                            required: false
                        };
                        
                        window.liftFormBuilder.formData.fields.push(testField);
                        
                        $('#debug-output').show().html(
                            '<h4>✅ Field Added</h4>' +
                            '<p>New fields count: ' + window.liftFormBuilder.formData.fields.length + '</p>' +
                            '<pre>' + JSON.stringify(testField, null, 2) + '</pre>'
                        );
                    } else {
                        $('#debug-output').show().html('<h4>❌ Form Builder not available</h4>');
                    }
                });
                
                $('#test-save-process').click(function() {
                    if (typeof window.liftFormBuilder !== 'undefined') {
                        var output = '<h4>Testing Save Process</h4>';
                        
                        try {
                            // Test cleanFormData
                            if (window.liftFormBuilder.cleanFormData) {
                                var cleanData = window.liftFormBuilder.cleanFormData();
                                output += '<p>✅ cleanFormData() works</p>';
                                output += '<p>Clean fields count: ' + cleanData.fields.length + '</p>';
                                
                                // Test JSON stringify
                                try {
                                    var json = JSON.stringify(cleanData.fields);
                                    output += '<p>✅ JSON.stringify() works, length: ' + json.length + '</p>';
                                    output += '<details><summary>JSON Data</summary><pre>' + json + '</pre></details>';
                                } catch (e) {
                                    output += '<p>❌ JSON.stringify() failed: ' + e.message + '</p>';
                                }
                            } else {
                                output += '<p>❌ cleanFormData() not found</p>';
                            }
                        } catch (e) {
                            output += '<p>❌ Error: ' + e.message + '</p>';
                        }
                        
                        $('#debug-output').show().html(output);
                    }
                });
                
                $('#inject-test-field').click(function() {
                    if (typeof window.liftFormBuilder !== 'undefined') {
                        // Force inject field data
                        window.liftFormBuilder.formData.fields = [
                            {
                                id: 'injected_field_1',
                                name: 'customer_name',
                                type: 'text',
                                label: 'Customer Name',
                                placeholder: 'Enter your name',
                                required: true
                            },
                            {
                                id: 'injected_field_2', 
                                name: 'customer_email',
                                type: 'email',
                                label: 'Email Address',
                                placeholder: 'Enter your email',
                                required: true
                            }
                        ];
                        
                        $('#injection-result').show().html(
                            '<h4>✅ Test Fields Injected</h4>' +
                            '<p>Fields count: ' + window.liftFormBuilder.formData.fields.length + '</p>' +
                            '<p>Now try to save the form to test if it works.</p>'
                        );
                    }
                });
                
                $('#clear-form-data').click(function() {
                    if (typeof window.liftFormBuilder !== 'undefined') {
                        window.liftFormBuilder.formData.fields = [];
                        $('#injection-result').show().html('<h4>🗑️ Form Data Cleared</h4>');
                    }
                });
            });
            </script>
        </div>
        <?php
    }
}

// Initialize the fix
new LIFT_Empty_Fields_Fix();
