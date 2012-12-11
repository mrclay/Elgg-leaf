<?php

/**
 * Object that builds an object instance to return it as a value.
 *
 * Resolvable objects such as Elgg_Di_Reference can be used to determine the classname,
 * constructor arguments, or values to be passed to setter methods.
 *
 * <code>
 * // This factory, when resolved, will create a Pizza object, passing in two arguments,
 * // the second of which is pulled from the container. Before the pizza is returned,
 * // the method "setDough" is called and passed the container's value for "dough".
 * $factory = new Elgg_Di_Factory('Pizza', array(
 *     'deluxe',
 *     new Elgg_Di_Reference('cheese'),
 * ));
 * $factory->setSetter('setDough', new Elgg_Di_Reference('dough'));
 * $container->set('pizza', $factory);
 * </code>
 *
 * @access private
 */
class Elgg_Di_Factory implements Elgg_Di_ResolvableInterface {

	protected $class;
	protected $arguments;
	protected $setters = array();
	protected $container;

	/**
	 * @param string|Elgg_Di_ResolvableInterface $class
	 * @param array $constructorArguments
	 */
	public function __construct($class, array $constructorArguments = array()) {
		$this->class = $class;
		$this->arguments = $constructorArguments;
	}

	/**
	 * Prepare a setter method to be called on the constructed object before being returned. A reference
	 * object can be used to have the value pulled from the container at read-time.
	 *
	 * @param string $method
	 * @param mixed $value a value or a value which can be resolved at read-time
	 * @return Elgg_Di_Factory
	 */
	public function setSetter($method, $value) {
		$this->setters[$method] = $value;
		return $this;
	}

	/**
	 * @param Elgg_Di_Container $container
	 * @return object
	 */
	public function resolveValue(Elgg_Di_Container $container) {
		$this->container = $container;
		$class = $this->_resolve($this->class, true);
		if (empty($this->arguments)) {
			$obj = new $class();
		} else {
			$arguments = array_values($this->arguments);
			$arguments = array_map(array($this, '_resolve'), $arguments);
			$ref = new ReflectionClass($class);
			$obj = $ref->newInstanceArgs($arguments);
		}
		foreach ($this->setters as $method => $value) {
			$obj->{$method}($this->_resolve($value));
		}
		return $obj;
	}

	/**
	 * @param mixed $value
	 * @param bool $requireString
	 * @return mixed
	 * @throws ErrorException
	 */
	protected function _resolve($value, $requireString = false) {
		if ($value instanceof Elgg_Di_ResolvableInterface) {
			/* @var Elgg_Di_ResolvableInterface $value */
			$value = $value->resolveValue($this->container);
		}
		if ($requireString && !is_string($value)) {
			throw new ErrorException('Resolved value was not a string');
		}
		return $value;
	}
}
