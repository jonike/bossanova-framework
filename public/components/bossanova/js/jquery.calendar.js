/*******************************************************************************
* Bossanova PHP Framework 1.0.1
* 2013 Paul Hodel <paul.hodel@gmail.com> 
* http://www.bossanova-framework.com
*
* JS UI Calendar
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
	 * Create a calendar picker element based on existing input. If calendar already exists open the calendar picker.
	 * @param {Object} options : calendar default options
	 * @return void
	 */

	init : function( options ) { 

		// Default options
		// format: calendar format
		// readonly: input text is a readonly [0/1]
		// clock: show the hour and minutes picker [0/1]
		// months: array of string so can be translated
		// weekdays: array of string so can be translated

		var defaults = {	format:'DD/MM/YYYY HH24:MI',
							readonly:1,
							today:0,
							clock:1,
							clear:1,
							months:['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
							weekdays:['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
							weekdays_short:['S', 'M', 'T', 'W', 'T', 'F', 'S']
						};

		// Save the options binded to the correct element
		var options =  $.extend(defaults, options);

		// Make sure use upper case in the format
		options.format = options.format.toUpperCase();

		// Assembly the calendar
		$.each ($(this), function (k, v) {
			if ($(this).hasClass('jquery_calendar_value') == true) {
				//$(this).calendar('open');
			} else {
				$(this).calendar('create', v, options);
			}
		})
	},

	/**
	 * Create the elements for every object in the selector
	 * @param {Object} obj : object selected in the selector
	 * @param {Object} options : options
	 * @return void
	 */

	create : function( obj, options ) {
		// Object id
		var id = $(obj).attr('id');

		// Keep non id elements to be instantiate without any problems
		if (!id) {
			id = 'calendar_' + Math.floor(Math.random() * 1000) + 9999;
			$(this).attr('id', id);
		}

		// Save options
		$.fn.calendar.options[id] = options;

		// Months
		var months = options.months;

		// Global attributes
		var input = document.createElement('input');
		if ($(obj).attr('class')) {
			$(input).attr('class', $(obj).attr('class'));
		}
		$(input).addClass('jquery_calendar_input');
		$(input).removeClass('calendar');
		$(input).attr('type', 'text');

		// Copy style and class from the calendar element to the visual element
		if ($(obj).attr('style')) {
			$(input).attr('style', $(obj).attr('style'));
		}

		// Adding calendar class for styling
		$(obj).addClass('jquery_calendar_value');

		// Check for read only or create a mask to the visual input
		if (options.readonly == 1) {
			$(input).attr('readonly', 'readonly');
			$(input).attr('onclick', "$('#"+id+"').calendar('open')");
		} else {
			// The Masking will work only with NUMBERS
			$(input).keydown(function (e) {
				e = e || window.event;
				var code = e.charCode || e.keyCode;

				// Ignore some of the keys to format
				if (code == 13 || code == 32 || code == 8 || code == 37 || code == 39) {
				} else {
					// Get the cursor position
					var start = this.selectionStart;

					// Variables to help the mask
					var repos = false;
					var position = 0;

					// Visual data
					var v1 = $(this).val();
					v1 = v1.substr(0,start);
					v1 = v1.replace(/[^0-9]/g,'');
					var v2 = options.format.replace(/[0-9]/g,'');
					var v3 = '';
					j = 0;

					// Format the values following the format rules
					for (i = 0; i < v2.length; i++) {
						if (v2[i].match(/[a-zA-Z]/)) {
							if (v1[j]) {
								v3 += v1[j];
								j++;
								position = i;
							} else {
								if (repos == false) {
									repos = true;
									position = i;
								} else {
									v3 += '_';
								}
							}
						} else {
							v3 += v2[i];
						}
					}

					// Set the value in the input again with corrections
					$(this).val(v3);

					// Set the cursor position
					if (start < position - 1) position = start;
					this.setSelectionRange(position, position);
				}
			});

			// Functions to mask the calendar when is possible to type in the date values in the input box
			$(input).keyup(function (e) {
				var v1 = $(this).val();
				var v2 = options.format.replace(/[0-9]/g,'');
				if (v1.length > v2.length) {
					v1 = v1.substr(0, v2.length);
					$(this).val(v1);
				}
			})

			// Copy the visual data to the calendar element in the final format
			$(input).blur(function () {
				var v1 = $(this).val();
				var v2 = options.format.replace(/[0-9]/g,'');

				var test = 1;
				// Get year
				var y = v2.search("YYYY");
				y = v1.substr(y,4).replace('_', '');
				if (y.length != 4) test = 0;
				// Get month
				var m = v2.search("MM");
				m = v1.substr(m,2).replace('_', '');
				if (m.length != 2 || m == 0 || m > 12) test = 0;
				// Get day
				var d = v2.search("DD");
				d = v1.substr(d,2).replace('_', '');
				if (d.length != 2 || d == 0 || d > 31) test = 0;

				// Get hour
				var h = v2.search("HH");
				if (h > 0) {
					h = v1.substr(h,2).replace('_', '');
					if (h.length != 2 || h > 23) test = 0;
				} else {
					h = '00';
				}
				// Get minutes
				var i = v2.search("MI");
				if (i > 0) {
					i = v1.substr(i,2).replace('_', '');
					if (i.length != 2 || i > 60) test = 0;
				} else {
					i = '00';
				}
				// Get seconds
				var s = v2.search("SS");
				if (s > 0) {
					s = v1.substr(s,2).replace('_', '');
					if (s.length != 2 || s > 60) test = 0;
				} else {
					s = '00';
				}

				if (test == 1) {
					var data = y + '-' + m + '-' + d + ' ' + h + ':' +  i + ':' + s;
					$(this).prev().val(data);
				} else {
					$(this).val('');
					$(this).prev().val('');
				}
			});
		}

		// Setting todays object date to today
		if ($(obj).val() == '' && options.today == 1) {
			var data = new Date();
			dia = ''+ data.getDate();
			if (dia.length == 1) dia = '0' + dia;
			mes = ''+(data.getMonth() + 1);
			if (mes.length == 1) mes = '0' + mes;
			ano = data.getFullYear();
			hora = ''+data.getHours();
			if (hora.length == 1) hora = '0' + hora;
			min = ''+data.getMinutes();
			if (min.length == 1) min = '0' + min;
			$(obj).val(ano + '-' + mes + '-' + dia + ' ' + hora + ':' + min + ':00');
		}

		// Creating calendar container
		var div = document.createElement('div');
		$(div).attr('class', 'jquery_calendar bossanova-ui');

		// Structure of objects
		$(obj).after('<div style="position:absolute;display:inline;"><div class="jquery_calendar_icon" onclick="$(\'#'+id+'\').calendar(\'open\');"></div></div>');
		$(obj).after($(div));
		$(obj).after($(input));

		// Hide calendar controllers
		$(obj).css('display','none');
		$(div).css('display','none');

		// Month and year options
		var modal = document.createElement('div');
		$(modal).attr('class', 'jquery_calendar_container');

		var calendar = document.createElement('table');
		$(calendar).attr('cellpadding', '0');
		$(calendar).attr('cellspacing', '0');
		$(modal).append(calendar);

		var calendar_header = document.createElement('thead');
		$(calendar).append(calendar_header);

		var calendar_content = document.createElement('tbody');
		$(calendar).append(calendar_content);

		// Calendar
		var data = new Date();
		month = parseInt(data.getMonth()) + 1;

		// Month and year html
		var html = '';
		html += '<tr align="center"><td></td><td align="right" onclick="$(\'#'+id+'\').calendar(\'prev\')" class="jquery_calendar_command"><div class="jquery_calendar_icon_left"></div></td><td colspan="3"><input type="hidden" class="jquery_calendar_day" value="'+data.getDate()+'"> <span class="jquery_calendar_month_label" onclick="$(this).parents(\'.jquery_calendar\').calendar(\'months\')">' + months[ data.getMonth() ] +'</span><input type="hidden" class="jquery_calendar_month" value="' + month +'"> <span class="jquery_calendar_year_label" onclick="$(this).parents(\'.jquery_calendar\').calendar(\'years\')">'+data.getFullYear()+'</span> <input type="hidden" class="jquery_calendar_year" value="'+data.getFullYear()+'"></td><td align="left" onclick="$(\'#'+id+'\').calendar(\'next\')" class="jquery_calendar_command"><div class="jquery_calendar_icon_right"></div></td><td><span class="jquery_calendar_header_close" onclick="$(\'#'+id+'\').calendar(\'close\', 0);">x</span></td></tr>';
		$(calendar_header).html(html);

		// Create calendar table picker
		$(div).calendar('days');
		$(div).html("");
		$(div).append($(modal));

		// Update labels
		if ($(obj).val()) $(obj).calendar('label');
	},

	/**
	 * Go to the previous month
	 * @return void
	 */

	prev : function () {
		// Object id
		var id = $(this).attr('id');

		// Loading month labels
		var months = $.fn.calendar.options[id].months;

		// Find the calendar table
		var table = $(this).next().next();

		// Check if the visualization is the days picker or years picker
		if ($(table).find('.jquery_calendar_years').length > 0) {
			var year = $(table).find(".jquery_calendar_year");
			$(year).val($(year).val() - 12);

			// Update labels in the calendar table headers
			$(table).find(".jquery_calendar_year_label").html($(year).val());

			// Update picker table of days
			$(table).calendar('years');
		} else {
			// Get the current values from table
			var month = $(table).find(".jquery_calendar_month");
			var year = $(table).find(".jquery_calendar_year");

			// Go to the previous month
			if ($(month).val() < 2) {
				$(month).val(12);
				$(year).val($(year).val() - 1);
			} else {
				$(month).val($(month).val() - 1);
			}

			// Update labels in the calendar table headers
			$(table).find(".jquery_calendar_month_label").html(months[$(month).val()-1]);
			$(table).find(".jquery_calendar_year_label").html($(year).val());

			// Update picker table of days
			$(table).calendar('days');
		}
	},

	/**
	 * Go to the next month
	 * @return void
	 */

	next : function () {
		// Object id
		var id = $(this).attr('id');

		// Loading month labels
		var months = $.fn.calendar.options[id].months;

		// Find the calendar table
		var table = $(this).next().next();

		// Check if the visualization is the days picker or years picker
		if ($(table).find('.jquery_calendar_years').length > 0) {
			var year = $(table).find(".jquery_calendar_year");
			$(year).val(parseInt($(year).val()) + 12);

			// Update labels in the calendar table headers
			$(table).find(".jquery_calendar_year_label").html($(year).val());

			// Update picker table of days
			$(table).calendar('years');
		} else {
			// Get the current values from table
			var month = $(table).find(".jquery_calendar_month");
			var year = $(table).find(".jquery_calendar_year");

			// Go to the next month
			if ($(month).val() > 11) {
				$(month).val(1);
				$(year).val(parseInt($(year).val()) + 1);
			} else {
				$(month).val(parseInt($(month).val()) + 1);
			}

			// Update labels in the calendar table headers
			$(table).find(".jquery_calendar_month_label").html(months[$(month).val()-1]);
			$(table).find(".jquery_calendar_year_label").html($(year).val());

			// Update picker table of days
			$(table).calendar('days');
		}
	},

	/**
	 * Set the value
	 * @return void
	 */

	set : function( val ) {
		$(this).val(val);
		$(this).calendar('label');
	},

	/**
	 * Set the label in the user friendly format
	 * @return void
	 */

	label : function( ) {
		// Object id
		var id = $(this).attr('id');

		// Loading options
		var format = $.fn.calendar.options[id].format;

		value = '';
		if ($(this).val()) {
			d = $(this).val();
			d = d.split(' ');

			var m = '';
			var h = '';

			if (d[1]) {
				h = d[1].split(':');
				m = h[1];
				h = h[0];
			}

			d = d[0].split('-');

			var calendar = new Date(d[0], d[1]-1, d[2]);
			var weekday = new Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

			value = format;
			value = value.replace('WD', weekday[calendar.getDay()]);
			value = value.replace('DD', d[2]);
			value = value.replace('MM', d[1]);
			value = value.replace('YYYY', d[0]);
			//value = value.replace('YY', d[2].substring(2,4));

			if (h) {
				value = value.replace('HH24', h);
			}

			if ($(this).find(".calendar_hour").val() > 12) {
				value = value.replace('HH12', h - 12);
			} else {
				value = value.replace('HH12', h);
			}
	
			value = value.replace('MI', m);
			value = value.replace('SS', 0);
		}

		$(this).next().val(value);
		//$(this).next().focus();
	},

	/**
	 * Open the calendar picker
	 * @return void
	 */

	open : function ( ) {
		// Object id
		var id = $(this).attr('id');

		// Loading options
		var format = $.fn.calendar.options[id].format;

		// Get main input object
		var value = $(this).val();

		// Get calendar table
		var table = $(this).next().next();
		$(table).css("display", "");

		// Setting values in the table based on the current date in the main object

		if (value) {
			value = value.split(' ');
			v1 = value[0].split('-');
			v2 = value[1].split(':');

			v1[0] = parseInt(v1[0]);
			v1[1] = parseInt(v1[1]);
			v1[2] = parseInt(v1[2]);

			$(table).find('.jquery_calendar_day').val(v1[2]);
			$(table).find('.jquery_calendar_month').val(v1[1]);
			$(table).find('.jquery_calendar_year').val(v1[0]);

			if (value[1]) v2 = value[1].split(':');

			if (v2[0] != '') $(table).find('.jquery_calendar_hour').val(v2[0]);
			if (v2[1] != '') $(table).find('.jquery_calendar_min').val(v2[1]);

			// Update datepicker headers
			var months = $.fn.calendar.options[id].months;
			$(table).find('.jquery_calendar_month_label').html(months[v1[1]-1]);
			$(table).find('.jquery_calendar_year_label').html(v1[0]);
		}

		$(table).calendar('days');
	},

	/**
	 * Open the calendar picker
	 * @param udpate {boolean} update the input values and label based on the new calendar picker
	 * @return void
	 */

	close : function ( update ) {
		// Object id
		var id = $(this).attr('id');

		// Loading options
		var format = $.fn.calendar.options[id].format;

		// Loading clock option
		var clock = $.fn.calendar.options[id].clock;

		// Update the date string in the object
		if (update == 1) {
			var calendar = $(this).next().next(); 
			var d = $(calendar).find(".jquery_calendar_day").val();
			var m = $(calendar).find(".jquery_calendar_month").val();
			var y = $(calendar).find(".jquery_calendar_year").val();

			if (m.length == 1) m = '0' + m;
			if (d.length == 1) d = '0' + d;

			if (clock) {
				var h = $(calendar).find(".jquery_calendar_hour").val();
				var i = $(calendar).find(".jquery_calendar_min").val();
				if (h.length == 1) h = '0' + h;
				if (i.length == 1) i = '0' + i;
			} else {
				h = '00';
				i = '00';
			}

			// Update the input object
			$(this).val(y + '-' + m + '-' + d + ' ' + h + ':' + i + ':00');

			// Update the label to the user
			$(this).calendar('label');

			// Hid calendar
			$(calendar).css('display','none');
		}

		// Hide the calendar table
		var div = $(this).next().next();
		$(div).css('display', 'none');
	},

	/**
	 * Reset calendar data in the element
	 * @return void
	 */

	reset : function( ) {

		$(this).val('');
		$(this).next().val('');
		$(this).next().next().css('display','none');
	},

	/**
	 * Update calendar configuration
	 * @param {Object} obj : object selected in the selector
	 * @param {Object} options : options
	 * @return void
	 */

	config : function( options ) {
		// Object id
		var id = $(this).attr('id');

		// Default options
		// format: calendar format
		// readonly: input text is a readonly [0/1]
		// clock: show the hour and minutes picker [0/1]
		// months: array of string so can be translated
		// weekdays: array of string so can be translated

		var defaults = {	format:'DD/MM/YYYY HH24:MI',
							readonly:1,
							today:0,
							clock:1,
							clear:1,
							months:['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
							weekdays:['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
							weekdays_short:['S', 'M', 'T', 'W', 'T', 'F', 'S']
						};

		// Save the options binded to the correct element
		var options =  $.extend(defaults, options);

		// Make sure use upper case in the format
		options.format = options.format.toUpperCase();

		// Save options
		if (id && $.fn.calendar.options[id]) {
			$.fn.calendar.options[id] = options;
		}
	},

	/**
	 * Internal method to assembly the HTML day picker interface
	 * @return void
	 */

	days : function( ) {

		// Object id
		var id = $(this).prev().prev().attr('id');

		// Clear
		var clear = $.fn.calendar.options[id].clear;

		// Loading month labels
		var weekdays = $.fn.calendar.options[id].weekdays_short;

		// Loading clock option
		var clock = $.fn.calendar.options[id].clock;

		// Variables
		var i = 0;
		var d = 0;
		var calendar;
		var calendar_day;
		var calendar_day_style;
		var calendar_click;
		var today = 0;
		var today_d = 0;

		// Loading calendar current date
		var day = $(this).find(".jquery_calendar_day").val();
		var month = $(this).find(".jquery_calendar_month").val();
		var year = $(this).find(".jquery_calendar_year").val();
		var hour = $(this).find(".jquery_calendar_hour").val();
		var min = $(this).find(".jquery_calendar_min").val();

		// Setting current values in case of NULLs
		calendar = new Date();
		if (!year) year = calendar.getFullYear();
		if (!month) month = parseInt(calendar.getMonth()) + 1;
		if (!hour) hour = calendar.getHours();
		if (!min) min = calendar.getMinutes();

		// Flag if this is the current month and year
		if ((calendar.getMonth() == month-1) && (calendar.getFullYear() == year)) {
			today = 1;
			today_d = calendar.getDate();
		}

		calendar = new Date(year, month, 0, 0, 0);
		nd = calendar.getDate();

		calendar = new Date(year, month-1, 0, hour, min);
		fd = calendar.getDay() + 1;

		// HTML elements for hour and minutes
		var hora = '';
		var horas = '';
		var hour_selected = '';

		for (i = 0; i < 24; i++) {
			hour_selected = '';
			if (i == parseInt(calendar.getHours())) hour_selected = ' selected';
			hora = '' + i;
			if (hora.length == 1) hora = '0' + hora;
			horas += '<option value="'+hora+'" ' + hour_selected + '>' + hora + '</option>';
		}

		var min = '';
		var mins = '';

		for (i = 0; i < 60; i++) {
			min_selected = '';
			if (i == parseInt(calendar.getMinutes())) min_selected = ' selected';
			min = '' + i;
			if (min.length == 1) min = '0' + min;
			mins += '<option value="'+min+'" ' + min_selected + '>' + min + '</option>';
		}

		// HTML headers
		var calendar_table = '<tr align="center" id="jquery_calendar_weekdays">';

		for (i = 0; i < 7; i++) {
			calendar_table += '<td width="30">'+weekdays[i]+'</td>';
		}

		calendar_table += '</tr><tr align="center">';

		// Avoid a blank line
		if (fd == 7) {
			var j = 7;
		} else {
			var j = 0;
		}

		// Mouting the table
		for (i = j; i < (Math.ceil((nd + fd) / 7) * 7); i++) {
			if ((i >= fd) && (i < nd + fd)) {
				d += 1;
			} else {
				d = 0;
			}

			calendar_day_style = '';

			if (d == 0) {
				calendar_day = '';
				calendar_click = '';
			} else {
				calendar_day = d;
				if (d < 10) {
					calendar_day = '0' + d;
				}

				if (clock == 1) {
					calendar_click = ' onclick="var obj = $(this).parents(\'.jquery_calendar\'); $(obj).find(\'.jquery_calendar_day\').val('+calendar_day+'); $(obj).calendar(\'days\');"';
				} else {
					calendar_click = ' onclick="var obj = $(this).parents(\'.jquery_calendar\'); $(obj).find(\'.jquery_calendar_day\').val('+calendar_day+'); $(obj).calendar(\'days\'); $(obj).prev().prev().calendar(\'close\', 1);"';
				}

				calendar_day = d;

				// Sundays
				if (!(i % 7)) {
					calendar_day_style += 'color:red;'
				}

				// Today
				if ((today == 1) && (today_d == d)) {
					calendar_day_style += 'font-weight:bold;';
				}

				// Selected day
				if (calendar_day == day) {
					calendar_day_style+= 'background-color:#eee;';
				}
			}

			if ((i > 0) && (!(i % 7))) calendar_table += '</tr><tr align="center">';
			calendar_table += '<td style="'+calendar_day_style+'" ' + calendar_click + '>'+calendar_day+'</td>';
		}

		// Table footer
		calendar_table += '<tr><td colspan="7" style="padding:6px;"> ';

		// Showing the timepicker
		if (clock == 1) {
			calendar_table += '<select class="jquery_calendar_hour">'+horas+'</select> <select class="jquery_calendar_min">'+mins+'</select> ';
		} else {
			calendar_table += '<select class="jquery_calendar_hour" style="display:none">'+horas+'</select> <select class="jquery_calendar_min" style="display:none">'+mins+'</select> ';
		}

		// Button OK
		calendar_table += '<div class="jquery_calendar_ok" onclick="$(this).parents(\'.jquery_calendar\').prev().prev().calendar(\'close\', 1);">Ok</div>';

		// Show clear button
		if (clear) {
			calendar_table += '<div onclick="$(this).parents(\'.jquery_calendar\').prev().prev().calendar(\'reset\');" style="float:right;padding:5px;">clear</div>';
		}

		// Close table
		calendar_table += '</td></tr>';

		// Appeding HTML to the element table
		$(this).find('tbody').html(calendar_table);
	},

	/**
	 * Internal method to assembly the HTML of month picker interface
	 * @return void
	 */

	months : function( ) { 
		// Object id
		var id = $(this).prev().prev().attr('id');

		// Loading month labels
		var months = $.fn.calendar.options[id].months;

		var calendar_table = '<td colspan="7"><table class="jquery_calendar_months" width="100%"><tr align="center">';

		for (i = 0; i < 12; i++) {
			if ((i > 0) && (!(i % 3))) calendar_table += '</tr><tr align="center">';
			month = parseInt(i) + 1;
			calendar_table += '<td onclick="var obj = $(this).parents(\'.jquery_calendar\'); $(obj).find(\'.jquery_calendar_month_label\').html(\''+months[i]+'\'); $(obj).find(\'.jquery_calendar_month\').val('+month+'); $(obj).calendar(\'days\')">'+ months[i] +'</td>';
		}

		calendar_table += '</tr></table></td>';

		$(this).find('tbody').html(calendar_table);
	},

	/**
	 * Internal method to assembly the HTML of year picker interface
	 * @return void
	 */

	years : function( ) { 

		// Get current selected year
		var year = $(this).find('.jquery_calendar_year').val();

		// Array of years
		var y = [];
		for (i = 0; i < 25; i++) {
			y[i] = parseInt(year) + (i - 12);
		}

		// Bold the current selected year
		y[12] = '<b>' + y[12] + '</b>';

		// Assembling the year tables
		var calendar_table = '<td colspan="7"><table class="jquery_calendar_years" width="100%"><tr align="center">';

		for (i = 0; i < 25; i++) {
			if ((i > 0) && (!(i % 5))) calendar_table += '</tr><tr align="center">';
			calendar_table += '<td onclick="var obj = $(this).parents(\'.jquery_calendar\'); $(obj).find(\'.jquery_calendar_year_label\').html(\''+y[i]+'\'); $(obj).find(\'.jquery_calendar_year\').val('+y[i]+'); $(obj).calendar(\'days\')">'+ y[i] +'</td>';
		}

		calendar_table += '</tr></table></td>';

		$(this).find('tbody').html(calendar_table);
	}
};

$.fn.calendar = function( method ) {
	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}  
};

})( jQuery );

$.fn.calendar.options = new Array();