/**
 * LIFT Forms Builder JavaScript - Debug Version
 */

console.log('Loading LIFT Forms Builder...');

(function($) {
    'use strict';
    
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('jQuery UI loaded:', typeof $.ui !== 'undefined');
    
    let formBuilder = {
        fieldCounter: 0,
        
        init: function() {
            console.log('Initializing Form Builder...');
            this.initDragDrop();
            console.log('Drag drop initialized');
        },
        
        initDragDrop: function() {
            console.log('Setting up drag and drop...');
            
            // Check if elements exist
            console.log('Field items found:', $('.field-item').length);
            console.log('Canvas found:', $('#form-canvas').length);
            
            // Make field items draggable
            $('.field-item').draggable({
                helper: 'clone',
                cursor: 'grabbing',
                opacity: 0.8,
                revert: 'invalid',
                start: function(event, ui) {
                    console.log('Drag started for:', $(this).data('type'));
                    ui.helper.css('z-index', 1000);
                    $('#form-canvas').addClass('drag-over');
                }
            });
            
            console.log('Draggable initialized on', $('.field-item').length, 'items');
            
            // Make canvas droppable
            $('#form-canvas').droppable({
                accept: '.field-item',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    const fieldType = ui.draggable.data('type');
                    console.log('Dropped field type:', fieldType);
                    formBuilder.addField(fieldType);
                    $('#form-canvas').removeClass('drag-over');
                },
                over: function() {
                    console.log('Drag over canvas');
                    $(this).addClass('drag-over');
                },
                out: function() {
                    console.log('Drag out of canvas');
                    $(this).removeClass('drag-over');
                }
            });
            
            console.log('Droppable initialized on canvas');
        },
        
        addField: function(type) {
            console.log('Adding field type:', type);
            this.fieldCounter++;
            const fieldId = 'field_' + this.fieldCounter;
            
            const fieldHtml = `
                <div class="canvas-field" data-type="${type}" data-id="${fieldId}">
                    <div class="field-header">
                        <strong>${this.getFieldLabel(type)}</strong>
                        <button type="button" class="field-delete" onclick="$(this).closest('.canvas-field').remove()">Delete</button>
                    </div>
                    <div class="field-preview">
                        ${this.getFieldPreview(type, fieldId)}
                    </div>
                </div>
            `;
            
            // Hide placeholder and add field
            $('.canvas-placeholder').hide();
            $('#form-canvas').append(fieldHtml);
            $('#form-canvas').addClass('has-fields');
            
            console.log('Field added:', fieldId);
        },
        
        getFieldLabel: function(type) {
            const labels = {
                text: 'Text Input',
                email: 'Email Address',
                textarea: 'Textarea',
                number: 'Number',
                date: 'Date',
                file: 'File Upload',
                select: 'Dropdown',
                radio: 'Radio Buttons',
                checkbox: 'Checkboxes'
            };
            return labels[type] || type;
        },
        
        getFieldPreview: function(type, fieldId) {
            switch(type) {
                case 'text':
                case 'email':
                case 'number':
                case 'date':
                    return `<input type="${type}" placeholder="Preview of ${type} field" disabled>`;
                case 'textarea':
                    return `<textarea placeholder="Preview of textarea field" disabled></textarea>`;
                case 'select':
                    return `<select disabled><option>Preview of dropdown</option></select>`;
                case 'file':
                    return `<input type="file" disabled>`;
                case 'radio':
                    return `<label><input type="radio" disabled> Option 1</label><br><label><input type="radio" disabled> Option 2</label>`;
                case 'checkbox':
                    return `<label><input type="checkbox" disabled> Option 1</label><br><label><input type="checkbox" disabled> Option 2</label>`;
                default:
                    return `<p>Preview of ${type} field</p>`;
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, checking for form builder...');
        if ($('.lift-form-builder').length > 0) {
            console.log('Form builder found, initializing...');
            formBuilder.init();
        } else {
            console.log('Form builder not found on this page');
        }
    });
    
    // Make formBuilder globally accessible
    window.liftFormBuilder = formBuilder;
    
})(jQuery);
