<?php

namespace ReflectionRouter;

class Router {
	private $namespace = '\\';

	private $moduleParam;
	private $actionParam;

	public function __construct($moduleParam, $actionParam) {
		$this->moduleParam = $moduleParam;
		$this->actionParam = $actionParam;
	}

	public function setNamespace($namespace) {
		if (strpos('\\', $namespace) !== 0) {
			$namespace = '\\' . $namespace;
		}

		$this->namespace = $namespace;
	}

	public function dispatch(array $input) {
		if (!isset($input[$this->moduleParam])) {
			throw new ModuleNotSpecifiedException(); 
		}

		if (!isset($input[$this->actionParam])) {
			throw new ActionNotSpecifiedException(); 
		}

		$action = new Action($this->namespace);
		return $action->perform($input[$this->moduleParam], $input[$this->actionParam], $input);
	}
}

class Action {
	private $namespace;

	public function __construct($namespace) {
		$this->namespace = $namespace;
	}

	private function perform($module, $action, array $input) {
		$targetClass = $this->namespace . '\\' . $module;

		try {
			$actionMap = new ActionMap($targetClass);
		} catch(\ReflectionException $e) {
			throw new ModuleNotFoundException($module, $targetClass);
		}

		$constructorParams = $actionMap->getModuleParams($input);
		if ($constructorParams === NULL) {
			throw new ModuleParamsIncorrectException();
		}

		try {
			$actionParams = $actionMap->getActionParams($action, $input);
		} catch (\ReflectionException $e) {
			throw new ActionNotFoundException();
		}

		if ($actionParams === NULL) {
			throw new ActionParamsIncorrectException();
		}
		
		$module = call_user_func_array( 
			array( 
				new \ReflectionClass($className),
				'newInstance' 
			), 
			$constructorParams 
		); 

		return call_user_func_array(array($module, $action), $actionParams);
	}
}

class ActionMap {
	public $reflectedClass;

	public function __construct($className) {
		$this->reflectedClass = new \ReflectionClass($className);
	}

	/**
	 * Using array_key_exists to allow for NULL input
	 */
	private function getMethodParams(\ReflectionMethod $reflectedMethod, $input) {
		$methodParams = array();
		foreach ($reflectedMethod->getParameters() as $reflectedParam) {
			$paramName = $reflectedParam->getName();
			$hasDefault = $reflectedParam->isDefaultValueAvailable();

			if (array_key_exists($paramName, $input)) {
				$methodParams[$paramName] = $input[$paramName];
				if ($hasDefault) {
					$defaultType = gettype($reflectedParam->getDefaultValue());
					// Cannot determine param type if default is NULL
					if ($defaultType !== 'NULL') {
						settype($methodParams[$paramName], $defaultType);
					}
				}
			} elseif ($hasDefault) {
				$methodParams[$paramName] = $reflectedParam->getDefaultValue();
				continue;
			}

			$paramClass = $reflectedParam->getClass();
			if ($paramClass instanceof \ReflectionClass) {
				$paramClassName = $paramClass->getName();

				// Attempt to get default object if type hinted 
				// with extended param interface
				if (!array_key_exists($paramName, $input)) {
					if ($paramClass->implementsInterface('ReflectionRouter\\ActionParamExtended') && $paramClassName::hasDefaultValue()) {
						$methodParams[$paramName] = $paramClassName::getDefaultValue();
					} else {
						// Missing and no default
						return NULL;
					}
				}

				// Create param according to type hint and determine
				// if it is valid
				$paramObject = new $paramClassName($methodParams[$paramName]);
				if ($paramObject instanceof ActionParam && !$paramObject->isValid()) {
					// Object param is not valid
					return NULL;
				}

				$methodParams[$paramName] = $paramObject;
			}

			if (!array_key_exists($paramName, $methodParams)) {
				return NULL;
			}
		}

		return $methodParams;
	}

	public function getModuleParams($input = array()) {
		$reflectedMethod = $this->reflectedClass->getConstructor();

		if ($reflectedMethod === NULL) {
			return array();
		}

		return $this->getMethodParams($reflectedMethod, $input);
	}

	public function getActionParams($action, $input = array()) {
		$reflectedMethod = $this->reflectedClass->getMethod($action);
		return $this->getMethodParams($reflectedMethod, $input);
	}
}

interface ActionParam {
	public function isValid();
}

interface ActionParamExtended extends ActionParam {
	public function hasDefaultValue();
	public function getDefaultValue();
}

class Exception extends \Exception {
}

class ModuleNotFoundException extends Exception {
}

class ActionNotFoundException extends Exception {
}

class ParamsIncorrectException extends Exception {
}

class ActionParamsIncorrectException extends ParamsIncorrectException  {
}

class ModuleParamsIncorrectException extends ParamsIncorrectException {
}


/*getName
ReflectionParameter::getClass
ReflectionParameter::getDefaultValue
ReflectionParameter::isDefaultValueAvailable*/
