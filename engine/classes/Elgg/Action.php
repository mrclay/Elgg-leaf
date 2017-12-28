<?php

namespace Elgg;

/**
 * Models an event passed to action handlers
 *
 * @since 2.0.0
 */
interface Action {

	/**
	 * Get the name of the action
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the parameters from the request query
	 *
	 * @param bool $filter Sanitize input values
	 *
	 * @return array
	 */
	public function getParams($filter = true);

	/**
	 * Get an element of the params array. If the params array is not an array,
	 * the default will always be returned.
	 *
	 * @param string $key     The key of the value in the params array
	 * @param mixed  $default The value to return if missing
	 * @param bool   $filter  Sanitize input value
	 *
	 * @return mixed
	 */
	public function getParam($key, $default = null, $filter = true);

	/**
	 * Gets the "entity" key from the params if it holds an Elgg entity
	 *
	 * @param string $key Input key to pull entity GUID from
	 *
	 * @return \ElggEntity|null
	 */
	public function getEntityParam($key = 'guid');

	/**
	 * Gets the "user" key from the params if it holds an Elgg user
	 *
	 * @param string $key Input key to pull user GUID from, or "username" for a username.
	 *
	 * @return \ElggUser|null
	 */
	public function getUserParam($key = 'user_guid');

	/**
	 * Is the request from Ajax?
	 *
	 * @return bool
	 */
	public function isXhr();

	/**
	 * Get the Elgg application
	 *
	 * @return \Elgg\Application
	 */
	public function elgg();
}
