<?php
/**
 * Debug LIFT Forms Drag and Drop
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

echo "<h2>LIFT Forms Drag & Drop Debug</h2>";

// Check jQuery UI scripts
echo "<h3>Scripts Status:</h3>";
$scripts = wp_scripts();

$required_scripts = array(
    'jquery',
    'jquery-ui-core',
    'jquery-ui-sortable',
    'jquery-ui-draggable',
    'jquery-ui-droppable',
    'lift-forms-builder'
);

foreach ($required_scripts as $script) {
    $loaded = isset($scripts->registered[$script]);
    echo "<p>" . ($loaded ? "✅" : "❌") . " $script</p>";
}

echo "<h3>Quick Drag & Drop Test HTML:</h3>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Drag & Drop Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
    <style>
        .field-palette {
            width: 200px;
            float: left;
            background: #f9f9f9;
            padding: 20px;
            margin-right: 20px;
        }
        .field-item {
            background: #fff;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            cursor: grab;
            border-radius: 4px;
        }
        .field-item:hover {
            background: #f0f0f0;
        }
        .form-canvas {
            width: 400px;
            float: left;
        }
        .canvas-content {
            min-height: 400px;
            background: #fff;
            border: 2px dashed #ddd;
            padding: 20px;
            border-radius: 4px;
        }
        .canvas-content.drag-over {
            background: #f0f8ff;
            border-color: #0073aa;
        }
        .canvas-field {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            cursor: move;
        }
        .ui-sortable-placeholder {
            background: #f0f8ff;
            border: 2px dashed #0073aa;
            height: 50px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h2>Drag & Drop Test</h2>
    
    <div class="field-palette">
        <h3>Field Palette</h3>
        <div class="field-item" data-type="text">📝 Text Input</div>
        <div class="field-item" data-type="email">📧 Email</div>
        <div class="field-item" data-type="textarea">📄 Textarea</div>
        <div class="field-item" data-type="number">🔢 Number</div>
        <div class="field-item" data-type="date">📅 Date</div>
    </div>
    
    <div class="form-canvas">
        <h3>Form Canvas</h3>
        <div class="canvas-content" id="canvas">
            <p style="text-align: center; color: #666;">Drag fields here to build your form</p>
        </div>
    </div>
    
    <div style="clear: both;"></div>
    
    <script>
    $(document).ready(function() {
        let fieldCounter = 0;
        
        // Make field items draggable
        $('.field-item').draggable({
            helper: 'clone',
            cursor: 'grabbing',
            opacity: 0.8,
            revert: 'invalid',
            start: function(event, ui) {
                ui.helper.css('z-index', 1000);
                $('.canvas-content').addClass('drag-over');
            },
            stop: function(event, ui) {
                $('.canvas-content').removeClass('drag-over');
            }
        });
        
        // Make canvas droppable
        $('.canvas-content').droppable({
            accept: '.field-item',
            tolerance: 'pointer',
            drop: function(event, ui) {
                const fieldType = ui.draggable.data('type');
                addField(fieldType);
                $(this).removeClass('drag-over');
            },
            over: function() {
                $(this).addClass('drag-over');
            },
            out: function() {
                $(this).removeClass('drag-over');
            }
        });
        
        // Make canvas sortable
        $('.canvas-content').sortable({
            items: '.canvas-field',
            placeholder: 'ui-sortable-placeholder',
            tolerance: 'pointer'
        });
        
        function addField(type) {
            fieldCounter++;
            const fieldHtml = `
                <div class="canvas-field" data-type="${type}" data-id="field_${fieldCounter}">
                    <strong>${getFieldLabel(type)}</strong>
                    <p>Field ID: field_${fieldCounter}</p>
                    <button onclick="$(this).parent().remove()" style="float: right;">Delete</button>
                    <div style="clear: both;"></div>
                </div>
            `;
            
            // Remove placeholder text
            $('.canvas-content p').remove();
            $('.canvas-content').append(fieldHtml);
        }
        
        function getFieldLabel(type) {
            const labels = {
                text: '📝 Text Input',
                email: '📧 Email Address',
                textarea: '📄 Textarea',
                number: '🔢 Number',
                date: '📅 Date'
            };
            return labels[type] || type;
        }
        
        console.log('Drag & Drop initialized successfully');
        console.log('jQuery version:', $.fn.jquery);
        console.log('jQuery UI version:', $.ui ? $.ui.version : 'Not loaded');
    });
    </script>
</body>
</html>

<?php
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Try dragging items from the Field Palette to the Form Canvas</li>";
echo "<li>Check browser console for any JavaScript errors</li>";
echo "<li>If this test works, the issue is in the LIFT Forms integration</li>";
echo "<li>If this doesn't work, it's a jQuery UI loading issue</li>";
echo "</ol>";

echo "<h3>Check WordPress Admin:</h3>";
echo "<ul>";
echo "<li>Go to Form Builder page: <a href='" . admin_url('admin.php?page=lift-forms-builder') . "' target='_blank'>Open Form Builder</a></li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Check Console tab for JavaScript errors</li>";
echo "<li>Check Network tab to see if scripts are loading</li>";
echo "</ul>";
?>
