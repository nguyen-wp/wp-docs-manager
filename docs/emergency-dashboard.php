<?php
/**
 * Emergency Dashboard Page
 */

// Include WordPress
require_once('../../../wp-config.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php'));
    exit;
}

$current_user = wp_get_current_user();

// Check if user has document access
if (!in_array('documents_user', $current_user->roles) && !current_user_can('view_lift_documents')) {
    wp_die('You do not have permission to access this page.');
}

// Get user's documents
$user_documents = get_posts(array(
    'post_type' => 'lift_document',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => 'lift_docs_assigned_users',
            'value' => '"' . $current_user->ID . '"',
            'compare' => 'LIKE'
        )
    )
));

get_header();
?>

<style>
.docs-dashboard {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
}

.dashboard-header {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.document-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
}

.document-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
}

.document-actions {
    margin-top: 15px;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-right: 10px;
    font-size: 14px;
}

.btn:hover {
    background: #005a87;
    color: white;
}

.btn-secondary {
    background: #666;
}

.btn-secondary:hover {
    background: #444;
}

.user-info {
    background: #e3f2fd;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 3px solid #1976d2;
}
</style>

<div class="docs-dashboard">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo esc_html($current_user->display_name); ?>!</h1>
        
        <div class="user-info">
            <strong>User Information:</strong><br>
            Email: <?php echo esc_html($current_user->user_email); ?><br>
            User Code: <?php echo esc_html(get_user_meta($current_user->ID, 'lift_docs_user_code', true)); ?><br>
            Documents Available: <?php echo count($user_documents); ?>
        </div>
        
        <p>
            <a href="<?php echo wp_logout_url(home_url('/wp-content/plugins/wp-docs-manager/emergency-login.php')); ?>" class="btn btn-secondary">Logout</a>
            <a href="<?php echo home_url(); ?>" class="btn">Back to Home</a>
        </p>
    </div>
    
    <div class="documents-section">
        <h2>Your Documents</h2>
        
        <?php if (!empty($user_documents)): ?>
        <div class="documents-grid">
            <?php foreach ($user_documents as $document): ?>
            <div class="document-card">
                <div class="document-title"><?php echo esc_html($document->post_title); ?></div>
                
                <?php if ($document->post_content): ?>
                <div class="document-description">
                    <?php echo wp_trim_words($document->post_content, 20); ?>
                </div>
                <?php endif; ?>
                
                <div class="document-actions">
                    <?php 
                    $file_id = get_post_meta($document->ID, 'lift_docs_file', true);
                    if ($file_id) {
                        $file_url = wp_get_attachment_url($file_id);
                        if ($file_url) {
                            echo '<a href="' . esc_url($file_url) . '" target="_blank" class="btn">View</a>';
                            echo '<a href="' . esc_url($file_url) . '" download class="btn btn-secondary">Download</a>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php else: ?>
        <div class="user-info">
            <p>No documents are currently assigned to your account.</p>
            <p>Please contact the administrator if you believe this is an error.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
?>
