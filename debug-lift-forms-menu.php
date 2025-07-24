<?php
/**
 * Debug LIFT Forms Menu
 * 
 * Check why LIFT Forms menu is not showing up
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add debug menu
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug LIFT Forms',
        'Debug LIFT Forms',
        'manage_options',
        'debug-lift-forms',
        'debug_lift_forms_page'
    );
});

function debug_lift_forms_page() {
    ?>
    <div class="wrap">
        <h1>Debug LIFT Forms Menu</h1>
        
        <h2>Class Check</h2>
        <p><strong>LIFT_Forms class exists:</strong> <?php echo class_exists('LIFT_Forms') ? 'YES' : 'NO'; ?></p>
        
        <h2>Post Type Check</h2>
        <?php
        $post_types = get_post_types(array(), 'objects');
        $lift_document_exists = isset($post_types['lift_document']);
        ?>
        <p><strong>lift_document post type exists:</strong> <?php echo $lift_document_exists ? 'YES' : 'NO'; ?></p>
        
        <?php if ($lift_document_exists): ?>
            <p><strong>lift_document capabilities:</strong></p>
            <ul>
                <?php
                $post_type_obj = $post_types['lift_document'];
                foreach ($post_type_obj->cap as $cap_name => $cap_value) {
                    echo '<li>' . $cap_name . ': ' . $cap_value . '</li>';
                }
                ?>
            </ul>
        <?php endif; ?>
        
        <h2>Current User Capabilities</h2>
        <?php
        $current_user = wp_get_current_user();
        $user_caps = $current_user->allcaps;
        $relevant_caps = array();
        
        foreach ($user_caps as $cap => $has_cap) {
            if ($has_cap && (strpos($cap, 'lift') !== false || strpos($cap, 'manage') !== false || strpos($cap, 'edit') !== false)) {
                $relevant_caps[$cap] = $has_cap;
            }
        }
        ?>
        <p><strong>User ID:</strong> <?php echo $current_user->ID; ?></p>
        <p><strong>User Roles:</strong> <?php echo implode(', ', $current_user->roles); ?></p>
        <p><strong>Relevant Capabilities:</strong></p>
        <ul>
            <?php foreach ($relevant_caps as $cap => $has_cap): ?>
                <li><?php echo $cap; ?>: <?php echo $has_cap ? 'YES' : 'NO'; ?></li>
            <?php endforeach; ?>
        </ul>
        
        <h2>Menu Check</h2>
        <?php
        global $submenu;
        $lift_document_menu = isset($submenu['edit.php?post_type=lift_document']) ? $submenu['edit.php?post_type=lift_document'] : array();
        ?>
        <p><strong>lift_document submenu items:</strong></p>
        <ul>
            <?php if (empty($lift_document_menu)): ?>
                <li>No submenu items found</li>
            <?php else: ?>
                <?php foreach ($lift_document_menu as $menu_item): ?>
                    <li><?php echo implode(' | ', $menu_item); ?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        
        <h2>Hook Check</h2>
        <p><strong>admin_menu hook has LIFT_Forms:</strong> 
        <?php 
        global $wp_filter;
        $admin_menu_hooks = isset($wp_filter['admin_menu']) ? $wp_filter['admin_menu'] : array();
        $has_lift_forms = false;
        
        foreach ($admin_menu_hooks as $priority => $hooks) {
            foreach ($hooks as $hook) {
                if (is_array($hook['function']) && isset($hook['function'][0]) && is_object($hook['function'][0]) && get_class($hook['function'][0]) === 'LIFT_Forms') {
                    $has_lift_forms = true;
                    break 2;
                }
            }
        }
        echo $has_lift_forms ? 'YES' : 'NO';
        ?>
        </p>
        
        <h2>Files Check</h2>
        <p><strong>class-lift-forms.php exists:</strong> <?php echo file_exists(LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-forms.php') ? 'YES' : 'NO'; ?></p>
        
        <h2>Test Actions</h2>
        <p><a href="<?php echo admin_url('edit.php?post_type=lift_document&page=lift-forms'); ?>" class="button">Try Direct LIFT Forms Link</a></p>
        <p><a href="<?php echo admin_url('edit.php?post_type=lift_document&page=lift-forms-builder'); ?>" class="button">Try Direct Form Builder Link</a></p>
        
        <h2>Manual Menu Creation Test</h2>
        <?php
        if (isset($_GET['test_manual']) && $_GET['test_manual'] === '1') {
            // Manually try to create the menu
            add_submenu_page(
                'edit.php?post_type=lift_document',
                'LIFT Forms (Test)',
                'Forms (Test)',
                'manage_options',
                'lift-forms-test',
                function() {
                    echo '<div class="wrap"><h1>LIFT Forms Test Menu</h1><p>This menu was created manually for testing.</p></div>';
                }
            );
            echo '<p style="color: green;">Manual menu created! Check if "Forms (Test)" appears in LIFT Documents menu.</p>';
        } else {
            echo '<p><a href="' . add_query_arg('test_manual', '1') . '" class="button">Create Test Menu</a></p>';
        }
        ?>
    </div>
    <?php
}
