<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;

class MockModel extends Model {
	protected $properties = [
		'id'           => 'int',
		'firstName'    => [ 'string', 'Michael' ],
		'lastName'     => 'string',
		'emails'       => [ 'array', [] ],
		'microseconds' => 'float',
		'number'       => 'number',
	];
}
