<?php
/**
 * Test file to initialize CSS/JS Editor
 * Visit this file directly to initialize default CSS/JS
 */

// This file should only be used for testing
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-config.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Initialize CSS/JS Editor
require_once(LIFT_DOCS_PLUGIN_DIR . 'includes/class-lift-docs-css-js-editor.php');
$editor = LIFT_Docs_CSS_JS_Editor::get_instance();
$editor->force_init_default_styles();

echo '<h1>LIFT Docs CSS/JS Editor Test</h1>';
echo '<p>Default CSS and JS have been initialized!</p>';

echo '<h2>Current CSS:</h2>';
$css = get_option('lift_docs_custom_css', '');
echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd;">' . esc_html($css) . '</pre>';

echo '<h2>Current JS:</h2>';
$js = get_option('lift_docs_custom_js', '');
echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd;">' . esc_html($js) . '</pre>';

echo '<p><a href="' . wp_get_referer() . '">← Go Back</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=lift-docs-css-editor') . '">Open CSS Editor</a> | ';
echo '<a href="' . admin_url('admin.php?page=lift-docs-js-editor') . '">Open JS Editor</a></p>';
?>
