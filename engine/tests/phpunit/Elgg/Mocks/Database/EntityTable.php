<?php

namespace Elgg\Mocks\Database;

use Elgg\Config;
use Elgg\Database;
use Elgg\Database\EntityTable as DbEntityTable;
use ElggEntity;
use ElggGroup;
use ElggObject;
use ElggUser;
use Elgg\TestCase;

class EntityTable extends DbEntityTable {

	/**
	 * @var ElggEntity
	 */
	public $mocks = [];

	/**
	 * @var \stdClass[]
	 */
	public $rows = [];

	/**
	 *
	 * @var TestCase
	 */
	private $test;

	/**
	 * @var int
	 */
	private $iterator = 100;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(Config $config, Database $db) {
		parent::__construct($config, $db);
		$this->test = TestCase::getInstance();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($guid, $type = '') {
		if (empty($this->mocks[$guid])) {
			return false;
		}
		$entity = $this->mocks[$guid];
		if (!$type) {
			return $entity;
		}
		if ($type && $entity && $entity->getType() == $type) {
			return $entity;
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRow($guid) {
		return (isset($this->rows[$guid])) ? $this->rows[$guid] : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists($guid) {
		return $guid && array_key_exists($guid, $this->mocks);
	}

	/**
	 * Setup a mock entity
	 *
	 * @param int    $guid       GUID of the mock entity
	 * @param string $type       Type of the mock entity
	 * @param string $subtype    Subtype of the mock entity
	 * @param array  $attributes Attributes of the mock entity
	 * @return ElggEntity
	 */
	public function setup($guid, $type, $subtype, array $attributes = []) {
		if (!isset($guid)) {
			$this->iterator++;
			$guid = $this->iterator;
		}

		$subtype_id = get_subtype_id($type, $subtype);
		if (!$subtype_id) {
			$subtype_id = add_subtype($type, $subtype);
		}
		$attributes['guid'] = $guid;
		$attributes['type'] = $type;
		$attributes['subtype'] = $subtype_id;

		$primary_attributes = array(
			'owner_guid' => 0,
			'container_guid' => 0,
			'site_guid' => 1,
			'access_id' => ACCESS_PUBLIC,
			'time_created' => time(),
			'time_updated' => time(),
			'last_action' => time(),
			'enabled' => 'yes',
		);

		switch ($type) {
			case 'object' :
				$class = ElggObject::class;
				$external_attributes = [
					'title' => null,
					'description' => null,
				];
				break;
			case 'user' :
				$class = ElggUser::class;
				$external_attributes = [
					'name' => "John Doe $guid",
					'username' => "john_doe_$guid",
					'password' => null,
					'salt' => null,
					'password_hash' => null,
					'email' => "john_doe_$guid@example.com",
					'language' => 'en',
					'banned' => "no",
					'admin' => 'no',
					'prev_last_action' => null,
					'last_login' => null,
					'prev_last_login' => null,
				];
				break;
			case 'group' :
				$class = ElggGroup::class;
				$external_attributes = [
					'name' => null,
					'description' => null,
				];
				break;
		}

		$map = array_merge($primary_attributes, $external_attributes, $attributes);

		$attrs = (object) $map;

		$entity = new $class($attrs);

		foreach ($map as $name => $value) {
			if (!isset($entity->$name)) {
				// not an attribute, so needs to be set again
				$entity->$name = $value;
			}
		}

		$this->rows[$guid] = $attrs;
		$this->mocks[$guid] = $entity;

		return $entity;
	}

}
