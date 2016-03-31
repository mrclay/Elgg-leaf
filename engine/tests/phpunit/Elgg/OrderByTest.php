<?php
namespace Elgg;

class OrderByTest extends \PHPUnit_Framework_TestCase {

	public function testCanParseSingle() {
		$obj = OrderBy::fromString(' e.guid desc ');
		$this->assertEquals('DESC', $obj->getDirection());
		$this->assertEquals('e.guid', $obj->getExpression());

		$obj = OrderBy::fromString('e.guid');
		$this->assertEquals('ASC', $obj->getDirection());
		$this->assertEquals('e.guid', $obj->getExpression());
	}

	public function testCanHandleArbitraryExpressions() {
		$obj = OrderBy::asc('MAX(10, e.guid)');
		$this->assertInstanceOf(OrderBy::class, $obj);
		$this->assertEquals('ASC', $obj->getDirection());
		$this->assertEquals('MAX(10, e.guid)', $obj->getExpression());

		$obj = OrderBy::desc('MAX(10, e.guid)');
		$this->assertInstanceOf(OrderBy::class, $obj);
		$this->assertEquals('DESC', $obj->getDirection());
		$this->assertEquals('MAX(10, e.guid)', $obj->getExpression());
	}
}
