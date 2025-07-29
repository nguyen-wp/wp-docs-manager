/**
 * LIFT Docs - File Upload and Digital Signature Functionality
 * Enhanced form fields with image preview and online signature capability
 */

(function($) {
    'use strict';

    // Global variables
    let signaturePads = {};
    let imagePreviewElements = {};

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        initializeFileUploads();
        initializeSignatureFields();
        setupFormSubmission();
    });

    /**
     * Initialize file upload fields with image preview
     */
    function initializeFileUploads() {
        $('.lift-form-field.lift-field-file').each(function() {
            const $field = $(this);
            const $input = $field.find('input[type="file"]');
            
            if ($input.length) {
                setupFileUpload($input);
            }
        });
    }

    /**
     * Setup individual file upload field
     */
    function setupFileUpload($input) {
        const fieldId = $input.attr('id');
        const $container = $input.closest('.lift-form-field');
        
        // Create upload container
        const $uploadContainer = $('<div class="file-upload-container">');
        const $dropZone = $('<div class="file-drop-zone">');
        const $previewArea = $('<div class="file-preview-area">');
        const $uploadButton = $('<div class="file-upload-button">');
        
        // Setup drop zone
        $dropZone.html(`
            <div class="drop-zone-content">
                <i class="dashicons dashicons-upload"></i>
                <p class="drop-zone-text">Kéo thả file vào đây hoặc <span class="browse-link">chọn file</span></p>
                <p class="drop-zone-hint">Hỗ trợ: JPG, PNG, PDF, DOC, DOCX (Max: 5MB)</p>
            </div>
        `);
        
        // Setup upload button
        $uploadButton.html(`
            <button type="button" class="btn btn-secondary file-browse-btn">
                <i class="dashicons dashicons-plus"></i>
                Chọn File
            </button>
        `);
        
        // Insert after the original input
        $input.hide();
        $uploadContainer.append($dropZone, $previewArea, $uploadButton);
        $input.after($uploadContainer);
        
        // Bind events
        bindFileUploadEvents($input, $uploadContainer);
    }

    /**
     * Bind file upload events
     */
    function bindFileUploadEvents($input, $container) {
        const $dropZone = $container.find('.file-drop-zone');
        const $previewArea = $container.find('.file-preview-area');
        const $browseBtn = $container.find('.file-browse-btn');
        const $browseLink = $container.find('.browse-link');
        
        // Click to browse
        $browseBtn.add($browseLink).on('click', function(e) {
            e.preventDefault();
            $input.click();
        });
        
        // File input change
        $input.on('change', function() {
            handleFileSelection(this.files, $previewArea, $input);
        });
        
        // Drag and drop
        $dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });
        
        $dropZone.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });
        
        $dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            const files = e.originalEvent.dataTransfer.files;
            handleFileSelection(files, $previewArea, $input);
        });
    }

    /**
     * Handle file selection
     */
    function handleFileSelection(files, $previewArea, $input) {
        if (!files || files.length === 0) return;
        
        const file = files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        // Validate file size
        if (file.size > maxSize) {
            showError('File quá lớn. Kích thước tối đa là 5MB.');
            return;
        }
        
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
            showError('Định dạng file không được hỗ trợ.');
            return;
        }
        
        // Create preview
        createFilePreview(file, $previewArea, $input);
        
        // Trigger upload
        uploadFile(file, $input);
    }

    /**
     * Create file preview
     */
    function createFilePreview(file, $previewArea, $input) {
        $previewArea.empty().show();
        
        const $preview = $('<div class="file-preview-item">');
        const $info = $('<div class="file-info">');
        const $actions = $('<div class="file-actions">');
        
        // File icon and info
        let iconClass = 'dashicons-media-default';
        if (file.type.startsWith('image/')) {
            iconClass = 'dashicons-format-image';
        } else if (file.type === 'application/pdf') {
            iconClass = 'dashicons-pdf';
        } else if (file.type.includes('word')) {
            iconClass = 'dashicons-media-text';
        }
        
        $info.html(`
            <div class="file-icon">
                <i class="dashicons ${iconClass}"></i>
            </div>
            <div class="file-details">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${formatFileSize(file.size)}</div>
                <div class="upload-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <span class="progress-text">Đang tải lên...</span>
                </div>
            </div>
        `);
        
        // Actions
        $actions.html(`
            <button type="button" class="btn btn-secondary btn-sm remove-file">
                <i class="dashicons dashicons-no"></i>
            </button>
        `);
        
        $preview.append($info, $actions);
        $previewArea.append($preview);
        
        // Image preview for image files
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const $imagePreview = $('<div class="image-preview">');
                $imagePreview.html(`<img src="${e.target.result}" alt="Preview">`);
                $preview.prepend($imagePreview);
            };
            reader.readAsDataURL(file);
        }
        
        // Remove file handler
        $actions.find('.remove-file').on('click', function() {
            $preview.remove();
            $input.val('');
            if ($previewArea.children().length === 0) {
                $previewArea.hide();
            }
        });
    }

    /**
     * Upload file via AJAX
     */
    function uploadFile(file, $input) {
        const $preview = $input.closest('.lift-form-field').find('.file-preview-item').last();
        const $progressBar = $preview.find('.progress-fill');
        const $progressText = $preview.find('.progress-text');
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'lift_upload_file');
        formData.append('nonce', liftFormsFrontend.nonce);
        
        $.ajax({
            url: liftFormsFrontend.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $progressBar.css('width', percentComplete + '%');
                        $progressText.text(Math.round(percentComplete) + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $progressText.text('Tải lên thành công!');
                    $progressBar.addClass('success');
                    
                    // Store file URL in hidden input
                    const $hiddenInput = $('<input type="hidden">');
                    $hiddenInput.attr('name', $input.attr('name') + '_url');
                    $hiddenInput.val(response.data.url);
                    $input.after($hiddenInput);
                    
                    // Update preview with download link
                    const $downloadLink = $('<a class="download-link" href="' + response.data.url + '" target="_blank">');
                    $downloadLink.html('<i class="dashicons dashicons-download"></i> Tải xuống');
                    $preview.find('.file-actions').prepend($downloadLink);
                } else {
                    showError(response.data || 'Lỗi tải file lên');
                    $preview.remove();
                }
            },
            error: function() {
                showError('Lỗi kết nối khi tải file lên');
                $preview.remove();
            }
        });
    }

    /**
     * Initialize signature fields
     */
    function initializeSignatureFields() {
        $('.lift-form-field.lift-field-signature').each(function() {
            const $field = $(this);
            setupSignatureField($field);
        });
    }

    /**
     * Setup signature field
     */
    function setupSignatureField($field) {
        const fieldId = $field.find('input').attr('id') || 'signature_' + Date.now();
        
        // Create signature container
        const $container = $('<div class="signature-container">');
        const $canvas = $('<canvas class="signature-canvas">');
        const $actions = $('<div class="signature-actions">');
        const $hiddenInput = $('<input type="hidden">');
        
        // Setup canvas
        $canvas.attr('id', fieldId + '_canvas');
        $canvas.attr('width', 400);
        $canvas.attr('height', 200);
        
        // Setup hidden input
        $hiddenInput.attr('name', $field.find('input').attr('name'));
        $hiddenInput.attr('id', fieldId + '_data');
        
        // Setup actions
        $actions.html(`
            <button type="button" class="btn btn-secondary clear-signature">
                <i class="dashicons dashicons-eraser"></i>
                Xóa chữ ký
            </button>
            <button type="button" class="btn btn-primary save-signature" disabled>
                <i class="dashicons dashicons-yes"></i>
                Lưu chữ ký
            </button>
        `);
        
        // Build container
        $container.append($canvas, $actions, $hiddenInput);
        
        // Replace original input
        $field.find('input').hide();
        $field.append($container);
        
        // Initialize signature pad
        initializeSignaturePad($canvas[0], fieldId, $actions);
    }

    /**
     * Initialize signature pad
     */
    function initializeSignaturePad(canvas, fieldId, $actions) {
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        
        // Set canvas style
        ctx.strokeStyle = '#2c3e50';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        
        // Clear canvas
        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            $actions.find('.save-signature').prop('disabled', true);
            $('#' + fieldId + '_data').val('');
        }
        
        // Draw function
        function draw(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            
            lastX = x;
            lastY = y;
            
            // Enable save button
            $actions.find('.save-signature').prop('disabled', false);
        }
        
        // Mouse events
        $(canvas).on('mousedown', function(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        });
        
        $(canvas).on('mousemove', draw);
        
        $(canvas).on('mouseup mouseout', function() {
            isDrawing = false;
        });
        
        // Touch events for mobile
        $(canvas).on('touchstart', function(e) {
            e.preventDefault();
            const touch = e.originalEvent.touches[0];
            const rect = canvas.getBoundingClientRect();
            lastX = touch.clientX - rect.left;
            lastY = touch.clientY - rect.top;
            isDrawing = true;
        });
        
        $(canvas).on('touchmove', function(e) {
            e.preventDefault();
            if (!isDrawing) return;
            
            const touch = e.originalEvent.touches[0];
            const rect = canvas.getBoundingClientRect();
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            
            lastX = x;
            lastY = y;
            
            $actions.find('.save-signature').prop('disabled', false);
        });
        
        $(canvas).on('touchend', function() {
            isDrawing = false;
        });
        
        // Button events
        $actions.find('.clear-signature').on('click', clearCanvas);
        
        $actions.find('.save-signature').on('click', function() {
            saveSignature(canvas, fieldId);
        });
        
        // Store reference
        signaturePads[fieldId] = {
            canvas: canvas,
            clear: clearCanvas
        };
        
        // Initialize with white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    /**
     * Save signature
     */
    function saveSignature(canvas, fieldId) {
        const dataURL = canvas.toDataURL('image/png');
        
        // Save to server
        $.ajax({
            url: liftFormsFrontend.ajaxurl,
            type: 'POST',
            data: {
                action: 'lift_save_signature',
                signature: dataURL,
                field_id: fieldId,
                nonce: liftFormsFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#' + fieldId + '_data').val(response.data.url);
                    showSuccess('Chữ ký đã được lưu thành công!');
                } else {
                    showError(response.data || 'Lỗi lưu chữ ký');
                }
            },
            error: function() {
                showError('Lỗi kết nối khi lưu chữ ký');
            }
        });
    }

    /**
     * Setup form submission
     */
    function setupFormSubmission() {
        $('.lift-form').on('submit', function(e) {
            // Validate signatures before submit
            let hasInvalidSignature = false;
            
            $('.signature-container').each(function() {
                const $container = $(this);
                const $hiddenInput = $container.find('input[type="hidden"]');
                const $saveBtn = $container.find('.save-signature');
                
                if (!$saveBtn.prop('disabled') && !$hiddenInput.val()) {
                    showError('Vui lòng lưu chữ ký trước khi gửi form');
                    hasInvalidSignature = true;
                    return false;
                }
            });
            
            if (hasInvalidSignature) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Utility functions
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showError(message) {
        // Create or update error message
        let $errorDiv = $('.lift-form-error-message');
        if ($errorDiv.length === 0) {
            $errorDiv = $('<div class="lift-form-error-message alert alert-error">');
            $('.lift-form').prepend($errorDiv);
        }
        $errorDiv.html('<i class="dashicons dashicons-warning"></i> ' + message).show();
        
        // Auto hide after 5 seconds
        setTimeout(function() {
            $errorDiv.fadeOut();
        }, 5000);
    }

    function showSuccess(message) {
        // Create or update success message
        let $successDiv = $('.lift-form-success-message');
        if ($successDiv.length === 0) {
            $successDiv = $('<div class="lift-form-success-message alert alert-success">');
            $('.lift-form').prepend($successDiv);
        }
        $successDiv.html('<i class="dashicons dashicons-yes"></i> ' + message).show();
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            $successDiv.fadeOut();
        }, 3000);
    }

})(jQuery);
