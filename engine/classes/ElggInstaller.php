<?php

use Elgg\Filesystem\Directory;
use Elgg\Application;
use Elgg\Config;
use Elgg\Database\DbConfig;
use Elgg\Project\Paths;
use Elgg\Di\ServiceProvider;
use Elgg\Http\Request;

/**
 * Elgg Installer.
 * Controller for installing Elgg. Supports both web-based on CLI installation.
 *
 * This controller steps the user through the install process. The method for
 * each step handles both the GET and POST requests. There is no XSS/CSRF protection
 * on the POST processing since the installer is only run once by the administrator.
 *
 * The installation process can be resumed by hitting the first page. The installer
 * will try to figure out where to pick up again.
 *
 * All the logic for the installation process is in this class, but it depends on
 * the core libraries. To do this, we selectively load a subset of the core libraries
 * for the first few steps and then load the entire engine once the database and
 * site settings are configured. In addition, this controller does its own session
 * handling until the database is setup.
 *
 * There is an aborted attempt in the code at creating the data directory for
 * users as a subdirectory of Elgg's root. The idea was to protect this directory
 * through a .htaccess file. The problem is that a malicious user can upload a
 * .htaccess of his own that overrides the protection for his user directory. The
 * best solution is server level configuration that turns off AllowOverride for the
 * data directory. See ticket #3453 for discussion on this.
 */
class ElggInstaller {
	
	private $steps = [
		'welcome',
		'requirements',
		'database',
		'settings',
		'admin',
		'complete',
		];

	private $has_completed = [
		'config' => false,
		'database' => false,
		'settings' => false,
		'admin' => false,
	];

	private $is_action = false;

	private $autoLogin = true;

	/**
	 * @var ServiceProvider
	 */
	private $services;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var Application
	 */
	private $app;

	/**
	 * Dispatches a request to one of the step controllers
	 *
	 * @return void
	 * @throws InstallationException
	 */
	public function run() {
		$this->getApp();

		$this->is_action = $this->services->request->getMethod() === 'POST';

		$step = get_input('step', 'welcome');

		if (!in_array($step, $this->getSteps())) {
			$msg = elgg_echo('InstallationException:UnknownStep', [$step]);
			throw new InstallationException($msg);
		}

		$this->determineInstallStatus();
	
		$this->checkInstallCompletion($step);

		// check if this is an install being resumed
		$this->resumeInstall($step);

		$this->finishBootstrapping($step);

		$params = $this->services->request->request->all();

		$method = "run" . ucwords($step);
		$this->$method($params);
	}

	/**
	 * Build the application needed by the installer
	 *
	 * @return Application
	 * @throws InstallationException
	 */
	protected function getApp() {
		if ($this->app) {
			return $this->app;
		}

		$config = new Config();
		$config->elgg_config_locks = false;
		$config->installer_running = true;
		$config->dbencoding = 'utf8mb4';

		$this->config = $config;
		$this->services = new ServiceProvider($config);

		$app = Application::factory([
			'service_provider' => $this->services,
			'handle_exceptions' => false,
			'handle_shutdown' => false,

			// allows settings.php to be loaded, which might try to write to global $CONFIG
			// which is only a problem due to the config values that deep-write to arrays,
			// like cookies.
			'overwrite_global_config' => false,
		]);
		$app->loadCore();
		$this->app = $app;

		$this->services->setValue('session', \ElggSession::getMock());
		$this->services->views->setViewtype('installation');
		$this->services->views->registerPluginViews(Paths::elgg());
		$this->services->translator->registerTranslations(Paths::elgg() . "install/languages/", true);

		return $this->app;
	}

	/**
	 * Set the auto login flag
	 *
	 * @param bool $flag Auto login
	 *
	 * @return void
	 */
	public function setAutoLogin($flag) {
		$this->autoLogin = (bool) $flag;
	}

