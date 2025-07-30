/**
 * Frontend JavaScript for LIFT Docs System
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize grid layout for document archives
    initArchiveGridLayout();

    // Initialize document features
    initDocumentTracking();
    initDownloadTracking();

    // Document search functionality
    var searchForm = $('.lift-docs-search');
    var searchInput = searchForm.find('input[type="search"]');
    var searchTimeout;

    if (searchInput.length) {
        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            var searchTerm = $(this).val();

            if (searchTerm.length >= 3) {
                searchTimeout = setTimeout(function() {
                    performSearch(searchTerm);
                }, 500);
            }
        });
    }

    /**
     * Initialize grid layout for archive pages
     */
    function initArchiveGridLayout() {
        // Check if we're on a document archive page
        if ($('body').hasClass('post-type-archive-lift_document') ||
            $('body').hasClass('tax-lift_doc_category') ||
            $('body').hasClass('tax-lift_doc_tag')) {

            // Wrap posts in grid container
            var $posts = $('.site-main .post, .site-main article');
            if ($posts.length > 1) {
                $posts.wrapAll('<div class="posts-grid"></div>');
            }

            // Add document meta to each post
            $posts.each(function() {
                var $post = $(this);
                var postId = $post.attr('id');

                if (postId && postId.includes('post-')) {
                    enhanceArchivePost($post);
                }
            });
        }
    }

    /**
     * Enhance archive post display
     */
    function enhanceArchivePost($post) {
        // Add document meta if not present
        var $entryFooter = $post.find('.entry-footer');
        if ($entryFooter.length && !$entryFooter.find('.lift-doc-meta').length) {
            var postId = $post.attr('id').replace('post-', '');

            // Add download button if file exists
            addDownloadButton($post, postId);

            // Add view count if enabled
            addViewCount($post, postId);
        }
    }

    /**
     * Add download button to archive post
     */
    function addDownloadButton($post, postId) {
        $.ajax({
            url: lift_docs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_get_document_download_url',
                post_id: postId,
                nonce: lift_docs_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.download_url) {
                    var downloadBtn = '<a href="' + response.data.download_url + '" class="lift-download-btn" data-document-id="' + postId + '">' +
                                    '<span class="dashicons dashicons-download"></span> Download' +
                                    '</a>';

                    var $entryFooter = $post.find('.entry-footer');
                    if ($entryFooter.length) {
                        $entryFooter.append('<div class="lift-doc-actions">' + downloadBtn + '</div>');
                    }
                }
            }
        });
    }

    /**
     * Add view count to archive post
     */
    function addViewCount($post, postId) {
        $.ajax({
            url: lift_docs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_get_document_views',
                post_id: postId,
                nonce: lift_docs_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.views > 0) {
                    var viewCount = '<span class="lift-view-count">' +
                                  '<span class="dashicons dashicons-visibility"></span> ' +
                                  response.data.views + ' views' +
                                  '</span>';

                    var $entryFooter = $post.find('.entry-footer');
                    if ($entryFooter.length) {
                        if (!$entryFooter.find('.lift-doc-meta').length) {
                            $entryFooter.append('<div class="lift-doc-meta"></div>');
                        }
                        $entryFooter.find('.lift-doc-meta').append(viewCount);
                    }
                }
            }
        });
    }

    /**
     * Initialize document view tracking
     */
    function initDocumentTracking() {
        if ($('body').hasClass('single-lift_document')) {
            var postId = $('article[id^="post-"]').attr('id');
            if (postId) {
                postId = postId.replace('post-', '');

                // Track page view
                $.ajax({
                    url: lift_docs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lift_track_document_view',
                        post_id: postId,
                        nonce: lift_docs_ajax.nonce
                    }
                });
            }
        }
    }

    /**
     * Initialize download tracking
     */
    function initDownloadTracking() {
        $(document).on('click', '.lift-download-btn, a[href*="lift_download"]', function() {
            var documentId = $(this).data('document-id') || getDocumentIdFromUrl($(this).attr('href'));

            if (documentId) {
                $.ajax({
                    url: lift_docs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lift_track_download',
                        document_id: documentId,
                        nonce: lift_docs_ajax.nonce
                    }
                });
            }
        });
    }

    /**
     * Extract document ID from download URL
     */
    function getDocumentIdFromUrl(url) {
        var match = url.match(/lift_download=(\d+)/);
        return match ? match[1] : null;
    }

    // Live search function
    function performSearch(searchTerm) {
        var resultsContainer = $('.lift-docs-search-results');

        if (!resultsContainer.length) {
            resultsContainer = $('<div class="lift-docs-search-results"></div>');
            searchForm.after(resultsContainer);
        }

        resultsContainer.html('<div class="lift-docs-loading"><div class="lift-docs-spinner"></div> Searching...</div>');

        $.ajax({
            url: lift_docs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_search_documents',
                search_term: searchTerm,
                nonce: lift_docs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.documents, resultsContainer);
                } else {
                    resultsContainer.html('<p>No documents found.</p>');
                }
            },
            error: function() {
                resultsContainer.html('<p>Search failed. Please try again.</p>');
            }
        });
    }

    // Display search results
    function displaySearchResults(documents, container) {
        if (documents.length === 0) {
            container.html('<p>No documents found.</p>');
            return;
        }

        var html = '<div class="lift-docs-search-list">';

        $.each(documents, function(index, doc) {
            html += '<div class="lift-doc-search-item">';
            html += '<h4><a href="' + doc.permalink + '">' + doc.title + '</a></h4>';

            if (doc.excerpt) {
                html += '<p class="excerpt">' + doc.excerpt + '</p>';
            }

            html += '<div class="doc-meta">';
            html += '<span class="date">' + doc.date + '</span>';

            if (doc.categories.length > 0) {
                html += ' | <span class="category">' + doc.categories[0] + '</span>';
            }

            if (doc.views > 0) {
                html += ' | <span class="views">' + doc.views + ' views</span>';
            }

            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        container.html(html);
    }

    // Load more documents functionality
    var loadMoreBtn = $('.lift-docs-load-more button');
    var currentPage = 1;
    var isLoading = false;

    if (loadMoreBtn.length) {
        loadMoreBtn.on('click', function(e) {
            e.preventDefault();

            if (isLoading) return;

            isLoading = true;
            loadMoreBtn.prop('disabled', true).text('Loading...');

            currentPage++;

            $.ajax({
                url: lift_docs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'lift_load_more_documents',
                    page: currentPage,
                    category: loadMoreBtn.data('category') || '',
                    tag: loadMoreBtn.data('tag') || '',
                    orderby: loadMoreBtn.data('orderby') || 'date',
                    order: loadMoreBtn.data('order') || 'DESC',
                    nonce: lift_docs_ajax.nonce
                },
                success: function(response) {
                    isLoading = false;
                    loadMoreBtn.prop('disabled', false).text('Load More');

                    if (response.success) {
                        $('.lift-docs-list').append(response.data.html);

                        if (!response.data.has_more) {
                            loadMoreBtn.fadeOut();
                        }
                    } else {
                        loadMoreBtn.fadeOut();
                    }
                },
                error: function() {
                    isLoading = false;
                    loadMoreBtn.prop('disabled', false).text('Load More');
                    alert('Failed to load more documents. Please try again.');
                }
            });
        });
    }

    // Infinite scroll
    if ($('.lift-docs-infinite-scroll').length) {
        $(window).on('scroll', function() {
            if (isLoading) return;

            var scrollTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            var documentHeight = $(document).height();

            if (scrollTop + windowHeight >= documentHeight - 500) {
                loadMoreBtn.trigger('click');
            }
        });
    }

    // Share functionality
    $(document).on('click', '.lift-docs-share-btn', function(e) {
        e.preventDefault();

        var url = $(this).data('url');
        var title = document.title;

        if (navigator.share) {
            // Use native Web Share API if available
            navigator.share({
                title: title,
                url: url
            }).catch(function(error) {
            });
        } else {
            // Fallback to copy to clipboard
            copyToClipboard(url);
            showNotification('Link copied to clipboard!', 'success');
        }
    });

    // Copy to clipboard function
    function copyToClipboard(text) {
        var textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
        }

        document.body.removeChild(textArea);
    }

    // Show notification
    function showNotification(message, type) {
        var notification = $('<div class="lift-docs-notification lift-docs-notification-' + type + '">' + message + '</div>');

        $('body').append(notification);

        notification.fadeIn(300);

        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Filter functionality
    $('.lift-docs-filter').on('change', function() {
        var filterType = $(this).data('filter');
        var filterValue = $(this).val();
        var documentsContainer = $('.lift-docs-list');

        documentsContainer.addClass('lift-docs-loading');

        $.ajax({
            url: lift_docs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lift_search_documents',
                [filterType]: filterValue,
                nonce: lift_docs_ajax.nonce
            },
            success: function(response) {
                documentsContainer.removeClass('lift-docs-loading');

                if (response.success) {
                    updateDocumentsList(response.data.documents);
                } else {
                    documentsContainer.html('<p>No documents found for the selected filter.</p>');
                }
            },
            error: function() {
                documentsContainer.removeClass('lift-docs-loading');
                alert('Filter failed. Please try again.');
            }
        });
    });

    // Update documents list
    function updateDocumentsList(documents) {
        var container = $('.lift-docs-list');
        container.empty();

        if (documents.length === 0) {
            container.html('<p>No documents found.</p>');
            return;
        }

        $.each(documents, function(index, doc) {
            var docHtml = '<div class="lift-doc-item">';
            docHtml += '<h3><a href="' + doc.permalink + '">' + doc.title + '</a></h3>';

            if (doc.excerpt) {
                docHtml += '<p class="excerpt">' + doc.excerpt + '</p>';
            }

            docHtml += '<div class="doc-meta">';
            docHtml += '<span class="date">' + doc.date + '</span>';
            docHtml += '<span class="author"> by ' + doc.author + '</span>';

            if (doc.categories.length > 0) {
                docHtml += '<span class="category"> | ' + doc.categories.join(', ') + '</span>';
            }

            if (doc.views > 0) {
                docHtml += '<span class="views"> | ' + doc.views + ' views</span>';
            }

            docHtml += '</div>';
            docHtml += '</div>';

            container.append(docHtml);
        });
    }

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();

        var target = $(this.getAttribute('href'));

        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 600);
        }
    });

    // Document card hover effects
    $(document).on('mouseenter', '.lift-doc-card', function() {
        $(this).addClass('hovered');
    }).on('mouseleave', '.lift-doc-card', function() {
        $(this).removeClass('hovered');
    });

    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        $('.lift-doc-card img.lazy').each(function() {
            imageObserver.observe(this);
        });
    }

    // Reading progress indicator
    if ($('.lift-document-single').length) {
        var progressBar = $('<div class="reading-progress"><div class="progress-bar"></div></div>');
        $('body').prepend(progressBar);

        $(window).on('scroll', function() {
            var scrollTop = $(window).scrollTop();
            var documentHeight = $(document).height() - $(window).height();
            var progress = (scrollTop / documentHeight) * 100;

            $('.progress-bar').css('width', progress + '%');
        });
    }

    // Document download tracking
    $(document).on('click', '.lift-docs-download-btn', function() {
        var documentId = $(this).data('document-id');

        if (documentId) {
            // Track download event
            $.ajax({
                url: lift_docs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'lift_track_download',
                    document_id: documentId,
                    nonce: lift_docs_ajax.nonce
                }
            });
        }
    });

    // Auto-hide notifications
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut();
    }, 5000);

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
            e.preventDefault();
            searchInput.focus();
        }

        // ESC to close search results
        if (e.keyCode === 27) {
            $('.lift-docs-search-results').slideUp();
        }
    });

    // Print functionality
    $('.print-document').on('click', function(e) {
        e.preventDefault();
        window.print();
    });

    // Initialize tooltips
    $('[data-tooltip]').each(function() {
        $(this).addClass('lift-docs-tooltip');
    });

    // Document Content Modal functionality
    initDocumentContentModal();

    /**
     * Initialize document content modal
     */
    function initDocumentContentModal() {
        // Handle content preview button click
        $(document).on('click', '.btn-content-preview', function(e) {
            e.preventDefault();

            var documentId = $(this).data('document-id');
            var button = $(this);

            if (!documentId) {
                alert('Invalid document ID');
                return;
            }

            // Show loading state
            button.prop('disabled', true);
            var originalText = button.html();
            button.html('<span class="dashicons dashicons-update"></span> Loading...');

            // Load document content via AJAX
            $.ajax({
                url: lift_docs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_document_content',
                    document_id: documentId,
                    nonce: lift_docs_ajax.nonce
                },
                success: function(response) {
                    // Reset button state
                    button.prop('disabled', false).html(originalText);

                    if (response.success) {
                        showDocumentContentModal(response.data.title, response.data.content);
                    } else {
                        alert('Error loading document content: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    // Reset button state
                    button.prop('disabled', false).html(originalText);
                    alert('Network error. Please try again.');
                }
            });
        });

        // Handle modal close
        $(document).on('click', '.lift-modal-close, .lift-modal-backdrop', function(e) {
            e.preventDefault();
            closeDocumentContentModal();
        });

        // Handle ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC key
                closeDocumentContentModal();
            }
        });

        // Prevent modal content click from closing modal
        $(document).on('click', '.lift-modal-content', function(e) {
            e.stopPropagation();
        });

        // Handle modal click to close
        $(document).on('click', '.lift-modal', function(e) {
            if (e.target === this) {
                closeDocumentContentModal();
            }
        });
    }

    /**
     * Show document content modal
     */
    function showDocumentContentModal(title, content) {
        var modal = $('#lift-document-modal');
        var backdrop = $('#lift-modal-backdrop');

        if (modal.length === 0) {
            // Create modal if it doesn't exist
            var modalHtml = '<div id="lift-document-modal" class="lift-modal" style="display: none;">' +
                '<div class="lift-modal-content">' +
                    '<div class="lift-modal-header">' +
                        '<h2 id="modal-document-title">Document Content</h2>' +
                        '<button type="button" class="lift-modal-close">&times;</button>' +
                    '</div>' +
                    '<div class="lift-modal-body">' +
                        '<div id="modal-document-content"></div>' +
                    '</div>' +
                '</div>' +
            '</div>';

            $('body').append(modalHtml);
            modal = $('#lift-document-modal');
        }

        if (backdrop.length === 0) {
            $('body').append('<div id="lift-modal-backdrop" class="lift-modal-backdrop" style="display: none;"></div>');
            backdrop = $('#lift-modal-backdrop');
        }

        // Set content
        $('#modal-document-title').text(title);
        $('#modal-document-content').html(content);

        // Show modal with animation
        $('body').addClass('modal-open');
        backdrop.fadeIn(200);
        modal.fadeIn(200);

        // Focus management for accessibility
        modal.find('.lift-modal-close').focus();
    }

    /**
     * Close document content modal
     */
    function closeDocumentContentModal() {
        var modal = $('#lift-document-modal');
        var backdrop = $('#lift-modal-backdrop');

        if (modal.is(':visible')) {
            modal.fadeOut(200);
            backdrop.fadeOut(200);
            $('body').removeClass('modal-open');

            // Return focus to the button that opened the modal
            $('.btn-content-preview:focus').blur();
        }
    }
});

// Document ready end
