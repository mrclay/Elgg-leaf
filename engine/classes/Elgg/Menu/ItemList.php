<?php
namespace Elgg\Menu;

use ElggMenuItem;
use ElggMenuBuilder;

/**
 * Helper object for working with a linear set of menu items, e.g. inside a "prepare" hook.
 */
class ItemList {

	/**
	 * @var ElggMenuItem[]
	 */
	protected $items = [];

	/**
	 * @var string[]
	 */
	protected $names = [];

	/**
	 * Constructor
	 *
	 * @param ItemList|array $items List of items (or item specification arrays)
	 */
	public function __construct($items = []) {
		$this->appendList($items);
	}

	/**
	 * Get the list as an array of items
	 *
	 * @return ElggMenuItem[]
	 */
	public function toArray() {
		return $this->items;
	}

	/**
	 * Get an item, optionally removing it from the list
	 *
	 * <code>
	 * if ($item = $list->get('blog')) {
	 *     // edit the blog item
	 * }
	 * </code>
	 *
	 * @param string|int|ElggMenuItem $item   Item, name, or position
	 * @param bool                    $remove Remove the item, too?
	 *
	 * @return null|ElggMenuItem
	 */
	public function get($item, $remove = false) {
		$pos = $this->search($item);
		if ($pos === false) {
			return null;
		}

		$item = $this->items[$pos];

		if ($remove) {
			unset($this->items[$pos], $this->names[$pos]);
			$this->items = array_values($this->items);
			$this->names = array_values($this->names);
		}

		return $item;
	}

	/**
	 * Sort the list and optionally the descendants
	 *
	 * @param callable|string $sort_by   Sort type as string or php callback
	 * @param bool            $recursive Sort descendants?
	 * @return void
	 */
	public function sort($sort_by, $recursive = true) {
		$comparators = [
			'text' => [ElggMenuBuilder::class, 'compareByText'],
			'name' => [ElggMenuBuilder::class, 'compareByName'],
			'priority' => [ElggMenuBuilder::class, 'compareByPriority'],
		];
		if (is_string($sort_by) && isset($comparators[$sort_by])) {
			$sort_by = $comparators[$sort_by];
		} elseif (!is_callable($sort_by)) {
			throw new \InvalidArgumentException('$sort_by must be "text", "name", "priority", or a callable');
		}

		foreach ($this->items as $key => $item) {
			$item->setData('original_order', $key);
		}
		usort($this->items, $sort_by);
		$this->names = array_map(function (ElggMenuItem $item) {
			return $item->getName();
		}, $this->items);

		if ($recursive) {
			foreach ($this->items as $item) {
				$item->sortChildren($sort_by);
			}
		}
	}

	/**
	 * Push an item onto the end. If already in the list, it's moved to the end.
	 *
	 * @param ElggMenuItem $item Item
	 *
	 * @return ElggMenuItem
	 */
	public function push(ElggMenuItem $item) {
		$this->remove($item);
		$this->items[] = $item;
		$this->names[] = $item->getName();
		return $item;
	}

	/**
	 * Remove all items
	 *
	 * @return self
	 */
	public function removeAll() {
		$this->items = $this->names = [];
		return $this;
	}

	/**
	 * Append a list of items
	 *
	 * @param ItemList|array $items List of items (or item specification arrays)
	 *
	 * @return self
	 */
	public function appendList($items) {
		if ($items instanceof ItemList) {
			$items = $items->items;
		} elseif (isset($items['name'])) {
			// a single spec array
			$items = [$items];
		}
		foreach ($items as $item) {
			$this->push($this->normalizeItem($item));
		}
		return $this;
	}

	/**
	 * Does the list have an item?
	 *
	 * @param string $name Item name
	 *
	 * @return bool
	 */
	public function has($name) {
		return in_array($name, $this->names);
	}

	/**
	 * Get the number of items
	 *
	 * @return int
	 */
	public function count() {
		return count($this->items);
	}

	/**
	 * Splice the list
	 *
	 * @see array_splice For docs on the offset/length arguments
	 *
	 * @param int            $offset      Splice offset
	 * @param int            $length      Splice length
	 * @param ItemList|array $replacement Replacement list/items
	 *
	 * @return self
	 */
	public function splice($offset, $length = 0, $replacement = null) {
		if ($replacement instanceof ElggMenuItem) {
			/* @var ElggMenuItem $replacement */
			$replacementItems = [$replacement];
			$replacementNames = [$replacement->getName()];
		} else {
			if (!$replacement instanceof ItemList) {
				$replacement = new ItemList((array)$replacement);
			}
			$replacementItems = $replacement->items;
			$replacementNames = $replacement->names;
		}
		array_splice($this->items, $offset, $length, $replacementItems);
		array_splice($this->names, $offset, $length, $replacementNames);
		return $this;
	}

