<?php
namespace Elgg\Testing;

use Elgg\HooksRegistrationService;
use Elgg\Testing;

trait WrappedHookRegistrationTrait {

	/**
	 * @var array
	 */
	public $captured = array();

	/**
	 * @var bool
	 */
	public $allow_register = true;

	/**
	 * @var bool
	 */
	public $allow_unregister = true;

	/**
	 * @var callable
	 */
	public $trigger_handler = null;

	/**
	 * @var bool
	 */
	public $allow_passthrough = true;

	/**
	 * @var HooksRegistrationService
	 */
	public $wrapped_service;

	public function registerHandler($name, $type, $callback, $priority = 500) {
		$this->captured['register'][] = Testing::getLocalVars(get_defined_vars());

		if (!$this->allow_register) {
			return false;
		}

		return $this->wrapped_service->registerHandler($name, $type, $callback, $priority);
	}

	public function unregisterHandler($name, $type, $callback) {
		$this->captured['unregister'][] = Testing::getLocalVars(get_defined_vars());

		if (!$this->allow_unregister) {
			return false;
		}

		return $this->wrapped_service->unregisterHandler($name, $type, $callback);
	}

	public function setLogger(\Elgg\Logger $logger = null) {
		return $this->wrapped_service->setLogger($logger);
	}

	public function hasHandler($name, $type) {
		return $this->wrapped_service->hasHandler($name, $type);
	}

	public function getAllHandlers() {
		return $this->wrapped_service->getAllHandlers();
	}
}
