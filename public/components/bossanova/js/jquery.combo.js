/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com
*
* JS UI Combo
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

	init : function( options, callback ) { 

		// Callback function
		if (!callback) callback = function () { }

		var defaults = { include_blank_option:1 };
		var options =  $.extend(defaults, options);

		// Selector to the main object
		var select = $(this);

		// Blank option?
		if (options.include_blank_option == 1) {
			$(select).append($("<option />").val('').text(''));
		}

		// Load options from a remote URL
		if (options.url) {
			$.getJSON (options.url, function (data) {
				// Create options
				$.each(data, function( k, v ) {
					$(select).append($("<option />").val(v.id).text(v.name));
				});

				if (options.value) {
					$(select).val(options.value);
				}
			})
			.always(callback);
		} else if (options.numeric) {
			// Create numeric options
			for (i = options.numeric[0]; i <= options.numeric[1]; i++) {
				$(select).append($("<option />").val(i).text(i));
			}
		}
	}
};

$.fn.combo = function( method ) {
	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );