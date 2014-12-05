<?php
namespace Elgg;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * Use the elgg_* versions instead.
 *
 * @todo 1.10 remove deprecated view injections
 * @todo inject/remove dependencies: $CONFIG, hooks, site_url
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Views
 * @since      1.9.0
 */
class ViewsService {

	public $config_wrapper;
	public $site_url_wrapper;
	public $user_wrapper;
	public $user_wrapped;

	/**
	 * @see \Elgg\ViewsService::fileExists
	 * @var array
	 */
	protected $file_exists_cache = array();

	/**
	 * Global Elgg configuration
	 * 
	 * @var \stdClass
	 */
	public $CONFIG;

	/**
	 * @var Logger
	 */
	public $logger;

	/**
	 * @var PluginHooksService
	 */
	public $hooks;

	/**
	 * Constructor
	 *
	 * @param \Elgg\PluginHooksService $hooks  The hooks service
	 * @param \Elgg\Logger             $logger Logger
	 */
	public function __construct(\Elgg\PluginHooksService $hooks, \Elgg\Logger $logger) {
		global $CONFIG;
		$this->CONFIG = $CONFIG;
		$this->hooks = $hooks;
		$this->logger = $logger;
	}

	/**
	 * Get the user object in a wrapper
	 * 
	 * @return \Elgg\DeprecationWrapper|null
	 */
	public function getUserWrapper() {
		$user = _elgg_services()->session->getLoggedInUser();
		if ($user) {
			if ($user !== $this->user_wrapped) {
				$warning = 'Use elgg_get_logged_in_user_entity() rather than assuming elgg_view() '
							. 'populates $vars["user"]';
				$this->user_wrapper = new \Elgg\DeprecationWrapper($user, $warning, 1.8);
			}
			$user = $this->user_wrapper;
		}
		return $user;
	}

