/**
 * (c) 2013 Jexcel Plugin v1.0.0 > Bossanova UI
 * http://www.github.com/paulhodel/jexcel
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Jexcel
 * 
 * ROADMAP:
 * setData reset table (remove selections, hide corner)
 * spare rows and columns
 * 
 * Multiple tables in the same window
 * Multiple tabs
 * Merged cells,
 * Improve masking and data validation,
 * Include formulas,
 * Reorder methods,
 * Drag and drop rows and columns
 * Automatic columns based on the paste
 */

(function( $ ){

var methods = {

    /**
     * Initial configuration and loading
     * 
     * @param {Object} options configuration
     * @return void
     */
    init : function( options ) {
        // Loading default configuration
        var defaults = {
            colWidths:[],
            colAlignments:[],
            columns:[],
        };

        var options =  $.extend(defaults, options);

        // Id
        var id = $(this).prop('id');

        // Main object
        var main = $(this);

        // Register options
        $.fn.jexcel.defaults = new Array();
        $.fn.jexcel.defaults[id] = options;

        // Loading initial data from remote sources
        var results = [];

        // Preparations
        for (i = 0; i < options.colHeaders.length; i++) {
            // Avoid erros
            if (! options.columns[i].source) {
                $.fn.jexcel.defaults[id].columns[i].source = [];
            }

            // Load innitial source for json autocomplete
            if (options.columns[i].type == 'autocomplete') {
                // if remote content
                if (options.columns[i].url) {
                    results.push($.ajax({
                        url: options.columns[i].url,
                        index: i,
                        success: function (result) {
                            // Create the dynamic sources
                            $.fn.jexcel.defaults[id].columns[this.index].source = result;
                            // Combo
                            $.fn.jexcel.defaults[id].columns[this.index].combo = $(main).jexcel('createCombo', result);
                        }
                    }));
                } else if (options.columns[i].source) {
                    // Combo
                    $.fn.jexcel.defaults[id].columns[i].combo = $(main).jexcel('createCombo', options.columns[i].source);
                }
            } else if (options.columns[i].type == 'dropdown') {
                // Creating the mapping
                $.fn.jexcel.defaults[id].columns[i].combo = $(main).jexcel('createCombo', options.columns[i].source);
            } else if (options.columns[i].type == 'dropdown') {
                if (! $.fn.jexcel.defaults[id].columns[i].options) {
                    $.fn.jexcel.defaults[id].columns[i].options = {};
                }
                if (! $.fn.jexcel.defaults[id].columns[i].options.format) {
                    $.fn.jexcel.defaults[id].columns[i].options.format = 'DD/MM/YYYY';
                }
            }
        }

        // Waiting all data is loaded
        if (results.length > 0) {
            $.when.apply(this, results).done(function() {
                $(main).jexcel('createTable');
            });
        } else {
            $(main).jexcel('createTable');
        }
    },

    /**
     * Create table
     * 
     * @return void
     */
    createTable : function() {
        // Funcions
        var main = $(this);

        // Id
        var id = $(this).prop('id');

        // Data
        if (! $.fn.jexcel.defaults[id].data) {
            $.fn.jexcel.defaults[id].data = [];
        }

        // Length
        if (! $.fn.jexcel.defaults[id].data.length) {
            $.fn.jexcel.defaults[id].data = [[]];
        }

        // Var options
        var options = $.fn.jexcel.defaults[id];

        // Create main menu table container
        var table = document.createElement('table');
        $(table).prop('class', 'jexcel');
        $(table).prop('cellpadding', '0');
        $(table).prop('cellspacing', '0');

        // Unselectable
        $(table).prop('unselectable', 'yes');
        $(table).prop('onselectstart', 'return false');
        $(table).prop('draggable', 'false');

        // Header
        if (! options.colHeaders) {
            // Letters
        }

        var thead = document.createElement('thead');
        var tbody = document.createElement('tbody');

        // Create headers
        var tr = '<td width="30" class="label"></td>';

        for (i = 0; i < options.colHeaders.length; i++) {
            if (! options.columns[i]) {
                options.columns[i] = { type: 'text' };
            }

            if (! options.colHeaders[i]) {
                // TODO: i > 24
                options.colHeaders[i] = String.fromCharCode(65 + i);
            }

            width = options.colWidths[i] || 50;
            align = options.colAlignments[i] || 'left';

            if (options.columns[i].type == 'hidden') {
                // TODO: when it is first check the whole selection not include
                tr += '<td id="col-' + i + '" style="display:none;">' + options.colHeaders[i] + '</td>';
            } else {
                // Default types
                tr += '<td id="col-' + i + '" width="' + width + '" align="' + align +'">' + options.colHeaders[i] + '</td>';
            }
        }

        $(thead).html('<tr>' + tr + '</tr>');

        // Append content
        $(table).append(thead);
        $(table).append(tbody);

        // Prevent dragging
        $(table).on('dragstart', function () {
            return false;
        });

        // Main object
        $(this).html(table);

        // Add the corner square and textarea one time onlly
        if (! $('.jexcel_corner').length) {
            // Corner one for all sheets in a page
            var corner = document.createElement('div');
            $(corner).prop('class', 'jexcel_corner');

            // Hidden textarea copy and paste helper
            var textarea = document.createElement('textarea');
            $(textarea).prop('id', 'textarea');
            $(textarea).prop('class', 'jexcel_textarea');

            // Append elements
            $(this).append(corner);
            $(this).append(textarea);

            // Prevent dragging
            $(corner).on('dragstart', function () {
                return false;
            });
        }

        // All header cells
        var header = $(this).find('thead td').not(':first');

        // All cells
        var cells;

        // Row index
        var rows;

        // Current selected cell
        var selectedCell = null;

        // Current selected row
        var selectedRow = null;

        // Current selected header
        var selectedHeader = null;

        // Cell selection by header click
        $(header).mousedown(function (e) {
            var o = $(this).prop('id').split('-');

            if (e.shiftKey || e.ctrlKey) {
                // Updade selection multi columns
                var d = $(selectedHeader).prop('id').split('-');
            } else {
                // Update selection single column
                var d = $(this).prop('id').split('-');
                // Keep track of which header was selected first
                selectedHeader = $(this);
            }

            // Get cell objects 
            var o1 = $(main).find('#' + o[1] + '-0');
            var o2 = $(main).find('#' + d[1] + '-' + parseInt(options.data.length-1));

            $(main).jexcel('updateSelection', o1, o2);
        });

        // Cell selection mouseover
        $(header).mouseover(function (e) {
            if (selectedHeader) {
                // Updade selection
                if (e.buttons) {
                    var o = $(selectedHeader).prop('id');
                    var d = $(this).prop('id');
                    if (o && d) {
                        o = o.split('-');
                        d = d.split('-');
                        // Get cell objects 
                        var o1 = $(main).find('#' + o[1] + '-0');
                        var o2 = $(main).find('#' + d[1] + '-' + parseInt(options.data.length-1));
                        // Update selection
                        $(main).jexcel('updateSelection', o1, o2);
                    }
                }
            }
        });

        // Clousure event handler for the cells
        $.fn.jexcel.defaults[id].bindEvents = function () {
            // All cells
            cells = $(main).find('tbody td').not('.label');

            // Row index
            rows = $(main).find('tbody td.rowIndex');

            // Cell selection click
            $(cells).off('mousedown');
            $(cells).on('mousedown', function (e) {
                if (! $(selectedCell).hasClass('edition')) {
                    if (! e.shiftKey) {
                        selectedCell = $(this);
                    }

                    $(main).jexcel('updateSelection', selectedCell, $(this));
                }

                // Interrupt header tracking
                selectedHeader = false;

                // Interrupt row tracking
                selectedRow = false;
            });

            // Cell selection mouseoveri
            $(cells).off('mouseover');
            $(cells).on('mouseover', function (e) {
                if (! $(selectedCell).hasClass('edition')) {
                    if (selectedCell && !selectedHeader) {
                        if (selectedCorner == true) {
                            // Copy option
                            $(main).jexcel('updateCornerSelection', $(this));
                        } else {
                            // Updade selection
                            if (e.buttons) {
                                $(main).jexcel('updateSelection', selectedCell, $(this));
                            }
                        }
                    }
                }
            });

            // Edition on double click
            $(cells).off('dblclick');
            $(cells).on('dblclick', function () {
                $(main).jexcel('openEditor', $(this));
            });

            // Row index selection
            $(rows).off('mousedown');
            $(rows).on('mousedown', function (e) {
                var o = $(this).prop('id').split('-');

                if (e.shiftKey || e.ctrlKey) {
                    // Updade selection multi columns
                    var d = $(selectedRow).prop('id').split('-');
                } else {
                    // Update selection single column
                    var d = $(this).prop('id').split('-');
                    // Keep track of which header was selected first
                    selectedRow = $(this);
                }

                // Get cell objects 
                var o1 = $(main).find('#0-' + o[1]);
                var o2 = $(main).find('#' + parseInt(options.columns.length-1) + '-' + d[1]);

                $(main).jexcel('updateSelection', o1, o2);
            });

            // Row index selection selection mouseover
            $(rows).off('mouseover');
            $(rows).on('mouseover', function (e) {
                if (selectedRow) {
                    // Updade selection
                    if (e.buttons) {
                        var o = $(selectedRow).prop('id');
                        var d = $(this).prop('id');
                        if (o && d) {
                            o = o.split('-');
                            d = d.split('-');
                            // Get cell objects 
                            var o1 = $(main).find('#0-' + o[1]);
                            var o2 = $(main).find('#' + parseInt(options.columns.length-1) + '-'  + d[1]);

                            $(main).jexcel('updateSelection', o1, o2);
                        }
                    }
                }
            });

            // TODO: Add custom event handles based on the user definition (onclick, onmouseover, etc)
        }

        // Load data
        $(this).jexcel('setData');

        // Corner controls
        var selectedCorner = false;
        $('.jexcel_corner').mousedown(function (e) {
            selectedCorner = true;
        });

        // Double click
        $('.jexcel_corner').dblclick(function (e) {
            var selection = $(main).find('tbody td.highlight');
            // Selected cells
            var o = $(selection[0]).prop('id').split('-');
            var d = $(selection[selection.length - 1]).prop('id').split('-');
            // Adjust double copy
            o[1] = parseInt(d[1]) + 1;
            d[1] = parseInt(options.data.length);
            // Copy
            $(main).jexcel('copyData', o, d);
        });

        // Cancel selections
        $(document).mouseup(function () {
            selectedCorner = false;

            // Data to be copied
            var selection = $(main).find('tbody td.selection');

            if ($(selection).length > 0) {
                // First and last cells
                var o = $(selection[0]).prop('id').split('-');
                var d = $(selection[selection.length - 1]).prop('id').split('-');
                // Copy data
                $(main).jexcel('copyData', o, d);
                // Remove selection
                $(cells).removeClass('selection');
                $(cells).removeClass('selection-left');
                $(cells).removeClass('selection-right');
                $(cells).removeClass('selection-top');
                $(cells).removeClass('selection-bottom');
            }
        });

        // Keyboard controls
        var keyBoardCell = null;
        $(document).keydown(function(e) {
            if (selectedCell) {
                var cell = null;

                if (e.which == 37) {
                    // Left
                   if (! $(selectedCell).hasClass('edition')) {
                      cell = $(selectedCell).prev();
                   }
                } else if (e.which == 39) {
                    // Right
                   if (! $(selectedCell).hasClass('edition')) {
                      cell = $(selectedCell).next();
                   }
                } else if (e.which == 38) {
                    // Top
                   if (! $(selectedCell).hasClass('edition')) {
                      id = $(selectedCell).prop('id').split('-');
                      cell = $(selectedCell).parent().prev().find('#' + id[0] + '-' + (id[1] - 1));
                   }
                } else if (e.which == 40) {
                    // Bottom
                   if (! $(selectedCell).hasClass('edition')) {
                      id = $(selectedCell).prop('id').split('-');
                      cell = $(selectedCell).parent().next().find('#' + id[0] + '-' + (parseInt(id[1]) + 1));
                   }
                } else if (e.which == 27) {
                    // Escape
                    if ($(selectedCell).hasClass('edition')) {
                        // Exit without saving
                        $(main).jexcel('closeEditor', $(selectedCell), false);
                    }
                } else if (e.which == 13) {
                    // Enter (confirm changes in case of edition)
                    if ($(selectedCell).hasClass('edition')) {
                        // Exit saving data
                        $(main).jexcel('closeEditor', $(selectedCell), true);
                    } else {
                        // New record in case selectedCell in the last row
                        id = $(selectedCell).prop('id').split('-');
                        if (id[1] == options.data.length - 1) {
                            $(main).jexcel('insertRow');
                        }
                    }
                } else if (e.which == 46) {
                    // Delete (erase cell in case no edition is running)
                    if (! $(selectedCell).hasClass('edition')) {
                        $(main).jexcel('setValue', $(main).find('.highlight'), '');
                    }
                } else {
                    if (! e.shiftKey && ! e.ctrlKey) {
                        // Start edition in case a valid character. 
                        if (! $(selectedCell).hasClass('edition')) {
                            // TODO: check the sample characters able to start a edition
                            if (/[a-zA-Z0-9]/.test(String.fromCharCode(e.keyCode))) {
                                $(main).jexcel('setValue', $(selectedCell), '');
                                $(main).jexcel('openEditor', $(selectedCell));
                            }
                        }
                    }
                }

                // Arrows control
                if (cell) {
                    // Control selected cell
                    if ($(cell).length > 0 && $(cell).prop('id').substr(0,3) != 'row') {
                        // In case of a multiple cell selection
                        if (e.shiftKey || e.ctrlKey) {
                            // Keep first selected cell
                            if (! keyBoardCell) {
                                keyBoardCell = selectedCell;
                            }

                            // Origin cell
                            o = keyBoardCell;
                        } else {
                            // Single selection reset history
                            keyBoardCell = null;

                            // Origin cell
                            o = cell;
                        }

                        // Target cell
                        t = cell;

                        // Current cell
                        selectedCell = cell;

                        // Focus
                        $(cell).focus();

                        // Update selection
                        $(main).jexcel('updateSelection', o, t);
                    }
                }
            } else {
                // Valid functions without specific selectedCell
                if (e.which == 46) {
                     // Delete (erase cell in case no edition is running)
                     $(main).jexcel('setValue', $(main).find('.highlight'), '');
                 }
            }
        });

        // Copy data from the table in excel format
        $(document).bind('copy', function() {
            $(main).jexcel('copy', true);
        });

        // Cut data from the table in excel format
        $(document).bind('cut', function() {
            $(main).jexcel('copy', true);
            // Remove current data 
            $(main).jexcel('setValue', $(main).find('.highlight'), '');
        });

        // Paste data from excel format to the table
        $(document).bind('paste', function(e) {
            $(main).jexcel('paste', selectedCell, e.originalEvent.clipboardData.getData('text'));
        });
    },

    /**
     * Set data
     * 
     * @param array data In case no data is sent, default is reloaded
     * @return void
     */
    setData : function(data) {
        // Id
        var id = $(this).prop('id');

        // Update data
        if (data) {
            if (typeof(data) == 'string') {
                data = JSON.parse(data);
            }

            $.fn.jexcel.defaults[id].data = data;
        }

        // Options
        var options = $.fn.jexcel.defaults[id];

        // Data container
        var tbody = $(this).find('tbody');

        // Create content
        var content = '';
        var contentCell = '';

        for (j = 0; j < options.data.length; j++) {
            // Index column
            tr = '<td id="row-' + j + '" class="label rowIndex">' + parseInt(j + 1) + '</td>'; // TODO: <div class="dragLine"><div></div></div>
            // Data columns
            for (i = 0; i < options.colHeaders.length; i++) {
                // Aligment
                align = options.colAlignments[i] || 'left';

                // Native options
                if (options.columns[i].type == 'hidden') {
                    // Hidden behavior
                    contentCell = options.data[j][i] ? options.data[j][i] : '';
                    tr += '<td id="' + i + '-' + j + '" style="display:none;">' + contentCell + '</td>';
                } else {
                    // Default types
                    if (options.columns[i].type == 'checkbox') {
                        // Checkboxes
                        if (options.data[j][i] == 'true' || options.data[j][i] == 1) {
                            contentCell = '<input type="checkbox" checked="checked">';
                        } else {
                            contentCell = '<input type="checkbox">';
                        }
                    } else if (options.columns[i].type == 'dropdown' || options.columns[i].type == 'autocomplete') {
                        // Dropdown and autocompletes
                        k = '';
                        v = '';
                        if (options.data[j][i]) {
                            if (options.columns[i].combo[options.data[j][i]]) {
                                k = options.data[j][i];
                                v = options.columns[i].combo[options.data[j][i]];
                            }
                        }
                        contentCell = '<input type="hidden" value="' +  k + '"><label>' + v + '</label>';
                    } else if (options.columns[i].type == 'calendar') {
                        // Valid string data yyyy-mm-dd hh:mm:ss
                        if (options.data[j][i].length > 8) {
                            input = document.createElement('input');
                            $(input).prop('type', 'hidden');
                            $(input).prop('value', options.data[j][i])
                            contentCell = $(input).calendar('label', options.columns[i].options.format);
                        } else {
                            contentCell = '';
                        }
                        contentCell = '<input type="hidden" value="' +  options.data[j][i] + '"><label>' + contentCell + '</label>';
                    } else {
                        // Default
                        contentCell = options.data[j][i] ? options.data[j][i] : '';
                    }

                    // Readonly property
                    readonly = '';
                    if (options.columns[i].readOnly == true) {
                        readonly = 'readonly';
                    }

                    tr += '<td id="' + i + '-' + j + '" align="' + align +'" class="' + readonly + '">' + contentCell + '</td>';
                }
            }

            content += '<tr>' + tr + '</tr>';
        }

        // Add or replace data from the main table
        $(tbody).html(content);

        // Bind events
        $.fn.jexcel.defaults[id].bindEvents();
    },

    /**
     * Update table settings helper. Update cells after loading
     * 
     * @param methods
     * @return void
     */
    updateSettings : function(options) {
        var cells = $(main).find('tbody td').not('.label');
        if (options.cells) {
            $.each(cells, function (k, v) {
                id = $(v).prop('id').split('-');
                options.cells($(v), id[0], id[1]);
            });
        }
    },

    /**
     * Open the editor
     * 
     * @param object cell
     * @return void
     */
    openEditor : function(cell) {
        // Id
        var id = $(this).prop('id');

        // Main
        var main = $(this);

        // Options
        var options = $.fn.jexcel.defaults[id];

        // Get cell position
        var position = $(cell).prop('id').split('-');

        // Readonly
        if ($(cell).hasClass('readonly') == true) {
            // Do nothing
        } else {
            // Holder
            $.fn.jexcel.edition = $(cell).html();

            // If there is a custom editor for it
            if (options.columns[position[0]].editor) {
                // Keep the current value
                $(cell).addClass('edition');

                // Custom editors
                options.columns[position[0]].editor.openEditor(cell);
            } else {
                // Native functions
                if (options.columns[position[0]].type == 'checkbox' || options.columns[position[0]].type == 'hidden') {
                    // Do nothing for checkboxes or hidden columns
                } else if (options.columns[position[0]].type == 'dropdown') {
                    // Keep the current value
                    $(cell).addClass('edition');

                    // Create dropdown
                    var source = options.columns[position[0]].source;

                    var html = '<select>';
                    for (i = 0; i < source.length; i++) {
                        if (typeof(source[i]) == 'object') {
                            k = source[i].id;
                            v = source[i].name;
                        } else {
                            k = source[i];
                            v = source[i];
                        }
                        html += '<option value="' + k + '">' + v + '</option>';
                    }
                    html += '</select>';

                    // Get current value
                    var value = $(cell).find('input').val();

                    // Open editor
                    $(cell).html(html);

                    // Editor configuration
                    var editor = $(cell).find('select');
                    $(editor).change(function () {
                        $(main).jexcel('closeEditor', $(this).parent(), true);
                    });
                    $(editor).blur(function () {
                        $(main).jexcel('closeEditor', $(this).parent(), true);
                    });
                    
                    $(editor).focus();
                    if (value) {
                        $(editor).val(value);
                    }
                } else if (options.columns[position[0]].type == 'calendar') {
                    $(cell).addClass('edition');

                    // Get content
                    var html = $(cell).find('input');

                    // Basic editor
                    var editor = document.createElement('input');
                    $(editor).prop('class', 'editor');
                    $(editor).css('width', $(cell).width());
                    $(cell).html(editor);

                    // Close editor callback for calendar
                    options.columns[position[0]].options.onchange = function () {
                        $(main).jexcel('closeEditor', $(cell), true);
                    }

                    // Current value
                    $(editor).val(html);
                    $(editor).calendar(options.columns[position[0]].options);

                    // Close editor handler
                    $(editor).blur(function () {
                        $(main).jexcel('closeEditor', $(this).parent(), true);
                    });
                } else if (options.columns[position[0]].type == 'autocomplete') {
                    // Keep the current value
                    $(cell).addClass('edition');

                    // Get content
                    var html = $(cell).find('label').html();
                    var value = $(cell).find('input').val();

                    // Basic editor
                    var editor = document.createElement('input');
                    $(editor).prop('class', 'editor');
                    $(editor).css('width', $(cell).width());

                    // Results
                    var result = document.createElement('div');
                    $(result).prop('class', 'results');
                    if (html) {
                       $(result).html('<li id="' + value + '">' + html + '</li>');
                    } else {
                       $(result).css('display', 'none');
                    }

                    // Search
                    var timeout = null;
                    $(editor).on('keyup', function () {
                        // String
                        var str = $(this).val();

                        // Timeout
                        if (timeout) {
                            clearTimeout(timeout)
                        }

                        // Delay search
                        timeout = setTimeout(function () { 
                            // Object
                            $(result).html('');
                            // List result
                            showResult = function(data, str) {
                                // Create options
                                $.each(data, function(k, v) {
                                    if (typeof(v) == 'object') {
                                        name = v.name;
                                        id = v.id;
                                    } else {
                                        name = v;
                                        id = v;
                                    }

                                    if (name.toLowerCase().indexOf(str.toLowerCase()) != -1) {
                                        li = document.createElement('li');
                                        $(li).prop('id', id)
                                        $(li).html(name);
                                        $(li).mousedown(function (e) {
                                            // TODO: avoid other selection in this handler.
                                            $(cell).html(this);
                                            $(main).jexcel('closeEditor', $(cell), true);
                                        });
                                        $(result).append(li);
                                    }
                                });

                                if (! $(result).html()) {
                                    $(result).html('<div style="padding:6px;">No result found</div>');
                                }
                                $(result).css('display', '');
                            }

                            // Search
                            if (options.columns[position[0]].url) {
                                $.getJSON (options.columns[position[0]].url + '?q=' + str + '&r=' + $(main).jexcel('getRowData', position[1]), function (data) {
                                    showResult(data, str);
                                });
                            } else if (options.columns[position[0]].source) {
                                showResult(options.columns[position[0]].source, str);
                            }
                        }, 500);
                    });
                    $(cell).html(editor);
                    $(cell).append(result);

                    // Current value
                    $(editor).focus();
                    $(editor).val('');

                    // Close editor handler
                    $(editor).blur(function () {
                        $(main).jexcel('closeEditor', $(cell), false);
                    });
                } else {
                    // Keep the current value
                    $(cell).addClass('edition');

                    // Get content
                    var html = $(cell).html();

                    // Basic editor
                    var editor = document.createElement('input');
                    $(editor).prop('class', 'editor');
                    $(editor).css('width', $(cell).width());
                    $(cell).html(editor);

                    // Bind mask for numeric
                    if (options.columns[position[0]].type == 'numeric' && options.columns[position[0]].mask) {
                        $(this).jexcel('bindNumericMask', $(editor), options.columns[position[0]].mask);
                    }

                    // Current value
                    $(editor).focus();
                    $(editor).val(html);

                    // Close editor handler
                    $(editor).blur(function () {
                        $(main).jexcel('closeEditor', $(this).parent(), true);
                    });
                }
            }
        }
    },

    /**
     * Close the editor and save the information
     * 
     * @param object cell
     * @param boolean save
     * @return void
     */
    closeEditor : function(cell, save) {
        // Remove edition mode mark
        $(cell).removeClass('edition');

        // Id
        var id = $(this).prop('id');

        // Options
        var options = $.fn.jexcel.defaults[id];

        // Get cell properties
        if (save == true) {
            // Cell identification
            var position = $(cell).prop('id').split('-');

            // Before change
            if (typeof(options.columns[position[0]].beforechange) == 'function') {
                options.columns[position[0]].beforechange($(this), $(cell));
            }

            // If custom editor
            if (options.columns[position[0]].editor) {
                // Custom editor
                options.columns[position[0]].editor.closeEditor(cell, save);
            } else {
                // Native functions
                if (options.columns[position[0]].type == 'checkbox' || options.columns[position[0]].type == 'hidden') {
                    // Do nothing
                } else if (options.columns[position[0]].type == 'dropdown') {
                    // Get value
                    var value = $(cell).find('select').val();
                    var text = $(cell).find('select').find('option:selected').text();
                    // Set value
                    $(cell).html('<input type="hidden" value="' + value + '">' + text);
                } else if (options.columns[position[0]].type == 'autocomplete') {
                    // Set value
                    var obj = $(cell).find('li');
                    if (obj.length > 0) {
                        var value = $(cell).find('li').prop('id');
                        var text = $(cell).find('li').html();
                        $(cell).html('<input type="hidden" value="' + value + '"><label>' + text + '</label>');
                    } else {
                        $(cell).html('');
                    }
                } else if (options.columns[position[0]].type == 'calendar') {
                    var value = $(cell).find('.jquery_calendar_value').val();
                    var text = $(cell).find('.jquery_calendar_input').val();
                    $(cell).html('<input type="hidden" value="' + value + '"><label>' + text + '</label>');
                } else {
                    // Defaut editor
                    $(cell).html($(cell).find('.editor').val());
                }
            }

            // Change
            if (typeof(options.columns[position[0]].change) == 'function') {
                options.columns[position[0]].change($(this), $(cell), $(this).jexcel('getValue', $(cell)));
            }
        } else {
            // Restore value
            $(cell).html($.fn.jexcel.edition);

            // Finish temporary edition
            $.fn.jexcel.edition = null;
        }
    },

    /**
     * Get the value from a cell
     * 
     * @param object cell
     * @return string value
     */
    getValue : function(cell) {
        // If is a string get the cell object
        if (typeof(cell) != 'object') {
            cell = $(this).find('[id=' + cell +']');
        }

        // Id
        var id = $(this).prop('id');

        // Global options
        var options = $.fn.jexcel.defaults[id];

        // Configuration
        var position = $(cell).prop('id').split('-');

        // Get value based on the type
        if (options.columns[position[0]].editor) {
            // Custom editor
            value = options.columns[position[0]].editor.getValue(cell);
        } else {
            // Native functions
            if (options.columns[position[0]].type == 'checkbox') {
                // Get checkbox value
                value = $(cell).find('input').is(':checked') ? 1 : 0;
            } else if (options.columns[position[0]].type == 'dropdown' || options.columns[position[0]].type == 'autocomplete' || options.columns[position[0]].type == 'calendar') {
                // Get value
                value = $(cell).find('input').val();
            } else {
                // Get default value
                value = $(cell).html();
            }
        }

        return value ? value : '';
    },

    /**
     * Set a cell value
     * 
     * @param object cell destination cell
     * @param object value value
     * @return void
     */
    setValue : function(cell, value) {
        // If is a string get the cell object
        if (typeof(cell) !== 'object') {
            cell = $(this).find('[id=' + cell +']');
        }

        // Id
        var id = $(this).prop('id');

        // Main object
        var main = $(this);

        // Global options
        var options = $.fn.jexcel.defaults[id];

        // Go throw all cells
        $.each(cell, function(k, v) {
            // Cell identification
            var position = $(v).prop('id').split('-');

            // Before Change
            if (typeof(options.columns[position[0]].beforechange) == 'function') {
                options.columns[position[0]].beforechange($(this), $(v));
            }

            if (options.columns[position[0]].editor) {
                // Custom editor
                options.columns[position[0]].editor.setValue(v, value);
            } else {
                // Native functions
                if (options.columns[position[0]].type == 'checkbox') {
                    if (value == 1 || value == true) {
                        $(v).find('input').prop('checked', true);
                    } else {
                        $(v).find('input').prop('checked', false);
                    }
                } else if (options.columns[position[0]].type == 'dropdown' || options.columns[position[0]].type == 'autocomplete') {
                    // Dropdown and autocompletes
                    key = '';
                    val = '';
                    if (value) {
                        if (options.columns[position[0]].combo[value]) {
                            key = value;
                            val = options.columns[position[0]].combo[value];
                        }
                    }

                    $(v).html('<input type="hidden" value="' +  key + '"><label>' + val + '</label>');
                } else if (options.columns[position[0]].type == 'calendar') {
                    
                } else {
                    $(v).html(value);
                }
            }

            // Change
            if (typeof(options.columns[position[0]].change) == 'function') {
                options.columns[position[0]].change($(main), $(v), value);
            }
        });
    },

    /**
     * Update the cells selection
     * 
     * @param object o cell origin
     * @param object d cell destination
     * @return void
     */
    updateSelection : function(o, d) {
        // Main table
        var main = $(this);

        // Cells
        var cells = $(this).find('tbody td');
        var header = $(this).find('thead td');

        // Remove highlight
        $(cells).removeClass('highlight');
        $(cells).removeClass('highlight-left');
        $(cells).removeClass('highlight-right');
        $(cells).removeClass('highlight-top');
        $(cells).removeClass('highlight-bottom');

        // Update selected column
        $(header).removeClass('selected');
        $(cells).removeClass('selected');
        $(o).addClass('class', 'selected');

        // Define coordinates
        o = $(o).prop('id').split('-');
        d = $(d).prop('id').split('-');

        if (parseInt(o[0]) < parseInt(d[0])) {
            px = parseInt(o[0]);
            ux = parseInt(d[0]);
        } else {
            px = parseInt(d[0]);
            ux = parseInt(o[0]);
        }

        if (parseInt(o[1]) < parseInt(d[1])) {
            py = parseInt(o[1]);
            uy = parseInt(d[1]);
        } else {
            py = parseInt(d[1]);
            uy = parseInt(o[1]);
        }

        // Redefining styles
        for (i = px; i <= ux; i++) {
            for (j = py; j <= uy; j++) {
                $(this).find('#' + i + '-' + j).addClass('highlight');
                $(this).find('#' + px + '-' + j).addClass('highlight-left');
                $(this).find('#' + ux + '-' + j).addClass('highlight-right');
                $(this).find('#' + i + '-' + py).addClass('highlight-top');
                $(this).find('#' + i + '-' + uy).addClass('highlight-bottom');

                // Row and column headers
                $(main).find('#col-' + i).addClass('selected');
                $(main).find('#row-' + j).addClass('selected');
            }
        }

        // Find corner cell
        $(this).jexcel('updateCornerPosition');
    },

    /**
     * Update the cells move data TODO: copy multi columns - TODO!
     * 
     * @param object o cell origin
     * @param object d cell destination
     * @return void
     */
    updateCornerSelection : function(current) {
        // Main table
        var main = $(this);

        // Remove selection
        var cells = $(this).find('tbody td');
        $(cells).removeClass('selection');
        $(cells).removeClass('selection-left');
        $(cells).removeClass('selection-right');
        $(cells).removeClass('selection-top');
        $(cells).removeClass('selection-bottom');

        // Get selection
        var selection = $(this).find('tbody td.highlight');

        // Get elements first and last
        var s = $(selection[0]).prop('id').split('-');
        var d = $(selection[selection.length - 1]).prop('id').split('-');

        // Get current
        var c = $(current).prop('id').split('-');

        // Vertical copy
        if (c[1] > d[1] || c[1] < s[1]) {
            // Vertical
            var px = parseInt(s[0]);
            var ux = parseInt(d[0]);
            if (parseInt(c[1]) > parseInt(d[1])) {
                var py = parseInt(d[1]) + 1;
                var uy = parseInt(c[1]);
            } else {
                var py = parseInt(c[1]);
                var uy = parseInt(s[1]) - 1;
            }
        } else if (c[0] > d[0] || c[0] < s[0]) {
            // Horizontal copy
            var py = parseInt(s[1]);
            var uy = parseInt(d[1]);
            if (parseInt(c[0]) > parseInt(d[0])) {
                var px = parseInt(d[0]) + 1;
                var ux = parseInt(c[0]);
            } else {
                var px = parseInt(c[0]);
                var ux = parseInt(s[0]) - 1;
            }
        }

        for (j = py; j <= uy; j++) {
            for (i = px; i <= ux; i++) {
                $(this).find('#' + i + '-' + j).addClass('selection');
                $(this).find('#' + i + '-' + py).addClass('selection-top');
                $(this).find('#' + i + '-' + uy).addClass('selection-bottom');
                $(this).find('#' + px + '-' + j).addClass('selection-left');
                $(this).find('#' + ux + '-' + j).addClass('selection-right');
            }
        }

        //$(this).jexcel('updateCornerPosition');
    },

    /**
     * Update corner position
     * 
     * @return void
     */
    updateCornerPosition : function() {
        var cells = $(this).find('.highlight');
        corner = $(cells).last();

        // Get the position of the corner helper
        var t = parseInt($(corner).offset().top) + $(corner).height() + 5;
        var l = parseInt($(corner).offset().left) + $(corner).width() + 5;

        // Place the corner in the correct place
        $('.jexcel_corner').css('top', t);
        $('.jexcel_corner').css('left', l);
    },

    /**
     * Get the data from a row
     * 
     * @param integer row number
     * @return string value
     */
    getRowData : function(row) {
       // Get row
       row = $(this).find('#row-' + row).parent().find('td').not(':first');

       // String
       var str = '';

       // Search all tds in a row
       if (row.length > 0) {
          for (i = 0; i < row.length; i++) {
             str += $(this).jexcel('getValue', $(row)[i]) + ',';
          }
       }

       return str;
    },

    /**
     * Get the whole table data
     * 
     * @param integer row number
     * @return string value
     */
    getData : function(highlighted) {
        // Control vars
        var dataset = [];
        var px = 0;
        var py = 0;

        // Column and row length
        var x = $(this).find('thead tr td').not(':first').length;
        var y = $(this).find('tbody tr').length;

        // Go through the columns to get the data
        for (j = 0; j < y; j++) {
            px = 0;
            for (i = 0; i < x; i++) {
                // Cell
                cell = $(this).find('#' + i + '-' + j);

                // Cell selected or fullset
                if (! highlighted || $(cell).hasClass('highlight')) {
                    // Get value
                    if (! dataset[py]) {
                        dataset[py] = [];
                    }
                    dataset[py][px] = $(this).jexcel('getValue', $(cell));
                    px++;
                }
            }
            if (px > 0) {
                py++;
            }
        }

       return dataset;
    },

    /**
     * Copy method
     * 
     * @param integer row number
     * @return string value
     */
    copy : function(highlighted) {
        var str = '';
        var row = '';
        var pc = false;
        var pr = false;

        // Column and row length
        var x = $(this).find('thead tr td').not(':first').length;
        var y = $(this).find('tbody tr').length;

        // Go through the columns to get the data
        for (j = 0; j < y; j++) {
            row = '';
            pc = false;
            for (i = 0; i < x; i++) {
                // Get cell
                cell = $(this).find('#' + i + '-' + j);

                // If cell is highlighted
                if (! highlighted || $(cell).hasClass('highlight')) {
                    if (pc) {
                        row += "\t";
                    }
                    // Get value
                    row += $(this).jexcel('getValue', $(cell));
                    pc = true;
                }
            }
            if (row) {
                if (pr) {
                    str += "\n";
                }
                str += row;
                pr = true;
            }
        }

        // Create a hidden textarea to copy the values
        txt = $(this).find('#textarea');
        $(txt).val(str);
        $(txt).select();
        document.execCommand("copy");
    },

    /**
     * Paste method TODO: if the clipboard is larger than the table create automatically columns/rows?
     * 
     * @param integer row number
     * @return string value
     */
    paste : function(cell, data) {
        // Id
        var id = $(this).prop('id');

        // Data
        data = data.split("\r\n");

        // Initial position
        var position = $(cell).prop('id').split('-')
        var x = position[0];
        var y = position[1];

    	// Automatic adding new rows when the copied data is larger then the table
    	if (parseInt(y + data.length) > $.fn.jexcel.defaults[id].data.length) {
        	$(this).jexcel('insertRow', null, parseInt(y) + data.length - $.fn.jexcel.defaults[id].data.length);
    	}

        // Go through the columns to get the data
        for (j = 0; j < data.length; j++) {
        	// Explode column values
            row = data[j].split("\t");
            for (i = 0; i < row.length; i++) {
                // Get cell
                cell = $(this).find('#' + (parseInt(i) + parseInt(x))  + '-' + (parseInt(j) + parseInt(y)));

                // If cell exists
                if ($(cell).length > 0) {
                    $(this).jexcel('setValue', $(cell), row[i]);
                }
            }
        }
    },

    /**
     * Insert a new row TODO: add relative row
     * 
     * @param object relativeRow - add new row from line number, or null for the end of the table
     * @param object numLines - how many lines to be included
     * 
     * @return void
     */
    insertRow : function(relativeRow, numLines) {
        // Id
        var id = $(this).prop('id');

        // Main configuration
        var options = $.fn.jexcel.defaults[id];

        // Num lines
        if (! numLines) {
        	// Add one line is the default
        	numLines = 1;
        } else if (numLines > 100) {
        	// TODO: is this a good practise to limit the user will?
        	numLines = 100
        } 

        j = parseInt(options.data.length);

        // Adding lines
        for (row = 0; row < numLines; row++) {
	        // New row
	        var tr = '<td id="row-' + j + '" class="label rowIndex">' + (j + 1) + '</td>';

	        for (i = 0; i < options.colHeaders.length; i++) {
	            // Aligment
	            align = options.colAlignments[i] || 'left';

	            // Hidden column
	            if (options.columns[i].type == 'hidden') {
	                tr += '<td id="' + i + '-' + j + '" style="display:none;"></td>';
	            } else {
	                // Native options
	                if (options.columns[i].type == 'checkbox') {
	                    contentCell = '<input type="checkbox">';
	                } else if (options.columns[i].type == 'dropdown' || options.columns[i].type == 'autocomplete' || options.columns[i].type == 'calendar') {
	                    contentCell = '<input type="hidden" value=""><label></label>';
	                } else {
	                    contentCell = '';
	                }

	                tr += '<td id="' + i + '-' + j + '" align="' + align +'">' + contentCell + '</td>';
	            }
	        }

	        tr = '<tr>' + tr + '</tr>';

	        $(this).find('tbody').append(tr);

	        // New data
	        options.data[j] = [];

	        j++;
        }

        // Bind events
        options.bindEvents();
    },

    /**
     * Update column source for dropboxes
     */
    setSource : function (column, source) {
        // In case the column is an object
        if (typeof(column) == 'object') {
            column = $(column).prop('id').split('-');
            column = column[0];
        }

        // Id
        var id = $(this).prop('id');

        // Update defaults
        $.fn.jexcel.defaults[id].columns[column].source = source;
        $.fn.jexcel.defaults[id].columns[column].combo = $(this).jexcel('createCombo', source);
    },

    /**
     * Helper function to copy data using the corner icon
     */
    copyData : function(o, d) {
        var data = $(this).jexcel('getData', true);

        // Cells
        var px = parseInt(o[0]);
        var ux = parseInt(d[0]);
        var py = parseInt(o[1]);
        var uy = parseInt(d[1]);

        // Copy data procedure
        var posx = 0;
        var posy = 0;
        for (j = py; j <= uy; j++) {
            // Controls
            if (data[posy] == undefined) {
                posy = 0;
            }
            posx = 0;

            // Data columns
            for (i = px; i <= ux; i++) {
                // Column
                if (data[posy] == undefined) {
                    posx = 0;
                } else if (data[posy][posx] == undefined) {
                    posx = 0;
                }

                // Get cell
                cell = $(this).find('#' + i + '-' + j);

                // Update non-readonly
                if (! $(cell).hasClass('readonly')) {
                    $(this).jexcel('setValue', cell, data[posy][posx]);
                }
                posx++;
            }
            posy++;
        }
    },

    // Combo
    createCombo : function (result) {
        // Creating the mapping
        var combo = [];
        if (result.length > 0) {
            for (var j = 0; j < result.length; j++) {
                if (typeof(result[j]) == 'object') {
                    key = result[j].id
                    val = result[j].name;
                } else {
                    key = result[j];
                    val = result[j];
                }
                combo[key] = val;
            }
        }

        return combo;
    },

    /**
     * Mask for numeric columns (TODO: improve method and add mask for text)
     */
    bindNumericMask : function (element, format)
    {
        // The Masking will work only with NUMBERS
        $(element).keydown(function (e) {
            e = e || window.event;
            var code = e.charCode || e.keyCode;

            // Ignore some of the keys to format
            if (code == 13 || code == 32 || code == 8 || code == 37 || code == 39) {
            } else {
                // Get the cursor position
                var start = this.selectionStart;

                // Variables to help the mask
                var repos = false;
                var position = 0;

                // Visual data
                var v1 = $(this).val();
                v1 = v1.substr(0,start);
                v1 = v1.replace(/[^0-9]/g,'');
                var v2 = format.replace(/[0-9]/g,'');
                var v3 = '';
                j = 0;

                // Format the values following the format rules
                for (i = 0; i < v2.length; i++) {
                    if (v2[i].match(/[a-zA-Z]/)) {
                        if (v1[j]) {
                            v3 += v1[j];
                            j++;
                            position = i;
                        } else {
                            if (repos == false) {
                                repos = true;
                                position = i;
                            } else {
                                v3 += '_';
                            }
                        }
                    } else {
                        v3 += v2[i];
                    }
                }

                // Set the value in the input again with corrections
                $(this).val(v3);

                // Set the cursor position
                if (start < position - 1) position = start;
                this.setSelectionRange(position, position);
            }
        });

        // Functions to mask the calendar when is possible to type in the date values in the input box
        $(element).keyup(function (e) {
            var v1 = $(this).val();
            var v2 = format.replace(/[0-9]/g,'');
            if (v1.length > v2.length) {
                v1 = v1.substr(0, v2.length);
                $(this).val(v1);
            }
        })
    }
};

$.fn.jexcel = function( method ) {
    if ( methods[method] ) {
        return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
        return methods.init.apply( this, arguments );
    } else {
        $.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
    }
};

})( jQuery );