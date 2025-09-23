<?php
use StellarWP\Models\Config;
use StellarWP\Schema\Config as Schema_Config;
use StellarWP\Schema\Register;
use StellarWP\Models\Tests\Schema\MockModelTable;
use StellarWP\Models\Tests\Schema\MockRelationshipTable;
use StellarWP\Models\Tests\Schema\Container;
use StellarWP\DB\DB;

Schema_Config::set_db( DB::class );
Schema_Config::set_container( tests_models_get_container() );

Config::setHookPrefix( 'test_' );

tests_models_drop_tables();

Register::table( MockModelTable::class );
Register::table( MockRelationshipTable::class );

tests_add_filter(
	'shutdown',
	'tests_models_drop_tables'
);

function tests_models_drop_tables() {
	DB::query( DB::prepare( "DROP TABLE IF EXISTS %i", MockModelTable::table_name() ) );
	DB::query( DB::prepare( "DROP TABLE IF EXISTS %i", MockRelationshipTable::table_name() ) );
}

function tests_models_get_container() : Container {
	static $container = null;

	if ( null !== $container ) {
		return $container;
	}

	$container = new Container();

	return $container;
}
