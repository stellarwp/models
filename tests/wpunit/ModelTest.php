<?php

namespace StellarWP\Models;

use StellarWP\Models\Tests\ModelsTestCase;
use StellarWP\Models\Tests\MockModel;
use StellarWP\Models\Tests\MockModelWithRelationship;

/**
 * @since 1.0.0
 *
 * @coversDefaultClass Model
 */
class TestModel extends ModelsTestCase {
	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testFillShouldAssignProperties() {
		$model = new MockModel();

		$model->fill( [ 'id' => 1, 'firstName' => 'Bill', 'lastName' => 'Murray' ] );

		$this->assertEquals( 1, $model->id );
		$this->assertEquals( 'Bill', $model->firstName );
		$this->assertEquals( 'Murray', $model->lastName );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testDefaultPropertyValues() {
		$model = new MockModel();

		$this->assertNull( $model->id );
		$this->assertSame( 'Michael', $model->firstName );
		$this->assertNull( $model->lastName );
		$this->assertSame( [], $model->emails );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testConstructorShouldFillAttributesAndAssignProperties() {
		$model = new MockModel( [ 'id' => 1, 'firstName' => 'Bill', 'lastName' => 'Murray' ] );

		$this->assertEquals( 1, $model->id );
		$this->assertEquals( 'Bill', $model->firstName );
		$this->assertEquals( 'Murray', $model->lastName );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testGetAttributeShouldReturnPropertyValue() {
		$model = new MockModel( [ 'id' => 1 ] );

		$this->assertEquals( 1, $model->getAttribute( 'id' ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testGetAttributeShouldReturnCustomDefaultValue() {
		$model = new MockModel( [ 'id' => 1 ] );

		$this->assertEquals(
			'shakalaka',
			$model->getAttribute( 'lastName', 'shakalaka' )
		);
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testGetAttributeShouldThrowInvalidArgumentException() {
		$this->expectException( Config::getInvalidArgumentException() );

		$model = new MockModel( [ 'id' => 1 ] );

		$model->getAttribute( 'iDontExist' );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testSetAttributeShouldAssignPropertyValue() {
		$model = new MockModel( [ 'id' => 1 ] );
		$model->setAttribute( 'firstName', 'Bill' );

		$this->assertEquals( 'Bill', $model->firstName );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testIsPropertyTypeValidShouldReturnTrueWhenPropertyIsValid() {
		$model = new MockModel();

		$this->assertTrue( $model->isPropertyTypeValid( 'id', 1 ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @dataProvider invalidTypeProvider
	 *
	 * @return void
	 */
	public function testIsPropertyTypeValidShouldReturnFalseWhenPropertyIsInValid( $key, $value ) {
		$model = new MockModel();

		$this->assertFalse( $model->isPropertyTypeValid( $key, $value ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testModelShouldHaveDirtyAttributes() {
		$model = new MockModel(
			[
				'id'        => 1,
				'firstName' => 'Bill',
				'lastName'  => 'Murray',
				'emails'    => [ 'billMurray@givewp.com' ],
			]
		);

		$model->lastName = 'Gates';

		$this->assertEquals( [ 'lastName' => 'Gates' ], $model->getDirty() );
		$this->assertEquals( true, $model->isDirty() );
		$this->assertTrue( $model->isDirty( 'lastName' ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testModelShouldHaveCleanAttributes() {
		$model = new MockModel(
			[
				'id'        => 1,
				'firstName' => 'Bill',
				'lastName'  => 'Murray',
				'emails'    => [ 'billMurray@givewp.com' ],
			]
		);

		$model->lastName = 'Gates';

		$this->assertEquals( false, $model->isClean() );
		$this->assertEquals( true, $model->isClean( 'firstName' ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testIssetShouldReturnTrue() {
		$model = new MockModel( [ 'id' => 0 ] );

		$this->assertTrue( isset( $model->id ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testIssetShouldReturnFalse() {
		$model = new MockModel();

		$this->assertFalse( isset( $model->id ) );

		$model->id = null;

		$this->assertFalse( isset( $model->id ) );
	}

	/**
	 * @since        2.20.1
	 *
	 * @dataProvider invalidTypeProvider
	 *
	 * @return void
	 */
	public function testModelShouldThrowExceptionForAssigningInvalidPropertyType( $key, $value ) {
		$this->expectException( Config::getInvalidArgumentException() );

		new MockModel( [ $key => $value ] );
	}

	/**
	 * @return void
	 */
	public function testModelRelationshipPropertyShouldThrowException() {
		$this->expectException( Config::getInvalidArgumentException() );

		$model = new MockModelWithRelationship();

		$model->relatedButNotCallable;
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testModelRelationshipPropertyShouldReturnCallable() {
		$model = new MockModelWithRelationship();

		$this->assertEquals( $model->relatedAndCallableHasOne, $model->relatedAndCallableHasOne()->get() );
		$this->assertEquals( $model->relatedAndCallableHasMany, $model->relatedAndCallableHasMany()->getAll() );
	}

	/**
	 * @since 1.0.0
	 */
	public function testModelRelationsShouldBeCached() {
		$model = new MockModelWithRelationship();

		$post = $model->relatedAndCallableHasOne;

		self::assertSame( $model->relatedAndCallableHasOne, $post );
	}

	/**
	 * @since 1.0.0
	 */
	public function testShouldThrowExceptionForGettingMissingProperty() {
		$this->expectException( Config::getInvalidArgumentException() );

		$model = new MockModel();

		$model->iDontExist;
	}

	/**
	 * @since 1.0.0
	 */
	public function testShouldThrowExceptionForSettingMissingProperty() {
		$this->expectException( Config::getInvalidArgumentException() );

		$model = new MockModel();

		$model->iDontExist = 'foo';
	}

	public function testShouldSetMultipleAttributes() {
		$model = new MockModel();
		$model->setAttributes( [
			'firstName' => 'Luke',
			'lastName'  => 'Skywalker',
		] );

		$this->assertEquals( 'Luke', $model->firstName );
		$this->assertEquals( 'Skywalker', $model->lastName );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function testIsSet() {
		$model = new MockModel();

		// This has a default so we should see as set.
		$this->assertTrue( $model->isSet( 'firstName' ) );

		// No default, and hasn't been set so show false.
		$this->assertFalse( $model->isSet( 'lastName' ) );

		// Now we set it, so it should be true - even though we set it to null.
		$model->lastName = null;
		$this->assertTrue( $model->isSet( 'lastName' ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function invalidTypeProvider() {
		return [
			[ 'id', 'Not an integer' ],
			[ 'firstName', 100 ],
			[ 'emails', 'Not an array' ],
			[ 'microseconds', 'Not a float' ],
			[ 'number', '12' ] // numeric strings do not work; must be int or float
		];
	}
}
