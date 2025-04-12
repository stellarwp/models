<?php

namespace StellarWP\Models\Tests;

use StellarWP\DB\DB;
use StellarWP\DB\QueryBuilder\QueryBuilder;
use StellarWP\Models\Model;
use StellarWP\Models\ValueObjects\Relationship;

class MockModelWithRelationship extends Model {
	protected static $properties = [
		'id' => 'int',
	];

	protected static $relationships = [
		'relatedButNotCallable'     => Relationship::HAS_ONE,
		'relatedAndCallableHasOne'  => Relationship::HAS_ONE,
		'relatedAndCallableHasMany' => Relationship::HAS_MANY,
	];

	/**
	 * @return QueryBuilder
	 */
	public function relatedAndCallableHasOne() {
		return DB::table( 'posts' );
	}

	/**
	 * @return QueryBuilder
	 */
	public function relatedAndCallableHasMany() {
		return DB::table( 'posts' );
	}
}
