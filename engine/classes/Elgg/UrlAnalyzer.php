<?php

namespace Elgg;

/**
 * Sniffs Elgg-relevant properties from URLs
 */
class UrlAnalyzer {

	private $host;
	private $path;
	private $scheme;

	/**
	 * Constructor
	 *
	 * @param string $site_url Site URL. if not given, will retrieve from elgg_get_site_url()
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($site_url = null) {
		if (!$site_url && is_callable('elgg_get_site_url')) {
			$site_url = elgg_get_site_url();
		}
		if (!preg_match('~^(https?)\\://([^/]+)(/.*)~', $site_url, $m)) {
			throw new \InvalidArgumentException('$site_url must be full URL');
		}
		$this->scheme = $m[1];
		$this->host = $m[2];
		$this->path = $m[3];
	}

	/**
	 * Sniff a GUID from a URL
	 *
	 * @param string $url URL
	 * @return int 0 if no GUID found
	 */
	public function getGuid($url) {
		$url = $this->analyze($url);
		return empty($url['guid']) ? 0 : $url['guid'];
	}

	/**
	 * Sniff a container GUID from a URL
	 *
	 * @param string $url
	 * @return int 0 if no GUID found
	 */
	public function getContainerGuid($url) {
		$url = $this->analyze($url);
		return empty($url['container_guid']) ? 0 : $url['container_guid'];
	}

	/**
	 * Get Elgg-relevant properties of a URL
	 *
	 * @param string $url URL
	 * @return array|bool
	 */
	public function analyze($url) {
		$url = trim($url);
		if (!preg_match('~\\A(https?)\\://([^/]+)(/[^\\?]*)~', $url, $m)) {
			return false;
		}
		list (, $scheme, $host, $path) = $m;
		$ret = array(
			'scheme_matches' => ($scheme === $this->scheme),
			'host_matches' => ($host === $this->host),
			'guid' => null,
			'container_guid' => null,
			'action' => null,
			'handler' => null,
			'handler_segments' => array(),
		);
		$ret['in_site'] = ($ret['host_matches'] && (0 === strpos($path, $this->path)));
		if (!$ret['in_site']) {
			return $ret;
		}

		$site_path = substr($path, strlen($this->path));
		if (preg_match('~\\Aaction/(.*)~', $site_path, $m)) {
			if (preg_match('~\\A[^/]~', $m[1])) {
				$ret['action'] = $m[1];
			}
			return $ret;
		}

		$segments = explode('/', $site_path);
		if (empty($segments[0])) {
			return $ret;
		}

		$ret['handler'] = $segments[0];
		$ret['handler_segments'] = array_slice($segments, 1);
		if ($segments[0] === 'profile') {
			return $ret;
		}

		if ((count($segments) >= 3)
			&& in_array($segments[1], array('view', 'read'))
			&& preg_match('~\\A[1-9]\\d*\\z~', $segments[2])
		) {
			$ret['guid'] = (int)$segments[2];
		} elseif (preg_match('~\\A[^/]+/group/([1-9]\\d*)/all\\z~', $site_path, $m)) {
			// this is a listing of group items
			$ret['container_guid'] = (int) $m[1];

		} elseif (preg_match('~\\A[^/]+/add/([1-9]\\d*)\\z~', $site_path, $m)) {
			// this is a new item creation page
			$ret['container_guid'] = (int) $m[1];

		} elseif (preg_match('~\\A(?:[^/]+/)+([1-9]\\d*)(?:\\z|/)~', $site_path, $m)) {
			// less-reliable guessing
			$ret['guid'] = (int) $m[1];
		}
		return $ret;
	}
}
