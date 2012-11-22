<?php

/**
 * @access private
 */
class Elgg_Developers_QueryProfiler {

	protected $periods = array();

	protected $queriesQueued = 0;
	protected $queriesPerformed = 0;
	protected $lastTime;

	public function __construct($startupTime) {
		$this->lastTime = $startupTime;
	}

	/**
	 * @param string $name
	 * @param int $queriesPerformed
	 * @param int $queriesQueued
	 */
	public function recordPeriod($name, $queriesPerformed, $queriesQueued) {
		$numPerformed = $queriesPerformed - $this->queriesPerformed;
		$this->queriesPerformed = $queriesPerformed;
		$numQueued = $queriesQueued - $this->queriesQueued;
		$this->queriesQueued = $queriesQueued;

		$time = microtime(true);
		$elapsedTime = $time - $this->lastTime;
		$this->lastTime = $time;

		$period = new Elgg_Developers_QueryProfiler_Period();
		$period->name = $name;
		$period->elapsedTime = round($elapsedTime, 4);
		$period->queriesPerformed = $numPerformed;
		$period->queriesQueued = $numQueued;
		$this->periods[] = $period;
	}

	/**
	 * @return Elgg_Developers_QueryProfiler_Period[]
	 */
	public function getPeriods() {
		return $this->periods;
	}
}
