/**
 * LIFT Forms Builder JavaScript
 */

(function($) {
    'use strict';
    
    let formBuilder = {
        fieldCounter: 0,
        selectedField: null,
        formData: {
            fields: [],
            settings: {}
        },
        
        init: function() {
            console.log('LIFT Forms Builder: Initializing...');
            this.bindEvents();
            this.initDragDrop();
            this.loadExistingForm();
            console.log('LIFT Forms Builder: Initialization complete');
        },
        
        bindEvents: function() {
            // Save form
            $('#save-form').on('click', this.saveForm.bind(this));
            
            // Preview form
            $('#preview-form').on('click', this.previewForm.bind(this));
            
            // Clear form
            $('#clear-form').on('click', this.clearForm.bind(this));
            
            // Close settings panel
            $('.panel-close').on('click', this.closeSettingsPanel.bind(this));
            
            // Close modals
            $('.lift-modal-close, .lift-modal-backdrop').on('click', this.closeModal.bind(this));
            
            // Form field changes
            $(document).on('input change', '.settings-form input, .settings-form textarea, .settings-form select', this.updateFieldSettings.bind(this));
            
            // Option field changes - specific handler for option inputs
            $(document).on('input change', '.option-item input', this.updateFieldOptions.bind(this));
            
            // Add/remove options
            $(document).on('click', '.add-option', this.addOption.bind(this));
            $(document).on('click', '.option-remove', this.removeOption.bind(this));
            
            // Field controls
            $(document).on('click', '.field-edit', this.editField.bind(this));
            $(document).on('click', '.field-delete', this.deleteField.bind(this));
            $(document).on('click', '.field-duplicate', this.duplicateField.bind(this));
            
            // Column field controls
            $(document).on('click', '.column-field .field-edit', this.editColumnField.bind(this));
            $(document).on('click', '.column-field .field-delete', this.deleteColumnField.bind(this));
            
            // Field selection
            $(document).on('click', '.canvas-field', this.selectField.bind(this));
            
            // Prevent field selection when clicking drag handle
            $(document).on('click', '.drag-handle', function(e) {
                e.stopPropagation();
            });
        },
        
        initDragDrop: function() {
            // Make field items draggable
            $('.field-item').draggable({
                helper: 'clone',
                cursor: 'grabbing',
                opacity: 0.8,
                revert: 'invalid',
                start: function(event, ui) {
                    ui.helper.css('z-index', 1000);
                    $('#form-canvas').addClass('drag-over');
                }
            });
            
            // Make canvas droppable
            $('#form-canvas').droppable({
                accept: '.field-item',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    const fieldType = ui.draggable.data('type');
                    formBuilder.addField(fieldType);
                    $('#form-canvas').removeClass('drag-over');
                },
                over: function() {
                    $(this).addClass('drag-over');
                },
                out: function() {
                    $(this).removeClass('drag-over');
                }
            });
            
            // Make canvas sortable with enhanced features
            $('#form-canvas').sortable({
                items: '.canvas-field',
                placeholder: 'ui-sortable-placeholder',
                handle: '.drag-handle',
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.8,
                distance: 5,
                start: function(event, ui) {
                    ui.placeholder.html('<div class="placeholder-content">Drop field here</div>');
                    $('#form-canvas').addClass('sorting-active');
                    ui.item.addClass('being-sorted');
                },
                stop: function(event, ui) {
                    $('#form-canvas').removeClass('sorting-active');
                    ui.item.removeClass('being-sorted');
                },
                update: function() {
                    formBuilder.updateFieldOrder();
                    formBuilder.showSortNotification();
                }
            });
            
            // Make columns droppable for field layouts
            this.initColumnDroppable();
        },
        
        initColumnDroppable: function() {
            // Initialize droppable for existing columns
            $(document).on('mouseenter', '.column', function() {
                if (!$(this).hasClass('ui-droppable')) {
                    $(this).droppable({
                        accept: '.field-item',
                        tolerance: 'pointer',
                        over: function() {
                            $(this).addClass('drag-over');
                        },
                        out: function() {
                            $(this).removeClass('drag-over');
                        },
                        drop: function(event, ui) {
                            const fieldType = ui.draggable.data('type');
                            const columnElement = $(this);
                            columnElement.removeClass('drag-over');
                            
                            // Add field to this column
                            formBuilder.addFieldToColumn(fieldType, columnElement);
                        }
                    });
                }
            });
        },
        
        addFieldToColumn: function(type, columnElement) {
            this.fieldCounter++;
            const fieldId = 'field_' + this.fieldCounter;
            const fieldName = type + '_' + this.fieldCounter;
            
            const fieldConfig = this.getFieldDefaults(type);
            fieldConfig.id = fieldId;
            fieldConfig.name = fieldName;
            fieldConfig.type = type;
            
            // Create compact field for column
            const fieldHtml = this.renderColumnField(fieldConfig);
            columnElement.find('.column-placeholder').hide();
            columnElement.append(fieldHtml);
            
            // Add to form data
            this.formData.fields.push(fieldConfig);
            this.updateFormData();
        },
        
        renderColumnField: function(field) {
            const preview = this.renderFieldPreview(field);
            return `
                <div class="column-field" data-field-id="${field.id}">
                    <div class="field-preview">${preview}</div>
                </div>
            `;
        },
        
        addField: function(type) {
            this.fieldCounter++;
            const fieldId = 'field_' + this.fieldCounter;
            const fieldName = type + '_' + this.fieldCounter;
            
            const fieldConfig = this.getFieldDefaults(type);
            fieldConfig.id = fieldId;
            fieldConfig.name = fieldName;
            fieldConfig.type = type;
            
            this.formData.fields.push(fieldConfig);
            
            const fieldHtml = this.renderCanvasField(fieldConfig);
            $('#form-canvas').append(fieldHtml);
            
            // Hide placeholder
            $('.canvas-placeholder').hide();
            $('#form-canvas').addClass('has-fields');
            
            // Select the new field
            this.selectFieldById(fieldId);
            
            // Update form data
            this.updateFormData();
            
            // Update canvas sortable
            $('#form-canvas').sortable('refresh');
            
            // Initialize column droppables if this is a column field
            if (type === 'column') {
                setTimeout(() => {
                    this.initColumnDroppables();
                }, 100);
            }
        },
        
        initColumnDroppables: function() {
            // Make columns droppable
            $('.column').droppable({
                accept: '.field-item',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    const fieldType = ui.draggable.data('type');
                    const columnIndex = $(this).data('column');
                    formBuilder.addFieldToColumn(fieldType, $(this));
                    $(this).removeClass('drag-over');
                },
                over: function() {
                    $(this).addClass('drag-over');
                },
                out: function() {
                    $(this).removeClass('drag-over');
                }
            });
        },
        
        addFieldToColumn: function(fieldType, columnElement) {
            this.fieldCounter++;
            const fieldId = 'field_' + this.fieldCounter;
            const fieldName = fieldType + '_' + this.fieldCounter;
            
            const fieldConfig = this.getFieldDefaults(fieldType);
            fieldConfig.id = fieldId;
            fieldConfig.name = fieldName;
            fieldConfig.type = fieldType;
            
            // Add to form data
            this.formData.fields.push(fieldConfig);
            
            // Create column field HTML
            const columnFieldHtml = this.renderColumnField(fieldConfig);
            
            // Add to column
            columnElement.append(columnFieldHtml);
            columnElement.addClass('has-fields');
            
            // Update form data
            this.updateFormData();
            
            console.log('Field added to column:', fieldType, fieldId);
        },
        
        editColumnField: function(e) {
            e.stopPropagation();
            const fieldElement = $(e.target).closest('.column-field');
            const fieldId = fieldElement.data('field-id');
            const field = this.getFieldById(fieldId);
            
            if (field) {
                this.selectedField = field;
                this.showFieldSettings(field);
            }
        },
        
        deleteColumnField: function(e) {
            e.stopPropagation();
            
            if (!confirm('Are you sure you want to delete this field?')) {
                return;
            }
            
            const fieldElement = $(e.target).closest('.column-field');
            const fieldId = fieldElement.data('field-id');
            
            // Remove from form data
            this.formData.fields = this.formData.fields.filter(field => field.id !== fieldId);
            
            // Remove from DOM
            fieldElement.remove();
            
            // Check if column is empty
            const column = fieldElement.closest('.column');
            if (column.find('.column-field').length === 0) {
                column.removeClass('has-fields');
            }
            
            // Close settings panel if this field was selected
            if (this.selectedField && this.selectedField.id === fieldId) {
                this.closeSettingsPanel();
            }
            
            // Update form data
            this.updateFormData();
        },
        
        renderColumnField: function(field) {
            const preview = this.renderFieldPreview(field);
            
            return `
                <div class="column-field" data-field-id="${field.id}">
                    <div class="field-controls">
                        <button type="button" class="field-control-btn field-edit" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="field-control-btn delete field-delete" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <div class="field-preview">
                        ${preview}
                    </div>
                </div>
            `;
        },
        
        getFieldDefaults: function(type) {
            const defaults = {
                text: {
                    label: 'Text Input',
                    placeholder: 'Enter text...',
                    required: false,
                    description: ''
                },
                textarea: {
                    label: 'Textarea',
                    placeholder: 'Enter text...',
                    required: false,
                    description: '',
                    rows: 4
                },
                email: {
                    label: 'Email Address',
                    placeholder: 'Enter email...',
                    required: false,
                    description: ''
                },
                number: {
                    label: 'Number',
                    placeholder: 'Enter number...',
                    required: false,
                    description: '',
                    min: '',
                    max: '',
                    step: ''
                },
                date: {
                    label: 'Date',
                    required: false,
                    description: ''
                },
                file: {
                    label: 'File Upload',
                    required: false,
                    description: '',
                    accept: '',
                    multiple: false
                },
                select: {
                    label: 'Dropdown',
                    required: false,
                    description: '',
                    options: [
                        { label: 'Option 1', value: 'option1' },
                        { label: 'Option 2', value: 'option2' }
                    ]
                },
                radio: {
                    label: 'Radio Buttons',
                    required: false,
                    description: '',
                    options: [
                        { label: 'Option 1', value: 'option1' },
                        { label: 'Option 2', value: 'option2' }
                    ]
                },
                checkbox: {
                    label: 'Checkboxes',
                    required: false,
                    description: '',
                    options: [
                        { label: 'Option 1', value: 'option1' },
                        { label: 'Option 2', value: 'option2' }
                    ]
                },
                section: {
                    title: 'Section Title',
                    description: 'Section description'
                },
                column: {
                    columns: 2,
                    fields: [[], []]
                },
                html: {
                    content: '<p>HTML content goes here</p>'
                }
            };
            
            return defaults[type] || {};
        },
        
        renderCanvasField: function(field) {
            const fieldHtml = `
                <div class="canvas-field" data-field-id="${field.id}" data-field-type="${field.type}">
                    <div class="field-header">
                        <div class="drag-handle" title="Drag to reorder">
                            <span class="dashicons dashicons-menu"></span>
                        </div>
                        <div class="field-info">
                            <span class="field-type-badge">${field.type}</span>
                            <span class="field-name">${field.label || field.name}</span>
                        </div>
                        <div class="field-controls">
                            <button type="button" class="field-control-btn field-edit" title="Edit Field">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="field-control-btn field-duplicate" title="Duplicate Field">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                            <button type="button" class="field-control-btn field-delete delete" title="Delete Field">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="field-preview">
                        ${this.renderFieldPreview(field)}
                    </div>
                </div>
            `;
            
            return fieldHtml;
        },
        
        renderFieldPreview: function(field) {
            const required = field.required ? '<span class="required">*</span>' : '';
            const description = field.description ? `<small class="field-description">${field.description}</small>` : '';
            
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                    return `
                        <label>${field.label}${required}</label>
                        <input type="${field.type}" placeholder="${field.placeholder || ''}" disabled>
                        ${description}
                    `;
                    
                case 'date':
                    return `
                        <label>${field.label}${required}</label>
                        <input type="date" disabled>
                        ${description}
                    `;
                    
                case 'textarea':
                    return `
                        <label>${field.label}${required}</label>
                        <textarea placeholder="${field.placeholder || ''}" rows="${field.rows || 4}" disabled></textarea>
                        ${description}
                    `;
                    
                case 'file':
                    return `
                        <label>${field.label}${required}</label>
                        <input type="file" ${field.multiple ? 'multiple' : ''} disabled>
                        ${description}
                    `;
                    
                case 'select':
                    let selectOptions = '<option>Please select...</option>';
                    if (field.options) {
                        field.options.forEach(option => {
                            selectOptions += `<option value="${option.value}">${option.label}</option>`;
                        });
                    }
                    return `
                        <label>${field.label}${required}</label>
                        <select disabled>${selectOptions}</select>
                        ${description}
                    `;
                    
                case 'radio':
                    let radioOptions = '';
                    if (field.options) {
                        field.options.forEach((option, index) => {
                            radioOptions += `
                                <label class="radio-label">
                                    <input type="radio" name="${field.name}" value="${option.value}" disabled>
                                    <span>${option.label}</span>
                                </label>
                            `;
                        });
                    }
                    return `
                        <fieldset>
                            <legend>${field.label}${required}</legend>
                            ${radioOptions}
                        </fieldset>
                        ${description}
                    `;
                    
                case 'checkbox':
                    let checkboxOptions = '';
                    if (field.options) {
                        field.options.forEach((option, index) => {
                            checkboxOptions += `
                                <label class="checkbox-label">
                                    <input type="checkbox" name="${field.name}[]" value="${option.value}" disabled>
                                    <span>${option.label}</span>
                                </label>
                            `;
                        });
                    }
                    return `
                        <fieldset>
                            <legend>${field.label}${required}</legend>
                            ${checkboxOptions}
                        </fieldset>
                        ${description}
                    `;
                    
                case 'section':
                    return `
                        <div class="section-title">${field.title || 'Section Title'}</div>
                        <p>${field.description || 'Section description'}</p>
                    `;
                    
                case 'column':
                    const columnCount = field.columns || 2;
                    let columnsHtml = '';
                    for (let i = 0; i < columnCount; i++) {
                        columnsHtml += `
                            <div class="column" data-column="${i}">
                                <div class="column-placeholder">Drop fields here</div>
                            </div>
                        `;
                    }
                    return `<div class="columns-container">${columnsHtml}</div>`;
                    
                case 'html':
                    return field.content || '<p>HTML content</p>';
                    
                default:
                    return `<p>Unknown field type: ${field.type}</p>`;
            }
        },
        
        selectField: function(e) {
            e.stopPropagation();
            const fieldElement = $(e.currentTarget);
            this.selectFieldElement(fieldElement);
        },
        
        selectFieldById: function(fieldId) {
            const fieldElement = $(`.canvas-field[data-field-id="${fieldId}"]`);
            this.selectFieldElement(fieldElement);
        },
        
        selectFieldElement: function(fieldElement) {
            // Remove previous selection
            $('.canvas-field').removeClass('selected');
            
            // Select current field
            fieldElement.addClass('selected');
            
            const fieldId = fieldElement.data('field-id');
            this.selectedField = this.getFieldById(fieldId);
            
            if (this.selectedField) {
                this.showFieldSettings(this.selectedField);
            }
        },
        
        showFieldSettings: function(field) {
            const settingsHtml = this.renderFieldSettings(field);
            $('#field-settings-content').html(settingsHtml);
            $('#field-settings-panel').addClass('active');
        },
        
        renderFieldSettings: function(field) {
            let settingsHtml = `
                <div class="settings-form">
                    <div class="settings-group">
                        <label>Field Label</label>
                        <input type="text" name="label" value="${field.label || ''}" placeholder="Enter field label">
                    </div>
            `;
            
            // Common settings for most fields
            if (['text', 'textarea', 'email', 'number'].includes(field.type)) {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Placeholder Text</label>
                        <input type="text" name="placeholder" value="${field.placeholder || ''}" placeholder="Enter placeholder text">
                    </div>
                `;
            }
            
            if (field.type === 'textarea') {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Rows</label>
                        <input type="number" name="rows" value="${field.rows || 4}" min="2" max="20">
                    </div>
                `;
            }
            
            if (field.type === 'number') {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Minimum Value</label>
                        <input type="number" name="min" value="${field.min || ''}" placeholder="Minimum value">
                    </div>
                    <div class="settings-group">
                        <label>Maximum Value</label>
                        <input type="number" name="max" value="${field.max || ''}" placeholder="Maximum value">
                    </div>
                    <div class="settings-group">
                        <label>Step</label>
                        <input type="number" name="step" value="${field.step || ''}" placeholder="Step value">
                    </div>
                `;
            }
            
            if (field.type === 'file') {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Accepted File Types</label>
                        <input type="text" name="accept" value="${field.accept || ''}" placeholder="e.g., .pdf,.doc,.jpg">
                    </div>
                    <div class="settings-group checkbox-setting">
                        <input type="checkbox" name="multiple" ${field.multiple ? 'checked' : ''}>
                        <label>Allow Multiple Files</label>
                    </div>
                `;
            }
            
            // Options for select, radio, checkbox
            if (['select', 'radio', 'checkbox'].includes(field.type)) {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Options</label>
                        <div class="options-list">
                `;
                
                if (field.options && field.options.length > 0) {
                    field.options.forEach((option, index) => {
                        settingsHtml += `
                            <div class="option-item">
                                <input type="text" name="option_label_${index}" value="${option.label || ''}" placeholder="Option label">
                                <input type="text" name="option_value_${index}" value="${option.value || option.label || ''}" placeholder="Option value">
                                <button type="button" class="option-remove">Remove</button>
                            </div>
                        `;
                    });
                } else {
                    // Add default options if none exist
                    settingsHtml += `
                        <div class="option-item">
                            <input type="text" name="option_label_0" value="Option 1" placeholder="Option label">
                            <input type="text" name="option_value_0" value="option1" placeholder="Option value">
                            <button type="button" class="option-remove">Remove</button>
                        </div>
                        <div class="option-item">
                            <input type="text" name="option_label_1" value="Option 2" placeholder="Option label">
                            <input type="text" name="option_value_1" value="option2" placeholder="Option value">
                            <button type="button" class="option-remove">Remove</button>
                        </div>
                    `;
                }
                
                settingsHtml += `
                        </div>
                        <button type="button" class="add-option">Add Option</button>
                    </div>
                `;
            }
            
            // Section settings
            if (field.type === 'section') {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Section Title</label>
                        <input type="text" name="title" value="${field.title || ''}" placeholder="Enter section title">
                    </div>
                `;
            }
            
            // Column settings
            if (field.type === 'column') {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Number of Columns</label>
                        <select name="columns">
                            <option value="2" ${field.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${field.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${field.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                `;
            }
            
            // HTML content
            if (field.type === 'html') {
                settingsHtml += `
                    <div class="settings-group">
                        <label>HTML Content</label>
                        <textarea name="content" rows="8" placeholder="Enter HTML content">${field.content || ''}</textarea>
                    </div>
                `;
            }
            
            // Description for most fields
            if (!['section', 'column', 'html'].includes(field.type)) {
                settingsHtml += `
                    <div class="settings-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Enter field description">${field.description || ''}</textarea>
                    </div>
                `;
                
                // Required checkbox
                settingsHtml += `
                    <div class="settings-group checkbox-setting">
                        <input type="checkbox" name="required" ${field.required ? 'checked' : ''}>
                        <label>Required Field</label>
                    </div>
                `;
            }
            
            settingsHtml += '</div>';
            
            return settingsHtml;
        },
        
        updateFieldSettings: function(e) {
            if (!this.selectedField) return;
            
            const fieldElement = $(`.canvas-field[data-field-id="${this.selectedField.id}"]`);
            const settingName = $(e.target).attr('name');
            const settingValue = $(e.target).is(':checkbox') ? $(e.target).is(':checked') : $(e.target).val();
            
            // Update field data
            this.selectedField[settingName] = settingValue;
            
            // Handle options update
            if (settingName.startsWith('option_')) {
                this.updateFieldOptions();
            }
            
            // Update field preview
            const previewHtml = this.renderFieldPreview(this.selectedField);
            fieldElement.find('.field-preview').html(previewHtml);
            
            // If this is a column field and columns setting changed, reinitialize droppables
            if (this.selectedField.type === 'column' && settingName === 'columns') {
                setTimeout(() => {
                    this.initColumnDroppables();
                }, 100);
            }
            
            // Update form data
            this.updateFormData();
        },
        
        updateFieldOptions: function() {
            if (!this.selectedField || !['select', 'radio', 'checkbox'].includes(this.selectedField.type)) {
                return;
            }
            
            const options = [];
            const optionsList = $('.field-settings-panel.active .options-list');
            
            optionsList.find('.option-item').each(function() {
                const label = $(this).find('input[name^="option_label_"]').val();
                const value = $(this).find('input[name^="option_value_"]').val() || label;
                
                if (label && label.trim() !== '') {
                    options.push({ 
                        label: label.trim(), 
                        value: value.trim() || label.trim() 
                    });
                }
            });
            
            this.selectedField.options = options;
            
            // Update the field preview in the canvas
            this.updateFieldPreview();
        },
        
        addOption: function(e) {
            e.preventDefault();
            const optionsList = $(e.target).siblings('.options-list');
            if (optionsList.length === 0) {
                // Try parent container approach
                const optionsList2 = $(e.target).closest('.settings-group').find('.options-list');
                if (optionsList2.length > 0) {
                    this.addOptionToList(optionsList2);
                }
            } else {
                this.addOptionToList(optionsList);
            }
        },
        
        addOptionToList: function(optionsList) {
            const index = optionsList.find('.option-item').length;
            console.log('Adding option at index:', index);
            
            const optionHtml = `
                <div class="option-item">
                    <input type="text" name="option_label_${index}" value="" placeholder="Option label">
                    <input type="text" name="option_value_${index}" value="" placeholder="Option value">
                    <button type="button" class="option-remove">Remove</button>
                </div>
            `;
            
            optionsList.append(optionHtml);
            console.log('Option added. Total options:', optionsList.find('.option-item').length);
            
            // Update field options after adding
            this.updateFieldOptions();
        },
        
        removeOption: function(e) {
            e.preventDefault();
            $(e.target).closest('.option-item').remove();
            this.updateFieldOptions();
        },
        
        updateFieldPreview: function() {
            if (!this.selectedField) {
                return;
            }
            
            const fieldElement = $(`.canvas-field[data-field-id="${this.selectedField.id}"]`);
            if (fieldElement.length > 0) {
                const newPreview = this.renderFieldPreview(this.selectedField);
                fieldElement.find('.field-preview').html(newPreview);
            }
        },
        
        editField: function(e) {
            e.stopPropagation();
            const fieldElement = $(e.target).closest('.canvas-field');
            this.selectFieldElement(fieldElement);
        },
        
        deleteField: function(e) {
            e.stopPropagation();
            
            if (!confirm('Are you sure you want to delete this field?')) {
                return;
            }
            
            const fieldElement = $(e.target).closest('.canvas-field');
            const fieldId = fieldElement.data('field-id');
            
            // Remove from form data
            this.formData.fields = this.formData.fields.filter(field => field.id !== fieldId);
            
            // Remove from DOM
            fieldElement.remove();
            
            // Close settings panel if this field was selected
            if (this.selectedField && this.selectedField.id === fieldId) {
                this.closeSettingsPanel();
            }
            
            // Show placeholder if no fields left
            if (this.formData.fields.length === 0) {
                $('.canvas-placeholder').show();
                $('#form-canvas').removeClass('has-fields');
            }
            
            // Update form data
            this.updateFormData();
        },
        
        duplicateField: function(e) {
            e.stopPropagation();
            const fieldElement = $(e.target).closest('.canvas-field');
            const fieldId = fieldElement.data('field-id');
            const originalField = this.getFieldById(fieldId);
            
            if (originalField) {
                // Create duplicate with new ID
                this.fieldCounter++;
                const duplicateField = JSON.parse(JSON.stringify(originalField));
                duplicateField.id = 'field_' + this.fieldCounter;
                duplicateField.name = originalField.type + '_' + this.fieldCounter;
                duplicateField.label = originalField.label + ' (Copy)';
                
                this.formData.fields.push(duplicateField);
                
                const fieldHtml = this.renderCanvasField(duplicateField);
                fieldElement.after(fieldHtml);
                
                // Select the duplicated field
                this.selectFieldById(duplicateField.id);
            }
        },
        
        updateFieldOrder: function() {
            const newOrder = [];
            $('#form-canvas .canvas-field').each(function(index) {
                const fieldId = $(this).data('field-id');
                const field = formBuilder.getFieldById(fieldId);
                if (field) {
                    field.order = index + 1; // Set the new order
                    newOrder.push(field);
                }
            });
            
            this.formData.fields = newOrder;
            console.log('Field order updated:', newOrder.map(f => `${f.name} (${f.order})`));
        },
        
        showSortNotification: function() {
            // Show a subtle notification that fields were reordered
            const notification = $('<div class="sort-notification">Fields reordered</div>');
            $('body').append(notification);
            
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 2000);
        },
        
        getFieldById: function(fieldId) {
            return this.formData.fields.find(field => field.id === fieldId);
        },
        
        closeSettingsPanel: function() {
            $('#field-settings-panel').removeClass('active');
            $('.canvas-field').removeClass('selected');
            this.selectedField = null;
        },
        
        cleanFieldData: function(field) {
            const cleaned = {};
            
            // Copy only serializable properties
            const allowedProps = [
                'id', 'name', 'type', 'label', 'placeholder', 'required', 
                'description', 'options', 'min', 'max', 'step', 'rows', 
                'multiple', 'accept', 'content', 'order', 'validation'
            ];
            
            allowedProps.forEach(prop => {
                if (field.hasOwnProperty(prop) && field[prop] !== undefined) {
                    // Handle arrays (like options)
                    if (Array.isArray(field[prop])) {
                        cleaned[prop] = field[prop].filter(item => item !== undefined && item !== null);
                    } 
                    // Handle objects
                    else if (typeof field[prop] === 'object' && field[prop] !== null) {
                        try {
                            // Test if object is serializable
                            JSON.stringify(field[prop]);
                            cleaned[prop] = field[prop];
                        } catch (e) {
                            // Skip unserializable properties
                        }
                    } 
                    // Handle primitives
                    else {
                        cleaned[prop] = field[prop];
                    }
                }
            });
            
            return cleaned;
        },
        
        // Clean all form data for serialization
        cleanFormData: function() {
            return {
                fields: this.formData.fields.map(field => this.cleanFieldData(field)),
                settings: this.formData.settings || {}
            };
        },
        
        updateFormData: function() {
            // Get current field order from canvas and maintain field data
            const orderedFields = [];
            
            $('#form-canvas .canvas-field').each((index, element) => {
                const fieldId = $(element).data('field-id');
                const existingField = this.formData.fields.find(f => f.id === fieldId);
                
                if (existingField) {
                    // Update order and keep all field data
                    existingField.order = index;
                    orderedFields.push(existingField);
                }
            });
            
            // Update formData with ordered fields
            this.formData.fields = orderedFields;
        },
        
        findFieldById: function(fieldId) {
            return this.formData.fields.find(f => f.id === fieldId);
        },
        
        saveForm: function() {
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
            
            // Clean form data before serialization
            const cleanData = this.cleanFormData();
            
            // Test JSON.stringify
            let fieldsJson;
            try {
                fieldsJson = JSON.stringify(cleanData.fields);
            } catch (e) {
                alert('Error preparing form data: ' + e.message);
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
                    fields: fieldsJson,
                    settings: JSON.stringify(cleanData.settings)
                },
                success: function(response) {
                    if (response.success) {
                        if (!formId) {
                            // New form - update form ID
                            $('#form-id').val(response.data.form_id);
                            
                            // Update URL to include ID
                            const newUrl = window.location.href + '&id=' + response.data.form_id;
                            window.history.replaceState({}, '', newUrl);
                        }
                        
                        alert(liftForms.strings.saved);
                    } else {
                        alert(response.data || liftForms.strings.error);
                    }
                },
                error: function() {
                    alert(liftForms.strings.error);
                },
                complete: function() {
                    saveBtn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        previewForm: function() {
            const formName = $('#form-name').val().trim();
            const formDescription = $('#form-description').val().trim();
            
            if (this.formData.fields.length === 0) {
                alert('Please add some fields to preview the form');
                return;
            }
            
            let previewHtml = `
                <div class="lift-form-container">
                    <div class="lift-form-header">
                        <h2>${formName || 'Untitled Form'}</h2>
                        ${formDescription ? `<p>${formDescription}</p>` : ''}
                    </div>
                    <form class="lift-form">
            `;
            
            this.formData.fields.forEach(field => {
                previewHtml += this.renderFieldForPreview(field);
            });
            
            previewHtml += `
                        <div class="lift-form-submit">
                            <button type="submit" class="lift-form-submit-btn">Submit Form</button>
                        </div>
                    </form>
                </div>
            `;
            
            $('#form-preview-content').html(previewHtml);
            this.showModal('#form-preview-modal');
        },
        
        renderFieldForPreview: function(field) {
            // This renders actual form fields for preview
            const required = field.required ? 'required' : '';
            const requiredAsterisk = field.required ? ' <span class="required">*</span>' : '';
            const description = field.description ? `<small class="field-description">${field.description}</small>` : '';
            
            let html = `<div class="lift-form-field lift-field-${field.type}">`;
            
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                case 'date':
                    html += `
                        <label for="${field.id}">${field.label}${requiredAsterisk}</label>
                        <input type="${field.type}" id="${field.id}" name="${field.name}" placeholder="${field.placeholder || ''}" ${required}>
                        ${description}
                    `;
                    break;
                    
                case 'textarea':
                    html += `
                        <label for="${field.id}">${field.label}${requiredAsterisk}</label>
                        <textarea id="${field.id}" name="${field.name}" placeholder="${field.placeholder || ''}" rows="${field.rows || 4}" ${required}></textarea>
                        ${description}
                    `;
                    break;
                    
                case 'file':
                    html += `
                        <label for="${field.id}">${field.label}${requiredAsterisk}</label>
                        <input type="file" id="${field.id}" name="${field.name}" ${field.multiple ? 'multiple' : ''} ${required}>
                        ${description}
                    `;
                    break;
                    
                case 'select':
                    html += `<label for="${field.id}">${field.label}${requiredAsterisk}</label>`;
                    html += `<select id="${field.id}" name="${field.name}" ${required}>`;
                    html += '<option value="">Please select...</option>';
                    if (field.options) {
                        field.options.forEach(option => {
                            html += `<option value="${option.value}">${option.label}</option>`;
                        });
                    }
                    html += `</select>${description}`;
                    break;
                    
                case 'radio':
                    html += `<fieldset><legend>${field.label}${requiredAsterisk}</legend>`;
                    if (field.options) {
                        field.options.forEach(option => {
                            html += `
                                <label class="radio-label">
                                    <input type="radio" name="${field.name}" value="${option.value}" ${required}>
                                    <span>${option.label}</span>
                                </label>
                            `;
                        });
                    }
                    html += `</fieldset>${description}`;
                    break;
                    
                case 'checkbox':
                    html += `<fieldset><legend>${field.label}${requiredAsterisk}</legend>`;
                    if (field.options) {
                        field.options.forEach(option => {
                            html += `
                                <label class="checkbox-label">
                                    <input type="checkbox" name="${field.name}[]" value="${option.value}">
                                    <span>${option.label}</span>
                                </label>
                            `;
                        });
                    }
                    html += `</fieldset>${description}`;
                    break;
                    
                case 'section':
                    html += `
                        <div class="section-title">${field.title || 'Section Title'}</div>
                        <p>${field.description || ''}</p>
                    `;
                    break;
                    
                case 'html':
                    html += field.content || '';
                    break;
            }
            
            html += '</div>';
            return html;
        },
        
        clearForm: function() {
            if (!confirm('Are you sure you want to clear all fields? This action cannot be undone.')) {
                return;
            }
            
            this.formData.fields = [];
            $('#form-canvas').empty();
            $('#form-canvas').html('<div class="canvas-placeholder"><p>Drag fields from the left panel to build your form</p></div>');
            $('.canvas-placeholder').show();
            $('#form-canvas').removeClass('has-fields');
            this.closeSettingsPanel();
        },
        
        loadExistingForm: function() {
            // Load existing form data if editing
            const formId = $('#form-id').val();
            if (!formId) return;
            
            // Get form data via AJAX
            $.ajax({
                url: liftForms.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lift_forms_get',
                    nonce: liftForms.nonce,
                    form_id: formId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        formBuilder.loadFormData(response.data);
                    }
                },
                error: function() {
                    console.warn('Could not load existing form data');
                }
            });
        },
        
        safeJsonParse: function(jsonString) {
            if (!jsonString) return null;
            
            try {
                // If already an object/array, return as is
                if (typeof jsonString !== 'string') {
                    return jsonString;
                }
                
                // Clean the string
                let clean = jsonString.trim();
                
                // Remove BOM and other problematic characters
                clean = clean.replace(/^\uFEFF/, ''); // Remove BOM
                clean = clean.replace(/[\u0000-\u001F\u007F-\u009F]/g, ''); // Remove control chars
                
                // Check if it starts and ends with proper JSON characters
                if (!clean.startsWith('{') && !clean.startsWith('[')) {
                    return null;
                }
                
                return JSON.parse(clean);
            } catch (e) {
                return null;
            }
        },
        
        loadFormData: function(formData) {
            // Clear existing canvas
            $('#form-canvas').empty();
            this.formData.fields = [];
            this.fieldCounter = 0;
            
            // Parse form fields if they exist
            if (formData.form_fields) {
                // Use safe JSON parser
                const fields = this.safeJsonParse(formData.form_fields);
                
                if (fields && Array.isArray(fields) && fields.length > 0) {
                    // Load each field
                    fields.forEach(field => {
                        this.loadField(field);
                    });
                    
                    // Hide placeholder and show fields
                    $('.canvas-placeholder').hide();
                    $('#form-canvas').addClass('has-fields');
                } else {
                    this.showEmptyCanvas();
                }
            } else {
                this.showEmptyCanvas();
            }
            
            // Load form settings if they exist
            if (formData.settings) {
                const settings = this.safeJsonParse(formData.settings);
                this.formData.settings = settings || {};
            }
        },
        
        loadField: function(fieldData) {
            // Ensure field has required properties
            if (!fieldData.id) {
                this.fieldCounter++;
                fieldData.id = 'field_' + this.fieldCounter;
            }
            
            if (!fieldData.name) {
                fieldData.name = fieldData.type + '_' + this.fieldCounter;
            }
            
            // Update field counter
            const fieldNum = parseInt(fieldData.id.replace('field_', ''));
            if (fieldNum > this.fieldCounter) {
                this.fieldCounter = fieldNum;
            }
            
            // Add to form data
            this.formData.fields.push(fieldData);
            
            // Render field
            const fieldHtml = this.renderCanvasField(fieldData);
            $('#form-canvas').append(fieldHtml);
        },
        
        showEmptyCanvas: function() {
            $('#form-canvas').html(`
                <div class="canvas-placeholder">
                    <p>Drag fields from the left panel to build your form</p>
                </div>
            `);
            $('.canvas-placeholder').show();
            $('#form-canvas').removeClass('has-fields');
        },
        
        showModal: function(modalSelector) {
            $(modalSelector).show();
            $(modalSelector.replace('modal', 'modal-backdrop')).show();
        },
        
        closeModal: function(e) {
            if ($(e.target).hasClass('lift-modal-close') || $(e.target).hasClass('lift-modal-backdrop')) {
                $('.lift-modal').hide();
                $('.lift-modal-backdrop').hide();
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.lift-form-builder').length > 0) {
            formBuilder.init();
        }
    });
    
    // Make formBuilder globally accessible
    window.liftFormBuilder = formBuilder;
    
})(jQuery);
