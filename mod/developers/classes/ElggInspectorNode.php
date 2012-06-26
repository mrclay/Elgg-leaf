<?php
/**
 * Node for a tree structure for inspection data
 *
 */

class ElggInspectorNode {
	public $id;
	public $name;
	public $data;
	public $children;
	private $sort_type;

	/**
	 * Create a node
	 * @param string $name The name of the node
	 * @param array  $data Extra data attached to the node
	 * @param string $sort How its children should be sorted
	 */
	public function  __construct($name, array $data = array(), $sort = 'name') {
		$this->id = ElggInspectorNode::getNextId();
		$this->name = $name;
		$this->data = $data;
		$this->children = array();
		$this->sort_type = $sort;
	}

	/**
	 * Add a child to this node
	 * @param ElggInpectorNode $node The node to add
	 */
	public function addChild(ElggInspectorNode $node) {
		$this->children[] = $node;
	}

	/**
	 * Prepare this node for json serialization
	 */
	public function prepare() {
		// the infovis javascript library expects this to be an object
		$this->data = (object)$this->data;

		if ($this->sort_type == 'name') {
			usort($this->children, array('ElggInspectorNode', 'compareByName'));
		} else {
			ElggInspectorNode::$sort_key = $this->sort_type;
			usort($this->children, array('ElggInspectorNode', 'compareByData'));
		}

		foreach ($this->children as $child) {
			$child->prepare();
		}
	}

	protected static $count = 0;
	protected static function getNextId() {
		return ++ElggInspectorNode::$count;
	}

	protected static function compareByName($a, $b) {
		return strcmp($a->name, $b->name);
	}

	// @todo replace with closure when we start requiring PHP 5.3
	protected static $sort_key;
	protected static function compareByData($a, $b) {
		return strnatcmp($a->data[ElggInspectorNode::$sort_key], $b->data[ElggInspectorNode::$sort_key]);
	}
}
