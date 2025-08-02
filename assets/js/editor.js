jQuery(document).ready(function($) {
    'use strict';

    var cssEditor, jsEditor;

    // Initialize CodeMirror editors
    function initializeEditors() {
        // CSS Editor
        if ($('#custom-css-editor').length) {
            cssEditor = wp.codeEditor.initialize($('#custom-css-editor'), {
                type: 'text/css',
                codemirror: {
                    mode: 'css',
                    theme: 'default',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 2,
                    indentWithTabs: false,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    foldGutter: true,
                    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    extraKeys: {
                        'Ctrl-S': function(cm) {
                            saveCSSCode();
                        },
                        'Cmd-S': function(cm) {
                            saveCSSCode();
                        },
                        'Ctrl-/': 'toggleComment',
                        'Cmd-/': 'toggleComment'
                    }
                }
            });
        }

        // JS Editor
        if ($('#custom-js-editor').length) {
            jsEditor = wp.codeEditor.initialize($('#custom-js-editor'), {
                type: 'application/javascript',
                codemirror: {
                    mode: 'javascript',
                    theme: 'default',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 2,
                    indentWithTabs: false,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    foldGutter: true,
                    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    extraKeys: {
                        'Ctrl-S': function(cm) {
                            saveJSCode();
                        },
                        'Cmd-S': function(cm) {
                            saveJSCode();
                        },
                        'Ctrl-/': 'toggleComment',
                        'Cmd-/': 'toggleComment'
                    }
                }
            });
        }
    }

    // Save CSS code
    function saveCSSCode() {
        var cssCode = cssEditor ? cssEditor.codemirror.getValue() : $('#custom-css-editor').val();
        
        $('.editor-container').addClass('editor-loading');
        
        $.ajax({
            url: lift_editor_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'save_custom_css',
                nonce: lift_editor_vars.nonce,
                custom_css: cssCode
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(lift_editor_vars.save_error, 'error');
                }
            },
            error: function() {
                showMessage(lift_editor_vars.save_error, 'error');
            },
            complete: function() {
                $('.editor-container').removeClass('editor-loading');
            }
        });
    }

    // Save JS code
    function saveJSCode() {
        var jsCode = jsEditor ? jsEditor.codemirror.getValue() : $('#custom-js-editor').val();
        
        $('.editor-container').addClass('editor-loading');
        
        $.ajax({
            url: lift_editor_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'save_custom_js',
                nonce: lift_editor_vars.nonce,
                custom_js: jsCode
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(lift_editor_vars.save_error, 'error');
                }
            },
            error: function() {
                showMessage(lift_editor_vars.save_error, 'error');
            },
            complete: function() {
                $('.editor-container').removeClass('editor-loading');
            }
        });
    }

    // Show message
    function showMessage(message, type) {
        var $messageDiv = $('#editor-message');
        $messageDiv.removeClass('notice-success notice-error')
                   .addClass('notice-' + type)
                   .html('<p>' + message + '</p>')
                   .show();
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 3000);
    }

    // Format CSS code
    function formatCSS() {
        if (cssEditor) {
            var code = cssEditor.codemirror.getValue();
            var formatted = formatCSSString(code);
            cssEditor.codemirror.setValue(formatted);
        }
    }

    // Format JavaScript code
    function formatJS() {
        if (jsEditor) {
            var code = jsEditor.codemirror.getValue();
            var formatted = formatJSString(code);
            jsEditor.codemirror.setValue(formatted);
        }
    }

    // Simple CSS formatter
    function formatCSSString(css) {
        return css
            .replace(/\s*{\s*/g, ' {\n    ')
            .replace(/;\s*/g, ';\n    ')
            .replace(/\s*}\s*/g, '\n}\n\n')
            .replace(/,\s*/g, ',\n')
            .replace(/^\s+/gm, function(match) {
                return match.replace(/\s/g, '    ');
            })
            .trim();
    }

    // Simple JS formatter
    function formatJSString(js) {
        var formatted = js;
        var indent = 0;
        var lines = formatted.split('\n');
        var result = [];

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            
            if (line.match(/^[\}\]]/)) {
                indent--;
            }
            
            if (line.length > 0) {
                result.push('    '.repeat(Math.max(0, indent)) + line);
            } else {
                result.push('');
            }
            
            if (line.match(/[\{\[]$/)) {
                indent++;
            }
        }
        
        return result.join('\n');
    }

    // Clear CSS code
    function clearCSS() {
        if (confirm('Are you sure you want to clear all CSS code? This action cannot be undone.')) {
            if (cssEditor) {
                cssEditor.codemirror.setValue('');
            } else {
                $('#custom-css-editor').val('');
            }
        }
    }

    // Clear JS code
    function clearJS() {
        if (confirm('Are you sure you want to clear all JavaScript code? This action cannot be undone.')) {
            if (jsEditor) {
                jsEditor.codemirror.setValue('');
            } else {
                $('#custom-js-editor').val('');
            }
        }
    }

    // Event handlers
    $(document).on('click', '#save-css', function(e) {
        e.preventDefault();
        saveCSSCode();
    });

    $(document).on('click', '#save-js', function(e) {
        e.preventDefault();
        saveJSCode();
    });

    $(document).on('click', '#format-css', function(e) {
        e.preventDefault();
        formatCSS();
    });

    $(document).on('click', '#format-js', function(e) {
        e.preventDefault();
        formatJS();
    });

    $(document).on('click', '#clear-css', function(e) {
        e.preventDefault();
        clearCSS();
    });

    $(document).on('click', '#clear-js', function(e) {
        e.preventDefault();
        clearJS();
    });

    // Form submission handlers
    $('#css-editor-form').on('submit', function(e) {
        e.preventDefault();
        saveCSSCode();
    });

    $('#js-editor-form').on('submit', function(e) {
        e.preventDefault();
        saveJSCode();
    });

    // Prevent accidental page leave with unsaved changes
    var originalCSSContent = $('#custom-css-editor').val();
    var originalJSContent = $('#custom-js-editor').val();

    function hasUnsavedChanges() {
        var currentCSS = cssEditor ? cssEditor.codemirror.getValue() : $('#custom-css-editor').val();
        var currentJS = jsEditor ? jsEditor.codemirror.getValue() : $('#custom-js-editor').val();
        
        return (currentCSS !== originalCSSContent) || (currentJS !== originalJSContent);
    }

    $(window).on('beforeunload', function(e) {
        if (hasUnsavedChanges()) {
            var message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });

    // Update original content after successful save
    $(document).on('ajaxSuccess', function(event, xhr, settings) {
        if (settings.data && (settings.data.indexOf('save_custom_css') !== -1 || settings.data.indexOf('save_custom_js') !== -1)) {
            var response = xhr.responseJSON;
            if (response && response.success) {
                originalCSSContent = cssEditor ? cssEditor.codemirror.getValue() : $('#custom-css-editor').val();
                originalJSContent = jsEditor ? jsEditor.codemirror.getValue() : $('#custom-js-editor').val();
            }
        }
    });

    // Add editor hints and autocomplete
    function addEditorHints() {
        if (cssEditor) {
            // Add CSS hints
            cssEditor.codemirror.on('keyup', function(cm, event) {
                if (!cm.state.completionActive && event.keyCode !== 13 && event.keyCode !== 27) {
                    CodeMirror.commands.autocomplete(cm, null, {hint: CodeMirror.hint.css});
                }
            });
        }

        if (jsEditor) {
            // Add JS hints
            jsEditor.codemirror.on('keyup', function(cm, event) {
                if (!cm.state.completionActive && event.keyCode !== 13 && event.keyCode !== 27) {
                    CodeMirror.commands.autocomplete(cm, null, {hint: CodeMirror.hint.javascript});
                }
            });
        }
    }

    // Search functionality
    function addSearchFunctionality() {
        if (cssEditor) {
            cssEditor.codemirror.setOption('extraKeys', Object.assign(
                cssEditor.codemirror.getOption('extraKeys') || {},
                {
                    'Ctrl-F': 'findPersistent',
                    'Cmd-F': 'findPersistent',
                    'Ctrl-H': 'replace',
                    'Cmd-H': 'replace'
                }
            ));
        }

        if (jsEditor) {
            jsEditor.codemirror.setOption('extraKeys', Object.assign(
                jsEditor.codemirror.getOption('extraKeys') || {},
                {
                    'Ctrl-F': 'findPersistent',
                    'Cmd-F': 'findPersistent',
                    'Ctrl-H': 'replace',
                    'Cmd-H': 'replace'
                }
            ));
        }
    }

    // Initialize everything
    initializeEditors();
    
    // Add additional features after a short delay to ensure editors are ready
    setTimeout(function() {
        addEditorHints();
        addSearchFunctionality();
    }, 500);

    // Resize editors when window resizes
    $(window).on('resize', function() {
        if (cssEditor) {
            cssEditor.codemirror.refresh();
        }
        if (jsEditor) {
            jsEditor.codemirror.refresh();
        }
    });

    // Refresh editors when tab becomes visible (for better performance)
    $(document).on('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(function() {
                if (cssEditor) {
                    cssEditor.codemirror.refresh();
                }
                if (jsEditor) {
                    jsEditor.codemirror.refresh();
                }
            }, 100);
        }
    });
});