	/**
	 * A batch install of Elgg
	 *
	 * All required parameters must be passed in as an associative array. See
	 * $requiredParams for a list of them. This creates the necessary files,
	 * loads the database, configures the site settings, and creates the admin
	 * account. If it fails, an exception is thrown. It does not check any of
	 * the requirements as the multiple step web installer does.
	 *
	 * @param array $params          Array of key value pairs
	 * @param bool  $create_htaccess Should .htaccess be created
	 *
	 * @return void
	 * @throws InstallationException
	 */
	public function batchInstall(array $params, $create_htaccess = false) {
		$this->getApp();

		$defaults = [
			'dbhost' => 'localhost',
			'dbprefix' => 'elgg_',
			'language' => 'en',
			'siteaccess' => ACCESS_PUBLIC,
		];
		$params = array_merge($defaults, $params);

		$required_params = [
			'dbuser',
			'dbpassword',
			'dbname',
			'sitename',
			'wwwroot',
			'dataroot',
			'displayname',
			'email',
			'username',
			'password',
		];
		foreach ($required_params as $key) {
			if (empty($params[$key])) {
				$msg = elgg_echo('install:error:requiredfield', [$key]);
				throw new InstallationException($msg);
			}
		}

		// password is passed in once
		$params['password1'] = $params['password2'] = $params['password'];

		if ($create_htaccess) {
			$rewrite_tester = new ElggRewriteTester();
			if (!$rewrite_tester->createHtaccess($params['wwwroot'])) {
				throw new InstallationException(elgg_echo('install:error:htaccess'));
			}
		}

		$this->determineInstallStatus();

		if (!$this->has_completed['config']) {
			if (!$this->createSettingsFile($params)) {
				throw new InstallationException(elgg_echo('install:error:settings'));
			}
		}

		$this->loadSettingsFile();

		// Make sure settings file matches parameters
		$config = $this->config;
		$config_keys = [
			// param key => config key
			'dbhost' => 'dbhost',
			'dbuser' => 'dbuser',
			'dbpassword' => 'dbpass',
			'dbname' => 'dbname',
			'dataroot' => 'dataroot',
			'dbprefix' => 'dbprefix',
		];
		foreach ($config_keys as $params_key => $config_key) {
			if ($params[$params_key] !== $config->$config_key) {
				throw new InstallationException(elgg_echo('install:error:settings_mismatch', [$config_key]));
			}
		}

		if (!$this->connectToDatabase()) {
			throw new InstallationException(elgg_echo('install:error:databasesettings'));
		}

		if (!$this->has_completed['database']) {
			if (!$this->installDatabase()) {
				throw new InstallationException(elgg_echo('install:error:cannotloadtables'));
			}
		}

		// load remaining core libraries
		$this->finishBootstrapping('settings');

		if (!$this->saveSiteSettings($params)) {
			throw new InstallationException(elgg_echo('install:error:savesitesettings'));
		}

		if (!$this->createAdminAccount($params)) {
			throw new InstallationException(elgg_echo('install:admin:cannot_create'));
		}
	}

	/**
	 * Renders the data passed by a controller
	 *
	 * @param string $step The current step
	 * @param array  $vars Array of vars to pass to the view
	 *
	 * @return void
	 */
	protected function render($step, $vars = []) {
		$vars['next_step'] = $this->getNextStep($step);

		$title = elgg_echo("install:$step");
		$body = elgg_view("install/pages/$step", $vars);
				
		echo elgg_view_page(
				$title,
				$body,
				'default',
				[
					'step' => $step,
					'steps' => $this->getSteps(),
					]
				);
		exit;
	}

	/**
	 * Step controllers
	 */

	/**
	 * Welcome controller
	 *
	 * @param array $vars Not used
	 *
	 * @return void
	 */
	protected function runWelcome($vars) {
		$this->render('welcome');
	}

	/**
	 * Requirements controller
	 *
	 * Checks version of php, libraries, permissions, and rewrite rules
	 *
	 * @param array $vars Vars
	 *
	 * @return void
	 */
	protected function runRequirements($vars) {

		$report = [];

		// check PHP parameters and libraries
		$this->checkPHP($report);

		// check URL rewriting
		$this->checkRewriteRules($report);

		// check for existence of settings file
		if ($this->checkSettingsFile($report) != true) {
			// no file, so check permissions on engine directory
			$this->isInstallDirWritable($report);
		}

		// check the database later
		$report['database'] = [[
			'severity' => 'info',
			'message' => elgg_echo('install:check:database')
		]];

		// any failures?
		$numFailures = $this->countNumConditions($report, 'failure');

		// any warnings
		$numWarnings = $this->countNumConditions($report, 'warning');


		$params = [
			'report' => $report,
			'num_failures' => $numFailures,
			'num_warnings' => $numWarnings,
		];

		$this->render('requirements', $params);
	}

