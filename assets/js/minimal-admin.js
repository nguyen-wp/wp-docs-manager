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
        
        // Auto-hide WordPress notices
        $('.notice.is-dismissible').each(function() {
            const notice = $(this);
            if (notice.hasClass('notice-success')) {
                setTimeout(function() {
                    notice.fadeOut(500, function() {
                        notice.remove();
                    });
                }, 5000);
            }
        });
        
        // Basic save form functionality
        $('#save-form').off('click.minimal-admin').on('click.minimal-admin', function(e) {
            // Check if advanced form builder is active
            if ($('#form-fields-list .form-row').length > 0 || 
                (window.formBuilder && window.formBuilder.formData && 
                 typeof window.formBuilder.formData === 'object' && 
                 window.formBuilder.formData.type === 'advanced')) {
                // Let the advanced form builder handle the save
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            const formId = $('#form-id').val();
            const formName = $('#form-name').val().trim();
            const formDescription = $('#form-description').val().trim();
            
            // Enhanced validation for form name
            if (!formName) {
                showMessage('Please enter a form name before saving.', 'error');
                $('#form-name').focus().addClass('error');
                return;
            }
            
            // Check minimum length
            if (formName.length < 3) {
                showMessage('Form name must be at least 3 characters long.', 'error');
                $('#form-name').focus().addClass('error');
                return;
            }
            
            // Check for valid characters (letters, numbers, spaces, basic punctuation)
            const validNamePattern = /^[a-zA-Z0-9\s\-_.()]+$/;
            if (!validNamePattern.test(formName)) {
                showMessage('Form name contains invalid characters. Please use only letters, numbers, spaces, and basic punctuation.', 'error');
                $('#form-name').focus().addClass('error');
                return;
            }
            
            // Remove error styling if validation passes
            $('#form-name').removeClass('error');
            
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
                            
                            // Show success message first
                            showMessage('Form created successfully! Redirecting to edit page...', 'success');
                            
                            // Redirect to edit page after short delay
                            setTimeout(function() {
                                // Try multiple methods to build the correct URL
                                let editUrl;
                                
                                // Method 1: Use current location with query string replacement
                                if (window.location.search) {
                                    // Replace existing query params
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('page', 'lift-forms-builder');
                                    url.searchParams.set('id', response.data.form_id);
                                    url.searchParams.set('created', '1');
                                    editUrl = url.href;
                                } else {
                                    // Method 2: Build from scratch
                                    const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
                                    editUrl = baseUrl + '?page=lift-forms-builder&id=' + response.data.form_id + '&created=1';
                                }
                                
                                // Try redirect, with fallback
                                try {
                                    window.location.href = editUrl;
                                } catch (error) {
                                    // Fallback: reload current page with form ID
                                    window.location.reload();
                                }
                            }, 1500);
                        } else {
                            // For existing forms, just show success message
                            showMessage('Form updated successfully!', 'success');
                        }
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
        
        // Clear error styling when user starts typing
        $('#form-name').on('input', function() {
            $(this).removeClass('error');
            $('.lift-form-message.error').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-save form name and description on change
        let saveTimeout;
        $('#form-name, #form-description').on('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // Could implement auto-save here if needed
            }, 1000);
        });
    });
    
})(jQuery);
