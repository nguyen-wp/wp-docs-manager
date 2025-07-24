<?php
/**
 * Test file for Multiple Files Support
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add test notice
add_action('admin_notices', 'lift_docs_multiple_files_test_notice');

function lift_docs_multiple_files_test_notice() {
    $screen = get_current_screen();
    if ($screen && ($screen->post_type === 'lift_document' || $screen->id === 'lift_document')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>LIFT Docs Multiple Files Test:</strong> ';
        echo 'You can now add multiple files to a single document. ';
        echo 'Each file will have its own secure download link.';
        echo '</p>';
        echo '</div>';
    }
}

// Test function to create sample document with multiple files
add_action('wp_ajax_create_test_multi_file_document', 'create_test_multi_file_document');

function create_test_multi_file_document() {
    // Check permissions
    if (!current_user_can('edit_posts')) {
        wp_die('Insufficient permissions');
    }
    
    // Create a test document
    $post_id = wp_insert_post(array(
        'post_title' => 'Test Multi-File Document - ' . date('Y-m-d H:i:s'),
        'post_content' => 'This is a test document with multiple files for testing the new multiple files functionality.',
        'post_status' => 'publish',
        'post_type' => 'lift_document'
    ));
    
    if ($post_id && !is_wp_error($post_id)) {
        // Add multiple test file URLs
        $test_files = array(
            'https://example.com/document1.pdf',
            'https://example.com/document2.docx',
            'https://example.com/document3.xlsx'
        );
        
        update_post_meta($post_id, '_lift_doc_file_urls', $test_files);
        update_post_meta($post_id, '_lift_doc_file_url', $test_files[0]); // Backward compatibility
        update_post_meta($post_id, '_lift_doc_file_size', 2048000); // 2MB total
        
        wp_send_json_success(array(
            'message' => 'Test document created successfully',
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit')
        ));
    } else {
        wp_send_json_error('Failed to create test document');
    }
}

// Add test button to admin bar
add_action('admin_bar_menu', 'add_multi_file_test_button', 999);

function add_multi_file_test_button($wp_admin_bar) {
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        $wp_admin_bar->add_node(array(
            'id' => 'test-multi-files',
            'title' => '🧪 Test Multi Files',
            'href' => '#',
            'meta' => array(
                'onclick' => 'createTestMultiFileDocument(); return false;'
            )
        ));
    }
}

// Add JavaScript for test functionality
add_action('admin_footer', 'multi_file_test_scripts');

function multi_file_test_scripts() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <script type="text/javascript">
        function createTestMultiFileDocument() {
            if (confirm('Create a test document with multiple files?')) {
                jQuery.post(ajaxurl, {
                    action: 'create_test_multi_file_document',
                    _ajax_nonce: '<?php echo wp_create_nonce('create_test_multi_file'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Test document created! Redirecting to edit page...');
                        window.location.href = response.data.edit_url;
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            }
        }
        
        // Test multiple files functionality on current page
        jQuery(document).ready(function($) {
            // Check if we're on document edit page
            if ($('#lift_doc_files_container').length > 0) {
                console.log('LIFT Docs Multi-File Test: Document edit page detected');
                
                // Test adding files programmatically
                var testFiles = [
                    'https://example.com/test1.pdf',
                    'https://example.com/test2.docx'
                ];
                
                // Add test button
                var testButton = $('<button type="button" class="button" style="margin-left: 10px;">🧪 Add Test Files</button>');
                testButton.click(function() {
                    var container = $('#lift_doc_files_container');
                    var existingRows = container.find('.file-input-row');
                    
                    // Clear existing if empty
                    if (existingRows.length === 1 && existingRows.first().find('.file-url-input').val() === '') {
                        existingRows.first().find('.file-url-input').val(testFiles[0]);
                        $('#add_file_button').click();
                        container.find('.file-input-row').last().find('.file-url-input').val(testFiles[1]);
                    }
                    
                    alert('Test files added! You can now see multiple file inputs.');
                });
                
                $('#add_file_button').after(testButton);
                
                console.log('LIFT Docs Multi-File Test: Test functionality added');
            }
            
            // Test secure links generation
            if ($('textarea[readonly].code').length > 0) {
                console.log('LIFT Docs Multi-File Test: Secure links detected');
                
                $('textarea[readonly].code').each(function(index) {
                    var textarea = $(this);
                    var url = textarea.val();
                    
                    if (url.includes('/lift-docs/download/')) {
                        console.log('LIFT Docs Multi-File Test: Download link ' + (index + 1) + ' found');
                    }
                });
            }
        });
        </script>
        <?php
    }
}

// Add CSS for test styling
add_action('admin_head', 'multi_file_test_styles');

function multi_file_test_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <style type="text/css">
        /* Test styling for multiple files */
        .file-input-row.test-file {
            background: #fff3cd;
            border: 1px dashed #ffc107;
            padding: 10px;
            border-radius: 4px;
        }
        
        .multiple-download-links .download-link-item {
            animation: fadeInUp 0.3s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Test button styling */
        button[onclick*="createTestMultiFileDocument"] {
            background: #28a745 !important;
            color: white !important;
        }
        </style>
        <?php
    }
}

// Log functionality
add_action('wp_loaded', 'log_multi_file_functionality');

function log_multi_file_functionality() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LIFT Docs Multi-File: Functionality loaded and ready for testing');
    }
}

// Test migration from single file to multiple files
add_action('admin_init', 'test_file_migration');

function test_file_migration() {
    // Only run this test once per session
    if (isset($_SESSION['lift_docs_migration_tested'])) {
        return;
    }
    
    $_SESSION['lift_docs_migration_tested'] = true;
    
    // Get documents with only single file URL
    $posts = get_posts(array(
        'post_type' => 'lift_document',
        'posts_per_page' => 5,
        'meta_query' => array(
            array(
                'key' => '_lift_doc_file_url',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_lift_doc_file_urls',
                'compare' => 'NOT EXISTS'
            )
        )
    ));
    
    if (defined('WP_DEBUG') && WP_DEBUG && !empty($posts)) {
        error_log('LIFT Docs Multi-File Test: Found ' . count($posts) . ' documents that need migration from single to multiple file format');
    }
}
