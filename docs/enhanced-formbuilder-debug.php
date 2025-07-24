<?php
/**
 * Enhanced FormBuilder JSON Debug
 * Intercept và debug JSON trước khi AJAX gửi
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enhanced JavaScript debugging
add_action('admin_footer', 'lift_enhanced_formbuilder_debug_js');

function lift_enhanced_formbuilder_debug_js() {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'lift-forms') !== false) {
        ?>
        <script>
        (function($) {
            // Enhanced JSON debugging
            console.log('🔧 LIFT Forms Enhanced Debug Loaded');
            
            // Override existing save function if it exists
            if (window.saveForm) {
                const originalSaveForm = window.saveForm;
                window.saveForm = function() {
                    console.log('🚀 Intercepted saveForm call');
                    
                    // Debug form data collection
                    const formName = $('#form-name').val();
                    const formDescription = $('#form-description').val();
                    
                    console.log('📝 Form Name:', formName);
                    console.log('📄 Form Description:', formDescription);
                    
                    // Debug fields collection
                    const fieldsData = [];
                    $('.form-field').each(function(index) {
                        const $field = $(this);
                        console.log(`🔍 Processing field ${index}:`, $field);
                        
                        const fieldData = {
                            id: $field.find('[name="field_id"]').val() || 'field_' + (index + 1),
                            name: $field.find('[name="field_name"]').val() || 'field_' + (index + 1),
                            type: $field.find('[name="field_type"]').val() || 'text',
                            label: $field.find('[name="field_label"]').val() || 'Field ' + (index + 1),
                            placeholder: $field.find('[name="field_placeholder"]').val() || '',
                            required: $field.find('[name="field_required"]').is(':checked'),
                            description: $field.find('[name="field_description"]').val() || '',
                            order: index
                        };
                        
                        console.log(`📊 Field ${index} data:`, fieldData);
                        fieldsData.push(fieldData);
                    });
                    
                    console.log('📋 All fields collected:', fieldsData);
                    
                    // Test JSON stringify
                    let fieldsJSON;
                    try {
                        fieldsJSON = JSON.stringify(fieldsData);
                        console.log('✅ JSON stringify successful');
                        console.log('🔤 Fields JSON:', fieldsJSON);
                        console.log('📏 JSON length:', fieldsJSON.length);
                        
                        // Test JSON parse back
                        const parsed = JSON.parse(fieldsJSON);
                        console.log('✅ JSON parse test successful:', parsed);
                        
                    } catch (e) {
                        console.error('❌ JSON stringify failed:', e);
                        fieldsJSON = '[]';
                    }
                    
                    // Debug AJAX data preparation
                    const ajaxData = {
                        action: 'lift_forms_save',
                        nonce: $('#_lift_forms_nonce').val(),
                        form_id: $('#form_id').val() || 0,
                        name: formName,
                        description: formDescription,
                        fields: fieldsJSON,
                        settings: '{}'
                    };
                    
                    console.log('📡 AJAX data prepared:', ajaxData);
                    
                    // Manual AJAX call with enhanced debugging
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: ajaxData,
                        beforeSend: function(xhr, settings) {
                            console.log('📤 AJAX before send:', settings);
                            console.log('📤 Data being sent:', settings.data);
                        },
                        success: function(response) {
                            console.log('📥 AJAX success response:', response);
                            if (response.success) {
                                alert('✅ Form saved successfully!');
                                if (response.data && response.data.form_id) {
                                    $('#form_id').val(response.data.form_id);
                                }
                            } else {
                                console.error('❌ Server returned error:', response.data);
                                alert('❌ Error: ' + (response.data || 'Unknown error'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('❌ AJAX error:', xhr, status, error);
                            console.error('❌ Response text:', xhr.responseText);
                            alert('❌ AJAX Error: ' + error);
                        },
                        complete: function(xhr, status) {
                            console.log('🏁 AJAX complete:', status);
                        }
                    });
                    
                    return false; // Prevent original function
                };
            }
            
            // Also intercept any direct AJAX calls
            $(document).ajaxSend(function(event, xhr, settings) {
                if (settings.data && typeof settings.data === 'string' && settings.data.includes('lift_forms_save')) {
                    console.log('🕵️ Detected LIFT Forms AJAX call');
                    console.log('📡 Settings:', settings);
                    console.log('📄 Data:', settings.data);
                    
                    // Parse the data to check fields
                    const params = new URLSearchParams(settings.data);
                    const fields = params.get('fields');
                    if (fields) {
                        console.log('🔍 Fields parameter:', fields);
                        console.log('📏 Fields length:', fields.length);
                        console.log('🔤 First 200 chars:', fields.substring(0, 200));
                        
                        // Test JSON validity
                        try {
                            const parsed = JSON.parse(fields);
                            console.log('✅ Fields JSON is valid:', parsed);
                        } catch (e) {
                            console.error('❌ Fields JSON is invalid:', e);
                            console.error('❌ Invalid JSON:', fields);
                        }
                    }
                }
            });
            
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.data && typeof settings.data === 'string' && settings.data.includes('lift_forms_save')) {
                    console.log('📥 LIFT Forms AJAX response:', xhr.responseText);
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('📋 Parsed response:', response);
                    } catch (e) {
                        console.error('❌ Could not parse response as JSON:', e);
                    }
                }
            });
            
        })(jQuery);
        </script>
        <?php
    }
}

// Add this debug tool to admin menu
add_action('admin_menu', 'lift_enhanced_debug_menu');

function lift_enhanced_debug_menu() {
    add_submenu_page(
        null,
        'LIFT Enhanced Debug',
        'LIFT Enhanced Debug',
        'manage_options',
        'lift-enhanced-debug',
        'lift_enhanced_debug_page'
    );
}

function lift_enhanced_debug_page() {
    ?>
    <div class="wrap">
        <h1>🔧 LIFT Forms Enhanced Debug</h1>
        
        <div class="card">
            <h2>Debug Status</h2>
            <p>✅ Enhanced JavaScript debugging is active on LIFT Forms pages.</p>
            <p>🔍 Open browser console (F12) when using Form Builder to see detailed logs.</p>
        </div>
        
        <div class="card">
            <h2>How to Debug</h2>
            <ol>
                <li>Go to <strong>LIFT Forms > Add New</strong></li>
                <li>Open browser console (Press F12)</li>
                <li>Add some fields to the form</li>
                <li>Click "Save Form"</li>
                <li>Watch the console for detailed debug information</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>What to Look For</h2>
            <ul>
                <li><strong>🔤 Fields JSON:</strong> Check if JSON format is correct</li>
                <li><strong>📡 AJAX data:</strong> Verify data being sent</li>
                <li><strong>📥 Response:</strong> Check server response</li>
                <li><strong>❌ Errors:</strong> Look for JSON parsing errors</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Quick Actions</h2>
            <p>
                <a href="<?php echo admin_url('admin.php?page=lift-forms-add'); ?>" class="button button-primary">🔧 Test Form Builder</a>
                <a href="<?php echo admin_url('admin.php?page=lift-save-debug'); ?>" class="button">🐛 Basic Debug Tool</a>
            </p>
        </div>
        
        <div class="card">
            <h2>Manual JSON Test</h2>
            <p>Test JSON validation manually:</p>
            <textarea id="json-test" rows="5" cols="80" placeholder='[{"id":"field_1","name":"test","type":"text","label":"Test"}]'></textarea><br>
            <button onclick="testJSON()" class="button">Test JSON</button>
            <div id="json-result"></div>
            
            <script>
            function testJSON() {
                const input = document.getElementById('json-test').value;
                const result = document.getElementById('json-result');
                
                try {
                    const parsed = JSON.parse(input);
                    result.innerHTML = '<div style="color: green;">✅ Valid JSON<br><pre>' + JSON.stringify(parsed, null, 2) + '</pre></div>';
                } catch (e) {
                    result.innerHTML = '<div style="color: red;">❌ Invalid JSON: ' + e.message + '</div>';
                }
            }
            </script>
        </div>
    </div>
    <?php
}
?>
