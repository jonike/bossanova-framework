/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2015 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com
*
* JS UI Modal
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
	 * Create the base HTML for the modal
	 * @param {Object} options : modal options
	 * @return void
	 */

	init : function( options ) { 

		var defaults = { title:'Untitled', closed:0, width:600, height:480 };
		var options =  $.extend(defaults, options);

		// Create the accordion in the first call, from the second call open modal

		$.each ($(this), function (k, v) {

			if ($(this).hasClass('jquery_modal') == true) {
				if ($(this).css('display') == 'none') {
					$(this).css('display', '');
				} else {
					$(this).css('display', 'none');
				}
			} else {
				// Set class as the default class
				$(this).attr('class', 'jquery_modal bossanova-ui');

				// Loading HTML from the original source
				var html = $(this).html();

				// Preparing new HTML container
				html = '<div class="jquery_modal_container" style="width:'+options.width+'px;height:'+options.height+'px;"><div class="jquery_modal_title" unselectable="on">' + options.title + '<span style="float:right;cursor:pointer;" onclick="$(this).parent().parent().parent().modal();">x</span></div><div class="jquery_modal_content"> ' + html + ' </div></div>';

				// Current status
				if (options.closed == 1) {
					$(this).css('display', 'none');
				}

				// Loading HTML into the new formated modal
				$(this).html(html);
			}

			// Keep initial conditions
			$(this).find('.jquery_modal_title').mousedown(function( event ) {
				jquery_modal_coord_x = event.clientX;
				jquery_modal_coord_y = event.clientY;
				jquery_modal_coord_t = parseInt($(this).parent().css('top'));
				jquery_modal_coord_l = parseInt($(this).parent().css('left'));

				if ( document.selection ) {
					document.selection.empty();
				} else if ( window.getSelection ) {
					window.getSelection().removeAllRanges();
				}
			});

			// Move dialog box
			$(this).find('.jquery_modal_title').mousemove(function( event ) {
				if (event.buttons == 1 || event.buttons == 3) {
					$(this).parent().css('top', jquery_modal_coord_t - (jquery_modal_coord_y - event.clientY) + 'px');
					$(this).parent().css('left', jquery_modal_coord_l - (jquery_modal_coord_x - event.clientX) + 'px');
					$(this).css('cursor', 'move');
				} else {
					$(this).css('cursor', 'auto');
				}
			});
		})
	}
};

$.fn.modal = function( method ) {

	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );

var jquery_modal_coord_x = 0;
var jquery_modal_coord_y = 0;
var jquery_modal_coord_t = 0;
var jquery_modal_coord_l = 0;