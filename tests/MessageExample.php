<?php

namespace ReflectionRouter;

class MessageExample {
	public $ownerId;

	public function __construct($ownerId) {
		$this->ownerId = $ownerId;
	}

	public function related() {
		return array();
	}

	public function compose($title, $message) {
		return $title . ': ' . $message;
	}

	public function delete($id = NULL) {

	}

	public function load(\SplFileInfo $file = NULL) {

	}
}
