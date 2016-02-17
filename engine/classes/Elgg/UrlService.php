<?php

namespace Elgg;

use Elgg\Http\Request;
use Elgg\Services\Urls;

class UrlsService implements Urls {

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var string
	 */
	private $site_url;

	/**
	 * Constructor
	 *
	 * @param string  $site_url Elgg site URL
	 * @param Request $request  Elgg request
	 */
	public function __construct($site_url, Request $request) {
		$this->site_url = rtrim($site_url . '/') . '/';
		$this->setRequest($request);
	}

	/**
	 * Update the Elgg request object
	 *
	 * @param Request $request Elgg request
	 * @access private
	 * @internal Do not use
	 */
	public function setRequest(Request $request) {
		$this->request = $request;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPathSegments() {
		return $this->request->getUrlSegments();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFirstPathSegment() {
		return $this->request->getFirstUrlSegment();
	}
}
