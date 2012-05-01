<?php

namespace ReflectionRouter;

require_once '../ReflectionRouter.php';
require_once 'MessageExample.php';

class InputTest extends \PHPUnit_Framework_TestCase {
	private $moduleParam;
	private $actionParam;

	public function testSuccess() {
		$input = new Input($this->moduleParam, $this->actionParam);
		$input->dispatch('MessageExample');
	}
}
