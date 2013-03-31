<?php
/**
 * ElggStaticVariableCache
 * Dummy cache which stores values in a static array. Using this makes future
 * replacements to other caching back ends (eg memcache) much easier.
 *
 * @package    Elgg.Core
 * @subpackage Cache
 */
class ElggStaticVariableCache extends ElggSharedMemoryCache {
	/**
	 * The cache.
	 *
	 * @var array
	 */
	private static $__cache;

	/**
	 * Maximum size of cache
	 * 
	 * @var int
	 */
	private $max_size = 0;

	/**
	 * Create the variable cache.
	 *
	 * This function creates a variable cache in a static variable in
	 * memory, optionally with a given namespace (to avoid overlap).
	 *
	 * @param string $namespace The namespace for this cache to write to.
	 * @param int    $max_size maximum size of cache. If given, the least recently loaded key will be 
	 *                         deleted to keep the size under the limit.
	 * @warning namespaces of the same name are shared!
	 */
	function __construct($namespace = 'default', $max_size = 0) {
		$this->setNamespace($namespace);
		$this->clear();
		$this->max_size = (int)$max_size;
	}

	/**
	 * Save a key
	 *
	 * @param string $key  Name
	 * @param string $data Value
	 *
	 * @return boolean
	 */
	public function save($key, $data) {
		$namespace = $this->getNamespace();

		ElggStaticVariableCache::$__cache[$namespace][$key] = $data;
		
		if ($this->max_size && count(ElggStaticVariableCache::$__cache[$namespace]) > $this->max_size) {
			// remove least recently used key
			reset(ElggStaticVariableCache::$__cache[$namespace]);
			$least_recently_used_key = key(ElggStaticVariableCache::$__cache[$namespace]);
			unset(ElggStaticVariableCache::$__cache[$namespace][$least_recently_used_key]);
		}

		return true;
	}

	/**
	 * Load a key
	 *
	 * @param string $key    Name
	 * @param int    $offset Offset
	 * @param int    $limit  Limit
	 *
	 * @return string
	 */
	public function load($key, $offset = 0, $limit = null) {
		$namespace = $this->getNamespace();

		if (isset(ElggStaticVariableCache::$__cache[$namespace][$key])) {
			$value = ElggStaticVariableCache::$__cache[$namespace][$key];
			
			if ($this->max_size) {
				// move this key to the end of the array (most recently used)
				unset(ElggStaticVariableCache::$__cache[$namespace][$key]);
				ElggStaticVariableCache::$__cache[$namespace][$key] = $value;
			}
			
			return $value;
		}

		return false;
	}

	/**
	 * Invalidate a given key.
	 *
	 * @param string $key Name
	 *
	 * @return bool
	 */
	public function delete($key) {
		$namespace = $this->getNamespace();

		unset(ElggStaticVariableCache::$__cache[$namespace][$key]);

		return true;
	}

	/**
	 * Clears the cache for a particular namespace
	 *
	 * @return void
	 */
	public function clear() {
		$namespace = $this->getNamespace();

		if (!isset(ElggStaticVariableCache::$__cache)) {
			ElggStaticVariableCache::$__cache = array();
		}

		ElggStaticVariableCache::$__cache[$namespace] = array();
	}
}
