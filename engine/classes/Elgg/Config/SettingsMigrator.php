<?php
namespace Elgg\Config;

use Elgg\Database;

/**
 * Migrates DB values to settings.php
 *
 * @access private
 */
class SettingsMigrator {

	/**
	 * Migrate dataroot and wwwroot to the settings file (needed in 3.0)
	 *
	 * @param Database  $db            Database
	 * @param string    $settings_path settings.php file path
	 * @param \stdClass $CONFIG        Elgg's config object
	 *
	 * @return void
	 */
	public function migrate(Database $db, $settings_path, $CONFIG) {
		try {
			$row = $db->getDataRow("
				SELECT value FROM {$db->prefix}datalists
				WHERE name = 'dataroot'
			");

			if ($row) {
				$lines = [
					"",
					"/**",
					" * The full file path for Elgg data storage. E.g. /path/to/elgg-data/",
					" *",
					" * @global string \$CONFIG->dataroot",
					" */",
					"\$CONFIG->dataroot = '{$row->value}';",
					""
				];
				$bytes = implode(PHP_EOL, $lines);

				file_put_contents($settings_path, $bytes, FILE_APPEND | LOCK_EX);

				$CONFIG->dataroot = $row->value;
			} else {
				error_log("The DB table {$db->prefix}datalists did not have 'dataroot'.");
			}
		} catch (\DatabaseException $ex) {
			error_log($ex->getMessage());
		}

		try {
			$row = $db->getDataRow("
				SELECT url FROM {$db->prefix}sites_entity
				WHERE guid = 1
			");

			if ($row) {
				$lines = [
					"",
					"/**",
					" * The installation root URL of the site. E.g. https://example.org/elgg/",
					" *",
					" * If not provided, this is sniffed from the Symfony Request object",
					" *",
					" * @global string \$CONFIG->wwwroot",
					" */",
					"\$CONFIG->dataroot = '{$row->url}';",
					""
				];
				$bytes = implode(PHP_EOL, $lines);

				file_put_contents($settings_path, $bytes, FILE_APPEND | LOCK_EX);

				$CONFIG->wwwroot = $row->url;
			} else {
				error_log("The DB table {$db->prefix}sites_entity did not have 'url'.");
			}
		} catch (\DatabaseException $ex) {
			error_log($ex->getMessage());
		}
	}
}
