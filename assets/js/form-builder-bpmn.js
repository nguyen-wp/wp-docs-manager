/**
 * Form Builder for WordPress
 * Row/Column based form creation with drag & drop interface
 */
(function($) {
    'use strict';

    let formBuilderInstance = null;
    let currentFormId = 0;
    let formData = [];
    let layoutData = {
        rows: []
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('#form-builder-container').length) {
            initFormBuilder();

            // Expose form builder to global scope for minimal admin access
            window.formBuilder = {
                formData: formData,
                getFormData: function() {
                    return formData;
                },
                setFormData: function(data) {
                    formData = data;
                    updateGlobalFormData();
                }
            };
        }
    });

    /**
     * Update global form data variables
     */
    function updateGlobalFormData() {
        // Prepare data for saving - include both flat fields and layout structure
        const dataToSave = {
            fields: formData,
            layout: layoutData
        };

        window.liftCurrentFormFields = dataToSave;
        if (window.formBuilder) {
            window.formBuilder.formData = dataToSave;
        }
    }

    /**
     * Get form data in proper structure for saving
     */
    function getFormDataForSaving() {
        return {
            layout: buildLayoutStructure(),
            fields: formData
        };
    }

    /**
     * Build layout structure from DOM
     */
    function buildLayoutStructure() {
        const rows = [];

        $('#form-fields-list .form-row').each(function() {
            const rowElement = $(this);
            const rowId = rowElement.data('row-id');
            const columns = [];

            rowElement.find('.form-column').each(function() {
                const columnElement = $(this);
                const columnId = columnElement.data('column-id');
                const fields = [];

                // Get fields in this column
                columnElement.find('.compact-field-item, .form-field-item').each(function() {
                    const fieldElement = $(this);
                    const fieldId = fieldElement.data('field-id') || fieldElement.find('[data-field-id]').data('field-id');

                    if (fieldId) {
                        const fieldData = formData.find(f => f.id === fieldId);
                        if (fieldData) {
                            fields.push(fieldData);
                        }
                    }
                });

                columns.push({
                    id: columnId,
                    width: columnElement.css('flex') || '1',
                    fields: fields
                });
            });

            rows.push({
                id: rowId,
                columns: columns
            });
        });

        return { rows: rows };
    }

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
            createFormBuilderUI();
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
            return;
        }

        // Use HTML form builder with row/column structure
        createFormBuilderUI(existingData);
    }

    /**
     * Create Form Builder UI (HTML based)
     */
    function createFormBuilderUI(existingData = []) {
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
                                <button type="button" class="field-type-btn draggable" data-type="header" draggable="true">
                                    <span class="dashicons dashicons-heading"></span> Header
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="paragraph" draggable="true">
                                    <span class="dashicons dashicons-text-page"></span> Paragraph
                                </button>
                                <button type="button" class="field-type-btn draggable" data-type="signature" draggable="true">
                                    <span class="dashicons dashicons-edit"></span> Signature
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="form-builder-canvas">
                    <div class="form-action-builder">
                        <div class="builder-actions">
                            <button type="button" class="button" id="preview-form">Preview</button>
                            <button type="button" class="button button-secondary" id="clear-form">Clear All</button>
                        </div>
                    </div>

                    <div id="form-header" class="form-action-builder-header">
                        <div class="header-editor-container">
                            <div class="editor-header">
                                <h3>Form Header</h3>
                            </div>
                            <div class="editor-content" id="header-editor-content">
                                <div class="editor-edit" id="header-editor">
                                    <div class="wp-editor-wrap">
                                        <textarea id="form-header-editor" name="form_header" rows="6" class="wp-editor-area" placeholder="Enter form header content..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-fields-area">
                        <div id="form-fields-list">
                            <div class="no-fields-message"><p>No rows added yet. Drag a row layout from the palette to get started.</p></div>
                        </div>
                    </div>

                    <div id="form-footer" class="form-action-builder-footer">
                        <div class="footer-editor-container">
                            <div class="editor-header">
                                <h3>Form Footer</h3>
                            </div>
                            <div class="editor-content" id="footer-editor-content">
                                <div class="editor-edit" id="footer-editor">
                                    <div class="wp-editor-wrap">
                                        <textarea id="form-footer-editor" name="form_footer" rows="6" class="wp-editor-area" placeholder="Enter form footer content..."></textarea>
                                    </div>
                                </div>
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
                                <label>Options</label>
                                <div id="field-options-list">
                                    <!-- Options will be dynamically added here -->
                                </div>
                                <button type="button" class="button" id="add-option-btn">
                                    <span class="dashicons dashicons-plus-alt"></span> Add Option
                                </button>
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

                <!-- Form Preview Modal -->
                <div id="form-preview-modal" class="field-modal" style="display: none;">
                    <div class="modal-content modal-large">
                        <div class="modal-header">
                            <h4>Form Preview</h4>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div id="form-preview-content">
                                <!-- Preview content will be inserted here -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button" id="close-preview">Close Preview</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright-type-buttons">
                Copyright © LIFT Creations
            </div>
        `;

        container.html(builderHTML);

        // Always ensure we have at least one row with one column
        if (layoutData.rows.length === 0) {
            createDefaultRow();
        }

        // Load existing data if any
        if (existingData && existingData.length > 0) {
            // Ensure each field has an ID
            formData = existingData.map(field => {
                if (!field.id) {
                    field.id = 'field-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                }
                return field;
            });

            // If we have form data but no row structure, create default structure
            if ($('#form-fields-list .form-row').length === 0) {
                loadLayout(layoutData);
            }
        } else {
            // Create default row if no existing data
            loadLayout(layoutData);
        }

        // Store form data in global variable for minimal admin access
        updateGlobalFormData();

        // Bind form builder events
        bindFormBuilderEvents();
    }

    /**
     * Bind events for form builder
     */
    function bindFormBuilderEvents() {
        // Field type buttons
        $(document).on('click', '.field-type-btn', function() {
            const fieldType = $(this).data('type');
            addField(fieldType);
        });

        // Edit field
        $(document).on('click', '.edit-field-btn', function() {
            const index = $(this).data('index');
            const fieldId = $(this).data('field-id');

            if (index !== undefined) {
                // Using index-based approach
                editField(index);
            } else if (fieldId !== undefined) {
                // Using field-id based approach
                const fieldIndex = formData.findIndex(f => (f.id || formData.indexOf(f)) == fieldId);
                if (fieldIndex !== -1) {
                    editField(fieldIndex);
                }
            }
        });

        // Delete field
        $(document).on('click', '.delete-field-btn', function() {
            const index = $(this).data('index');
            const fieldId = $(this).data('field-id');

            if (confirm('Are you sure you want to delete this field?')) {
                if (index !== undefined) {
                    // Using index-based approach
                    deleteField(index);
                } else if (fieldId !== undefined) {
                    // Using field-id based approach
                    const fieldIndex = formData.findIndex(f => (f.id || formData.indexOf(f)) == fieldId);
                    if (fieldIndex !== -1) {
                        deleteField(fieldIndex);
                    }
                }
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

        // Option management events
        $(document).on('click', '#add-option-btn', function() {
            addOption();
        });

        $(document).on('click', '.remove-option', function() {
            $(this).closest('.option-item').remove();
            updateOptionIndices();
        });

        $(document).on('click', '.move-option-up', function() {
            const optionItem = $(this).closest('.option-item');
            const prevItem = optionItem.prev('.option-item');
            if (prevItem.length) {
                optionItem.insertBefore(prevItem);
                updateOptionIndices();
            }
        });

        $(document).on('click', '.move-option-down', function() {
            const optionItem = $(this).closest('.option-item');
            const nextItem = optionItem.next('.option-item');
            if (nextItem.length) {
                optionItem.insertAfter(nextItem);
                updateOptionIndices();
            }
        });

        $(document).on('input', '.option-input', function() {
            // Auto-save option changes could be implemented here
        });

        // Column settings modal events
        $(document).on('click', '.modal-close, #cancel-column-settings', function() {
            $('#column-settings-modal').hide();
        });

        $(document).on('click', '#save-column-settings', function() {
            saveColumnSettings();
        });

        // Form preview modal events
        $(document).on('click', '.modal-close, #close-preview', function() {
            $('#form-preview-modal').hide();
        });

        // Clear form
        $(document).on('click', '#clear-form', function() {
            if (confirm('Are you sure you want to clear all fields and rows?')) {
                formData = [];
                // Clear entire container including rows
                $('#form-fields-list').html('<div class="no-fields-message"><p>No fields added yet. Click on field types to add them.</p></div>');
            }
        });

        // Preview form
        $(document).on('click', '#preview-form', function() {
            previewForm();
        });

        // Signature pad events
        $(document).on('click', '.signature-clear', function() {
            const canvasId = $(this).data('target');
            clearSignature(canvasId);
        });

        $(document).on('click', '.signature-undo', function() {
            const canvasId = $(this).data('target');
            undoSignature(canvasId);
        });

        // Initialize signature pads when preview modal is shown
        $(document).on('modal-shown', '#form-preview-modal', function() {
            initSignaturePads();
        });

        // Initialize signature pads when preview is opened
        $(document).on('click', '#preview-form', function() {
            setTimeout(function() {
                initSignaturePads();
            }, 100);
        });

        // Initialize sortable for form fields
        initSortableFields();

        // Re-initialize sortable after field updates
        $(document).on('fields-updated', function() {
            initSortableFields();
        });
    }

    /**
     * Initialize signature pads
     */
    function initSignaturePads() {
        $('.signature-canvas').each(function() {
            const canvas = this;
            const ctx = canvas.getContext('2d');
            let drawing = false;
            let strokes = [];
            let currentStroke = [];

            // Set canvas properties
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // Touch and mouse events
            $(canvas).off('mousedown touchstart').on('mousedown touchstart', function(e) {
                e.preventDefault();
                drawing = true;
                currentStroke = [];
                const pos = getMousePos(canvas, e);
                currentStroke.push(pos);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            });

            $(canvas).off('mousemove touchmove').on('mousemove touchmove', function(e) {
                if (!drawing) return;
                e.preventDefault();
                const pos = getMousePos(canvas, e);
                currentStroke.push(pos);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            });

            $(canvas).off('mouseup touchend').on('mouseup touchend', function(e) {
                if (!drawing) return;
                e.preventDefault();
                drawing = false;
                strokes.push([...currentStroke]);
                updateSignatureData(canvas);
            });

            // Store strokes data on canvas
            canvas.signatureStrokes = strokes;
        });
    }

    /**
     * Get mouse position relative to canvas
     */
    function getMousePos(canvas, e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.type.includes('touch') ? e.originalEvent.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.originalEvent.touches[0].clientY : e.clientY;

        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    /**
     * Clear signature
     */
    function clearSignature(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            canvas.signatureStrokes = [];
            updateSignatureData(canvas);
        }
    }

    /**
     * Undo last stroke
     */
    function undoSignature(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (canvas && canvas.signatureStrokes && canvas.signatureStrokes.length > 0) {
            canvas.signatureStrokes.pop();
            redrawSignature(canvas);
            updateSignatureData(canvas);
        }
    }

    /**
     * Redraw signature from strokes
     */
    function redrawSignature(canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (canvas.signatureStrokes) {
            canvas.signatureStrokes.forEach(stroke => {
                if (stroke.length > 0) {
                    ctx.beginPath();
                    ctx.moveTo(stroke[0].x, stroke[0].y);
                    stroke.forEach(point => {
                        ctx.lineTo(point.x, point.y);
                    });
                    ctx.stroke();
                }
            });
        }
    }

    /**
     * Update signature data in hidden input
     */
    function updateSignatureData(canvas) {
        const dataURL = canvas.toDataURL('image/png');
        const fieldName = canvas.id.replace('_canvas', '');
        const hiddenInput = document.getElementById(fieldName);
        if (hiddenInput) {
            hiddenInput.value = dataURL;
        }
    }

    /**
     * Initialize sortable functionality for form fields - Updated for compact layout
     */
    function initSortableFields() {
        // Check if jQuery UI sortable is available
        if (typeof $.fn.sortable !== 'undefined') {
            $('#form-fields-list').sortable({
                items: '.compact-field-item, .form-field-item',
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
                }
            });

            // Make columns sortable too
            $('.form-column').sortable({
                items: '.compact-field-item, .form-field-item',
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
     * Initialize native HTML5 drag and drop as fallback - Updated for compact layout
     */
    function initNativeDragDrop() {
        let draggedElement = null;

        // Drag start
        $(document).on('dragstart', '.compact-field-item, .form-field-item', function(e) {
            draggedElement = this;
            $(this).addClass('being-dragged');

            // Store the field index and source container
            const index = Array.from(this.parentNode.children).indexOf(this);
            const sourceContainer = $(this).closest('#form-fields-list, .form-column').attr('class') || 'form-fields-list';

            // Find field ID from nested elements
            let fieldId = $(this).data('field-id');
            if (!fieldId) {
                const fieldIdElement = $(this).find('[data-field-id]').first();
                fieldId = fieldIdElement.data('field-id');
            }

            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                index: index,
                sourceContainer: sourceContainer,
                fieldId: fieldId
            }));
        });

        // Drag end
        $(document).on('dragend', '.compact-field-item, .form-field-item', function(e) {
            $(this).removeClass('being-dragged');
            $('.compact-field-item, .form-field-item, .form-column').removeClass('drag-over');
            draggedElement = null;
        });

        // Drag over for field items
        $(document).on('dragover', '.compact-field-item, .form-field-item', function(e) {
            e.preventDefault();
            if (this !== draggedElement) {
                $(this).addClass('drag-over');
            }
        });

        // Drag over for columns
        $(document).on('dragover', '.form-column', function(e) {
            e.preventDefault();
            if (!$(e.target).hasClass('compact-field-item') && !$(e.target).hasClass('form-field-item')) {
                $(this).addClass('drag-over');
            }
        });

        // Drag leave
        $(document).on('dragleave', '.compact-field-item, .form-field-item, .form-column', function(e) {
            // Only remove drag-over if we're really leaving the element
            if (!$.contains(this, e.relatedTarget)) {
                $(this).removeClass('drag-over');
            }
        });

        // Drop on field items
        $(document).on('drop', '.compact-field-item, .form-field-item', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            if (this !== draggedElement) {
                try {
                    const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
                    const dropIndex = Array.from(this.parentNode.children).indexOf(this);

                    // Move the field in the data array
                    moveFieldToPosition(data.index, dropIndex);
                } catch (ex) {
                }
            }
        });

        // Drop on columns
        $(document).on('drop', '.form-column', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            // Only handle drops directly on the column, not on field items within
            if (!$(e.target).hasClass('compact-field-item') && !$(e.target).hasClass('form-field-item')) {
                try {
                    const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));

                    // Append field to this column
                    appendFieldToColumn(data.fieldId, $(this));
                } catch (ex) {
                }
            }
        });

        // Also handle dropping on the main container
        $(document).on('dragover', '#form-fields-list', function(e) {
            e.preventDefault();
        });

        $(document).on('drop', '#form-fields-list', function(e) {
            e.preventDefault();
            $('.compact-field-item, .form-field-item, .form-column').removeClass('drag-over');
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

            // Field position is handled by drag & drop in the UI
            // No need to re-render as we're using row/column structure
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

        // Field position is handled by drag & drop in the UI
        // Trigger update event for other components
        $(document).trigger('fields-updated');
    }

    /**
     * Update field order based on DOM order
     */
    function updateFieldOrder() {
        const newOrder = [];

        $('#form-fields-list .form-row').each(function() {
            $(this).find('.form-column .compact-field-item').each(function() {
                const fieldIdElements = $(this).find('[data-field-id]');
                if (fieldIdElements.length > 0) {
                    const fieldId = fieldIdElements.first().data('field-id');
                    const field = formData.find(f => (f.id || formData.indexOf(f)) == fieldId);
                    if (field) {
                        newOrder.push(field);
                    }
                }
            });
        });

        formData = newOrder;
    }

    /**
     * Add new field
     */
    function addField(type) {
        // Always check if we have row structure - never allow direct field addition
        if ($('#form-fields-list .form-row').length === 0) {
            alert('Please add a row first before adding fields. Drag a row layout from the palette to get started.');
            return;
        }

        // Check if we have any columns to drop fields into
        if ($('#form-fields-list .form-column').length === 0) {
            alert('Please add columns to your rows before adding fields. You need at least one column to place fields.');
            return;
        }

        // Don't create the field yet - user should drag from palette to specific column
        alert('Please drag the field from the palette to a specific column in your form.');
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
            header: 'Header',
            paragraph: 'Paragraph',
            signature: 'Signature'
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

        // Show/hide form fields based on field type
        if (field.type === 'header' || field.type === 'paragraph' || field.type === 'signature') {
            // For header, paragraph, and signature, hide placeholder and required fields
            $('#field-placeholder').closest('.form-field').hide();
            $('#field-required').closest('.form-field').hide();
            if (field.type === 'header' || field.type === 'paragraph') {
                $('#field-name').closest('.form-field').hide();
            } else {
                // Signature still needs a name for form submission
                $('#field-name').closest('.form-field').show();
            }
        } else {
            // Show all fields for other types
            $('#field-placeholder').closest('.form-field').show();
            $('#field-required').closest('.form-field').show();
            $('#field-name').closest('.form-field').show();
        }

        // Show/hide options field based on field type
        if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
            $('.options-field').show();
            populateOptionsField(field.options || []);
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

        // Collect options from dynamic options list
        if ($('.options-field').is(':visible')) {
            field.options = collectOptionsFromField();
        }

        // Update global variable for minimal admin access
        updateGlobalFormData();

        // Update field in-place without re-rendering entire structure
        updateFieldInPlace(field);
        $('#field-edit-modal').hide();
    }

    /**
     * Populate options field with existing options
     */
    function populateOptionsField(options) {
        const container = $('#field-options-list');
        container.empty();

        if (options && options.length > 0) {
            options.forEach((option, index) => {
                addOptionItem(option, index);
            });
        } else {
            // Add two default options
            addOptionItem('Option 1', 0);
            addOptionItem('Option 2', 1);
        }
    }

    /**
     * Add a new option item to the list
     */
    function addOption() {
        const container = $('#field-options-list');
        const index = container.find('.option-item').length;
        addOptionItem(`Option ${index + 1}`, index);
    }

    /**
     * Create and add an option item element
     */
    function addOptionItem(value, index) {
        const container = $('#field-options-list');
        const optionHTML = `
            <div class="option-item" data-index="${index}">
                <input type="text" class="option-input" value="${value}" placeholder="Enter option text">
                <div class="option-actions">
                    <button type="button" class="option-btn move-option-up" title="Move up">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <button type="button" class="option-btn move-option-down" title="Move down">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <button type="button" class="option-btn remove-option" title="Remove option">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `;
        container.append(optionHTML);
        updateOptionIndices();
    }

    /**
     * Update option indices after reordering
     */
    function updateOptionIndices() {
        $('#field-options-list .option-item').each(function(index) {
            $(this).attr('data-index', index);
        });
    }

    /**
     * Collect options from the dynamic options field
     */
    function collectOptionsFromField() {
        const options = [];
        $('#field-options-list .option-input').each(function() {
            const value = $(this).val().trim();
            if (value) {
                options.push(value);
            }
        });
        return options;
    }

    /**
     * Update field in place without destroying row structure - Compact version
     */
    function updateFieldInPlace(field) {
        const fieldElement = $(`.compact-field-item`).filter(function() {
            return $(this).find(`[data-field-id="${field.id}"]`).length > 0;
        });

        if (fieldElement.length) {
            // Replace the entire field element with updated version
            const newFieldHTML = generateFieldPreview(field);
            fieldElement.replaceWith(newFieldHTML);
        }
    }

    /**
     * Delete field - Updated for compact layout
     */
    function deleteField(index) {
        const field = formData[index];
        if (!field) return;

        // Remove from DOM first - find by field ID in compact layout
        const fieldElement = $(`.compact-field-item`).filter(function() {
            return $(this).find(`[data-field-id="${field.id}"]`).length > 0;
        });

        const parentColumn = fieldElement.closest('.form-column');

        fieldElement.remove();

        // Check if parent column is now empty
        if (parentColumn.length && parentColumn.find('.compact-field-item, .form-field-item').length === 0) {
            parentColumn.removeClass('has-fields');
            parentColumn.find('.column-placeholder').show();
        }

        // Remove from data array
        formData.splice(index, 1);

        // Update global variable for minimal admin access
        updateGlobalFormData();

        // If no fields remain, show no fields message
        if (formData.length === 0) {
            // Keep the row structure but show placeholder message in empty columns
            $('#form-fields-list .form-column').each(function() {
                if ($(this).find('.compact-field-item, .form-field-item').length === 0) {
                    $(this).removeClass('has-fields');
                    $(this).find('.column-placeholder').show();
                }
            });
        }
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

        // Update global variable for minimal admin access
        updateGlobalFormData();

        // Visual reordering is handled by drag & drop
    }

    /**
     * Generate field preview HTML - Compact version
     */
    function generateFieldPreview(field) {
        const required = field.required ? ' <span style="color: red;">*</span>' : '';
        const fieldType = field.type.toUpperCase();

        switch (field.type) {
            case 'text':
            case 'email':
            case 'number':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <input type="${field.type}" placeholder="${field.placeholder || field.label}" disabled class="field-compact-input">
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        ${required ? '<span class="required-indicator">Required</span>' : ''}
                    </div>
                `;

            case 'textarea':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <textarea placeholder="${field.placeholder || field.label}" disabled class="field-compact-input" rows="2"></textarea>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        ${required ? '<span class="required-indicator">Required</span>' : ''}
                    </div>
                `;

            case 'select':
                const selectOptions = field.options && field.options.length > 0
                    ? field.options.map(opt => `<option>${opt}</option>`).join('')
                    : '<option>Option 1</option><option>Option 2</option>';
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <select disabled class="field-compact-input">
                                <option>${field.label}</option>
                                ${selectOptions}
                            </select>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        ${required ? '<span class="required-indicator">Required</span>' : ''}
                    </div>
                `;

            case 'radio':
                const radioCount = field.options ? field.options.length : 3;
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <div class="field-compact-input radio-preview">
                                <span class="field-name">${field.label}</span>
                                <span class="options-count">(${radioCount} options)</span>
                            </div>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        ${required ? '<span class="required-indicator">Required</span>' : ''}
                    </div>
                `;

            case 'checkbox':
                const checkboxCount = field.options ? field.options.length : 1;
                if (field.options && field.options.length > 0) {
                    // Multiple checkboxes (checkbox group)
                    return `
                        <div class="compact-field-item">
                            <div class="field-compact-header">
                                <span class="field-drag-handle" title="Drag to move">
                                    <span class="dashicons dashicons-move"></span>
                                </span>
                                <span class="field-type-badge">${fieldType}</span>
                                <div class="field-compact-input checkbox-preview">
                                    <span class="field-name">${field.label}</span>
                                    <span class="options-count">(${checkboxCount} options)</span>
                                </div>
                                <div class="field-compact-actions">
                                    <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                            ${required ? '<span class="required-indicator">Required</span>' : ''}
                        </div>
                    `;
                } else {
                    // Single checkbox
                    return `
                        <div class="compact-field-item">
                            <div class="field-compact-header">
                                <span class="field-drag-handle" title="Drag to move">
                                    <span class="dashicons dashicons-move"></span>
                                </span>
                                <span class="field-type-badge">${fieldType}</span>
                                <div class="field-compact-input checkbox-preview">
                                    <input type="checkbox" disabled> <span class="field-name">${field.label}</span>
                                </div>
                                <div class="field-compact-actions">
                                    <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                            ${required ? '<span class="required-indicator">Required</span>' : ''}
                        </div>
                    `;
                }

            case 'date':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <input type="date" disabled class="field-compact-input" placeholder="${field.label}">
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        ${required ? '<span class="required-indicator">Required</span>' : ''}
                    </div>
                `;

            case 'file':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <div class="field-compact-input file-preview">
                                <span class="dashicons dashicons-upload"></span>
                                <span class="field-name">${field.label}</span>
                            </div>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        ${required ? '<span class="required-indicator">Required</span>' : ''}
                    </div>
                `;

            case 'header':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <div class="field-compact-input header-preview">
                                <span class="dashicons dashicons-heading"></span>
                                <span class="field-name">${field.label}</span>
                            </div>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

            case 'paragraph':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <div class="field-compact-input paragraph-preview">
                                <span class="dashicons dashicons-text-page"></span>
                                <span class="field-name">${field.label}</span>
                            </div>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

            case 'signature':
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">${fieldType}</span>
                            <div class="field-compact-input signature-preview">
                                <span class="dashicons dashicons-edit"></span>
                                <span class="field-name">${field.label}</span>
                            </div>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

            default:
                return `
                    <div class="compact-field-item">
                        <div class="field-compact-header">
                            <span class="field-drag-handle" title="Drag to move">
                                <span class="dashicons dashicons-move"></span>
                            </span>
                            <span class="field-type-badge">UNKNOWN</span>
                            <div class="field-compact-input">
                                <span class="field-name">Unknown field type: ${field.type}</span>
                            </div>
                            <div class="field-compact-actions">
                                <button type="button" class="compact-btn edit-field-btn" data-field-id="${field.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="compact-btn delete-field-btn" data-field-id="${field.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
        }
    }

    /**
     * Generate full form preview HTML with proper formatting
     */
    function generateFullFormPreview(field) {
        const required = field.required ? ' <span style="color: red;">*</span>' : '';

        switch (field.type) {
            case 'text':
            case 'email':
            case 'number':
                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}${required}</label>
                        <input type="${field.type}" id="${field.name}" name="${field.name}" placeholder="${field.placeholder || ''}" class="form-control" />
                    </div>
                `;

            case 'textarea':
                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}${required}</label>
                        <textarea id="${field.name}" name="${field.name}" placeholder="${field.placeholder || ''}" class="form-control" rows="4"></textarea>
                    </div>
                `;

            case 'select':
                const selectOptions = field.options && field.options.length > 0
                    ? field.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')
                    : '<option value="">No options available</option>';
                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}${required}</label>
                        <select id="${field.name}" name="${field.name}" class="form-control">
                            <option value="">Choose an option...</option>
                            ${selectOptions}
                        </select>
                    </div>
                `;

            case 'radio':
                const radioOptions = field.options && field.options.length > 0
                    ? field.options.map((opt, i) =>
                        `<div class="radio-option">
                            <input type="radio" id="${field.name}_${i}" name="${field.name}" value="${opt}" />
                            <label for="${field.name}_${i}">${opt}</label>
                        </div>`
                    ).join('')
                    : '<div class="radio-option"><input type="radio" disabled /> <label>No options available</label></div>';
                return `
                    <div class="form-group">
                        <label>${field.label}${required}</label>
                        <div class="radio-group">
                            ${radioOptions}
                        </div>
                    </div>
                `;

            case 'checkbox':
                if (field.options && field.options.length > 0) {
                    // Multiple checkboxes (checkbox group)
                    const checkboxOptions = field.options.map((opt, i) =>
                        `<div class="checkbox-option">
                            <input type="checkbox" id="${field.name}_${i}" name="${field.name}[]" value="${opt}" />
                            <label for="${field.name}_${i}">${opt}</label>
                        </div>`
                    ).join('');
                    return `
                        <div class="form-group">
                            <label>${field.label}${required}</label>
                            <div class="checkbox-group">
                                ${checkboxOptions}
                            </div>
                        </div>
                    `;
                } else {
                    // Single checkbox
                    return `
                        <div class="form-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="${field.name}" name="${field.name}" value="1" />
                                <label for="${field.name}">${field.label}${required}</label>
                            </div>
                        </div>
                    `;
                }

            case 'date':
                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}${required}</label>
                        <input type="date" id="${field.name}" name="${field.name}" class="form-control" />
                    </div>
                `;

            case 'file':
                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}${required}</label>
                        <input type="file" id="${field.name}" name="${field.name}" class="form-control" />
                    </div>
                `;

            case 'header':
                return `
                    <div class="form-group">
                        <h3 class="form-header">${field.label}</h3>
                    </div>
                `;

            case 'paragraph':
                return `
                    <div class="form-group">
                        <p class="form-paragraph">${field.label}</p>
                    </div>
                `;

            case 'signature':
                return `
                    <div class="form-group">
                        <label for="${field.name}">${field.label}${required}</label>
                        <div class="signature-pad-container">
                            <canvas id="${field.name}_canvas" class="signature-canvas" width="400" height="150"></canvas>
                            <div class="signature-controls">
                                <button type="button" class="btn btn-secondary signature-clear" data-target="${field.name}_canvas">Clear</button>
                                <button type="button" class="btn btn-secondary signature-undo" data-target="${field.name}_canvas">Undo</button>
                            </div>
                            <input type="hidden" id="${field.name}" name="${field.name}" class="signature-data" />
                        </div>
                    </div>
                `;

            default:
                return `
                    <div class="form-group">
                        <label>Unknown field type: ${field.type}</label>
                        <input type="text" disabled class="form-control" value="Unsupported field type" />
                    </div>
                `;
        }
    }

    /**
     * Preview form - Updated to use modal with full formatting
     */
    function previewForm() {
        if (formData.length === 0) {
            alert('No fields to preview. Add some fields first.');
            return;
        }

        // Get header and footer content directly from textareas
        const headerContent = $('#form-header-editor').val() || '';
        const footerContent = $('#form-footer-editor').val() || '';

        let previewHTML = '';

        // Check if we have row/column structure
        if ($('#form-fields-list .form-row').length > 0) {
            // Generate preview with row/column structure
            previewHTML = '<div class="form-preview-container">';

            // Add header if exists
            if (headerContent.trim()) {
                previewHTML += '<div class="form-header-content">' + headerContent + '</div>';
            }

            $('#form-fields-list .form-row').each(function() {
                const rowElement = $(this);
                const columns = rowElement.find('.form-column');

                if (columns.length > 0) {
                    previewHTML += '<div class="preview-row">';

                    columns.each(function() {
                        const columnElement = $(this);
                        const columnFields = [];

                        // Get fields in this column
                        columnElement.find('.compact-field-item, .form-field-item').each(function() {
                            const fieldIdElements = $(this).find('[data-field-id]');
                            if (fieldIdElements.length > 0) {
                                const fieldId = fieldIdElements.first().data('field-id');
                                const field = formData.find(f => (f.id || formData.indexOf(f)) == fieldId);
                                if (field) {
                                    columnFields.push(field);
                                }
                            }
                        });

                        // Calculate column width based on flex or data attributes
                        const columnWidth = Math.floor(100 / columns.length);
                        previewHTML += `<div class="preview-column" style="width: ${columnWidth}%; padding: 0 10px;">`;

                        // Add fields in this column
                        columnFields.forEach(field => {
                            previewHTML += generateFullFormPreview(field);
                        });

                        previewHTML += '</div>';
                    });

                    previewHTML += '</div>';
                }
            });

            // Add footer if exists
            if (footerContent.trim()) {
                previewHTML += '<div class="form-footer-content">' + footerContent + '</div>';
            }

            previewHTML += '</div>';
        }

        // Insert into modal and show
        $('#form-preview-content').html(previewHTML);
        $('#form-preview-modal').show();
    }

    /**
     * Bind save events
     */
    function bindEvents() {
        // Initialize drag and drop
        initDragAndDrop();

        // Save form - ensure we remove any existing handlers first
        $(document).off('click.form-builder', '#save-form').on('click.form-builder', '#save-form', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Prevent other handlers from running
            saveForm();
        });

        // Clear error styling when user starts typing in form name
        $(document).on('input', '#form-name', function() {
            $(this).removeClass('error');
            $('.lift-form-message.error').fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Auto-save every 30 seconds - DISABLED for debugging
        // setInterval(function() {
        //     if (formData.length > 0) {
        //         saveForm(true); // Silent save
        //     }
        // }, 30000);

        // Header and Footer Editor Events
        bindHeaderFooterEvents();
    }

    /**
     * Save form data
     */
    function saveForm(silent = false) {
        // Enhanced validation for form name (skip for silent saves like auto-save)
        if (!silent) {
            const formName = $('#form-name').val().trim();

            if (!formName) {
                showFormMessage('Please enter a form name before saving.', 'error');
                $('#form-name').focus().addClass('error');
                return;
            }

            // Check minimum length
            if (formName.length < 3) {
                showFormMessage('Form name must be at least 3 characters long.', 'error');
                $('#form-name').focus().addClass('error');
                return;
            }

            // Check for valid characters
            const validNamePattern = /^[a-zA-Z0-9\s\-_.()]+$/;
            if (!validNamePattern.test(formName)) {
                showFormMessage('Form name contains invalid characters. Please use only letters, numbers, spaces, and basic punctuation.', 'error');
                $('#form-name').focus().addClass('error');
                return;
            }

            // Remove error styling if validation passes
            $('#form-name').removeClass('error');

            $('.lift-save-indicator').text('Saving...').show();
        }

        let saveData;

        if (formBuilderInstance && typeof formBuilderInstance.formData === 'function') {
            // FormBuilder library data
            saveData = formBuilderInstance.formData();

            // Store in global variable for minimal admin access
            updateGlobalFormData();
        } else {
            // Form builder data - use proper structure
            saveData = getFormDataForSaving();

            // Store in global variable for minimal admin access
            updateGlobalFormData();
        }

        // Get header and footer data
        const headerFooterData = getHeaderFooterData();

        $.ajax({
            url: (window.liftFormBuilder && liftFormBuilder.ajaxurl) || ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'lift_forms_save', // Use consistent action name
                nonce: (window.liftFormBuilder && liftFormBuilder.nonce) || '',
                form_id: currentFormId,
                name: $('#form-name').val() || 'Untitled Form',
                description: $('#form-description').val() || '',
                fields: typeof saveData === 'string' ? saveData : JSON.stringify(saveData),
                settings: JSON.stringify({}),
                form_header: headerFooterData.form_header,
                form_footer: headerFooterData.form_footer
            },
            success: function(response) {
                if (response.success) {
                    if (!silent) {
                        $('.lift-save-indicator').text('Saved').removeClass('error');
                        setTimeout(() => $('.lift-save-indicator').fadeOut(), 2000);

                        // Show success message
                        showFormMessage('Form saved successfully!', 'success');
                    }

                    if (response.data && response.data.form_id && !currentFormId) {
                        currentFormId = response.data.form_id;
                        $('#form-id').val(currentFormId);

                        // For new forms, redirect to edit page after showing message
                        if (!silent) {
                            showFormMessage('Form created successfully! Redirecting to edit page...', 'success');
                            setTimeout(function() {
                                // Build correct edit URL without post_type parameter
                                const baseUrl = '/wp-admin/admin.php';
                                const editUrl = baseUrl + '?page=lift-forms-builder&id=' + response.data.form_id + '&created=1';

                                // Redirect to correct URL
                                try {
                                    window.location.href = editUrl;
                                } catch (error) {
                                    // Fallback: reload current page with form ID
                                    window.location.reload();
                                }
                            }, 1500);
                        }
                    }
                } else {
                    if (!silent) {
                        $('.lift-save-indicator').text('Save failed').addClass('error');
                        showFormMessage(response.data || 'Error saving form', 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                if (!silent) {
                    $('.lift-save-indicator').text('Save failed').addClass('error');
                }
            }
        });
    }

    /**
     * Load form data
     */
    function loadFormData(formId) {
        $.ajax({
            url: (window.liftFormBuilder && liftFormBuilder.ajaxurl) || ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'lift_forms_get',
                nonce: (window.liftFormBuilder && liftFormBuilder.nonce) || '',
                form_id: formId
            },
            success: function(response) {

                if (response.success && response.data.form_fields) {
                    try {
                        let loadedData;

                        if (typeof response.data.form_fields === 'string') {
                            // Try to clean the string first
                            let cleanString = response.data.form_fields.trim();
                            loadedData = JSON.parse(cleanString);
                        } else {
                            loadedData = response.data.form_fields;
                        }

                        // Handle different data structures
                        if (loadedData.layout && loadedData.layout.rows && loadedData.layout.rows.length > 0) {
                            // New structure with layout and actual rows
                            layoutData = loadedData.layout;
                            formData = loadedData.fields || [];

                            // Create the form builder UI first, then load the layout
                            createFormBuilderUI();

                            loadLayout(loadedData.layout);
                        } else if (loadedData.fields && loadedData.fields.length > 0) {
                            // Structure has fields but empty/missing layout - recreate layout with fields
                            formData = loadedData.fields || [];

                            // Create default row structure and place fields in first column
                            createDefaultRow();

                            // Add fields to the first column
                            setTimeout(() => {
                                const firstColumn = $('#form-fields-list .form-column').first();
                                if (firstColumn.length && formData.length > 0) {
                                    firstColumn.find('.column-placeholder').hide();

                                    formData.forEach(field => {
                                        if (field && field.type) {
                                            // Ensure field has ID
                                            if (!field.id) {
                                                field.id = 'field-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                                            }
                                            const fieldHTML = generateFieldPreview(field);
                                            firstColumn.append(fieldHTML);
                                        }
                                    });
                                    firstColumn.addClass('has-fields');

                                    // Update layout data to reflect the actual structure
                                    updateLayoutFromDOM();
                                } else {
                                    // Could not find first column or no fields to add
                                }
                            }, 100);

                        } else if (Array.isArray(loadedData)) {
                            // Old flat structure - convert to row/column layout
                            formData = loadedData;
                            createDefaultRow();
                            createFormBuilderUI(loadedData);
                        } else {
                            // Create default structure
                            createDefaultRow();
                            createFormBuilderUI(loadedData);
                        }

                        updateGlobalFormData();

                        // Load header and footer data
                        const headerContent = response.data.form_header || '';
                        const footerContent = response.data.form_footer || '';
                        loadHeaderFooterData(headerContent, footerContent);

                    } catch (error) {
                        // JSON parse error - create default UI
                        createFormBuilderUI();
                    }
                } else {
                    createFormBuilderUI();
                }
            },
            error: function(xhr, status, error) {
                createFormBuilderUI();
            }
        });
    }

    /**
     * Load layout with rows and columns
     */
    function loadLayout(layout) {
        const container = $('#form-fields-list');
        container.html(''); // Clear existing content

        if (!layout || !layout.rows || layout.rows.length === 0) {
            container.html('<div class="no-fields-message"><p>No rows added yet. Drag a row layout from the palette to get started.</p></div>');
            return;
        }

        layout.rows.forEach((row, rowIndex) => {
            if (!row.columns || row.columns.length === 0) {
                return;
            }

            // Create row HTML
            let rowHTML = `<div class="form-row" data-row-id="${row.id}" draggable="true">`;

            // Add row drag handle
            rowHTML += `
                <div class="row-drag-handle" title="Drag to reorder row">
                    ⋮⋮
                </div>
            `;

            // Add row controls
            rowHTML += `
                <div class="row-controls">
                    <button type="button" class="row-control-btn" title="Add Column" onclick="addColumn('${row.id}')">
                        <span class="dashicons dashicons-plus-alt"></span> Col
                    </button>
                    <button type="button" class="row-control-btn" title="Remove Column" onclick="removeColumn('${row.id}')">
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

            // Add columns
            row.columns.forEach((column, columnIndex) => {
                rowHTML += `
                    <div class="form-column" data-column-id="${column.id}" style="flex: ${column.width || '1'}; position: relative;">
                        <div class="column-header">
                            <span class="column-title">Column ${columnIndex + 1}</span>
                            <div class="column-actions">
                                <select class="column-width-selector" onchange="changeColumnWidth('${column.id}', this.value)">
                                    <option value="1">Auto</option>
                                    <option value="0.16">16.67% (1/6)</option>
                                    <option value="0.25">25% (1/4)</option>
                                    <option value="0.33">33.33% (1/3)</option>
                                    <option value="0.5">50% (1/2)</option>
                                    <option value="0.66">66.67% (2/3)</option>
                                    <option value="0.75">75% (3/4)</option>
                                    <option value="0.83">83.33% (5/6)</option>
                                    <option value="1">100%</option>
                                </select>
                            </div>
                        </div>
                        <div class="column-content">
                `;

                // Add fields to column
                if (column.fields && Array.isArray(column.fields) && column.fields.length > 0) {
                    column.fields.forEach((field, fieldIndex) => {
                        if (field && field.type) {
                            const fieldHTML = generateFieldPreview(field);
                            rowHTML += fieldHTML;
                        }
                    });
                    rowHTML += '</div>'; // Close column-content
                } else {
                    rowHTML += '<div class="column-placeholder">Drop fields here</div></div>';
                }

                rowHTML += '</div>'; // Close form-column
            });

            rowHTML += '</div>'; // Close form-row

            // Try to re-select container if it's somehow lost
            let workingContainer = container;
            if (!workingContainer || workingContainer.length === 0) {
                workingContainer = $('#form-fields-list');
            }

            workingContainer.append(rowHTML);
        });

        // Initialize drag and drop and other events
        try {
            if (typeof bindRowEvents === 'function') {
                bindRowEvents();
            }
        } catch (error) {
            // Error in bindRowEvents
        }

        try {
            if (typeof initColumnResize === 'function') {
                initColumnResize();
            }
        } catch (error) {
            // Error in initColumnResize
        }

        try {
            if (typeof initDragAndDrop === 'function') {
                initDragAndDrop();
            }
        } catch (error) {
            // Error in initDragAndDrop
        }
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
        $(document).off('dragstart.fieldDrag').on('dragstart.fieldDrag', '.form-field-item[draggable="true"], .compact-field-item[draggable="true"]', function(e) {
            draggedElement = this;

            // Find field ID from nested elements for compact layout
            let fieldId = $(this).data('field-id');
            if (!fieldId) {
                const fieldIdElement = $(this).find('[data-field-id]').first();
                fieldId = fieldIdElement.data('field-id');
            }

            draggedData = {
                fieldId: fieldId,
                source: 'canvas-field'
            };

            $(this).addClass('drag-ghost');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', '');
        });

        $(document).off('dragend.fieldDrag').on('dragend.fieldDrag', '.form-field-item[draggable="true"], .compact-field-item[draggable="true"]', function(e) {
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

        updateGlobalFormData();

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
        const fieldHTML = generateFieldPreview(fieldData);

        column.find('.column-placeholder').hide();
        column.addClass('has-fields');
        column.append(fieldHTML);

        // Make the field draggable
        column.find('.compact-field-item').last().attr('draggable', 'true');

        // Add to formData
        formData.push(fieldData);

        // Update global data
        updateGlobalFormData();

        // Auto-open edit modal for new field
        setTimeout(() => {
            const fieldIndex = formData.findIndex(f => f.id === fieldData.id);
            if (fieldIndex !== -1) {
                editField(fieldIndex);
            }
        }, 100);
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
            label: getDefaultLabel(fieldType)
        };

        // Add type-specific properties
        if (fieldType === 'header' || fieldType === 'paragraph') {
            // Header and paragraph don't need name, placeholder, or required
            fieldData.content = fieldData.label; // Use content instead of label for display
        } else if (fieldType === 'signature') {
            // Signature needs a name but not placeholder or required
            fieldData.name = fieldType + '_' + Date.now();
        } else {
            // Regular form fields
            fieldData.name = fieldType + '_' + Date.now();
            fieldData.required = false;
            fieldData.placeholder = '';
        }

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

       /**
     * Show form message
     */
    function showFormMessage(message, type) {
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

    /**
     * Create a default row with one column if no layout exists
     */
    function createDefaultRow() {
        const rowId = 'row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        const columnId = 'col-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        layoutData.rows = [{
            id: rowId,
            columns: [{
                id: columnId,
                width: '1',
                fields: []
            }]
        }];
    }

    /**
     * Bind Header and Footer Editor Events
     */
    function bindHeaderFooterEvents() {
        // No toggle events needed since editors are always visible
    }

    /**
     * Get Header and Footer Content for Saving
     */
    function getHeaderFooterData() {
        return {
            form_header: $('#form-header-editor').val() || '',
            form_footer: $('#form-footer-editor').val() || ''
        };
    }

    /**
     * Load Header and Footer Content
     */
    function loadHeaderFooterData(headerContent, footerContent) {
        // Load header directly into textarea
        $('#form-header-editor').val(headerContent || '');

        // Load footer directly into textarea
        $('#form-footer-editor').val(footerContent || '');
    }

    // Expose debug function globally for testing
    // window.debugFormData = debugFormData;

})(jQuery);
