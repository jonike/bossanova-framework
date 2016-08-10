/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com
*
* JS UI Color
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

	init : function( options ) { 

		var defaults = { };
		var options =  $.extend(defaults, options);

		$(this).attr('class', $(this).attr('class') + ' jquery_color_input');
		var div = document.createElement('div');
		$(div).attr('class', 'jquery_color');

		$(this).after('<img class="jquery_color_icon" onclick="$(this).prev().prev().color(\'open\');"/>');
		$(this).after($(div));

		$(div).css('display','none');

		var color_table = document.createElement('table');
		$(color_table).attr('cellpadding', '0');
		$(color_table).attr('cellspacing', '0');

		var color_html = '';
		
		var x = 0;
		var y = 0;
		var z = 0;

		var palette = new Array('00','33','66','99','CC','FF');

		for (x = 0; x < 6; x++) {
			color_html += '<tr>';

			for (y = 0; y < 6; y++) {
				for (z = 0; z < 6; z++) {
					color_html += '<td><div style="background-color:#' + palette[x] + palette[y] + palette[z] + '" onclick="$(this).parent().parent().parent().parent().parent().color(\'set\', \'' + palette[x] + palette[y] + palette[z] + '\')"></div></td>';
				}
			}

			color_html += '</tr>';
		}

		$(color_table).html(color_html);

		$(div).html(color_table);
	},
	set : function( value ) {

		$(this).css('display','none');

		$(this).prev().val('#' + value);
	},
	open : function ( ) {

		var value = $(this).val();
		var pos = $(this).position();
		var div = $(this).next();
	
		$(div).css('top', pos.top);
		$(div).css('left', pos.left);
		$(div).css("display", "");
	},
	close : function ()
	{
		var div = $(this).next().next();

		div.css('display', 'none');
	}
};

$.fn.color = function( method ) {
	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );
