define(function (require) {
	require('elgg/dev');
	var $ = require('jquery');
	var spinner = require('elgg/spinner');
	var lightbox = require('elgg/lightbox');

	$('.developers-gear .elgg-icon-settings-alt')
		.prop('hidden', false)
		.on('click', function () {
			lightbox.open({
				href: $(this).data('href'),
				initialWidth: '90%',
				maxWidth: false,
				width: '90%',
				speed: 0,
				onComplete: function () {
					$('#developer-settings-form')
						.on('submit', spinner.start)
						.find('fieldset > div')
						.each(function () {
							var $help = $('span.elgg-text-help', this);
							var $label = $('label', this);

							if ($help.length != 1 || $label.length != 1) {
								return;
							}

							var $icon = $('<span class="elgg-icon-info elgg-icon" />');
							var $both = $([$icon[0], $help[0]])
								.appendTo($label)
								.on('click', function () {
									$both.toggle();
									$.colorbox.resize();
									return false;
								});
						});
					lightbox.resize();
				}
			});
		});

	$(document).on('click', '.developers-gear-popup a', function() {
		if ($(this).is('.elgg-menu-parent')) {
			return false;
		}
		spinner.start();
	});
});

