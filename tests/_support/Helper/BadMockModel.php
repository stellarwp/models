<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;
use DateTime;

class BadMockModel extends Model {
	protected static array $properties = [
		'id'           => 'int',
		'firstName'    => [ 'string', 'Michael' ],
		'lastName'     => 'string',
		'emails'       => [ 'array', [] ],
		'microseconds' => 'float',
		'number'       => 'int',
		'date'         => DateTime::class,
	];
}
