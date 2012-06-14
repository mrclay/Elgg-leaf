<?php
/**
 * Elgg memcache support.
 *
 * Requires php5-memcache to work.
 *
 * @package Elgg.Core
 * @subpackage Cache.Memcache
 */



/**
 * Return true if memcache is available and configured.
 *
 * @return bool
 *
 * @deprecated
 */
function is_memcache_available() {
	return (bool) ElggMemcache::getInstance('default');
}