	/**
	 * Database set up controller
	 *
	 * Creates the settings.php file and creates the database tables
	 *
	 * @param array $submissionVars Submitted form variables
	 *
	 * @return void
	 */
	protected function runDatabase($submissionVars) {

		$formVars = [
			'dbuser' => [
				'type' => 'text',
				'value' => '',
				'required' => true,
				],
			'dbpassword' => [
				'type' => 'password',
				'value' => '',
				'required' => false,
				],
			'dbname' => [
				'type' => 'text',
				'value' => '',
				'required' => true,
				],
			'dbhost' => [
				'type' => 'text',
				'value' => 'localhost',
				'required' => true,
				],
			'dbprefix' => [
				'type' => 'text',
				'value' => 'elgg_',
				'required' => true,
				],
			'dataroot' => [
				'type' => 'text',
				'value' => '',
				'required' => true,
			],
			'wwwroot' => [
				'type' => 'url',
				'value' => _elgg_config()->wwwroot,
				'required' => true,
			],
			'timezone' => [
				'type' => 'dropdown',
				'value' => 'UTC',
				'options' => \DateTimeZone::listIdentifiers(),
				'required' => true
			]
		];

		if ($this->checkSettingsFile()) {
			// user manually created settings file so we fake out action test
			$this->is_action = true;
		}

		if ($this->is_action) {
			call_user_func(function () use ($submissionVars, $formVars) {
				// only create settings file if it doesn't exist
				if (!$this->checkSettingsFile()) {
					if (!$this->validateDatabaseVars($submissionVars, $formVars)) {
						// error so we break out of action and serve same page
						return;
					}

					if (!$this->createSettingsFile($submissionVars)) {
						return;
					}
				}

				// check db version and connect
				if (!$this->connectToDatabase()) {
					return;
				}

				if (!$this->installDatabase()) {
					return;
				}

				system_message(elgg_echo('install:success:database'));

				$this->continueToNextStep('database');
			});
		}

		$formVars = $this->makeFormSticky($formVars, $submissionVars);

		$params = ['variables' => $formVars,];

		if ($this->checkSettingsFile()) {
			// settings file exists and we're here so failed to create database
			$params['failure'] = true;
		}

		$this->render('database', $params);
	}

	/**
	 * Site settings controller
	 *
	 * Sets the site name, URL, data directory, etc.
	 *
	 * @param array $submissionVars Submitted vars
	 *
	 * @return void
	 */
	protected function runSettings($submissionVars) {
		$formVars = [
			'sitename' => [
				'type' => 'text',
				'value' => 'My New Community',
				'required' => true,
				],
			'siteemail' => [
				'type' => 'email',
				'value' => '',
				'required' => false,
				],
			'siteaccess' => [
				'type' => 'access',
				'value' => ACCESS_PUBLIC,
				'required' => true,
				],
		];

		if ($this->is_action) {
			call_user_func(function () use ($submissionVars, $formVars) {
				if (!$this->validateSettingsVars($submissionVars, $formVars)) {
					return;
				}

				if (!$this->saveSiteSettings($submissionVars)) {
					return;
				}

				system_message(elgg_echo('install:success:settings'));

				$this->continueToNextStep('settings');
			});
		}

		$formVars = $this->makeFormSticky($formVars, $submissionVars);

		$this->render('settings', ['variables' => $formVars]);
	}

	/**
	 * Admin account controller
	 *
	 * Creates an admin user account
	 *
	 * @param array $submissionVars Submitted vars
	 *
	 * @return void
	 */
	protected function runAdmin($submissionVars) {
		$formVars = [
			'displayname' => [
				'type' => 'text',
				'value' => '',
				'required' => true,
				],
			'email' => [
				'type' => 'email',
				'value' => '',
				'required' => true,
				],
			'username' => [
				'type' => 'text',
				'value' => '',
				'required' => true,
				],
			'password1' => [
				'type' => 'password',
				'value' => '',
				'required' => true,
				'pattern' => '.{6,}',
				],
			'password2' => [
				'type' => 'password',
				'value' => '',
				'required' => true,
				],
		];

		if ($this->is_action) {
			call_user_func(function () use ($submissionVars, $formVars) {
				if (!$this->validateAdminVars($submissionVars, $formVars)) {
					return;
				}

				if (!$this->createAdminAccount($submissionVars, $this->autoLogin)) {
					return;
				}

				system_message(elgg_echo('install:success:admin'));

				$this->continueToNextStep('admin');
			});
		}

		// Bit of a hack to get the password help to show right number of characters
		// We burn the value into the stored translation.
		$lang = $this->services->translator->getCurrentLanguage();
		$translations = $this->services->translator->getLoadedTranslations();
		$this->services->translator->addTranslation($lang, [
			'install:admin:help:password1' => sprintf(
				$translations[$lang]['install:admin:help:password1'],
				$this->config->min_password_length
			),
		]);

		$formVars = $this->makeFormSticky($formVars, $submissionVars);

		$this->render('admin', ['variables' => $formVars]);
	}

