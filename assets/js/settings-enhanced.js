/**
 * Enhanced Settings Page JavaScript for LIFT Docs System
 * Modern WordPress Admin Interface Interactions
 */
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('LIFT Docs Settings: Enhanced JavaScript loaded');
    
    // Initialize enhanced settings
    initEnhancedSettings();
    
    function initEnhancedSettings() {
        // Add enhanced body class
        $('body').addClass('lift-enhanced-settings');
        
        // Enhanced tab functionality
        initEnhancedTabs();
        
        // Enhanced form controls
        initEnhancedFormControls();
        
        // Enhanced media uploader
        initEnhancedMediaUploader();
        
        // Enhanced color pickers
        initEnhancedColorPickers();
        
        // Enhanced tooltips and help
        initEnhancedTooltips();
        
        // Enhanced form validation
        initEnhancedFormValidation();
        
        // Enhanced save feedback
        initEnhancedSaveFeedback();
    }
    
    function initEnhancedTabs() {
        console.log('Initializing enhanced tabs...');
        
        // Get current tab from URL or default to general
        var urlParams = new URLSearchParams(window.location.search);
        var currentTab = urlParams.get('tab') || 'general';
        
        // Function to switch to a specific tab with enhanced animations
        function switchToTab(tabName) {
            console.log('Switching to tab:', tabName);
            
            // Remove active class from all tabs and content
            $('.lift-nav-tab').removeClass('nav-tab-active');
            $('.lift-tab-content').removeClass('active');
            
            // Add loading state
            $('.lift-nav-tab[data-tab="' + tabName + '"]').addClass('loading');
            
            // Hide all tab content with fade out
            $('.lift-tab-content').fadeOut(200, function() {
                // Add active class to target tab
                $('.lift-nav-tab[data-tab="' + tabName + '"]').addClass('nav-tab-active').removeClass('loading');
                
                // Show target content with fade in
                $('#' + tabName + '-tab').fadeIn(300).addClass('active');
                
                // Trigger custom event for tab switch
                $(document).trigger('liftTabSwitch', [tabName]);
            });
        }
        
        // Handle tab switching with enhanced UX
        $('.nav-tab-js').off('click').on('click', function(e) {
            e.preventDefault();
            
            var $clickedTab = $(this);
            var targetTab = $clickedTab.data('tab');
            
            // Don't switch if already active
            if ($clickedTab.hasClass('nav-tab-active')) {
                return;
            }
            
            switchToTab(targetTab);
            
            // Update URL without page reload
            var newUrl = window.location.origin + window.location.pathname + '?post_type=lift_document&page=lift-docs-settings&tab=' + targetTab;
            window.history.pushState({tab: targetTab}, '', newUrl);
        });
        
        // Handle browser back/forward
        $(window).off('popstate').on('popstate', function(event) {
            if (event.originalEvent.state && event.originalEvent.state.tab) {
                switchToTab(event.originalEvent.state.tab);
            } else {
                // Fallback to URL parameter
                var urlParams = new URLSearchParams(window.location.search);
                var tab = urlParams.get('tab') || 'general';
                switchToTab(tab);
            }
        });
        
        // Initialize - hide all tabs first, then show the correct one
        $('.lift-tab-content').hide().removeClass('active');
        $('.lift-nav-tab').removeClass('nav-tab-active');
        
        // Switch to the current tab (from URL or default)
        switchToTab(currentTab);
        
        // Add smooth hover effects
        $('.lift-nav-tab').hover(
            function() {
                if (!$(this).hasClass('nav-tab-active')) {
                    $(this).stop().animate({opacity: 0.8}, 200);
                }
            },
            function() {
                if (!$(this).hasClass('nav-tab-active')) {
                    $(this).stop().animate({opacity: 1}, 200);
                }
            }
        );
    }
    
    function initEnhancedFormControls() {
        console.log('Initializing enhanced form controls...');
        
        // Enhanced form control interactions
        $(document).off('focus blur', '.lift-form-control').on('focus', '.lift-form-control', function() {
            $(this).closest('td').addClass('focused');
            $(this).addClass('lift-form-control-focused');
        }).on('blur', '.lift-form-control', function() {
            $(this).closest('td').removeClass('focused');
            $(this).removeClass('lift-form-control-focused');
        });
        
        // Enhanced checkbox interactions
        $(document).off('change', '.lift-checkbox').on('change', '.lift-checkbox', function() {
            var $wrapper = $(this).closest('.lift-checkbox-wrapper');
            var $label = $(this).siblings('.lift-checkbox-label');
            
            if ($(this).is(':checked')) {
                $wrapper.addClass('checked');
                $label.addClass('checked');
                
                // Add animation
                $(this).addClass('lift-checkbox-animate');
                setTimeout(() => {
                    $(this).removeClass('lift-checkbox-animate');
                }, 300);
            } else {
                $wrapper.removeClass('checked');
                $label.removeClass('checked');
            }
        });
        
        // Initialize checkbox states
        $('.lift-checkbox:checked').each(function() {
            $(this).closest('.lift-checkbox-wrapper').addClass('checked');
            $(this).siblings('.lift-checkbox-label').addClass('checked');
        });
        
        // Enhanced select interactions
        $(document).off('change', '.lift-form-select').on('change', '.lift-form-select', function() {
            $(this).addClass('lift-select-changed');
            setTimeout(() => {
                $(this).removeClass('lift-select-changed');
            }, 500);
        });
    }
    
    function initEnhancedMediaUploader() {
        console.log('Initializing enhanced media uploader...');
        
        // Enhanced media upload button styling
        $('.button[id*="upload"]').each(function() {
            if (!$(this).hasClass('lift-button-enhanced')) {
                $(this).addClass('lift-button lift-button-secondary lift-button-enhanced');
                
                // Add icon if not present
                if (!$(this).find('i').length) {
                    $(this).prepend('<i class="fas fa-upload"></i> ');
                }
            }
        });
    }
    
    function initEnhancedColorPickers() {
        console.log('Initializing enhanced color pickers...');
        
        // Enhance existing color pickers
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.color-picker').each(function() {
                if (!$(this).hasClass('wp-color-picker')) {
                    $(this).addClass('lift-color-picker').wpColorPicker({
                        change: function(event, ui) {
                            // Add change animation
                            $(this).addClass('lift-color-changed');
                            setTimeout(() => {
                                $(this).removeClass('lift-color-changed');
                            }, 500);
                        }
                    });
                }
            });
        }
    }
    
    function initEnhancedTooltips() {
        console.log('Initializing enhanced tooltips...');
        
        // Add enhanced tooltips to form labels
        $('.form-table th label').each(function() {
            var $this = $(this);
            if (!$this.attr('title') && $this.siblings('.lift-description').length) {
                var description = $this.siblings('.lift-description').text();
                if (description.length > 0 && description.length < 100) {
                    $this.attr('title', description);
                }
            }
        });
        
        // Add help icons to complex fields
        $('.lift-description').each(function() {
            var $this = $(this);
            if ($this.text().length > 100 && !$this.find('.lift-help-icon').length) {
                $this.prepend('<i class="fas fa-info-circle lift-help-icon"></i> ');
            }
        });
    }
    
    function initEnhancedFormValidation() {
        console.log('Initializing enhanced form validation...');
        
        // Real-time validation for number fields
        $(document).off('input', 'input[type="number"].lift-form-control').on('input', 'input[type="number"].lift-form-control', function() {
            var $this = $(this);
            var value = parseFloat($this.val());
            var min = parseFloat($this.attr('min'));
            var max = parseFloat($this.attr('max'));
            
            $this.removeClass('lift-form-error lift-form-warning');
            
            if (!isNaN(min) && value < min) {
                $this.addClass('lift-form-error');
            } else if (!isNaN(max) && value > max) {
                $this.addClass('lift-form-error');
            } else if (!isNaN(value)) {
                $this.addClass('lift-form-valid');
                setTimeout(() => {
                    $this.removeClass('lift-form-valid');
                }, 1000);
            }
        });
        
        // Enhanced required field validation
        $('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).hasClass('lift-form-control')) {
                $(this).addClass('lift-form-control');
            }
            $(this).addClass('lift-form-required');
        });
    }
    
    function initEnhancedSaveFeedback() {
        console.log('Initializing enhanced save feedback...');
        
        // Enhanced form submission feedback
        $('form').off('submit.liftEnhanced').on('submit.liftEnhanced', function() {
            var $form = $(this);
            var $submitBtn = $form.find('.button-primary');
            
            // Add loading state
            $submitBtn.addClass('lift-loading').prop('disabled', true);
            
            // Change button text
            var originalText = $submitBtn.text();
            $submitBtn.text('Saving...');
            
            // Add progress indicator
            if (!$form.find('.lift-save-progress').length) {
                $form.append('<div class="lift-save-progress"><div class="lift-progress-bar"></div></div>');
            }
            
            // Animate progress bar
            var $progressBar = $('.lift-progress-bar');
            $progressBar.css('width', '0%').animate({width: '100%'}, 2000);
            
            // Re-enable after 5 seconds as fallback
            setTimeout(function() {
                $submitBtn.removeClass('lift-loading').prop('disabled', false).text(originalText);
                $('.lift-save-progress').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        });
        
        // Check for WordPress admin notices and enhance them
        setTimeout(function() {
            $('.notice.notice-success').each(function() {
                if (!$(this).hasClass('lift-notice-enhanced')) {
                    $(this).addClass('lift-notice-enhanced lift-success-box');
                    $(this).prepend('<i class="fas fa-check-circle"></i>');
                }
            });
            
            $('.notice.notice-error').each(function() {
                if (!$(this).hasClass('lift-notice-enhanced')) {
                    $(this).addClass('lift-notice-enhanced lift-error-box');
                    $(this).prepend('<i class="fas fa-exclamation-triangle"></i>');
                }
            });
        }, 500);
    }
    
    // Custom events for extensibility
    $(document).on('liftTabSwitch', function(event, tabName) {
        console.log('Tab switched to:', tabName);
        
        // Trigger any tab-specific initializations
        switch(tabName) {
            case 'interface':
                // Re-initialize color pickers if needed
                if (typeof $.fn.wpColorPicker !== 'undefined') {
                    $('.color-picker:not(.wp-color-picker)').wpColorPicker();
                }
                break;
            case 'help':
                // Initialize help content interactions
                initHelpContent();
                break;
        }
    });
    
    function initHelpContent() {
        // Make help content more interactive
        $('.lift-info-box, .lift-warning-box, .lift-success-box').each(function() {
            if (!$(this).hasClass('lift-interactive')) {
                $(this).addClass('lift-interactive');
                
                // Add click to expand/collapse functionality for long content
                if ($(this).height() > 200) {
                    $(this).addClass('lift-collapsible');
                    $(this).on('click', function() {
                        $(this).toggleClass('lift-expanded');
                    });
                }
            }
        });
    }
    
    // Initialize help content on page load
    initHelpContent();
    
    console.log('LIFT Docs Settings: Enhanced JavaScript initialization complete');
});
