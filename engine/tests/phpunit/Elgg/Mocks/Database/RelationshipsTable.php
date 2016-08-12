<?php

namespace Elgg\Mocks\Database;

use Elgg\Database;
use Elgg\Database\EntityTable as DbEntityTable;
use Elgg\Database\MetadataTable as DbMetadataTable;
use Elgg\Database\RelationshipsTable as DbRelationshipsTable;
use Elgg\EventsService;
use ElggMetadata;
use ElggRelationship;

class RelationshipsTable extends DbRelationshipsTable {

	/**
	 * @var ElggMetadata
	 */
	public $mocks = [];

	/**
	 * @var int
	 */
	private $iterator = 100;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(Database $db, DbEntityTable $entities, DbMetadataTable $metadata, EventsService $events) {
		parent::__construct($db, $entities, $metadata, $events);
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
	public function check($guid_one, $relationship, $guid_two) {
		foreach ($this->mocks as $rel) {
			if ($rel->guid_one != $guid_one) {
				continue;
			}
			if ($rel->guid_two != $guid_two) {
				continue;
			}
			if ($rel->relationship != $relationship) {
				continue;
			}
			return $rel;
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($id, $call_event = true) {
		if (!isset($this->mocks[$id])) {
			return false;
		}

		unset($this->mocks[$id]);
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function remove($guid_one, $relationship, $guid_two) {
		$rel = $this->check($guid_one, $relationship, $guid_two);
		if (!$rel) {
			return false;
		}
		return $this->delete($rel->id);
	}

	/**
	 * {@inheritdoc}
	 */
	public function add($guid_one, $relationship, $guid_two) {
		$rel = $this->checkRelationship($guid_one, $relationship, $guid_two);
		if ($rel) {
			return false;
		}

		$this->iterator++;
		$id = $this->iterator;

		if (!$this->get($guid_one) || !$this->get($guid_two) || !$relationship) {
			return false;
		}

		$rel = new ElggRelationship((object) [
					'id' => $id,
					'guid_one' => $guid_one,
					'guid_two' => $guid_two,
					'relationship' => $relationship,
					'time_created' => time(),
		]);

		$this->mocks[$id] = $rel;
		return true;
	}

}
