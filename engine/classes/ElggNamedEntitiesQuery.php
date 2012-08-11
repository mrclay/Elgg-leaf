<?php

/**
 * Make an elgg_get_entities() options array modifiable by plugin hook.
 */
class ElggNamedEntitiesQuery {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var string
	 */
	protected $queryName;

	/**
	 * @var bool
	 */
	protected $wasAltered = false;

	/**
	 * @param string $query_name
	 * @param array $options
	 */
	public function __construct($query_name, array $options = array()) {
		$this->options = $options;
		$this->queryName = $query_name;
	}

	/**
	 * @return array options for argument of elgg_get_entities()
	 */
	public function getOptions() {
		if (!$this->wasAltered) {
			$hook_params = array(
				'function' => 'elgg_get_entities',
				'options' => $this->options,
				'query_name' => $this->queryName,
			);
			$result = elgg_trigger_plugin_hook('query:entities:modify', $this->queryName, $hook_params, $this->options);
			if (is_array($result)) {
				$this->options = $result;
			}

			$this->wasAltered = true;
		}
		return $this->options;
	}
}
