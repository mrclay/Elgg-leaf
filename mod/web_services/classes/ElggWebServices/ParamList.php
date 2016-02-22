<?php

namespace ElggWebServices;

/**
 * @method Param addIntParam(string $name)
 * @method Param addBoolParam(string $name)
 * @method Param addFloatParam(string $name)
 * @method Param addStringParam(string $name)
 * @method Param addArrayParam(string $name)
 */
class ParamList {

	/**
	 * @var Param[]
	 */
	private $params = [];

	/**
	 * Handle an undefined method call
	 *
	 * @param string $name Method name
	 * @param array  $args Arguments
	 * @return Param
	 */
	public function __call($name, array $args) {
		if (preg_match('~^add(Int|Bool|Float|String|Array)Param\\z~', $name, $m)) {
			if (!isset($args[0]) || !is_string($args[0])) {
				throw new \InvalidArgumentException('$name must be a string.');
			}

			$type = strtolower($m[1]);
			$param_name = $args[0];
			$this->params[$param_name] = new Param($type);
			return $this->params[$param_name];
		}

		throw new \BadMethodCallException("Method '$name' does not exist.");
	}

	/**
	 * Get the set of params as an array
	 *
	 * @return array
	 */
	public function toArray() {
		$map = function (Param $param) {
			return $param->toArray();
		};
		return array_map($map, $this->params);
	}
}
