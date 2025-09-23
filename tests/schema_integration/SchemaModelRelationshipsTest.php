<?php

namespace StellarWP\Models;

use lucatume\WPBrowser\TestCase\WPTestCase;
use DateTime;
use StellarWP\Models\Tests\Schema\MockModelSchemaWithRelationship;
use StellarWP\Models\Tests\Schema\MockRelationshipTable;
use StellarWP\DB\DB;

class SchemaModelRelationshipsTest extends WPTestCase {
	public function test_save() {
		[ $post_id_1, $post_id_2 ] = self::factory()->post->create_many( 2 );
		$model_data = [
			'lastName'     => 'Angelo',
			'emails'       => [ 'angelo@stellarwp.com', 'michael@stellarwp.com' ],
			'microseconds' => microtime( true ),
			'int'          => '1234567890',
			'date'         => new DateTime( '2023-06-13 17:25:00' ),
			'posts'        => [ $post_id_1, $post_id_2 ],
		];

		$model = MockModelSchemaWithRelationship::fromData( $model_data, 1 );
		$this->assertTrue( $model->isDirty() );
		$id = $model->save();
		$this->assertFalse( $model->isDirty() );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		$this->assertEquals( 'Michael', $model->get_firstName() );
		$this->assertEquals( $model_data['lastName'], $model->get_lastName() );
		$this->assertEquals( $model_data['emails'], $model->get_emails() );
		$this->assertEquals( $model_data['microseconds'], $model->get_microseconds() );
		$this->assertEquals( $model_data['int'], $model->get_int() );
		$this->assertEquals( $model_data['date'], $model->get_date() );
		$this->assertEquals( $model_data['posts'], $model->get_posts() );
	}

	public function test_queries_returns_models() {
		[ $post_id_1, $post_id_2, $post_id_3, $post_id_4 ] = self::factory()->post->create_many( 4 );
		$model_data = [
			[
				'lastName' => 'Angelo',
				'emails'   => [ 'michael@stellarwp.com', 'angelo@stellarwp.com' ],
				'microseconds' => microtime( true ),
				'int'          => '1234567890',
				'date'         => new DateTime( '2023-06-13 17:25:00' ),
				'posts'        => [ $post_id_1, $post_id_2 ],
			],
			[
				'lastName'     => 'Doe',
				'emails'       => [ 'john@doe.com' ],
				'microseconds' => 30.0 + microtime( true ),
				'int'          => '0987654321',
				'date'         => new DateTime( '2021-03-10 19:37:23' ),
			],
			[
				'firstName'    => 'Dimi',
				'lastName'     => 'Dimitrov',
				'emails'       => 'dimi@stellarwp.com',
				'microseconds' => 10.0 + microtime( true ),
				'int'          => '019287465',
				'date'         => new DateTime( '2024-11-23 18:49:54' ),
				'posts'        => [ $post_id_4 ],
			],
		];

		$models = [];

		foreach ( $model_data as $data ) {
			$model = MockModelSchemaWithRelationship::fromData( $data, 1 );
			$model->save();
			$models[] = $model;
		}

		$table = $models[0]->getTableInterface();

		$results = iterator_to_array($table::get_all());

		$this->assertCount( 3, $results );
		$this->assertInstanceOf( MockModelSchemaWithRelationship::class, $results[0] );
		$this->assertInstanceOf( MockModelSchemaWithRelationship::class, $results[1] );
		$this->assertInstanceOf( MockModelSchemaWithRelationship::class, $results[2] );
		$this->assertEquals( $models[0]->get_id(), $results[0]->get_id() );
		$this->assertEquals( $models[1]->get_id(), $results[1]->get_id() );
		$this->assertEquals( $models[2]->get_id(), $results[2]->get_id() );
		$this->assertEquals( $models[0]->get_firstName(), $results[0]->get_firstName() );
		$this->assertEquals( $models[1]->get_firstName(), $results[1]->get_firstName() );
		$this->assertEquals( $models[2]->get_firstName(), $results[2]->get_firstName() );
		$this->assertEquals( $models[0]->get_lastName(), $results[0]->get_lastName() );
		$this->assertEquals( $models[1]->get_lastName(), $results[1]->get_lastName() );
		$this->assertEquals( $models[2]->get_lastName(), $results[2]->get_lastName() );
		$this->assertEquals( $models[0]->get_emails(), $results[0]->get_emails() );
		$this->assertEquals( $models[1]->get_emails(), $results[1]->get_emails() );
		$this->assertEquals( $models[2]->get_emails(), $results[2]->get_emails() );
		$this->assertEquals( $models[0]->get_microseconds(), $results[0]->get_microseconds() );
		$this->assertEquals( $models[1]->get_microseconds(), $results[1]->get_microseconds() );
		$this->assertEquals( $models[2]->get_microseconds(), $results[2]->get_microseconds() );
		$this->assertEquals( $models[0]->get_int(), $results[0]->get_int() );
		$this->assertEquals( $models[1]->get_int(), $results[1]->get_int() );
		$this->assertEquals( $models[2]->get_int(), $results[2]->get_int() );
		$this->assertEquals( $models[0]->get_date(), $results[0]->get_date() );
		$this->assertEquals( $models[1]->get_date(), $results[1]->get_date() );
		$this->assertEquals( $models[2]->get_date(), $results[2]->get_date() );
		$this->assertEquals( $models[0]->get_posts(), $results[0]->get_posts() );
		$this->assertEquals( $models[1]->get_posts(), $results[1]->get_posts() );
		$this->assertEquals( $models[2]->get_posts(), $results[2]->get_posts() );
	}

	public function test_delete() {
		[ $post_id_1, $post_id_2 ] = self::factory()->post->create_many( 2 );
		$model_data = [
			'lastName' => 'Angelo',
			'emails'   => [ 'michael@stellarwp.com', 'angelo@stellarwp.com' ],
			'microseconds' => microtime( true ),
			'int'          => '1234567890',
			'date'         => new DateTime( '2023-06-13 17:25:00' ),
			'posts'        => [ $post_id_1, $post_id_2 ],
		];

		$model = MockModelSchemaWithRelationship::fromData( $model_data, 1 );
		$model->save();

		$results = DB::get_col( DB::prepare( "SELECT %i FROM %i WHERE %i = %d", 'post_id', MockRelationshipTable::table_name(), 'mock_model_id', $model->get_id() ) );

		$this->assertEquals( $model_data['posts'], $results );

		$this->assertInstanceOf( MockModelSchemaWithRelationship::class, $model->getTableInterface()::get_by_id( $model->get_id() ) );
		$this->assertGreaterThan( 0, $model->get_id() );
		$this->assertTrue( $model->delete() );
		$this->assertNull( $model->getTableInterface()::get_by_id( $model->get_id() ) );

		$results = DB::get_col( DB::prepare( "SELECT %i FROM %i WHERE %i = %d", 'post_id', MockRelationshipTable::table_name(), 'mock_model_id', $model->get_id() ) );
		$this->assertEmpty( $results );
	}
}
