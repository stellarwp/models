<?php

namespace StellarWP\Models;

use lucatume\WPBrowser\TestCase\WPTestCase;
use DateTime;
use StellarWP\Models\Tests\Schema\MockModelSchema;

class SchemaModelTest extends WPTestCase {
	public function test_save() {
		$model_data = [
			'lastName'     => 'Angelo',
			'emails'       => [ 'angelo@stellarwp.com', 'michael@stellarwp.com' ],
			'microseconds' => microtime( true ),
			'int'          => '1234567890',
			'date'         => new DateTime( '2023-06-13 17:25:00' ),
		];

		$model = MockModelSchema::fromData( $model_data, 1 );
		// $this->assertTrue( $model->isDirty() );
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
	}
}
