/**
 * Document Details Modal JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('LIFT Docs Admin Modal: Loaded');
        
        // Handle details button click
        $(document).on('click', '.lift-details-btn', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var data = $button.data();
            
            console.log('Opening modal with data:', data);
            
            // Populate modal with data
            populateModal(data);
            
            // Show modal
            showModal();
        });
        
        // Handle modal close
        $(document).on('click', '.lift-modal-close, .lift-modal-backdrop', function(e) {
            e.preventDefault();
            hideModal();
        });
        
        // Handle copy buttons
        $(document).on('click', '.lift-copy-btn', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var target = $button.data('target');
            var targetText = $button.data('target-text');
            var textToCopy = '';
            
            if (targetText) {
                // Use direct text
                textToCopy = targetText;
            } else if (target) {
                // Use input field value
                var $input = $(target);
                if ($input.length) {
                    textToCopy = $input.val();
                }
            }
            
            if (textToCopy) {
                // Copy to clipboard using modern API if available
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        showCopyFeedback($button);
                    }).catch(function(err) {
                        console.error('Clipboard API failed:', err);
                        fallbackCopy(textToCopy, $button);
                    });
                } else {
                    fallbackCopy(textToCopy, $button);
                }
            }
        });
        
        /**
         * Show copy feedback
         */
        function showCopyFeedback($button) {
            var originalText = $button.text();
            $button.addClass('copied').text(liftDocsAdmin.strings.copied);
            
            setTimeout(function() {
                $button.removeClass('copied').text(originalText);
            }, 2000);
        }
        
        /**
         * Fallback copy method for older browsers
         */
        function fallbackCopy(text, $button) {
            try {
                // Create temporary textarea
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                
                showCopyFeedback($button);
            } catch (err) {
                console.error('Could not copy text: ', err);
                
                // Show error feedback
                var originalText = $button.text();
                $button.addClass('copy-error').text('Error');
                setTimeout(function() {
                    $button.removeClass('copy-error').text(originalText);
                }, 2000);
            }
        }
        
        // Handle ESC key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && $('#lift-document-details-modal').is(':visible')) {
                hideModal();
            }
        });
        
        /**
         * Populate modal with document data
         */
        function populateModal(data) {
            console.log('Populating modal with data:', data);
            
            // Set view URL and preview link
            $('#lift-view-url').val(data.viewUrl || '');
            $('#lift-view-preview').attr('href', data.viewUrl || '#');
            
            // Get files count and parse multiple URLs
            var filesCount = parseInt(data.filesCount) || 0;
            var downloadUrls = [];
            var onlineViewUrls = [];
            var secureDownloadUrls = [];
            
            // Parse JSON data if available
            try {
                if (data.downloadUrls && typeof data.downloadUrls === 'string') {
                    downloadUrls = JSON.parse(data.downloadUrls);
                }
                if (data.onlineViewUrls && typeof data.onlineViewUrls === 'string') {
                    onlineViewUrls = JSON.parse(data.onlineViewUrls);
                }
                if (data.secureDownloadUrls && typeof data.secureDownloadUrls === 'string') {
                    secureDownloadUrls = JSON.parse(data.secureDownloadUrls);
                }
            } catch (e) {
                console.error('Error parsing URLs:', e);
            }
            
            // Set view description
            if (filesCount > 1) {
                $('#lift-view-description').text('📄 Xem trang document với ' + filesCount + ' files được đính kèm').show();
            } else if (filesCount === 1) {
                $('#lift-view-description').text('📄 Xem trang document với 1 file được đính kèm').show();
            } else {
                $('#lift-view-description').text('⚠️ Chưa có file nào được upload').show();
            }
            
            // Handle download URLs
            if (downloadUrls.length > 1) {
                // Multiple files - show list
                $('#lift-single-download').hide();
                $('#lift-multiple-downloads').show();
                
                var downloadHtml = '<div class="multiple-files-list">';
                downloadUrls.forEach(function(fileData, index) {
                    var fileIcon = getFileIcon(fileData.name);
                    downloadHtml += '<div class="file-item" style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #007cba;">';
                    downloadHtml += '<div class="file-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">';
                    downloadHtml += '<span style="font-size: 16px;">' + fileIcon + '</span>';
                    downloadHtml += '<strong style="color: #23282d;">' + (fileData.name || 'File ' + (index + 1)) + '</strong>';
                    downloadHtml += '</div>';
                    downloadHtml += '<div class="lift-input-group">';
                    downloadHtml += '<input type="text" value="' + fileData.url + '" readonly onclick="this.select()" style="font-size: 12px;" />';
                    downloadHtml += '<button type="button" class="button lift-copy-btn" data-target-text="' + fileData.url + '">Copy</button>';
                    
                    // Find corresponding online view URL
                    var onlineViewUrl = onlineViewUrls.find(function(ovData) {
                        return ovData.index === fileData.index;
                    });
                    if (onlineViewUrl && onlineViewUrl.url) {
                        downloadHtml += '<a href="' + onlineViewUrl.url + '" class="button" target="_blank">View Online</a>';
                    }
                    
                    downloadHtml += '</div>';
                    downloadHtml += '</div>';
                });
                downloadHtml += '</div>';
                $('#lift-multiple-downloads').html(downloadHtml);
                
            } else if (downloadUrls.length === 1) {
                // Single file - use single input
                $('#lift-single-download').show();
                $('#lift-multiple-downloads').hide();
                $('#lift-download-url').val(downloadUrls[0].url || '');
                
                // Set online view link for single file
                if (onlineViewUrls.length > 0 && onlineViewUrls[0].url) {
                    $('#lift-online-view').attr('href', onlineViewUrls[0].url).show();
                } else {
                    $('#lift-online-view').hide();
                }
                
            } else {
                // No files
                $('#lift-single-download').show();
                $('#lift-multiple-downloads').hide();
                $('#lift-download-url').val('');
                $('#lift-online-view').hide();
            }
            
            // Handle secure download URLs
            if (secureDownloadUrls.length > 0) {
                $('#lift-secure-download-group').show();
                
                if (secureDownloadUrls.length > 1) {
                    // Multiple secure files
                    $('#lift-single-secure-download').hide();
                    $('#lift-multiple-secure-downloads').show();
                    
                    var secureHtml = '<div class="multiple-files-list">';
                    secureDownloadUrls.forEach(function(fileData, index) {
                        var fileIcon = getFileIcon(fileData.name);
                        secureHtml += '<div class="file-item" style="margin-bottom: 10px; padding: 10px; background: #f0f8ff; border-radius: 4px; border-left: 3px solid #0073aa;">';
                        secureHtml += '<div class="file-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">';
                        secureHtml += '<span style="font-size: 16px;">🔒</span>';
                        secureHtml += '<span style="font-size: 14px;">' + fileIcon + '</span>';
                        secureHtml += '<strong style="color: #0073aa;">' + (fileData.name || 'File ' + (index + 1)) + '</strong>';
                        secureHtml += '</div>';
                        secureHtml += '<div class="lift-input-group">';
                        secureHtml += '<input type="text" value="' + fileData.url + '" readonly onclick="this.select()" style="font-size: 12px;" />';
                        secureHtml += '<button type="button" class="button lift-copy-btn" data-target-text="' + fileData.url + '">Copy Secure</button>';
                        secureHtml += '</div>';
                        secureHtml += '</div>';
                    });
                    secureHtml += '</div>';
                    $('#lift-multiple-secure-downloads').html(secureHtml);
                    
                } else {
                    // Single secure file
                    $('#lift-single-secure-download').show();
                    $('#lift-multiple-secure-downloads').hide();
                    $('#lift-secure-download-url').val(secureDownloadUrls[0].url || '');
                }
            } else {
                $('#lift-secure-download-group').hide();
            }
            
            // Set shortcode
            $('#lift-shortcode').val(data.shortcode || '');
            
            // Set statistics
            $('#lift-views').text(data.views || '0');
            $('#lift-downloads').text(data.downloads || '0');
            $('#lift-file-size').text(data.fileSize || '—');
            $('#lift-files-count').text(filesCount);
        }
        
        /**
         * Get file icon based on file name
         */
        function getFileIcon(fileName) {
            if (!fileName) return '📄';
            
            var ext = fileName.toLowerCase().split('.').pop();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
                return '🖼️';
            } else if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'].includes(ext)) {
                return '🎥';
            } else if (['mp3', 'wav', 'ogg', 'flac', 'aac'].includes(ext)) {
                return '🎵';
            } else if (ext === 'pdf') {
                return '📕';
            } else if (['doc', 'docx'].includes(ext)) {
                return '📘';
            } else if (['xls', 'xlsx'].includes(ext)) {
                return '📗';
            } else if (['ppt', 'pptx'].includes(ext)) {
                return '📙';
            } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
                return '📦';
            } else {
                return '📄';
            }
        }
        
        /**
         * Show modal
         */
        function showModal() {
            $('body').addClass('modal-open');
            $('#lift-modal-backdrop').fadeIn(200);
            $('#lift-document-details-modal').fadeIn(200);
        }
        
        /**
         * Hide modal
         */
        function hideModal() {
            $('body').removeClass('modal-open');
            $('#lift-document-details-modal').fadeOut(200);
            $('#lift-modal-backdrop').fadeOut(200);
        }
        
        /**
         * Add some body styles when modal is open
         */
        $('<style>')
            .prop('type', 'text/css')
            .html('body.modal-open { overflow: hidden; }')
            .appendTo('head');
    });
    
})(jQuery);
