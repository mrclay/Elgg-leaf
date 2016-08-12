<?php

namespace Elgg\Mocks\Database;

use Elgg\Cache\MetadataCache;
use Elgg\Database;
use Elgg\Database\EntityTable as DbEntityTable;
use Elgg\Database\MetadataTable as DbMetadataTabe;
use Elgg\Database\MetastringsTable;
use Elgg\EventsService;
use ElggMetadata;
use ElggSession;
use Elgg\TestCase;

class MetadataTable extends DbMetadataTabe {

	/**
	 * @var ElggMetadata
	 */
	public $mocks = [];

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
	public function __construct(MetadataCache $cache, Database $db, DbEntityTable $entityTable, EventsService $events, MetastringsTable $metastringsTable, ElggSession $session) {
		parent::__construct($cache, $db, $entityTable, $events, $metastringsTable, $session);
		$this->test = TestCase::getInstance();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($id) {
		if (empty($this->mocks[$id])) {
			return false;
		}
		return $this->mocks[$id];
	}

	/**
	 * {@inheritdoc}
	 */
	public function create($entity_guid, $name, $value, $value_type = '', $owner_guid = 0, $access_id = ACCESS_PRIVATE, $allow_multiple = false) {
		$entity = get_entity((int) $entity_guid);
		if (!$entity) {
			return false;
		}

		$this->iterator++;
		$id = $this->iterator;

		$metadata = $this->test->getMockBuilder(ElggMetadata::class)
				->setMethods(['delete'])
				->setConstructorArgs([
					(object) [
						'type' => 'metadata',
						'id' => $id,
						'entity_guid' => $entity->guid,
						'owner_guid' => $entity->owner_guid,
						'name' => $name,
						'value' => $value,
						'time_created' => time(),
						'access_id' => $entity->guid,
					]
				])
				->getMock();

		$metadata->expects($this->test->any())
				->method('delete')
				->will($this->test->returnCallback(function() use ($id) {
							return $this->delete($id);
						}));

		$this->mocks[$id] = $metadata;

		return $metadata->id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($id) {
		if (!isset($this->mocks[$id])) {
			return false;
		}
		unset($this->mocks[$id]);
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAll(array $options = array()) {
		$guids = elgg_extract('guids', $options);
		$rows = [];
		foreach ($this->mocks as $id => $md) {
			if (empty($guids) || in_array($md->entity_guid, $guids)) {
				$rows[] = $md;
			}
		}
		return $rows;
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteAll(array $options = array()) {
		$guids = elgg_extract('guids', $options);
		$deleted = false;
		foreach ($this->mocks as $id => $md) {
			if (empty($guids) || in_array($md->entity_guid, $guids)) {
				unset($this->mocks[$id]);
				$deleted = true;
			}
		}
		return $deleted;
	}

}
