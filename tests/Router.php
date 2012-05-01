<?php

namespace ReflectionRouter;

require_once '../ReflectionRouter.php';
require_once 'RegisterExample.php';
require_once 'MessageExample.php';

class RouterTest extends \PHPUnit_Framework_TestCase {
	private $router;

	public function setUp() {
		$this->router = new Router('module', 'action', 'ReflectionRouter');
	}

	public function testModuleNotSpecified() {
		$this->setExpectedException('ReflectionRouter\\ModuleNotSpecifiedException');
		$this->router->dispatch(array());
	}

	public function testActionNotSpecified() {
		$this->setExpectedException('ReflectionRouter\\ActionNotSpecifiedException');
		$this->router->dispatch(array(
			'module' => 'MODULE',
		));
	}

	public function testSuccess() {
		$result = $this->router->dispatch(array(
			'module' => 'RegisterExample',
			'action' => 'submit',
			'email' => 'mjac@mjac.co.uk',
		));
		$this->assertInstanceOf('ReflectionRouter\\EmailAddressExample', $result);
		$this->assertSame('mjac@mjac.co.uk', $result->email);
	}
}

