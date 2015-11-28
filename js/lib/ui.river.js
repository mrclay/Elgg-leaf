elgg.provide('elgg.ui.river');

elgg.ui.river.init = function() {
	$('#elgg-river-selector').change(function() {
		var url = window.location.href;
		if (window.location.search.length) {
			url = url.substring(0, url.indexOf('?'));
		}
		url += '?' + $(this).val();
		elgg.forward(url);
	});

	$(document).on('elgg.ui.toggle', function (e, data) {
		if (data.$target.is('.elgg-river-responses > .elgg-form-comment-save')) {
			if (data.$toggler.hasClass('elgg-state-active')) {
				data.$target.find('.elgg-input-text').focus();
			} else {
				data.$toggler.blur();
			}
		}
	});
};

elgg.register_hook_handler('init', 'system', elgg.ui.river.init);