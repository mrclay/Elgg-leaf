<?php

namespace Elgg\Mocks\Database;

class SubtypeTable extends \Elgg\Database\SubtypeTable {

	/**
	 * @var array
	 */
	private $subtypes = [];

	/**
	 * {@inheritdoc}
	 */
	public function __construct(\Elgg\Database $db) {
		parent::__construct($db);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getId($type, $subtype) {
		foreach ($this->subtypes as $id => $row) {
			if ($row['type'] == $type && $row['subtype'] == $subtype) {
				return $id;
			}
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function add($type, $subtype, $class = "") {
		$id = $this->getId($type, $subtype);
		if ($id) {
			return $id;
		}
		if (!empty($this->subtypes)) {
			$last = max(array_keys($this->subtypes));
		} else {
			$last = 0;
		}

		$last++;

		$this->subtypes[$last] = [
			'type' => $type,
			'subtype' => $subtype,
			'class' => $class,
		];

		return $last;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSubtype($subtype_id) {
		if (!isset($this->subtypes[$subtype_id])) {
			return false;
		}
		return $this->subtypes[$subtype_id]['subtype'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getClass($type, $subtype) {
		$id = $this->getId($type, $subtype);
		if (empty($this->subtypes[$id]['class'])) {
			return false;
		}
		return $this->subtypes[$id]['class'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getClassFromId($subtype_id) {
		if (empty($this->subtypes[$subtype_id]['class'])) {
			return false;
		}
		return $this->subtypes[$subtype_id]['class'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function remove($type, $subtype) {
		$id = $this->getId($type, $subtype);
		if (!isset($this->subtypes[$id])) {
			return false;
		}
		unset($this->subtypes[$id]);
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function update($type, $subtype, $class = '') {
		$id = $this->getId($type, $subtype);
		if (!$id) {
			return false;
		}

		$this->subtypes[$id] = [
			'type' => $type,
			'subtype' => $subtype,
			'class' => $class,
		];

		return true;
	}

}
