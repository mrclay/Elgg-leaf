<?php
namespace Elgg;

/**
 * @access private
 */
final class Includer {

	/**
	 * Include a file with as little context as possible
	 *
	 * @param string $file File to include
	 * @return mixed
	 */
	static public function includeFile($file) {
		return (include $file);
	}

	/**
	 * Require a file with as little context as possible
	 *
	 * @param string $file File to require
	 * @return mixed
	 */
	static public function requireFile($file) {
		return (require $file);
	}
}
