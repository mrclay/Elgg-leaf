<?php
return [
	// viewtype
	"default" => [
		/**
		 * view => path
		 *
		 * If the path begins with "/" or a drive letter like "C:", the path is considered absolute.
		 *
		 * Otherwise it's resolved relative to Elgg's install root. E.g. see these:
		 */
		"js/require.js" => "vendor/bower-asset/requirejs/require.js",
		"js/jquery.js" => "vendor/bower-asset/jquery/dist/jquery.min.js",
		"js/jquery.min.map" => "vendor/bower-asset/jquery/dist/jquery.min.map",
		"js/jquery-migrate.js" => "vendor/bower-asset/jquery-migrate/jquery-migrate.min.js",
		"js/sprintf.js" => "vendor/bower-asset/sprintf/dist/sprintf.min.js",
		"js/text.js" => "vendor/bower-asset/text/text.js",
		"js/jquery.jeditable.js" => "vendor/bower-asset/jquery-jeditable/jquery.jeditable.js",
		"js/jquery.imgareaselect.js" => "vendor/bower-asset/jquery-imgareaselect/jquery.imgareaselect.dev.js",
		"jquery.imgareaselect.css" => "vendor/bower-asset/jquery-imgareaselect/distfiles/css/imgareaselect-deprecated.css",
		"js/jquery.ui.autocomplete.html.js" => "vendor/bower-asset/jquery-ui-extensions/src/autocomplete/jquery.ui.autocomplete.html.js",
		"js/ui.river.js" => "js/lib/ui.river.js",
		"js/ui.avatar_cropper.js" => "js/lib/ui.avatar_cropper.js",
		"js/ui.friends_picker.js" => "js/lib/ui.friends_picker.js",
		"js/ui.autocomplete.js" => "js/lib/ui.autocomplete.js",
		"js/jquery.form.js" => "vendor/bower-asset/jquery-form/jquery.form.js",

		"lightbox.css" => "vendors/elgg-colorbox-theme/colorbox.css",
		"colorbox-images/border1.png" => "vendors/elgg-colorbox-theme/colorbox-images/border1.png",
		"colorbox-images/border2.png" => "vendors/elgg-colorbox-theme/colorbox-images/border2.png",
		"colorbox-images/loading.gif" => "vendors/elgg-colorbox-theme/colorbox-images/loading.gif",

		// Plugins may use __DIR__ to reference absolute paths. E.g.
		// "underscore.js" => __DIR__ . "/vendors/underscore.js",
	],
];
