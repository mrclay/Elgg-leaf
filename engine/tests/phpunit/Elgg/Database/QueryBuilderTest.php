<?php
namespace Elgg\Database;

use Elgg\Database;
use Doctrine\DBAL\Connection;
use Elgg\OrderBy;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanAppend() {
		$qb = $this->getQb();
		$qb->select('*');
		$qb->from('foo');
		$qb->appendSql('LIMIT 1');

		$this->assertEquals('SELECT * FROM foo LIMIT 1', $qb->getSQL());
	}

	public function testCanAutoPrefix() {
		$qb = $this->getQb();
		$qb->select('*')
			->from('{foo}');

		$this->assertEquals('SELECT * FROM test_foo', $qb->getSQL());
	}

	public function testCanInjectOrder() {
		$qb = $this->getQb();
		$qb->select('*')
			->from('foo')
			->setOrderFromOptions([
				'order_by' => 'e.guid, foo DESC',
			]);

		$this->assertEquals('SELECT * FROM foo ORDER BY e.guid ASC, foo DESC', $qb->getSQL());
		$this->assertEquals([], $qb->getParameters());

		$qb = $this->getQb();
		$qb->select('*')
			->from('foo')
			->setOrderFromOptions([
				'order_by' => [OrderBy::asc('FUNC(bar)'), OrderBy::desc('bar')],
			]);

		$this->assertEquals('SELECT * FROM foo ORDER BY FUNC(bar) ASC, bar DESC', $qb->getSQL());
		$this->assertEquals([], $qb->getParameters());
	}

	public function testCanCreateSet() {
		$qb = $this->getQb();
		$qb->select('*')
			->from('foo');
		$qb->where('bar IN ' . $qb->createSet(['2', -1]));

		$pattern = '~^SELECT \\* FROM foo WHERE bar IN \\((\\:dcValue\d+),(\\:dcValue\d+)\\)~';
		$this->assertEquals(1, preg_match($pattern, $qb->getSQL(), $m));
		$this->assertEquals([$m[1], $m[2]], array_keys($qb->getParameters()));
	}

	private function getQb() {
		return new QueryBuilder($this->getConnectionMock(), $this->getDbMock());
	}

	/**
	 * @return Database|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getDbMock() {
		return $this->getMock(
			Database::class,
			['updateData'],
			[
				new \Elgg\Database\Config((object)['dbprefix' => 'test_']),
				_elgg_services()->logger
			]
		);
	}

	/**
	 * @return Connection|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getConnectionMock() {
		return $this->getMock(
			Connection::class,
			[],
			[],
			'',
			false
		);
	}
}
