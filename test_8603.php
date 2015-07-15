<?php

set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';

$c = new ElggCrypto();

$tokens = [];

for ($i = 0; $i < 1000000; $i++) {
	$token = $c->getRandomBytes(10);

	if (isset($tokens[$token])) {
		die('duplicate!');
	}

	// to avoid OOM we only collect 100K strings to compare against
	if ($i < 100000) {
		$tokens[$token] = true;
	}

	if ($i % 1000 == 0) {
		echo "$i<br>";
		ob_flush();
		flush();
	}
}
echo "No duplicates.";
