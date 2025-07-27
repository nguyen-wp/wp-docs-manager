/**
 * Form Builder Field Sync Fix
 * Sửa lỗi sync fields giữa canvas và formData
 */

(function($) {
    'use strict';
    
    // Enhanced field synchronization
    window.liftFormBuilderFix = {
        
        /**
         * Enhanced updateFormData that preserves fields
         */
        enhancedUpdateFormData: function() {
            if (!window.liftFormBuilder) return;
            
            console.log('=== Enhanced Update Form Data ===');
            console.log('Current formData.fields:', window.liftFormBuilder.formData.fields);
            
            // Get canvas fields
            const canvasFields = $('#form-canvas .canvas-field');
            console.log('Canvas fields count:', canvasFields.length);
            
            if (canvasFields.length === 0) {
                // If no canvas fields but we have formData fields, keep them
                if (window.liftFormBuilder.formData.fields.length > 0) {
                    console.log('No canvas fields but formData has fields - keeping formData');
                    return; // Don't clear the formData
                }
            }
            
            // Build ordered fields array
            const orderedFields = [];
            
            canvasFields.each((index, element) => {
                const $element = $(element);
                const fieldId = $element.data('field-id');
                
                console.log('Processing canvas field:', fieldId);
                
                if (fieldId) {
                    // Find existing field data
                    let existingField = window.liftFormBuilder.formData.fields.find(f => f.id === fieldId);
                    
                    if (existingField) {
                        existingField.order = index;
                        orderedFields.push(existingField);
                        console.log('Added existing field:', existingField);
                    } else {
                        // Create basic field data from canvas if not found
                        const fieldType = $element.data('field-type') || 'text';
                        const basicField = {
                            id: fieldId,
                            name: fieldId.replace('field_', ''),
                            type: fieldType,
                            label: fieldType.charAt(0).toUpperCase() + fieldType.slice(1) + ' Field',
                            order: index,
                            required: false
                        };
                        orderedFields.push(basicField);
                        console.log('Created basic field:', basicField);
                    }
                }
            });
            
            // Update formData
            window.liftFormBuilder.formData.fields = orderedFields;
            console.log('Updated formData.fields:', window.liftFormBuilder.formData.fields);
            console.log('=== End Enhanced Update Form Data ===');
        },
        
        /**
         * Force sync canvas with formData
         */
        forceSyncCanvasWithFormData: function() {
            if (!window.liftFormBuilder || !window.liftFormBuilder.formData.fields) return;
            
            console.log('Force syncing canvas with formData...');
            
            const canvas = $('#form-canvas');
            if (canvas.length === 0) return;
            
            // Clear canvas
            canvas.empty();
            
            // Re-render all fields from formData
            window.liftFormBuilder.formData.fields.forEach(field => {
                if (window.liftFormBuilder.renderCanvasField) {
                    const fieldHtml = window.liftFormBuilder.renderCanvasField(field);
                    canvas.append(fieldHtml);
                }
            });
            
            // Hide/show placeholder
            if (window.liftFormBuilder.formData.fields.length > 0) {
                $('.canvas-placeholder').hide();
                canvas.addClass('has-fields');
            } else {
                $('.canvas-placeholder').show();
                canvas.removeClass('has-fields');
            }
            
            console.log('Canvas sync complete');
        },
        
        /**
         * Backup and restore form data
         */
        backupFormData: function() {
            if (window.liftFormBuilder && window.liftFormBuilder.formData) {
                const backup = JSON.parse(JSON.stringify(window.liftFormBuilder.formData));
                localStorage.setItem('liftFormBuilderBackup', JSON.stringify(backup));
                console.log('Form data backed up');
                return backup;
            }
            return null;
        },
        
        restoreFormData: function() {
            const backup = localStorage.getItem('liftFormBuilderBackup');
            if (backup && window.liftFormBuilder) {
                try {
                    const restoredData = JSON.parse(backup);
                    window.liftFormBuilder.formData = restoredData;
                    this.forceSyncCanvasWithFormData();
                    console.log('Form data restored from backup');
                    return true;
                } catch (e) {
                    console.error('Error restoring form data:', e);
                }
            }
            return false;
        },
        
        /**
         * Debug current state
         */
        debugCurrentState: function() {
            const state = {
                formBuilderExists: typeof window.liftFormBuilder !== 'undefined',
                formDataExists: window.liftFormBuilder && window.liftFormBuilder.formData,
                fieldsCount: 0,
                canvasFieldsCount: $('#form-canvas .canvas-field').length,
                canvasHasPlaceholder: $('.canvas-placeholder').is(':visible'),
                fieldIds: []
            };
            
            if (window.liftFormBuilder && window.liftFormBuilder.formData) {
                state.fieldsCount = window.liftFormBuilder.formData.fields ? window.liftFormBuilder.formData.fields.length : 0;
                state.formData = window.liftFormBuilder.formData;
            }
            
            // Get field IDs from canvas
            $('#form-canvas .canvas-field').each(function() {
                const fieldId = $(this).data('field-id');
                if (fieldId) state.fieldIds.push(fieldId);
            });
            
            console.log('Current Form Builder State:', state);
            return state;
        }
    };
    
    // Auto-initialize when ready
    $(document).ready(function() {
        if ($('.lift-form-builder').length > 0) {
            console.log('Form Builder page detected, initializing fixes...');
            
            // Override updateFormData if form builder exists
            setTimeout(function() {
                if (window.liftFormBuilder && window.liftFormBuilder.updateFormData) {
                    const originalUpdate = window.liftFormBuilder.updateFormData;
                    
                    window.liftFormBuilder.updateFormData = function() {
                        console.log('updateFormData called - using enhanced version');
                        window.liftFormBuilderFix.enhancedUpdateFormData();
                    };
                    
                    console.log('updateFormData overridden with enhanced version');
                }
                
                // Auto-backup form data when fields change using modern MutationObserver
                let backupTimer;
                const canvasElement = document.getElementById('form-canvas');
                if (canvasElement) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList') {
                                clearTimeout(backupTimer);
                                backupTimer = setTimeout(function() {
                                    window.liftFormBuilderFix.backupFormData();
                                }, 1000);
                            }
                        });
                    });
                    
                    observer.observe(canvasElement, {
                        childList: true,
                        subtree: true
                    });
                }
                
            }, 2000);
        }
    });
    
    // Add global debug functions
    window.debugFormBuilder = window.liftFormBuilderFix.debugCurrentState;
    window.fixFormBuilder = window.liftFormBuilderFix.forceSyncCanvasWithFormData;
    window.backupFormData = window.liftFormBuilderFix.backupFormData;
    window.restoreFormData = window.liftFormBuilderFix.restoreFormData;
    
})(jQuery);

// Console helpers
console.log('Form Builder Fix loaded. Available functions:');
console.log('- debugFormBuilder() - Show current state');
console.log('- fixFormBuilder() - Force sync canvas with formData');  
console.log('- backupFormData() - Backup current form data');
console.log('- restoreFormData() - Restore form data from backup');
