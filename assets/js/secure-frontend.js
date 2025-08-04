/*
 * LIFT Docs System - Secure Frontend JavaScript
 * Shared functionality for secure pages and document forms
 */

// Ensure jQuery is available
if (typeof jQuery === 'undefined') {
    // LIFT Docs: jQuery is required but not loaded
} else {
    jQuery(document).ready(function($) {
        'use strict';

        // Initialize secure frontend features
        var LIFT_SecureFrontend = {

        init: function() {
            this.initDownloadTracking();
            this.initFormValidation();
            this.initAccessibilityFeatures();
            this.initPrintSupport();
        },

        /**
         * Track download events
         */
        initDownloadTracking: function() {
            $('.lift-download-btn').on('click', function(e) {
                var $btn = $(this);
                var fileName = $btn.closest('.file-item').find('.file-name').text();

                // Add loading state
                $btn.addClass('downloading');
                $btn.find('.dashicons').removeClass('dashicons-download').addClass('dashicons-update');

                // Analytics tracking (if available)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'download', {
                        'event_category': 'Document',
                        'event_label': fileName
                    });
                }

                // Remove loading state after a delay
                setTimeout(function() {
                    $btn.removeClass('downloading');
                    $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-download');
                }, 2000);
            });
        },

    /**
     * Enhanced form validation
     */
    initFormValidation: function() {
        // Real-time validation
        $('.form-control').on('blur', function() {
            var $field = $(this);
            var $formField = $field.closest('.form-field');

            // Remove previous validation states
            $formField.removeClass('field-valid field-invalid');

            // Check if field is required and empty
            if ($field.prop('required') && !$field.val().trim()) {
                $formField.addClass('field-invalid');
                return;
            }

            // Email validation
            if ($field.attr('type') === 'email' && $field.val()) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($field.val())) {
                    $formField.addClass('field-invalid');
                    return;
                }
            }

            // URL validation
            if ($field.attr('type') === 'url' && $field.val()) {
                var urlRegex = /^https?:\/\/.+\..+/;
                if (!urlRegex.test($field.val())) {
                    $formField.addClass('field-invalid');
                    return;
                }
            }

            // Mark as valid if we get here
            if ($field.val().trim()) {
                $formField.addClass('field-valid');
            }
        });

        // Form submission validation
        $('#document-form').on('submit', function(e) {
            var $form = $(this);
            var isValid = true;

            // Validate required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                var $formField = $field.closest('.form-field');

                if (!$field.val().trim()) {
                    $formField.addClass('field-invalid');
                    isValid = false;
                } else {
                    $formField.removeClass('field-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                this.showValidationError('Please fill in all required fields.');
                return false;
            }
        });
    },

    /**
     * Accessibility enhancements
     */
    initAccessibilityFeatures: function() {
        // Add ARIA labels to form fields
        $('.form-field').each(function() {
            var $field = $(this);
            var $label = $field.find('label');
            var $input = $field.find('.form-control, input, textarea, select');

            if ($label.length && $input.length) {
                var labelId = 'label-' + Math.random().toString(36).substr(2, 9);
                $label.attr('id', labelId);
                $input.attr('aria-labelledby', labelId);

                // Add required indicator
                if ($input.prop('required')) {
                    $input.attr('aria-required', 'true');
                }
            }
        });

        // Keyboard navigation for file downloads
        $('.lift-download-btn').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this)[0].click();
            }
        });

        // Focus management for modals/alerts
        if (typeof LIFT_Modal !== 'undefined') {
            LIFT_Modal.trapFocus();
        }
    },

    /**
     * Print support
     */
    initPrintSupport: function() {
        // Add print styles dynamically
        var printStyles = `
            @media print {
                .form-actions, .lift-download-btn, .btn { display: none !important; }
                .document-form-wrapper, .lift-docs-custom-layout .container {
                    box-shadow: none; border: 1px solid #ddd;
                }
                body.lift-secure-page { background: #fff !important; padding: 0 !important; }
            }
        `;

        if (!$('#lift-print-styles').length) {
            $('<style id="lift-print-styles">').text(printStyles).appendTo('head');
        }

        // Print button functionality (if present)
        $('.print-document').on('click', function(e) {
            e.preventDefault();
            window.print();
        });
    },

    /**
     * Show validation error message
     */
    showValidationError: function(message) {
        // Remove existing error messages
        $('.validation-error').remove();

        // Create error message
        var $error = $('<div class="notice notice-error validation-error">')
            .text(message)
            .hide()
            .prependTo('.form-content-section')
            .slideDown();

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $error.slideUp(function() {
                $(this).remove();
            });
        }, 5000);

        // Scroll to error
        $('html, body').animate({
            scrollTop: $error.offset().top - 20
        }, 300);
    },

    /**
     * Show success message
     */
    showSuccessMessage: function(message) {
        // Remove existing messages
        $('.validation-success').remove();

        // Create success message
        var $success = $('<div class="notice notice-success validation-success">')
            .text(message)
            .hide()
            .prependTo('.form-content-section')
            .slideDown();

        // Auto-hide after 3 seconds
        setTimeout(function() {
            $success.slideUp(function() {
                $(this).remove();
            });
        }, 3000);
    }

    }; // End of LIFT_SecureFrontend object

    // Initialize the frontend functionality
    LIFT_SecureFrontend.init();

    // Export for use by other scripts
    if (typeof window.LIFT_SecureFrontend === 'undefined') {
        window.LIFT_SecureFrontend = LIFT_SecureFrontend;
    }

    }); // End of jQuery document ready

} // End of jQuery availability check