/**
 * Enhanced Forms Builder JavaScript - Fix JSON Serialization Issues
 */

(function($) {
    'use strict';
    
    // Enhanced JSON cleaning and validation
    window.liftFormsEnhanced = {
        
        /**
         * Safe JSON stringify with error handling
         */
        safeStringify: function(obj) {
            try {
                // Remove circular references and problematic properties
                const cleaned = this.removeCircularReferences(obj);
                return JSON.stringify(cleaned);
            } catch (e) {
                console.error('LIFT Forms: JSON stringify error:', e);
                return null;
            }
        },
        
        /**
         * Remove circular references from object
         */
        removeCircularReferences: function(obj, seen = new WeakSet()) {
            if (obj === null || typeof obj !== "object") {
                return obj;
            }
            
            if (seen.has(obj)) {
                return {}; // Return empty object for circular reference
            }
            
            seen.add(obj);
            
            if (Array.isArray(obj)) {
                return obj.map(item => this.removeCircularReferences(item, seen));
            }
            
            const cleaned = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    // Skip DOM elements and functions
                    if (typeof obj[key] === 'function' || 
                        (obj[key] && obj[key].nodeType) ||
                        key.startsWith('jQuery') ||
                        key.startsWith('$')) {
                        continue;
                    }
                    
                    cleaned[key] = this.removeCircularReferences(obj[key], seen);
                }
            }
            
            return cleaned;
        },
        
        /**
         * Clean field data for serialization
         */
        cleanFieldData: function(field) {
            const allowedProps = [
                'id', 'name', 'type', 'label', 'placeholder', 'required', 
                'description', 'options', 'min', 'max', 'step', 'rows', 
                'multiple', 'accept', 'content', 'validation', 'className',
                'order', 'value', 'defaultValue'
            ];
            
            const cleaned = {};
            
            allowedProps.forEach(prop => {
                if (field.hasOwnProperty(prop) && field[prop] !== undefined && field[prop] !== null) {
                    if (Array.isArray(field[prop])) {
                        // Clean array items
                        cleaned[prop] = field[prop].filter(item => {
                            return item !== undefined && item !== null && item !== '';
                        }).map(item => {
                            if (typeof item === 'object') {
                                return this.removeCircularReferences(item);
                            }
                            return item;
                        });
                    } else if (typeof field[prop] === 'object') {
                        cleaned[prop] = this.removeCircularReferences(field[prop]);
                    } else {
                        cleaned[prop] = field[prop];
                    }
                }
            });
            
            return cleaned;
        },
        
        /**
         * Validate field data before saving
         */
        validateFieldData: function(field) {
            const errors = [];
            
            // Required properties
            if (!field.id || typeof field.id !== 'string') {
                errors.push('Field must have a valid ID');
            }
            
            if (!field.type || typeof field.type !== 'string') {
                errors.push('Field must have a valid type');
            }
            
            if (!field.name || typeof field.name !== 'string') {
                errors.push('Field must have a valid name');
            }
            
            // Type-specific validation
            if (['select', 'radio', 'checkbox'].includes(field.type)) {
                if (!field.options || !Array.isArray(field.options) || field.options.length === 0) {
                    errors.push('Choice fields must have at least one option');
                }
            }
            
            return errors;
        },
        
        /**
         * Enhanced form data cleaning
         */
        cleanFormData: function(formData) {
            const cleaned = {
                fields: [],
                settings: formData.settings || {}
            };
            
            if (formData.fields && Array.isArray(formData.fields)) {
                formData.fields.forEach(field => {
                    const validationErrors = this.validateFieldData(field);
                    if (validationErrors.length === 0) {
                        cleaned.fields.push(this.cleanFieldData(field));
                    } else {
                        console.warn('LIFT Forms: Invalid field data:', field, validationErrors);
                    }
                });
            }
            
            return cleaned;
        },
        
        /**
         * Test JSON serialization
         */
        testJsonSerialization: function(data) {
            const json = this.safeStringify(data);
            if (!json) {
                return { success: false, error: 'Failed to stringify data' };
            }
            
            try {
                const parsed = JSON.parse(json);
                return { success: true, json: json, parsed: parsed };
            } catch (e) {
                return { success: false, error: 'Invalid JSON: ' + e.message };
            }
        }
    };
    
    // Enhance existing form builder if it exists
    $(document).ready(function() {
        if (window.liftFormBuilder && typeof window.liftFormBuilder === 'object') {
            
            // Override the cleanFormData method
            const originalCleanFormData = window.liftFormBuilder.cleanFormData;
            window.liftFormBuilder.cleanFormData = function() {
                return window.liftFormsEnhanced.cleanFormData(this.formData);
            };
            
            // Override the saveForm method to use enhanced validation
            const originalSaveForm = window.liftFormBuilder.saveForm;
            window.liftFormBuilder.saveForm = function() {
                const formId = $('#form-id').val();
                const formName = $('#form-name').val().trim();
                const formDescription = $('#form-description').val().trim();
                
                if (!formName) {
                    alert('Please enter a form name');
                    $('#form-name').focus();
                    return;
                }
                
                // Ensure form data is up to date
                this.updateFormData();
                
                if (this.formData.fields.length === 0) {
                    alert('Please add at least one field to the form');
                    return;
                }
                
                // Use enhanced cleaning
                const cleanData = window.liftFormsEnhanced.cleanFormData(this.formData);
                
                // Test JSON serialization
                const jsonTest = window.liftFormsEnhanced.testJsonSerialization(cleanData.fields);
                if (!jsonTest.success) {
                    alert('Error preparing form data: ' + jsonTest.error);
                    console.error('LIFT Forms JSON Error:', jsonTest);
                    return;
                }
                
                const saveBtn = $('#save-form');
                const originalText = saveBtn.text();
                saveBtn.text(liftForms.strings.saving).prop('disabled', true);
                
                $.ajax({
                    url: liftForms.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lift_forms_save',
                        nonce: liftForms.nonce,
                        form_id: formId,
                        name: formName,
                        description: formDescription,
                        fields: jsonTest.json,
                        settings: window.liftFormsEnhanced.safeStringify(cleanData.settings)
                    },
                    success: function(response) {
                        console.log('LIFT Forms Save Response:', response);
                        
                        if (response.success) {
                            if (!formId) {
                                $('#form-id').val(response.data.form_id);
                                const newUrl = window.location.href + '&id=' + response.data.form_id;
                                window.history.replaceState({}, '', newUrl);
                            }
                            alert(liftForms.strings.saved);
                        } else {
                            alert(response.data || liftForms.strings.error);
                            console.error('LIFT Forms Save Error:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('LIFT Forms AJAX Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        alert('Network error: ' + error);
                    },
                    complete: function() {
                        saveBtn.text(originalText).prop('disabled', false);
                    }
                });
            };
        }
    });
    
})(jQuery);

// Debug helper - log all AJAX requests
if (typeof ajaxurl !== 'undefined') {
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.data && typeof settings.data === 'string' && settings.data.includes('lift_forms')) {
            console.log('LIFT Forms AJAX Request:', {
                url: settings.url,
                data: settings.data,
                type: settings.type
            });
        }
    });
    
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.data && typeof settings.data === 'string' && settings.data.includes('lift_forms')) {
            console.log('LIFT Forms AJAX Response:', {
                status: xhr.status,
                response: xhr.responseText,
                settings: settings
            });
        }
    });
}
