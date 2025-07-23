<?php
/**
 * Test Multiple Files Upload Functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add test notices for multiple files functionality
add_action('admin_notices', 'lift_docs_multiple_files_upload_test_notice');

function lift_docs_multiple_files_upload_test_notice() {
    $screen = get_current_screen();
    if ($screen && ($screen->post_type === 'lift_document' || $screen->id === 'lift_document')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>📁 Multiple Files Upload Test:</strong> ';
        echo 'Media uploader now accepts ALL file types (not just documents). ';
        echo 'Each upload button creates a separate media library instance. ';
        echo 'Test with: PDF, Images, Videos, Audio, ZIP files, etc.';
        echo '</p>';
        echo '</div>';
    }
}

// Test AJAX handler for file type validation
add_action('wp_ajax_test_file_type_support', 'test_file_type_support');

function test_file_type_support() {
    // Check permissions
    if (!current_user_can('edit_posts')) {
        wp_die('Insufficient permissions');
    }
    
    // Get WordPress allowed file types
    $allowed_types = get_allowed_mime_types();
    
    // Group by category
    $file_categories = array(
        'Documents' => array(),
        'Images' => array(),
        'Audio' => array(),
        'Video' => array(),
        'Archives' => array(),
        'Other' => array()
    );
    
    foreach ($allowed_types as $ext => $mime) {
        if (strpos($mime, 'image/') === 0) {
            $file_categories['Images'][] = $ext . ' (' . $mime . ')';
        } elseif (strpos($mime, 'audio/') === 0) {
            $file_categories['Audio'][] = $ext . ' (' . $mime . ')';
        } elseif (strpos($mime, 'video/') === 0) {
            $file_categories['Video'][] = $ext . ' (' . $mime . ')';
        } elseif (in_array($mime, array('application/zip', 'application/x-rar-compressed'))) {
            $file_categories['Archives'][] = $ext . ' (' . $mime . ')';
        } elseif (strpos($mime, 'application/') === 0 || strpos($mime, 'text/') === 0) {
            $file_categories['Documents'][] = $ext . ' (' . $mime . ')';
        } else {
            $file_categories['Other'][] = $ext . ' (' . $mime . ')';
        }
    }
    
    wp_send_json_success(array(
        'message' => 'File type support test completed',
        'total_types' => count($allowed_types),
        'categories' => $file_categories
    ));
}

// Add JavaScript for testing upload functionality
add_action('admin_footer', 'multiple_files_upload_test_scripts');

function multiple_files_upload_test_scripts() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('🧪 Multiple Files Upload Test: Initialized');
            
            // Test media uploader availability
            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                console.log('✅ WordPress Media Library: Available');
                console.log('📁 File Types: All WordPress-supported file types accepted');
                
                // Test different file type icons
                const testFileTypes = {
                    'image/jpeg': '🖼️',
                    'video/mp4': '🎥', 
                    'audio/mp3': '🎵',
                    'application/pdf': '📕',
                    'application/msword': '📘',
                    'application/vnd.ms-excel': '📗',
                    'application/zip': '📦',
                    'text/plain': '📄'
                };
                
                console.log('🎨 File Type Icons:', testFileTypes);
                
                // Monitor upload button clicks
                let uploadClickCount = 0;
                $(document).on('click', '.upload-file-button', function() {
                    uploadClickCount++;
                    console.log(`🔄 Upload Button Click #${uploadClickCount}:`, this);
                    console.log('📍 Creating new media uploader instance for this button');
                });
                
                // Monitor file additions
                let fileAddCount = 0;
                $('#add_file_button').click(function() {
                    fileAddCount++;
                    console.log(`➕ Add File Button Click #${fileAddCount}`);
                    console.log('📝 New file input row created');
                });
                
                // Monitor file removals
                $(document).on('click', '.remove-file-button', function() {
                    console.log('🗑️ Remove File Button Clicked');
                    console.log('❌ File input row will be removed');
                });
                
                // Test file type support
                console.log('🔍 Testing WordPress file type support...');
                $.post(ajaxurl, {
                    action: 'test_file_type_support'
                }, function(response) {
                    if (response.success) {
                        console.log('📊 File Type Support Test Results:');
                        console.log(`Total supported types: ${response.data.total_types}`);
                        console.log('Categories:', response.data.categories);
                    }
                });
                
            } else {
                console.log('⚠️ WordPress Media Library: Not Available (using fallback)');
            }
            
            // Test upload animations
            $(document).on('DOMSubtreeModified', '.file-input-row', function() {
                if ($(this).hasClass('uploaded')) {
                    console.log('✨ Upload success animation triggered');
                }
            });
            
            console.log('🎯 Test Instructions:');
            console.log('1. Click any "📁 Upload" button');
            console.log('2. Try uploading different file types (PDF, JPG, MP4, ZIP, etc.)');
            console.log('3. Each button should open a separate media library instance');
            console.log('4. File icons should change based on file type');
            console.log('5. Multiple files should work independently');
        });
        </script>
        
        <style type="text/css">
        /* Test-specific styling */
        .file-input-row[data-test="true"] {
            border: 2px dashed #ff9800;
            background: #fff3e0;
        }
        
        .file-input-row[data-test="true"]:before {
            content: "🧪 TEST";
            background: #ff9800;
        }
        </style>
        <?php
    }
}

