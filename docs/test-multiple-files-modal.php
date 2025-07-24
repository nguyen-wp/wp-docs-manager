<?php
/**
 * Test Multiple Files Modal System
 * 
 * This file tests the enhanced modal system for multiple files support
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin bar buttons for testing
add_action('wp_before_admin_bar_render', function() {
    global $wp_admin_bar;
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $wp_admin_bar->add_node(array(
        'id' => 'test-multiple-modal',
        'title' => '🔍 Test Multiple Files Modal',
        'href' => '#',
        'meta' => array(
            'onclick' => 'testMultipleFilesModal(); return false;'
        )
    ));
    
    $wp_admin_bar->add_node(array(
        'id' => 'create-test-docs',
        'title' => '📄 Create Test Documents',
        'href' => '#',
        'meta' => array(
            'onclick' => 'createTestDocuments(); return false;'
        )
    ));
});

// Add JavaScript for testing
add_action('wp_footer', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <script>
    function testMultipleFilesModal() {
        console.log('=== Testing Multiple Files Modal System ===');
        
        // Test 1: Check if modal elements exist
        console.log('1. Checking modal elements...');
        var modal = document.getElementById('lift-document-details-modal');
        var multipleDownloads = document.getElementById('lift-multiple-downloads');
        var singleDownload = document.getElementById('lift-single-download');
        var secureGroup = document.getElementById('lift-secure-download-group');
        
        console.log('Modal exists:', !!modal);
        console.log('Multiple downloads container exists:', !!multipleDownloads);
        console.log('Single download container exists:', !!singleDownload);
        console.log('Secure download group exists:', !!secureGroup);
        
        // Test 2: Check for CSS classes
        console.log('2. Checking CSS classes...');
        var cssRules = [];
        for (var i = 0; i < document.styleSheets.length; i++) {
            try {
                var sheet = document.styleSheets[i];
                if (sheet.cssRules) {
                    for (var j = 0; j < sheet.cssRules.length; j++) {
                        var rule = sheet.cssRules[j];
                        if (rule.selectorText && rule.selectorText.includes('multiple-files-list')) {
                            cssRules.push(rule.selectorText);
                        }
                    }
                }
            } catch (e) {
                // Cross-origin or other CSS access issues
            }
        }
        console.log('Multiple files CSS rules found:', cssRules);
        
        // Test 3: Test modal functionality with sample data
        console.log('3. Testing modal with sample multiple files data...');
        
        var sampleData = {
            postId: 123,
            viewUrl: 'http://example.com/document/123',
            viewLabel: 'View Document',
            downloadUrls: JSON.stringify([
                {url: 'http://example.com/file1.pdf', name: 'Document 1.pdf', index: 0},
                {url: 'http://example.com/file2.docx', name: 'Document 2.docx', index: 1},
                {url: 'http://example.com/file3.xlsx', name: 'Spreadsheet.xlsx', index: 2}
            ]),
            onlineViewUrls: JSON.stringify([
                {url: 'http://example.com/view/file1.pdf', name: 'Document 1.pdf', index: 0},
                {url: 'http://example.com/view/file2.docx', name: 'Document 2.docx', index: 1},
                {url: 'http://example.com/view/file3.xlsx', name: 'Spreadsheet.xlsx', index: 2}
            ]),
            secureDownloadUrls: JSON.stringify([
                {url: 'http://example.com/secure/file1.pdf', name: 'Document 1.pdf', index: 0},
                {url: 'http://example.com/secure/file2.docx', name: 'Document 2.docx', index: 1},
                {url: 'http://example.com/secure/file3.xlsx', name: 'Spreadsheet.xlsx', index: 2}
            ]),
            shortcode: '[lift_document_download id="123"]',
            views: '15',
            downloads: '8',
            fileSize: '2.5 MB',
            filesCount: 3,
            canView: 'true',
            canDownload: 'true'
        };
        
        // Check if admin modal script is loaded
        if (typeof jQuery !== 'undefined' && jQuery('.lift-details-btn').length > 0) {
            console.log('Admin modal detected - testing button data simulation...');
            
            // Create a temporary button with test data
            var testButton = jQuery('<button>')
                .addClass('button button-small lift-details-btn test-button')
                .text('Test Multiple Files')
                .data(sampleData);
            
            jQuery('body').append(testButton);
            
            // Trigger click to test modal
            setTimeout(function() {
                testButton.click();
                console.log('Test button clicked - modal should open with multiple files');
                
                // Clean up
                setTimeout(function() {
                    testButton.remove();
                }, 5000);
            }, 1000);
            
        } else {
            console.log('Admin modal script not loaded or no buttons found');
        }
        
        // Test 4: Check JavaScript functions
        console.log('4. Checking JavaScript functions...');
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery available:', !!jQuery);
            console.log('Lift modal handlers:', jQuery._data(document, 'events'));
        }
        
        console.log('=== Multiple Files Modal Test Complete ===');
    }
    
    function createTestDocuments() {
        console.log('=== Creating Test Documents with Multiple Files ===');
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'create_test_multiple_files_docs',
                nonce: '<?php echo wp_create_nonce('test_multiple_files_docs'); ?>'
            },
            success: function(response) {
                console.log('Test documents creation response:', response);
                
                if (response.success) {
                    alert('✅ Test documents created successfully!\n\n' + 
                          'Created documents:\n' + 
                          response.data.documents.map(function(doc) {
                              return '• ' + doc.title + ' (' + doc.files_count + ' files)';
                          }).join('\n') + 
                          '\n\nGo to Documents page to test the modal.');
                } else {
                    alert('❌ Error creating test documents: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('❌ AJAX error: ' + error);
            }
        });
    }
    
    // Auto-run basic tests on admin pages
    jQuery(document).ready(function($) {
        if (window.location.href.includes('wp-admin')) {
            setTimeout(function() {
                console.log('🔍 Auto-testing Multiple Files Modal...');
                
                // Check if we're on the documents page
                if (window.location.href.includes('edit.php') && window.location.href.includes('post_type=lift_document')) {
                    console.log('📄 On documents page - checking for details buttons...');
                    
                    var detailsButtons = $('.lift-details-btn');
                    console.log('Found', detailsButtons.length, 'details buttons');
                    
                    if (detailsButtons.length > 0) {
                        detailsButtons.each(function(index) {
                            var $btn = $(this);
                            var data = $btn.data();
                            console.log('Button', index + 1, 'data:', {
                                filesCount: data.filesCount,
                                hasDownloadUrls: !!data.downloadUrls,
                                hasSecureUrls: !!data.secureDownloadUrls,
                                canDownload: data.canDownload
                            });
                        });
                    }
                }
            }, 2000);
        }
    });
    </script>
    <?php
});

// AJAX handler for creating test documents
add_action('wp_ajax_create_test_multiple_files_docs', function() {
    if (!current_user_can('manage_options')) {
        wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'test_multiple_files_docs')) {
        wp_die(json_encode(array('success' => false, 'data' => 'Invalid nonce')));
    }
    
    $created_docs = array();
    
    // Test Document 1: Single file
    $doc1_id = wp_insert_post(array(
        'post_title' => '📄 Test Document - Single File',
        'post_content' => 'This is a test document with a single file attachment for testing the modal system.',
        'post_type' => 'lift_document',
        'post_status' => 'publish'
    ));
    
    if ($doc1_id) {
        update_post_meta($doc1_id, '_lift_doc_file_urls', array(
            'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf'
        ));
        update_post_meta($doc1_id, '_lift_doc_file_url', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
        update_post_meta($doc1_id, '_lift_doc_file_size', 13264);
        
        $created_docs[] = array(
            'id' => $doc1_id,
            'title' => 'Test Document - Single File',
            'files_count' => 1
        );
    }
    
    // Test Document 2: Multiple files (3 files)
    $doc2_id = wp_insert_post(array(
        'post_title' => '📄 Test Document - Multiple Files (3)',
        'post_content' => 'This is a test document with multiple file attachments for testing the enhanced modal system.',
        'post_type' => 'lift_document',
        'post_status' => 'publish'
    ));
    
    if ($doc2_id) {
        update_post_meta($doc2_id, '_lift_doc_file_urls', array(
            'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'https://file-examples.com/storage/fe86a1f69bb8a97f4e69dd1/2017/10/file_example_JPG_100kB.jpg',
            'https://sample-videos.com/zip/10/mp4/mp4/SampleVideo_1280x720_1mb.mp4'
        ));
        update_post_meta($doc2_id, '_lift_doc_file_url', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
        update_post_meta($doc2_id, '_lift_doc_file_size', 45890);
        
        $created_docs[] = array(
            'id' => $doc2_id,
            'title' => 'Test Document - Multiple Files (3)',
            'files_count' => 3
        );
    }
    
    // Test Document 3: Large number of files (5 files)
    $doc3_id = wp_insert_post(array(
        'post_title' => '📄 Test Document - Many Files (5)',
        'post_content' => 'This is a test document with many file attachments for stress testing the modal system.',
        'post_type' => 'lift_document',
        'post_status' => 'publish'
    ));
    
    if ($doc3_id) {
        update_post_meta($doc3_id, '_lift_doc_file_urls', array(
            'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'https://file-examples.com/storage/fe86a1f69bb8a97f4e69dd1/2017/10/file_example_JPG_100kB.jpg',
            'https://sample-videos.com/zip/10/mp4/mp4/SampleVideo_1280x720_1mb.mp4',
            'https://www.soundjay.com/misc/sounds/bell-ringing-05.wav',
            'https://github.com/visknut/JavaGuide/archive/refs/heads/main.zip'
        ));
        update_post_meta($doc3_id, '_lift_doc_file_url', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
        update_post_meta($doc3_id, '_lift_doc_file_size', 125600);
        
        $created_docs[] = array(
            'id' => $doc3_id,
            'title' => 'Test Document - Many Files (5)',
            'files_count' => 5
        );
    }
    
    // Test Document 4: No files (for testing empty state)
    $doc4_id = wp_insert_post(array(
        'post_title' => '📄 Test Document - No Files',
        'post_content' => 'This is a test document with no file attachments for testing empty state handling.',
        'post_type' => 'lift_document',
        'post_status' => 'publish'
    ));
    
    if ($doc4_id) {
        $created_docs[] = array(
            'id' => $doc4_id,
            'title' => 'Test Document - No Files',
            'files_count' => 0
        );
    }
    
    wp_die(json_encode(array(
        'success' => true,
        'data' => array(
            'message' => 'Test documents created successfully',
            'documents' => $created_docs,
            'total' => count($created_docs)
        )
    )));
});

// Admin notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-lift_document') {
        return;
    }
    ?>
    <div class="notice notice-info">
        <p>
            <strong>🔍 Multiple Files Modal Testing Active</strong><br>
            • Click "🔍 Test Multiple Files Modal" in admin bar to run tests<br>
            • Click "📄 Create Test Documents" to generate test data<br>
            • Check browser console for detailed test results
        </p>
    </div>
    <?php
});

if (WP_DEBUG) {
    error_log('LIFT Docs: Multiple Files Modal Test System loaded');
}
?>
