/**
 * Admin JavaScript for LIFT Docs System
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Media uploader for documents
    var fileFrame;
    
    $('#upload_file_button').on('click', function(e) {
        e.preventDefault();
        
        // If the media frame already exists, reopen it
        if (fileFrame) {
            fileFrame.open();
            return;
        }
        
        // Create the media frame
        fileFrame = wp.media({
            title: 'Select Document File',
            button: {
                text: 'Use this file'
            },
            multiple: false
        });
        
        // When a file is selected, run a callback
        fileFrame.on('select', function() {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            
            $('#lift_doc_file_url').val(attachment.url);
            $('#lift_doc_file_size').val(attachment.filesizeInBytes || '');
            
            // Show preview if it's an image
            if (attachment.type === 'image') {
                var preview = '<div class="file-preview"><img src="' + attachment.url + '" style="max-width: 200px; height: auto;" /></div>';
                $('.file-preview').remove();
                $('#upload_file_button').after(preview);
            }
        });
        
        // Open the modal
        fileFrame.open();
    });
    
    // Bulk actions functionality
    $('.lift-docs-bulk-select').on('change', function() {
        var checked = $('.lift-docs-bulk-select:checked').length;
        
        if (checked > 0) {
            $('.lift-docs-bulk-actions').show();
        } else {
            $('.lift-docs-bulk-actions').hide();
        }
    });
    
    // Select all checkbox
    $('#lift-docs-select-all').on('change', function() {
        $('.lift-docs-bulk-select').prop('checked', $(this).prop('checked'));
        $('.lift-docs-bulk-select').trigger('change');
    });
    
    // Bulk action execution
    $('#lift-docs-bulk-apply').on('click', function(e) {
        e.preventDefault();
        
        var action = $('#lift-docs-bulk-action').val();
        var documentIds = [];
        
        $('.lift-docs-bulk-select:checked').each(function() {
            documentIds.push($(this).val());
        });
        
        if (!action || documentIds.length === 0) {
            alert('Please select an action and at least one document.');
            return;
        }
        
        if (!confirm('Are you sure you want to perform this action on ' + documentIds.length + ' document(s)?')) {
            return;
        }
        
        $(this).prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: lift_docs_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_bulk_action',
                bulk_action: action,
                document_ids: documentIds,
                nonce: lift_docs_admin_ajax.nonce
            },
            success: function(response) {
                $('#lift-docs-bulk-apply').prop('disabled', false).text('Apply');
                
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    location.reload();
                } else {
                    showNotification('Bulk action failed: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function() {
                $('#lift-docs-bulk-apply').prop('disabled', false).text('Apply');
                showNotification('Bulk action failed. Please try again.', 'error');
            }
        });
    });
    
    // Analytics period selector
    $('#analytics-period').on('change', function() {
        var period = $(this).val();
        loadAnalytics(period);
    });
    
    // Load analytics data
    function loadAnalytics(period) {
        $('.analytics-content').addClass('loading');
        
        $.ajax({
            url: lift_docs_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_admin_analytics',
                period: period,
                nonce: lift_docs_admin_ajax.nonce
            },
            success: function(response) {
                $('.analytics-content').removeClass('loading');
                
                if (response.success) {
                    updateAnalyticsDisplay(response.data);
                } else {
                    showNotification('Failed to load analytics data.', 'error');
                }
            },
            error: function() {
                $('.analytics-content').removeClass('loading');
                showNotification('Failed to load analytics data.', 'error');
            }
        });
    }
    
    // Update analytics display
    function updateAnalyticsDisplay(data) {
        // Update view stats
        if (data.view_stats) {
            var totalViews = data.view_stats.reduce(function(sum, item) {
                return sum + parseInt(item.views);
            }, 0);
            $('#total-views').text(totalViews.toLocaleString());
        }
        
        // Update download stats
        if (data.download_stats) {
            var totalDownloads = data.download_stats.reduce(function(sum, item) {
                return sum + parseInt(item.downloads);
            }, 0);
            $('#total-downloads').text(totalDownloads.toLocaleString());
        }
        
        // Update popular documents
        if (data.popular_docs) {
            var popularHtml = '<ol>';
            $.each(data.popular_docs, function(index, doc) {
                popularHtml += '<li>';
                popularHtml += '<a href="' + doc.edit_link + '">' + doc.title + '</a>';
                popularHtml += ' (' + doc.views + ' views)';
                popularHtml += '</li>';
            });
            popularHtml += '</ol>';
            $('.popular-documents-list').html(popularHtml);
        }
    }
    
    // Document upload functionality
    var uploadArea = $('.lift-docs-upload-area');
    var uploadInput = $('#document-upload');
    
    // Drag and drop functionality
    uploadArea.on('dragenter dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Only remove dragover class if we're actually leaving the drop area
        if (!$.contains(this, e.relatedTarget)) {
            $(this).removeClass('dragover');
        }
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadFiles(files);
        }
    });
    
    // Click to upload
    uploadArea.on('click', function() {
        uploadInput.click();
    });
    
    uploadInput.on('change', function() {
        if (this.files.length > 0) {
            uploadFiles(this.files);
        }
    });
    
    // Upload files function
    function uploadFiles(files) {
        $.each(files, function(index, file) {
            uploadFile(file);
        });
    }
    
    // Upload single file
    function uploadFile(file) {
        var formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'lift_upload_document');
        formData.append('nonce', lift_docs_admin_ajax.nonce);
        
        var progressBar = createProgressBar(file.name);
        
        $.ajax({
            url: lift_docs_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        progressBar.find('.progress-bar').css('width', percentComplete + '%');
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    progressBar.find('.progress-status').text('Upload complete!');
                    $('#lift_doc_file_url').val(response.data.url);
                    $('#lift_doc_file_size').val(response.data.size);
                    
                    setTimeout(function() {
                        progressBar.fadeOut();
                    }, 2000);
                } else {
                    progressBar.find('.progress-status').text('Upload failed: ' + (response.data || 'Unknown error'));
                    progressBar.addClass('error');
                }
            },
            error: function() {
                progressBar.find('.progress-status').text('Upload failed. Please try again.');
                progressBar.addClass('error');
            }
        });
    }
    
    // Create progress bar
    function createProgressBar(filename) {
        var progressHtml = '<div class="upload-progress">';
        progressHtml += '<div class="filename">' + filename + '</div>';
        progressHtml += '<div class="lift-docs-progress">';
        progressHtml += '<div class="progress-bar"></div>';
        progressHtml += '</div>';
        progressHtml += '<div class="progress-status">Uploading...</div>';
        progressHtml += '</div>';
        
        var progressBar = $(progressHtml);
        $('.upload-progress-container').append(progressBar);
        
        return progressBar;
    }
    
    // Settings form validation
    $('#lift-docs-settings-form').on('submit', function() {
        var documentsPerPage = $('#documents_per_page').val();
        var maxFileSize = $('#max_file_size').val();
        
        if (documentsPerPage < 1 || documentsPerPage > 100) {
            alert('Documents per page must be between 1 and 100.');
            return false;
        }
        
        if (maxFileSize < 1 || maxFileSize > 1024) {
            alert('Max file size must be between 1 and 1024 MB.');
            return false;
        }
        
        return true;
    });
    
    // Real-time settings preview
    $('#documents_per_page').on('input', function() {
        var value = $(this).val();
        $('.documents-per-page-preview').text(value + ' documents per page');
    });
    
    // Color picker for theme customization
    if ($.fn.wpColorPicker) {
        $('.color-picker').wpColorPicker();
    }
    
    // Sortable functionality for document order
    if ($.fn.sortable) {
        $('.lift-docs-sortable').sortable({
            placeholder: 'sort-placeholder',
            update: function(event, ui) {
                var order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                $.ajax({
                    url: lift_docs_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lift_save_document_order',
                        order: order,
                        nonce: lift_docs_admin_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Document order saved!', 'success');
                        }
                    }
                });
            }
        });
    }
    
    // Auto-save functionality
    var autoSaveTimeout;
    
    $('.auto-save-field').on('input', function() {
        clearTimeout(autoSaveTimeout);
        var field = $(this);
        
        autoSaveTimeout = setTimeout(function() {
            autoSaveField(field);
        }, 2000);
    });
    
    function autoSaveField(field) {
        var fieldName = field.attr('name');
        var fieldValue = field.val();
        var postId = $('#post_ID').val();
        
        if (!postId) return;
        
        $.ajax({
            url: lift_docs_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_auto_save_field',
                post_id: postId,
                field_name: fieldName,
                field_value: fieldValue,
                nonce: lift_docs_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    field.addClass('auto-saved');
                    setTimeout(function() {
                        field.removeClass('auto-saved');
                    }, 1000);
                }
            }
        });
    }
    
    // Show notification function
    function showNotification(message, type) {
        var notification = $('<div class="lift-docs-notice ' + type + '">' + message + '</div>');
        
        $('.wrap h1').after(notification);
        
        notification.fadeIn(300);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Enhanced search in admin
    var adminSearch = $('#lift-docs-admin-search');
    var searchResults = $('.lift-docs-admin-search-results');
    
    adminSearch.on('input', function() {
        var searchTerm = $(this).val();
        
        if (searchTerm.length >= 2) {
            performAdminSearch(searchTerm);
        } else {
            searchResults.empty().hide();
        }
    });
    
    function performAdminSearch(searchTerm) {
        $.ajax({
            url: lift_docs_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_admin_search',
                search_term: searchTerm,
                nonce: lift_docs_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displayAdminSearchResults(response.data);
                } else {
                    searchResults.html('<p>No documents found.</p>').show();
                }
            }
        });
    }
    
    function displayAdminSearchResults(results) {
        var html = '<ul>';
        
        $.each(results, function(index, doc) {
            html += '<li>';
            html += '<a href="' + doc.edit_link + '">' + doc.title + '</a>';
            html += '<small> - ' + doc.status + ' | ' + doc.date + '</small>';
            html += '</li>';
        });
        
        html += '</ul>';
        searchResults.html(html).show();
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S for save
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
            e.preventDefault();
            $('#publish, #save-post').click();
        }
        
        // Ctrl/Cmd + Shift + P for preview
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 80) {
            e.preventDefault();
            $('#post-preview').click();
        }
    });
    
    // Initialize tooltips
    $('[data-tooltip]').each(function() {
        $(this).addClass('lift-docs-tooltip');
    });
    
    // Chart initialization (if Chart.js is available)
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    
    function initializeCharts() {
        // Views chart
        var viewsCtx = document.getElementById('views-chart');
        if (viewsCtx) {
            new Chart(viewsCtx, {
                type: 'line',
                data: {
                    labels: [], // Will be populated via AJAX
                    datasets: [{
                        label: 'Views',
                        data: [],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Downloads chart
        var downloadsCtx = document.getElementById('downloads-chart');
        if (downloadsCtx) {
            new Chart(downloadsCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Downloads',
                        data: [],
                        backgroundColor: '#00a32a'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').hide();
        $(targetTab).show();
    });
    
    // Initialize first tab
    $('.nav-tab:first').addClass('nav-tab-active');
    $('.tab-content:first').show();
});

// Helper functions
window.LiftDocsAdmin = {
    showNotification: function(message, type) {
        var notification = jQuery('<div class="lift-docs-notice ' + type + '">' + message + '</div>');
        jQuery('.wrap h1').after(notification);
        notification.fadeIn(300);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                jQuery(this).remove();
            });
        }, 5000);
    },
    
    confirmAction: function(message) {
        return confirm(message || 'Are you sure you want to perform this action?');
    }
};
