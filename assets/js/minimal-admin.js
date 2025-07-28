/**
 * LIFT Forms - Minimal Admin JavaScript
 * Only basic functionality for form header
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Check if we're on the form builder page
        if ($('.lift-form-builder').length === 0) {
            return;
        }
        
        console.log('LIFT Forms - Minimal admin loaded');
        
        // Basic save form functionality
        $('#save-form').on('click', function(e) {
            // Check if advanced form builder is active
            if ($('#form-fields-list .form-row').length > 0 || 
                (window.formBuilder && window.formBuilder.formData && 
                 typeof window.formBuilder.formData === 'object' && 
                 window.formBuilder.formData.type === 'advanced')) {
                // Let the advanced form builder handle the save
                console.log('Advanced form builder detected, skipping minimal admin save');
                return;
            }

            const formId = $('#form-id').val();
            const formName = $('#form-name').val().trim();
            const formDescription = $('#form-description').val().trim();
            
            if (!formName) {
                alert('Please enter a form name');
                $('#form-name').focus();
                return;
            }
            
            const $saveBtn = $(this);
            const originalText = $saveBtn.text();
            
            $saveBtn.text('Saving...').prop('disabled', true);
            
            // Get current form fields from form builder if available
            let formFields = [];
            
            // Try to get fields from form builder instance
            if (window.formBuilder && window.formBuilder.formData && window.formBuilder.formData.fields) {
                formFields = window.formBuilder.formData.fields;
            }
            // Try to get from global variable if form builder sets it
            else if (window.liftCurrentFormFields) {
                formFields = window.liftCurrentFormFields;
            }
            // Try to get from canvas fields if they exist
            else if ($('#form-canvas .canvas-field').length > 0) {
                // Extract field data from canvas
                $('#form-canvas .canvas-field').each(function() {
                    const fieldElement = $(this);
                    const fieldId = fieldElement.data('field-id');
                    const fieldType = fieldElement.data('field-type');
                    
                    if (fieldId && fieldType) {
                        const field = {
                            id: fieldId,
                            type: fieldType,
                            name: fieldType + '_' + fieldId.replace('field_', ''),
                            label: fieldElement.find('.field-name').text() || fieldType.charAt(0).toUpperCase() + fieldType.slice(1),
                            required: false
                        };
                        formFields.push(field);
                    }
                });
            }

            // Simple AJAX save with actual form data
            $.ajax({
                url: liftForms.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lift_forms_save',
                    nonce: liftForms.nonce,
                    form_id: formId,
                    name: formName,
                    description: formDescription,
                    fields: JSON.stringify(formFields),
                    settings: JSON.stringify({})
                },
                success: function(response) {
                    if (response.success) {
                        if (!formId) {
                            $('#form-id').val(response.data.form_id);
                            // Update URL if this is a new form
                            const newUrl = window.location.href + '&id=' + response.data.form_id;
                            window.history.replaceState({}, '', newUrl);
                        }
                        showMessage('Form saved successfully!', 'success');
                    } else {
                        showMessage(response.data || 'Error saving form', 'error');
                    }
                },
                error: function() {
                    showMessage('Network error occurred', 'error');
                },
                complete: function() {
                    $saveBtn.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Basic preview functionality
        $('#preview-form').on('click', function() {
            const formName = $('#form-name').val().trim() || 'Untitled Form';
            const formDescription = $('#form-description').val().trim();
            
            let previewHTML = `
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                    <h2 style="margin-top: 0; color: #333;">${formName}</h2>
                    ${formDescription ? `<p style="color: #666; margin-bottom: 20px;">${formDescription}</p>` : ''}
                    <div style="padding: 40px; text-align: center; background: #f9f9f9; border: 1px dashed #ddd; border-radius: 4px;">
                        <p style="color: #888; margin: 0;">No form fields have been added yet.</p>
                        <p style="color: #888; margin: 10px 0 0 0; font-size: 12px;">Add fields to the form builder to see them in the preview.</p>
                    </div>
                </div>
            `;
            
            // Create simple modal
            const modal = $(`
                <div id="simple-preview-modal" style="
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: rgba(0,0,0,0.7); z-index: 100000; display: flex; 
                    align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;
                ">
                    <div style="
                        background: #fff; max-width: 800px; width: 100%; max-height: 90vh; 
                        overflow-y: auto; border-radius: 4px; position: relative;
                    ">
                        <div style="
                            padding: 20px; border-bottom: 1px solid #ddd; 
                            display: flex; justify-content: space-between; align-items: center;
                        ">
                            <h3 style="margin: 0;">Form Preview</h3>
                            <button type="button" id="close-preview" style="
                                background: none; border: none; font-size: 24px; 
                                cursor: pointer; padding: 0; color: #666;
                            ">&times;</button>
                        </div>
                        <div style="padding: 20px;">
                            ${previewHTML}
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            // Close modal events
            $('#close-preview, #simple-preview-modal').on('click', function(e) {
                if (e.target.id === 'close-preview' || e.target.id === 'simple-preview-modal') {
                    modal.remove();
                }
            });
        });
        
        // Show message function
        function showMessage(message, type) {
            // Remove any existing messages
            $('.lift-form-message').remove();
            
            const messageEl = $(`
                <div class="lift-form-message ${type}">
                    ${message}
                </div>
            `);
            
            $('.lift-form-builder').before(messageEl);
            
            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(function() {
                    messageEl.fadeOut(function() {
                        messageEl.remove();
                    });
                }, 3000);
            }
        }
        
        // Auto-save form name and description on change
        let saveTimeout;
        $('#form-name, #form-description').on('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // Could implement auto-save here if needed
                console.log('Form data changed');
            }, 1000);
        });
    });
    
})(jQuery);
