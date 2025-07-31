<?php
/**
 * Dashboard Widget for LIFT Docs System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LIFT_Docs_Dashboard_Widget {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_widget_styles'));
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        // Only show to users who can manage documents or have documents_user role
        if (!current_user_can('manage_options') && !in_array('documents_user', wp_get_current_user()->roles)) {
            return;
        }

        wp_add_dashboard_widget(
            'lift_docs_dashboard_widget',
            '' . __('LIFT Documents Overview', 'lift-docs-system'),
            array($this, 'render_dashboard_widget'),
            null,
            null,
            'normal',
            'high'
        );
    }

    /**
     * Enqueue widget styles
     */
    public function enqueue_widget_styles($hook) {
        if ($hook !== 'index.php') {
            return;
        }

        // Enqueue Font Awesome for icons
        wp_enqueue_style('font-awesome-6', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
        
        // Add custom styles for the widget
        wp_add_inline_style('dashboard', $this->get_widget_css());
    }

    /**
     * Get widget CSS
     */
    private function get_widget_css() {
        return '
        .lift-docs-widget {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .lift-docs-widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .lift-docs-widget-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .lift-docs-stat-box {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #ddd;
        }
        
        .lift-docs-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            display: block;
        }
        
        .lift-docs-stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .lift-docs-documents-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .lift-docs-documents-table th {
            background: #f1f1f1;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        .lift-docs-documents-table th:first-child {
            width: 70%;
        }
        
        .lift-docs-documents-table th:last-child {
            width: 30%;
            text-align: center;
        }
        
        .lift-docs-documents-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e1e5e9;
            vertical-align: middle;
        }
        
        .lift-docs-documents-table td:last-child {
            text-align: center;
        }
        
        .lift-docs-documents-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .lift-docs-combined-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .lift-docs-doc-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .lift-docs-separator {
            color: #ccc;
            font-weight: bold;
        }
        
        .lift-docs-doc-name {
            font-weight: 600;
            color: #0073aa;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .lift-docs-status {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .lift-docs-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .lift-docs-status.processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .lift-docs-status.done {
            background: #d4edda;
            color: #155724;
        }
        
        .lift-docs-status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .lift-docs-assigned-users {
            font-size: 11px;
            color: #666;
        }
        
        .lift-docs-assigned-users span {
            font-size: 11px;
        }
        
        .lift-docs-user-count {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
        }
        
        .lift-docs-action-btn {
            background: #0073aa;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .lift-docs-action-btn:hover {
            background: #005a87;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .lift-docs-widget-footer {
            margin-top: 15px;
            text-align: center;
            padding: 15px 0 12px ;
        }
        
        .lift-docs-view-all-btn {
            background: #f1f1f1;
            color: #0073aa;
            border: 1px solid #0073aa;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .lift-docs-view-all-btn:hover {
            background: #0073aa;
            color: white;
            text-decoration: none;
        }
        
        .lift-docs-no-documents {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .lift-docs-no-documents i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        ';
    }

    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        
        // Get documents based on user role
        if ($is_admin) {
            // Admin sees all documents
            $documents = $this->get_all_documents();
        } else {
            // Regular users see only their assigned documents
            $documents = $this->get_user_documents($current_user->ID);
        }

        // Get statistics
        $stats = $this->get_documents_stats($current_user->ID, $is_admin);

        ?>
        <div class="lift-docs-widget">
            
            <!-- Statistics Section -->
            <div class="lift-docs-widget-stats">
                <div class="lift-docs-stat-box">
                    <span class="lift-docs-stat-number"><?php echo $stats['total']; ?></span>
                    <span class="lift-docs-stat-label"><?php _e('Total', 'lift-docs-system'); ?></span>
                </div>
                <div class="lift-docs-stat-box">
                    <span class="lift-docs-stat-number"><?php echo $stats['pending']; ?></span>
                    <span class="lift-docs-stat-label"><?php _e('Pending', 'lift-docs-system'); ?></span>
                </div>
                <div class="lift-docs-stat-box">
                    <span class="lift-docs-stat-number"><?php echo $stats['processing']; ?></span>
                    <span class="lift-docs-stat-label"><?php _e('Processing', 'lift-docs-system'); ?></span>
                </div>
                <div class="lift-docs-stat-box">
                    <span class="lift-docs-stat-number"><?php echo $stats['done']; ?></span>
                    <span class="lift-docs-stat-label"><?php _e('Completed', 'lift-docs-system'); ?></span>
                </div>
            </div>

            <!-- Documents Table -->
            <?php if (!empty($documents)): ?>
                <table class="lift-docs-documents-table">
                    <thead>
                        <tr>
                            <th><?php _e('Document Details', 'lift-docs-system'); ?></th>
                            <th><?php _e('Action', 'lift-docs-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <?php
                            $assigned_users = $this->get_document_assigned_users($doc->ID);
                            $status = get_post_meta($doc->ID, '_lift_doc_status', true) ?: 'pending';
                            $filter_url = $this->get_documents_list_url($assigned_users, $is_admin);
                            ?>
                            <tr>
                                <td>
                                    <div class="lift-docs-combined-info">
                                        <div class="lift-docs-doc-name" title="<?php echo esc_attr($doc->post_title); ?>">
                                            <?php echo esc_html($doc->post_title); ?>
                                        </div>
                                        <div class="lift-docs-doc-meta">
                                            <span class="lift-docs-status <?php echo esc_attr($status); ?>">
                                                <?php echo esc_html(ucfirst($status)); ?>
                                            </span>
                                            <span class="lift-docs-separator">•</span>
                                            <span class="lift-docs-assigned-users">
                                                <?php echo $this->render_assigned_users_column($assigned_users); ?>
                                            </span>
                                        </div>
                                        <small style="color: #666; display: block; margin-top: 5px;">
                                            <?php echo date_i18n(get_option('date_format'), strtotime($doc->post_date)); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($filter_url); ?>" class="lift-docs-action-btn">
                                        <?php _e('View', 'lift-docs-system'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="lift-docs-no-documents">
                    <i class="fas fa-file-alt"></i>
                    <p><?php _e('No documents found.', 'lift-docs-system'); ?></p>
                    <?php if ($is_admin): ?>
                        <a href="<?php echo admin_url('post-new.php?post_type=lift_document'); ?>" class="lift-docs-action-btn">
                            <i class="fas fa-plus"></i> <?php _e('Create Document', 'lift-docs-system'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="lift-docs-widget-footer">
                <a href="<?php echo admin_url('edit.php?post_type=lift_document'); ?>" class="lift-docs-view-all-btn">
                    <i class="fas fa-list"></i> <?php _e('View All Documents', 'lift-docs-system'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Get all documents (for admin)
     */
    private function get_all_documents($limit = 10) {
        $args = array(
            'post_type' => 'lift_document',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_lift_doc_status',
                    'value' => array('pending', 'processing', 'done'),
                    'compare' => 'IN'
                ),
                array(
                    'key' => '_lift_doc_status',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        return get_posts($args);
    }

    /**
     * Get documents assigned to specific user
     */
    private function get_user_documents($user_id, $limit = 10) {
        $args = array(
            'post_type' => 'lift_document',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_lift_doc_assigned_users',
                    'value' => serialize(array($user_id)),
                    'compare' => 'LIKE'
                )
            )
        );

        return get_posts($args);
    }

    /**
     * Get documents statistics
     */
    private function get_documents_stats($user_id, $is_admin = false) {
        $base_args = array(
            'post_type' => 'lift_document',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        if (!$is_admin) {
            $base_args['meta_query'] = array(
                array(
                    'key' => '_lift_doc_assigned_users',
                    'value' => serialize(array($user_id)),
                    'compare' => 'LIKE'
                )
            );
        }

        // Get all documents
        $all_docs = get_posts($base_args);
        $total = count($all_docs);

        // Count by status
        $stats = array('total' => $total, 'pending' => 0, 'processing' => 0, 'done' => 0, 'cancelled' => 0);

        foreach ($all_docs as $doc_id) {
            $status = get_post_meta($doc_id, '_lift_doc_status', true) ?: 'pending';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    /**
     * Get assigned users for a document
     */
    private function get_document_assigned_users($doc_id) {
        $user_ids = get_post_meta($doc_id, '_lift_doc_assigned_users', true);
        
        if (empty($user_ids) || !is_array($user_ids)) {
            return array();
        }

        $users = array();
        foreach ($user_ids as $user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Render assigned users column like Documents list
     */
    private function render_assigned_users_column($assigned_users) {
        if (empty($assigned_users) || !is_array($assigned_users)) {
            return '<span style="color: #d63638; font-weight: 500;">' . __('Admin & Editor Only', 'lift-docs-system') . '</span>';
        }

        $user_count = count($assigned_users);
        $total_document_users = count(get_users(array('role' => 'documents_user')));

        if ($user_count === 0) {
            return '<span style="color: #d63638;">' . __('No Access', 'lift-docs-system') . '</span>';
        } elseif ($user_count === $total_document_users) {
            return '<span style="color: #007cba; font-weight: 500;">' . __('All Document Users Assigned', 'lift-docs-system') . '</span>';
        } else {
            $user_names = array();
            $max_display = 2; // Show fewer in widget for space

            for ($i = 0; $i < min($user_count, $max_display); $i++) {
                if (isset($assigned_users[$i])) {
                    $user_names[] = $assigned_users[$i]->display_name;
                }
            }

            if ($user_count > $max_display) {
                $remaining = $user_count - $max_display;
                return '<span style="color: #135e96; font-weight: 500;">' .
                       esc_html(implode(', ', $user_names)) .
                       ' <small style="color: #666;">+' . $remaining . ' ' . __('more', 'lift-docs-system') . '</small>' .
                       '</span>';
            } else {
                return '<span style="color: #135e96; font-weight: 500;">' . esc_html(implode(', ', $user_names)) . '</span>';
            }
        }
    }

    /**
     * Get documents list URL with user filter
     */
    private function get_documents_list_url($assigned_users = array(), $is_admin = false) {
        $base_url = admin_url('edit.php?post_type=lift_document');
        
        if (!$is_admin && !empty($assigned_users)) {
            // For regular users, filter by their own documents
            $current_user = wp_get_current_user();
            $base_url = add_query_arg('assigned_user', $current_user->ID, $base_url);
        } elseif ($is_admin && !empty($assigned_users)) {
            // For admin, filter by first assigned user
            $first_user = reset($assigned_users);
            $base_url = add_query_arg('assigned_user', $first_user->ID, $base_url);
        }

        return $base_url;
    }
}
