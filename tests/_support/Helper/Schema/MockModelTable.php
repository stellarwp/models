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

class MockModelTable extends Table {
	const SCHEMA_VERSION = '0.0.1-test';

	protected static $base_table_name = 'test_repository_table';

	protected static $group = 'test_group';

	protected static $schema_slug = 'test-repository';


	public static function get_schema_history(): array {
		$columns = new Column_Collection();

		$columns[] = new ID( 'id' );
		$columns[] = ( new String_Column( 'firstName' ) )->set_default( 'Michael' );
		$columns[] = ( new String_Column( 'lastName' ) );
		$columns[] = ( new Text_Column( 'emails' ) )->set_php_type( Text_Column::PHP_TYPE_JSON );
		$columns[] = ( new Float_Column( 'microseconds' ) )->set_length( 15 )->set_precision( 4 );
		$columns[] = ( new Integer_Column( 'int' ) );
		$columns[] = ( new DateTime_Column( 'date' ) );

		return [
			static::SCHEMA_VERSION => new Table_Schema( static::table_name( true ), $columns ),
		];
	}

	public static function transform_from_array( array $data ): MockModelSchema {
		return MockModelSchema::fromData( $data );
	}
}
