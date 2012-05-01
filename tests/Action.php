<?php

namespace ReflectionRouter;

require_once '../ReflectionRouter.php';
require_once 'RegisterExample.php';
require_once 'MessageExample.php';

class ActionTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		$this->action = new Action('ReflectionRouter');
	}

	public function testActionSuccess() {
		$result = $this->action->perform('MessageExample', 'compose', array(
			'ownerId' => 1,
			'title' => 'TITLE',
			'message' => 'MESSAGE',
		));
		$this->assertSame('TITLE: MESSAGE', $result);
	}

	public function testModuleNotFound() {
		$this->setExpectedException('ReflectionRouter\\ModuleNotFoundException');
		$this->action->perform('missing', 'doesnotmatter');
	}

	public function testModuleParamsIncorrect() {
		$this->setExpectedException('ReflectionRouter\\ModuleParamsIncorrectException');
		$this->action->perform('MessageExample', 'doesnotmatter');
	}

	public function testActionNotFound() {
		$this->setExpectedException('ReflectionRouter\\ActionNotFoundException');
		$this->action->perform('MessageExample', 'doesnotmatter', array(
			'ownerId' => 'valid',
		));
	}

	public function testActionParamsIncorrect() {
		$this->setExpectedException('ReflectionRouter\\ActionParamsIncorrectException');
		$this->action->perform('MessageExample', 'compose', array(
			'ownerId' => 'valid',
		));
	}

	public function testNamespaceNonAlphanumeric() {
		$this->setExpectedException('ReflectionRouter\\ModuleNotFoundException');
		$action = new Action('^\\\\#"~`"""');
		$action->perform('test', 'action');
	}

	public function testAccessOutsideNamespace() {
		$this->setExpectedException('ReflectionRouter\\ModuleNotFoundException');
		$action = new Action('\\private');
		$action->perform('..\\ReflectionRouter\\MessageExample', 'action');
	}
}
