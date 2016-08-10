/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com
*
* JS UI Tabs
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
	 * Create an tab object
	 * @param {Object} options : tab options
	 * @return void
	 */

	init : function( options ) {

		var defaults = { };
		var options =  $.extend(defaults, options);

		// Keep the id from the tab object
		var main = $(this).attr("id");

		// Save the options in a global scope to be used later 
		$.fn.tabs.options[main] = options;
		$(this).attr("style", "width:100%;height:100%;");

		// Create the main table element
		var table = document.createElement('table');
		var thead = document.createElement('thead');
		var tbody = document.createElement('tbody');
		var tr1 = document.createElement('tr');
		var tr2 = document.createElement('tr');

		// Elements necessary properties
		$(table).attr("class", "jquery_tabs bossanova-ui");
		$(table).attr("cellpadding", "0");
		$(table).attr("cellspacing", "0");

		// Split head and body elements in the table
		$(tr1).html("<td valign='bottom' style='height:10px;vertical-align:bottom;'><div class='jquery_tabs_head'></div></td>");
		$(tr2).html("<td><div style='height:100%;clear:both;' class='jquery_tabs_body'></div></td>");

		// Appeding necessary elements in the table
		$(thead).append(tr1);
		$(tbody).append(tr2);
		$(table).append(thead);
		$(table).append(tbody);

		// Append final table inside the main object 
		$(this).html(table);
	},

	/**
	 * Add a new tab
	 * @param {Object} tab : tab options
	 * 										{
	 *											title:		'Title of the tab',
	 *											id:			'unique_string_identification_of_the_tab',
	 *											html:		'html for the tab content',
	 *											url:		'url for the tab content',
	 *											padding:	'tab padding',
	 *											closable:	true
	 *										}
	 * @return void
	 */

	add : function( tab, __callback ) {
		// Keep compatibility, this will be deprected for the next version
		if (tab.index) {
			tab.id = tab.index;
		}

		// Get the id from the object
		var main_id = $(this).attr("id");

		// Check to see if the tab with this id already exists
		if ($('#' + main_id + '_' + tab.id).length == 0) {
			// Create the new tab object
			var item = document.createElement("div");
			$(item).attr("class", "jquery_tabs_tab");
			$(item).attr("id", main_id + '_' + tab.id);
			$(item).attr("onclick", "$('#" + main_id + "').tabs('open', '" + tab.id + "');");

			// Create the tab title
			var span = document.createElement("span");
			$(span).html(tab.title);
			$(span).attr("onclick", "$('#" + main_id + "').tabs('open', '" + tab.id + "')");
			$(item).append(span);

			// Create a close buttom and append to the tab case option exists
			if (tab.closable != 0) {
				var close = document.createElement("span");
				$(close).attr("class", "jquery_tabs_close");
				$(close).attr("onclick", "$('#" + main_id + "').tabs('close', '" + tab.id + "')");
				$(close).html('x');
				$(item).append(close);
			}

			// Add the tab in the correct place
			$(this).children('table').children('thead').children('tr').children('td').children('div').append(item);

			// Here we start adding the related content to the tab
			item = document.createElement("div");
			$(item).attr("class", "jquery_tabs_content");
			$(item).attr("id", main_id + '_' + tab.id + '_content');
			$(item).css('display', 'none');

			// This is to remove the default padding
			if (tab.padding == '0') {
				$(item).css('padding', '0px');
			}

			// Add any initial static HTML content
			if (tab.html) {
				$(item).html(tab.html);
			}

			// Copy any initial static HTML content from any other HTML object on the page
			if (tab.src) {
				$(item).html($(tab.src).html());
			}

			// Add a remote HTML content to the tab
			if (tab.url) {
				// You can submit adicional option in the ajax call
				if (!tab.data) $(item).load(tab.url, function () {
					if (typeof __callback == 'function') {
						__callback();
					}
				});
				else
				{
					$(item).load(tab.url, tab.data, function () {
						if (typeof __callback == 'function') {
							__callback();
						}
					});
				}
			}

			// Add the content container element in the correct place
			$(this).children('table').children('tbody').children('tr').children('td').children('div').append(item);

		} else if (tab.reload == 1) {
			// Force the reload of the data in the already existing tab
			if (tab.url) {
				if (!tab.data) $('#' + main_id + '_' + tab.id + '_content').load(tab.url, function () {
					if (typeof __callback == 'function') {
						__callback();
					}
				});
				else
				{
					$('#' + main_id + '_' + tab.id + '_content').load(tab.url, tab.data, function () {
						if (typeof __callback == 'function') {
							__callback();
						}
					});
				}
			}
		}

		$(this).tabs('open', tab.id);
	},

	/**
	 * Open a tab
	 * @param {string} id : tab id to be opened
	 * @return void
	 */

	open : function( id ) {
		// Get the id from the object
		if (id) id = $(this).attr("id") + '_' + id;

		// Find all tabs in the tabs head container
		j = $(this).find('thead .jquery_tabs_tab');

		// Apply several styling changes in all tabs
		for (var i = 0; i < j.length; i++) {
			// If id is NULL we will open the first tab
			if (!id) id = $(j[i]).attr("id");

			$(j[i]).css('z-index', '0')
			$(j[i]).css('top', '2px')
			$(j[i]).attr("class","jquery_tabs_tab");

			// Hide all contents
			$('#' + j[i].id + '_content').css("display","none");
		}

		// Apply some styling in the selected tab
		$('#'+id).attr("class", "jquery_tabs_tab jquery_tabs_tab_selected");
		$('#'+id).attr("style", "z-index:0;display:block;top:0px;");

		// Show selected related content
		$('#'+id+'_content').css("display", "block");

		// This is to keep compatibility with google maps full resize inside the div (need to use map variable name)
		if (typeof map == 'object') google.maps.event.trigger(map, 'resize');
	},

	/**
	 * Close a tab by id
	 * @param {string} id : tab id to be closed
	 * @return void
	 */

	close : function( id ) {
		// Get the id from the object
		id = '#' + $(this).attr("id") + '_' + id;

		// Hide the closed tab
		$(id).css('display', 'none');

		// Open the first visible tab
		$(this).tabs('open');

		// Stop onclick propagation
		e = window.event;

		if (e.stopPropagation) {
			e.stopPropagation();
		} else {
			e.cancelBubble = true;
		}

		return false;
	}
};

$.fn.tabs = function( method ) {

	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );

$.fn.tabs.options = new Array();