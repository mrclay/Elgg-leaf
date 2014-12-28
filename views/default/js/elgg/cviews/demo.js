define(function (require) {
	var cviews = require('elgg/cviews');

	var preview = cviews.factory('demo-preview'),
		river = cviews.factory('demo-river');

	cviews.paginate(river, function (offset) {
		$(preview.getSelector()).html('');
	});

	$(river.getSelector()).on('click', '.elgg-list-river > li', function () {
		var id = this.id.match(/item-river-(\d+)/)[1];

		preview.vars.river_id = id;

		cviews.update(preview);

		return false;
	});
});
