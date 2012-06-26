<?php
/**
 * Inspect Elgg variables
 *
 */

class ElggInspector {

	/**
	 * Get Elgg event information
	 *
	 * 1st level: event names
	 * 2nd level: handlers with data: priority
	 */
	public function getEvents() {
		global $CONFIG;

		$tree = new ElggInspectorNode('events');
		foreach ($CONFIG->events as $event => $types) {
			foreach ($types as $type => $handlers) {
				$node = new ElggInspectorNode($event . ',' . $type, array(), 'priority');
				foreach ($handlers as $priority => $handler) {
					$data = array('priority' => $priority);
					$node->addChild(new ElggInspectorNode($handler, $data));
				}
				$tree->addChild($node);
			}
		}

		return $tree;
	}

	/**
	 * Get Elgg plugin hooks information
	 *
	 * 1st level: hook names
	 * 2nd level: handlers with data: priority
	 */
	public function getPluginHooks() {
		global $CONFIG;

		$tree = new ElggInspectorNode('hooks');
		foreach ($CONFIG->hooks as $hook => $types) {
			foreach ($types as $type => $handlers) {
				$node = new ElggInspectorNode($hook . ',' . $type, array(), 'priority');
				foreach ($handlers as $priority => $handler) {
					$data = array('priority' => $priority);
					$node->addChild(new ElggInspectorNode($handler, $data));
				}
				$tree->addChild($node);
			}
		}

		return $tree;
	}

	/**
	 * Get Elgg view information
	 *
	 * Directory structure of views
	 */
	public function getViews() {

		$views = $this->findViews();
		$tree = new ElggInspectorNode('views');

		// create the directory structure based on individual view names and
		// store the view locations  on a node when appropriate
		$nodes = array('/' => $tree);
		foreach ($views as $name => $locations) {
			$dirs = explode("/", $name);
			$parent = '/';
			$current = '/';
			foreach ($dirs as $dir) {
				$current .= $dir . '/';
				if (!isset($nodes[$current])) {
					$nodes[$current] = new ElggInspectorNode($dir);
					$nodes[$parent]->addChild($nodes[$current]);
				}
				$parent .= $dir . '/';
			}
			$nodes[$current]->data = $locations;
		}

		return $tree;
	}

	/**
	 * Get Elgg widget information
	 *
	 * 1st level: widget handler with data: name, description, contexts, allow multiple
	 */
	public function getWidgets() {
		global $CONFIG;

		$tree = new ElggInspectorNode('widgets');
		foreach ($CONFIG->widgets->handlers as $handler => $widget) {
			$node = new ElggInspectorNode($handler, (array)$widget);
			$tree->addChild($node);
		}

		return $tree;
	}


	/**
	 * Get Elgg actions information
	 *
	 * 1st level: actions names with data: file, public, admin
	 */
	public function getActions() {
		global $CONFIG;

		$tree = new ElggInspectorNode('actions');
		foreach ($CONFIG->actions as $action => $info) {
			$node = new ElggInspectorNode($action, $info);
			$tree->addChild($node);
		}

		return $tree;
	}

	/**
	 * Get simplecache information
	 *
	 * 1st level: view name
	 */
	public function getSimpleCache() {
		global $CONFIG;

		$tree = new ElggInspectorNode('simple cache');
		foreach ($CONFIG->views->simplecache as $view) {
			$node = new ElggInspectorNode($view);
			$tree->addChild($node);
		}

		return $tree;
	}

	/**
	 * Get Elgg web services API methods
	 *
	 * 1st level: method with data: function, parameters, call_method, api auth, user auth
	 */
	public function getWebServices() {
		global $API_METHODS;

		$tree = new ElggInspectorNode('actions');
		foreach ($API_METHODS as $method => $info) {
			$info['parameters'] = array_keys($info['parameters']);
			$node = new ElggInspectorNode($method, $info);
			$tree->addChild($node);
		}

		return $tree;
	}

	/**
	 * Create array of all php files in directory and subdirectories
	 *
	 * @param $dir full path to directory to begin search
	 * @return array of every php file in $dir or below in file tree
	 */
	protected function recurseFileTree($dir) {
		$view_list = array();

		$handle = opendir($dir);
		while ($file = readdir($handle)) {
			if ($file[0] == '.') {

			} else if (is_dir($dir . $file)) {
				$view_list = array_merge($view_list, $this->recurseFileTree($dir . $file. "/"));
			} else {
				$extension = strrchr(trim($file, "/"), '.');
				if ($extension === ".php") {
					$view_list[] = $dir . $file;
				}
			}
		}
		closedir($handle);

		return $view_list;
	}

	/**
	 * Find all views that are registered with Elgg
	 *
	 * Core views are assumed rather than registered so we much manually load these.
	 * Views that have been overridden do not appear. We would have to crawl all
	 * the plugin directories to show all overridden views.
	 *
	 * @return array
	 */
	protected function findViews() {
		global $CONFIG;

		$coreViews = $this->recurseFileTree($CONFIG->viewpath . "default/");

		// remove base path and php extension
		array_walk($coreViews, create_function('&$v,$k', 'global $CONFIG; $v = substr($v, strlen($CONFIG->viewpath . "default/"), -4);'));

		// setup views array before adding extensions and plugin views
		$views = array();
		foreach ($coreViews as $view) {
			$views[$view] = array(500 => $CONFIG->viewpath . "default/" . $view . ".php");
		}

		// add plugins and handle overrides
		foreach ($CONFIG->views->locations['default'] as $view => $location) {
			$views[$view] = array(500 => $location . $view . ".php");
		}

		// now extensions
		foreach ($CONFIG->views->extensions as $view => $extensions) {
			$view_list = array();
			foreach ($extensions as $priority => $ext_view) {
				if (isset($views[$ext_view])) {
					$view_list[$priority] = $views[$ext_view][500];
				}
			}
			if (count($view_list) > 0) {
				$views[$view] = $view_list;
			}
		}

		ksort($views);
		return $views;
	}
}
