//<script>

elgg.provide('elgg.bookmarks');

elgg.bookmarks.init = function() {
	// append the title to the url
	var title = document.title;
	var e = $('a.elgg-bookmark-page');
	var link = e.attr('href') + '&title=' + encodeURIComponent(title);
	e.attr('href', link);
};

require(['elgg/hooks/register'], function(register) {
	register('init', 'system', elgg.bookmarks.init);
});
