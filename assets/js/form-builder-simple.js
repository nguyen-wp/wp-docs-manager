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
            <div class="simple-form-builder">
                <div class="form-builder-header">
                    <h3>Simple Form Builder</h3>
                    <div class="builder-actions">
                        <button type="button" class="button button-primary" id="add-field">Add Field</button>
                        <button type="button" class="button" id="preview-form">Preview</button>
                        <button type="button" class="button button-secondary" id="clear-form">Clear All</button>
                    </div>
                </div>
                
                <div class="form-builder-content">
                    <div class="field-types-panel">
                        <h4>Field Types</h4>
                        <div class="field-type-buttons">
                            <button type="button" class="field-type-btn" data-type="text">
                                <span class="dashicons dashicons-editor-textcolor"></span> Text
                            </button>
                            <button type="button" class="field-type-btn" data-type="textarea">
                                <span class="dashicons dashicons-editor-paragraph"></span> Textarea
                            </button>
                            <button type="button" class="field-type-btn" data-type="select">
                                <span class="dashicons dashicons-menu-alt"></span> Select
                            </button>
                            <button type="button" class="field-type-btn" data-type="radio">
                                <span class="dashicons dashicons-marker"></span> Radio
                            </button>
                            <button type="button" class="field-type-btn" data-type="checkbox">
                                <span class="dashicons dashicons-yes"></span> Checkbox
                            </button>
                            <button type="button" class="field-type-btn" data-type="email">
                                <span class="dashicons dashicons-email"></span> Email
                            </button>
                            <button type="button" class="field-type-btn" data-type="number">
                                <span class="dashicons dashicons-calculator"></span> Number
                            </button>
                            <button type="button" class="field-type-btn" data-type="date">
                                <span class="dashicons dashicons-calendar-alt"></span> Date
                            </button>
                            <button type="button" class="field-type-btn" data-type="file">
                                <span class="dashicons dashicons-upload"></span> File
                            </button>
                            <button type="button" class="field-type-btn" data-type="hidden">
                                <span class="dashicons dashicons-hidden"></span> Hidden
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-fields-area">
                        <div id="form-fields-list">
                            <div class="no-fields-message">
                                <p>No fields added yet. Click on field types to add them.</p>
                            </div>
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
                <div class="form-field-item">
                    <div class="field-header">
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
                } else {
                    console.error('Failed to load form data:', response.data);
                    createFormBuilder();
                }
            },
            error: function(xhr, status, error) {
                console.error('Load error:', error);
                createFormBuilder();
            }
        });
    }

})(jQuery);
