<?php
namespace Elgg;

/**
 * Combo handler
 *
 * @access private
 *
 * @package Elgg.Core
 */
class ComboHandler {

	/**
	 * @var Application
	 */
	private $application;

	/**
	 * Constructor
	 *
	 * @param Application $app Elgg Application
	 */
	public function __construct(Application $app) {
		$this->application = $app;
	}

	/**
	 * Handle a request for a cached view
	 *
	 * @param array $get_vars    $_GET variables
	 * @param array $server_vars $_SERVER variables
	 * @return void
	 */
	public function handleRequest($get_vars, $server_vars) {
		$config = $this->application->config;

		$request = $this->parseGetVars($get_vars);
		if (!$request) {
			$this->send403();
		}
		$ts = $request['ts'];
		$modules = $request['modules'];

		header('Content-Type: text/javascript', true);

		// this may/may not have to connect to the DB
		$this->setupSimplecache();

		$filter = new Amd\ViewFilter();

		// we can't use $config->get yet. It fails before the core is booted
		if (!$config->getVolatile('simplecache_enabled')) {

			$this->application->bootCore();

			$output = '';

			foreach ($modules as $module) {
				$view = "js/$module.js";

				if (!_elgg_is_view_cacheable($view)) {
					$this->send403();
				}

				$view_out = elgg_view($view);
				if ($view_out === '') {
					continue;
				}

				$filtered = $filter->filter($view, $view_output);
					if ($filtered === $view_output) {
						// may not be a module
						if (!preg_match('/^define\([\'"]/m', $view_output)) {
							// no define was found... this may not be a module
							continue;
						}
					}
				}


			if (!_elgg_is_view_cacheable($view)) {
				$this->send403();
			} else {
				echo $this->renderView($view, $viewtype);
			}
			exit;
		}

		$etag = "\"$ts\"";
		// If is the same ETag, content didn't change.
		if (isset($server_vars['HTTP_IF_NONE_MATCH']) && trim($server_vars['HTTP_IF_NONE_MATCH']) === $etag) {
			header("HTTP/1.1 304 Not Modified");
			exit;
		}

		$filename = $config->getVolatile('dataroot') . 'views_simplecache/' . md5("$viewtype|$view");
		if (file_exists($filename)) {
			$this->sendCacheHeaders($etag);
			readfile($filename);
			exit;
		}

		$this->application->bootCore();

		elgg_set_viewtype($viewtype);
		if (!_elgg_is_view_cacheable($view)) {
			$this->send403();
		}

		$cache_timestamp = (int)$config->get('lastcache');

		if ($cache_timestamp == $ts) {
			$this->sendCacheHeaders($etag);

			$content = $this->getProcessedView($view, $viewtype);

			$dir_name = $config->getDataPath() . 'views_simplecache/';
			if (!is_dir($dir_name)) {
				mkdir($dir_name, 0700);
			}

			file_put_contents($filename, $content);
		} else {
			// if wrong timestamp, don't send HTTP cache
			$content = $this->renderView($view, $viewtype);
		}

		echo $content;
		exit;
	}

	/**
	 * Parse a request
	 *
	 * @param array $vars Request URL
	 *
	 * @return array Cache parameters (empty array if failure)
	 */
	public function parseGetVars($vars) {
		if (empty($vars['m']) || !isset($vars['ts'])) {
			return [];
		}

		$modules = explode(' ', (string)$vars['m']);
		sort($modules);
		$modules = array_unique($modules);

		foreach ($modules as $name) {
			if ($name === 'elgg' || 0 === strpos($name, 'languages/')) {
				return [];
			}
		}

		// we'll validate module names via view system
		return [
			'ts' => (string)$vars['ts'],
			'modules' => $modules,
		];
	}

	/**
	 * Do a minimal engine load
	 *
	 * @return void
	 */
	protected function setupSimplecache() {
		// we can't use Elgg\Config::get yet. It fails before the core is booted
		$config = $this->application->config;
		$config->loadSettingsFile();

		if ($config->getVolatile('dataroot') && $config->getVolatile('simplecache_enabled') !== null) {
			// we can work with these...
			return;
		}

		$db = $this->application->getDb();

		try {
			$rows = $db->getData("
				SELECT `name`, `value`
				FROM {$db->getTablePrefix()}datalists
				WHERE `name` IN ('dataroot', 'simplecache_enabled')
			");
			if (!$rows) {
				$this->send403('Cache error: unable to get the data root');
			}
		} catch (\DatabaseException $e) {
			if (0 === strpos($e->getMessage(), "Elgg couldn't connect")) {
				$this->send403('Cache error: unable to connect to database server');
			} else {
				$this->send403('Cache error: unable to connect to Elgg database');
			}
			exit; // unnecessary, but helps PhpStorm understand
		}

		foreach ($rows as $row) {
			$config->set($row->name, $row->value);
		}

		if (!$config->getVolatile('dataroot')) {
			$this->send403('Cache error: unable to get the data root');
		}
	}

	/**
	 * Send cache headers
	 *
	 * @param string $etag ETag value
	 * @return void
	 */
	protected function sendCacheHeaders($etag) {
		header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', strtotime("+6 months")), true);
		header("Pragma: public", true);
		header("Cache-Control: public", true);
		header("ETag: $etag");
	}

	/**
	 * Get the contents of a view for caching
	 *
	 * @param string $view     The view name
	 * @param string $viewtype The viewtype
	 * @return string
	 * @see CacheHandler::renderView()
	 */
	protected function getProcessedView($view, $viewtype) {
		$content = $this->renderView($view, $viewtype);

		$hook_type = _elgg_get_view_filetype($view);
		$hook_params = array(
			'view' => $view,
			'viewtype' => $viewtype,
			'view_content' => $content,
		);
		return _elgg_services()->hooks->trigger('simplecache:generate', $hook_type, $hook_params, $content);
	}

	/**
	 * Render a view for caching
	 *
	 * @param string $view     The view name
	 * @param string $viewtype The viewtype
	 * @return string
	 */
	protected function renderView($view, $viewtype) {
		elgg_set_viewtype($viewtype);

		if (!elgg_view_exists($view)) {
			$this->send403();
		}

		// disable error reporting so we don't cache problems
		$this->application->config->set('debug', null);

		// @todo elgg_view() checks if the page set is done (isset($CONFIG->pagesetupdone)) and
		// triggers an event if it's not. Calling elgg_view() here breaks submenus
		// (at least) because the page setup hook is called before any
		// contexts can be correctly set (since this is called before page_handler()).
		// To avoid this, lie about $CONFIG->pagehandlerdone to force
		// the trigger correctly when the first view is actually being output.
		$this->application->config->set('pagesetupdone', true);

		return elgg_view($view);
	}

	/**
	 * Send an error message to requestor
	 *
	 * @param string $msg Optional message text
	 * @return void
	 */
	protected function send403($msg = 'Cache error: bad request') {
		header('HTTP/1.1 403 Forbidden');
		echo $msg;
		exit;
	}
}

