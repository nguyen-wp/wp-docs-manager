/**
 * Simple Form Builder for WordPress
 * Using jQuery FormBuilder library for easy form creation
 */
(function($) {
    'use strict';

    let formBuilderInstance = null;
    let currentFormId = 0;
    let formData = [];

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('#form-builder-container').length) {
            console.log('Form builder container found, initializing...');
            initFormBuilder();
        }
    });

    /**
     * Initialize Form Builder
     */
    function initFormBuilder() {
        // Get form ID if editing
        currentFormId = parseInt($('#form-id').val()) || 0;
        
        // Load existing form data if editing
        if (currentFormId > 0) {
            loadFormData(currentFormId);
        } else {
            createFormBuilder();
        }

        // Bind save events
        bindEvents();
    }

    /**
     * Create Form Builder
     */
    function createFormBuilder(existingData = []) {
        const container = $('#form-builder-container');
        
        if (!container.length) {
            console.error('Form builder container not found');
            return;
        }

        // Clear container and show form builder
        container.html('<div id="form-builder-simple"></div>');

        // Check if FormBuilder library is available
        if (typeof FormBuilder === 'undefined') {
            // Fallback to simple HTML form builder
            createSimpleFormBuilder(existingData);
            return;
        }

        // Initialize FormBuilder with custom configuration
        try {
            formBuilderInstance = $('#form-builder-simple').formBuilder({
                formData: existingData,
                showActionButtons: true,
                controlOrder: [
                    'text',
                    'textarea', 
                    'select',
                    'radio-group',
                    'checkbox-group',
                    'checkbox',
                    'date',
                    'file',
                    'number',
                    'hidden',
                    'header',
                    'paragraph',
                    'button'
                ],
                typeUserDisabledAttrs: {
                    'text': ['access'],
                    'textarea': ['access'],
                    'select': ['access'],
                    'radio-group': ['access'],
                    'checkbox-group': ['access'],
                    'date': ['access'],
                    'file': ['access'],
                    'number': ['access']
                },
                typeUserAttrs: {
                    text: {
                        className: {
                            label: 'CSS Class',
                            value: 'form-control'
                        }
                    }
                },
                i18n: {
                    locale: 'en-US',
                    location: window.liftAjax ? window.liftAjax.pluginUrl + '/assets/js/i18n/' : ''
                }
            });

            console.log('FormBuilder initialized successfully');
        } catch (error) {
            console.error('FormBuilder initialization failed:', error);
            createSimpleFormBuilder(existingData);
        }
    }

    /**
     * Fallback Simple Form Builder (HTML based)
     */
    function createSimpleFormBuilder(existingData = []) {
        const container = $('#form-builder-container');
        
        const builderHTML = `
            <div class="modern-form-builder">
                <div class="form-builder-palette">
                    <!-- Row Layouts Section -->
                    <div class="palette-section">
                        <div class="palette-header" data-section="rows">
                            <h3>Row Layouts</h3>
                            <span class="palette-toggle">▼</span>
                        </div>
                        <div class="palette-content">
                            <div class="row-layout-buttons">
                                <button type="button" class="row-layout-btn draggable" data-type="row" data-columns="1" draggable="true">
                                    <span class="dashicons dashicons-editor-justify"></span> Single Column Row
                                </button>
                                <button type="button" class="row-layout-btn draggable" data-type="row" data-columns="2" draggable="true">
                                    <span class="dashicons dashicons-columns"></span> Two Column Row
                                </button>
                                <button type="button" class="row-layout-btn draggable" data-type="row" data-columns="3" draggable="true">
                                    <span class="dashicons dashicons-grid-view"></span> Three Column Row
                                </button>
                                <button type="button" class="row-layout-btn draggable" data-type="row" data-columns="4" draggable="true">
                                    <span class="dashicons dashicons-screenoptions"></span> Four Column Row
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Field Elements Section -->
                    <div class="palette-section">
                        <div class="palette-header" data-section="fields">
                            <h3>Form Fields</h3>
                            <span class="palette-toggle">▼</span>
                        </div>
                        <div class="palette-content">
                            <div class="field-type-buttons">
                                <button type="button" class="field-type-btn draggable" data-type="text" draggable="true">
                                    <span class="dashicons dashicons-editor-textcolor"></span> Text
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="textarea" draggable="true">
                                    <span class="dashicons dashicons-editor-paragraph"></span> Textarea
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="select" draggable="true">
                                    <span class="dashicons dashicons-menu-alt"></span> Select
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="radio" draggable="true">
                                    <span class="dashicons dashicons-marker"></span> Radio
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="checkbox" draggable="true">
                                    <span class="dashicons dashicons-yes"></span> Checkbox
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="email" draggable="true">
                                    <span class="dashicons dashicons-email"></span> Email
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="number" draggable="true">
                                    <span class="dashicons dashicons-calculator"></span> Number
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="date" draggable="true">
                                    <span class="dashicons dashicons-calendar-alt"></span> Date
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="file" draggable="true">
                                    <span class="dashicons dashicons-upload"></span> File
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="hidden" draggable="true">
                                    <span class="dashicons dashicons-hidden"></span> Hidden
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="form-builder-canvas">
                    <div class="form-builder-header">
                        <div class="builder-actions">
                            <button type="button" class="button" id="preview-form">Preview</button>
                            <button type="button" class="button button-secondary" id="clear-form">Clear All</button>
                        </div>
                    </div>
                    
                    <div class="form-fields-area">
                        <div id="form-fields-list">
                            <div class="no-fields-message"><p>No fields added yet. Click on field types to add them.</p></div>
                        </div>
                    </div>
                </div>
                
                <!-- Field Edit Modal -->
                <div id="field-edit-modal" class="field-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>Edit Field</h4>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-field">
                                <label>Field Label</label>
                                <input type="text" id="field-label" class="widefat">
                            </div>
                            <div class="form-field">
                                <label>Field Name</label>
                                <input type="text" id="field-name" class="widefat">
                            </div>
                            <div class="form-field">
                                <label>Placeholder</label>
                                <input type="text" id="field-placeholder" class="widefat">
                            </div>
                            <div class="form-field">
                                <label>
                                    <input type="checkbox" id="field-required"> Required
                                </label>
                            </div>
                            <div class="form-field options-field" style="display: none;">
                                <label>Options (one per line)</label>
                                <textarea id="field-options" class="widefat" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button button-primary" id="save-field">Save Field</button>
                            <button type="button" class="button" id="cancel-field">Cancel</button>
                        </div>
                    </div>
                </div>
                
                <!-- Column Settings Modal -->
                <div id="column-settings-modal" class="field-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4>Column Settings</h4>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-field">
                                <label>Column Width</label>
                                <select id="column-width" class="widefat">
                                    <option value="1">Auto</option>
                                    <option value="0.16">16.67% (1/6)</option>
                                    <option value="0.25">25% (1/4)</option>
                                    <option value="0.33">33.33% (1/3)</option>
                                    <option value="0.5">50% (1/2)</option>
                                    <option value="0.66">66.67% (2/3)</option>
                                    <option value="0.75">75% (3/4)</option>
                                    <option value="0.83">83.33% (5/6)</option>
                                    <option value="2">2x Width</option>
                                    <option value="3">3x Width</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>CSS Classes</label>
                                <input type="text" id="column-classes" class="widefat" placeholder="custom-class another-class">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button button-primary" id="save-column-settings">Save Settings</button>
                            <button type="button" class="button" id="cancel-column-settings">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright-type-buttons">
                Copyright © LIFT Creations
            </div>
        `;

        container.html(builderHTML);
        
        // Load existing data if any
        if (existingData && existingData.length > 0) {
            formData = existingData;
            renderFields();
        }

        // Bind simple form builder events
        bindSimpleFormBuilderEvents();
        
        console.log('Simple form builder created successfully');
    }

    /**
     * Bind events for simple form builder
     */
    function bindSimpleFormBuilderEvents() {
        // Field type buttons
        $(document).on('click', '.field-type-btn', function() {
            const fieldType = $(this).data('type');
            addField(fieldType);
        });

        // Edit field
        $(document).on('click', '.edit-field-btn', function() {
            const index = $(this).data('index');
            editField(index);
        });

        // Delete field
        $(document).on('click', '.delete-field-btn', function() {
            const index = $(this).data('index');
            if (confirm('Are you sure you want to delete this field?')) {
                deleteField(index);
            }
        });

        // Move field up
        $(document).on('click', '.move-up-btn', function() {
            const index = $(this).data('index');
            moveField(index, 'up');
        });

        // Move field down
        $(document).on('click', '.move-down-btn', function() {
            const index = $(this).data('index');
            moveField(index, 'down');
        });

        // Modal events
        $(document).on('click', '.modal-close, #cancel-field', function() {
            $('#field-edit-modal').hide();
        });

        $(document).on('click', '#save-field', function() {
            saveFieldEdit();
        });

        // Column settings modal events
        $(document).on('click', '.modal-close, #cancel-column-settings', function() {
            $('#column-settings-modal').hide();
        });

        $(document).on('click', '#save-column-settings', function() {
            saveColumnSettings();
        });

        // Clear form
        $(document).on('click', '#clear-form', function() {
            if (confirm('Are you sure you want to clear all fields?')) {
                formData = [];
                renderFields();
            }
        });

        // Preview form
        $(document).on('click', '#preview-form', function() {
            previewForm();
        });

        // Initialize sortable for form fields
        initSortableFields();

        // Re-initialize sortable after field updates
        $(document).on('fields-updated', function() {
            initSortableFields();
        });
    }

    /**
     * Initialize sortable functionality for form fields
     */
    function initSortableFields() {
        // Check if jQuery UI sortable is available
        if (typeof $.fn.sortable !== 'undefined') {
            $('#form-fields-list').sortable({
                items: '.form-field-item',
                handle: '.field-drag-handle',
                placeholder: 'field-sort-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                helper: 'clone',
                connectWith: '.form-column', // Allow dragging between columns
                start: function(event, ui) {
                    ui.item.addClass('being-dragged');
                    ui.placeholder.addClass('field-sort-placeholder');
                },
                stop: function(event, ui) {
                    ui.item.removeClass('being-dragged');
                    updateFieldOrder();
                },
                update: function(event, ui) {
                    // Field order has changed
                    console.log('Field order updated');
                }
            });

            // Make columns sortable too
            $('.form-column').sortable({
                items: '.form-field-item',
                handle: '.field-drag-handle',
                placeholder: 'field-sort-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                helper: 'clone',
                connectWith: '#form-fields-list, .form-column',
                start: function(event, ui) {
                    ui.item.addClass('being-dragged');
                    ui.placeholder.addClass('field-sort-placeholder');
                },
                stop: function(event, ui) {
                    ui.item.removeClass('being-dragged');
                    $('.form-column').removeClass('drag-over');
                    updateFieldOrder();
                },
                over: function(event, ui) {
                    $(this).addClass('drag-over');
                },
                out: function(event, ui) {
                    $(this).removeClass('drag-over');
                }
            });
        } else {
            // Fallback: Use native HTML5 drag and drop
            initNativeDragDrop();
        }
    }

    /**
     * Initialize native HTML5 drag and drop as fallback
     */
    function initNativeDragDrop() {
        let draggedElement = null;

        // Drag start
        $(document).on('dragstart', '.form-field-item', function(e) {
            draggedElement = this;
            $(this).addClass('being-dragged');
            
            // Store the field index and source container
            const index = Array.from(this.parentNode.children).indexOf(this);
            const sourceContainer = $(this).closest('#form-fields-list, .form-column').attr('class') || 'form-fields-list';
            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                index: index,
                sourceContainer: sourceContainer,
                fieldId: $(this).data('field-id')
            }));
        });

        // Drag end
        $(document).on('dragend', '.form-field-item', function(e) {
            $(this).removeClass('being-dragged');
            $('.form-field-item, .form-column').removeClass('drag-over');
            draggedElement = null;
        });

        // Drag over for field items
        $(document).on('dragover', '.form-field-item', function(e) {
            e.preventDefault();
            if (this !== draggedElement) {
                $(this).addClass('drag-over');
            }
        });

        // Drag over for columns
        $(document).on('dragover', '.form-column', function(e) {
            e.preventDefault();
            if (!$(e.target).hasClass('form-field-item')) {
                $(this).addClass('drag-over');
            }
        });

        // Drag leave
        $(document).on('dragleave', '.form-field-item, .form-column', function(e) {
            // Only remove drag-over if we're really leaving the element
            if (!$.contains(this, e.relatedTarget)) {
                $(this).removeClass('drag-over');
            }
        });

        // Drop on field items
        $(document).on('drop', '.form-field-item', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            if (this !== draggedElement) {
                try {
                    const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
                    const dropIndex = Array.from(this.parentNode.children).indexOf(this);
                    
                    // Move the field in the data array
                    moveFieldToPosition(data.index, dropIndex);
                } catch (ex) {
                    console.error('Error parsing drag data:', ex);
                }
            }
        });

        // Drop on columns
        $(document).on('drop', '.form-column', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            // Only handle drops directly on the column, not on field items within
            if (!$(e.target).hasClass('form-field-item')) {
                try {
                    const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
                    
                    // Append field to this column
                    appendFieldToColumn(data.fieldId, $(this));
                } catch (ex) {
                    console.error('Error parsing drag data:', ex);
                }
            }
        });

        // Also handle dropping on the main container
        $(document).on('dragover', '#form-fields-list', function(e) {
            e.preventDefault();
        });

        $(document).on('drop', '#form-fields-list', function(e) {
            e.preventDefault();
            $('.form-field-item, .form-column').removeClass('drag-over');
        });
    }

    /**
     * Append field to specific column
     */
    function appendFieldToColumn(fieldId, $column) {
        const field = formData.find(f => (f.id || formData.indexOf(f)) == fieldId);
        if (!field) return;

        // Remove field from current position
        const fieldIndex = formData.indexOf(field);
        if (fieldIndex > -1) {
            // For now, just log the action - you can implement column-specific logic here
            console.log('Moving field to column:', field.label, $column);
            
            // Re-render to update positions
            renderFields();
        }
    }

    /**
     * Move field to a specific position
     */
    function moveFieldToPosition(fromIndex, toIndex) {
        if (fromIndex === toIndex) return;
        
        // Remove field from old position
        const field = formData.splice(fromIndex, 1)[0];
        
        // Insert at new position
        formData.splice(toIndex, 0, field);
        
        // Re-render fields
        renderFields();
        
        // Trigger update event
        $(document).trigger('fields-updated');
    }

    /**
     * Update field order based on DOM order
     */
    function updateFieldOrder() {
        const newOrder = [];
        $('#form-fields-list .form-field-item').each(function() {
            const fieldId = $(this).data('field-id');
            const field = formData.find(f => (f.id || formData.indexOf(f)) == fieldId);
            if (field) {
                newOrder.push(field);
            }
        });
        formData = newOrder;
        console.log('Field order updated:', formData.map(f => f.label));
    }

    /**
     * Add new field
     */
    function addField(type) {
        const field = {
            type: type,
            label: getDefaultLabel(type),
            name: 'field_' + (formData.length + 1),
            placeholder: '',
            required: false,
            options: type === 'select' || type === 'radio' ? ['Option 1', 'Option 2'] : []
        };

        formData.push(field);
        renderFields();
        
        // Auto-open edit modal for new field
        editField(formData.length - 1);
    }

    /**
     * Get default label for field type
     */
    function getDefaultLabel(type) {
        const labels = {
            text: 'Text Field',
            textarea: 'Text Area',
            select: 'Select Field',
            radio: 'Radio Group',
            checkbox: 'Checkbox',
            email: 'Email Field',
            number: 'Number Field',
            date: 'Date Field',
            file: 'File Upload',
            hidden: 'Hidden Field'
        };
        return labels[type] || 'Field';
    }

    /**
     * Edit field
     */
    function editField(index) {
        const field = formData[index];
        if (!field) return;

        // Populate modal
        $('#field-label').val(field.label);
        $('#field-name').val(field.name);
        $('#field-placeholder').val(field.placeholder);
        $('#field-required').prop('checked', field.required);
        
        if (field.options && field.options.length > 0) {
            $('#field-options').val(field.options.join('\n'));
            $('.options-field').show();
        } else {
            $('.options-field').hide();
        }

        // Store current editing index
        $('#field-edit-modal').data('editing-index', index).show();
    }

    /**
     * Save field edit
     */
    function saveFieldEdit() {
        const index = $('#field-edit-modal').data('editing-index');
        const field = formData[index];
        
        if (!field) return;

        // Update field data
        field.label = $('#field-label').val();
        field.name = $('#field-name').val();
        field.placeholder = $('#field-placeholder').val();
        field.required = $('#field-required').is(':checked');
        
        if ($('.options-field').is(':visible')) {
            const optionsText = $('#field-options').val();
            field.options = optionsText ? optionsText.split('\n').filter(opt => opt.trim()) : [];
        }

        // Re-render and close modal
        renderFields();
        $('#field-edit-modal').hide();
    }

    /**
     * Delete field
     */
    function deleteField(index) {
        formData.splice(index, 1);
        renderFields();
    }

    /**
     * Move field
     */
    function moveField(index, direction) {
        if (direction === 'up' && index > 0) {
            [formData[index], formData[index - 1]] = [formData[index - 1], formData[index]];
        } else if (direction === 'down' && index < formData.length - 1) {
            [formData[index], formData[index + 1]] = [formData[index + 1], formData[index]];
        }
        renderFields();
    }

    /**
     * Render fields
     */
    function renderFields() {
        const container = $('#form-fields-list');
        
        if (formData.length === 0) {
            container.html('<div class="no-fields-message"><p>No fields added yet. Click on field types to add them.</p></div>');
            return;
        }

        let html = '';
        formData.forEach((field, index) => {
            html += `
                <div class="form-field-item" draggable="true" data-field-id="${field.id || index}">
                    <div class="field-header">
                        <span class="field-drag-handle" title="Drag to move">
                            <span class="dashicons dashicons-move"></span>
                        </span>
                        <span class="field-type">${field.type.toUpperCase()}</span>
                        <span class="field-label">${field.label}</span>
                        <div class="field-actions">
                            <button type="button" class="button-link move-up-btn" data-index="${index}" title="Move Up">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                            <button type="button" class="button-link move-down-btn" data-index="${index}" title="Move Down">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <button type="button" class="button-link edit-field-btn" data-index="${index}" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button-link delete-field-btn" data-index="${index}" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="field-preview">
                        ${generateFieldPreview(field)}
                    </div>
                </div>
            `;
        });

        container.html(html);
        
        // Trigger event to reinitialize sortable
        $(document).trigger('fields-updated');
    }

    /**
     * Generate field preview HTML
     */
    function generateFieldPreview(field) {
        const required = field.required ? ' <span style="color: red;">*</span>' : '';
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'number':
                return `
                    <label>${field.label}${required}</label>
                    <input type="${field.type}" placeholder="${field.placeholder}" disabled>
                `;
            
            case 'textarea':
                return `
                    <label>${field.label}${required}</label>
                    <textarea placeholder="${field.placeholder}" disabled rows="3"></textarea>
                `;
            
            case 'select':
                const selectOptions = field.options.map(opt => `<option>${opt}</option>`).join('');
                return `
                    <label>${field.label}${required}</label>
                    <select disabled>
                        <option>Choose...</option>
                        ${selectOptions}
                    </select>
                `;
            
            case 'radio':
                const radioOptions = field.options.map((opt, i) => 
                    `<label><input type="radio" name="${field.name}" disabled> ${opt}</label>`
                ).join('<br>');
                return `
                    <label>${field.label}${required}</label><br>
                    ${radioOptions}
                `;
            
            case 'checkbox':
                return `
                    <label>
                        <input type="checkbox" disabled> ${field.label}${required}
                    </label>
                `;
            
            case 'date':
                return `
                    <label>${field.label}${required}</label>
                    <input type="date" disabled>
                `;
            
            case 'file':
                return `
                    <label>${field.label}${required}</label>
                    <input type="file" disabled>
                `;
            
            case 'hidden':
                return `
                    <div style="background: #f0f0f0; padding: 8px; border: 1px dashed #ccc;">
                        Hidden Field: ${field.name}
                    </div>
                `;
            
            default:
                return `<p>Unknown field type: ${field.type}</p>`;
        }
    }

    /**
     * Preview form
     */
    function previewForm() {
        if (formData.length === 0) {
            alert('No fields to preview. Add some fields first.');
            return;
        }

        let previewHTML = '<div class="form-preview"><h3>Form Preview</h3><form>';
        
        formData.forEach(field => {
            previewHTML += '<div class="form-group">' + generateFieldPreview(field).replace(/disabled/g, '') + '</div>';
        });
        
        previewHTML += '<button type="submit" class="button button-primary">Submit</button></form></div>';
        
        // Open preview in new window
        const previewWindow = window.open('', '_blank', 'width=600,height=400,scrollbars=yes');
        previewWindow.document.write(`
            <html>
                <head>
                    <title>Form Preview</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .form-group { margin-bottom: 15px; }
                        label { display: block; margin-bottom: 5px; font-weight: bold; }
                        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ccc; }
                        .button { padding: 10px 15px; background: #0073aa; color: white; border: none; cursor: pointer; }
                    </style>
                </head>
                <body>${previewHTML}</body>
            </html>
        `);
        previewWindow.document.close();
    }

    /**
     * Bind save events
     */
    function bindEvents() {
        // Initialize drag and drop
        initDragAndDrop();
        
        // Save form
        $(document).on('click', '#save-form', function(e) {
            e.preventDefault();
            saveForm();
        });

        // Auto-save every 30 seconds
        setInterval(function() {
            if (formData.length > 0) {
                saveForm(true); // Silent save
            }
        }, 30000);
    }

    /**
     * Save form data
     */
    function saveForm(silent = false) {
        if (!silent) {
            $('.lift-save-indicator').text('Saving...').show();
        }

        let saveData;
        
        if (formBuilderInstance && typeof formBuilderInstance.formData === 'function') {
            // FormBuilder library data
            saveData = formBuilderInstance.formData;
        } else {
            // Simple form builder data
            saveData = JSON.stringify(formData);
        }

        $.ajax({
            url: liftAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'lift_save_form',
                nonce: liftAjax.nonce,
                form_id: currentFormId,
                form_data: saveData,
                form_title: $('#form-title').val() || 'Untitled Form'
            },
            success: function(response) {
                if (response.success) {
                    if (!silent) {
                        $('.lift-save-indicator').text('Saved').removeClass('error');
                        setTimeout(() => $('.lift-save-indicator').fadeOut(), 2000);
                    }
                    
                    if (response.data.form_id && !currentFormId) {
                        currentFormId = response.data.form_id;
                        $('#form-id').val(currentFormId);
                    }
                } else {
                    if (!silent) {
                        $('.lift-save-indicator').text('Save failed').addClass('error');
                    }
                    console.error('Save failed:', response.data);
                }
            },
            error: function(xhr, status, error) {
                if (!silent) {
                    $('.lift-save-indicator').text('Save failed').addClass('error');
                }
                console.error('Save error:', error);
            }
        });
    }

    /**
     * Load form data
     */
    function loadFormData(formId) {
        $.ajax({
            url: liftAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'lift_load_form',
                nonce: liftAjax.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success && response.data.form_data) {
                    try {
                        const loadedData = typeof response.data.form_data === 'string' 
                            ? JSON.parse(response.data.form_data) 
                            : response.data.form_data;
                        
                        createFormBuilder(loadedData);
                        
                        // Update form title if available
                        if (response.data.form_title) {
                            $('#form-title').val(response.data.form_title);
                        }
                        
                        console.log('Form data loaded successfully');
                    } catch (error) {
                        console.error('Error parsing form data:', error);
                        createFormBuilder();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Load error:', error);
                createFormBuilder();
            }
        });
    }

    /**
     * Initialize Drag and Drop functionality
     */
    function initDragAndDrop() {
        let draggedElement = null;
        let draggedData = null;

        // Initialize palette collapsible sections
        initPaletteSections();

        // Make row layout buttons draggable
        $('.row-layout-btn.draggable').on('dragstart', function(e) {
            draggedElement = this;
            draggedData = {
                type: 'row',
                columns: parseInt($(this).data('columns')),
                source: 'palette'
            };
            
            $(this).addClass('drag-ghost');
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
            e.originalEvent.dataTransfer.setData('text/html', '');
        });

        $('.row-layout-btn.draggable').on('dragend', function(e) {
            $(this).removeClass('drag-ghost');
            draggedElement = null;
            draggedData = null;
        });

        // Make field type buttons draggable
        $('.field-type-btn.draggable').on('dragstart', function(e) {
            draggedElement = this;
            draggedData = {
                type: $(this).data('type'),
                source: 'palette'
            };
            
            $(this).addClass('drag-ghost');
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
            e.originalEvent.dataTransfer.setData('text/html', '');
        });

        $('.field-type-btn.draggable').on('dragend', function(e) {
            $(this).removeClass('drag-ghost');
            draggedElement = null;
            draggedData = null;
        });

        // Handle row dragging within canvas using event delegation
        $(document).off('dragstart.rowDrag').on('dragstart.rowDrag', '.form-row[draggable="true"]', function(e) {
            draggedElement = this;
            draggedData = {
                rowId: $(this).data('row-id'),
                source: 'canvas-row'
            };
            
            $(this).addClass('drag-ghost');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', '');
        });

        $(document).off('dragend.rowDrag').on('dragend.rowDrag', '.form-row[draggable="true"]', function(e) {
            $(this).removeClass('drag-ghost');
            clearRowDropIndicators();
            draggedElement = null;
            draggedData = null;
        });

        // Handle field dragging within canvas using event delegation
        $(document).off('dragstart.fieldDrag').on('dragstart.fieldDrag', '.form-field-item[draggable="true"]', function(e) {
            draggedElement = this;
            draggedData = {
                fieldId: $(this).data('field-id'),
                source: 'canvas-field'
            };
            
            $(this).addClass('drag-ghost');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', '');
        });

        $(document).off('dragend.fieldDrag').on('dragend.fieldDrag', '.form-field-item[draggable="true"]', function(e) {
            $(this).removeClass('drag-ghost');
            draggedElement = null;
            draggedData = null;
        });

        // Make canvas droppable for rows with better handling
        $('#form-fields-list').off('dragover.rowDrop').on('dragover.rowDrop', function(e) {
            if (!draggedData) return;
            
            if (draggedData.type === 'row' || draggedData.source === 'canvas-row') {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = draggedData.source === 'canvas-row' ? 'move' : 'copy';
                $(this).addClass('drag-over');
                
                // Show drop indicator between rows for canvas-row dragging
                if (draggedData.source === 'canvas-row') {
                    showRowDropIndicator(e.originalEvent.clientY);
                }
            }
        });

        $('#form-fields-list').off('dragleave.rowDrop').on('dragleave.rowDrop', function(e) {
            // Only remove drag-over if we're really leaving the container
            if (!$.contains(this, e.relatedTarget) && e.relatedTarget !== this) {
                $(this).removeClass('drag-over');
                clearRowDropIndicators();
            }
        });

        $('#form-fields-list').off('drop.rowDrop').on('drop.rowDrop', function(e) {
            if (!draggedData) return;
            
            e.preventDefault();
            $(this).removeClass('drag-over');
            clearRowDropIndicators();
            
            if (draggedData.type === 'row' && draggedData.source === 'palette') {
                // Add new row
                addNewRow(draggedData.columns);
            } else if (draggedData.source === 'canvas-row') {
                // Move existing row (reorder)
                moveRow(draggedData.rowId, e.originalEvent.clientY);
            }
        });

        // Make columns droppable for fields using event delegation
        $(document).off('dragover.columnDrop').on('dragover.columnDrop', '.form-column', function(e) {
            if (!draggedData) return;
            
            if (draggedData.source === 'palette' && draggedData.type !== 'row') {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                $(this).addClass('drag-over');
            } else if (draggedData.source === 'canvas-field') {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('drag-over');
            }
        });

        $(document).off('dragleave.columnDrop').on('dragleave.columnDrop', '.form-column', function(e) {
            // Only remove drag-over if we're really leaving the column
            if (!$.contains(this, e.relatedTarget) && e.relatedTarget !== this) {
                $(this).removeClass('drag-over');
            }
        });

        $(document).off('drop.columnDrop').on('drop.columnDrop', '.form-column', function(e) {
            if (!draggedData) return;
            
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            if (draggedData.source === 'palette' && draggedData.type !== 'row') {
                // Add new field to column
                addFieldToColumn(draggedData.type, $(this));
            } else if (draggedData.source === 'canvas-field') {
                // Move existing field to column
                moveFieldToColumn(draggedData.fieldId, $(this));
            }
        });
    }

    /**
     * Initialize palette collapsible sections
     */
    function initPaletteSections() {
        $('.palette-header').on('click', function() {
            const section = $(this).closest('.palette-section');
            section.toggleClass('collapsed');
        });
    }

    /**
     * Column Layout Management - REMOVED
     * Now using drag & drop row system
     */

    function addNewRow(columns) {
        const container = $('#form-fields-list');
        container.find('.no-fields-message').hide();
        
        const rowId = 'row-' + Date.now();
        let rowHTML = `<div class="form-row" data-row-id="${rowId}" draggable="true">`;
        
        // Add row drag handle
        rowHTML += `
            <div class="row-drag-handle" title="Drag to reorder row">
                ⋮⋮
            </div>
        `;
        
        // Add row controls
        rowHTML += `
            <div class="row-controls">
                <button type="button" class="row-control-btn" title="Add Column" onclick="addColumn('${rowId}')">
                    <span class="dashicons dashicons-plus-alt"></span> Col
                </button>
                <button type="button" class="row-control-btn" title="Remove Column" onclick="removeColumn('${rowId}')">
                    <span class="dashicons dashicons-minus"></span> Col
                </button>
                <button type="button" class="row-control-btn add-row" title="Add Row Below">
                    <span class="dashicons dashicons-plus-alt"></span> Row
                </button>
                <button type="button" class="row-control-btn delete delete-row" title="Delete Row">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;
        
        // Add columns with resize handles
        for (let i = 0; i < columns; i++) {
            const columnId = `${rowId}-col-${i}`;
            const flexBasis = Math.floor(100 / columns);
            
            rowHTML += `
                <div class="form-column" data-column-id="${columnId}" style="flex: 1; position: relative;">
                    <div class="column-header">
                        <span class="column-title">Column ${i + 1}</span>
                        <div class="column-actions">
                            <select class="column-width-selector" onchange="changeColumnWidth('${columnId}', this.value)">
                                <option value="1">Auto</option>
                                <option value="0.16">16.67% (1/6)</option>
                                <option value="0.25">25% (1/4)</option>
                                <option value="0.33">33.33% (1/3)</option>
                                <option value="0.5">50% (1/2)</option>
                                <option value="0.66">66.67% (2/3)</option>
                                <option value="0.75">75% (3/4)</option>
                                <option value="0.83">83.33% (5/6)</option>
                                <option value="2">2x Width</option>
                                <option value="3">3x Width</option>
                            </select>
                            <button type="button" class="column-action-btn" title="Column Settings" onclick="openColumnSettings('${columnId}')">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </button>
                        </div>
                    </div>
                    <div class="column-placeholder">
                    </div>
                    ${i < columns - 1 ? '<div class="column-resize-handle"></div>' : ''}
                </div>
            `;
        }
        
        rowHTML += '</div>';
        container.append(rowHTML);
        
        // Bind row events
        bindRowEvents();
        initColumnResize();
    }

    function bindRowEvents() {
        // Add row button
        $(document).off('click', '.add-row').on('click', '.add-row', function() {
            const currentRow = $(this).closest('.form-row');
            const columns = currentRow.find('.form-column').length;
            const newRowHTML = generateRowHTML(columns);
            currentRow.after(newRowHTML);
            
            // Re-bind events and initialize column resize for the new row
            bindRowEvents();
            initColumnResize();
        });

        // Delete row button
        $(document).off('click', '.delete-row').on('click', '.delete-row', function() {
            if (confirm('Are you sure you want to delete this row and all its fields?')) {
                $(this).closest('.form-row').remove();
                
                // Show no fields message if no rows left
                if ($('#form-fields-list .form-row').length === 0) {
                    $('#form-fields-list .no-fields-message').show();
                }
            }
        });
    }

    function generateRowHTML(columns) {
        const rowId = 'row-' + Date.now();
        let rowHTML = `<div class="form-row" data-row-id="${rowId}" draggable="true">`;
        
        // Add row drag handle
        rowHTML += `
            <div class="row-drag-handle" title="Drag to reorder row">
                ⋮⋮
            </div>
        `;
        
        // Add row controls with full functionality
        rowHTML += `
            <div class="row-controls">
                <button type="button" class="row-control-btn" title="Add Column" onclick="addColumn('${rowId}')">
                    <span class="dashicons dashicons-plus-alt"></span> Col
                </button>
                <button type="button" class="row-control-btn" title="Remove Column" onclick="removeColumn('${rowId}')">
                    <span class="dashicons dashicons-minus"></span> Col
                </button>
                <button type="button" class="row-control-btn add-row" title="Add Row Below">
                    <span class="dashicons dashicons-plus-alt"></span> Row
                </button>
                <button type="button" class="row-control-btn delete delete-row" title="Delete Row">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;
        
        // Add columns with full functionality including width selector and settings
        for (let i = 0; i < columns; i++) {
            const columnId = `${rowId}-col-${i}`;
            
            rowHTML += `
                <div class="form-column" data-column-id="${columnId}" style="flex: 1; position: relative;">
                    <div class="column-header">
                        <span class="column-title">Column ${i + 1}</span>
                        <div class="column-actions">
                            <select class="column-width-selector" onchange="changeColumnWidth('${columnId}', this.value)">
                                <option value="col-auto">col-auto</option>
                                <option value="col-1">col-1</option>
                                <option value="col-2">col-2</option>
                                <option value="col-3">col-3</option>
                                <option value="col-4">col-4</option>
                                <option value="col-5">col-5</option>
                                <option value="col-6">col-6</option>
                                <option value="col-7">col-7</option>
                                <option value="col-8">col-8</option>
                                <option value="col-9">col-9</option>
                                <option value="col-10">col-10</option>
                                <option value="col-11">col-11</option>
                                <option value="col-12">col-12</option>
                            </select>
                            <button type="button" class="column-action-btn" title="Column Settings" onclick="openColumnSettings('${columnId}')">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </button>
                        </div>
                    </div>
                    <div class="column-placeholder">
                    </div>
                    ${i < columns - 1 ? '<div class="column-resize-handle"></div>' : ''}
                </div>
            `;
        }
        
        return rowHTML + '</div>';
    }

    function getCurrentLayout() {
        return parseInt($('.column-btn.active').data('columns')) || 1;
    }

    function addFieldToColumn(fieldType, column) {
        const fieldData = createFieldData(fieldType);
        const fieldHTML = generateFieldHTML(fieldData);
        
        column.find('.column-placeholder').hide();
        column.addClass('has-fields');
        column.append(fieldHTML);
        
        // Make the field draggable
        column.find('.form-field-item').last().attr('draggable', 'true');
        
        formData.push(fieldData);
    }

    function moveFieldToColumn(fieldId, targetColumn) {
        const fieldElement = $(`.form-field-item[data-field-id="${fieldId}"]`);
        const sourceColumn = fieldElement.closest('.form-column');
        
        // Move field to target column
        targetColumn.find('.column-placeholder').hide();
        targetColumn.addClass('has-fields');
        targetColumn.append(fieldElement);
        
        // Check if source column is empty
        if (sourceColumn.length && sourceColumn.find('.form-field-item').length === 0) {
            sourceColumn.removeClass('has-fields');
            sourceColumn.find('.column-placeholder').show();
        }
    }

    function convertToSingleColumn() {
        const container = $('#form-fields-list');
        const allFields = container.find('.form-field-item').detach();
        
        // Remove all rows
        container.find('.form-row').remove();
        
        // Add fields back to single column
        allFields.each(function() {
            container.append(this);
        });
        
        if (allFields.length === 0) {
            container.find('.no-fields-message').show();
        }
    }

    /**
     * Column and Row Management Functions
     */
    function addColumn(rowId) {
        const row = $(`.form-row[data-row-id="${rowId}"]`);
        const currentColumns = row.find('.form-column').length;
        
        if (currentColumns >= 6) {
            alert('Maximum 6 columns allowed per row');
            return;
        }
        
        const columnId = `${rowId}-col-${currentColumns}`;
        const columnHTML = `
            <div class="form-column" data-column-id="${columnId}" style="flex: 1; position: relative;">
                <div class="column-header">
                    <span class="column-title">Column ${currentColumns + 1}</span>
                    <div class="column-actions">
                        <select class="column-width-selector" onchange="changeColumnWidth('${columnId}', this.value)">
                            <option value="1">Auto</option>
                            <option value="0.16">16.67% (1/6)</option>
                            <option value="0.25">25% (1/4)</option>
                            <option value="0.33">33.33% (1/3)</option>
                            <option value="0.5">50% (1/2)</option>
                            <option value="0.66">66.67% (2/3)</option>
                            <option value="0.75">75% (3/4)</option>
                            <option value="0.83">83.33% (5/6)</option>
                            <option value="2">2x Width</option>
                            <option value="3">3x Width</option>
                        </select>
                        <button type="button" class="column-action-btn" title="Column Settings" onclick="openColumnSettings('${columnId}')">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </button>
                    </div>
                </div>
                <div class="column-placeholder">
                </div>
                <div class="column-resize-handle"></div>
            </div>
        `;
        
        row.append(columnHTML);
        initColumnResize();
    }

    function removeColumn(rowId) {
        const row = $(`.form-row[data-row-id="${rowId}"]`);
        const columns = row.find('.form-column');
        
        if (columns.length <= 1) {
            alert('At least one column is required per row');
            return;
        }
        
        const lastColumn = columns.last();
        if (lastColumn.find('.form-field-item').length > 0) {
            if (!confirm('This column contains fields. Are you sure you want to remove it?')) {
                return;
            }
        }
        
        lastColumn.remove();
    }

    function changeColumnWidth(columnId, flexValue) {
        const column = $(`.form-column[data-column-id="${columnId}"]`);
        column.css('flex', flexValue);
    }

    function openColumnSettings(columnId) {
        const column = $(`.form-column[data-column-id="${columnId}"]`);
        if (!column.length) return;
        
        // Get current column data
        const currentFlex = column.css('flex') || '1';
        const currentClasses = column.attr('data-custom-classes') || '';
        
        // Populate modal fields
        $('#column-width').val(currentFlex);
        $('#column-classes').val(currentClasses);
        
        // Store current editing column ID
        $('#column-settings-modal').data('editing-column-id', columnId).show();
    }

    function saveColumnSettings() {
        const columnId = $('#column-settings-modal').data('editing-column-id');
        const column = $(`.form-column[data-column-id="${columnId}"]`);
        
        if (!column.length) return;
        
        // Get form values
        const width = $('#column-width').val();
        const classes = $('#column-classes').val();
        
        // Update column width
        column.css('flex', width);
        column.find('.column-width-selector').val(width);
        
        // Update custom classes
        if (classes) {
            column.attr('data-custom-classes', classes);
            column.addClass(classes);
        } else {
            column.removeAttr('data-custom-classes');
        }
        
        // Close modal
        $('#column-settings-modal').hide();
    }

    function initColumnResize() {
        $('.column-resize-handle').off('mousedown').on('mousedown', function(e) {
            e.preventDefault();
            
            const handle = $(this);
            const column = handle.parent();
            const nextColumn = column.next('.form-column');
            
            if (!nextColumn.length) return;
            
            handle.addClass('resizing');
            
            const startX = e.pageX;
            const startWidth = column.outerWidth();
            const startNextWidth = nextColumn.outerWidth();
            
            $(document).on('mousemove.columnResize', function(e) {
                const deltaX = e.pageX - startX;
                const newWidth = startWidth + deltaX;
                const newNextWidth = startNextWidth - deltaX;
                
                if (newWidth > 50 && newNextWidth > 50) {
                    const totalWidth = newWidth + newNextWidth;
                    const flex1 = newWidth / totalWidth * 2;
                    const flex2 = newNextWidth / totalWidth * 2;
                    
                    column.css('flex', flex1);
                    nextColumn.css('flex', flex2);
                }
            });
            
            $(document).on('mouseup.columnResize', function() {
                $(document).off('mousemove.columnResize mouseup.columnResize');
                handle.removeClass('resizing');
            });
        });
    }

    function moveRow(rowId, dropY) {
        const draggedRow = $(`.form-row[data-row-id="${rowId}"]`);
        const allRows = $('#form-fields-list .form-row').not(draggedRow);
        
        if (allRows.length === 0) {
            return; // No other rows to compare against
        }
        
        let targetRow = null;
        let insertAfter = false;
        let minDistance = Infinity;
        
        // Find the closest row to the drop position
        allRows.each(function() {
            const row = $(this);
            const rect = this.getBoundingClientRect();
            const rowMiddle = rect.top + rect.height / 2;
            const distance = Math.abs(dropY - rowMiddle);
            
            if (distance < minDistance) {
                minDistance = distance;
                targetRow = row;
                insertAfter = dropY > rowMiddle;
            }
        });
        
        // Move the row to the new position
        if (targetRow && targetRow.length) {
            if (insertAfter) {
                targetRow.after(draggedRow);
            } else {
                targetRow.before(draggedRow);
            }
        } else {
            // If no target found, append to the end
            $('#form-fields-list').append(draggedRow);
        }
        
        console.log('Row moved:', rowId, 'after:', insertAfter ? targetRow.data('row-id') : 'before ' + targetRow.data('row-id'));
    }

    // Make functions globally accessible for onclick handlers
    window.addColumn = addColumn;
    window.removeColumn = removeColumn;
    window.changeColumnWidth = changeColumnWidth;
    window.openColumnSettings = openColumnSettings;

    /**
     * Create field data object
     */
    function createFieldData(fieldType) {
        const fieldId = 'field-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        const fieldData = {
            id: fieldId,
            type: fieldType,
            label: fieldType.charAt(0).toUpperCase() + fieldType.slice(1) + ' Field',
            name: fieldType + '_' + Date.now(),
            required: false,
            placeholder: ''
        };

        // Add type-specific properties
        if (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') {
            fieldData.options = ['Option 1', 'Option 2', 'Option 3'];
        }

        return fieldData;
    }

    /**
     * Generate HTML for a single field
     */
    function generateFieldHTML(field) {
        return `
            <div class="form-field-item" draggable="true" data-field-id="${field.id}">
                <div class="field-header">
                    <span class="field-drag-handle" title="Drag to move">
                        <span class="dashicons dashicons-move"></span>
                    </span>
                    <span class="field-type">${field.type.toUpperCase()}</span>
                    <span class="field-label">${field.label}</span>
                    <div class="field-actions">
                        <button type="button" class="button-link edit-field-btn" data-field-id="${field.id}" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button-link delete-field-btn" data-field-id="${field.id}" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="field-preview">
                    ${generateFieldPreview(field)}
                </div>
            </div>
        `;
    }

    /**
     * Show drop indicator between rows
     */
    function showRowDropIndicator(dropY) {
        // Clear existing indicators
        clearRowDropIndicators();
        
        if (!draggedData || draggedData.source !== 'canvas-row') return;
        
        const draggedRow = $(`.form-row[data-row-id="${draggedData.rowId}"]`);
        const allRows = $('#form-fields-list .form-row').not(draggedRow);
        
        if (allRows.length === 0) return;
        
        let targetRow = null;
        let insertAfter = false;
        let minDistance = Infinity;
        
        // Find the closest row to the drop position
        allRows.each(function() {
            const row = $(this);
            const rect = this.getBoundingClientRect();
            const rowMiddle = rect.top + rect.height / 2;
            const distance = Math.abs(dropY - rowMiddle);
            
            if (distance < minDistance) {
                minDistance = distance;
                targetRow = row;
                insertAfter = dropY > rowMiddle;
            }
        });
        
        // Add visual indicator
        if (targetRow && targetRow.length) {
            if (insertAfter) {
                targetRow.addClass('drop-after');
            } else {
                targetRow.addClass('drop-before');
            }
        }
    }

    /**
     * Clear all row drop indicators
     */
    function clearRowDropIndicators() {
        $('.form-row').removeClass('drop-before drop-after');
    }

})(jQuery);
