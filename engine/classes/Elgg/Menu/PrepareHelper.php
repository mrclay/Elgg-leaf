<?php
namespace Elgg\Menu;

use ElggMenuItem;

/**
 * Helper for altering menus inside the "prepare" hook
 */
class PrepareHelper {

	/**
	 * @var ItemList[]
	 */
	private $lists;

	/**
	 * Constructor
	 *
	 * @param ElggMenuItem[][] $sections Map of menu sections (value of the "prepare" hook)
	 */
	public function __construct(array $sections) {
		$this->lists = array_map(function ($items) {
			return new ItemList($items);
		}, $sections);
	}

	/**
	 * Get an item list for a section
	 *
	 * @param string $name    Section name
	 * @param mixed  $default Default value if not found
	 *
	 * @return ItemList
	 */
	public function getSection($name, $default = null) {
		if (isset($this->lists[$name])) {
			return $this->lists[$name];
		}

		return $default;
	}

	/**
	 * Set the item list for a section
	 *
	 * @param string                  $name Section name
	 * @param ItemList|ElggMenuItem[] $list List of items
	 */
	public function setSection($name, $list) {
		if (!$list instanceof ItemList) {
			$list = new ItemList($list);
		}
		$this->lists[$name] = $list;
	}

	/**
	 * Get the array of sections (for returning in the "prepare" hook)
	 *
	 * @return ElggMenuItem[][]
	 */
	public function toArray() {
		return array_map(function (ItemList $list) {
			return $list->toArray();
		}, $this->lists);
	}
}
