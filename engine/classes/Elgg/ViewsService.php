<?php
namespace Elgg;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * Use the elgg_* versions instead.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Views
 * @since      1.9.0
 */
class ViewsService {

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
	private $CONFIG;

	/**
	 * @var PluginHooksService
	 */
	private $hooks;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var array
	 */
	private $overriden_locations = array();

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
			if (isset($this->CONFIG->views->locations[$viewtype][$view])) {
				$this->overriden_locations[$viewtype][$view][] = $this->CONFIG->views->locations[$viewtype][$view];
			}

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

	/**
	 * @access private
	 */
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
		$rendered = $this->renderView($view, $vars, false, '', false);
		if ($rendered) {
			elgg_deprecated_notice("The $view view has been deprecated. $suggestion", $version, 3);
		}
		return $rendered;
	}

	/**
	 * @access private
	 */
	public function renderView($view, array $vars = array(), $bypass = false, $viewtype = '', $issue_missing_notice = true) {
		

		if (!is_string($view) || !is_string($viewtype)) {
			$this->logger->log("View and Viewtype in views must be a strings: $view", 'NOTICE');
			return '';
		}
		// basic checking for bad paths
		if (strpos($view, '..') !== false) {
			return '';
		}

		if (!is_array($vars)) {
			$this->logger->log("Vars in views must be an array: $view", 'ERROR');
			$vars = array();
		}

		// Get the current viewtype
		if ($viewtype === '' && 0 === strpos($view, 'resources/')) {
			$viewtype = 'default';
		}

		if ($viewtype === '' || !_elgg_is_valid_viewtype($viewtype)) {
			$viewtype = elgg_get_viewtype();
		}

		// allow altering $vars
		$vars_hook_params = [
			'view' => $view,
			'vars' => $vars,
			'viewtype' => $viewtype,
		];
		$vars = $this->hooks->trigger('view_vars', $view, $vars_hook_params, $vars);

		$view_orig = $view;

		// Trigger the pagesetup event
		if (!isset($this->CONFIG->pagesetupdone) && !empty($this->CONFIG->boot_complete)) {
			$this->CONFIG->pagesetupdone = true;
			_elgg_services()->events->trigger('pagesetup', 'system');
		}

		// If it's been requested, pass off to a template handler instead
		if ($bypass == false && isset($this->CONFIG->template_handler) && !empty($this->CONFIG->template_handler)) {
			$template_handler = $this->CONFIG->template_handler;
			if (is_callable($template_handler)) {
				return call_user_func($template_handler, $view, $vars);
			}
		}

		// Set up any extensions to the requested view
		if (isset($this->CONFIG->views->extensions[$view])) {
			$viewlist = $this->CONFIG->views->extensions[$view];
		} else {
			$viewlist = array(500 => $view);
		}

		$content = '';
		foreach ($viewlist as $view) {

			$rendering = $this->renderViewFile($view, $vars, $viewtype, $issue_missing_notice);
			if ($rendering !== false) {
				$content .= $rendering;
				continue;
			}

			// attempt to load default view
			if ($viewtype !== 'default' && $this->doesViewtypeFallback($viewtype)) {

				$rendering = $this->renderViewFile($view, $vars, 'default', $issue_missing_notice);
				if ($rendering !== false) {
					$content .= $rendering;
				}
			}
		}

		// Plugin hook
		$params = array('view' => $view_orig, 'vars' => $vars, 'viewtype' => $viewtype);
		$content = $this->hooks->trigger('view', $view_orig, $params, $content);

		return $content;
	}

	/**
	 * Wrapper for file_exists() that caches false results (the stat cache only caches true results).
	 * This saves us from many unneeded file stat calls when a common view uses a fallback.
	 *
	 * @param string $path Path to the file
	 * @return bool
	 */
	protected function fileExists($path) {
		if (!isset($this->file_exists_cache[$path])) {
			$this->file_exists_cache[$path] = file_exists($path);
		}
		return $this->file_exists_cache[$path];
	}

	/**
	 * Includes view PHP or static file
	 * 
	 * @param string $view                 The view name
	 * @param array  $vars                 Variables passed to view
	 * @param string $viewtype             The viewtype
	 * @param bool   $issue_missing_notice Log a notice if the view is missing
	 *
	 * @return string|false output generated by view file inclusion or false
	 */
	private function renderViewFile($view, array $vars, $viewtype, $issue_missing_notice) {
		$view_location = $this->getViewLocation($view, $viewtype);

		if ($this->fileExists("{$view_location}$viewtype/$view.php")) {
			ob_start();
			include("{$view_location}$viewtype/$view.php");
			return ob_get_clean();
		} else if ($this->fileExists("{$view_location}$viewtype/$view")) {
			return file_get_contents("{$view_location}$viewtype/$view");
		} else {
			if ($issue_missing_notice) {
				$this->logger->log("$viewtype/$view view does not exist.", 'NOTICE');
			}
			return false;
		}
	}

	/**
	 * @access private
	 */
	public function viewExists($view, $viewtype = '', $recurse = true) {
		
		if (empty($view) || !is_string($view)) {
			return false;
		}
		
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

	/**
	 * Register a plugin's views
	 *
	 * @param string $path       Base path of the plugin
	 * @param string $failed_dir This var is set to the failed directory if registration fails
	 * @return bool
	 *
	 * @access private
	 */
	public function registerPluginViews($path, &$failed_dir = '') {
		$view_dir = "$path/views/";

		// plugins don't have to have views.
		if (!is_dir($view_dir)) {
			return true;
		}

		// but if they do, they have to be readable
		$handle = opendir($view_dir);
		if (!$handle) {
			$failed_dir = $view_dir;
			return false;
		}

		while (false !== ($view_type = readdir($handle))) {
			$view_type_dir = $view_dir . $view_type;

			if ('.' !== substr($view_type, 0, 1) && is_dir($view_type_dir)) {
				if ($this->autoregisterViews('', $view_type_dir, $view_dir, $view_type)) {
					elgg_register_viewtype($view_type);
				} else {
					$failed_dir = $view_type_dir;
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get views overridden by setViewLocation() calls.
	 *
	 * @return array
	 *
	 * @access private
	 */
	public function getOverriddenLocations() {
		return $this->overriden_locations;
	}

	/**
	 * Set views overridden by setViewLocation() calls.
	 *
	 * @param array $locations
	 * @return void
	 *
	 * @access private
	 */
	public function setOverriddenLocations(array $locations) {
		$this->overriden_locations = $locations;
	}
}
