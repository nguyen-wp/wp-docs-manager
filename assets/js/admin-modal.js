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
            var $input = $(target);
            
            if ($input.length) {
                // Select and copy text
                $input.select();
                $input[0].setSelectionRange(0, 99999); // For mobile
                
                try {
                    document.execCommand('copy');
                    
                    // Show feedback
                    var originalText = $button.text();
                    $button.addClass('copied').text(liftDocsAdmin.strings.copied);
                    
                    setTimeout(function() {
                        $button.removeClass('copied').text(originalText);
                    }, 2000);
                    
                } catch (err) {
                    console.error('Could not copy text: ', err);
                }
            }
        });
        
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
            // Set view URL and preview link
            $('#lift-view-url').val(data.viewUrl || '');
            $('#lift-view-preview').attr('href', data.viewUrl || '#');
            
            // Set download URL
            $('#lift-download-url').val(data.downloadUrl || '');
            
            // Set secure download URL (show/hide based on availability)
            if (data.secureDownloadUrl) {
                $('#lift-secure-download-url').val(data.secureDownloadUrl);
                $('#lift-secure-download-group').show();
            } else {
                $('#lift-secure-download-group').hide();
            }
            
            // Set shortcode
            $('#lift-shortcode').val(data.shortcode || '');
            
            // Set statistics
            $('#lift-views').text(data.views || '0');
            $('#lift-downloads').text(data.downloads || '0');
            $('#lift-file-size').text(data.fileSize || '—');
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
