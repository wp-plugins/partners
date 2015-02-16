<?php

namespace MightyDev\Util;

class FormField
{
	public $name;
	public $value;
	public $error;
	public $message;

	public function __construct( $name, $value = null, $error = false, $message = null ) {
		$this->name = $name;
		$this->$value = $value;
		$this->error = $error;
		$this->message = $message;
	}

	public function __toString()
	{
		return $this->value;
	}
}
