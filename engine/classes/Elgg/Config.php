<?php
namespace Elgg;


/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Config
 * @since      1.10.0
 */
class Config {
	/**
	 * Configuration storage. Is usually reference to global $CONFIG
	 * 
	 * @var \stdClass
	 */
	private $config;

	/**
	 * @var bool
	 */
	private $settings_loaded = false;

	/**
	 * Constructor
	 *
	 * @param \stdClass $config     Elgg's $CONFIG object
	 * @param bool      $set_global Copy the config object to global $CONFIG
	 */
	public function __construct(\stdClass $config = null, $set_global = true) {
		if (!$config) {
			$config = new \stdClass();
		}
		$this->config = $config;

		if ($set_global) {
			/**
			 * Configuration values.
			 *
			 * The $CONFIG global contains configuration values required
			 * for running Elgg as defined in the settings.php file.
			 *
			 * Plugin authors are encouraged to use elgg_get_config() instead of accessing
			 * the global directly.
			 *
			 * @see elgg_get_config()
			 * @see engine/settings.php
			 * @global \stdClass $CONFIG
			 */
			global $CONFIG;
			$CONFIG = $config;
		}
	}

	/**
	 * Get the URL for the current (or specified) site
	 *
	 * @param int $site_guid The GUID of the site whose URL we want to grab
	 * @return string
	 */
	function getSiteUrl($site_guid = 0) {
		if ($site_guid == 0) {
			return $this->config->wwwroot;
		}
	
		$site = get_entity($site_guid);
	
		if (!$site instanceof \ElggSite) {
			return false;
		}
		/* @var \ElggSite $site */
	
		return $site->url;
	}
	
	/**
	 * Get the plugin path for this installation
	 *
	 * @return string
	 */
	function getPluginsPath() {
		return $this->config->pluginspath;
	}
	
	/**
	 * Get the data directory path for this installation
	 *
	 * @return string
	 */
	function getDataPath() {
		return $this->config->dataroot;
	}
	
	/**
	 * Get the root directory path for this installation
	 *
	 * @return string
	 */
	function getRootPath() {
		return $this->config->path;
	}
	
	/**
	 * Get an Elgg configuration value
	 *
	 * @param string $name      Name of the configuration value
	 * @param int    $site_guid null for installation setting, 0 for default site
	 *
	 * @return mixed Configuration value or null if it does not exist
	 */
	function get($name, $site_guid = 0) {
		$name = trim($name);
	
		// do not return $config value if asking for non-current site
		if (($site_guid === 0 || $site_guid === null || $site_guid == $this->config->site_guid) && isset($this->config->$name)) {
			return $this->config->$name;
		}
	
		if ($site_guid === null) {
			// installation wide setting
			$value = _elgg_services()->datalist->get($name);
		} else {
			if ($site_guid == 0) {
				$site_guid = (int) $this->config->site_guid;
			}
	
			// hit DB only if we're not sure if value isn't already loaded
			if (!isset($this->config->site_config_loaded) || $site_guid != $this->config->site_guid) {
				// site specific setting
				$value = _elgg_services()->configTable->get($name, $site_guid);
			} else {
				$value = null;
			}
		}
	
		// @todo document why we don't cache false
		if ($value === false) {
			return null;
		}
	
		if ($site_guid == $this->config->site_guid || $site_guid === null) {
			$this->config->$name = $value;
		}
	
		return $value;
	}
	
	/**
	 * Set an Elgg configuration value
	 *
	 * @warning This does not persist the configuration setting. Use elgg_save_config()
	 *
	 * @param string $name  Name of the configuration value
	 * @param mixed  $value Value
	 *
	 * @return void
	 */
	function set($name, $value) {
		$name = trim($name);
		$this->config->$name = $value;
	}
	
	/**
	 * Save a configuration setting
	 *
	 * @param string $name      Configuration name (cannot be greater than 255 characters)
	 * @param mixed  $value     Configuration value. Should be string for installation setting
	 * @param int    $site_guid null for installation setting, 0 for default site
	 *
	 * @return bool
	 */
	function save($name, $value, $site_guid = 0) {
		$name = trim($name);
	
		if (strlen($name) > 255) {
			_elgg_services()->logger->error("The name length for configuration variables cannot be greater than 255");
			return false;
		}
	
		if ($site_guid === null) {
			if (is_array($value) || is_object($value)) {
				return false;
			}
			$result = _elgg_services()->datalist->set($name, $value);
		} else {
			if ($site_guid == 0) {
				$site_guid = (int) $this->config->site_guid;
			}
			$result = _elgg_services()->configTable->set($name, $value, $site_guid);
		}
	
		if ($site_guid === null || $site_guid == $this->config->site_guid) {
			_elgg_services()->config->set($name, $value);
		}
	
		return $result;
	}

	/**
	 * Merge the settings file into the storage object
	 *
	 * A particular location can be specified via $CONFIG->Config_file
	 *
	 * To skip settings loading, set $CONFIG->Config_file to null
	 *
	 * @return void
	 */
	public function loadSettingsFile() {
		if (isset($this->config->Config_file)) {
			if ($this->config->Config_file === null) {
				return;
			}
			$path = $this->config->Config_file;
		} else {
			$path = dirname(dirname(__DIR__)) . '/settings.php';
		}

		// No settings means a fresh install
		if (!is_file($path)) {
			header("Location: install.php");
			exit;
		}

		if (!is_readable($path)) {
			echo "The Elgg settings file exists but the web server doesn't have read permission to it.";
			exit;
		}

		// we assume settings is going to write to CONFIG, but we may need to copy its values
		// into our local config
		global $CONFIG;
		$global_is_bound = (isset($CONFIG) && $CONFIG === $this->config);

		require_once $path;

		// normalize commonly needed values
		if (isset($CONFIG->dataroot)) {
			$CONFIG->dataroot = rtrim($CONFIG->dataroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}

		if (!$global_is_bound) {
			// must manually copy settings into our storage
			foreach ($CONFIG as $key => $value) {
				$this->config->{$key} = $value;
			}
		}

		$this->settings_loaded = true;
	}

	/**
	 * Get the raw \stdClass object used for storage.
	 *
	 * We need this early in boot to avoid using get(), which triggers DB reads.
	 *
	 * @internal Do not use this plugins or new core code!
	 * @todo Make this unnecessary. We probably need methods to query $this->config (has/get)
	 *
	 * @return \stdClass
	 * @access private
	 */
	public function getStorageObject() {
		return $this->config;
	}
}