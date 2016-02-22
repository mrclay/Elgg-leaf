<?php

namespace ElggWebServices\Methods;

use ElggWebServices\BaseMethod;

class ListAll extends BaseMethod {

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return "system.api.list";
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription() {
		return elgg_echo("system.api.list");
	}

	/**
	 * {@inheritdoc}
	 */
	public function __invoke(array $params) {
		return list_all_apis();
	}
}