	/**
	 * Controller for last step
	 *
	 * @return void
	 */
	protected function runComplete() {

		// nudge to check out settings
		$link = elgg_format_element([
			'#tag_name' => 'a',
			'#text' => elgg_echo('install:complete:admin_notice:link_text'),
			'href' => elgg_normalize_url('admin/settings/basic'),
		]);
		$notice = elgg_echo('install:complete:admin_notice', [$link]);
		elgg_add_admin_notice('fresh_install', $notice);

		$this->render('complete');
	}

	/**
	 * Step management
	 */

	/**
	 * Get an array of steps
	 *
	 * @return array
	 */
	protected function getSteps() {
		return $this->steps;
	}

	/**
	 * Forwards the browser to the next step
	 *
	 * @param string $currentStep Current installation step
	 *
	 * @return void
	 */
	protected function continueToNextStep($currentStep) {
		$this->is_action = false;
		forward($this->getNextStepUrl($currentStep));
	}

	/**
	 * Get the next step as a string
	 *
	 * @param string $currentStep Current installation step
	 *
	 * @return string
	 */
	protected function getNextStep($currentStep) {
		$index = 1 + array_search($currentStep, $this->steps);
		if (isset($this->steps[$index])) {
			return $this->steps[$index];
		} else {
			return null;
		}
	}

	/**
	 * Get the URL of the next step
	 *
	 * @param string $currentStep Current installation step
	 *
	 * @return string
	 */
	protected function getNextStepUrl($currentStep) {
		$nextStep = $this->getNextStep($currentStep);
		return $this->config->wwwroot . "install.php?step=$nextStep";
	}

	/**
	 * Updates $this->has_completed according to the current installation
	 *
	 * @return void
	 * @throws InstallationException
	 */
	protected function determineInstallStatus() {
		$path = Paths::settingsFile();
		if (!is_file($path) || !is_readable($path)) {
			return;
		}

		$this->loadSettingsFile();

		$this->has_completed['config'] = true;

		// must be able to connect to database to jump install steps
		$dbSettingsPass = $this->checkDatabaseSettings(
			$this->config->dbuser,
			$this->config->dbpass,
			$this->config->dbname,
			$this->config->dbhost
		);

		if (!$dbSettingsPass) {
			return;
		}

		$db = $this->services->db;

		// check that the config table has been created
		$result = $db->getData("SHOW TABLES");
		if ($result) {
			foreach ($result as $table) {
				$table = (array) $table;
				if (in_array("{$db->prefix}config", $table)) {
					$this->has_completed['database'] = true;
				}
			}
			if ($this->has_completed['database'] == false) {
				return;
			}
		} else {
			// no tables
			return;
		}

		// check that the config table has entries
		$query = "SELECT COUNT(*) AS total FROM {$db->prefix}config";
		$result = $db->getData($query);
		if ($result && $result[0]->total > 0) {
			$this->has_completed['settings'] = true;
		} else {
			return;
		}

		// check that the users entity table has an entry
		$query = "SELECT COUNT(*) AS total FROM {$db->prefix}users_entity";
		$result = $db->getData($query);
		if ($result && $result[0]->total > 0) {
			$this->has_completed['admin'] = true;
		} else {
			return;
		}
	}

	/**
	 * Security check to ensure the installer cannot be run after installation
	 * has finished. If this is detected, the viewer is sent to the front page.
	 *
	 * @param string $step Installation step to check against
	 *
	 * @return void
	 */
	protected function checkInstallCompletion($step) {
		if ($step != 'complete') {
			if (!in_array(false, $this->has_completed)) {
				// install complete but someone is trying to view an install page
				forward();
			}
		}
	}

	/**
	 * Check if this is a case of a install being resumed and figure
	 * out where to continue from. Returns the best guess on the step.
	 *
	 * @param string $step Installation step to resume from
	 *
	 * @return string
	 */
	protected function resumeInstall($step) {
		// only do a resume from the first step
		if ($step !== 'welcome') {
			return;
		}

		if ($this->has_completed['database'] == false) {
			return;
		}

		if ($this->has_completed['settings'] == false) {
			forward("install.php?step=settings");
		}

		if ($this->has_completed['admin'] == false) {
			forward("install.php?step=admin");
		}

		// everything appears to be set up
		forward("install.php?step=complete");
	}

	/**
	 * Bootstrapping
	 */

