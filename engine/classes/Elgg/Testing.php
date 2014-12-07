<?php
namespace Elgg;

use Elgg\Di\ServiceProvider;
use Elgg\Testing\EventRecorder;
use Elgg\Testing\HookRecorder;

/**
 * @access private
 */
class Testing {

	protected static $global_names = [
		'this',
		'GLOBALS',
		'_SERVER',
		'_GET',
		'_POST',
		'_FILES',
		'_COOKIE',
		'_SESSION',
		'_REQUEST',
		'_ENV',
	];

	/**
	 * @var ServiceProvider
	 */
	protected $services;

	/**
	 * Constructor
	 *
	 * @param ServiceProvider $services
	 */
	public function __construct(ServiceProvider $services) {
		$this->services = $services;
	}

	/**
	 * @return EventRecorder
	 */
	public function wrapEvents() {
		return $this->wrapService('events', function (EventsService $events) {
			return new EventRecorder($events);
		});
	}

	/**
	 * @return HookRecorder
	 */
	public function wrapHooks() {
		return $this->wrapService('hooks', function (PluginHooksService $hooks) {
			return new HookRecorder($hooks);
		});
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public function unwrapService($name) {
		$service = $this->services->{"__$name"};
		$this->services->setValue($name, $service);
		$this->services->remove("__$name");
	}

	/**
	 * @param string   $name
	 * @param callable $factory
	 *
	 * @return mixed The service wrapper
	 */
	protected function wrapService($name, \Closure $factory) {
		$service = $this->services->{$name};
		$wrapper = $factory($service);
		$this->services->setValue("__$name", $service);
		$this->services->setValue($name, $wrapper);
		return $wrapper;
	}

	/**
	 * Filter the output of get_defined_vars() to local vars.
	 *
	 * @see get_defined_vars()
	 *
	 * @param array $defined_vars return value of get_defined_vars()
	 * @return array
	 */
	public static function getLocalVars(array $defined_vars) {
		foreach (self::$global_names as $name) {
			unset($defined_vars[$name]);
		}
		return $defined_vars;
	}
}