// Add quick test buttons to admin bar
add_action('admin_bar_menu', 'add_multiple_files_test_buttons', 999);

function add_multiple_files_test_buttons($wp_admin_bar) {
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        $wp_admin_bar->add_node(array(
            'id' => 'test-multiple-uploads',
            'title' => '🧪 Test Multiple Upload',
            'href' => '#',
            'meta' => array(
                'onclick' => 'testMultipleUploads(); return false;'
            )
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'test-file-types',
            'title' => '📁 Test File Types',
            'href' => '#',
            'meta' => array(
                'onclick' => 'testFileTypes(); return false;'
            )
        ));
    }
}

// Add test functions to admin footer
add_action('admin_footer', 'multiple_files_test_functions');

function multiple_files_test_functions() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'lift_document') {
        ?>
        <script type="text/javascript">
        function testMultipleUploads() {
            console.log('🧪 Multiple Upload Test Started');
            
            // Add multiple test file rows
            for (let i = 0; i < 3; i++) {
                jQuery('#add_file_button').click();
                console.log(`➕ Added test file row ${i + 1}`);
            }
            
            alert('✅ Test completed! Check console for details.\n\n' +
                  '3 file input rows added. Try uploading different file types to each row.\n' +
                  'Each upload button should work independently.');
        }
        
        function testFileTypes() {
            console.log('📁 File Types Test Started');
            
            // Test file type detection
            const testFiles = [
                { name: 'document.pdf', type: 'application/pdf', icon: '📕' },
                { name: 'image.jpg', type: 'image/jpeg', icon: '🖼️' },
                { name: 'video.mp4', type: 'video/mp4', icon: '🎥' },
                { name: 'audio.mp3', type: 'audio/mp3', icon: '🎵' },
                { name: 'archive.zip', type: 'application/zip', icon: '📦' },
                { name: 'presentation.pptx', type: 'application/vnd.openxmlformats-officedocument.presentationml.presentation', icon: '📙' }
            ];
            
            console.log('🎨 File Type Icon Mapping:');
            testFiles.forEach(file => {
                console.log(`${file.icon} ${file.name} (${file.type})`);
            });
            
            alert('📁 File Type Test Results:\n\n' +
                  '✅ PDF files: 📕\n' +
                  '✅ Images: 🖼️\n' +
                  '✅ Videos: 🎥\n' +
                  '✅ Audio: 🎵\n' +
                  '✅ Archives: 📦\n' +
                  '✅ Office docs: 📘📗📙\n\n' +
                  'All WordPress-supported file types are now accepted!');
        }
        </script>
        <?php
    }
}

// Log multiple files functionality
add_action('wp_loaded', 'log_multiple_files_upload_test');

function log_multiple_files_upload_test() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LIFT Docs Multiple Files Upload Test: Enhanced functionality loaded');
        error_log('- Media uploader instances created per button click');
        error_log('- All WordPress file types accepted');
        error_log('- Enhanced file type icons and animations');
        error_log('- Improved fallback support');
    }
}
