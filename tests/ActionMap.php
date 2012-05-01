<?php

namespace ReflectionRouter;

require_once '../ReflectionRouter.php';
require_once 'RegisterExample.php';
require_once 'MessageExample.php';

class ActionMapTest extends \PHPUnit_Framework_TestCase {
	public function testClassMissing() {
		$this->setExpectedException('ReflectionException');
		$actionMap = new ActionMap('doesnotexist');
	}

	public function testClassExistsRegister() {
		return new ActionMap('ReflectionRouter\RegisterExample');
	}

	public function testClassExistsMessage() {
		return new ActionMap('ReflectionRouter\MessageExample');
	}

	/**
	 * @depends testClassExistsRegister
	 */
	public function testModuleEmptyConstructor(ActionMap $actionMap) {
		$params = $actionMap->getModuleParams();
		$this->assertInternalType('array', $params);
		$this->assertEmpty($params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testModuleFail(ActionMap $actionMap) {
		$params = $actionMap->getModuleParams();
		$this->assertNull($params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testModuleParam(ActionMap $actionMap) {
		$params = $actionMap->getModuleParams(array(
			'ownerId' => 6,
		));

		$this->assertInternalType('array', $params);
		$this->assertArrayHasKey('ownerId', $params);
		$this->assertSame($params['ownerId'], 6);
	}


	// BASIC ACTIONS

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionMissing(ActionMap $actionMap) {
		$this->setExpectedException('ReflectionException');
		$actionMap->getActionParams('doesnotexist');
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionEmptySuccess(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('related');
		$this->assertInternalType('array', $params);
		$this->assertEmpty($params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionEmptyFail(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('compose');
		$this->assertNull($params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionUnnecessaryInput(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('compose', array(
			'title' => 'TITLE',
			'message' => 'MESSAGE',
			'notused' => 'NOTUSED',
		));

		$this->assertSame(array(
			'title' => 'TITLE',
			'message' => 'MESSAGE',	
		), $params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionDefaultNecessary(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('delete');

		$this->assertSame(array(
			'id' => NULL,
		), $params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionDefaultUnncessary(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('delete', array(
			'id' => 5,
		));

		$this->assertSame(array(
			'id' => 5,
		), $params);
	}
	
	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionCorrectOrder(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('compose', array(
			'message' => 'm',
			'title' => 't',
		));

		$this->assertSame(array(
			'title' => 't',
			'message' => 'm',
		), $params);
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionNativeClassHint(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('load', array(
			'file' => 'ActionMap.php',
		));

		$this->assertInternalType('array', $params);
		$this->assertArrayHasKey('file', $params);
		$this->assertInstanceOf('\\SplFileInfo', $params['file']);
		$this->assertSame('ActionMap.php', $params['file']->getPathname());
	}

	/**
	 * @depends testClassExistsMessage
	 */
	public function testActionNativeClassHintDefault(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('load');

		$this->assertSame(array(
			'file' => NULL,
		), $params);
	}

	// TYPE CASTING
	
	/**
	 * @depends testClassExistsRegister
	 */
	public function testTypeCastSuccess(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('name', array(
			'name' => 1337,
		));
		$this->assertSame(array(
			'name' => '1337',
		), $params);
	}

	/**
	 * @depends testClassExistsRegister
	 */
	public function testNoTypeCast(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('lastname', array(
			'lastname' => 1337,
		));
		$this->assertSame(array(
			'lastname' => 1337,
		), $params);
	}


	// PARAM INTERFACE

	/**
	 * @depends testClassExistsRegister
	 */
	public function testParamValid(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('submit', array(
			'email' => 'mjac@mjac.co.uk',
		));
		
		$this->assertInternalType('array', $params);
		$this->assertArrayHasKey('email', $params);
		$this->assertInstanceOf('ReflectionRouter\\EmailAddressExample', $params['email']);
		$this->assertTrue($params['email']->isValid());
	}

	/**
	 * @depends testClassExistsRegister
	 */
	public function testParamInvalid(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('submit', array(
			'email' => 'notvalid',
		));
		
		$this->assertNull($params);
	}

	/**
	 * @depends testClassExistsRegister
	 */
	public function testParamNoDefault(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('submit');
		$this->assertNull($params);
	}

	/**
	 * @depends testClassExistsRegister
	 */
	public function testParamDefault(ActionMap $actionMap) {
		$params = $actionMap->getActionParams('colorscheme');

		$this->assertInternalType('array', $params);
		$this->assertArrayHasKey('color', $params);
		$this->assertInstanceOf('ReflectionRouter\\ColorSchemeExample', $params['color']);
		$this->assertTrue($params['color']->isValid());
	}
}
