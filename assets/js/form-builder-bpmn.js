/**
 * LIFT Forms - BPMN.io Form Builder Integration
 */

(function($) {
    'use strict';

    let formBuilder = null;
    let formSchema = null;
    let currentFormId = 0;
    let isLoading = false;

    // Initialize when DOM is ready and form-js is available
    $(document).ready(function() {
        if ($('#form-builder-container').length) {
            console.log('Form builder container found, initializing...');
            // Simple fallback - just create a basic form builder
            initSimpleFormBuilder();
        }
    });

    /**
     * Initialize Simple Form Builder (fallback)
     */
    function initSimpleFormBuilder() {
        console.log('Initializing Simple Form Builder...');
        
        // Get form ID if editing
        currentFormId = parseInt($('#form-id').val()) || 0;
        
        // Load existing form data if editing
        if (currentFormId > 0) {
            loadFormData(currentFormId);
        } else {
            createSimpleFormBuilder();
        }

        // Bind events
        bindModernFormBuilderEvents();
    }

    /**
     * Create modern 3-panel form builder (inspired by BPMN.io)
     */
    function createSimpleFormBuilder(schema = null) {
        const container = document.getElementById('form-builder-container');
        
        if (!container) {
            console.error('Form builder container not found');
            showError('Form builder container not found');
            return;
        }

        // Clear loading message
        container.innerHTML = '';

        // Default empty schema
        const defaultSchema = schema || {
            type: 'default',
            components: []
        };

        // Create modern 3-panel layout
        const builderHTML = `
            <div class="modern-form-builder">
                <!-- Components Palette -->
                <div class="form-builder-palette">
                    <div class="palette-header">
                        <h3>Components</h3>
                    </div>
                    <div class="palette-content">
                        <div class="palette-group">
                            <div class="palette-group-header">
                                <span class="dashicons dashicons-editor-textcolor palette-item-icon"></span>
                                Input Fields
                            </div>
                            <div class="palette-items">
                                <div class="palette-item" data-type="textfield" draggable="true">
                                    <span class="dashicons dashicons-editor-textcolor palette-item-icon"></span>
                                    Text Field
                                </div>
                                <div class="palette-item" data-type="textarea" draggable="true">
                                    <span class="dashicons dashicons-editor-paragraph palette-item-icon"></span>
                                    Textarea
                                </div>
                                <div class="palette-item" data-type="number" draggable="true">
                                    <span class="dashicons dashicons-calculator palette-item-icon"></span>
                                    Number
                                </div>
                                <div class="palette-item" data-type="email" draggable="true">
                                    <span class="dashicons dashicons-email palette-item-icon"></span>
                                    Email
                                </div>
                            </div>
                        </div>
                        
                        <div class="palette-group">
                            <div class="palette-group-header">
                                <span class="dashicons dashicons-menu-alt palette-item-icon"></span>
                                Selection
                            </div>
                            <div class="palette-items">
                                <div class="palette-item" data-type="select" draggable="true">
                                    <span class="dashicons dashicons-menu-alt palette-item-icon"></span>
                                    Select
                                </div>
                                <div class="palette-item" data-type="radio" draggable="true">
                                    <span class="dashicons dashicons-marker palette-item-icon"></span>
                                    Radio Group
                                </div>
                                <div class="palette-item" data-type="checkbox" draggable="true">
                                    <span class="dashicons dashicons-yes palette-item-icon"></span>
                                    Checkbox
                                </div>
                            </div>
                        </div>
                        
                        <div class="palette-group">
                            <div class="palette-group-header">
                                <span class="dashicons dashicons-admin-page palette-item-icon"></span>
                                Presentation
                            </div>
                            <div class="palette-items">
                                <div class="palette-item" data-type="heading" draggable="true">
                                    <span class="dashicons dashicons-heading palette-item-icon"></span>
                                    Heading
                                </div>
                                <div class="palette-item" data-type="text" draggable="true">
                                    <span class="dashicons dashicons-text-page palette-item-icon"></span>
                                    Text Block
                                </div>
                                <div class="palette-item" data-type="separator" draggable="true">
                                    <span class="dashicons dashicons-minus palette-item-icon"></span>
                                    Separator
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Canvas -->
                <div class="form-builder-canvas">
                    <div class="canvas-header">
                        <h4 class="canvas-title">Form Design</h4>
                        <div class="canvas-actions">
                            <button type="button" class="button button-secondary" id="clear-canvas">
                                <span class="dashicons dashicons-trash"></span> Clear All
                            </button>
                            <button type="button" class="button button-primary" id="preview-canvas">
                                <span class="dashicons dashicons-visibility"></span> Preview
                            </button>
                        </div>
                    </div>
                    <div class="form-canvas-area" id="form-canvas">
                        <div class="canvas-drop-zone">
                            <h4>Drag & Drop Components</h4>
                            <p>Drag components from the left panel to build your form</p>
                        </div>
                    </div>
                </div>
                
                <!-- Properties Panel -->
                <div class="form-builder-properties">
                    <div class="properties-header">
                        <h3>Properties</h3>
                    </div>
                    <div class="properties-content" id="properties-panel">
                        <div class="properties-empty">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <h4>No Component Selected</h4>
                            <p>Select a component to edit its properties</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = builderHTML;
        
        // Initialize with default schema
        if (defaultSchema.components && defaultSchema.components.length > 0) {
            renderCanvasFields(defaultSchema.components);
        }

        formSchema = defaultSchema;
        
        // Bind events
        bindModernFormBuilderEvents();

        console.log('Modern form builder created successfully');
    }

    /**
     * Render fields in canvas
     */
    function renderCanvasFields(components) {
        const canvas = $('#form-canvas');
        const dropZone = canvas.find('.canvas-drop-zone');
        
        if (components.length > 0) {
            canvas.addClass('has-fields');
            dropZone.hide();
            
            // Clear existing fields
            canvas.find('.canvas-form-field').remove();
            
            components.forEach((component, index) => {
                const fieldHTML = createCanvasFieldHTML(component, index);
                canvas.append(fieldHTML);
            });
        } else {
            canvas.removeClass('has-fields');
            dropZone.show();
        }
    }

    /**
     * Create HTML for canvas field
     */
    function createCanvasFieldHTML(component, index) {
        return `
            <div class="canvas-form-field" data-index="${index}" data-type="${component.type}">
                <div class="field-controls">
                    <button type="button" class="field-control-btn edit-field" title="Edit">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="field-control-btn delete-field delete" title="Delete">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                ${getCanvasFieldPreviewHTML(component)}
            </div>
        `;
    }

    /**
     * Get preview HTML for canvas field
     */
    function getCanvasFieldPreviewHTML(component) {
        switch (component.type) {
            case 'textfield':
                return `
                    <label>${component.label || 'Text Field'}</label>
                    <input type="text" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'textarea':
                return `
                    <label>${component.label || 'Textarea'}</label>
                    <textarea placeholder="${component.placeholder || ''}" disabled></textarea>
                `;
            case 'number':
                return `
                    <label>${component.label || 'Number Field'}</label>
                    <input type="number" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'email':
                return `
                    <label>${component.label || 'Email Field'}</label>
                    <input type="email" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'select':
                const options = component.options || ['Option 1', 'Option 2'];
                return `
                    <label>${component.label || 'Select Field'}</label>
                    <select disabled>
                        <option>Choose an option...</option>
                        ${options.map(opt => `<option>${opt}</option>`).join('')}
                    </select>
                `;
            case 'radio':
                const radioOptions = component.options || ['Option 1', 'Option 2'];
                return `
                    <label>${component.label || 'Radio Group'}</label>
                    <div class="radio-group">
                        ${radioOptions.map((opt, i) => `
                            <label style="font-weight: normal; margin-bottom: 4px; display: block;">
                                <input type="radio" name="radio_${component.key}" disabled> ${opt}
                            </label>
                        `).join('')}
                    </div>
                `;
            case 'checkbox':
                return `
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" disabled>
                        <span>${component.label || 'Checkbox option'}</span>
                    </label>
                `;
            case 'heading':
                const level = component.level || 'h3';
                return `<${level} style="margin: 0; color: #495057;">${component.text || 'Heading'}</${level}>`;
            case 'text':
                return `<div style="color: #495057; line-height: 1.5;">${component.text || 'Text content goes here...'}</div>`;
            case 'separator':
                return `<hr style="border: none; border-top: 1px solid #dee2e6; margin: 16px 0;">`;
            default:
                return '<div>Unknown field type</div>';
        }
    }

    /**
     * Show properties panel for selected component
     */
    function showPropertiesPanel(component, index) {
        const panel = $('#properties-panel');
        
        const propertiesHTML = `
            <div class="property-section">
                <h4>General</h4>
                <div class="property-field">
                    <label>Label</label>
                    <input type="text" id="prop-label" value="${component.label || ''}" placeholder="Field label">
                </div>
                <div class="property-field">
                    <label>Key</label>
                    <input type="text" id="prop-key" value="${component.key || ''}" placeholder="field_key">
                </div>
                ${component.type !== 'heading' && component.type !== 'text' && component.type !== 'separator' ? `
                <div class="property-field">
                    <label>Required</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="prop-required" ${component.required ? 'checked' : ''}>
                        <label for="prop-required">This field is required</label>
                    </div>
                </div>
                ` : ''}
            </div>
            
            ${(component.type === 'textfield' || component.type === 'textarea' || component.type === 'number' || component.type === 'email') ? `
            <div class="property-section">
                <h4>Input Settings</h4>
                <div class="property-field">
                    <label>Placeholder</label>
                    <input type="text" id="prop-placeholder" value="${component.placeholder || ''}" placeholder="Placeholder text">
                </div>
                ${component.type === 'textfield' ? `
                <div class="property-field">
                    <label>Max Length</label>
                    <input type="number" id="prop-maxlength" value="${component.maxlength || ''}" placeholder="Maximum characters">
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            ${(component.type === 'select' || component.type === 'radio') ? `
            <div class="property-section">
                <h4>Options</h4>
                <div class="property-field">
                    <label>Options (one per line)</label>
                    <textarea id="prop-options" placeholder="Option 1\nOption 2\nOption 3">${(component.options || []).join('\n')}</textarea>
                </div>
            </div>
            ` : ''}
            
            ${(component.type === 'heading') ? `
            <div class="property-section">
                <h4>Heading Settings</h4>
                <div class="property-field">
                    <label>Text</label>
                    <input type="text" id="prop-text" value="${component.text || ''}" placeholder="Heading text">
                </div>
                <div class="property-field">
                    <label>Level</label>
                    <select id="prop-level">
                        <option value="h1" ${component.level === 'h1' ? 'selected' : ''}>H1</option>
                        <option value="h2" ${component.level === 'h2' ? 'selected' : ''}>H2</option>
                        <option value="h3" ${component.level === 'h3' ? 'selected' : ''}>H3</option>
                        <option value="h4" ${component.level === 'h4' ? 'selected' : ''}>H4</option>
                    </select>
                </div>
            </div>
            ` : ''}
            
            ${(component.type === 'text') ? `
            <div class="property-section">
                <h4>Text Settings</h4>
                <div class="property-field">
                    <label>Content</label>
                    <textarea id="prop-text" placeholder="Text content">${component.text || ''}</textarea>
                </div>
            </div>
            ` : ''}
            
            <div class="property-section">
                <button type="button" class="button button-primary" id="save-properties">Save Changes</button>
                <button type="button" class="button" id="cancel-properties">Cancel</button>
            </div>
        `;
        
        panel.html(propertiesHTML);
        
        // Store current editing index
        panel.data('editing-index', index);
    }

    /**
     * Bind modern form builder events
     */
    function bindModernFormBuilderEvents() {
        // Palette group toggles
        $(document).on('click', '.palette-group-header', function() {
            $(this).toggleClass('collapsed');
            $(this).closest('.palette-group').toggleClass('collapsed');
        });

        // Drag and drop from palette
        $(document).on('dragstart', '.palette-item', function(e) {
            const fieldType = $(this).data('type');
            e.originalEvent.dataTransfer.setData('text/plain', fieldType);
            $(this).addClass('dragging');
        });

        $(document).on('dragend', '.palette-item', function() {
            $(this).removeClass('dragging');
        });

        // Canvas drop zone
        $(document).on('dragover', '#form-canvas', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave', '#form-canvas', function(e) {
            if (!$(this).has(e.relatedTarget).length) {
                $(this).removeClass('drag-over');
            }
        });

        $(document).on('drop', '#form-canvas', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const fieldType = e.originalEvent.dataTransfer.getData('text/plain');
            if (fieldType) {
                addNewCanvasField(fieldType);
            }
        });

        // Field selection and editing
        $(document).on('click', '.canvas-form-field', function(e) {
            if ($(e.target).closest('.field-controls').length) return;
            
            $('.canvas-form-field').removeClass('selected');
            $(this).addClass('selected');
            
            const index = $(this).data('index');
            const component = formSchema.components[index];
            showPropertiesPanel(component, index);
        });

        // Field controls
        $(document).on('click', '.edit-field', function(e) {
            e.stopPropagation();
            const fieldItem = $(this).closest('.canvas-form-field');
            fieldItem.click();
        });

        $(document).on('click', '.delete-field', function(e) {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this field?')) {
                const fieldItem = $(this).closest('.canvas-form-field');
                const index = fieldItem.data('index');
                
                formSchema.components.splice(index, 1);
                renderCanvasFields(formSchema.components);
                
                // Clear properties panel
                $('#properties-panel').html(`
                    <div class="properties-empty">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h4>No Component Selected</h4>
                        <p>Select a component to edit its properties</p>
                    </div>
                `);
            }
        });

        // Properties panel
        $(document).on('click', '#save-properties', function() {
            const panel = $('#properties-panel');
            const index = panel.data('editing-index');
            
            if (index >= 0 && formSchema.components[index]) {
                const component = formSchema.components[index];
                
                // Update component properties
                component.label = $('#prop-label').val();
                component.key = $('#prop-key').val();
                component.placeholder = $('#prop-placeholder').val();
                component.maxlength = $('#prop-maxlength').val();
                component.required = $('#prop-required').is(':checked');
                component.text = $('#prop-text').val();
                component.level = $('#prop-level').val();
                
                if ($('#prop-options').length) {
                    component.options = $('#prop-options').val().split('\n').filter(opt => opt.trim());
                }
                
                // Re-render canvas
                renderCanvasFields(formSchema.components);
                
                // Update properties panel
                showPropertiesPanel(component, index);
                
                console.log('Component updated:', component);
            }
        });

        $(document).on('click', '#cancel-properties', function() {
            $('.canvas-form-field').removeClass('selected');
            $('#properties-panel').html(`
                <div class="properties-empty">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h4>No Component Selected</h4>
                    <p>Select a component to edit its properties</p>
                </div>
            `);
        });

        // Canvas actions
        $(document).on('click', '#clear-canvas', function() {
            if (confirm('Are you sure you want to clear all fields?')) {
                formSchema.components = [];
                renderCanvasFields(formSchema.components);
                $('#properties-panel').html(`
                    <div class="properties-empty">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h4>No Component Selected</h4>
                        <p>Select a component to edit its properties</p>
                    </div>
                `);
            }
        });

        $(document).on('click', '#preview-canvas', function() {
            previewForm();
        });
    }

    /**
     * Add new field to canvas
     */
    function addNewCanvasField(fieldType) {
        const fieldConfig = {
            textfield: { label: 'Text Field', key: `textfield_${Date.now()}`, placeholder: 'Enter text...' },
            textarea: { label: 'Textarea', key: `textarea_${Date.now()}`, placeholder: 'Enter text...' },
            number: { label: 'Number Field', key: `number_${Date.now()}`, placeholder: 'Enter number...' },
            email: { label: 'Email Field', key: `email_${Date.now()}`, placeholder: 'Enter email...' },
            select: { label: 'Select Field', key: `select_${Date.now()}`, options: ['Option 1', 'Option 2', 'Option 3'] },
            radio: { label: 'Radio Group', key: `radio_${Date.now()}`, options: ['Option 1', 'Option 2'] },
            checkbox: { label: 'Checkbox', key: `checkbox_${Date.now()}` },
            heading: { label: 'Heading', key: `heading_${Date.now()}`, text: 'Heading Text', level: 'h3' },
            text: { label: 'Text Block', key: `text_${Date.now()}`, text: 'Text content goes here...' },
            separator: { label: 'Separator', key: `separator_${Date.now()}` }
        };

        const config = fieldConfig[fieldType];
        if (!config) return;

        const newComponent = {
            type: fieldType,
            ...config
        };

        formSchema.components.push(newComponent);
        renderCanvasFields(formSchema.components);
        
        console.log('New field added:', newComponent);
    }

    /**
     * Load form data from server
     */
    function loadFormData(formId) {
        if (isLoading) return;
        
        isLoading = true;
        showLoading();

        $.ajax({
            url: liftFormBuilder.ajaxurl,
            type: 'POST',
            data: {
                action: 'lift_form_builder_load',
                form_id: formId,
                nonce: liftFormBuilder.nonce
            },
            success: function(response) {
                if (response.success && response.data.schema) {
                    try {
                        const schema = typeof response.data.schema === 'string' 
                            ? JSON.parse(response.data.schema) 
                            : response.data.schema;
                        
                        createSimpleFormBuilder(schema);
                        
                        // Update form name and description
                        if (response.data.name) {
                            $('#form-name').val(response.data.name);
                        }
                        if (response.data.description) {
                            $('#form-description').val(response.data.description);
                        }
                        
                    } catch (error) {
                        console.error('Error parsing form schema:', error);
                        createSimpleFormBuilder(); // Create with default schema
                    }
                } else {
                    createSimpleFormBuilder(); // Create with default schema
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading form:', error);
                showError('Error loading form data');
                createSimpleFormBuilder(); // Create with default schema anyway
            },
            complete: function() {
                isLoading = false;
            }
        });
    }

    /**
     * Save form
     */
    function saveForm() {
        if (isLoading || !formBuilder) {
            return;
        }

        const formName = $('#form-name').val().trim();
        const formDescription = $('#form-description').val().trim();

        if (!formName) {
            alert(liftFormBuilder.strings.error || 'Please enter a form name');
            $('#form-name').focus();
            return;
        }

        isLoading = true;
        
        // Update button state
        const saveButton = $('#save-form');
        const originalText = saveButton.text();
        saveButton.text(liftFormBuilder.strings.saving || 'Saving...').prop('disabled', true);

        try {
            const schema = formSchema || { type: 'default', components: [] };
            
            $.ajax({
                url: liftFormBuilder.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lift_form_builder_save',
                    form_id: currentFormId,
                    form_name: formName,
                    form_description: formDescription,
                    form_schema: JSON.stringify(schema),
                    nonce: liftFormBuilder.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message || liftFormBuilder.strings.saved);
                        
                        // Update form ID if this was a new form
                        if (response.data.form_id && currentFormId === 0) {
                            currentFormId = response.data.form_id;
                            $('#form-id').val(currentFormId);
                            
                            // Update URL to include form ID
                            const url = new URL(window.location);
                            url.searchParams.set('id', currentFormId);
                            window.history.replaceState({}, '', url);
                        }
                    } else {
                        alert(response.data.message || liftFormBuilder.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error saving form:', error);
                    alert(liftFormBuilder.strings.error || 'Error saving form');
                },
                complete: function() {
                    isLoading = false;
                    saveButton.text(originalText).prop('disabled', false);
                }
            });
            
        } catch (error) {
            console.error('Error getting form schema:', error);
            alert('Error preparing form data for save');
            isLoading = false;
            saveButton.text(originalText).prop('disabled', false);
        }
    }

    /**
     * Preview form
     */
    function previewForm() {
        if (!formSchema) {
            alert(liftFormBuilder.strings.error || 'No form data to preview');
            return;
        }

        try {
            // Create preview modal
            const modal = $(`
                <div class="lift-form-preview-modal">
                    <div class="lift-modal-backdrop"></div>
                    <div class="lift-modal-content">
                        <div class="lift-modal-header">
                            <h2>${liftFormBuilder.strings.preview || 'Form Preview'}</h2>
                            <button type="button" class="lift-modal-close">&times;</button>
                        </div>
                        <div class="lift-modal-body">
                            <div id="form-preview-container">
                                ${generatePreviewHTML(formSchema.components)}
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(modal);

            // Close modal events
            modal.find('.lift-modal-close, .lift-modal-backdrop').on('click', function() {
                modal.remove();
            });

            // ESC key to close
            $(document).on('keydown.preview-modal', function(e) {
                if (e.key === 'Escape') {
                    modal.remove();
                    $(document).off('keydown.preview-modal');
                }
            });

        } catch (error) {
            console.error('Error creating form preview:', error);
            alert('Error creating form preview: ' + error.message);
        }
    }

    /**
     * Generate preview HTML from schema
     */
    function generatePreviewHTML(components) {
        if (!components || components.length === 0) {
            return '<p>No form fields to preview.</p>';
        }

        let html = '<form class="preview-form">';
        
        components.forEach((component, index) => {
            html += `<div class="form-field">`;
            
            if (component.label) {
                html += `<label for="field_${index}">${component.label}</label>`;
            }
            
            switch (component.type) {
                case 'textfield':
                    html += `<input type="text" id="field_${index}" name="${component.key}" placeholder="${component.placeholder || ''}" />`;
                    break;
                case 'textarea':
                    html += `<textarea id="field_${index}" name="${component.key}" placeholder="${component.placeholder || ''}"></textarea>`;
                    break;
                case 'select':
                    html += `<select id="field_${index}" name="${component.key}">`;
                    html += `<option value="">Select an option</option>`;
                    if (component.options) {
                        component.options.forEach(option => {
                            html += `<option value="${option}">${option}</option>`;
                        });
                    }
                    html += `</select>`;
                    break;
                case 'checkbox':
                    html += `<label><input type="checkbox" id="field_${index}" name="${component.key}" /> ${component.label}</label>`;
                    break;
                default:
                    html += `<div>Unknown field type: ${component.type}</div>`;
            }
            
            html += `</div>`;
        });
        
        html += '<div class="form-actions"><button type="submit" class="button button-primary">Submit</button></div>';
        html += '</form>';
        
        return html;
    }

    /**
     * Show loading message
     */
    function showLoading() {
        const container = $('#form-builder-container');
        container.html(`
            <div class="loading-message">
                <div class="spinner"></div>
                <p>${liftFormBuilder.strings.loading || 'Loading form builder...'}</p>
            </div>
        `);
    }

    /**
     * Show error message
     */
    function showError(message) {
        const container = $('#form-builder-container');
        container.html(`
            <div class="error-message">
                <span class="dashicons dashicons-warning"></span>
                <h3>${liftFormBuilder.strings.error || 'Error'}</h3>
                <p>${message}</p>
                <button type="button" class="button" onclick="location.reload()">Reload Page</button>
            </div>
        `);
    }

    /**
     * Show success notification
     */
    function showNotification(message) {
        const notification = $(`
            <div class="lift-form-notification success">
                <span class="dashicons dashicons-yes-alt"></span>
                <span>${message}</span>
            </div>
        `);

        $('body').append(notification);

        // Auto remove after 3 seconds
        setTimeout(function() {
            notification.fadeOut(300, function() {
                notification.remove();
            });
        }, 3000);
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Save form button
        $(document).on('click', '#save-form', saveForm);

        // Preview form button
        $(document).on('click', '#preview-form', previewForm);

        // Auto-save on form name/description change (debounced)
        let saveTimeout;
        $('#form-name, #form-description').on('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                if (currentFormId > 0 && formBuilder) {
                    saveForm();
                }
            }, 2000);
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveForm();
            }
        });
    }

    // Expose global functions for debugging
    window.LiftFormBuilder = {
        getSchema: function() {
            return formSchema;
        },
        saveForm: saveForm,
        previewForm: previewForm,
        addField: addNewField
    };

})(jQuery);