	/**
	 * Load remaining engine libraries and complete bootstrapping
	 *
	 * @param string $step Which step to boot strap for. Required because
	 *                     boot strapping is different until the DB is populated.
	 *
	 * @return void
	 * @throws InstallationException
	 */
	protected function finishBootstrapping($step) {

		$index_db = array_search('database', $this->getSteps());
		$index_settings = array_search('settings', $this->getSteps());
		$index_admin = array_search('admin', $this->getSteps());
		$index_complete = array_search('complete', $this->getSteps());
		$index_step = array_search($step, $this->getSteps());

		// To log in the user, we need to use the Elgg core session handling.
		// Otherwise, use default php session handling
		$use_elgg_session = ($index_step == $index_admin && $this->is_action) || ($index_step == $index_complete);
		if (!$use_elgg_session) {
			$session = ElggSession::fromFiles($this->config);
			$session->setName('Elgg_install');
			$this->services->setValue('session', $session);
		}

		if ($index_step > $index_db) {
			// once the database has been created, load rest of engine

			// dummy site needed to boot
			$this->config->site = new ElggSite();

			$this->app->bootCore();
		}
	}

	/**
	 * Load settings
	 *
	 * @return void
	 * @throws InstallationException
	 */
	protected function loadSettingsFile() {
		try {
			$config = Config::fromFile(Paths::settingsFile());
			$this->config->mergeValues($config->getValues());

			// in case the DB instance is already captured in services, we re-inject its settings.
			$this->services->db->resetConnections(DbConfig::fromElggConfig($this->config));
		} catch (\Exception $e) {
			$msg = elgg_echo('InstallationException:CannotLoadSettings');
			throw new InstallationException($msg, 0, $e);
		}
	}

	/**
	 * Action handling methods
	 */

	/**
	 * If form is reshown, remember previously submitted variables
	 *
	 * @param array $formVars       Vars int he form
	 * @param array $submissionVars Submitted vars
	 *
	 * @return array
	 */
	protected function makeFormSticky($formVars, $submissionVars) {
		foreach ($submissionVars as $field => $value) {
			$formVars[$field]['value'] = $value;
		}
		return $formVars;
	}

	/* Requirement checks support methods */

	/**
	 * Indicates whether the webserver can add settings.php on its own or not.
	 *
	 * @param array $report The requirements report object
	 *
	 * @return bool
	 */
	protected function isInstallDirWritable(&$report) {
		if (!is_writable(Paths::projectConfig())) {
			$msg = elgg_echo('install:check:installdir', [Paths::PATH_TO_CONFIG]);
			$report['settings'] = [
				[
					'severity' => 'failure',
					'message' => $msg,
				]
			];
			return false;
		}

		return true;
	}

	/**
	 * Check that the settings file exists
	 *
	 * @param array $report The requirements report array
	 *
	 * @return bool
	 */
	protected function checkSettingsFile(&$report = []) {
		if (!is_file(Paths::settingsFile())) {
			return false;
		}

		if (!is_readable(Paths::settingsFile())) {
			$report['settings'] = [
				[
					'severity' => 'failure',
					'message' => elgg_echo('install:check:readsettings'),
				]
			];
		}
		
		return true;
	}

	/**
	 * Check version of PHP, extensions, and variables
	 *
	 * @param array $report The requirements report array
	 *
	 * @return void
	 */
	protected function checkPHP(&$report) {
		$phpReport = [];

		$min_php_version = '5.6.0';
		if (version_compare(PHP_VERSION, $min_php_version, '<')) {
			$phpReport[] = [
				'severity' => 'failure',
				'message' => elgg_echo('install:check:php:version', [$min_php_version, PHP_VERSION])
			];
		}

		$this->checkPhpExtensions($phpReport);

		$this->checkPhpDirectives($phpReport);

		if (count($phpReport) == 0) {
			$phpReport[] = [
				'severity' => 'pass',
				'message' => elgg_echo('install:check:php:success')
			];
		}

		$report['php'] = $phpReport;
	}

	/**
	 * Check the server's PHP extensions
	 *
	 * @param array $phpReport The PHP requirements report array
	 *
	 * @return void
	 */
	protected function checkPhpExtensions(&$phpReport) {
		$extensions = get_loaded_extensions();
		$requiredExtensions = [
			'pdo_mysql',
			'json',
			'xml',
			'gd',
		];
		foreach ($requiredExtensions as $extension) {
			if (!in_array($extension, $extensions)) {
				$phpReport[] = [
					'severity' => 'failure',
					'message' => elgg_echo('install:check:php:extension', [$extension])
				];
			}
		}

		$recommendedExtensions = [
			'mbstring',
		];
		foreach ($recommendedExtensions as $extension) {
			if (!in_array($extension, $extensions)) {
				$phpReport[] = [
					'severity' => 'warning',
					'message' => elgg_echo('install:check:php:extension:recommend', [$extension])
				];
			}
		}
	}

