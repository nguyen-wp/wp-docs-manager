<?php
/**
 * Test No Animations in Document Dashboard
 * Verify that all animations have been removed
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Administrator access required.');
}

echo '<h1>LIFT Docs - Animation Removal Test</h1>';
echo '<p><strong>Testing:</strong> Verify that all animations have been removed from Document Dashboard</p>';

// Test 1: Check CSS files for animations
echo '<h2>Test 1: CSS Animation Check</h2>';

$css_files = [
    'frontend-login.css' => 'assets/css/frontend-login.css',
    'admin.css' => 'assets/css/admin.css'
];

echo '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd;">';
echo '<tr style="background: #f1f1f1;">';
echo '<th style="padding: 8px; border: 1px solid #ccc;">CSS File</th>';
echo '<th style="padding: 8px; border: 1px solid #ccc;">Status</th>';
echo '<th style="padding: 8px; border: 1px solid #ccc;">Notes</th>';
echo '</tr>';

foreach ($css_files as $file_name => $file_path) {
    $full_path = plugin_dir_path(__FILE__) . $file_path;
    echo '<tr>';
    echo '<td style="padding: 8px; border: 1px solid #ccc;"><strong>' . $file_name . '</strong></td>';
    
    if (file_exists($full_path)) {
        $css_content = file_get_contents($full_path);
        
        // Check for animation keywords
        $animation_keywords = ['@keyframes', 'animation:', 'fadeIn', 'fadeOut', 'transition:'];
        $found_animations = [];
        
        foreach ($animation_keywords as $keyword) {
            if (stripos($css_content, $keyword) !== false) {
                $found_animations[] = $keyword;
            }
        }
        
        if (empty($found_animations)) {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #007cba;">✅ No animations found</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc;">All animations removed</td>';
        } else {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;">⚠️ Animations found</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc;">Found: ' . implode(', ', $found_animations) . '</td>';
        }
    } else {
        echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;">❌ File not found</td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">CSS file missing</td>';
    }
    echo '</tr>';
}
echo '</table>';

// Test 2: Check JavaScript files for animations
echo '<h2>Test 2: JavaScript Animation Check</h2>';

$js_files = [
    'frontend-login.js' => 'assets/js/frontend-login.js',
    'admin.js' => 'assets/js/admin.js'
];

echo '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd;">';
echo '<tr style="background: #f1f1f1;">';
echo '<th style="padding: 8px; border: 1px solid #ccc;">JS File</th>';
echo '<th style="padding: 8px; border: 1px solid #ccc;">Status</th>';
echo '<th style="padding: 8px; border: 1px solid #ccc;">Notes</th>';
echo '</tr>';

foreach ($js_files as $file_name => $file_path) {
    $full_path = plugin_dir_path(__FILE__) . $file_path;
    echo '<tr>';
    echo '<td style="padding: 8px; border: 1px solid #ccc;"><strong>' . $file_name . '</strong></td>';
    
    if (file_exists($full_path)) {
        $js_content = file_get_contents($full_path);
        
        // Check for animation methods
        $animation_methods = ['fadeIn(', 'fadeOut(', 'slideUp(', 'slideDown(', 'animate('];
        $found_methods = [];
        
        foreach ($animation_methods as $method) {
            if (stripos($js_content, $method) !== false) {
                $found_methods[] = $method;
            }
        }
        
        if (empty($found_methods)) {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #007cba;">✅ No animations found</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc;">All jQuery animations removed</td>';
        } else {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;">⚠️ Animations found</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc;">Found: ' . implode(', ', $found_methods) . '</td>';
        }
    } else {
        echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;">❌ File not found</td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">JS file missing</td>';
    }
    echo '</tr>';
}
echo '</table>';

// Test 3: Check PHP files for inline animations
echo '<h2>Test 3: PHP Inline Animation Check</h2>';

$php_files = [
    'Frontend Login' => 'includes/class-lift-docs-frontend-login.php',
    'Admin' => 'includes/class-lift-docs-admin.php',
    'Settings' => 'includes/class-lift-docs-settings.php'
];

echo '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd;">';
echo '<tr style="background: #f1f1f1;">';
echo '<th style="padding: 8px; border: 1px solid #ccc;">PHP File</th>';
echo '<th style="padding: 8px; border: 1px solid #ccc;">Animation Status</th>';
echo '<th style="padding: 8px; border: 1px solid #ccc;">Notes</th>';
echo '</tr>';

foreach ($php_files as $file_name => $file_path) {
    $full_path = plugin_dir_path(__FILE__) . $file_path;
    echo '<tr>';
    echo '<td style="padding: 8px; border: 1px solid #ccc;"><strong>' . $file_name . '</strong></td>';
    
    if (file_exists($full_path)) {
        $php_content = file_get_contents($full_path);
        
        // Check for inline CSS animations
        $inline_animations = ['transition:', 'animation:', '@keyframes', 'fadeIn', 'fadeOut'];
        $found_inline = [];
        
        foreach ($inline_animations as $keyword) {
            $count = substr_count(strtolower($php_content), strtolower($keyword));
            if ($count > 0) {
                $found_inline[] = $keyword . ' (' . $count . ')';
            }
        }
        
        if (empty($found_inline)) {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #007cba;">✅ Clean</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc;">No inline animations</td>';
        } else {
            echo '<td style="padding: 8px; border: 1px solid #ccc; color: #ff9800;">⚠️ Some found</td>';
            echo '<td style="padding: 8px; border: 1px solid #ccc;">Found: ' . implode(', ', $found_inline) . '</td>';
        }
    } else {
        echo '<td style="padding: 8px; border: 1px solid #ccc; color: #d63638;">❌ File not found</td>';
        echo '<td style="padding: 8px; border: 1px solid #ccc;">PHP file missing</td>';
    }
    echo '</tr>';
}
echo '</table>';

// Test 4: Summary and recommendations
echo '<h2>Test 4: Summary</h2>';
echo '<div style="background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 5px; padding: 15px; margin: 15px 0;">';
echo '<h4 style="color: #0c5460; margin-top: 0;">Animation Removal Summary:</h4>';
echo '<ul style="color: #0c5460;">';
echo '<li><strong>CSS Files:</strong> Removed transitions, animations, @keyframes, and fade effects</li>';
echo '<li><strong>JavaScript Files:</strong> Replaced fadeIn/fadeOut with show/hide</li>';
echo '<li><strong>PHP Files:</strong> Removed inline CSS animations and transitions</li>';
echo '<li><strong>Spinner Animations:</strong> Removed all spinning loading indicators</li>';
echo '<li><strong>Hover Effects:</strong> Kept hover states but removed transitions</li>';
echo '</ul>';
echo '</div>';

echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 15px 0;">';
echo '<h4 style="color: #155724; margin-top: 0;">Benefits:</h4>';
echo '<ul style="color: #155724;">';
echo '<li><strong>Performance:</strong> Faster page load and interaction</li>';
echo '<li><strong>Accessibility:</strong> Better for users with motion sensitivity</li>';
echo '<li><strong>Simplicity:</strong> Clean, instant UI responses</li>';
echo '<li><strong>Battery Life:</strong> Less CPU usage on mobile devices</li>';
echo '</ul>';
echo '</div>';

echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;">';
echo '<h4 style="color: #856404; margin-top: 0;">Testing Steps:</h4>';
echo '<ol style="color: #856404;">';
echo '<li>Login to document dashboard with a document user</li>';
echo '<li>Verify that document cards appear instantly (no fade in)</li>';
echo '<li>Test search/filter functionality - should show/hide instantly</li>';
echo '<li>Check buttons and links - should respond immediately</li>';
echo '<li>Verify forms submit without animation delays</li>';
echo '</ol>';
echo '</div>';

echo '<p><a href="' . home_url('/document-dashboard/') . '" class="button button-primary" target="_blank">Test Dashboard</a> ';
echo '<a href="' . home_url('/document-login/') . '" class="button" target="_blank">Test Login</a></p>';
?>
