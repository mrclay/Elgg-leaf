<?php
namespace Elgg;

use Elgg\Http\Request;
use Elgg\Http\ResponseFactory;
use PHPUnit_Framework_TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group HttpService
 */
class RouterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var PluginHooksService
	 */
	protected $hooks;

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var string
	 */
	protected $pages;

	/**
	 * @var int
	 */
	protected $fooHandlerCalls = 0;

	function setUp() {		
		$this->hooks = new PluginHooksService();
		$this->router = new Router($this->hooks);
		$this->pages = dirname(dirname(__FILE__)) . '/test_files/pages';
		$this->fooHandlerCalls = 0;

		// reset response factory
		_elgg_services()->setValue('responseFactory', new ResponseFactory(_elgg_services()->request, $this->hooks, _elgg_services()->ajax));
	}

	function hello_page_handler($segments, $identifier) {
		include "{$this->pages}/hello.php";
		
		return true;
	}

	function testCanRegisterFunctionsAsPageHandlers() {
		$registered = $this->router->registerPageHandler('hello', array($this, 'hello_page_handler'));
		
		$this->assertTrue($registered);

		$path = "hello/1/\xE2\x82\xAC"; // euro sign
		
		$handled = $this->router->route(_elgg_testing_request($path));
		$this->assertTrue($handled);

		$response = _elgg_services()->responseFactory->getSentResponse();
		$this->assertInstanceOf(Response::class, $response);

		$this->assertEquals($path, $response->getContent());

		$this->assertEquals(array(
			'hello' => array($this, 'hello_page_handler')
		), $this->router->getPageHandlers());
	}

	function testFailToRegisterInvalidCallback() {
		$registered = $this->router->registerPageHandler('hello', new stdClass());

		$this->assertFalse($registered);
	}
	
	function testCanUnregisterPageHandlers() {
		$this->router->registerPageHandler('hello', array($this, 'hello_page_handler'));
		$this->router->unregisterPageHandler('hello');
		
		ob_start();
		$handled = $this->router->route(_elgg_testing_request('hello'));
		$output = ob_get_clean();

		$response = _elgg_services()->responseFactory->getSentResponse();
		$this->assertFalse($response);

		// Normally we would expect the router to return false for this request,
		// but since it checks for headers_sent() and PHPUnit issues output before
		// this test runs, the headers have already been sent. It's enough to verify
		// that the output we buffered is empty.
		// $this->assertFalse($handled);
		$this->assertEmpty($output);
	}

	/**
	 * 1. Register a page handler for `/foo`
	 * 2. Register a plugin hook that uses the "handler" result param
	 *    to route all `/bar/*` requests to the `/foo` handler.
	 * 3. Route a request for a `/bar` page.
	 * 4. Check that the `/foo` handler was called.
	 */
	function testRouteSupportsSettingHandlerInHookResultForBackwardsCompatibility() {
		$this->router->registerPageHandler('foo', array($this, 'foo_page_handler'));
		$this->hooks->registerHandler('route', 'bar', array($this, 'bar_route_handler'));

		ob_start();
		$this->router->route(_elgg_testing_request('bar/baz'));
		ob_end_clean();
		
		$this->assertEquals(1, $this->fooHandlerCalls);

		$response = _elgg_services()->responseFactory->getSentResponse();
		$this->assertInstanceOf(Response::class, $response);
	}

	/**
	 * 1. Register a page handler for `/foo`
	 * 2. Register a plugin hook that uses the "handler" result param
	 *    to route all `/bar/*` requests to the `/foo` handler.
	 * 3. Route a request for a `/bar` page.
	 * 4. Check that the `/foo` handler was called.
	 */
	function testRouteSupportsSettingIdentifierInHookResultForBackwardsCompatibility() {
		$this->router->registerPageHandler('foo', array($this, 'foo_page_handler'));
		$this->hooks->registerHandler('route', 'bar', array($this, 'bar_route_identifier'));

		ob_start();
		$this->router->route(_elgg_testing_request('bar/baz'));
		ob_end_clean();

		$this->assertEquals(1, $this->fooHandlerCalls);

		$response = _elgg_services()->responseFactory->getSentResponse();
		$this->assertInstanceOf(Response::class, $response);

	}

	function testRouteOverridenFromHook() {
		$this->router->registerPageHandler('foo', array($this, 'foo_page_handler'));
		$this->hooks->registerHandler('route', 'foo', array($this, 'bar_route_override'));

		ob_start();
		$this->router->route(_elgg_testing_request('foo'));
		$result = ob_get_contents();
		ob_end_clean();

		$this->assertEquals("Page handler override from hook", $result);
		$this->assertEquals(0, $this->fooHandlerCalls);

		$response = _elgg_services()->responseFactory->getSentResponse();
		$this->assertInstanceOf(Response::class, $response);

		$this->assertEquals("Page handler override from hook", $response->getContent());
	}
	
	function foo_page_handler() {
		$this->fooHandlerCalls++;
		return true;
	}
	
	function bar_route_handler($hook, $type, $value, $params) {
		$value['handler'] = 'foo';
		return $value;
	}

	function bar_route_identifier($hook, $type, $value, $params) {
		$value['identifier'] = 'foo';
		return $value;
	}

	function bar_route_override($hook, $type, $value, $params) {
		echo "Page handler override from hook";
		return false;
	}
}

