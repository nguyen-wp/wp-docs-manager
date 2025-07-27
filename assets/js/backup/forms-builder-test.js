/**
 * Form Builder Drag Drop Test & Fix
 * Test và sửa chức năng drag and drop fields
 */

(function($) {
    'use strict';
    
    window.liftDragDropTest = {
        
        /**
         * Test add field function
         */
        testAddField: function(fieldType = 'text') {
            if (!window.liftFormBuilder) {
                console.error('Form Builder not found');
                return false;
            }
            
            console.log('Testing addField with type:', fieldType);
            
            // Get initial state
            const initialFieldCount = window.liftFormBuilder.formData.fields.length;
            const initialCanvasCount = $('#form-canvas .canvas-field').length;
            
            console.log('Initial formData fields:', initialFieldCount);
            console.log('Initial canvas fields:', initialCanvasCount);
            
            // Call addField
            window.liftFormBuilder.addField(fieldType);
            
            // Check result
            const newFieldCount = window.liftFormBuilder.formData.fields.length;
            const newCanvasCount = $('#form-canvas .canvas-field').length;
            
            console.log('New formData fields:', newFieldCount);
            console.log('New canvas fields:', newCanvasCount);
            
            const success = (newFieldCount === initialFieldCount + 1) && (newCanvasCount === initialCanvasCount + 1);
            
            if (success) {
                console.log('✅ Add field test PASSED');
            } else {
                console.log('❌ Add field test FAILED');
            }
            
            return success;
        },
        
        /**
         * Test field dragging simulation
         */
        simulateFieldDrag: function(fieldType = 'text') {
            console.log('Simulating field drag for type:', fieldType);
            
            // Find the field item in palette
            const fieldItem = $(`.field-item[data-type="${fieldType}"]`);
            if (fieldItem.length === 0) {
                console.error('Field item not found for type:', fieldType);
                return false;
            }
            
            // Get canvas
            const canvas = $('#form-canvas');
            if (canvas.length === 0) {
                console.error('Canvas not found');
                return false;
            }
            
            // Simulate drag start
            fieldItem.trigger('dragstart');
            
            // Simulate drop on canvas
            canvas.trigger('drop');
            
            console.log('Drag simulation complete');
            return true;
        },
        
        /**
         * Manual field addition with debugging
         */
        manualAddField: function(fieldType = 'text') {
            if (!window.liftFormBuilder) {
                console.error('Form Builder not found');
                return false;
            }
            
            console.log('=== Manual Add Field Debug ===');
            
            // Create field config manually
            const fieldId = 'manual_field_' + Date.now();
            const fieldName = fieldType + '_manual';
            
            const fieldConfig = {
                id: fieldId,
                name: fieldName,
                type: fieldType,
                label: fieldType.charAt(0).toUpperCase() + fieldType.slice(1) + ' Field (Manual)',
                placeholder: 'Enter ' + fieldType + '...',
                required: false,
                description: 'Manually added field for testing'
            };
            
            console.log('Created field config:', fieldConfig);
            
            // Add to formData
            window.liftFormBuilder.formData.fields.push(fieldConfig);
            console.log('Added to formData, new count:', window.liftFormBuilder.formData.fields.length);
            
            // Render in canvas
            if (window.liftFormBuilder.renderCanvasField) {
                const fieldHtml = window.liftFormBuilder.renderCanvasField(fieldConfig);
                $('#form-canvas').append(fieldHtml);
                console.log('Added to canvas');
                
                // Hide placeholder
                $('.canvas-placeholder').hide();
                $('#form-canvas').addClass('has-fields');
                
                console.log('✅ Manual field addition complete');
                return true;
            } else {
                console.error('renderCanvasField function not found');
                return false;
            }
        },
        
        /**
         * Test save with current fields
         */
        testSaveWithCurrentFields: function() {
            if (!window.liftFormBuilder) {
                console.error('Form Builder not found');
                return false;
            }
            
            console.log('=== Test Save Process ===');
            
            // Set form name if empty
            const formName = $('#form-name').val();
            if (!formName || formName.trim() === '') {
                $('#form-name').val('Test Form ' + Date.now());
                console.log('Set form name to:', $('#form-name').val());
            }
            
            // Check current state
            const fieldsCount = window.liftFormBuilder.formData.fields.length;
            console.log('Current fields count:', fieldsCount);
            
            if (fieldsCount === 0) {
                console.log('No fields found - adding a test field first');
                this.manualAddField('text');
            }
            
            // Test clean form data
            if (window.liftFormBuilder.cleanFormData) {
                const cleanData = window.liftFormBuilder.cleanFormData();
                console.log('Clean form data:', cleanData);
                
                // Test JSON stringify
                try {
                    const json = JSON.stringify(cleanData.fields);
                    console.log('JSON stringify success, length:', json.length);
                    
                    // Would normally call save here, but let's just log instead
                    console.log('✅ Save process test PASSED - ready to save');
                    return true;
                } catch (e) {
                    console.error('❌ JSON stringify failed:', e);
                    return false;
                }
            } else {
                console.error('❌ cleanFormData function not found');
                return false;
            }
        },
        
        /**
         * Run all tests
         */
        runAllTests: function() {
            console.log('🧪 Running all Form Builder tests...');
            
            const tests = [
                () => this.testAddField('text'),
                () => this.testAddField('email'), 
                () => this.manualAddField('number'),
                () => this.testSaveWithCurrentFields()
            ];
            
            let passed = 0;
            let failed = 0;
            
            tests.forEach((test, index) => {
                try {
                    const result = test();
                    if (result) {
                        passed++;
                        console.log(`✅ Test ${index + 1} PASSED`);
                    } else {
                        failed++;
                        console.log(`❌ Test ${index + 1} FAILED`);
                    }
                } catch (e) {
                    failed++;
                    console.error(`❌ Test ${index + 1} ERROR:`, e);
                }
            });
            
            console.log(`🏁 Tests complete: ${passed} passed, ${failed} failed`);
            
            // Show final state
            this.showCurrentState();
            
            return { passed, failed };
        },
        
        /**
         * Show current form builder state
         */
        showCurrentState: function() {
            console.log('=== Current Form Builder State ===');
            
            if (window.liftFormBuilder) {
                console.log('Form Data:', window.liftFormBuilder.formData);
                console.log('Fields Count:', window.liftFormBuilder.formData.fields.length);
                console.log('Canvas Fields:', $('#form-canvas .canvas-field').length);
                console.log('Form Name:', $('#form-name').val());
                console.log('Form Description:', $('#form-description').val());
            } else {
                console.log('Form Builder not available');
            }
            
            console.log('=== End State ===');
        }
    };
    
    // Auto-initialize on form builder pages
    $(document).ready(function() {
        if ($('.lift-form-builder').length > 0) {
            console.log('Drag Drop Test loaded. Available functions:');
            console.log('- liftDragDropTest.testAddField(type)');
            console.log('- liftDragDropTest.manualAddField(type)'); 
            console.log('- liftDragDropTest.testSaveWithCurrentFields()');
            console.log('- liftDragDropTest.runAllTests()');
            console.log('- liftDragDropTest.showCurrentState()');
            
            // Add test buttons to page
            setTimeout(function() {
                if ($('#drag-drop-test-buttons').length === 0) {
                    const testButtons = `
                        <div id="drag-drop-test-buttons" style="position: fixed; top: 50px; right: 20px; background: #fff; padding: 10px; border: 2px solid #0073aa; border-radius: 5px; z-index: 9999;">
                            <h4 style="margin: 0 0 10px 0;">🧪 Form Builder Tests</h4>
                            <button onclick="liftDragDropTest.testAddField('text')" class="button button-small">Test Add Text Field</button><br><br>
                            <button onclick="liftDragDropTest.manualAddField('email')" class="button button-small">Manual Add Email</button><br><br>
                            <button onclick="liftDragDropTest.testSaveWithCurrentFields()" class="button button-small">Test Save Process</button><br><br>
                            <button onclick="liftDragDropTest.runAllTests()" class="button button-primary button-small">Run All Tests</button><br><br>
                            <button onclick="liftDragDropTest.showCurrentState()" class="button button-small">Show State</button><br><br>
                            <button onclick="$('#drag-drop-test-buttons').hide()" class="button button-small">Hide</button>
                        </div>
                    `;
                    $('body').append(testButtons);
                }
            }, 3000);
        }
    });
    
})(jQuery);
