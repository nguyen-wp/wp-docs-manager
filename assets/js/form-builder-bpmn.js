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
        bindEvents();
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
                                    <div class="palette-item" data-type="password" draggable="true">
                                        <span class="dashicons dashicons-lock palette-item-icon"></span>
                                        Password
                                    </div>
                                    <div class="palette-item" data-type="url" draggable="true">
                                        <span class="dashicons dashicons-admin-links palette-item-icon"></span>
                                        URL
                                    </div>
                                    <div class="palette-item" data-type="tel" draggable="true">
                                        <span class="dashicons dashicons-phone palette-item-icon"></span>
                                        Phone
                                    </div>
                                    <div class="palette-item" data-type="date" draggable="true">
                                        <span class="dashicons dashicons-calendar-alt palette-item-icon"></span>
                                        Date
                                    </div>
                                    <div class="palette-item" data-type="time" draggable="true">
                                        <span class="dashicons dashicons-clock palette-item-icon"></span>
                                        Time
                                    </div>
                                    <div class="palette-item" data-type="datetime" draggable="true">
                                        <span class="dashicons dashicons-calendar palette-item-icon"></span>
                                        Date & Time
                                    </div>
                                    <div class="palette-item" data-type="file" draggable="true">
                                        <span class="dashicons dashicons-upload palette-item-icon"></span>
                                        File Upload
                                    </div>
                                    <div class="palette-item" data-type="range" draggable="true">
                                        <span class="dashicons dashicons-leftright palette-item-icon"></span>
                                        Range Slider
                                    </div>
                                    <div class="palette-item" data-type="color" draggable="true">
                                        <span class="dashicons dashicons-art palette-item-icon"></span>
                                        Color Picker
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
                                        Select Dropdown
                                    </div>
                                    <div class="palette-item" data-type="multiselect" draggable="true">
                                        <span class="dashicons dashicons-list-view palette-item-icon"></span>
                                        Multi Select
                                    </div>
                                    <div class="palette-item" data-type="radio" draggable="true">
                                        <span class="dashicons dashicons-marker palette-item-icon"></span>
                                        Radio Group
                                    </div>
                                    <div class="palette-item" data-type="checkbox" draggable="true">
                                        <span class="dashicons dashicons-yes palette-item-icon"></span>
                                        Checkbox
                                    </div>
                                    <div class="palette-item" data-type="checkboxgroup" draggable="true">
                                        <span class="dashicons dashicons-yes-alt palette-item-icon"></span>
                                        Checkbox Group
                                    </div>
                                    <div class="palette-item" data-type="toggle" draggable="true">
                                        <span class="dashicons dashicons-controls-repeat palette-item-icon"></span>
                                        Toggle Switch
                                    </div>
                                </div>
                            </div>
                        
                            <div class="palette-group">
                                <div class="palette-group-header">
                                    <span class="dashicons dashicons-admin-page palette-item-icon"></span>
                                    Content & Layout
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
                                    <div class="palette-item" data-type="html" draggable="true">
                                        <span class="dashicons dashicons-editor-code palette-item-icon"></span>
                                        HTML Block
                                    </div>
                                    <div class="palette-item" data-type="separator" draggable="true">
                                        <span class="dashicons dashicons-minus palette-item-icon"></span>
                                        Separator
                                    </div>
                                    <div class="palette-item" data-type="spacer" draggable="true">
                                        <span class="dashicons dashicons-editor-break palette-item-icon"></span>
                                        Spacer
                                    </div>
                                    <div class="palette-item" data-type="image" draggable="true">
                                        <span class="dashicons dashicons-format-image palette-item-icon"></span>
                                        Image
                                    </div>
                                    <div class="palette-item" data-type="video" draggable="true">
                                        <span class="dashicons dashicons-format-video palette-item-icon"></span>
                                        Video
                                    </div>
                                </div>
                            </div>
                            
                            <div class="palette-group">
                                <div class="palette-group-header">
                                    <span class="dashicons dashicons-admin-tools palette-item-icon"></span>
                                    Advanced
                                </div>
                                <div class="palette-items">
                                    <div class="palette-item" data-type="hidden" draggable="true">
                                        <span class="dashicons dashicons-hidden palette-item-icon"></span>
                                        Hidden Field
                                    </div>
                                    <div class="palette-item" data-type="rating" draggable="true">
                                        <span class="dashicons dashicons-star-filled palette-item-icon"></span>
                                        Star Rating
                                    </div>
                                    <div class="palette-item" data-type="signature" draggable="true">
                                        <span class="dashicons dashicons-edit palette-item-icon"></span>
                                        Signature
                                    </div>
                                    <div class="palette-item" data-type="captcha" draggable="true">
                                        <span class="dashicons dashicons-shield palette-item-icon"></span>
                                        Captcha
                                    </div>
                                    <div class="palette-item" data-type="calculation" draggable="true">
                                        <span class="dashicons dashicons-calculator palette-item-icon"></span>
                                        Calculation
                                    </div>
                                </div>
                            </div>
                        
                            <div class="palette-group">
                                <div class="palette-group-header">
                                    <span class="dashicons dashicons-admin-generic palette-item-icon"></span>
                                    Actions
                                </div>
                                <div class="palette-items">
                                    <div class="palette-item" data-type="submit" draggable="true">
                                        <span class="dashicons dashicons-yes palette-item-icon"></span>
                                        Submit Button
                                    </div>
                                    <div class="palette-item" data-type="reset" draggable="true">
                                        <span class="dashicons dashicons-undo palette-item-icon"></span>
                                        Reset Button
                                    </div>
                                    <div class="palette-item" data-type="button" draggable="true">
                                        <span class="dashicons dashicons-admin-links palette-item-icon"></span>
                                        Custom Button
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
            // Input Fields
            case 'textfield':
                return `
                    <label>${component.label || 'Text Field'}</label>
                    <input type="text" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'textarea':
                return `
                    <label>${component.label || 'Textarea'}</label>
                    <textarea rows="${component.rows || 4}" placeholder="${component.placeholder || ''}" disabled></textarea>
                `;
            case 'number':
                return `
                    <label>${component.label || 'Number Field'}</label>
                    <input type="number" placeholder="${component.placeholder || ''}" 
                           min="${component.min || ''}" max="${component.max || ''}" disabled>
                `;
            case 'email':
                return `
                    <label>${component.label || 'Email Field'}</label>
                    <input type="email" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'password':
                return `
                    <label>${component.label || 'Password Field'}</label>
                    <input type="password" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'url':
                return `
                    <label>${component.label || 'URL Field'}</label>
                    <input type="url" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'tel':
                return `
                    <label>${component.label || 'Phone Field'}</label>
                    <input type="tel" placeholder="${component.placeholder || ''}" disabled>
                `;
            case 'date':
                return `
                    <label>${component.label || 'Date Field'}</label>
                    <input type="date" disabled>
                `;
            case 'time':
                return `
                    <label>${component.label || 'Time Field'}</label>
                    <input type="time" disabled>
                `;
            case 'datetime':
                return `
                    <label>${component.label || 'Date & Time Field'}</label>
                    <input type="datetime-local" disabled>
                `;
            case 'file':
                return `
                    <label>${component.label || 'File Upload'}</label>
                    <input type="file" accept="${component.accept || '*'}" 
                           ${component.multiple ? 'multiple' : ''} disabled>
                `;
            case 'range':
                return `
                    <label>${component.label || 'Range Slider'}</label>
                    <input type="range" min="${component.min || 0}" max="${component.max || 100}" 
                           step="${component.step || 1}" disabled>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">
                        Range: ${component.min || 0} - ${component.max || 100}
                    </div>
                `;
            case 'color':
                return `
                    <label>${component.label || 'Color Picker'}</label>
                    <input type="color" disabled>
                `;
                
            // Selection Fields
            case 'select':
                const options = component.options || ['Option 1', 'Option 2'];
                return `
                    <label>${component.label || 'Select Field'}</label>
                    <select disabled>
                        <option>Choose an option...</option>
                        ${options.map(opt => `<option>${opt}</option>`).join('')}
                    </select>
                `;
            case 'multiselect':
                const multiOptions = component.options || ['Option 1', 'Option 2', 'Option 3'];
                return `
                    <label>${component.label || 'Multi Select'}</label>
                    <select multiple disabled size="3">
                        ${multiOptions.map(opt => `<option>${opt}</option>`).join('')}
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
            case 'checkboxgroup':
                const checkboxOptions = component.options || ['Option 1', 'Option 2', 'Option 3'];
                return `
                    <label>${component.label || 'Checkbox Group'}</label>
                    <div class="checkbox-group">
                        ${checkboxOptions.map(opt => `
                            <label style="font-weight: normal; margin-bottom: 4px; display: block;">
                                <input type="checkbox" value="${opt}" disabled> ${opt}
                            </label>
                        `).join('')}
                    </div>
                `;
            case 'toggle':
                return `
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <div style="position: relative; width: 44px; height: 24px; background: #ccc; border-radius: 12px;">
                            <div style="position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: 0.3s;"></div>
                        </div>
                        <span>${component.label || 'Toggle Switch'}</span>
                    </label>
                `;
                
            // Content & Layout
            case 'heading':
                const level = component.level || 'h3';
                return `<${level} style="margin: 0; color: #495057;">${component.text || 'Heading'}</${level}>`;
            case 'text':
                if (component.html) {
                    return `<div style="color: #495057; line-height: 1.5;">${component.html}</div>`;
                } else {
                    return `<div style="color: #495057; line-height: 1.5;">${component.text || 'Text content goes here...'}</div>`;
                }
            case 'html':
                return `<div style="color: #495057; line-height: 1.5; border: 1px dashed #ddd; padding: 8px;">${component.html || 'HTML content goes here...'}</div>`;
            case 'separator':
                return `<hr style="border: none; border-top: ${component.style === 'dashed' ? '1px dashed' : '1px solid'} #dee2e6; margin: 16px 0;">`;
            case 'spacer':
                return `<div style="height: ${component.height || '20px'}; background: repeating-linear-gradient(90deg, transparent, transparent 10px, #ddd 10px, #ddd 11px); opacity: 0.3;"></div>`;
            case 'image':
                return `
                    <div>
                        ${component.src ? 
                            `<img src="${component.src}" alt="${component.alt || ''}" 
                                 style="max-width: 100%; height: auto; width: ${component.width || 'auto'}; border: 1px solid #ddd;">` :
                            '<div style="border: 2px dashed #ddd; padding: 20px; text-align: center; color: #666;">📷 Image Placeholder</div>'
                        }
                    </div>
                `;
            case 'video':
                return `
                    <div>
                        ${component.src ? 
                            `<video controls style="width: 100%; max-width: ${component.width || '100%'}; height: ${component.height || '200px'}; border: 1px solid #ddd;">
                                <source src="${component.src}">
                            </video>` :
                            '<div style="border: 2px dashed #ddd; padding: 20px; text-align: center; color: #666; height: 120px; display: flex; align-items: center; justify-content: center;">🎥 Video Placeholder</div>'
                        }
                    </div>
                `;
                
            // Advanced
            case 'hidden':
                return `<div style="background: #f8f9fa; border: 1px dashed #6c757d; padding: 8px; color: #6c757d; font-size: 12px;">🔒 Hidden Field: ${component.key}</div>`;
            case 'rating':
                const stars = '★'.repeat(component.max || 5);
                const emptyStars = '☆'.repeat((component.max || 5) - (component.value || 0));
                return `
                    <label>${component.label || 'Star Rating'}</label>
                    <div style="font-size: 20px; color: #ffc107;">${stars.substring(0, component.value || 0)}<span style="color: #e9ecef;">${emptyStars}</span></div>
                `;
            case 'signature':
                return `
                    <label>${component.label || 'Signature'}</label>
                    <div style="width: ${component.width || '100%'}; height: ${component.height || '120px'}; border: 1px solid #ddd; background: #fafafa; display: flex; align-items: center; justify-content: center; color: #666;">
                        ✍️ Signature Area
                    </div>
                `;
            case 'captcha':
                return `
                    <label>${component.label || 'Captcha'}</label>
                    <div style="border: 1px solid #ddd; padding: 12px; background: #f8f9fa; display: flex; align-items: center; gap: 8px;">
                        <div style="background: #fff; border: 1px solid #ccc; padding: 8px; font-family: monospace; letter-spacing: 2px;">AB7K9</div>
                        <input type="text" placeholder="Enter captcha" disabled style="flex: 1;">
                    </div>
                `;
            case 'calculation':
                return `
                    <label>${component.label || 'Calculation'}</label>
                    <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 8px; color: #0066cc;">
                        🧮 Result: ${component.formula ? 'Formula defined' : 'No formula'}
                    </div>
                `;
                
            // Actions
            case 'submit':
                return `<button type="submit" class="button button-${component.style || 'primary'}" disabled style="margin-right: 8px;">${component.text || 'Submit'}</button>`;
            case 'reset':
                return `<button type="reset" class="button" disabled style="margin-right: 8px;">${component.text || 'Reset'}</button>`;
            case 'button':
                return `<button type="button" class="button" disabled style="margin-right: 8px;">${component.text || 'Click Me'}</button>`;
                
            default:
                return '<div style="color: #dc3545; border: 1px dashed #dc3545; padding: 8px;">Unknown field type: ' + component.type + '</div>';
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
                ${!['heading', 'text', 'html', 'separator', 'spacer', 'image', 'video', 'submit', 'reset', 'button'].includes(component.type) ? `
                <div class="property-field">
                    <label>Required</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="prop-required" ${component.required ? 'checked' : ''}>
                        <label for="prop-required">This field is required</label>
                    </div>
                </div>
                ` : ''}
            </div>
            
            ${['textfield', 'textarea', 'number', 'email', 'password', 'url', 'tel'].includes(component.type) ? `
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
                ${component.type === 'textarea' ? `
                <div class="property-field">
                    <label>Rows</label>
                    <input type="number" id="prop-rows" value="${component.rows || 4}" min="2" max="20">
                </div>
                ` : ''}
                ${component.type === 'number' ? `
                <div class="property-field">
                    <label>Min Value</label>
                    <input type="number" id="prop-min" value="${component.min || ''}" placeholder="Minimum value">
                </div>
                <div class="property-field">
                    <label>Max Value</label>
                    <input type="number" id="prop-max" value="${component.max || ''}" placeholder="Maximum value">
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            ${component.type === 'range' ? `
            <div class="property-section">
                <h4>Range Settings</h4>
                <div class="property-field">
                    <label>Min Value</label>
                    <input type="number" id="prop-min" value="${component.min || 0}">
                </div>
                <div class="property-field">
                    <label>Max Value</label>
                    <input type="number" id="prop-max" value="${component.max || 100}">
                </div>
                <div class="property-field">
                    <label>Step</label>
                    <input type="number" id="prop-step" value="${component.step || 1}" min="0.1" step="0.1">
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'file' ? `
            <div class="property-section">
                <h4>File Settings</h4>
                <div class="property-field">
                    <label>Accept</label>
                    <input type="text" id="prop-accept" value="${component.accept || '*'}" placeholder="e.g., .jpg,.png,.pdf">
                </div>
                <div class="property-field">
                    <label>Multiple Files</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="prop-multiple" ${component.multiple ? 'checked' : ''}>
                        <label for="prop-multiple">Allow multiple files</label>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${['select', 'multiselect', 'radio', 'checkboxgroup'].includes(component.type) ? `
            <div class="property-section">
                <h4>Options</h4>
                <div class="property-field">
                    <label>Options (one per line)</label>
                    <textarea id="prop-options" placeholder="Option 1\nOption 2\nOption 3">${(component.options || []).join('\n')}</textarea>
                </div>
                ${component.type === 'multiselect' ? `
                <div class="property-field">
                    <label>Size</label>
                    <input type="number" id="prop-size" value="${component.size || 3}" min="2" max="10">
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            ${component.type === 'heading' ? `
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
                        <option value="h5" ${component.level === 'h5' ? 'selected' : ''}>H5</option>
                        <option value="h6" ${component.level === 'h6' ? 'selected' : ''}>H6</option>
                    </select>
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'text' ? `
            <div class="property-section">
                <h4>Text Settings</h4>
                <div class="property-field">
                    <label>Content</label>
                    <textarea id="prop-text" placeholder="Text content" rows="4">${component.text || ''}</textarea>
                </div>
                <div class="property-field">
                    <label>HTML Mode</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="prop-html" ${component.html ? 'checked' : ''}>
                        <label for="prop-html">Allow HTML content</label>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'html' ? `
            <div class="property-section">
                <h4>HTML Settings</h4>
                <div class="property-field">
                    <label>HTML Content</label>
                    <textarea id="prop-html" placeholder="HTML content" rows="6">${component.html || ''}</textarea>
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'separator' ? `
            <div class="property-section">
                <h4>Separator Settings</h4>
                <div class="property-field">
                    <label>Style</label>
                    <select id="prop-style">
                        <option value="solid" ${component.style === 'solid' ? 'selected' : ''}>Solid</option>
                        <option value="dashed" ${component.style === 'dashed' ? 'selected' : ''}>Dashed</option>
                        <option value="dotted" ${component.style === 'dotted' ? 'selected' : ''}>Dotted</option>
                    </select>
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'spacer' ? `
            <div class="property-section">
                <h4>Spacer Settings</h4>
                <div class="property-field">
                    <label>Height</label>
                    <input type="text" id="prop-height" value="${component.height || '20px'}" placeholder="e.g., 20px, 2rem">
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'image' ? `
            <div class="property-section">
                <h4>Image Settings</h4>
                <div class="property-field">
                    <label>Source URL</label>
                    <input type="url" id="prop-src" value="${component.src || ''}" placeholder="https://example.com/image.jpg">
                </div>
                <div class="property-field">
                    <label>Alt Text</label>
                    <input type="text" id="prop-alt" value="${component.alt || ''}" placeholder="Image description">
                </div>
                <div class="property-field">
                    <label>Width</label>
                    <input type="text" id="prop-width" value="${component.width || ''}" placeholder="e.g., 100%, 300px">
                </div>
                <div class="property-field">
                    <label>Height</label>
                    <input type="text" id="prop-height" value="${component.height || ''}" placeholder="e.g., auto, 200px">
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'video' ? `
            <div class="property-section">
                <h4>Video Settings</h4>
                <div class="property-field">
                    <label>Source URL</label>
                    <input type="url" id="prop-src" value="${component.src || ''}" placeholder="https://example.com/video.mp4">
                </div>
                <div class="property-field">
                    <label>Width</label>
                    <input type="text" id="prop-width" value="${component.width || '100%'}" placeholder="e.g., 100%, 640px">
                </div>
                <div class="property-field">
                    <label>Height</label>
                    <input type="text" id="prop-height" value="${component.height || '300px'}" placeholder="e.g., 300px, 50vh">
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'rating' ? `
            <div class="property-section">
                <h4>Rating Settings</h4>
                <div class="property-field">
                    <label>Max Rating</label>
                    <input type="number" id="prop-max" value="${component.max || 5}" min="1" max="10">
                </div>
                <div class="property-field">
                    <label>Icon</label>
                    <select id="prop-icon">
                        <option value="star" ${component.icon === 'star' ? 'selected' : ''}>Star ★</option>
                        <option value="heart" ${component.icon === 'heart' ? 'selected' : ''}>Heart ♥</option>
                        <option value="thumb" ${component.icon === 'thumb' ? 'selected' : ''}>Thumb 👍</option>
                    </select>
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'signature' ? `
            <div class="property-section">
                <h4>Signature Settings</h4>
                <div class="property-field">
                    <label>Width</label>
                    <input type="text" id="prop-width" value="${component.width || '400px'}" placeholder="e.g., 400px, 100%">
                </div>
                <div class="property-field">
                    <label>Height</label>
                    <input type="text" id="prop-height" value="${component.height || '200px'}" placeholder="e.g., 200px, 150px">
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'captcha' ? `
            <div class="property-section">
                <h4>Captcha Settings</h4>
                <div class="property-field">
                    <label>Type</label>
                    <select id="prop-type">
                        <option value="simple" ${component.type === 'simple' ? 'selected' : ''}>Simple Text</option>
                        <option value="math" ${component.type === 'math' ? 'selected' : ''}>Math Problem</option>
                        <option value="recaptcha" ${component.type === 'recaptcha' ? 'selected' : ''}>reCAPTCHA</option>
                    </select>
                </div>
            </div>
            ` : ''}
            
            ${component.type === 'calculation' ? `
            <div class="property-section">
                <h4>Calculation Settings</h4>
                <div class="property-field">
                    <label>Formula</label>
                    <input type="text" id="prop-formula" value="${component.formula || ''}" placeholder="e.g., field1 + field2 * 0.1">
                </div>
                <div class="property-field">
                    <label>Decimal Places</label>
                    <input type="number" id="prop-decimals" value="${component.decimals || 2}" min="0" max="10">
                </div>
            </div>
            ` : ''}
            
            ${['submit', 'reset', 'button'].includes(component.type) ? `
            <div class="property-section">
                <h4>Button Settings</h4>
                <div class="property-field">
                    <label>Button Text</label>
                    <input type="text" id="prop-text" value="${component.text || ''}" placeholder="Button text">
                </div>
                <div class="property-field">
                    <label>Style</label>
                    <select id="prop-style">
                        <option value="primary" ${component.style === 'primary' ? 'selected' : ''}>Primary</option>
                        <option value="secondary" ${component.style === 'secondary' ? 'selected' : ''}>Secondary</option>
                        <option value="success" ${component.style === 'success' ? 'selected' : ''}>Success</option>
                        <option value="danger" ${component.style === 'danger' ? 'selected' : ''}>Danger</option>
                    </select>
                </div>
                ${component.type === 'button' ? `
                <div class="property-field">
                    <label>Action</label>
                    <select id="prop-action">
                        <option value="custom" ${component.action === 'custom' ? 'selected' : ''}>Custom Action</option>
                        <option value="calculate" ${component.action === 'calculate' ? 'selected' : ''}>Trigger Calculation</option>
                        <option value="validate" ${component.action === 'validate' ? 'selected' : ''}>Validate Form</option>
                    </select>
                </div>
                ` : ''}
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
        // Unbind existing events to prevent duplicates
        $(document).off('click.formbuilder dragstart.formbuilder dragend.formbuilder dragover.formbuilder dragleave.formbuilder drop.formbuilder');
        
        // Palette group toggles
        $(document).on('click.formbuilder', '.palette-group-header', function() {
            $(this).toggleClass('collapsed');
            $(this).closest('.palette-group').toggleClass('collapsed');
        });

        // Drag and drop from palette
        $(document).on('dragstart.formbuilder', '.palette-item', function(e) {
            const fieldType = $(this).data('type');
            e.originalEvent.dataTransfer.setData('text/plain', fieldType);
            $(this).addClass('dragging');
        });

        $(document).on('dragend.formbuilder', '.palette-item', function() {
            $(this).removeClass('dragging');
        });

        // Canvas drop zone
        $(document).on('dragover.formbuilder', '#form-canvas', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave.formbuilder', '#form-canvas', function(e) {
            if (!$(this).has(e.relatedTarget).length) {
                $(this).removeClass('drag-over');
            }
        });

        $(document).on('drop.formbuilder', '#form-canvas', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const fieldType = e.originalEvent.dataTransfer.getData('text/plain');
            if (fieldType) {
                addNewCanvasField(fieldType);
            }
        });

        // Field selection and editing
        $(document).on('click.formbuilder', '.canvas-form-field', function(e) {
            if ($(e.target).closest('.field-controls').length) return;
            
            $('.canvas-form-field').removeClass('selected');
            $(this).addClass('selected');
            
            const index = $(this).data('index');
            const component = formSchema.components[index];
            showPropertiesPanel(component, index);
        });

        // Field controls
        $(document).on('click.formbuilder', '.edit-field', function(e) {
            e.stopPropagation();
            const fieldItem = $(this).closest('.canvas-form-field');
            fieldItem.click();
        });

        $(document).on('click.formbuilder', '.delete-field', function(e) {
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
        $(document).on('click.formbuilder', '#save-properties', function() {
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

        $(document).on('click.formbuilder', '#cancel-properties', function() {
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
        $(document).on('click.formbuilder', '#clear-canvas', function() {
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

        $(document).on('click.formbuilder', '#preview-canvas', function() {
            previewForm();
        });
    }

    /**
     * Add new field to canvas
     */
    function addNewCanvasField(fieldType) {
        const fieldConfig = {
            // Input Fields
            textfield: { label: 'Text Field', key: `textfield_${Date.now()}`, placeholder: 'Enter text...' },
            textarea: { label: 'Textarea', key: `textarea_${Date.now()}`, placeholder: 'Enter text...', rows: 4 },
            number: { label: 'Number Field', key: `number_${Date.now()}`, placeholder: 'Enter number...', min: '', max: '' },
            email: { label: 'Email Field', key: `email_${Date.now()}`, placeholder: 'Enter email...' },
            password: { label: 'Password Field', key: `password_${Date.now()}`, placeholder: 'Enter password...' },
            url: { label: 'URL Field', key: `url_${Date.now()}`, placeholder: 'Enter URL...' },
            tel: { label: 'Phone Field', key: `tel_${Date.now()}`, placeholder: 'Enter phone number...' },
            date: { label: 'Date Field', key: `date_${Date.now()}` },
            time: { label: 'Time Field', key: `time_${Date.now()}` },
            datetime: { label: 'Date & Time Field', key: `datetime_${Date.now()}` },
            file: { label: 'File Upload', key: `file_${Date.now()}`, accept: '*', multiple: false },
            range: { label: 'Range Slider', key: `range_${Date.now()}`, min: 0, max: 100, step: 1 },
            color: { label: 'Color Picker', key: `color_${Date.now()}` },
            
            // Selection Fields
            select: { label: 'Select Field', key: `select_${Date.now()}`, options: ['Option 1', 'Option 2', 'Option 3'] },
            multiselect: { label: 'Multi Select', key: `multiselect_${Date.now()}`, options: ['Option 1', 'Option 2', 'Option 3'], multiple: true },
            radio: { label: 'Radio Group', key: `radio_${Date.now()}`, options: ['Option 1', 'Option 2'] },
            checkbox: { label: 'Checkbox', key: `checkbox_${Date.now()}` },
            checkboxgroup: { label: 'Checkbox Group', key: `checkboxgroup_${Date.now()}`, options: ['Option 1', 'Option 2', 'Option 3'] },
            toggle: { label: 'Toggle Switch', key: `toggle_${Date.now()}` },
            
            // Content & Layout
            heading: { label: 'Heading', key: `heading_${Date.now()}`, text: 'Heading Text', level: 'h3' },
            text: { label: 'Text Block', key: `text_${Date.now()}`, text: 'Text content goes here...', html: false },
            html: { label: 'HTML Block', key: `html_${Date.now()}`, html: '<p>HTML content goes here...</p>' },
            separator: { label: 'Separator', key: `separator_${Date.now()}`, style: 'solid' },
            spacer: { label: 'Spacer', key: `spacer_${Date.now()}`, height: '20px' },
            image: { label: 'Image', key: `image_${Date.now()}`, src: '', alt: '', width: '', height: '' },
            video: { label: 'Video', key: `video_${Date.now()}`, src: '', width: '100%', height: '300px' },
            
            // Advanced
            hidden: { label: 'Hidden Field', key: `hidden_${Date.now()}`, value: '' },
            rating: { label: 'Star Rating', key: `rating_${Date.now()}`, max: 5, icon: 'star' },
            signature: { label: 'Signature', key: `signature_${Date.now()}`, width: '400px', height: '200px' },
            captcha: { label: 'Captcha', key: `captcha_${Date.now()}`, type: 'simple' },
            calculation: { label: 'Calculation', key: `calculation_${Date.now()}`, formula: '', fields: [] },
            
            // Actions
            submit: { label: 'Submit Button', key: `submit_${Date.now()}`, text: 'Submit', style: 'primary' },
            reset: { label: 'Reset Button', key: `reset_${Date.now()}`, text: 'Reset', style: 'secondary' },
            button: { label: 'Custom Button', key: `button_${Date.now()}`, text: 'Click Me', action: 'custom' }
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
        addField: addNewCanvasField
    };

})(jQuery);
