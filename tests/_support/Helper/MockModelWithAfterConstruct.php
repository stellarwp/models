<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;

class MockModelWithAfterConstruct extends Model {
	protected static $properties = [
		'id' => 'int',
		'name' => 'string',
	];

	public bool $afterConstructCalled = false;
	public array $constructedAttributes = [];

	protected function afterConstruct(): void {
		$this->afterConstructCalled = true;
		$this->constructedAttributes = $this->toArray();
	}
}
