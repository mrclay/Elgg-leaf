define(function (require) {

	var elgg = require('elgg');
	var $ = require('jquery');
	require('jquery-ui');

	// the language module may need loading
	var i18n_ready = $.Deferred();
	if (elgg.get_language() === 'en') {
		i18n_ready.resolve();
	} else {
		require(['jquery-ui/i18n/datepicker-' + elgg.get_language() + '.min'], function () {
			i18n_ready.resolve();
		}, function () {
			// if load fails (e.g. lang code mismatch), carry on with English
			i18n_ready.resolve();
		});
	}

	var datepicker = {
		/**
		 * Initialize the date picker
		 *
		 * Uses the class .elgg-input-date as the selector.
		 *
		 * If the class .elgg-input-timestamp is set on the input element, the onSelect
		 * method converts the date text to a unix timestamp in seconds. That value is
		 * stored in a hidden element indicated by the id on the input field.
		 *
		 * Note that the UNIX timestamp is normalized to start of the day at UTC,
		 * so you may need to use timezone offsets if you expect a different timezone.
		 * 
		 * @param {string} selector Element selector
		 * @return void
		 * @requires jqueryui.datepicker
		 */
		init: function (selector) {
			selector = selector || '.elgg-input-date';
			var $elem = $(selector);
			if (!$elem.length) {
				return;
			}
			var defaults = {
				dateFormat: 'yy-mm-dd',
				nextText: '&#xBB;',
				prevText: '&#xAB;',
				changeMonth: true,
				changeYear: true
			};

			var opts = $elem.data('datepickerOpts') || {};
			opts = $.extend({}, defaults, opts);

			opts.onSelect = function (dateText, instance) {
				if ($(this).is('.elgg-input-timestamp')) {
					// convert to unix timestamp
					var timestamp = Date.UTC(instance.selectedYear, instance.selectedMonth, instance.selectedDay);
					timestamp = timestamp / 1000;

					$('input[rel="' + this.id + '"]').val(timestamp);
				}
			};

			// defer until language loaded
			i18n_ready.then(function () {
				$elem.datepicker(opts);
			});
		}
	};

	return datepicker;
});
