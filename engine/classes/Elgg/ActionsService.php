<?php
namespace Elgg;

use Elgg\Http\Input;
use Elgg\Http\ResponseBuilder;
use ElggCrypto;
use ElggSession;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * Use the elgg_* versions instead.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Actions
 * @since      1.9.0
 */
class ActionsService {

	use \Elgg\TimeUsing;
	
	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var ElggSession
	 */
	private $session;

	/**
	 * @var ElggCrypto
	 */
	private $crypto;

	/**
	 * @var HandlersService
	 */
	private $handlers;

	/**
	 * @var Input
	 */
	private $input;

	/**
	 * Registered actions storage
	 *
	 * Each element has keys:
	 *   "file" => filename
	 *   "access" => access level
	 *
	 * @var array
	 */
	private $actions = [];

	/**
	 * The current action being processed
	 * @var string
	 */
	private $currentAction = null;

	/**
	 * @var string[]
	 */
	private static $access_levels = ['public', 'logged_in', 'admin'];

	/**
	 * Constructor
	 *
	 * @param Config      $config  Config
	 * @param ElggSession $session Session
	 * @param ElggCrypto  $crypto  Crypto service
	 */
	public function __construct(
		Config $config,
		ElggSession $session,
		ElggCrypto $crypto,
		HandlersService $handlers,
		Input $input
	) {
		$this->config = $config;
		$this->session = $session;
		$this->crypto = $crypto;
		$this->handlers = $handlers;
		$this->input = $input;
	}

	/**
	 * Executes an action
	 * If called from action() redirect will be issued by the response factory
	 * If called as /action page handler response will be handled by \Elgg\Router
	 *
	 * @param string $action    Action name
	 * @param string $forwarder URL to forward to after completion
	 * @return ResponseBuilder|null
	 * @see action()
	 * @access private
	 */
	public function execute($action, $forwarder = "") {
		$action = rtrim($action, '/');
		$this->currentAction = $action;
		
		// Logout is for convenience.
		$exceptions = [
			'logout',
		];
	
		if (!in_array($action, $exceptions)) {
			// All actions require a token.
			$pass = $this->gatekeeper($action);
			if (!$pass) {
				return;
			}
		}
	
		$forwarder = str_replace($this->config->wwwroot, "", $forwarder);
		$forwarder = str_replace("http://", "", $forwarder);
		$forwarder = str_replace("@", "", $forwarder);
		if (substr($forwarder, 0, 1) == "/") {
			$forwarder = substr($forwarder, 1);
		}

		$ob_started = false;

		/**
		 * Prepare action response
		 *
		 * @param string $error_key   Error message key
		 * @param int    $status_code HTTP status code
		 * @return ResponseBuilder
		 */
		$forward = function ($error_key = '', $status_code = ELGG_HTTP_OK) use ($action, $forwarder, &$ob_started) {
			if ($error_key) {
				if ($ob_started) {
					ob_end_clean();
				}
				$msg = _elgg_services()->translator->translate($error_key, [$action]);
				_elgg_services()->systemMessages->addErrorMessage($msg);
				$response = new \Elgg\Http\ErrorResponse($msg, $status_code);
			} else {
				$content = ob_get_clean();
				$response = new \Elgg\Http\OkResponse($content, $status_code);
			}
			
			$forwarder = empty($forwarder) ? REFERER : $forwarder;
			$response->setForwardURL($forwarder);
			return $response;
		};

		if (!isset($this->actions[$action])) {
			return $forward('actionundefined', ELGG_HTTP_NOT_IMPLEMENTED);
		}

		$user = $this->session->getLoggedInUser();

		// access checks
		switch ($this->actions[$action]['access']) {
			case 'public':
				break;
			case 'logged_in':
				if (!$user) {
					return $forward('actionloggedout', ELGG_HTTP_FORBIDDEN);
				}
				break;
			default:
				// admin or misspelling
				if (!$user || !$user->isAdmin()) {
					return $forward('actionunauthorized', ELGG_HTTP_FORBIDDEN);
				}
		}

		ob_start();
		
		// To quietly cancel the file, return a falsey value in the "action" hook.
		if (!_elgg_services()->hooks->trigger('action', $action, null, true)) {
			return $forward('', ELGG_HTTP_OK);
		}

		// set the maximum execution time for actions
		$action_timeout = $this->config->action_time_limit;
		if (isset($action_timeout)) {
			set_time_limit($action_timeout);
		}

		$callback = $this->actions[$action]['callback'];
		if ($callback) {
			list($success, $result, $object) = $this->handlers->call($callback, 'action', [$action, $this->input]);
		} else {
			$result = Includer::includeFile($this->actions[$action]['file']);
		}

		if ($result instanceof ResponseBuilder) {
			ob_end_clean();
			return $result;
		}

		return $forward('', ELGG_HTTP_OK);
	}

