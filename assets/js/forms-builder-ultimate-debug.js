/**
 * Real-time Form Builder Debug - O                    right: 10px; 
                    width: 400px; 
                    max-height: 500px; 
                    background: #fff; 
                    border: 2px solid #dc3232; 
                    border-radius: 5px; 
                    z-index: 99999;
                    font-family: monospace;
                    font-size: 12px;
                ">" Monitor Everything
 * Giải pháp mạnh mẽ để debug lỗi "Form must have at least one field"
 */

(function($) {
    'use strict';
    
    // Real-time debugging system
    window.LiftFormBuilderDebugger = {
        
        logs: [],
        isDebugging: true,
        
        log: function(message, data = null) {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = {
                time: timestamp,
                message: message,
                data: data ? JSON.parse(JSON.stringify(data)) : null
            };
            
            this.logs.push(logEntry);
            
            if (this.isDebugging) {
                console.log(`🐛 [${timestamp}] ${message}`, data || '');
            }
            
            // Update debug panel if exists
            this.updateDebugPanel();
        },
        
        createDebugPanel: function() {
            if ($('#lift-debug-panel').length > 0) return;
            
            const panel = `
                <div id="lift-debug-panel" style="
                    position: fixed; 
                    top: 10px; 
                    right: 10px; 
                    width: 400px; 
                    max-height: 500px; 
                    background: #fff; 
                    border: 2px solid #dc3232; 
                    border-radius: 5px; 
                    z-index: 99999;
                    font-family: monospace;
                    font-size: 12px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                ">
                    <div style="background: #dc3232; color: #fff; padding: 10px; font-weight: bold;">
                        🐛 Form Builder Debugger
                        <button id="toggle-debug" style="float: right; background: none; border: none; color: #fff; cursor: pointer;">−</button>
                        <button id="clear-debug" style="float: right; background: none; border: none; color: #fff; cursor: pointer; margin-right: 5px;">🗑</button>
                    </div>
                    <div id="debug-content" style="padding: 10px; max-height: 400px; overflow-y: auto;">
                        <div id="debug-status"></div>
                        <div id="debug-logs"></div>
                    </div>
                    <div style="padding: 10px; border-top: 1px solid #ddd; background: #f9f9f9;">
                        <button id="force-add-field" class="button button-small" style="margin-right: 5px;">Force Add Field</button>
                        <button id="test-save" class="button button-small" style="margin-right: 5px;">Test Save</button>
                        <button id="show-state" class="button button-small">Show State</button>
                    </div>
                </div>
            `;
            
            $('body').append(panel);
            
            // Event handlers
            $('#toggle-debug').click(() => {
                const content = $('#debug-content');
                if (content.is(':visible')) {
                    content.hide();
                    $('#toggle-debug').text('+');
                } else {
                    content.show();
                    $('#toggle-debug').text('−');
                }
            });
            
            $('#clear-debug').click(() => {
                this.logs = [];
                $('#debug-logs').empty();
            });
            
            $('#force-add-field').click(() => this.forceAddField());
            $('#test-save').click(() => this.testSave());
            $('#show-state').click(() => this.showCurrentState());
        },
        
        updateDebugPanel: function() {
            const statusDiv = $('#debug-status');
            const logsDiv = $('#debug-logs');
            
            if (statusDiv.length === 0) return;
            
            // Update status
            const state = this.getCurrentState();
            statusDiv.html(`
                <div style="margin-bottom: 10px; padding: 5px; background: #f0f8ff; border: 1px solid #0073aa;">
                    <strong>Current State:</strong><br>
                    FormBuilder: ${state.hasFormBuilder ? '✅' : '❌'}<br>
                    Fields in formData: ${state.formDataFields}<br>
                    Fields in canvas: ${state.canvasFields}<br>
                    Form Name: ${state.formName || 'Empty'}
                </div>
            `);
            
            // Update logs (show last 10)
            const recentLogs = this.logs.slice(-10);
            logsDiv.html(recentLogs.map(log => `
                <div style="margin-bottom: 5px; padding: 3px; border-left: 3px solid #0073aa; background: #f9f9f9;">
                    <strong>[${log.time}]</strong> ${log.message}
                    ${log.data ? `<pre style="margin: 2px 0; font-size: 10px;">${JSON.stringify(log.data, null, 2)}</pre>` : ''}
                </div>
            `).join(''));
            
            // Auto scroll to bottom
            logsDiv.scrollTop(logsDiv[0].scrollHeight);
        },
        
        getCurrentState: function() {
            return {
                hasFormBuilder: typeof window.liftFormBuilder !== 'undefined',
                formDataFields: window.liftFormBuilder ? (window.liftFormBuilder.formData.fields ? window.liftFormBuilder.formData.fields.length : 0) : 0,
                canvasFields: $('#form-canvas .canvas-field').length,
                formName: $('#form-name').val()
            };
        },
        
        forceAddField: function() {
            this.log('🔨 Force adding field...');
            
            if (!window.liftFormBuilder) {
                this.log('❌ No FormBuilder found!');
                return;
            }
            
            // Create test field
            const testField = {
                id: 'debug_field_' + Date.now(),
                name: 'debug_test_field',
                type: 'text',
                label: 'Debug Test Field',
                placeholder: 'Test field added by debugger',
                required: false,
                description: 'This field was added by the debugger'
            };
            
            this.log('📝 Adding field to formData', testField);
            
            // Add to formData
            if (!window.liftFormBuilder.formData.fields) {
                window.liftFormBuilder.formData.fields = [];
            }
            window.liftFormBuilder.formData.fields.push(testField);
            
            this.log('✅ Field added to formData. New count:', window.liftFormBuilder.formData.fields.length);
            
            // Render in canvas
            if (window.liftFormBuilder.renderCanvasField) {
                const fieldHtml = window.liftFormBuilder.renderCanvasField(testField);
                $('#form-canvas').append(fieldHtml);
                $('.canvas-placeholder').hide();
                $('#form-canvas').addClass('has-fields');
                this.log('✅ Field rendered in canvas');
            } else {
                this.log('❌ renderCanvasField function not found!');
            }
            
            this.updateDebugPanel();
        },
        
        testSave: function() {
            this.log('💾 Testing save process...');
            
            if (!window.liftFormBuilder) {
                this.log('❌ No FormBuilder found!');
                return;
            }
            
            // Set form name if empty
            if (!$('#form-name').val().trim()) {
                $('#form-name').val('Debug Test Form ' + Date.now());
                this.log('📝 Set form name to:', $('#form-name').val());
            }
            
            const fieldsCount = window.liftFormBuilder.formData.fields ? window.liftFormBuilder.formData.fields.length : 0;
            this.log('📊 Current fields count:', fieldsCount);
            
            if (fieldsCount === 0) {
                this.log('⚠️ No fields found - adding test field first');
                this.forceAddField();
            }
            
            // Test clean form data
            let cleanData;
            try {
                if (window.liftFormBuilder.cleanFormData) {
                    cleanData = window.liftFormBuilder.cleanFormData();
                    this.log('🧹 Clean form data generated:', cleanData);
                } else {
                    this.log('❌ cleanFormData function not found!');
                    return;
                }
            } catch (e) {
                this.log('❌ Error in cleanFormData:', e.message);
                return;
            }
            
            // Test JSON stringify
            let jsonFields;
            try {
                jsonFields = JSON.stringify(cleanData.fields);
                this.log('✅ JSON stringify successful. Length:', jsonFields.length);
                this.log('📄 JSON content preview:', jsonFields.substring(0, 200) + '...');
            } catch (e) {
                this.log('❌ JSON stringify failed:', e.message);
                return;
            }
            
            // Test actual AJAX call
            this.log('🌐 Testing AJAX save call...');
            
            $.ajax({
                url: liftForms.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lift_forms_save',
                    nonce: liftForms.nonce,
                    form_id: $('#form-id').val() || 0,
                    name: $('#form-name').val(),
                    description: $('#form-description').val(),
                    fields: jsonFields,
                    settings: JSON.stringify(cleanData.settings || {})
                },
                success: (response) => {
                    this.log('✅ AJAX Success!', response);
                    if (response.success) {
                        this.log('🎉 Form saved successfully!');
                        if (response.data && response.data.form_id) {
                            $('#form-id').val(response.data.form_id);
                            this.log('📝 Form ID updated to:', response.data.form_id);
                        }
                    } else {
                        this.log('❌ Save failed:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    this.log('❌ AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                }
            });
        },
        
        showCurrentState: function() {
            const state = this.getCurrentState();
            this.log('📊 Current Form Builder State:', state);
            
            if (window.liftFormBuilder) {
                this.log('📋 FormData Details:', window.liftFormBuilder.formData);
                
                // Check canvas details
                const canvasFields = [];
                $('#form-canvas .canvas-field').each(function(index) {
                    canvasFields.push({
                        index: index,
                        id: $(this).data('field-id'),
                        type: $(this).data('field-type'),
                        visible: $(this).is(':visible')
                    });
                });
                this.log('🎨 Canvas Fields Details:', canvasFields);
            }
        },
        
        overrideFormBuilderMethods: function() {
            if (!window.liftFormBuilder) {
                this.log('⏳ Waiting for FormBuilder...');
                setTimeout(() => this.overrideFormBuilderMethods(), 1000);
                return;
            }
            
            this.log('🔧 Overriding FormBuilder methods...');
            
            // Override addField
            if (window.liftFormBuilder.addField) {
                const originalAddField = window.liftFormBuilder.addField;
                window.liftFormBuilder.addField = function(type) {
                    window.LiftFormBuilderDebugger.log('🆕 addField called with type:', type);
                    
                    const beforeCount = this.formData.fields.length;
                    const result = originalAddField.call(this, type);
                    const afterCount = this.formData.fields.length;
                    
                    window.LiftFormBuilderDebugger.log('📊 Fields count before/after:', {before: beforeCount, after: afterCount});
                    
                    if (afterCount <= beforeCount) {
                        window.LiftFormBuilderDebugger.log('⚠️ Field count did not increase! Possible issue.');
                    }
                    
                    return result;
                };
                this.log('✅ addField method overridden');
            }
            
            // Override updateFormData
            if (window.liftFormBuilder.updateFormData) {
                const originalUpdateFormData = window.liftFormBuilder.updateFormData;
                window.liftFormBuilder.updateFormData = function() {
                    window.LiftFormBuilderDebugger.log('🔄 updateFormData called');
                    
                    const beforeCount = this.formData.fields.length;
                    const canvasCount = $('#form-canvas .canvas-field').length;
                    
                    window.LiftFormBuilderDebugger.log('📊 Before update - FormData:', beforeCount, 'Canvas:', canvasCount);
                    
                    const result = originalUpdateFormData.call(this);
                    
                    const afterCount = this.formData.fields.length;
                    window.LiftFormBuilderDebugger.log('📊 After update - FormData:', afterCount);
                    
                    if (afterCount === 0 && canvasCount > 0) {
                        window.LiftFormBuilderDebugger.log('🚨 CRITICAL: updateFormData cleared fields when canvas has fields!');
                        // Try to recover
                        window.LiftFormBuilderDebugger.log('🔄 Attempting recovery...');
                        window.LiftFormBuilderDebugger.forceAddField();
                    }
                    
                    return result;
                };
                this.log('✅ updateFormData method overridden');
            }
            
            // Override saveForm
            if (window.liftFormBuilder.saveForm) {
                const originalSaveForm = window.liftFormBuilder.saveForm;
                window.liftFormBuilder.saveForm = function() {
                    window.LiftFormBuilderDebugger.log('💾 saveForm called');
                    
                    const fieldsCount = this.formData.fields.length;
                    window.LiftFormBuilderDebugger.log('📊 Fields count at save time:', fieldsCount);
                    
                    if (fieldsCount === 0) {
                        window.LiftFormBuilderDebugger.log('🚨 STOP: Attempting to save with 0 fields!');
                        window.LiftFormBuilderDebugger.log('🔄 Auto-adding field before save...');
                        window.LiftFormBuilderDebugger.forceAddField();
                        
                        // Wait a moment then try again
                        setTimeout(() => {
                            if (this.formData.fields.length > 0) {
                                window.LiftFormBuilderDebugger.log('✅ Field added, retrying save...');
                                originalSaveForm.call(this);
                            } else {
                                window.LiftFormBuilderDebugger.log('❌ Still no fields after auto-add!');
                                alert('ERROR: No fields to save. Check the debugger panel for details.');
                            }
                        }, 500);
                        
                        return;
                    }
                    
                    return originalSaveForm.call(this);
                };
                this.log('✅ saveForm method overridden');
            }
        },
        
        init: function() {
            this.log('🚀 Form Builder Debugger initialized');
            this.createDebugPanel();
            this.overrideFormBuilderMethods();
            
            // Monitor canvas changes
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        const removedNodes = Array.from(mutation.removedNodes);
                        
                        if (addedNodes.some(node => node.classList && node.classList.contains('canvas-field'))) {
                            this.log('👁️ Canvas field added');
                        }
                        
                        if (removedNodes.some(node => node.classList && node.classList.contains('canvas-field'))) {
                            this.log('👁️ Canvas field removed');
                        }
                        
                        this.updateDebugPanel();
                    }
                });
            });
            
            const canvas = document.getElementById('form-canvas');
            if (canvas) {
                observer.observe(canvas, { childList: true, subtree: true });
                this.log('👁️ Canvas observer started');
            }
            
            // Initial state
            this.showCurrentState();
        }
    };
    
    // Auto-initialize when ready
    $(document).ready(function() {
        if ($('.lift-form-builder').length > 0) {
            setTimeout(() => {
                window.LiftFormBuilderDebugger.init();
            }, 1000);
        }
    });
    
})(jQuery);
