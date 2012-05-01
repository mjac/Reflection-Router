<?php

namespace ReflectionRouter;

class RegisterExample {
	public function form() {
		return 'form';
	}

	public function submit(EmailAddressExample $email) {

	}

	public function name($name = 'Anonymous') {

	}

	public function lastname($lastname = NULL) {

	}

	public function colorscheme(ColorSchemeExample $color) {
	}
}

class EmailAddressExample implements ActionParam {
	private $email;

	public function __construct($email) {
		$this->email = $email;
	}

	public function isValid() {
		return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== FALSE;
	}
}

class ColorSchemeExample implements ActionParamExtended {
	public $black;

	public function __construct($color) {
		$this->black = $color === 'black';
	}

	public function isValid() {
		return TRUE;
	}

	public function hasDefaultValue() {
		return TRUE;
	}

	public function getDefaultValue() {
		return new ColorSchemeExample('black');
	}
}