	/**
	 * Registers an action
	 *
	 * @param string          $action  The name of the action (eg "register", "account/settings/save")
	 * @param callable|string $handler Action callback or filename of the action file
	 *                                 If not specified, will assume the action is in elgg/actions/<action>.php
	 * @param string          $access  Who is allowed to execute this action: public, logged_in, admin.
	 *                                 (default: logged_in)
	 *
	 * @return bool
	 *
	 * @see    elgg_register_action()
	 * @access private
	 */
	public function register($action, $handler = "", $access = 'logged_in') {
		// plugins are encouraged to call actions with a trailing / to prevent 301
		// redirects but we store the actions without it
		$action = rtrim($action, '/');

		if (empty($handler)) {
			$path = __DIR__ . '/../../../actions';
			$handler = realpath("$path/$action.php");
		}

		$file = false;
		$callback = false;

		if (is_string($handler) && substr($handler, -4, 4) === '.php') {
			if (!is_file($handler) || !is_readable($handler)) {
				elgg_log("File $handler for action $action is not readable", 'ERROR');
				return false;
			}
			$file = $handler;
		} else {
			$handler = $this->handlers->resolveCallable($handler);
			if (!is_callable($handler)) {
				elgg_log("Handler $handler for action $action is not callable", 'ERROR');
				return false;
			}
			$callback = $handler;
		}

		if (!in_array($access, self::$access_levels)) {
			_elgg_services()->logger->error("Unrecognized value '$access' for \$access in " . __METHOD__);
			$access = 'admin';
		}

		$this->actions[$action] = [
			'file' => $file,
			'callback' => $callback,
			'access' => $access,
		];

		return true;
	}
	
