define(function (require) {
	require('elgg/dev');
	var $ = require('jquery');
	var elgg = require('elgg');
	var spinner = require('elgg/spinner');

	$('.developers-gear .elgg-icon-refresh')
		.prop('hidden', false)
		.on('click', function () {
			spinner.start();
			location.href = elgg.security.addToken($(this).data('href'));
		});
});
