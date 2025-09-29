<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;

class MockModelWithAfterConstruct extends Model {
	protected static array $properties = [
		'id' => 'int',
		'name' => 'string',
	];

	public bool $afterConstructCalled = false;
	public array $constructedAttributes = [];

	protected function afterConstruct() {
		$this->afterConstructCalled = true;
		$this->constructedAttributes = $this->toArray();
	}
}
