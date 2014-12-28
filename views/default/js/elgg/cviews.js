define(function (require) {
	var elgg = require('elgg'),
		$ = require('jquery');

	function Cview(view, vars, cvid, inline) {
		this.view = view;
		this.vars = vars;
		this.cvid = cvid;
		this.inline = inline;
		this.getSelector = function () {
			return '#elgg-cview-' + this.cvid;
		};
	}

	var exports = {
		factory: function (item, asArray) {
			if (asArray && !$.isArray(item)) {
				item = [item];
			}
			if ($.isArray(item)) {
				return $.map(item, exports.factory);
			}
			if (item instanceof Cview) {
				return item;
			}
			if (typeof item === 'string') {
				item = $('#elgg-cview-' + item);
			}
			if (item.nodeType) {
				item = $(item);
			}
			if (item instanceof $) {
				var data = item.data('elggCview');
				item = {
					view: data.view,
					vars: data.vars,
					cvid: data.cvid,
					inline: item.is('span')
				};
			}
			// assume an object
			return new Cview(item.view, item.vars, item.cvid, item.inline);
		},

		getSelector: function (items) {
			items = exports.factory(items, true);
			return $.map(items, function (cview) {
					return cview.getSelector();
				})
				.join(',');
		},

		/**
		 * Fetch the contents of a view(s)
		 *
		 * @param {*} cviews The cvid/Cview object or an array of them
		 * @returns {jQuery.Deferred} A deferred object that will be resolved when data is available
		 */
		fetch: function (cviews) {
			var $els = $(exports.getSelector(cviews));

			$els.addClass('elgg-cview-fetching');

			var deferred = elgg.ajax({
				url: 'cviews/fetch',
				type: 'POST',
				contentType: 'application/json; charset=UTF-8',
				data: JSON.stringify(cviews),
				dataType: 'json'
			});
			deferred.done(function () {
				$els.removeClass('elgg-cview-fetching');
			});

			return deferred;
		},

		/**
		 * Update the content of the given views from the server
		 *
		 * @param {*} cviews The cvid/Cview object or an array of them
		 * @returns {jQuery.Deferred} A deferred object that will be resolved when the update is complete
		 */
		update: function (cviews) {
			var deferred = $.Deferred();

			cviews = exports.factory(cviews, true);

			$(document).trigger('cviews-prefetch', {cviews: cviews});

			exports.fetch(cviews).done(function (data) {
				$(document).trigger('cviews-postfetch', {cviews: cviews, data: data});

				$.each(cviews, function (i, cview) {
					$(cview.getSelector()).html(data[cview.cvid]);
				});

				$(document).trigger('cviews-updated', {cviews: cviews, data: data});

				deferred.resolve(data);
			}).fail(function () {
				deferred.reject();
			});

			return deferred;
		},

		/**
		 * Wire a listing view to be ajax-updated via elgg-pagination links.
		 *
		 * @param {*}        cview    The cvid/Cview object
		 * @param {function} callback If provided, the offset will be passed to it whenever the listing changes
		 */
		paginate: function (cview, callback) {
			cview = exports.factory(cview);
			$(document).on('click', cview.getSelector() + ' .elgg-pagination a', function () {
				var m = this.href.match(/\boffset=(\d+)/),
					offset = m ? m[1] : 0;
				cview.vars.offset = offset;

				exports.update(cview).done(function () {
					// allow pulling in vars changes from fetched HTML. otherwise we'll stick to the
					// closure
					cview = exports.factory(cview.cvid);

					if (callback) {
						callback(offset);
					}
				});

				return false;
			});
		}
	};

	return exports;
});
