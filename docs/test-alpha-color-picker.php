<?php
/**
 * Test file for Alpha Color Picker functionality
 * Run this to verify that transparent colors are working
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>LIFT Docs Alpha Color Picker Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f0f0f0;
        }
        .demo-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        .color-demo {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .color-preview {
            width: 100px;
            height: 50px;
            border: 1px solid #ccc;
            margin: 10px 0;
            background-size: 20px 20px;
            background-image: 
                linear-gradient(45deg, #f0f0f0 25%, transparent 25%), 
                linear-gradient(-45deg, #f0f0f0 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, #f0f0f0 75%), 
                linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }
        .instructions {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <h1>🎨 LIFT Docs Alpha Color Picker Test</h1>
        
        <div class="instructions">
            <h3>📋 Test Instructions:</h3>
            <p>This test page demonstrates the new transparent color picker functionality.</p>
            <ul>
                <li>✅ Color pickers now support RGBA values (transparency)</li>
                <li>✅ You can set alpha values from 0 (fully transparent) to 1 (fully opaque)</li>
                <li>✅ Colors are validated to ensure proper format</li>
                <li>✅ Default values are provided if invalid colors are entered</li>
            </ul>
        </div>

        <h2>🎯 Test Cases</h2>
        
        <div class="color-demo">
            <h3>Test 1: Background Color with Transparency</h3>
            <p>Current setting: <code><?php echo esc_html(get_option('lift_docs_login_bg_color', '#f0f4f8')); ?></code></p>
            <div class="color-preview" style="background-color: <?php echo esc_attr(get_option('lift_docs_login_bg_color', '#f0f4f8')); ?>;"></div>
            <p><em>Try setting: rgba(240, 244, 248, 0.5) for 50% transparency</em></p>
        </div>

        <div class="color-demo">
            <h3>Test 2: Form Background with Transparency</h3>
            <p>Current setting: <code><?php echo esc_html(get_option('lift_docs_login_form_bg', '#ffffff')); ?></code></p>
            <div class="color-preview" style="background-color: <?php echo esc_attr(get_option('lift_docs_login_form_bg', '#ffffff')); ?>;"></div>
            <p><em>Try setting: rgba(255, 255, 255, 0.8) for 80% white</em></p>
        </div>

        <div class="color-demo">
            <h3>Test 3: Button Color with Transparency</h3>
            <p>Current setting: <code><?php echo esc_html(get_option('lift_docs_login_btn_color', '#1976d2')); ?></code></p>
            <div class="color-preview" style="background-color: <?php echo esc_attr(get_option('lift_docs_login_btn_color', '#1976d2')); ?>;"></div>
            <p><em>Try setting: rgba(25, 118, 210, 0.9) for 90% blue</em></p>
        </div>

        <div class="color-demo">
            <h3>Test 4: Input Border with Transparency</h3>
            <p>Current setting: <code><?php echo esc_html(get_option('lift_docs_login_input_color', '#e0e0e0')); ?></code></p>
            <div class="color-preview" style="background-color: <?php echo esc_attr(get_option('lift_docs_login_input_color', '#e0e0e0')); ?>;"></div>
            <p><em>Try setting: rgba(224, 224, 224, 0.6) for 60% gray</em></p>
        </div>

        <div class="color-demo">
            <h3>Test 5: Text Color with Transparency</h3>
            <p>Current setting: <code><?php echo esc_html(get_option('lift_docs_login_text_color', '#333333')); ?></code></p>
            <div class="color-preview" style="background-color: <?php echo esc_attr(get_option('lift_docs_login_text_color', '#333333')); ?>;"></div>
            <p><em>Try setting: rgba(51, 51, 51, 0.85) for 85% dark gray</em></p>
        </div>

        <h2>🔧 How to Test</h2>
        <ol>
            <li>Go to <strong>LIFT Docs → Settings → Interface Tab</strong></li>
            <li>Look for the color picker fields</li>
            <li>Click on any color picker to open it</li>
            <li>You should see an additional <strong>Alpha slider</strong> at the bottom</li>
            <li>Adjust the alpha slider to change transparency</li>
            <li>Try entering RGBA values directly in the text field</li>
            <li>Save settings and check this page to see the results</li>
        </ol>

        <h2>✨ Supported Color Formats</h2>
        <ul>
            <li><strong>Hex:</strong> #ffffff, #fff</li>
            <li><strong>RGB:</strong> rgb(255, 255, 255)</li>
            <li><strong>RGBA:</strong> rgba(255, 255, 255, 0.5)</li>
        </ul>

        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <small>
                <strong>Note:</strong> The transparent color picker uses the wp-color-picker-alpha library 
                which extends WordPress's built-in color picker to support alpha transparency.
            </small>
        </div>
    </div>
</body>
</html>
