<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Model;
use StellarWP\Models\ModelQueryBuilder;
use StellarWP\Models\ValueObjects\Relationship;

class MockModelWithRelationship extends Model {
	protected static array $properties = [
		'id' => 'int',
	];

	protected static array $relationships = [
		'relatedButNotCallable'     => Relationship::HAS_ONE,
		'relatedAndCallableHasOne'  => Relationship::HAS_ONE,
		'relatedAndCallableHasMany' => Relationship::HAS_MANY,
	];

	/**
	 * @return ModelQueryBuilder<MockModel>
	 */
	public function relatedAndCallableHasOne(): ModelQueryBuilder {
		return ( new ModelQueryBuilder( MockModel::class ) )->from( 'posts' );
	}

	/**
	 * @return ModelQueryBuilder<MockModel>
	 */
	public function relatedAndCallableHasMany(): ModelQueryBuilder {
		return ( new ModelQueryBuilder( MockModel::class ) )->from( 'posts' );
	}
}
