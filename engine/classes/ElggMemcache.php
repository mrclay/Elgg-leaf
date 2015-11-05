<?php
/**
 * Memcache wrapper class.
 *
 * @package    Elgg.Core
 * @subpackage Memcache
 */
class ElggMemcache extends \ElggSharedMemoryCache {

	/**
	 * Expiry of saved items (default timeout after a day to prevent anything getting too stale)
	 */
	private $expires = 86400;

	/**
	 * Connect to memcache.
	 *
	 * @param string $namespace The namespace for this cache to write to -
	 * note, namespaces of the same name are shared!
	 *
	 * @throws ConfigurationException
	 */
	public function __construct($namespace = 'default') {
		global $CONFIG;

		$this->setNamespace($namespace);

		// Do we have memcache?
		if (!function_exists('apc_store')) {
			throw new \ConfigurationException('No apc_store');
		}

		// Set some defaults
		if (isset($CONFIG->memcache_expires)) {
			$this->expires = $CONFIG->memcache_expires;
		}
	}

	/**
	 * Set the default expiry.
	 *
	 * @param int $expires The lifetime as a unix timestamp or time from now. Defaults forever.
	 *
	 * @return void
	 */
	public function setDefaultExpiry($expires = 0) {
		$this->expires = $expires;
	}

	/**
	 * Combine a key with the namespace.
	 * Memcache can only accept <250 char key. If the given key is too long it is shortened.
	 *
	 * @param string $key The key
	 *
	 * @return string The new key.
	 */
	private function makeMemcacheKey($key) {
		$prefix = $this->getNamespace() . ":";

		if (strlen($prefix . $key) > 250) {
			$key = md5($key);
		}

		return $prefix . $key;
	}

	/**
	 * Saves a name and value to the cache
	 *
	 * @param string  $key     Name
	 * @param string  $data    Value
	 * @param integer $expires Expires (in seconds)
	 *
	 * @return bool
	 */
	public function save($key, $data, $expires = null) {
		$key = $this->makeMemcacheKey($key);

		if ($expires === null) {
			$expires = $this->expires;
		}

		$result = apc_store($key, $data, $expires);
		if ($result === false) {
			_elgg_services()->logger->error("MEMCACHE: SAVE FAIL $key");
		} else {
			_elgg_services()->logger->info("MEMCACHE: SAVE SUCCESS $key");
		}

		return $result;
	}

	/**
	 * Retrieves data.
	 *
	 * @param string $key    Name of data to retrieve
	 * @param int    $offset Offset
	 * @param int    $limit  Limit
	 *
	 * @return mixed
	 */
	public function load($key, $offset = 0, $limit = null) {
		$key = $this->makeMemcacheKey($key);

		$result = apc_fetch($key, $success);
		if (!$success) {
			_elgg_services()->logger->info("MEMCACHE: LOAD MISS $key");
		} else {
			_elgg_services()->logger->info("MEMCACHE: LOAD HIT $key");
		}

		return $result;
	}

	/**
	 * Delete data
	 *
	 * @param string $key Name of data
	 *
	 * @return bool
	 */
	public function delete($key) {
		$key = $this->makeMemcacheKey($key);

		apc_delete($key);
	}

	/**
	 * Clears the entire cache?
	 *
	 * @todo write or remove.
	 *
	 * @return true
	 */
	public function clear() {
		// DISABLE clearing for now - you must use delete on a specific key.
		return true;

		// @todo Namespaces as in #532
	}
}