	/**
	 * @access private
	 */
	public function autoregisterViews($view_base, $folder, $base_location_path, $viewtype) {
		$handle = opendir($folder);
		if ($handle) {
			while ($view = readdir($handle)) {
				if (!empty($view_base)) {
					$view_base_new = $view_base . "/";
				} else {
					$view_base_new = "";
				}

				if (substr($view, 0, 1) !== '.') {
					if (is_dir($folder . "/" . $view)) {
						$this->autoregisterViews($view_base_new . $view, $folder . "/" . $view,
							$base_location_path, $viewtype);
					} else {
						$this->setViewLocation($view_base_new . basename($view, '.php'),
							$base_location_path, $viewtype);
					}
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * @access private
	 */
	public function getViewLocation($view, $viewtype = '') {
		

		if (empty($viewtype)) {
			$viewtype = elgg_get_viewtype();
		}

		if (!isset($this->CONFIG->views->locations[$viewtype][$view])) {
			if (!isset($this->CONFIG->viewpath)) {
				return dirname(dirname(dirname(__FILE__))) . "/views/";
			} else {
				return $this->CONFIG->viewpath;
			}
		} else {
			return $this->CONFIG->views->locations[$viewtype][$view];
		}
	}

	/**
	 * @access private
	 */
	public function setViewLocation($view, $location, $viewtype = '') {
		

		if (empty($viewtype)) {
			$viewtype = 'default';
		}

		if (!isset($this->CONFIG->views)) {
			$this->CONFIG->views = new \stdClass;
		}

		if (!isset($this->CONFIG->views->locations)) {
			$this->CONFIG->views->locations = array($viewtype => array($view => $location));

		} else if (!isset($this->CONFIG->views->locations[$viewtype])) {
			$this->CONFIG->views->locations[$viewtype] = array($view => $location);

		} else {
			$this->CONFIG->views->locations[$viewtype][$view] = $location;
		}
	}

	/**
	 * @access private
	 */
	public function registerViewtypeFallback($viewtype) {
		

		if (!isset($this->CONFIG->viewtype)) {
			$this->CONFIG->viewtype = new \stdClass;
		}

		if (!isset($this->CONFIG->viewtype->fallback)) {
			$this->CONFIG->viewtype->fallback = array();
		}

		$this->CONFIG->viewtype->fallback[] = $viewtype;
	}

	public function doesViewtypeFallback($viewtype) {
		

		if (isset($this->CONFIG->viewtype) && isset($this->CONFIG->viewtype->fallback)) {
			return in_array($viewtype, $this->CONFIG->viewtype->fallback);
		}

		return false;
	}

	/**
	 * Display a view with a deprecation notice. No missing view NOTICE is logged
	 *
	 * @see elgg_view()
	 *
	 * @param string  $view       The name and location of the view to use
	 * @param array   $vars       Variables to pass to the view
	 * @param string  $suggestion Suggestion with the deprecation message
	 * @param string  $version    Human-readable *release* version: 1.7, 1.8, ...
	 *
	 * @return string The parsed view
	 * @access private
	 */
	public function renderDeprecatedView($view, array $vars, $suggestion, $version) {
		$rendered = elgg_view($view, $vars, false, null, '', false);
		if ($rendered) {
			elgg_deprecated_notice("The $view view has been deprecated. $suggestion", $version, 3);
		}
		return $rendered;
	}

	/**
	 * Wrapper for file_exists() that caches false results (the stat cache only caches true results).
	 * This saves us from many unneeded file stat calls when a common view uses a fallback.
	 *
	 * @param string $path Path to the file
	 * @return bool
	 */
	public function fileExists($path) {
		if (!isset($this->file_exists_cache[$path])) {
			$this->file_exists_cache[$path] = file_exists($path);
		}
		return $this->file_exists_cache[$path];
	}

	/**
	 * @access private
	 */
	public function viewExists($view, $viewtype = '', $recurse = true) {
		

		// Detect view type
		if ($viewtype === '' || !_elgg_is_valid_viewtype($viewtype)) {
			$viewtype = elgg_get_viewtype();
		}

		if (!isset($this->CONFIG->views->locations[$viewtype][$view])) {
			if (!isset($this->CONFIG->viewpath)) {
				$location = dirname(dirname(dirname(__FILE__))) . "/views/";
			} else {
				$location = $this->CONFIG->viewpath;
			}
		} else {
			$location = $this->CONFIG->views->locations[$viewtype][$view];
		}

		if ($this->fileExists("{$location}$viewtype/$view.php") ||
				$this->fileExists("{$location}$viewtype/$view")) {
			return true;
		}

		// If we got here then check whether this exists as an extension
		// We optionally recursively check whether the extended view exists also for the viewtype
		if ($recurse && isset($this->CONFIG->views->extensions[$view])) {
			foreach ($this->CONFIG->views->extensions[$view] as $view_extension) {
				// do not recursively check to stay away from infinite loops
				if ($this->viewExists($view_extension, $viewtype, false)) {
					return true;
				}
			}
		}

		// Now check if the default view exists if the view is registered as a fallback
		if ($viewtype != 'default' && $this->doesViewtypeFallback($viewtype)) {
			return $this->viewExists($view, 'default');
		}

		return false;

	}

	/**
	 * @access private
	 */
	public function extendView($view, $view_extension, $priority = 501, $viewtype = '') {
		

		if (!isset($this->CONFIG->views)) {
			$this->CONFIG->views = (object) array(
				'extensions' => array(),
			);
			$this->CONFIG->views->extensions[$view][500] = (string) $view;
		} else {
			if (!isset($this->CONFIG->views->extensions[$view])) {
				$this->CONFIG->views->extensions[$view][500] = (string) $view;
			}
		}

		// raise priority until it doesn't match one already registered
		while (isset($this->CONFIG->views->extensions[$view][$priority])) {
			$priority++;
		}

		$this->CONFIG->views->extensions[$view][$priority] = (string) $view_extension;
		ksort($this->CONFIG->views->extensions[$view]);

	}

	/**
	 * @access private
	 */
	public function unextendView($view, $view_extension) {
		

		if (!isset($this->CONFIG->views)) {
			return false;
		}

		if (!isset($this->CONFIG->views->extensions)) {
			return false;
		}

		if (!isset($this->CONFIG->views->extensions[$view])) {
			return false;
		}

		$priority = array_search($view_extension, $this->CONFIG->views->extensions[$view]);
		if ($priority === false) {
			return false;
		}

		unset($this->CONFIG->views->extensions[$view][$priority]);

		return true;
	}

	/**
	 * @access private
	 */
	public function registerCacheableView($view) {
		

		if (!isset($this->CONFIG->views)) {
			$this->CONFIG->views = new \stdClass;
		}

		if (!isset($this->CONFIG->views->simplecache)) {
			$this->CONFIG->views->simplecache = array();
		}

		$this->CONFIG->views->simplecache[$view] = true;
	}

	/**
	 * @access private
	 */
	public function isCacheableView($view) {
		

		if (!isset($this->CONFIG->views)) {
			$this->CONFIG->views = new \stdClass;
		}

		if (!isset($this->CONFIG->views->simplecache)) {
			$this->CONFIG->views->simplecache = array();
		}

		if (isset($this->CONFIG->views->simplecache[$view])) {
			return true;
		} else {
			$currentViewtype = elgg_get_viewtype();
			$viewtypes = array($currentViewtype);

			if ($this->doesViewtypeFallback($currentViewtype) && $currentViewtype != 'default') {
				$viewtypes[] = 'defaut';
			}

			// If a static view file is found in any viewtype, it's considered cacheable
			foreach ($viewtypes as $viewtype) {
				$view_file = $this->getViewLocation($view, $viewtype) . "$viewtype/$view";
				if ($this->fileExists($view_file)) {
					return true;
				}
			}

			// Assume not-cacheable by default
			return false;
		}
	}
}