	/**
	 * Check PHP parameters
	 *
	 * @param array $phpReport The PHP requirements report array
	 *
	 * @return void
	 */
	protected function checkPhpDirectives(&$phpReport) {
		if (ini_get('open_basedir')) {
			$phpReport[] = [
				'severity' => 'warning',
				'message' => elgg_echo("install:check:php:open_basedir")
			];
		}

		if (ini_get('safe_mode')) {
			$phpReport[] = [
				'severity' => 'warning',
				'message' => elgg_echo("install:check:php:safe_mode")
			];
		}

		if (ini_get('arg_separator.output') !== '&') {
			$separator = htmlspecialchars(ini_get('arg_separator.output'));
			$msg = elgg_echo("install:check:php:arg_separator", [$separator]);
			$phpReport[] = [
				'severity' => 'failure',
				'message' => $msg,
			];
		}

		if (ini_get('register_globals')) {
			$phpReport[] = [
				'severity' => 'failure',
				'message' => elgg_echo("install:check:php:register_globals")
			];
		}

		if (ini_get('session.auto_start')) {
			$phpReport[] = [
				'severity' => 'failure',
				'message' => elgg_echo("install:check:php:session.auto_start")
			];
		}
	}

	/**
	 * Confirm that the rewrite rules are firing
	 *
	 * @param array $report The requirements report array
	 *
	 * @return void
	 */
	protected function checkRewriteRules(&$report) {
		$tester = new ElggRewriteTester();
		$url = $this->config->wwwroot;
		$url .= Request::REWRITE_TEST_TOKEN . '?' . http_build_query([
				Request::REWRITE_TEST_TOKEN => '1',
		]);
		$report['rewrite'] = [$tester->run($url, Paths::project())];
	}

	/**
	 * Count the number of failures in the requirements report
	 *
	 * @param array  $report    The requirements report array
	 * @param string $condition 'failure' or 'warning'
	 *
	 * @return int
	 */
	protected function countNumConditions($report, $condition) {
		$count = 0;
		foreach ($report as $category => $checks) {
			foreach ($checks as $check) {
				if ($check['severity'] === $condition) {
					$count++;
				}
			}
		}

		return $count;
	}


	/**
	 * Database support methods
	 */

	/**
	 * Validate the variables for the database step
	 *
	 * @param array $submissionVars Submitted vars
	 * @param array $formVars       Vars in the form
	 *
	 * @return bool
	 */
	protected function validateDatabaseVars($submissionVars, $formVars) {

		foreach ($formVars as $field => $info) {
			if ($info['required'] == true && !$submissionVars[$field]) {
				$name = elgg_echo("install:database:label:$field");
				register_error(elgg_echo('install:error:requiredfield', [$name]));
				return false;
			}
		}

		// check that data root is absolute path
		if (stripos(PHP_OS, 'win') === 0) {
			if (strpos($submissionVars['dataroot'], ':') !== 1) {
				$msg = elgg_echo('install:error:relative_path', [$submissionVars['dataroot']]);
				register_error($msg);
				return false;
			}
		} else {
			if (strpos($submissionVars['dataroot'], '/') !== 0) {
				$msg = elgg_echo('install:error:relative_path', [$submissionVars['dataroot']]);
				register_error($msg);
				return false;
			}
		}

		// check that data root exists
		if (!is_dir($submissionVars['dataroot'])) {
			$msg = elgg_echo('install:error:datadirectoryexists', [$submissionVars['dataroot']]);
			register_error($msg);
			return false;
		}

		// check that data root is writable
		if (!is_writable($submissionVars['dataroot'])) {
			$msg = elgg_echo('install:error:writedatadirectory', [$submissionVars['dataroot']]);
			register_error($msg);
			return false;
		}

		if (!$this->config->data_dir_override) {
			// check that data root is not subdirectory of Elgg root
			if (stripos($submissionVars['dataroot'], $this->config->path) === 0) {
				$msg = elgg_echo('install:error:locationdatadirectory', [$submissionVars['dataroot']]);
				register_error($msg);
				return false;
			}
		}

		// according to postgres documentation: SQL identifiers and key words must
		// begin with a letter (a-z, but also letters with diacritical marks and
		// non-Latin letters) or an underscore (_). Subsequent characters in an
		// identifier or key word can be letters, underscores, digits (0-9), or dollar signs ($).
		// Refs #4994
		if (!preg_match("/^[a-zA-Z_][\w]*$/", $submissionVars['dbprefix'])) {
			register_error(elgg_echo('install:error:database_prefix'));
			return false;
		}

		return $this->checkDatabaseSettings(
			$submissionVars['dbuser'],
			$submissionVars['dbpassword'],
			$submissionVars['dbname'],
			$submissionVars['dbhost']
		);
	}

