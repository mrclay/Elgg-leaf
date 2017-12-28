<?php

namespace Elgg\ActionsService;

use Elgg\Application;
use Elgg\Http\Input;

/**
 * The object passed to invokable class name handlers
 *
 * @access private
 */
class Action implements \Elgg\Action {

	const EVENT_TYPE = 'action';

	/**
	 * @var Application
	 */
	protected $elgg;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var Input
	 */
	protected $input;

	/**
	 * @var bool
	 */
	protected $is_xhr;

	/**
	 * Constructor
	 *
	 * @param Application $elgg   Elgg application
	 * @param string      $name   Hook name
	 * @param Input       $input  Input service
	 * @param bool        $is_xhr Is the request from Ajax?
	 */
	public function __construct(Application $elgg, string $name, Input $input, bool $is_xhr) {
		$this->elgg = $elgg;
		$this->name = $name;
		$this->input = $input;
		$this->is_xhr = $is_xhr;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParams($filter = true) {
		return $this->input->all($filter);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParam($key, $default = null, $filter = true) {
		return $this->input->get($key, $default, $filter);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEntityParam($key = 'guid') {
		$guid = $this->input->get($key);
		if ($guid) {
			$entity = get_entity($guid);
			if ($entity instanceof \ElggEntity) {
				return $entity;
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserParam($key = 'user_guid') {
		if ($key === 'username') {
			$entity = get_user_by_username($this->input->get($key));
			return $entity ?: null;
		}

		$guid = $this->input->get($key);
		if ($guid) {
			$entity = get_entity($guid);
			if ($entity instanceof \ElggUser) {
				return $entity;
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isXhr() {
		return $this->is_xhr;
	}

	/**
	 * {@inheritdoc}
	 */
	public function elgg() {
		return $this->elgg;
	}

}
