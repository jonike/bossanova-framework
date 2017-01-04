/**
 * (c) 2013 Bossanova UI
 * http://www.github.com/paulhodel/bossanova-ui
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Simple accordion
 * 
 */

(function( $ ){

	var methods = {

	/**
	 * Create an accordion element
	 * @param {Object} options : accordion options
	 * @return void
	 */

	init : function( options ) { 
		var defaults = {};
		var options =  $.extend(defaults, options);

		// Set the class to provide the correct CSS style based on the main object
		$(this).attr("class", "jaccordion bossanova-ui");

		// Default child padding
		$(this).children("div").children("div").css("display", "none").css("padding", "10px");

		// Onclick event for every single element in the accordion
		$(this).children("div").children("h1").attr("onclick", "$(this).parent().parent().jaccordion('open', $(this))");
	},

	/**
	 * Open an element in the accordion
	 * @param {Object} tab : clicked inside the accordion sent as an object
	 * @return void
	 */

	open : function( element, __callback ) {
		// Select the element to be openned
		if (typeof element == 'number') {
			var obj = $(this).children("div").children("div").get(element)
		} else {
			var obj = $(element).next();
		}

		// Open object
		if ($(obj).css('display') == 'none') {
			// Close all child elements
			$(this).children("div").children("div").css("display", "none");

			$(obj).css("display", "");

			// Load the content inside the accordion
			if (($(obj).attr("url")) && (!$(obj).html())) {
				$.get($(obj).attr("url"), function (data) {
					$(obj).html(data);
					if (typeof __callback == 'function') __callback();
				});
			}
		} else {
			$(this).children("div").children("div").css("display", "none");
		}
	},

	/**
	 * Loading a static content inside an element in the accordion
	 * @param {Object} element {String} html : content to be inserted inside the element
	 * @return void
	 */

	load : function( element, html ) { 
		// Select the element
		var tab = $(this).children("div").get(element);

		// Add the HTML static content
		$(tab).children("div").html(html);
	},

	/**
	 * Loading a remote content inside an element in the accordion
	 * @param {Object} element {String} url : remove URL to load the content inside the element
	 * @return void
	 */

	loadUrl : function( element, url ) { 
		// Select the element
		var tab = $(this).children("div").get(element);

		// Add the HTML remote content inside
		$(tab).children("div").load(url);
	},

	/**
	 * Add a new element in the accordion with a static HTML content inside
	 * @param {String} title {String} html : add a new element with a static content inside
	 * @return void
	 */

	add : function( title, html ) { 

		// New element to be append in the accordion
		var html = '<div><h1 onclick="$(this).parent().parent().jaccordion(\'open\', $(this))"><span>' + title +'</span></h1><div style="padding:10px;display:none;">' + html + '</div></div>';

		// Append the element
		$(this).append(html);
	},

	/**
	 * Add a new element in the accordion with a remote HTML content inside
	 * @param {String} title {String} url : remove URL to load the content inside the element
	 * @return void
	 */

	addUrl : function( title, url ) { 

		// New element to be append in the accordion
		var html = '<div><h1 onclick="$(this).parent().parent().jaccordion(\'open\', $(this))"><span>' + title +'</span></h1><div style="padding:10px;display:none;" url="' + url + '"></div></div>';

		// Append the element
		$(this).append(html);
	},
	
	/**
	 * Remove a element from the accordion
	 * @param {Object} element : remove an element from the accordion 
	 * @return void
	 */

	remove : function( element ) {
		// Select the element and remove it
		$(this).children("div").get(element).remove();
	}
};

$.fn.jaccordion = function( method ) {
	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );
