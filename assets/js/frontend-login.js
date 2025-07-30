/**
 * Frontend Login JavaScript for Lift Documents
 */

jQuery(document).ready(function($) {

    // Initialize login page
    if ($('.lift-docs-login-container').length) {
        initLoginPage();
    }

    // Initialize dashboard page
    if ($('.lift-docs-dashboard-container').length) {
        initDashboardPage();
    }

    /**
     * Initialize Login Page
     */
    function initLoginPage() {
        // Handle login form submission
        $('#lift-docs-login-form').on('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });

        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            var $passwordField = $('#docs_password');
            var $icon = $(this).find('.dashicons');

            if ($passwordField.attr('type') === 'password') {
                $passwordField.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $passwordField.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });

        // Real-time field validation
        $('#docs_username, #docs_password').on('blur', function() {
            validateField($(this));
        });

        // Clear error messages on input
        $('#docs_username, #docs_password').on('input', function() {
            clearFieldError($(this));
            hideMessages();
        });

        // Enter key in username field should focus password field
        $('#docs_username').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#docs_password').focus();
            }
        });
    }

    /**
     * Initialize Dashboard Page
     */
    function initDashboardPage() {
        // Handle logout
        $('#docs-logout-btn').on('click', function(e) {
            e.preventDefault();
            handleLogout();
        });

        // Handle document search
        $('#docs-search').on('input', function() {
            filterDocuments();
        });

        // Handle document filter
        $('#docs-filter').on('change', function() {
            filterDocuments();
        });

        // Handle document actions
        $('.view-document-btn').on('click', function() {
            var documentId = $(this).data('document-id');
            var fileUrl = $(this).data('file-url');
            viewDocument(documentId, fileUrl);
        });

        $('.download-document-btn').on('click', function() {
            var documentId = $(this).data('document-id');
            var fileUrl = $(this).data('file-url');
            downloadDocument(documentId, fileUrl);
        });

        // Auto-refresh stats every 30 seconds
        setInterval(refreshDashboardStats, 30000);
    }

    /**
     * Handle Login
     */
    function handleLogin() {
        var $form = $('#lift-docs-login-form');
        var $button = $('.lift-login-btn');
        var $btnText = $('.btn-text');
        var $btnSpinner = $('.btn-spinner');

        // Validate form
        if (!validateLoginForm()) {
            return;
        }

        // Show loading state
        $button.prop('disabled', true);
        $btnText.hide();
        $btnSpinner.show();
        hideMessages();

        // Prepare form data
        var formData = {
            action: 'docs_login',
            username: $('#docs_username').val().trim(),
            password: $('#docs_password').val(),
            remember: $('#docs_remember').is(':checked') ? '1' : '0',
            nonce: $('input[name="docs_login_nonce"]').val()
        };

        // AJAX request
        $.ajax({
            url: liftDocsLogin.ajaxurl,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message || liftDocsLogin.strings.loginSuccess);

                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else {
                    showError(response.data || liftDocsLogin.strings.loginError);
                    resetLoginButton();
                }
            },
            error: function(xhr, status, error) {
                showError(liftDocsLogin.strings.loginError);
                resetLoginButton();
            }
        });

        function resetLoginButton() {
            $button.prop('disabled', false);
            $btnText.show();
            $btnSpinner.hide();
        }
    }

    /**
     * Handle Logout
     */
    function handleLogout() {
        if (!confirm('Are you sure you want to logout?')) {
            return;
        }

        var $button = $('#docs-logout-btn');
        $button.prop('disabled', true).html('<span class="spinner"></span> Logging out...');

        $.ajax({
            url: liftDocsLogin.ajaxurl,
            type: 'POST',
            data: {
                action: 'docs_logout'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-exit"></span> Logout');
                    alert('Logout failed. Please try again.');
                }
            },
            error: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-exit"></span> Logout');
                alert('Logout failed. Please try again.');
            }
        });
    }

    /**
     * Validate Login Form
     */
    function validateLoginForm() {
        var isValid = true;

        // Validate username
        if (!validateField($('#docs_username'))) {
            isValid = false;
        }

        // Validate password
        if (!validateField($('#docs_password'))) {
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validate Individual Field
     */
    function validateField($field) {
        var value = $field.val().trim();
        var fieldName = $field.attr('name');
        var isValid = true;

        // Clear previous errors
        clearFieldError($field);

        // Required field validation
        if (!value) {
            showFieldError($field, liftDocsLogin.strings.requiredField);
            isValid = false;
        }

        // Email format validation (if it looks like an email)
        if (fieldName === 'username' && value.includes('@') && !isValidEmail(value)) {
            showFieldError($field, liftDocsLogin.strings.invalidEmail);
            isValid = false;
        }

        return isValid;
    }

    /**
     * Show Field Error
     */
    function showFieldError($field, message) {
        $field.addClass('error');
        $field.closest('.lift-form-group').addClass('has-error');

        var $error = $('<div class="field-error">' + message + '</div>');
        $field.closest('.lift-form-group').append($error);
    }

    /**
     * Clear Field Error
     */
    function clearFieldError($field) {
        $field.removeClass('error');
        $field.closest('.lift-form-group').removeClass('has-error');
        $field.closest('.lift-form-group').find('.field-error').remove();
    }

    /**
     * Show Success Message
     */
    function showSuccess(message) {
        $('.login-success').html('<span class="dashicons dashicons-yes-alt"></span> ' + message).show();
        $('.login-error').hide();
    }

    /**
     * Show Error Message
     */
    function showError(message) {
        $('.login-error').html('<span class="dashicons dashicons-warning"></span> ' + message).show();
        $('.login-success').hide();
    }

    /**
     * Hide Messages
     */
    function hideMessages() {
        $('.login-error, .login-success').hide();
    }

    /**
     * Validate Email
     */
    function isValidEmail(email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Filter Documents
     */
    function filterDocuments() {
        var searchTerm = $('#docs-search').val().toLowerCase();
        var filterType = $('#docs-filter').val();

        $('.document-card').each(function() {
            var $card = $(this);
            var title = $card.find('.document-title').text().toLowerCase();
            var excerpt = $card.find('.document-excerpt').text().toLowerCase();
            var isDownloaded = $card.find('.downloaded-badge').length > 0;

            // Search filter
            var matchesSearch = !searchTerm ||
                               title.includes(searchTerm) ||
                               excerpt.includes(searchTerm);

            // Type filter
            var matchesFilter = true;
            if (filterType === 'downloaded') {
                matchesFilter = isDownloaded;
            } else if (filterType === 'recent') {
                // Show documents from last 30 days
                var dateText = $card.find('.document-date').text();
                // This is a simplified check - you might want to implement proper date parsing
                matchesFilter = true; // For now, show all as "recent"
            }

            if (matchesSearch && matchesFilter) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        // Show/hide no results message
        var visibleCards = $('.document-card:visible').length;
        if (visibleCards === 0 && $('.documents-grid .no-results').length === 0) {
            $('.documents-grid').append('<div class="no-results"><p>No documents match your search criteria.</p></div>');
        } else if (visibleCards > 0) {
            $('.no-results').remove();
        }
    }

    /**
     * View Document
     */
    function viewDocument(documentId, fileUrl) {
        // Track view
        trackDocumentAction(documentId, 'view');

        // Open in new tab/window
        window.open(fileUrl, '_blank');
    }

    /**
     * Download Document
     */
    function downloadDocument(documentId, fileUrl) {
        // Track download
        trackDocumentAction(documentId, 'download');

        // Create download link
        var link = document.createElement('a');
        link.href = fileUrl;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Update UI to show downloaded status
        updateDocumentStatus(documentId, 'downloaded');
    }

    /**
     * Track Document Action
     */
    function trackDocumentAction(documentId, action) {
        $.ajax({
            url: liftDocsLogin.ajaxurl,
            type: 'POST',
            data: {
                action: 'track_document_action',
                document_id: documentId,
                doc_action: action,
                nonce: liftDocsLogin.nonce
            }
        });
    }

    /**
     * Update Document Status
     */
    function updateDocumentStatus(documentId, status) {
        var $card = $('.document-card[data-document-id="' + documentId + '"]');

        if (status === 'downloaded') {
            if ($card.find('.downloaded-badge').length === 0) {
                $card.find('.document-badges').append('<span class="badge downloaded-badge">Downloaded</span>');
            }
        }
    }

    /**
     * Refresh Dashboard Stats
     */
    function refreshDashboardStats() {
        // Only refresh if user is still on the page
        if (!document.hidden && $('.lift-docs-dashboard-container').length) {
            $.ajax({
                url: liftDocsLogin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'refresh_dashboard_stats',
                    nonce: liftDocsLogin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats quietly
                        $('.dashboard-stats .stat-content h3').each(function(index) {
                            if (response.data.stats[index]) {
                                $(this).text(response.data.stats[index]);
                            }
                        });
                    }
                }
            });
        }
    }

    // Handle window focus to refresh data
    $(window).on('focus', function() {
        if ($('.lift-docs-dashboard-container').length) {
            refreshDashboardStats();
        }
    });

    // Handle form auto-fill detection
    setTimeout(function() {
        $('#docs_username, #docs_password').each(function() {
            if ($(this).val()) {
                $(this).addClass('has-value');
            }
        });
    }, 100);

    // Form field focus/blur effects
    $('#docs_username, #docs_password').on('focus blur input', function() {
        if ($(this).val().trim()) {
            $(this).addClass('has-value');
        } else {
            $(this).removeClass('has-value');
        }
    });

});
