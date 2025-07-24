/**
 * LIFT Forms Frontend JavaScript
 */

(function($) {
    'use strict';
    
    let liftFormsHandler = {
        
        init: function() {
            this.bindEvents();
            this.initValidation();
        },
        
        bindEvents: function() {
            // Form submission
            $(document).on('submit', '.lift-form', this.handleFormSubmit.bind(this));
            
            // Real-time validation
            $(document).on('blur', '.lift-form input, .lift-form textarea, .lift-form select', this.validateField.bind(this));
            
            // File input changes
            $(document).on('change', '.lift-form input[type="file"]', this.handleFileChange.bind(this));
            
            // Clear errors on input
            $(document).on('input change', '.lift-form input, .lift-form textarea, .lift-form select', this.clearFieldError.bind(this));
        },
        
        initValidation: function() {
            // Add validation attributes
            $('.lift-form').each(function() {
                $(this).find('input[required], textarea[required], select[required]').each(function() {
                    $(this).attr('aria-required', 'true');
                });
            });
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const formContainer = form.closest('.lift-form-container');
            const formId = formContainer.data('form-id');
            
            // Clear previous messages
            this.clearMessages(form);
            
            // Validate form
            if (!this.validateForm(form)) {
                this.showError(form, liftFormsFrontend.strings.error);
                return;
            }
            
            // Prepare form data
            const formData = this.prepareFormData(form);
            
            // Show loading state
            this.setLoadingState(form, true);
            
            // Submit form
            $.ajax({
                url: liftFormsFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lift_forms_submit',
                    nonce: liftFormsFrontend.nonce,
                    form_id: formId,
                    form_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        liftFormsHandler.showSuccess(form, response.data.message || liftFormsFrontend.strings.submitted);
                        liftFormsHandler.resetForm(form);
                    } else {
                        if (response.data && response.data.errors) {
                            liftFormsHandler.showFieldErrors(form, response.data.errors);
                        }
                        liftFormsHandler.showError(form, response.data.message || liftFormsFrontend.strings.error);
                    }
                },
                error: function() {
                    liftFormsHandler.showError(form, liftFormsFrontend.strings.error);
                },
                complete: function() {
                    liftFormsHandler.setLoadingState(form, false);
                }
            });
        },
        
        prepareFormData: function(form) {
            const formData = {};
            
            form.find('input, textarea, select').each(function() {
                const field = $(this);
                const name = field.attr('name');
                const type = field.attr('type');
                
                if (!name) return;
                
                if (type === 'checkbox') {
                    if (name.endsWith('[]')) {
                        // Multiple checkboxes
                        const baseName = name.replace('[]', '');
                        if (!formData[baseName]) {
                            formData[baseName] = [];
                        }
                        if (field.is(':checked')) {
                            formData[baseName].push(field.val());
                        }
                    } else {
                        // Single checkbox
                        formData[name] = field.is(':checked') ? field.val() : '';
                    }
                } else if (type === 'radio') {
                    if (field.is(':checked')) {
                        formData[name] = field.val();
                    }
                } else if (type === 'file') {
                    // Handle file uploads
                    if (field[0].files.length > 0) {
                        if (field.attr('multiple')) {
                            formData[name] = [];
                            for (let i = 0; i < field[0].files.length; i++) {
                                formData[name].push(field[0].files[i].name);
                            }
                        } else {
                            formData[name] = field[0].files[0].name;
                        }
                    }
                } else {
                    formData[name] = field.val();
                }
            });
            
            return formData;
        },
        
        validateForm: function(form) {
            let isValid = true;
            
            form.find('input, textarea, select').each(function() {
                if (!liftFormsHandler.validateField({ target: this })) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        validateField: function(e) {
            const field = $(e.target);
            const fieldContainer = field.closest('.lift-form-field');
            const value = field.val().trim();
            const isRequired = field.attr('required') || field.attr('aria-required') === 'true';
            const fieldType = field.attr('type') || field.prop('tagName').toLowerCase();
            
            // Clear previous errors
            this.clearFieldError({ target: field[0] });
            
            let isValid = true;
            let errorMessage = '';
            
            // Required field validation
            if (isRequired) {
                if (fieldType === 'checkbox') {
                    const name = field.attr('name');
                    if (name && name.endsWith('[]')) {
                        // Multiple checkboxes - check if at least one is checked
                        const checkedBoxes = fieldContainer.find(`input[name="${name}"]:checked`);
                        if (checkedBoxes.length === 0) {
                            isValid = false;
                            errorMessage = liftFormsFrontend.strings.requiredField;
                        }
                    } else {
                        // Single checkbox
                        if (!field.is(':checked')) {
                            isValid = false;
                            errorMessage = liftFormsFrontend.strings.requiredField;
                        }
                    }
                } else if (fieldType === 'radio') {
                    // Radio buttons - check if any in group is selected
                    const name = field.attr('name');
                    const checkedRadio = fieldContainer.find(`input[name="${name}"]:checked`);
                    if (checkedRadio.length === 0) {
                        isValid = false;
                        errorMessage = liftFormsFrontend.strings.requiredField;
                    }
                } else if (fieldType === 'file') {
                    // File upload
                    if (field[0].files.length === 0) {
                        isValid = false;
                        errorMessage = liftFormsFrontend.strings.requiredField;
                    }
                } else {
                    // Text fields
                    if (!value) {
                        isValid = false;
                        errorMessage = liftFormsFrontend.strings.requiredField;
                    }
                }
            }
            
            // Type-specific validation (only if field has value)
            if (value && isValid) {
                switch (fieldType) {
                    case 'email':
                        if (!this.isValidEmail(value)) {
                            isValid = false;
                            errorMessage = liftFormsFrontend.strings.invalidEmail;
                        }
                        break;
                        
                    case 'number':
                        if (isNaN(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid number.';
                        } else {
                            // Check min/max if specified
                            const min = field.attr('min');
                            const max = field.attr('max');
                            const numValue = parseFloat(value);
                            
                            if (min && numValue < parseFloat(min)) {
                                isValid = false;
                                errorMessage = `Value must be at least ${min}.`;
                            } else if (max && numValue > parseFloat(max)) {
                                isValid = false;
                                errorMessage = `Value must be no more than ${max}.`;
                            }
                        }
                        break;
                        
                    case 'date':
                        if (!this.isValidDate(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid date.';
                        }
                        break;
                }
            }
            
            // File size and type validation
            if (fieldType === 'file' && field[0].files.length > 0) {
                const file = field[0].files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB default
                
                if (file.size > maxSize) {
                    isValid = false;
                    errorMessage = 'File size must be less than 10MB.';
                }
                
                // Check accepted file types
                const accept = field.attr('accept');
                if (accept) {
                    const acceptedTypes = accept.split(',').map(type => type.trim());
                    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                    const mimeType = file.type;
                    
                    let isAcceptedType = false;
                    acceptedTypes.forEach(type => {
                        if (type.startsWith('.') && fileExtension === type) {
                            isAcceptedType = true;
                        } else if (type.includes('/') && mimeType === type) {
                            isAcceptedType = true;
                        }
                    });
                    
                    if (!isAcceptedType) {
                        isValid = false;
                        errorMessage = `Please select a valid file type (${accept}).`;
                    }
                }
            }
            
            // Show error if validation failed
            if (!isValid) {
                this.showFieldError(field, errorMessage);
            }
            
            return isValid;
        },
        
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        isValidDate: function(dateString) {
            const date = new Date(dateString);
            return date instanceof Date && !isNaN(date);
        },
        
        showFieldError: function(field, message) {
            const fieldContainer = field.closest('.lift-form-field');
            fieldContainer.addClass('error');
            
            // Remove existing error message
            fieldContainer.find('.field-error').remove();
            
            // Add error message
            const errorElement = $('<div class="field-error"></div>').text(message);
            fieldContainer.append(errorElement);
            
            // Add ARIA attributes
            field.attr('aria-invalid', 'true');
            field.attr('aria-describedby', field.attr('id') + '-error');
            errorElement.attr('id', field.attr('id') + '-error');
        },
        
        clearFieldError: function(e) {
            const field = $(e.target);
            const fieldContainer = field.closest('.lift-form-field');
            
            fieldContainer.removeClass('error');
            fieldContainer.find('.field-error').remove();
            field.removeAttr('aria-invalid aria-describedby');
        },
        
        showFieldErrors: function(form, errors) {
            Object.keys(errors).forEach(fieldName => {
                const field = form.find(`[name="${fieldName}"]`).first();
                if (field.length > 0) {
                    this.showFieldError(field, errors[fieldName]);
                }
            });
        },
        
        showError: function(form, message) {
            const messagesContainer = form.siblings('.lift-form-messages');
            const errorElement = messagesContainer.find('.form-error');
            
            errorElement.text(message).show();
            
            // Scroll to error
            $('html, body').animate({
                scrollTop: errorElement.offset().top - 100
            }, 300);
        },
        
        showSuccess: function(form, message) {
            const messagesContainer = form.siblings('.lift-form-messages');
            const successElement = messagesContainer.find('.form-success');
            
            successElement.text(message).show();
            
            // Scroll to success message
            $('html, body').animate({
                scrollTop: successElement.offset().top - 100
            }, 300);
        },
        
        clearMessages: function(form) {
            const messagesContainer = form.siblings('.lift-form-messages');
            messagesContainer.find('.form-error, .form-success').hide();
            
            // Clear field errors
            form.find('.lift-form-field.error').removeClass('error');
            form.find('.field-error').remove();
        },
        
        setLoadingState: function(form, isLoading) {
            const submitBtn = form.find('.lift-form-submit-btn');
            const btnText = submitBtn.find('.btn-text');
            const btnSpinner = submitBtn.find('.btn-spinner');
            
            if (isLoading) {
                submitBtn.prop('disabled', true);
                btnText.hide();
                btnSpinner.show();
            } else {
                submitBtn.prop('disabled', false);
                btnText.show();
                btnSpinner.hide();
            }
        },
        
        resetForm: function(form) {
            // Reset form fields
            form[0].reset();
            
            // Clear custom states
            form.find('.lift-form-field').removeClass('error');
            form.find('.field-error').remove();
            form.find('input, textarea, select').removeAttr('aria-invalid aria-describedby');
            
            // Reset file input displays
            form.find('input[type="file"]').each(function() {
                $(this).next('.file-display').remove();
            });
        },
        
        handleFileChange: function(e) {
            const fileInput = $(e.target);
            const files = e.target.files;
            
            // Remove existing file display
            fileInput.next('.file-display').remove();
            
            if (files.length > 0) {
                let displayText = '';
                
                if (files.length === 1) {
                    displayText = files[0].name;
                } else {
                    displayText = `${files.length} files selected`;
                }
                
                // Add file display
                const fileDisplay = $(`<div class="file-display">${displayText}</div>`);
                fileDisplay.css({
                    'font-size': '13px',
                    'color': '#666',
                    'margin-top': '5px',
                    'padding': '8px 12px',
                    'background': '#f8f9fa',
                    'border-radius': '4px',
                    'border': '1px solid #e0e0e0'
                });
                
                fileInput.after(fileDisplay);
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.lift-form').length > 0) {
            liftFormsHandler.init();
        }
    });
    
    // Make handler globally accessible
    window.liftFormsHandler = liftFormsHandler;
    
})(jQuery);
