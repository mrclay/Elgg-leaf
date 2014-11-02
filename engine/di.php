<?php
namespace Elgg;

use DI;
use DI\Container;
use Elgg\Queue\DatabaseQueue;
use Elgg\Notifications\NotificationsService;

return array(
	'access' => DI\link('Elgg\Access'),
	'accessCache' => DI\factory(function(Container $c) {
		return new \ElggStaticVariableCache('access');
	}),
	'accessCollections' => DI\factory(function(Container $c) {
		$guid = $c->get('config')->get('site_guid');
		return new \Elgg\Database\AccessCollections($guid);
	}),
	'autoloadManager' => DI\link('Elgg\AutoloadManager'),
	'actions' => DI\link('Elgg\ActionsService'),
	'adminNotices' => DI\link('Elgg\Database\AdminNotices'),
	'amdConfig' => DI\link('Elgg\AmdConfig'),
	'annotations' => DI\link('Elgg\Database\Annotations'),
	'autoP' => DI\link('ElggAutoP'),
	'config' => DI\link('Elgg\Config'),
	'configTable' => DI\link('Elgg\Database\ConfigTable'),
	'context' => DI\link('Elgg\Context'),
	'cookies' => DI\factory(function() {
		return \elgg_get_config('cookies');
	}),
	'crypto' => DI\link('ElggCrypto'),
	'datalist' => DI\factory(function(Container $c) {
		// TODO(ewinslow): Add back memcached support
		$db = $c->get('db');
		$dbprefix = $db->getTablePrefix();
		$pool = new \Elgg\Cache\MemoryPool();
		return new \Elgg\Database\Datalist($pool, $db, $c->get('logger'), "{$dbprefix}datalists");
	}),
	'dataroot' => DI\factory(function(Container $c) {
		return $c->get('config')->get('dataroot');
	}),
	'db' => DI\factory(function(Container $c) {
		global $CONFIG;
		$db_config = new \Elgg\Database\Config($CONFIG);
		return new \Elgg\Database($db_config, $c->get('logger'));
	}),
	'entityTable' => DI\link('Elgg\Database\EntityTable'),
	'events' => DI\factory(function(Container $c) {
		return $c->get('loggerDependencies')['events'];
	}),
	'externalFiles' => DI\link('Elgg\Assets\ExternalFiles'),
	'hooks' => DI\factory(function(Container $c) {
		return $c->get('loggerDependencies')['hooks'];
	}),
	'input' => DI\link('Elgg\Http\Input'),
	'jsroot' => DI\factory(function(Container $c) {
		return \_elgg_get_simplecache_root() . "js/";
	}),
	'logger' => DI\factory(function(Container $c) {
		return $c->get('loggerDependencies')['logger'];
	}),
	'loggerDependencies' => DI\factory(function(Container $c) {
		$svcs['hooks'] = new \Elgg\PluginHooksService();
		$svcs['logger'] = new \Elgg\Logger($svcs['hooks']);
		$svcs['hooks']->setLogger($svcs['logger']);
		$svcs['events'] = new \Elgg\EventsService();
		$svcs['events']->setLogger($svcs['logger']);
		return $svcs;
	}),
	'metadataCache' => DI\link('ElggVolatileMetadataCache'),
	'metadataTable' => DI\factory(function(Container $c) {
		// TODO(ewinslow): Use Elgg\Cache\Pool instead of MetadataCache
		return new \Elgg\Database\MetadataTable(
			$c->get('metadataCache'), $c->get('db'), $c->get('entityTable'),
			$c->get('events'), $c->get('metastringsTable'), $c->get('session'));
	}),
	'metastringsTable' => DI\factory(function(Container $c) {
		// TODO(ewinslow): Use memcache-based Pool if available...
		$pool = new \Elgg\Cache\MemoryPool();
		return new \Elgg\Database\MetastringsTable($pool, $c->get('db'));
	}),
	'notifications' => DI\factory(function(Container $c) {
		// @todo move queue in service provider
		$queue_name = \Elgg\Notifications\NotificationsService::QUEUE_NAME;
		$queue = new \Elgg\Queue\DatabaseQueue($queue_name, $c->get('db'));
		$sub = new \Elgg\Notifications\SubscriptionsService($c->get('db'));
		return new \Elgg\Notifications\NotificationsService($sub, $queue, $c->get('hooks'), $c->get('access'));
	}),
	'ownerPreloader' => DI\factory(function(Container $c) {
		return new \Elgg\EntityPreloader(array('owner_guid'));
	}),
	'persistentLogin' => DI\link('Elgg\PersistentLoginService'),
	'plugins' => DI\link('Elgg\Database\Plugins'),
	'queryCounter' => DI\factory(function(Container $c) {
		return new \Elgg\Database\QueryCounter($c->get('db'));
	}),
	'relationshipsTable' => DI\link('Elgg\Database\RelationshipsTable'),
	'request' => DI\link('Elgg\Http\Request'),
	'router' => DI\factory(function(Container $c) {
		// TODO(evan): Init routes from plugins or cache
		return new \Elgg\Router($c->get('hooks'));
	}),
	'server' => DI\factory(function(Container $c) {
		return $c->get('request')->server;
	}),
	'session' => DI\factory(function(Container $c) {
		// account for difference of session_get_cookie_params() and ini key names
		$params = $c->get('config')->get('session');
		foreach ($params as $key => $value) {
			if (in_array($key, array('path', 'domain', 'secure', 'httponly'))) {
				$params["cookie_$key"] = $value;
				unset($params[$key]);
			}
		}
		$handler = new \Elgg\Http\DatabaseSessionHandler($c->get('db'));
		$storage = new \Elgg\Http\NativeSessionStorage($params, $handler);
		return new \ElggSession($storage);
	}),
	'simpleCache' => DI\link('Elgg\Cache\SimpleCache'),
	'siteSecret' => DI\link('Elgg\Database\SiteSecret'),
	'stickyForms' => DI\link('Elgg\Forms\StickyForms'),
	'subtypeTable' => DI\link('Elgg\Database\SubtypeTable'),

	'systemCache' => DI\link('Elgg\Cache\SystemCache'),
//	'systemCache' => DI\factory(function(Container $c) {
//		$systemCache = new \ElggFileCache($c->get('dataroot') . "system_cache/");
//
//		// TODO(ewinslow): Move this to the autoloadManager factory once timing issues are resolved
//		$manager = $c->get('Elgg\AutoloadManager');
//		$manager->setStorage($systemCache);
//		$manager->loadCache();
//
//		return $systemCache;
//	}),

	'translator' => DI\link('Elgg\I18n\Translator'),
	'usersTable' => DI\link('Elgg\Database\UsersTable'),
	'views' => DI\factory(function(Container $c) {
		return new \Elgg\ViewsService($c->get('hooks'), $c->get('logger'));
	}),
	'widgets' => DI\link('Elgg\WidgetsService'),


	'Elgg\AmdConfig' => DI\object()->method('setBaseUrl', DI\link('jsroot')),
	'Elgg\ClassLoader' => DI\object()->method('register'),

	'Elgg\Database' => DI\factory(function(Container $c) {
		global $CONFIG;
		$db_config = new \Elgg\Database\Config($CONFIG);
		return new \Elgg\Database($db_config, $c->get('logger'));
	}),

	// probably wrong, config was changed
	//'Elgg\Database\Config' => DI\object()->constructor(DI\link('config')),

	'Elgg\Http\Request' => DI\factory(function() {
		return Http\Request::createFromGlobals();
	}),

//	'Elgg\Http\SessionStorage' => DI\factory(function(Container $c) {
//		$cookies = $c->get('cookies');
//
//		// account for difference of session_get_cookie_params() and ini key names
//		$params = $cookies['session'];
//		foreach ($params as $key => $value) {
//			if (in_array($key, array('path', 'domain', 'secure', 'httponly'))) {
//				$params["cookie_$key"] = $value;
//				unset($params[$key]);
//			}
//		}
//
//		return new Http\NativeSessionStorage($params, $c->get('Elgg\Http\DatabaseSessionHandler'));
//	}),

	'Elgg\PersistentLoginService' => DI\factory(function(Container $c) {
		$global_cookies_config = $c->get('cookies');
		$cookie_config = $global_cookies_config['remember_me'];
		$cookie_name = $cookie_config['name'];
		$cookie_token = $c->get('request')->cookies->get($cookie_name, '');
		return new \Elgg\PersistentLoginService(
			$c->get('db'), $c->get('session'), $c->get('crypto'), $cookie_config, $cookie_token);
	}),

	'Elgg\Queue\Queue' => DI\factory(function(Container $c) {
		return new DatabaseQueue(NotificationsService::QUEUE_NAME, $c->get('Elgg\Database'));
	}),
);