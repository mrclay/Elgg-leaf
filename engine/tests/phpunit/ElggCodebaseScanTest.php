<?php

class ElggCodebaseScanTest extends PHPUnit_Framework_TestCase {
	function testCodeLooksClean() {

		if (!empty($_ENV['TRAVIS']) && empty($_ENV['VARIA'])) {
			$this->markTestSkipped("Only tested on VARIA=true job in Travis-CI");
		}

		$root = dirname(dirname(dirname(__DIR__)));

		// Put classname in string to allow this file to compile in 5.2
		// @todo In 1.x replace with regular namespace
		$class = 'Elgg\Project\CodeStyle';
		$scanner = new $class();

		/* @var Elgg\Project\CodeStyle $scanner */
		$report = $scanner->fixDirectory($root, true);
		if (!$report) {
			return;
		}

		$json_opts = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
		$this->fail("Code style problems were found: " . json_encode($report, $json_opts));
	}
}
