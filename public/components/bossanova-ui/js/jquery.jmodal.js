/**
 * (c) 2013 jModal Bossanova UI
 * http://www.github.com/paulhodel/bossanova-ui
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Simple modal
 */

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

			if ($(this).hasClass('jmodal') == true) {
				if ($(this).css('display') == 'none') {
					$(this).css('display', '');
				} else {
					$(this).css('display', 'none');
				}
			} else {
				// Set class as the default class
				$(this).attr('class', 'jmodal bossanova-ui');

				// Loading HTML from the original source
				var html = $(this).html();

				// Preparing new HTML container
				html = '<div class="jmodal_container" style="min-width:'+options.width+'px;height:'+options.height+'px;"><div class="jmodal_title" unselectable="on">' + options.title + '<span style="float:right;cursor:pointer;" onclick="$(this).parent().parent().parent().jmodal();">x</span></div><div class="jmodal_content"> ' + html + ' </div></div>';

				// Current status
				if (options.closed == 1) {
					$(this).css('display', 'none');
				}

				// Loading HTML into the new formated modal
				$(this).html(html);
			}

			var jmodal_coord_x = 0;
			var jmodal_coord_y = 0;
			var jmodal_coord_t = 0;
			var jmodal_coord_l = 0;

			// Keep initial conditions
			$(this).find('.jmodal_title').mousedown(function( event ) {
				jmodal_coord_x = event.clientX;
				jmodal_coord_y = event.clientY;
				jmodal_coord_t = parseInt($(this).parent().css('top'));
				jmodal_coord_l = parseInt($(this).parent().css('left'));

				if ( document.selection ) {
					document.selection.empty();
				} else if ( window.getSelection ) {
					window.getSelection().removeAllRanges();
				}
			});

			// Move dialog box
			$(this).find('.jmodal_title').mousemove(function( event ) {
				if (event.buttons == 1 || event.buttons == 3) {
					$(this).parent().css('top', jmodal_coord_t - (jmodal_coord_y - event.clientY) + 'px');
					$(this).parent().css('left', jmodal_coord_l - (jmodal_coord_x - event.clientX) + 'px');
					$(this).css('cursor', 'move');
				} else {
					$(this).css('cursor', 'auto');
				}
			});
		})
	}
};

$.fn.jmodal = function( method ) {

	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );