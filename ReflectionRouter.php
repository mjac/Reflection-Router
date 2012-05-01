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

	private function getMethodParams(\ReflectionMethod $reflectedMethod, $input) {
		$methodParams = array();
		foreach ($reflectedMethod->getParameters() as $reflectedParam) {
			$paramName = $reflectedParam->getName();
			$hasDefault = $reflectedParam->isDefaultValueAvailable();

			if (isset($input[$paramName])) {
				$inputValue = $input[$paramName];
			} elseif ($hasDefault) {
				$methodParams[$paramName] = $reflectedParam->getDefaultValue();
				continue;
			} else {
				return NULL;
			}

			$paramClass = $reflectedParam->getClass();
			if ($paramClass instanceof \ReflectionClass) {
				$paramClassName = $paramClass->getName();
				$inputValue = new $paramClassName($inputValue);

				/*
				if ($inputValue instanceof ActionParamExtended) {
					// Put this in separate method to allow section above with hasDefault to consider the case when the class hint type has a default value
				} elseif ($inputValue instanceof ActionParam && !$inputValue->isValid()) {
					continue;
				}
				*/
			}

			$methodParams[$paramName] = $inputValue;
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
	public function hasDefault();
	public function getDefault();
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
