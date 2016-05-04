<?php

// none of the following may be empty
$params = array(
	// database parameters
	'dbuser' => 'ignored',
	'dbpassword' => 'ignored',
	'dbname' => 'ignored',
	'dbprefix' => 'elgg_',

	// http://doctrine-orm.readthedocs.io/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
	'dbal_url' => 'sqlite::memory:',

	// site settings
	'sitename' => 'SQLite!',
	'siteemail' => 'not_real@gmail.com',
	'wwwroot' => 'http://localhost/elgg-sqlite/',
	'dataroot' => '/path/to/elgg-sqlite-data/',

	// admin account
	'displayname' => 'Administrator',
	'email' => 'not_real@gmail.com',
	'username' => 'admin',
	'password' => 'fancypassword',
);

require_once __DIR__ . "/../../autoloader.php";

$installer = new ElggInstaller();

// install and create the .htaccess file
$installer->batchInstall($params, false);
