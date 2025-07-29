<?php
/**
 * Test page for LIFT Forms with File Upload and Signature functionality
 * 
 * This page demonstrates:
 * 1. File upload with image preview
 * 2. Digital signature field with online signing
 * 3. Form styling matching other input fields
 */

// Create a test form in the database if it doesn't exist
function create_test_form_with_uploads() {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'lift_forms';
    
    // Check if test form already exists
    $existing_form = $wpdb->get_row("SELECT id FROM $forms_table WHERE name = 'Test Form with Uploads and Signature'");
    
    if (!$existing_form) {
        $form_fields = json_encode([
            [
                'id' => 'test_name',
                'name' => 'full_name',
                'type' => 'text',
                'label' => 'Họ và tên',
                'placeholder' => 'Nhập họ và tên đầy đủ',
                'required' => true,
                'description' => 'Vui lòng nhập họ và tên chính xác'
            ],
            [
                'id' => 'test_email',
                'name' => 'email',
                'type' => 'email', 
                'label' => 'Email',
                'placeholder' => 'example@domain.com',
                'required' => true,
                'description' => 'Email sẽ được sử dụng để liên lạc'
            ],
            [
                'id' => 'test_document',
                'name' => 'document_upload',
                'type' => 'file',
                'label' => 'Tài liệu đính kèm',
                'required' => true,
                'accept' => '.jpg,.jpeg,.png,.pdf,.doc,.docx',
                'description' => 'Tải lên hình ảnh hoặc tài liệu liên quan (JPG, PNG, PDF, DOC, DOCX)'
            ],
            [
                'id' => 'test_photo',
                'name' => 'photo_upload',
                'type' => 'file',
                'label' => 'Ảnh cá nhân',
                'required' => false,
                'accept' => '.jpg,.jpeg,.png,.gif',
                'description' => 'Tải lên ảnh cá nhân (tùy chọn)'
            ],
            [
                'id' => 'test_message',
                'name' => 'message',
                'type' => 'textarea',
                'label' => 'Lời nhắn',
                'placeholder' => 'Nhập lời nhắn của bạn...',
                'required' => false,
                'description' => 'Thông tin bổ sung hoặc ghi chú'
            ],
            [
                'id' => 'test_signature',
                'name' => 'signature',
                'type' => 'signature',
                'label' => 'Chữ ký điện tử',
                'required' => true,
                'description' => 'Vui lòng ký tên vào ô bên dưới để xác nhận thông tin'
            ]
        ]);
        
        $wpdb->insert(
            $forms_table,
            [
                'name' => 'Test Form with Uploads and Signature',
                'description' => 'Biểu mẫu demo với tính năng upload file và chữ ký điện tử',
                'form_fields' => $form_fields,
                'status' => 'active',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );
        
        return $wpdb->insert_id;
    }
    
    return $existing_form->id;
}

// Add the test page shortcode
add_shortcode('lift_test_upload_form', function($atts) {
    $form_id = create_test_form_with_uploads();
    
    // Add some intro text
    $output = '<div class="lift-test-form-intro">';
    $output .= '<h2 style="color: #1976d2; margin-bottom: 20px;">🚀 LIFT Forms - Demo Tính Năng Mới</h2>';
    $output .= '<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #1976d2;">';
    $output .= '<h3 style="margin-top: 0; color: #1976d2;">✨ Tính năng được thêm:</h3>';
    $output .= '<ul style="margin: 15px 0; color: #333;">';
    $output .= '<li><strong>📁 Upload File với Preview:</strong> Kéo thả file hoặc chọn file, xem trước ảnh ngay lập tức</li>';
    $output .= '<li><strong>✍️ Chữ ký điện tử:</strong> Ký trực tuyến bằng chuột hoặc ngón tay, lưu tự động</li>';
    $output .= '<li><strong>🎨 Giao diện đồng nhất:</strong> Style giống các input field khác, gọn gàng và hiện đại</li>';
    $output .= '<li><strong>💾 Lưu trữ bảo mật:</strong> File lưu trong /wp-content/uploads/, chữ ký mã hóa MD5</li>';
    $output .= '</ul>';
    $output .= '<p style="margin-bottom: 0; font-style: italic; color: #666;">Hãy thử upload một vài file và ký tên để trải nghiệm!</p>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Render the form using existing shortcode
    $output .= do_shortcode('[lift_form id="' . $form_id . '"]');
    
    return $output;
});

// Add admin notice to show shortcode usage
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'lift-forms') {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>🎉 Tính năng mới!</strong> Sử dụng shortcode <code>[lift_test_upload_form]</code> để xem demo Upload File và Chữ ký điện tử.</p>';
        echo '</div>';
    }
});

?>
