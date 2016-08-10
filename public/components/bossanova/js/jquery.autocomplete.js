/*******************************************************************************
* Bossanova Jquery UI 1.2.0
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com/ui
*
* JS UI Aucocomplete
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
	 * Create an autocomplete element. Normally this is used in relation to a remove
	 * file that performance a query in a database for example.
	 * @param {Object} options : accordion options
	 * @return void
	 */

	init : function( options ) { 

		// options.type {String} single-regular for a single option only or multiple options selection.
		var defaults = { type:'multiple' };

		// Keep the options
		var options =  $.extend(defaults, options);
		var main = '#' + $(this).attr("id");
		$.fn.autocomplete.options[main] = options;

		// Create autocomplete results box
		var autocomplete_results = document.createElement('div');
		$(autocomplete_results).attr("id", $(this).attr("id") + "_results");
		$(autocomplete_results).attr("class", "jquery_autocomplete_results bossanova-ui");
		$(autocomplete_results).attr("style", "position:absolute;display:none;");
		if (parseInt($(this).css("width")) > 0) $(autocomplete_results).css("width", $(this).css("width"));

		// Create autocomplete selected options box
		var autocomplete_options = document.createElement('div');
		$(autocomplete_options).attr("id", $(this).attr("id") + "_options");
		$(autocomplete_options).addClass("jquery_autocomplete_options bossanova-ui");

		// Create search input
		if (options.type == 'single-regular') {
			var autocomplete_input = $('#'+$(this).attr("id"));
			$(autocomplete_input).attr("onkeyup", "if (jquery_autocomplete_timeout) clearInterval(jquery_autocomplete_timeout); jquery_autocomplete_timeout = window.setTimeout(function (a, b) { a.autocomplete('search', b) }, 500, $('#" + $(this).attr("id") + "'), this.value);");
			$(autocomplete_input).attr("onblur", "$('#" + $(this).attr("id") + "_results').fadeOut('fast', null)");
		} else {
			var autocomplete_input = document.createElement('input');
			if ($(this).attr("style")) $(autocomplete_input).attr("style", $(this).attr("style"));
			$(autocomplete_input).attr("onkeyup", "if (event.keyCode == 40) { $('#" + $(this).attr("id") + "').autocomplete('select_item', 2); } else if (event.keyCode == 38) { $('#" + $(this).attr("id") + "').autocomplete('select_item', 1); } else if (event.keyCode == 13) { $('#" + $(this).attr("id") + "').autocomplete('select_item', 3); } else { if (jquery_autocomplete_timeout) clearInterval(jquery_autocomplete_timeout); jquery_autocomplete_timeout = window.setTimeout(function (a, b) { a.autocomplete('search', b) }, 500, $('#" + $(this).attr("id") + "'), this.value); }");
			$(autocomplete_input).attr("onblur", "this.value = ''; $('#" + $(this).attr("id") + "_results').fadeOut('fast', null)");
			$(autocomplete_input).attr("type", "text");
			$(this).css("display", "none");
			$(this).before($(autocomplete_input));
		}

		// Append element
		$(this).after($(autocomplete_options));
		$(this).after($(autocomplete_results));
	},

	/**
	 * Select items using the keyboard (arrow up, down and enter to select)
	 * @param {Object} options : accordion options
	 * @return void
	 */

	select_item : function( dir ) {
		var main = "#" + $(this).attr("id");
		var content = "#" + $(this).attr("id") + "_results";
		var selected = $(content).find("li.autocomplete_content_record_selected");

		if (selected.length == 0) {
			selected = $(content).find("li.autocomplete_content_record");
			if (dir == 2) selected.first().attr('class', 'autocomplete_content_record_selected');
		} else {
			if (dir == 3) {
				$(selected).click();
				$(main).parent().find("input").blur();
			} else {
				if (dir == 2) {
					var prox = selected.next();
				} else {
					var prox = selected.prev();
				}

				if (prox.length > 0) {
					$(content).find("li.autocomplete_content_record_selected").attr('class', 'autocomplete_content_record');
					prox.attr('class', 'autocomplete_content_record_selected');
				}
			}
		}
	},

	/**
	 * Perform a search in the remote URL
	 * @param {String} val : string value of the search, normalized used to query something in the database
	 * @return void
	 */

	search : function( val ) {
		// Get main object ID
		var main = "#" + $(this).attr("id");

		// Loading configuration options
		var options = $.fn.autocomplete.options[main];

		// Current selected items
		var items = $(main).val().split(',');
	
		// Remove selected items from any possible result to avoid duplications
		var excluir = new Array;
		for (i = 0; i < items.length; i++) {
			excluir[items[i]] = 1;
		}

		// Check for any adicional argument to be sent
		var argument = '';
		if (options.argument) argument = $(options.argument).val();

		// Preparing box of results
		var content = "#" + $(this).attr("id") + "_results";
		$(content).css("display", "block");
		$(content).html("<div style='padding:6px;'>Searching...</div>");

		// Perform a remote search in the URL instantiated
		if ((options.url) && (val)) {
			$.ajax({
				url: options.url,
				type: 'GET',
				dataType:'json',
				data: { q:$.trim(val), selected:$(main).val(), argument:argument },
				success: function(result) {
					if (!result) {
						$(content).html("<div style='padding:6px;'>No results found.</div>");
					} else {
						$(content).html("");
						var limit = 0;
						$.each(result, function(k, v) {
							if ((limit < 50) && (!excluir[v.id])) {
								$(content).append('<li id="' + v.id + '" class="autocomplete_content_record" onclick="$(\'' + main + '\').autocomplete(\'add\', {id: this.id, name: this.innerHTML})">' + v.name + '</li>');
								limit++;
							}
						});

						if (limit == 0) {
							$(content).html("<div style='padding:6px;'>No results found.</div>");
						}
					}
				}
			});
		}
	},

	/**
	 * Add a item in the selected list
	 * @param {String} val : string value of the search, normalized used to query something in the database
	 * @return void
	 */

	add : function( data ) {
		// Get the main object
		var main = "#" + $(this).attr("id");

		// Get the options container
		var content = "#" + $(this).attr("id") + "_options";

		// Loading global configuration
		var options = $.fn.autocomplete.options[main];

		// Add a new item as a selected item
		if (options.type == 'single-regular') {
			$(main).attr("value", data.id);
		} else {
			var autocomplete_record = document.createElement('div');
			$(autocomplete_record).html('<input type="checkbox" value="' + data.id + '" checked="checked" onclick="$(parentNode).remove(); $(\'' + main + '\').autocomplete(\'update\');"> <span>' + data.name + '</span><br>');
			$(content).append(autocomplete_record);
			$(this).autocomplete('update');
		}
	},

	/**
	 * Update the selected list in case of remove one item from the list
	 * @return void
	 */

	update : function( ) {
		// Get main object
		var main = "#" + $(this).attr("id");

		// Loading selected items container
		var content = "#" + $(this).attr("id") + "_options";

		// Update selected list
		var items = $(content).find("input");
		var items_selected = '';

		for (var i = 0; i < items.length; i++) {
			if (items_selected != '') items_selected += ',';
			items_selected += items[i].value;
		}

		$(main).val(items_selected);
	},

	/**
	 * Update the selected list in case of remove one item from the list
	 * @param {String} val : string value of the search, normalized used to query something in the database
	 * @return void
	 */

	reset : function( ) {
		// Get the main object
		var main = "#" + $(this).attr("id");

		// Loading selected list container
		var content = "#" + $(this).attr("id") + "_options";

		// Reset container
		$(content).html('');

		// Reset main object value
		$(main).val('');
	}
};

$.fn.autocomplete = function( method ) {
	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}
};

})( jQuery );

// Global configuration holder
$.fn.autocomplete.options = new Array;

// Global autocomplete action timeout
var jquery_autocomplete_timeout = 0;