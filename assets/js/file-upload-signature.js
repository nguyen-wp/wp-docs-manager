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
        // Add a small delay to ensure all CSS is loaded
        setTimeout(function() {
            initializeFileUploads();
            initializeSignatureFields();
            setupFormSubmission();
            
            // Debug logging
            if (typeof console !== 'undefined') {
                console.log('LIFT Forms: File upload and signature functionality initialized');
                console.log('Found file fields:', $('.lift-form-field.lift-field-file').length);
                console.log('Found signature fields:', $('.lift-form-field.lift-field-signature').length);
                
                // Check for existing files
                $('.lift-form-field.lift-field-file').each(function(index) {
                    const $field = $(this);
                    const $currentFile = $field.find('.current-file');
                    console.log('File field ' + index + ':', $field);
                    console.log('Current file element:', $currentFile);
                    console.log('Current file text:', $currentFile.text());
                });
            }
        }, 100);
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
                
                // Check for existing file data
                const $currentFile = $field.find('.current-file');
                if ($currentFile.length && $currentFile.text().trim()) {
                    loadExistingFileData($field, $currentFile.text());
                }
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
     * Load existing file data
     */
    function loadExistingFileData($field, currentFileText) {
        const $container = $field.find('.file-upload-container');
        const $previewArea = $container.find('.file-preview-area');
        const $input = $field.find('input[type="file"]');
        
        if (!$container.length || !$previewArea.length) return;
        
        // Extract filename from the current file text
        const filename = currentFileText.replace('Current file: ', '').trim();
        
        // Debug logging
        console.log('Loading existing file:', filename);
        console.log('Current file text:', currentFileText);
        
        // Try to get file URL from hidden input if it exists
        const $hiddenInput = $field.find('input[type="hidden"][name$="_url"]');
        let fileUrl = '';
        
        if ($hiddenInput.length) {
            fileUrl = $hiddenInput.val();
            console.log('Found hidden input URL:', fileUrl);
        } else {
            // Check if filename is already a full URL
            if (filename.startsWith('http://') || filename.startsWith('https://')) {
                fileUrl = filename;
                console.log('Filename appears to be a full URL:', fileUrl);
            } else if (filename.startsWith('/wp-content/uploads/')) {
                // Relative path from wp-content/uploads
                fileUrl = window.location.origin + filename;
                console.log('Constructed full URL from relative path:', fileUrl);
            } else if (filename.includes('/')) {
                // Might be a relative path, try to construct full URL
                const uploadDir = liftFormsFrontend.uploadDir || '';
                if (uploadDir) {
                    fileUrl = uploadDir + '/' + filename;
                } else {
                    // Fallback to wp-content/uploads
                    fileUrl = window.location.origin + '/wp-content/uploads/' + filename;
                }
                console.log('Constructed URL from path:', fileUrl);
            } else {
                // Just a filename, construct full path
                const uploadDir = liftFormsFrontend.uploadDir || '';
                if (uploadDir) {
                    fileUrl = uploadDir + '/' + filename;
                } else {
                    // Fallback to wp-content/uploads
                    fileUrl = window.location.origin + '/wp-content/uploads/' + filename;
                }
                console.log('Constructed URL from filename:', fileUrl, 'from uploadDir:', uploadDir);
            }
        }
        
        if (filename && fileUrl) {
            // Create preview for existing file
            const $preview = $('<div class="file-preview-item existing-file">');
            const $info = $('<div class="file-info">');
            const $actions = $('<div class="file-actions">');
            
            // Determine file type and icon
            let iconClass = 'dashicons-media-default';
            const fileExt = filename.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                iconClass = 'dashicons-format-image';
            } else if (fileExt === 'pdf') {
                iconClass = 'dashicons-pdf';
            } else if (['doc', 'docx'].includes(fileExt)) {
                iconClass = 'dashicons-media-text';
            }
            
            $info.html(`
                <div class="file-icon">
                    <i class="dashicons ${iconClass}"></i>
                </div>
                <div class="file-details">
                    <div class="file-name">${filename}</div>
                    <div class="file-status">Đã tải lên</div>
                </div>
            `);
            
            $actions.html(`
                <a class="download-link" href="${fileUrl}" target="_blank">
                    <i class="dashicons dashicons-download"></i> Tải xuống
                </a>
                <button type="button" class="btn btn-secondary btn-sm remove-file">
                    <i class="dashicons dashicons-no"></i>
                </button>
            `);
            
            $preview.append($info, $actions);
            $previewArea.append($preview).show();
            
            // Add image preview for image files
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                const $imagePreview = $('<div class="image-preview">');
                const $img = $('<img alt="Preview">');
                
                $img.on('load', function() {
                    console.log('Image loaded successfully:', fileUrl);
                });
                
                $img.on('error', function() {
                    console.warn('Failed to load image:', fileUrl);
                    $imagePreview.html('<div class="image-error"><i class="dashicons dashicons-warning"></i> Could not load image preview</div>');
                });
                
                $img.attr('src', fileUrl);
                $imagePreview.append($img);
                $preview.prepend($imagePreview);
            }
            
            // Hide current file text
            $field.find('.current-file').hide();
            
            // Bind remove event
            $actions.find('.remove-file').on('click', function() {
                $preview.remove();
                $input.val('');
                if ($hiddenInput.length) {
                    $hiddenInput.remove();
                }
                if ($previewArea.children().length === 0) {
                    $previewArea.hide();
                }
                $field.find('.current-file').show();
            });
        }
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
            
            // Check for existing signature data
            const $currentSignature = $field.find('.current-signature img');
            if ($currentSignature.length) {
                loadExistingSignatureData($field, $currentSignature.attr('src'));
            }
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
     * Load existing signature data
     */
    function loadExistingSignatureData($field, signatureUrl) {
        const $container = $field.find('.signature-container');
        const $canvas = $container.find('.signature-canvas');
        const $hiddenInput = $container.find('input[type="hidden"]');
        const $actions = $container.find('.signature-actions');
        
        if (!$container.length || !$canvas.length || !signatureUrl) return;
        
        // Load signature image onto canvas
        const canvas = $canvas[0];
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = function() {
            // Clear canvas and set white background
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Draw the signature image
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            
            // Set the hidden input value
            $hiddenInput.val(signatureUrl);
            
            // Enable the save button (since signature exists)
            $actions.find('.save-signature').prop('disabled', false);
        };
        
        img.onerror = function() {
            console.warn('Could not load existing signature image:', signatureUrl);
        };
        
        img.src = signatureUrl;
        
        // Hide the current signature display
        $field.find('.current-signature').hide();
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
