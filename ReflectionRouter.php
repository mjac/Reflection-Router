<?php

namespace ReflectionRouter;

class Action {
	private $namespace;

	public function __construct($namespace) {
		$this->namespace = $namespace;
	}

	public function perform($module, $action, array $input = array()) {
		$targetClass = $this->namespace . '\\' . $module;

		try {
			$actionMap = new ActionMap($targetClass);
		} catch (\ReflectionException $e) {
			throw new ModuleNotFoundException($module, $targetClass, $e);
		}

		$constructorParams = $actionMap->getModuleParams($input);
		if ($constructorParams === NULL) {
			throw new ModuleParamsIncorrectException($module, $targetClass);
		}

		try {
			$actionParams = $actionMap->getActionParams($action, $input);
		} catch (\ReflectionException $e) {
			throw new ActionNotFoundException($module, $action, $targetClass, $e);
		}

		if ($actionParams === NULL) {
			throw new ActionParamsIncorrectException($module, $action, $targetClass);
		}

		$module = call_user_func_array( 
			array( 
				new \ReflectionClass($targetClass),
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
		if (!$reflectedMethod->isPublic()) {
			throw new ActionNotAccessibleException($reflectedMethod->class, $reflectedMethod->name);
		}

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
	public function __construct($message, \Exception $e = NULL) {
		parent::__construct($message, 0, $e);
	}
}

class ActionNotAccessibleException extends Exception {
	public function __construct($class, $method, \Exception $e = NULL) {
		parent::__construct("Module is not visible, cannot execute $class::$method", $e);
	}
}

class ModuleNotFoundException extends Exception {
	public function __construct($module, $targetClass, \Exception $e = NULL) {
		parent::__construct("Module <$module> not found, attempted to load $targetClass", $e);
	}
}

class ActionNotFoundException extends Exception {
	public function __construct($module, $action, $targetClass, \Exception $e = NULL) {
		parent::__construct("Action <$action> not found in module <$module> with full reference $targetClass", $e);
	}
}

class ActionParamsIncorrectException extends Exception {
	public function __construct($module, $action, $targetClass, \Exception $e = NULL) {
		parent::__construct("Parameters are incorrect for action <$action> in module <$module> with full reference $targetClass", $e);
	}
}

class ModuleParamsIncorrectException extends Exception {
	public function __construct($module, $targetClass, \Exception $e = NULL) {
		parent::__construct("Parameters are incorrect for module <$module> with full reference $targetClass", $e);
	}
}

