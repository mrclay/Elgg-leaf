<?php
namespace Elgg;
use Elgg\Debug\Inspector;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 * 
 * Use the elgg_* versions instead.
 * 
 * @access private
 * 
 * @package    Elgg.Core
 * @subpackage Hooks
 * @since      1.9.0
 */
class PluginHooksService extends \Elgg\HooksRegistrationService {

	/**
	 * Triggers a plugin hook
	 *
	 * @see elgg_trigger_plugin_hook
	 * @access private
	 */
	public function trigger($hook, $type, $params = null, $returnvalue = null) {
		$hooks = $this->getOrderedHandlers($hook, $type);

		foreach ($hooks as $callback) {
			if (!is_callable($callback)) {
				if ($this->logger) {
					$inspector = new Inspector();
					$this->logger->warn("handler for plugin hook [$hook, $type] is not callable: "
							. $inspector->describeCallable($callback));
				}
				continue;
			}

			$forward_hook_warning = function() use ($hook, $type, $callback) {
				elgg_deprecated_notice("'$hook', '$type' plugin hook should not be used to serve a response.
						Instead return an appropriate ResponseBuilder instance from an action or page handler.
						Do not terminate code execution with exit() or die() in $callback", '2.2');
			};
			
			if (in_array($hook, ['forward', 'action', 'route'])) {
				_elgg_services()->events->registerHandler('shutdown', 'system', $forward_hook_warning);
			}

			$args = array($hook, $type, $returnvalue, $params);
			$temp_return_value = call_user_func_array($callback, $args);
			if (!is_null($temp_return_value)) {
				$returnvalue = $temp_return_value;
			}

			if (in_array($hook, ['forward', 'action', 'route'])) {
				_elgg_services()->events->registerHandler('shutdown', 'system', $forward_hook_warning);
			}
		}
		
		return $returnvalue;
	}
}
