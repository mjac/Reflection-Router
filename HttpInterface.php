<?php

namespace HttpInterface;

class Input {
	public function __construct($moduleParam, $actionParam) {

	}

	public function dispatch() {

	}
}

class Action {
	public function dispatch() {
		// validate that name is a valid classname
		if (!class_exists($namespaceBase . $module)) {
			// exception
		}

		// reflect class to get constructor and check action exists
		// construct using input variables (often a file will always have certain inputs)

		// call action on class with other input variables
		// otherwise return nothing
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

/*getName
ReflectionParameter::getClass
ReflectionParameter::getDefaultValue
ReflectionParameter::isDefaultValueAvailable*/
