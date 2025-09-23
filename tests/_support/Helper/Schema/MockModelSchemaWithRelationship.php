<?php

namespace StellarWP\Models\Tests\Schema;

use StellarWP\Models\SchemaModel;
use StellarWP\Schema\Tables\Contracts\Table_Interface;
use StellarWP\Models\ValueObjects\Relationship;

class MockModelSchemaWithRelationship extends SchemaModel {
	public function __construct( array $attributes = [] ) {
		parent::__construct( $attributes );

		$this->defineRelationship( 'posts', Relationship::MANY_TO_MANY, MockRelationshipTable::class );
		$this->defineRelationshipColumns( 'posts', 'mock_model_id', 'post_id' );
	}
	public function getTableInterface(): Table_Interface {
		return new MockRelationshipModelTable();
	}
}
