<?php
namespace Elgg\Services;

/**
 * Describes an object that manages URLs
 */
interface Urls {

	/**
	 * Get path segments after the site path (e.g. /elgg/foo/bar might return ['foo', 'bar'])
	 *
	 * @return string[]
	 */
	public function getPathSegments();

	/**
	 * Get first path segment
	 *
	 * @see getPathSegments
	 *
	 * @return string empty string indicates home page
	 */
	public function getFirstPathSegment();
}
