<?php

namespace ElggWebServices;

class Param {
	private $type;
	private $required = true;
	private $default = null;

	/**
	 * Constructor
	 *
	 * @param string $type Type
	 */
	public function __construct($type) {
		$this->type = $type;
	}

	/**
	 * Set a default value
	 *
	 * @param mixed $value Default value
	 * @return self
	 */
	public function setDefault($value) {
		$this->default = $value;
		return $this;
	}

	/**
	 * Make the parameter optional
	 *
	 * @return self
	 */
	public function setOptional() {
		$this->required = false;
		return $this;
	}

	/**
	 * Get as an array
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'type' => $this->type,
			'default' => $this->default,
			'required' => $this->required,
		];
	}
}
