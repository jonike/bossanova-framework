/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com

* JS UI Menu
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
	 * Create the base HTML menu
	 * @param {Object} options : menu options
	 * @return void
	 */

	init : function( options ) {
		// Loading default configuration
		var defaults = {};
		var options =  $.extend(defaults, options);

		// Create main menu table container
		var table = document.createElement('table');
		$(table).attr('class','jquery_menu');
		$(table).attr('cellpadding','0');
		$(table).attr('cellspacing','0');

		// Loading menu json content
		$(this).menu('load', table, options.url);

		// Append menu to the main HTML menu
		$(this).html(table);
	},

	/**
	 * Load the menu content from a remote URL
	 * @param {Object} options : menu options
	 * @return void
	 */

	load : function( table, url ) {

		var click = '';

		// Clear container table
		$(table).html("");

		$.getJSON( url, function(data) {

			$.each(data, function(k, v) {

				subitens = '';

				if (v.itens) {
					$.each(v.itens, function(k1, v1) {
						click = '';
						if (v1.click) click = v1.click;
						if (v1.tab) {
							url = bossanova_url;
							//if (bossanova_base) url += bossanova_base + '/';
							url += v1.tab;
							click += "$('#tabs').tabs('add', {title:'"+v1.title+"', id:'"+v1.id+"', closable:'1', url:'" + url + "' });";
						}
						subitens += '<li onmouseover="$(this).attr(\'class\',\'jquery_menu_mouseover\');"  onmouseout="$(this).attr(\'class\',\'jquery_menu_mouseout\')" onclick="$(this).parent().css(\'display\', \'none\'); '+ click +'">' + v1.title + '</li>';
					});
				}

				click = '';
				if (v.click) click = v.click;
				if (v.tab) {
					url = bossanova_url;
					//if (bossanova_base) url += bossanova_base + '/';
					url += v.tab;
					click += "$('#tabs').tabs('add', {title:'"+v.title+"', id:'"+v.id+"', closable:'1', url:'" + url + "' });";
				}

				item = '';

				if (v.caption) {
					item += '<td id="eventos_chamada" style="display:none;" valign="top" onmouseover="$(this).children(\'ul\').css(\'display\', \'block\'); $(this).children(\'ul\').children(\'li\').css(\'width\', $(this).children(\'div\').css(\'width\'));" onmouseout="$(this).children(\'ul\').css(\'display\', \'none\');">';
					item += '<div class="jquery_menu_item" onclick="'+click+'" style="width:160px;"><table><td width="25"><img src="img/'+v.icon+'" title="'+v.title+'"></td><td style="text-align:left"><span class="blink_me">' + v.caption + '</span></td></table></div>';
					item += '</td>';
				} else {
					item += '<td valign="top" onmouseover="$(this).children(\'ul\').css(\'display\', \'block\'); $(this).children(\'ul\').children(\'li\').css(\'width\', $(this).children(\'div\').css(\'width\'));" onmouseout="$(this).children(\'ul\').css(\'display\', \'none\');">';
					if (v.icon) {
						item += '<div class="jquery_menu_item jquery_menu_img" onclick="'+click+'"><table><td><img src="img/'+v.icon+'" title="'+v.title+'"></td></table></div>';
					} else if (subitens) {
						item += '<div class="jquery_menu_item" onclick="'+click+'"><table><td>' + v.title + '</td></table><span class="jquery_menu_arrow"></span></div><ul>' + subitens + '</ul>';
					} else {
						item += '<div class="jquery_menu_item" onclick="'+click+'"><table><td>' + v.title + '</td></table></div>';
					}
					item += '</td>';
				}

				if (item) $(table).append(item);
			});
		});
	},

	/**
	 * Reload the menu based on a remote URL
	 * @param {string} URL : remote URL
	 * @return void
	 */

	refresh : function( url ) {
		// Select main table container
		var table = $(this).children('table');

		// Reload content
		$(this).menu('load', table, url);
	},

	/**
	 * Add a new main item in the menu
	 * @param {string} URL : remote URL
	 * @return void
	 */

	add : function( v ) { 
		// Define the new item HTML body
		var item = '<td onmouseover="$(this).children(\'ul\').css(\'display\', \'block\'); $(this).children(\'ul\').children(\'li\').css(\'width\', $(this).children(\'div\').css(\'width\'));" onmouseout="$(this).children(\'ul\').css(\'display\', \'none\');"><div><span>' + v.title + '</span></div></td>';
		// Append the item in the table container
		$(this).children('table').append(item);
	},

	/**
	 * Remove an item from the menu
	 * @param {integer} num : child position
	 * @return void
	 */

	remove : function( num ) {
		// Select the menu itens
		var td = $(this).children('table').children('td');
		// Remote the item
		$(td[num]).remove();
	}
};

$.fn.menu = function( method ) {
	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}
};

})( jQuery );