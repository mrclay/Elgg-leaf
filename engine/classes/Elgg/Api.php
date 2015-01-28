<?php
namespace Elgg;

use Elgg\Di\ServiceProvider;

/**
 * The Elgg public API
 *
 * @package Elgg
 *
 * @property-read string $db_prefix
 * @property-read \ElggSite $site
 * @property-read \ElggUser|null $logged_in_user
 * @property-read int $logged_in_user_guid
 */
class Api {

	/**
	 * @var ServiceProvider
	 */
	protected $services;

	/**
	 * Constructor
	 *
	 * @param ServiceProvider $services
	 */
	public function __construct(ServiceProvider $services) {
		$this->services = $services;
	}

	public function __get($name) {
		switch ($name) {
			case 'db_prefix': return $this->services->config->get('dbprefix');
			case 'site': return $this->services->config->get('site');
			case 'logged_in_user': return $this->services->session->getLoggedInUser();
			case 'logged_in_user_guid': return $this->services->session->getLoggedInUserGuid();
		}
	}

}
