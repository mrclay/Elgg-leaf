define(function(require) {
	
	var elgg = require('elgg');
	
	describe("elgg.i18n", function() {
	
		afterEach(function() {
			elgg.config.translations = {};
		});
		
		describe("elgg.echo", function() {
	
			it("translates the given string", function() {
				elgg.add_translation('en', {
					'hello': 'Hello!'
				});
				elgg.add_translation('es', {
					'hello': 'Hola!'
				});
				
				expect(elgg.echo('hello')).toBe('Hello!');
				expect(elgg.echo('hello', 'es')).toBe('Hola!');			
			});

			it("falls back to the default language", function() {
				elgg.add_translation('en', {
					'hello': 'Hello!'
				});

				expect(elgg.echo('hello', 'es')).toBe('Hello!');
			});

			it("returns the key on failure", function() {
				expect(elgg.echo('hello')).toBe('hello');
			});

			it("falls back even if an unrelated key exists in the requested language", function () {
				elgg.add_translation('en', {
					'hello': 'Hello!'
				});
				elgg.add_translation('es', {
					'goodbye': 'Adios!'
				});

				expect(elgg.echo('hello', 'es')).toBe('Hello!');
			});

			it("recognizes empty string as a valid translation", function () {
				elgg.add_translation('en', {
					'void': ''
				});

				expect(elgg.echo('void')).toBe('');
			});

			it("can follow references", function() {
				elgg.add_translation('en', {
					"dummy": "Dummy",
					"site:dummy": "__REF dummy",
					"group:dummy": "__REF site:dummy"
				});

				expect(elgg.echo('site:dummy')).toBe('Dummy');
				expect(elgg.echo('site:dummy', [], 'es')).toBe('Dummy');

				expect(elgg.echo('group:dummy')).toBe('Dummy');
				expect(elgg.echo('group:dummy', [], 'es')).toBe('Dummy');
			});
		});
	});
});