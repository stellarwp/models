<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;

class MockModel extends Model {
	protected static array $properties = [
		'id'           => 'int',
		'firstName'    => [ 'string', 'Michael' ],
		'lastName'     => 'string',
		'emails'       => [ 'array', [] ],
		'microseconds' => 'float',
		'int'       => 'int',
		'date'         => \DateTime::class,
	];
}