	/**
	 * Unregisters an action
	 *
	 * @param string $action Action name
	 *
	 * @return bool
	 *
	 * @see elgg_unregister_action()
	 * @access private
	 */
	public function unregister($action) {
		if (isset($this->actions[$action])) {
			unset($this->actions[$action]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validate an action token.
	 *
	 * Calls to actions will automatically validate tokens. If tokens are not
	 * present or invalid, the action will be denied and the user will be redirected.
	 *
	 * Plugin authors should never have to manually validate action tokens.
	 *
	 * @param bool  $visible_errors Emit {@link register_error()} errors on failure?
	 * @param mixed $token          The token to test against. Default: $_REQUEST['__elgg_token']
	 * @param mixed $ts             The time stamp to test against. Default: $_REQUEST['__elgg_ts']
	 *
	 * @return bool
	 *
	 * @see validate_action_token()
	 * @access private
	 */
	public function validateActionToken($visible_errors = true, $token = null, $ts = null) {
		if (!$token) {
			$token = get_input('__elgg_token');
		}
	
		if (!$ts) {
			$ts = get_input('__elgg_ts');
		}

		$session_id = $this->session->getId();

		if (($token) && ($ts) && ($session_id)) {
			if ($this->validateTokenOwnership($token, $ts)) {
				if ($this->validateTokenTimestamp($ts)) {
					// We have already got this far, so unless anything
					// else says something to the contrary we assume we're ok
					$returnval = _elgg_services()->hooks->trigger('action_gatekeeper:permissions:check', 'all', [
						'token' => $token,
						'time' => $ts
					], true);

					if ($returnval) {
						return true;
					} else if ($visible_errors) {
						register_error(_elgg_services()->translator->translate('actiongatekeeper:pluginprevents'));
					}
				} else if ($visible_errors) {
					// this is necessary because of #5133
					if (elgg_is_xhr()) {
						register_error(_elgg_services()->translator->translate(
							'js:security:token_refresh_failed',
							[$this->config->wwwroot]
						));
					} else {
						register_error(_elgg_services()->translator->translate('actiongatekeeper:timeerror'));
					}
				}
			} else if ($visible_errors) {
				// this is necessary because of #5133
				if (elgg_is_xhr()) {
					register_error(_elgg_services()->translator->translate('js:security:token_refresh_failed', [$this->config->wwwroot]));
				} else {
					register_error(_elgg_services()->translator->translate('actiongatekeeper:tokeninvalid'));
				}
			}
		} else {
			$req = _elgg_services()->request;
			$length = $req->server->get('CONTENT_LENGTH');
			$post_count = count($req->request);
			if ($length && $post_count < 1) {
				// The size of $_POST or uploaded file has exceed the size limit
				$error_msg = _elgg_services()->hooks->trigger('action_gatekeeper:upload_exceeded_msg', 'all', [
					'post_size' => $length,
					'visible_errors' => $visible_errors,
				], _elgg_services()->translator->translate('actiongatekeeper:uploadexceeded'));
			} else {
				$error_msg = _elgg_services()->translator->translate('actiongatekeeper:missingfields');
			}
			if ($visible_errors) {
				register_error($error_msg);
			}
		}

		return false;
	}

	/**
	 * Is the token timestamp within acceptable range?
	 *
	 * @param int $ts timestamp from the CSRF token
	 *
	 * @return bool
	 */
	protected function validateTokenTimestamp($ts) {
		$timeout = $this->getActionTokenTimeout();
		$now = $this->getCurrentTime()->getTimestamp();
		return ($timeout == 0 || ($ts > $now - $timeout) && ($ts < $now + $timeout));
	}

	/**
	 * Returns the action token timeout in seconds
	 *
	 * @return int number of seconds that action token is valid
	 *
	 * @see ActionsService::validateActionToken
	 * @access private
	 * @since 1.9.0
	 */
	public function getActionTokenTimeout() {
		if (($timeout = $this->config->action_token_timeout) === null) {
			// default to 2 hours
			$timeout = 2;
		}
		$hour = 60 * 60;
		return (int) ((float) $timeout * $hour);
	}

	/**
	 * Validates the presence of action tokens.
	 *
	 * This function is called for all actions.  If action tokens are missing,
	 * the user will be forwarded to the site front page and an error emitted.
	 *
	 * This function verifies form input for security features (like a generated token),
	 * and forwards if they are invalid.
	 *
	 * @param string $action The action being performed
	 *
	 * @return bool
	 *
	 * @see action_gatekeeper()
	 * @access private
	 */
	public function gatekeeper($action) {
		if ($action === 'login') {
			if ($this->validateActionToken(false)) {
				return true;
			}

			$token = get_input('__elgg_token');
			$ts = (int) get_input('__elgg_ts');
			if ($token && $this->validateTokenTimestamp($ts)) {
				// The tokens are present and the time looks valid: this is probably a mismatch due to the
				// login form being on a different domain.
				register_error(_elgg_services()->translator->translate('actiongatekeeper:crosssitelogin'));
				_elgg_services()->responseFactory->redirect('login', 'csrf');
				return false;
			}
		}
		
		if ($this->validateActionToken()) {
			return true;
		}
			
		_elgg_services()->responseFactory->redirect(REFERER, 'csrf');
		return false;
	}

	/**
	 * Was the given token generated for the session defined by session_token?
	 *
	 * @param string $token         CSRF token
	 * @param int    $timestamp     Unix time
	 * @param string $session_token Session-specific token
	 *
	 * @return bool
	 * @access private
	 */
	public function validateTokenOwnership($token, $timestamp, $session_token = '') {
		$required_token = $this->generateActionToken($timestamp, $session_token);

		return _elgg_services()->crypto->areEqual($token, $required_token);
	}
	
	/**
	 * Generate a token from a session token (specifying the user), the timestamp, and the site key.
	 *
	 * @see generate_action_token()
	 *
	 * @param int    $timestamp     Unix timestamp
	 * @param string $session_token Session-specific token
	 *
	 * @return string
	 * @access private
	 */
	public function generateActionToken($timestamp, $session_token = '') {
		if (!$session_token) {
			$session_token = elgg_get_session()->get('__elgg_session');
			if (!$session_token) {
				return false;
			}
		}

		return _elgg_services()->hmac->getHmac([(int) $timestamp, $session_token], 'md5')
			->getToken();
	}
	
	/**
	 * Check if an action is registered and its script exists.
	 *
	 * @param string $action Action name
	 *
	 * @return bool
	 *
	 * @see elgg_action_exists()
	 * @access private
	 */
	public function exists($action) {
		return (isset($this->actions[$action]) && file_exists($this->actions[$action]['file']));
	}
	
	/**
	 * Get all actions
	 *
	 * @return array
	 */
	public function getAllActions() {
		return $this->actions;
	}

	/**
	 * Send an updated CSRF token, provided the page's current tokens were not fake.
	 *
	 * @return ResponseBuilder
	 * @access private
	 */
	public function handleTokenRefreshRequest() {
		if (!elgg_is_xhr()) {
			return false;
		}

		// the page's session_token might have expired (not matching __elgg_session in the session), but
		// we still allow it to be given to validate the tokens in the page.
		$session_token = get_input('session_token', null, false);
		$pairs = (array) get_input('pairs', [], false);
		$valid_tokens = (object) [];
		foreach ($pairs as $pair) {
			list($ts, $token) = explode(',', $pair, 2);
			if ($this->validateTokenOwnership($token, $ts, $session_token)) {
				$valid_tokens->{$token} = true;
			}
		}

		$ts = $this->getCurrentTime()->getTimestamp();
		$token = $this->generateActionToken($ts);
		$data = [
			'token' => [
				'__elgg_ts' => $ts,
				'__elgg_token' => $token,
				'logged_in' => $this->session->isLoggedIn(),
			],
			'valid_tokens' => $valid_tokens,
			'session_token' => $this->session->get('__elgg_session'),
			'user_guid' => $this->session->getLoggedInUserGuid(),
		];

		elgg_set_http_header("Content-Type: application/json;charset=utf-8");
		return elgg_ok_response($data);
	}
}