	/**
	 * Confirm the settings for the database
	 *
	 * @param string $user     Username
	 * @param string $password Password
	 * @param string $dbname   Database name
	 * @param string $host     Host
	 *
	 * @return bool
	 */
	protected function checkDatabaseSettings($user, $password, $dbname, $host) {
		$config = new DbConfig((object) [
			'dbhost' => $host,
			'dbuser' => $user,
			'dbpass' => $password,
			'dbname' => $dbname,
			'dbencoding' => 'utf8mb4',
		]);
		$db = new \Elgg\Database($config);

		try {
			$db->getDataRow("SELECT 1");
		} catch (DatabaseException $e) {
			if (0 === strpos($e->getMessage(), "Elgg couldn't connect")) {
				register_error(elgg_echo('install:error:databasesettings'));
			} else {
				register_error(elgg_echo('install:error:nodatabase', [$dbname]));
			}
			return false;
		}

		// check MySQL version
		$version = $db->getServerVersion(DbConfig::READ_WRITE);
		if (version_compare($version, '5.5.3', '<')) {
			register_error(elgg_echo('install:error:oldmysql2', [$version]));
			return false;
		}

		return true;
	}

	/**
	 * Writes the settings file to the engine directory
	 *
	 * @param array $params Array of inputted params from the user
	 *
	 * @return bool
	 */
	protected function createSettingsFile($params) {
		$template = Application::elggDir()->getContents("elgg-config/settings.example.php");
		if (!$template) {
			register_error(elgg_echo('install:error:readsettingsphp'));
			return false;
		}

		foreach ($params as $k => $v) {
			$template = str_replace("{{" . $k . "}}", $v, $template);
		}

		$result = file_put_contents(Paths::settingsFile(), $template);
		if (!$result) {
			register_error(elgg_echo('install:error:writesettingphp'));
			return false;
		}

		return true;
	}

