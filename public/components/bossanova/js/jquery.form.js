/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com

* JS UI Form
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
*******************************************************************************/

(function( $ ){

	var methods = {

	init : function( options ) {

		var defaults = {};

		// Save options in a global variable
		var options =  $.extend(defaults, options);
		var id = $(this).attr('id');
		$.fn.form.options[id] = options;

		// Add automatic save action if save bottom exists
		$(this).find("input[name='save']").click (function () {
			$('#'+id).form('save');
		});
	},
	open : function( key, __callback ) {

		// Load global option from the variable
		var id = $(this).attr('id');
		var options = $.fn.form.options[id];
		var url = options.url;
		var data;

		// Load URL
		if (key) url = url + '/select/' + key;

		$.ajax({
			url: url,
			type: 'GET',
			dataType:'json',
			success: function(result) {
				data = result;
				$.each(result, function(k, v) {
					obj = $('#'+id).find('[name="'+k+'"]');

					if ($(obj).attr("type") == "checkbox") {
						if (v == 1) {
							$(obj).attr("checked", "checked");
						} else {
							$(obj).removeAttr("checked");
						}
					} else if ($(obj).attr("type") == "password") {
						// Do not update password boxes
					} else {
						$(obj).val(v);
					}
				});
			}
		}).done(__callback, data);
	},
	save : function( __callback ) { 

		var id = $(this).attr('id');
		var options = $.fn.form.options[id];
		var string = '#'+id+' input, #'+id+' select, #'+id+' textarea';

		var primarykey = $(this).find('[name="'+options.primarykey+'"]');
		var url = options.url;

		if ($(primarykey).val() > 0) {
			url = url + '/update/' + $(primarykey).val();
		} else {
			url = url + '/insert';
		}

		$.ajax({
			url: url,
			type: 'POST',
			dataType:'json',
			data: $(string).serializeArray(),
			success: function(result) {

				if (!result.error) {
					if (!$(primarykey).val()) {
						if (result.id) $(primarykey).val(result.id);
					}

					if ($('#'+id+'_grid').length > 0) {
						$('#'+id+'_grid').grid('refresh');
					}
				}

				if (__callback) {
					__callback(result);
				} else {
					alert(result.message);
				}
			}
		});
	},
	delete : function( key, __callback ) {
		var id = $(this).attr('id');
		var options = $.fn.form.options[id];
	
		$.ajax({
			url: options.url + '/delete/' + key,
			type: 'DELETE',
			dataType:'json',	
			success: function(result) {
				if (__callback) {
					__callback(result);
				} else {
					alert(result.message);

					if ($('#'+id+'_grid').length > 0) {
						$('#'+id+'_grid').grid('refresh');
					}
				}
			}
		});
	}
};

$.fn.form = function( method ) {

	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );

$.fn.form.options = new Array();