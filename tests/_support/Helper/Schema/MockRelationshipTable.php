<?php

namespace StellarWP\Models\Tests\Schema;

use StellarWP\Schema\Tables\Contracts\Table;
use StellarWP\Schema\Tables\Table_Schema;
use StellarWP\Schema\Collections\Column_Collection;
use StellarWP\Schema\Collections\Index_Collection;
use StellarWP\Schema\Columns\ID;
use StellarWP\Schema\Columns\Referenced_ID;
use StellarWP\Schema\Indexes\Classic_Index;
use Exception;

class MockRelationshipTable extends Table {
	const SCHEMA_VERSION = '0.0.1-test';

	protected static $base_table_name = 'test_relationship_table';

	protected static $group = 'test_group';

	protected static $schema_slug = 'test-relationship';

	public static function get_schema_history(): array {
		$columns = new Column_Collection();

		$columns[] = new ID( 'id' );
		$columns[] = new Referenced_ID( 'post_id' );
		$columns[] = new Referenced_ID( 'mock_model_id' );

		$indexes = new Index_Collection();
		$indexes[] = ( new Classic_Index( 'idx_post_id_mock_model_id' ) )->set_columns( 'post_id', 'mock_model_id' );

		$table_name = static::table_name( true );

		return [
			static::SCHEMA_VERSION => fn() => new Table_Schema( $table_name, $columns, $indexes ),
		];
	}

	public static function transform_from_array( array $data ) {
		return $data;
	}
}
