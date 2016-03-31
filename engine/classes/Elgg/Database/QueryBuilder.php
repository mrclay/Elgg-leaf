<?php
namespace Elgg\Database;

use Elgg\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQB;
use Elgg\OrderBy;

/**
 * Query builder based on Doctrine DBAL
 *
 * Arguments accepting table names can auto-prepend the Elgg prefix. Simply pass these in as "{table}".
 *
 * <code>
 * $qb = _elgg_services()->db->getQueryBuilder();
 * $qb->select('e.*')
 *    ->from('{entities}', 'e')
 *    ->setMaxResults(2);
 * $rows = $qb->execute();
 * </code>
 *
 * @link http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/stable/reference/query-builder.html
 *
 * @method QueryBuilder setParameter($key, $value, $type = null)
 * @method QueryBuilder setParameters(array $params, array $types = array())
 * @method QueryBuilder setFirstResult($firstResult)
 * @method QueryBuilder setMaxResults($maxResults)
 * @method QueryBuilder add($sqlPartName, $sqlPart, $append = false)
 * @method QueryBuilder addSelect($select = null)
 * @method QueryBuilder set($key, $value)
 * @method QueryBuilder where($predicate)
 * @method QueryBuilder andWhere($where)
 * @method QueryBuilder orWhere($where)
 * @method QueryBuilder groupBy($groupBy)
 * @method QueryBuilder addGroupBy($groupBy)
 * @method QueryBuilder setValue($column, $value)
 * @method QueryBuilder values(array $values)
 * @method QueryBuilder having($having)
 * @method QueryBuilder andHaving($having)
 * @method QueryBuilder orHaving($having)
 * @method QueryBuilder orderBy($sort, $order = null)
 * @method QueryBuilder addOrderBy($sort, $order = null)
 * @method QueryBuilder resetQueryParts($queryPartNames = null)
 * @method QueryBuilder resetQueryPart($queryPartName)
 *
 * @access private
 */
class QueryBuilder extends DoctrineQB {

	/**
	 * @var Database
	 */
	private $db;

	/**
	 * @var string
	 */
	private $append = '';

	/**
	 * Constructor
	 *
	 * @param Connection $connection Active connection
	 * @param Database   $db         Database
	 */
	public function __construct(Connection $connection, Database $db) {
		$this->db = $db;
		parent::__construct($connection);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSQL() {
		return parent::getSQL() . $this->append;
	}

	/**
	 * Append raw SQL to the output query
	 *
	 * @param string $sql SQL to append. E.g. "ON DUPLICATE ..."
	 *
	 * @return self
	 */
	public function appendSql($sql) {
		$this->append = " $sql";
		return $this;
	}

	/**
	 * If a string starts with "{" assume "{table}" and return "elgg_table"
	 *
	 * @param string|null $table Table name. E.g. "custom_table" or "{table}"
	 * @return string|null
	 */
	private function prefixTable($table) {
		// note we pass through empty values because some methods pass null through this
		return $table ? $this->db->prefixTable($table) : $table;
	}

	/**
	 * Create a set for use in with an IN operator
	 *
	 * @param mixed $values Value(s) to place in set
	 * @param int   $type   SQL type (e.g. \PDO::PARAM_INT)
	 *
	 * @return string
	 */
	public function createSet($values, $type = \PDO::PARAM_STR) {
		if (!is_array($values)) {
			$values = [$values];
		}

		$placeHolders = array_map(function ($value) use ($type) {
			// avoid making unnecessary placeholder for ints/string GUIDs
			if ($type === \PDO::PARAM_INT) {
				if (is_int($value)) {
					return (string)$value;
				}
				if (is_string($value) && preg_match('~^(?:0|[1-9]\\d*)\\z~', $value)) {
					return $value;
				}
			}

			return $this->createNamedParameter($value, $type);
		}, $values);

		return "(" . implode(',', $placeHolders) . ")";
	}

	/**
	 * Execute the query. This is a shortcut for the get_data(), get_data_row(), insert_data(),
	 * update_data(), and delete_data() functions.
	 *
	 * @param callable $callback   If a SELECT query, the function applied to each row
	 * @param bool     $single_row If a SELECT query, return only the first row?
	 *
	 * @return mixed
	 */
	public function execute($callback = null, $single_row = false) {
		$sql = $this->getSQL();
		$params = $this->getParameters();

		switch ($this->getType()) {
			case self::SELECT:
				if ($single_row) {
					return $this->db->getDataRow($sql, $callback, $params);
				} else {
					return $this->db->getData($this, $callback, $params);
				}

			case self::UPDATE:
				return $this->db->updateData($sql, true, $params);

			case self::INSERT:
				return $this->db->insertData($sql, $params);

			case self::DELETE:
				return $this->db->deleteData($sql, $params);
		}
	}

	/**
	 * Apply orderings from an options array
	 *
	 * @param array  $options Options array
	 * @param string $key     Options key
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setOrderFromOptions(array $options, $key = 'order_by') {
		if (empty($options['order_by'])) {
			return;
		}

		if (is_string($options[$key])) {
			$options[$key] = explode(',', $options[$key]);
		}
		if (!is_array($options[$key])) {
			throw new \InvalidArgumentException("option '$key' should be a set of orderings");
		}

		$order_bys = array_map(function ($el) use ($key) {
			if ($el instanceof OrderBy) {
				return $el;
			}
			if (is_string($el)) {
				return OrderBy::fromString($el);
			}

			throw new \InvalidArgumentException("option '$key' should be a set of orderings");
		}, $options[$key]);
		/* @var OrderBy[] $order_bys */

		foreach ($order_bys as $order_by) {
			$this->addOrderBy($order_by->getExpression(), $order_by->getDirection());
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function from($from, $alias = null) {
		return parent::from($this->prefixTable($from), $alias);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function insert($insert = null) {
		return parent::insert($this->prefixTable($insert));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function delete($delete = null, $alias = null) {
		return parent::delete($this->prefixTable($delete), $alias);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function update($update = null, $alias = null) {
		return parent::update($this->prefixTable($update), $alias);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function innerJoin($fromAlias, $join, $alias, $condition = null) {
		return parent::innerJoin($fromAlias, $this->prefixTable($join), $alias, $condition);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function join($fromAlias, $join, $alias, $condition = null) {
		return parent::join($fromAlias, $this->prefixTable($join), $alias, $condition);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return self
	 */
	public function leftJoin($fromAlias, $join, $alias, $condition = null) {
		return parent::leftJoin($fromAlias, $this->prefixTable($join), $alias, $condition);
	}
}
