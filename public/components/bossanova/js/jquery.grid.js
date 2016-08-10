/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com
*
* JS UI Grid
* 
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
* 
********************************************************************************/

(function( $ ){

	var methods = {

	/**
	 * Create a grid HTML container
	 * @param {Object} options : grid default options
	 * @return void
	 */

	init : function( options ) { 

		// Default options
		// grid type: single search column or multiple search column

		var defaults = {
							type:'0'
						};

		var options =  $.extend(defaults, options);

		// Save options for this id
		var main = $(this).attr("id");
		$.fn.grid.options[main] = options;

		// Create grid elements
		var table = document.createElement('table');
		var header = document.createElement('thead');
		var content = document.createElement('tbody');
		var row = document.createElement('tr');

		// Global classes
		$(this).addClass('jquery_grid bossanova-ui');
		$(table).addClass('jquery_grid_table');

		// Header cells
		var cell = '';

		// Combo with search options 
		var search_columns = '';

		// Grid columns and search column combo for the single search column grid
		var i = 0;
		$.each(options.columns, function(k, v) {
			// Column necessary attributes for the header
			cell = document.createElement('td');
			$(cell).attr("class", "jquery_grid_header_title");
			$(cell).attr("width", v.width);
			$(cell).css("width", v.width);
			if (v.display) $(cell).css("display", v.display);

			$(cell).html('<div style="position:absolute;"><div id="' + i++ + '" style="cursor:e-resize;width:2px;height:22px;position:relative;left:-7px;top:-5"></div></div>' + v.title + '<br>');
			$(row).append(cell);

			// Creating search option box
			if ((v.title) && (v.search > 0)) {
				search_columns += '<option value="' + k + '">' + v.title + '</option>';
			}
		});

		// Global objects 
		$(header).append(row);
		$(table).append(header);
		$(table).append(content);
		$(this).append(table);

		// Searching options
		var table_search = document.createElement('table');
		var table_search_tr = document.createElement('tr');

		// Searching type
		var type = 0;
		if (options.type == 1) type = 1;

		// Creating the row for search of grid is based on multiple column or single column search options
		if (type == 1) {
			// Create first TR of the table as the filters
			row = document.createElement("tr");

			// Creating the headers for the multiple column search grid
			i = 0;
			$.each(options.columns, function(k1, v1) {
				if (options.columns.length-1 > i) {
					// Create a new column
					cell = document.createElement("td");
					$(cell).attr("width", v1.width);
					$(cell).css("width", v1.width);
					if (v1.display) $(cell).css("display", v1.display);

					// Type of column
					if (v1.search == 1) {
						html = '<input type="text" name="q[' + i + ']" style="width:100%;" class="grid_search_field" onkeyup="if (event.keyCode == 13) { $(\'#' + main + '\').grid(\'search\', 0); }">';
					} else if (v1.search == 2) {
						html = '<select name="q[' + i + ']" style="width:100%;" class="grid_search_field" onchange="$(\'#' + main + '\').grid(\'search\', 0)"><option value=""></option>';
						$.each(v1.search_combo, function(k1, v1) {
							html += '<option value="' + k1 + '">' + v1 + '</option>';
						});
						html +='</select>';
					} else if (v1.search == 3) {
						var format = 'DD/MM/YYYY';
						if (v1.format) format = v.format;
						var id_calendar = main + '_select_column_value_' + i;
						html = '<input type="text" name="q[' + i + ']" id="' + id_calendar + '" style="width:100%;" class="grid_search_field"><script>$("#' + id_calendar + '").calendar({format:\'' + format + '\', clock:0, readonly:0 }); $("#' + id_calendar + '").next().keyup(function (event) {  if ($(this).val() == \'\') { $(this).val(\'\'); $(this).prev().val(\'\'); } if (event.keyCode == 13) $(\'#' + main + '\').grid(\'search\', 0); });</script>';
					} else {
						html = '<input style="width:100%;" class="grid_search_field" disabled="disabled" />';
					}

					// Header columns
					$(cell).html(html);
					$(cell).css("padding", "1px");
					row.appendChild(cell);

					i++;
				}
			});

			// Append row
			$(header).append(row);

			// Update button for the multiple search column grid
			$(table_search_tr).html('<td><input type="button" value="Update" onclick="$(\'#' + main + '\').grid(\'search\', 0);"></td>');
		} else {
			// Search row for the single column grid
			$(table_search_tr).html('<td style="padding:8px;">Filter</td><td><select id="grid_search_column" onchange="$(\'#' + main + '\').grid(\'options\', {})">' + search_columns + '</select></td><td id="grid_search_td_value"><input type="text" id="grid_search_column_value" style="width:220px;"></td><td><input type="button" value=" Go " onclick="$(\'#' + main + '\').grid(\'search\', 0)"></td>'); 
		}

		// Append search elements to the main grid table
		var table_search_div = document.createElement('div');
		var td1 = document.createElement('td');
		var td2 = document.createElement('td');

		// Creating search row
		$(table_search).append(table_search_tr);
		$(table_search_div).addClass("jquery_grid_content_search");
		$(table_search_div).append(table_search);
		$(this).append(table_search_div);

		// Creating HTML element for the search results counter
		$(td1).attr("align", "center");
		$(td1).attr("style", "padding:8px;");
		$(td1).attr("id", "grid_content_search_qr");
		$(td1).html("Showing from <b>0</b> to <b>0</b> of <b>0</b> results");
		$(table_search_tr).append(td1);

		// Page selector
		$(td2).attr("align", "right");
		$(td2).attr("id", "grid_content_search_sp");
		$(td2).html("<select id='grid_search_page'><option>0</option></select></td>");
		$(table_search_tr).append(td2);

		$(this).grid('load');
	},

	/**
	 * Create a grid HTML container
	 * @param {Object} options : grid default options
	 * @return void
	 */

	search : function( page ) { 

		var col = $(this).find('#grid_search_column').val();
		var val = $(this).find('#grid_search_column_value').val();

		if (!col) col = '0';
		if (!val) val = '';

		var fld = $(this).find('.grid_search_field').serialize();
		fld += '&column='+col;
		fld += '&value='+val;
		fld += '&page='+page;

		$(this).grid('load', { fields:fld });
	},

	/**
	 * Update the options for your search based on the column definitions
	 * @param {Object} param : parameters
	 * @return void
	 */

	options : function( param ) { 

		var main = $(this).attr("id");
		var column = $.fn.grid.options[main].columns;
		var td = $(this).find('#grid_search_td_value');
		var option = $(this).find('#grid_search_column').val();

		if (column[option]) {
			if (parseInt(column[option].search) == 1) {
				$(td).html('<input type="text" id="grid_search_column_value" style="width:220px;" onkeyup="if (event.keyCode == 13) { $(\'#' + main + '\').grid(\'search\', 0); }">');
			} else {
				if (parseInt(column[option].search) == 2) {
					var select = '<select id="grid_search_column_value" style="width:220px;"><option value=""></option>';
					$.each(column[option].search_combo, function(k1, v1) {
						select += '<option value="' + k1 + '">' + v1 + '</option>';
					});
					select +='</select>';
					td.html(select);
				} else {
					var format = 'DD/MM/YYYY';
					if (column[option].format) format = column[option].format;
					$(td).html('<input type="text" id="grid_search_column_value" style="width:220px;"><script>$("#grid_search_column_value").calendar({format:\'' + format + '\'});</script>');
				}
			}
		}
	},

	/**
	 * Refresh grid results
	 * @param void
	 * @return void
	 */

	refresh : function( ) {

		var col = $(this).find('#grid_search_column').val();
		var val = $(this).find('#grid_search_column_value').val();
		var pge = $(this).find('#grid_search_page').val();

		if (!col) col = '0';
		if (!val) val = '';
		if (!pge) val = '1';

		var fld = $(this).find('.grid_search_field').serialize();
		fld += '&column='+col;
		fld += '&value='+val;
		fld += '&page='+pge;

		$(this).grid('load', { fields:fld });
	},
	
	/**
	 * Loading grid data
	 * @param void
	 * @return void
	 */

	load : function( params ) {

		// Global objects
		var main = $(this).attr("id");
		var content = $(this).children("table").children("tbody");

		// Global options
		var options = $.fn.grid.options[main];

		// Default TR font color
		var color = 'black';

		// Source URL && Params
		var fields = '';
		if (params) {
			if (params.url) {
				options.url = params.url;
				params.url = null;
			}
			if (params && params.fields) {
				fields = params.fields;
			}
		}

		if (options.url) {
			$.getJSON(options.url, fields, function(data) {

				content.html("");

				var limit = 0;

				// If there is no results found
				if (data.total == 0) {
					row = document.createElement("tr");
					$(row).addClass("grid_content_record");

					cell = document.createElement("td");
					$(cell).attr("colspan", "99");
					$(cell).attr("style", "padding:10px;");
					$(cell).html('No records found');

					$(row).append(cell);
					$(content).append(row);
				}

				// Set the correct format for the result to be append in the grid table
				if (data.rows) {
					$.each(data.rows, function(k, v) {
						// Limit the number of records in the grid by 10
						if (limit < 11) {
							row = document.createElement("tr");
							$(row).addClass("jquery_grid_content_record");
							$(row).attr("id", v.id);

							// Default row font color
							if (v.color) {
								color = v.color;
							}

							cellnum = 0;
							$.each(v.cell, function(k1, v1) {
								cell = document.createElement("td");
								$(cell).html('<div style="overflow:hidden"><input style="width:100%;color:' + color + '" value="' + v1 + '" class="jquery_grid_column" readonly="readonly" onclick=" ' + options.actions[0].click + ' (' + row.id + ');" /></div>');
								$(cell).attr("width", options.columns[cellnum].width);
								$(cell).css("width", options.columns[cellnum].width);
								if (options.columns[cellnum].display) $(cell).css("display", options.columns[cellnum].display);
								$(row).append(cell);
								cellnum++;
							});

							html = '<div style="overflow:hidden">';

							$.each(options.actions, function(k1, v1) {
								html += '<img src="' + v1.icon + '" style="cursor:pointer;" onclick=" ' + v1.click + ' (' + row.id + ');">';
							});

							html += '</div>';

							cell = document.createElement("td");
							$(cell).html(html);
							$(row).append(cell);
							$(content).append(row);

							limit++;
						}
					});

					// Result counter
					var view1 = ((parseInt(data.page) - 1) * 10) + 1;
					var view2 = ((parseInt(data.page) - 1) * 10) + limit;

					var td1 = $('#' + main).find("#grid_content_search_qr");

					$(td1).html('Showing from <b>' + view1 + '</b> to <b>' + view2 + '</b> of <b>' + data.total + '</b> results');

					var pages = Math.ceil(data.total / 10);
					var pages_options = '';
					var pages_selected = '';

					for (var i = 1; i < pages+1; i++) {
						if (i == data.page) {
							pages_options += '<option value="' + i + '" selected="selected">' + i + '</option>';
						} else {
							 pages_options += '<option value="' + i + '">' + i + '</option>';
						}
					}

					// Replace counter results
					var td2 = $('#' + main).find("#grid_content_search_sp");
					$(td2).html('<select id="grid_search_page" onchange="$(\'#' + main + '\').grid(\'search\', this.value)">' + pages_options + '</select>');
				}
			});
		}
	}
};

$.fn.grid = function( method ) {

	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );

$.fn.grid.options = new Array();
