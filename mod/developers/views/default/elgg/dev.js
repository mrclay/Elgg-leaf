define(function (require) {
	var $ = require('jquery');
	var gear_html = require('text!elgg/dev.html');

	$(gear_html).appendTo('body');
});
