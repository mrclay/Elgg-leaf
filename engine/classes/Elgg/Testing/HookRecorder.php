<?php
namespace Elgg\Testing;

use Elgg\PluginHooksService;
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
 * @propery-read PluginHooksService $wrapped_service
 */
class HookRecorder extends PluginHooksService {
	use WrappedHookRegistrationTrait;

	public function __construct(PluginHooksService $hooks) {
		$this->wrapped_service = $hooks;
	}

	public function trigger($hook, $type, $params = null, $returnvalue = null) {
		$this->captured['trigger'][] = Testing::getLocalVars(get_defined_vars());

		if (is_callable($this->trigger_handler)) {
			return call_user_func($this->trigger_handler, $hook, $type, $params, $returnvalue, $this);
		}

		if (!$this->allow_passthrough) {
			return $returnvalue;
		}

		return $this->wrapped_service->trigger($hook, $type, $params, $returnvalue);
	}
}
