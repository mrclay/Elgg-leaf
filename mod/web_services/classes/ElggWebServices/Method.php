<?php

namespace ElggWebServices;

interface Method {

	const GET = 'GET';
	const POST = 'POST';

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getDescription();

	/**
	 * @return string
	 */
	public function getMethod();

	/**
	 * @return bool
	 */
	public function requireApiAuth();

	/**
	 * @return bool
	 */
	public function requireUserAuth();

	/**
	 * @return ParamList
	 */
	public function getParams();

	/**
	 * Compute the result of the method
	 *
	 * @param array $params Method params from the client
	 * @return mixed
	 */
	public function __invoke(array $params);
}
