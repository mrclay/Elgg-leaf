<?php
namespace Elgg;
use Elgg\Database\EntityTable;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * Use the elgg_* versions instead.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Logging
 * @since      1.9.0
 */
class PageOwner {

	/**
	 * @var \ElggEntity|false
	 */
	private $entity = false;

	/**
	 * @var Access
	 */
	private $access;

	/**
	 * @var EntityTable
	 */
	private $entity_table;

	/**
	 * Constructor
	 *
	 * @param Access      $access       Access service
	 * @param EntityTable $entity_table Entity service
	 */
	public function __construct(Access $access, EntityTable $entity_table) {
		$this->access = $access;
		$this->entity_table = $entity_table;
	}

	/**
	 * Set the guid of the entity that owns this page
	 *
	 * @param int $guid The guid of the page owner
	 * @return void
	 */
	public function setGuid($guid) {
		$guid = (int)$guid;
		if (!$guid) {
			$this->entity = false;
			return;
		}
		if ($this->entity && $this->entity->guid == $guid) {
			return;
		}
		// Why fetch the entity here? Since the fetch happens ignoring access, we don't get
		// the benefits of the cache and have to re-fetch it every call. So we cache the entity here.
		$ia = $this->access->setIgnoreAccess(true);
		$entity = $this->entity_table->get($guid);
		$this->access->setIgnoreAccess($ia);

		$this->entity = $entity ? $entity : false;
	}

	/**
	 * Gets the guid of the entity that owns the current page.
	 *
	 * @return int The current page owner guid (0 if none).
	 */
	public function getGuid() {
		return $this->entity ? $this->entity->guid : 0;
	}

	/**
	 * Gets the owner entity for the current page.
	 *
	 * @note Access is disabled when getting the page owner entity.
	 *
	 * @return \ElggUser|\ElggGroup|false The current page owner or false if none.
	 */
	public function getEntity() {
		return $this->entity;
	}
}
