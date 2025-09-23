<?php

namespace StellarWP\Models\Tests\Schema;

use StellarWP\Schema\Tables\Contracts\Table;
use StellarWP\Schema\Tables\Table_Schema;
use StellarWP\Schema\Collections\Column_Collection;
use StellarWP\Schema\Columns\ID;
use StellarWP\Schema\Columns\String_Column;
use StellarWP\Schema\Columns\Text_Column;
use StellarWP\Schema\Columns\Float_Column;
use StellarWP\Schema\Columns\Integer_Column;
use StellarWP\Schema\Columns\DateTime_Column;
use StellarWP\Schema\Columns\PHP_Types;

class MockRelationshipModelTable extends Table {
	const SCHEMA_VERSION = '0.0.1-test';

	protected static $base_table_name = 'test_repository_table';

	protected static $group = 'test_group';

	protected static $schema_slug = 'test-repository';

	public static function get_schema_history(): array {
		$columns = new Column_Collection();

		$columns[] = new ID( 'id' );
		$columns[] = ( new String_Column( 'firstName' ) )->set_default( 'Michael' );
		$columns[] = ( new String_Column( 'lastName' ) );
		$columns[] = ( new Text_Column( 'emails' ) )->set_php_type( PHP_Types::JSON );
		$columns[] = ( new Float_Column( 'microseconds' ) )->set_length( 17 )->set_precision( 6 );
		$columns[] = ( new Integer_Column( 'int' ) );
		$columns[] = ( new DateTime_Column( 'date' ) );

		$table_name = static::table_name( true );

		return [
			static::SCHEMA_VERSION => fn() => new Table_Schema( $table_name, $columns ),
		];
	}

	public static function transform_from_array( array $data ): MockModelSchemaWithRelationship {
		return MockModelSchemaWithRelationship::fromData( $data );
	}
}
