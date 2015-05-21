<?php
namespace Elgg\Services;

/**
 * Models an event passed to hook handlers
 */
interface Hook {

	/**
	 * Get the name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the type
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Get the current value of the hook
	 *
	 * @return mixed
	 */
	public function getValue();

	/**
	 * Get the params array
	 *
	 * @return mixed
	 */
	public function getParams();

	/**
	 * Get an element of the params array. If the params array is not an array,
	 * the default will always be returned.
	 *
	 * @param string $key     The key of the value in the params array
	 * @param mixed  $default The value to return if missing
	 *
	 * @return mixed
	 */
	public function getParam($key, $default = null);

	/**
	 * Gets the "entity" key from the params if it holds an Elgg entity
	 *
	 * @return \ElggEntity|null
	 */
	public function getEntity();

	/**
	 * Gets the "user" key from the params if it holds an Elgg user
	 *
	 * @return \ElggUser|null
	 */
	public function getUser();

	/**
	 * Get the Elgg application
	 *
	 * @return \Elgg\Application
	 */
	public function elgg();
}
