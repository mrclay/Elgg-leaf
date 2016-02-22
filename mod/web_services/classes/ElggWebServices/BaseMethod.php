<?php

namespace ElggWebServices;

abstract class BaseMethod implements Method {

	/**
	 * {@inheritdoc}
	 */
	abstract public function getName();

	/**
	 * {@inheritdoc}
	 */
	public function getDescription() {
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMethod() {
		return Method::GET;
	}

	/**
	 * {@inheritdoc}
	 */
	public function requireApiAuth() {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function requireUserAuth() {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParams() {
		return new ParamList();
	}

	/**
	 * {@inheritdoc}
	 */
	abstract public function __invoke(array $params);
}