	/**
	 * Bootstrap database connection before entire engine is available
	 *
	 * @return bool
	 */
	protected function connectToDatabase() {
		try {
			$this->services->db->setupConnections();
		} catch (DatabaseException $e) {
			register_error($e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Create the database tables
	 *
	 * @return bool
	 */
	protected function installDatabase() {
		$ret = \Elgg\Application::migrate();
		if ($ret) {
			init_site_secret();
		}
		return $ret;
	}

	/**
	 * Site settings support methods
	 */

	/**
	 * Create the data directory if requested
	 *
	 * @param array $submissionVars Submitted vars
	 * @param array $formVars       Variables in the form
	 *
	 * @return bool
	 */
	protected function createDataDirectory(&$submissionVars, $formVars) {
		// did the user have option of Elgg creating the data directory
		if ($formVars['dataroot']['type'] != 'combo') {
			return true;
		}

		// did the user select the option
		if ($submissionVars['dataroot'] != 'dataroot-checkbox') {
			return true;
		}

		$dir = sanitise_filepath($submissionVars['path']) . 'data';
		if (file_exists($dir) || mkdir($dir, 0700)) {
			$submissionVars['dataroot'] = $dir;
			if (!file_exists("$dir/.htaccess")) {
				$htaccess = "Order Deny,Allow\nDeny from All\n";
				if (!file_put_contents("$dir/.htaccess", $htaccess)) {
					return false;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Validate the site settings form variables
	 *
	 * @param array $submissionVars Submitted vars
	 * @param array $formVars       Vars in the form
	 *
	 * @return bool
	 */
	protected function validateSettingsVars($submissionVars, $formVars) {
		foreach ($formVars as $field => $info) {
			$submissionVars[$field] = trim($submissionVars[$field]);
			if ($info['required'] == true && $submissionVars[$field] === '') {
				$name = elgg_echo("install:settings:label:$field");
				register_error(elgg_echo('install:error:requiredfield', [$name]));
				return false;
			}
		}

		// check that email address is email address
		if ($submissionVars['siteemail'] && !is_email_address($submissionVars['siteemail'])) {
			$msg = elgg_echo('install:error:emailaddress', [$submissionVars['siteemail']]);
			register_error($msg);
			return false;
		}

		// @todo check that url is a url
		// @note filter_var cannot be used because it doesn't work on international urls

		return true;
	}

	/**
	 * Initialize the site including site entity, plugins, and configuration
	 *
	 * @param array $submissionVars Submitted vars
	 *
	 * @return bool
	 */
	protected function saveSiteSettings($submissionVars) {
		$site = new ElggSite();
		$site->name = strip_tags($submissionVars['sitename']);
		$site->access_id = ACCESS_PUBLIC;
		$site->email = $submissionVars['siteemail'];

		$guid = $site->save();
		if ($guid !== 1) {
			register_error(elgg_echo('install:error:createsite'));
			return false;
		}

		$this->config->site = $site;

		// new installations have run all the upgrades
		$upgrades = elgg_get_upgrade_files(Paths::elgg() . "engine/lib/upgrades/");

		$sets = [
			'installed' => time(),
			'version' => elgg_get_version(),
			'simplecache_enabled' => 1,
			'system_cache_enabled' => 1,
			'simplecache_lastupdate' => time(),
			'processed_upgrades' => $upgrades,
			'language' => 'en',
			'default_access' => $submissionVars['siteaccess'],
			'allow_registration' => false,
			'walled_garden' => false,
			'allow_user_default_access' => '',
			'default_limit' => 10,
			'security_protect_upgrade' => true,
			'security_notify_admins' => true,
			'security_notify_user_password' => true,
			'security_email_require_password' => true,
		];
		foreach ($sets as $key => $value) {
			elgg_save_config($key, $value);
		}

		// Enable a set of default plugins
		_elgg_generate_plugin_entities();

		foreach (elgg_get_plugins('any') as $plugin) {
			if ($plugin->getManifest()) {
				if ($plugin->getManifest()->getActivateOnInstall()) {
					$plugin->activate();
				}
				if (in_array('theme', $plugin->getManifest()->getCategories())) {
					$plugin->setPriority('last');
				}
			}
		}

		return true;
	}

	/**
	 * Admin account support methods
	 */

	/**
	 * Validate account form variables
	 *
	 * @param array $submissionVars Submitted vars
	 * @param array $formVars       Form vars
	 *
	 * @return bool
	 */
	protected function validateAdminVars($submissionVars, $formVars) {

		foreach ($formVars as $field => $info) {
			if ($info['required'] == true && !$submissionVars[$field]) {
				$name = elgg_echo("install:admin:label:$field");
				register_error(elgg_echo('install:error:requiredfield', [$name]));
				return false;
			}
		}

		if ($submissionVars['password1'] !== $submissionVars['password2']) {
			register_error(elgg_echo('install:admin:password:mismatch'));
			return false;
		}

		if (trim($submissionVars['password1']) == "") {
			register_error(elgg_echo('install:admin:password:empty'));
			return false;
		}

		$minLength = $this->services->configTable->get('min_password_length');
		if (strlen($submissionVars['password1']) < $minLength) {
			register_error(elgg_echo('install:admin:password:tooshort'));
			return false;
		}

		// check that email address is email address
		if ($submissionVars['email'] && !is_email_address($submissionVars['email'])) {
			$msg = elgg_echo('install:error:emailaddress', [$submissionVars['email']]);
			register_error($msg);
			return false;
		}

		return true;
	}

	/**
	 * Create a user account for the admin
	 *
	 * @param array $submissionVars Submitted vars
	 * @param bool  $login          Login in the admin user?
	 *
	 * @return bool
	 */
	protected function createAdminAccount($submissionVars, $login = false) {
		try {
			$guid = register_user(
				$submissionVars['username'],
				$submissionVars['password1'],
				$submissionVars['displayname'],
				$submissionVars['email']
			);
		} catch (Exception $e) {
			register_error($e->getMessage());
			return false;
		}

		if (!$guid) {
			register_error(elgg_echo('install:admin:cannot_create'));
			return false;
		}

		$user = get_entity($guid);
		if (!$user instanceof ElggUser) {
			register_error(elgg_echo('install:error:loadadmin'));
			return false;
		}

		elgg_set_ignore_access(true);
		if ($user->makeAdmin() == false) {
			register_error(elgg_echo('install:error:adminaccess'));
		} else {
			$this->services->configTable->set('admin_registered', 1);
		}
		elgg_set_ignore_access(false);

		// add validation data to satisfy user validation plugins
		$user->validated = 1;
		$user->validated_method = 'admin_user';

		if (!$login) {
			return true;
		}

		$session = ElggSession::fromDatabase($this->config, $this->services->db);
		$session->start();
		$this->services->setValue('session', $session);
		if (login($user) == false) {
			register_error(elgg_echo('install:error:adminlogin'));
		}

		return true;
	}
}
