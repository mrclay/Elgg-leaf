<?php
/**
 * Memcache wrapper class.
 *
 * @package    Elgg.Core
 * @subpackage Memcache
 */
class ElggMemcache extends ElggSharedMemoryCache {
	/**
	 * Minimum version of memcached needed to run
	 */
	private static $minimumServerVersion = '1.1.12';

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

		$result = self::getStorageInstance()->set($key, $data, null, $expires);
		if ($result === false) {
			elgg_log("MEMCACHE: FAILED TO SAVE $key", 'ERROR');
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

		$result = self::getStorageInstance()->get($key);

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

		$result = self::getStorageInstance()->delete($key, 0);
		if ($result === false) {
			elgg_log("MEMCACHE: FAILED TO DELETE $key", 'ERROR');
		}
		return $result;
	}

	/**
	 * Currently does nothing. When implemented, will clear the entire cache
	 *
	 * @todo write or remove.
	 *
	 * @return bool
	 */
	public function clear() {
		// DISABLE clearing for now - you must use delete on a specific key.
		return true;

		// @todo Namespaces as in #532
	}

	/**
	 * @static
	 * @return Memcache|bool
	 * @throws ConfigurationException
	 */
	static protected function getStorageInstance() {
		global $CONFIG;
		static $instance = null;

		if (null === $instance) {
			// Do we have memcache?
			if (!class_exists('Memcache')) {
				$instance = false;
				throw new ConfigurationException(elgg_echo('memcache:notinstalled'));
			}

			// Create memcache object
			$instance = new Memcache;

			// Now add servers
			if (!$CONFIG->memcache_servers) {
				$instance = false;
				throw new ConfigurationException(elgg_echo('memcache:noservers'));
			}

			if (is_callable(array($instance, 'addServer'))) {
				foreach ($CONFIG->memcache_servers as $server) {
					if (is_array($server)) {
						$instance->addServer(
							$server[0],
							isset($server[1]) ? $server[1] : 11211,
							isset($server[2]) ? $server[2] : FALSE,
							isset($server[3]) ? $server[3] : 1,
							isset($server[4]) ? $server[4] : 1,
							isset($server[5]) ? $server[5] : 15,
							isset($server[6]) ? $server[6] : TRUE
						);

					} else {
						$instance->addServer($server, 11211);
					}
				}
			} else {
				// don't use elgg_echo() here because most of the config hasn't been loaded yet
				// and it caches the language, which is hard coded in $CONFIG->language as en.
				// overriding it with real values later has no effect because it's already cached.
				elgg_log("This version of the PHP memcache API doesn't support multiple servers.", 'ERROR');

				$server = $CONFIG->memcache_servers[0];
				if (is_array($server)) {
					$instance->connect($server[0], $server[1]);
				} else {
					$instance->addServer($server, 11211);
				}
			}

			// Get version
			$version = $instance->getVersion();
			if (version_compare($version, ElggMemcache::$minimumServerVersion, '<')) {
				$msg = elgg_echo('memcache:versiontoolow',
					array(ElggMemcache::$minimumServerVersion,
						$version
					));
				$instance = false;
				throw new ConfigurationException($msg);
			}
		}
		return $instance;
	}

	/**
	 * @static
	 * @param string $namespace
	 * @return ElggMemcache|null
	 */
	static public function getInstance($namespace) {
		global $CONFIG;
		static $caches = array();
		static $available = null;

		// check availability
		if ($available === null) {
			// is available?
			if (empty($CONFIG->memcache)) {
				$available = false;
			} else {
				// try getting instance
				try {
					ElggMemcache::getStorageInstance();
					// No exception thrown so we have memcache available
					$available = true;
				} catch (Exception $e) {
					$available = false;
				}
			}
		}
		if (! $available) {
			return null;
		}

		// manage one instance for each namespaces
		if (! array_key_exists($namespace, $caches)) {
			try {
				$caches[$namespace] = new self($namespace);
			} catch (Exception $e) {
				$caches[$namespace] = false;
			}
		}
		return $caches[$namespace];
	}
}
