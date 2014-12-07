<?php
namespace Elgg\Testing;

use Elgg\EventsService;
use Elgg\Testing;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Testing
 * @since      1.11.0
 *
 * @propery-read EventsService $wrapped_service
 */
class EventRecorder extends EventsService {
	use WrappedHookRegistrationTrait;

	public $default_return = true;

	public function __construct(EventsService $events) {
		$this->wrapped_service = $events;
	}

	public function trigger($event, $type, $object = null, array $options = array()) {
		$this->captured['trigger'][] = Testing::getLocalVars(get_defined_vars());

		if (is_callable($this->trigger_handler)) {
			return call_user_func($this->trigger_handler, $event, $type, $object, $options, $this);
		}

		if (!$this->allow_passthrough) {
			return $this->default_return;
		}

		return $this->wrapped_service->trigger($event, $type, $object, $options);
	}
}
