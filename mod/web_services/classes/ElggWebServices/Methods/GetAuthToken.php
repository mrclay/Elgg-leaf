<?php

namespace ElggWebServices\Methods;

use ElggWebServices\BaseMethod;
use ElggWebServices\Method;
use ElggWebServices\ParamList;

class GetAuthToken extends BaseMethod {

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return "auth.gettoken";
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription() {
		return elgg_echo('auth.gettoken');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMethod() {
		return Method::POST;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParams() {
		$params = new ParamList();
		$params->addStringParam('username');
		$params->addStringParam('password');
		return $params;
	}

	/**
	 * {@inheritdoc}
	 */
	public function __invoke(array $params) {
		return auth_gettoken($params['username'], $params['password']);
	}
}
