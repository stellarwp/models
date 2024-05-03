<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;

class MockModel extends Model {
	protected $properties = [
		'id'        => 'int',
		'firstName' => [ 'string', 'Michael' ],
		'lastName'  => 'string',
		'emails'    => [ 'array', [] ],
		'isActive' => 'bool',
		'createdDatetime' => 'datetime',
		'createdDate' => 'date',
	];
}
