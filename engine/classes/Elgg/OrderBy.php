<?php
namespace Elgg;

/**
 * Representation of an SQL ordering
 */
class OrderBy {
	private $expression = '';
	private $direction = '';

	/**
	 * Constructor
	 *
	 * @param string $expression SQL expression to order by. E.g. "e.guid"
	 * @param string $direction  "ASC" or "DESC"
	 */
	private function __construct($expression, $direction = 'ASC') {
		if (empty($expression)) {
			throw new \InvalidArgumentException('$expression cannot be empty');
		}
		if (!in_array($direction, ['ASC', 'DESC'])) {
			throw new \InvalidArgumentException('$direction must be ASC or DESC');
		}

		$this->expression = $expression;
		$this->direction = $direction;
	}

	/**
	 * Get the expression
	 *
	 * @return string
	 */
	public function getExpression() {
		return $this->expression;
	}

	/**
	 * Get the direction
	 *
	 * @return string
	 */
	public function getDirection() {
		return $this->direction;
	}

	/**
	 * Make an ASC OrderBy
	 *
	 * @param string $expression Expression
	 * @return OrderBy
	 */
	public static function asc($expression) {
		return new self($expression, 'ASC');
	}

	/**
	 * Make a DESC OrderBy
	 *
	 * @param string $expression Expression
	 * @return OrderBy
	 */
	public static function desc($expression) {
		return new self($expression, 'DESC');
	}

	/**
	 * Make an OrderBy from a string
	 *
	 * @param string $order_by Order by. E.g. "expr", "expr ASC", or "expr DESC"
	 * @return OrderBy
	 */
	public static function fromString($order_by) {
		$order_by = trim($order_by);

		if (preg_match('~\s(asc|desc)?\\z~i', $order_by, $m)) {
			$direction = strtoupper($m[1]);
			$expression = trim(substr($order_by, 0, -strlen($direction)));
		} else {
			$direction = 'ASC';
			$expression = $order_by;
		}

		return new self($expression, $direction);
	}
}
