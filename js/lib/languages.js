/*globals vsprintf*/
/**
 * Provides language-related functionality
 */
elgg.provide('elgg.config.translations');

// default language - required by unit tests
elgg.config.language = 'en';

/**
 * Analagous to the php version.  Merges translations for a
 * given language into the current translations map.
 */
elgg.add_translation = function(lang, translations) {
	elgg.provide('elgg.config.translations.' + lang);

	elgg.extend(elgg.config.translations[lang], translations);
};

/**
 * Get the current language
 * @return {String}
 */
elgg.get_language = function() {
	var user = elgg.get_logged_in_user_entity();

	if (user && user.language) {
		return user.language;
	}

	return elgg.config.language;
};

!function() {
	var REFERENCE_PREFIX = '__REF ';

	var keys_stack = [];


	/**
	 * Translates a string
	 *
	 * @param {String} key      Message key
	 * @param {Array}  argv     vsprintf() arguments
	 * @param {String} language Desired language
	 *
	 * @return {String} The translation or the given key if no translation available
	 */
	elgg.echo = function(key, argv, language) {

		// handle elgg.echo('str', 'en')
		if (elgg.isString(argv)) {
			language = argv;
			argv = [];
		} else {
			argv = argv || [];
		}

		var requested_lang = language;

		language = language || elgg.get_language();

		var translations = elgg.config.translations,
			list = [language, elgg.get_language()],
			lang,
			str;

		while (lang = list.shift()) {
			if (translations[lang] && elgg.isString(translations[lang][key])) {
				str = translations[lang][key];
				if (0 == str.indexOf(REFERENCE_PREFIX)) {
					// dereference, first checking for circular references
					if ($.inArray(key, keys_stack) != -1) {
						return key;
					}

					keys_stack.push(key);
					var ret = elgg.echo(str.substring(REFERENCE_PREFIX.length), argv, requested_lang);
					keys_stack.pop();

					return ret;
				}

				if (argv.length) {
					str = vsprintf(str, argv);
				}

				return str;
			}
		}

		return key;
	};
}();