	/**
	 * Get a new list by slicing
	 *
	 * @note This returns a new object. If used with a PrepareHelper, you must push the returned object back
	 *       into the PrepareHelper.
	 *
	 * @see array_slice For docs on the offset/length arguments
	 *
	 * @param int      $offset Slice offset
	 * @param int|null $length Slice length
	 *
	 * @return ItemList
	 */
	public function slice($offset, $length = null) {
		return new ItemList(array_slice($this->items, $offset, $length));
	}

	/**
	 * Swap two items in the list
	 *
	 * @param string|int|ElggMenuItem $item1 Item, name, or position
	 * @param string|int|ElggMenuItem $item2 Item, name, or position
	 *
	 * @return bool success
	 */
	public function swap($item1, $item2) {
		$pos1 = $this->search($item1);
		$pos2 = $this->search($item2);

		if ($pos1 === false || $pos2 === false) {
			return false;
		}
		if ($pos1 === $pos2) {
			return true;
		}

		$tempItem = $this->items[$pos1];
		$this->items[$pos1] = $this->items[$pos2];
		$this->items[$pos2] = $tempItem;
		$tempName = $this->names[$pos1];
		$this->names[$pos1] = $this->names[$pos2];
		$this->names[$pos2] = $tempName;
		return true;
	}

	/**
	 * Move or insert an item to new position
	 *
	 * @param string|int|ElggMenuItem $item Item to move/insert
	 * @param int                     $pos  New position (after potentially removing the item)
	 *
	 * @return bool success
	 */
	public function move($item, $pos) {
		$removed_item = $this->remove($item);
		if ($removed_item) {
			$item = $removed_item;
		}
		if (!$item || is_string($item)) {
			return false;
		}
		if ($pos === -1 || $pos === count($this->names)) {
			$this->push($this->normalizeItem($item));
		} else {
			if ($pos < 0) {
				$pos += 1;
			}
			$pos = $this->normalizePosition($pos, true);
			$this->splice($pos, 0, $item);
		}
		return true;
	}

	/**
	 * Return the (non-negative) position of an item (or false if not found)
	 *
	 * @param string|int|ElggMenuItem $item Item, name, or position
	 *
	 * @return int|false false if not found
	 */
	public function search($item) {
		if ($item instanceof ElggMenuItem) {
			/* @var ElggMenuItem $item */
			$item = $item->getName();
		}

		if (is_string($item)) {
			return array_search($item, $this->names);
		} else {
			return $this->normalizePosition((int)$item);
		}
	}

	/**
	 * Remove and return an item from the list
	 *
	 * @param string|int|ElggMenuItem $item Item, name, or position
	 *
	 * @return ElggMenuItem|null
	 */
	public function remove($item) {
		return $this->get($item, true);
	}

	/**
	 * Replace an item with a new one
	 *
	 * @param string|int|ElggMenuItem $item        Item, name, or position
	 * @param ElggMenuItem            $replacement Replacement item
	 *
	 * @return bool success
	 */
	public function replace($item, ElggMenuItem $replacement) {
		$pos = $this->search($item);
		if ($pos !== false) {
			$this->items[$pos] = $replacement;
			$this->names[$pos] = $replacement->getName();
			return true;
		}

		return false;
	}

	/**
	 * If not given an item, use the menu factory to turn it into one
	 *
	 * @param ElggMenuItem|array $item Item or specification array
	 *
	 * @return ElggMenuItem
	 * @throws \InvalidArgumentException
	 */
	protected function normalizeItem($item) {
		if (!$item instanceof ElggMenuItem) {
			$item = ElggMenuItem::factory($item);
			if (!$item) {
				throw new \InvalidArgumentException('ElggMenuItem::factory received an invalid specification');
			}
		}
		return $item;
	}

	/**
	 * Convert negative offset to positive, optionally limiting return value to a valid offset
	 *
	 * @param int  $pos  Position
	 * @param bool $bind Always return valid array offset
	 *
	 * @return int|bool false if $pos would be out of bounds
	 */
	protected function normalizePosition($pos, $bind = false) {
		$count = count($this->items);
		if ($pos < 0) {
			$pos = $count + $pos;
		}

		if ($bind) {
			return min(max(0, $pos), $count - 1);
		} else {
			return ($pos >= 0 && $pos <= ($count - 1))
				? $pos
				: false;
		}
	}
}
