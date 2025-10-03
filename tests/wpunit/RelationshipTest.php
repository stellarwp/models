<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\Tests\ModelsTestCase;
use StellarWP\Models\ValueObjects\Relationship;

/**
 * @coversDefaultClass \StellarWP\Models\ValueObjects\Relationship
 */
class RelationshipTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::HAS_ONE
	 * @covers ::getValue
	 */
	public function testHasOneFactoryMethod() {
		$relationship = Relationship::HAS_ONE();

		$this->assertInstanceOf(Relationship::class, $relationship);
		$this->assertSame(Relationship::HAS_ONE, $relationship->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::HAS_MANY
	 * @covers ::getValue
	 */
	public function testHasManyFactoryMethod() {
		$relationship = Relationship::HAS_MANY();

		$this->assertInstanceOf(Relationship::class, $relationship);
		$this->assertSame(Relationship::HAS_MANY, $relationship->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::MANY_TO_MANY
	 * @covers ::getValue
	 */
	public function testManyToManyFactoryMethod() {
		$relationship = Relationship::MANY_TO_MANY();

		$this->assertInstanceOf(Relationship::class, $relationship);
		$this->assertSame(Relationship::MANY_TO_MANY, $relationship->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::BELONGS_TO
	 * @covers ::getValue
	 */
	public function testBelongsToFactoryMethod() {
		$relationship = Relationship::BELONGS_TO();

		$this->assertInstanceOf(Relationship::class, $relationship);
		$this->assertSame(Relationship::BELONGS_TO, $relationship->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::BELONGS_TO_MANY
	 * @covers ::getValue
	 */
	public function testBelongsToManyFactoryMethod() {
		$relationship = Relationship::BELONGS_TO_MANY();

		$this->assertInstanceOf(Relationship::class, $relationship);
		$this->assertSame(Relationship::BELONGS_TO_MANY, $relationship->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::HAS_ONE
	 * @covers ::HAS_MANY
	 */
	public function testInstanceCaching() {
		$relationship1 = Relationship::HAS_ONE();
		$relationship2 = Relationship::HAS_ONE();

		// Same instance should be returned (flyweight pattern)
		$this->assertSame($relationship1, $relationship2);

		$relationship3 = Relationship::HAS_MANY();
		$this->assertNotSame($relationship1, $relationship3);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::from
	 * @covers ::getValue
	 */
	public function testFromFactoryMethod() {
		$relationship = Relationship::from(Relationship::HAS_ONE);

		$this->assertInstanceOf(Relationship::class, $relationship);
		$this->assertSame(Relationship::HAS_ONE, $relationship->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::from
	 * @covers ::HAS_ONE
	 */
	public function testFromReturnsCachedInstance() {
		$relationship1 = Relationship::HAS_ONE();
		$relationship2 = Relationship::from(Relationship::HAS_ONE);

		$this->assertSame($relationship1, $relationship2);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::from
	 */
	public function testFromThrowsExceptionForInvalidType() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid relationship type: invalid-type');

		Relationship::from('invalid-type');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isHasOne
	 * @covers ::HAS_ONE
	 */
	public function testIsHasOne() {
		$hasOne = Relationship::HAS_ONE();
		$hasMany = Relationship::HAS_MANY();

		$this->assertTrue($hasOne->isHasOne());
		$this->assertFalse($hasMany->isHasOne());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isHasMany
	 * @covers ::HAS_MANY
	 */
	public function testIsHasMany() {
		$hasMany = Relationship::HAS_MANY();
		$hasOne = Relationship::HAS_ONE();

		$this->assertTrue($hasMany->isHasMany());
		$this->assertFalse($hasOne->isHasMany());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isManyToMany
	 * @covers ::MANY_TO_MANY
	 */
	public function testIsManyToMany() {
		$manyToMany = Relationship::MANY_TO_MANY();
		$hasOne = Relationship::HAS_ONE();

		$this->assertTrue($manyToMany->isManyToMany());
		$this->assertFalse($hasOne->isManyToMany());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isBelongsTo
	 * @covers ::BELONGS_TO
	 */
	public function testIsBelongsTo() {
		$belongsTo = Relationship::BELONGS_TO();
		$hasOne = Relationship::HAS_ONE();

		$this->assertTrue($belongsTo->isBelongsTo());
		$this->assertFalse($hasOne->isBelongsTo());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isBelongsToMany
	 * @covers ::BELONGS_TO_MANY
	 */
	public function testIsBelongsToMany() {
		$belongsToMany = Relationship::BELONGS_TO_MANY();
		$hasOne = Relationship::HAS_ONE();

		$this->assertTrue($belongsToMany->isBelongsToMany());
		$this->assertFalse($hasOne->isBelongsToMany());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isSingle
	 * @covers ::HAS_ONE
	 * @covers ::BELONGS_TO
	 * @covers ::HAS_MANY
	 */
	public function testIsSingle() {
		$hasOne = Relationship::HAS_ONE();
		$belongsTo = Relationship::BELONGS_TO();
		$hasMany = Relationship::HAS_MANY();

		$this->assertTrue($hasOne->isSingle());
		$this->assertTrue($belongsTo->isSingle());
		$this->assertFalse($hasMany->isSingle());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isMultiple
	 * @covers ::HAS_MANY
	 * @covers ::BELONGS_TO_MANY
	 * @covers ::MANY_TO_MANY
	 * @covers ::HAS_ONE
	 */
	public function testIsMultiple() {
		$hasMany = Relationship::HAS_MANY();
		$belongsToMany = Relationship::BELONGS_TO_MANY();
		$manyToMany = Relationship::MANY_TO_MANY();
		$hasOne = Relationship::HAS_ONE();

		$this->assertTrue($hasMany->isMultiple());
		$this->assertTrue($belongsToMany->isMultiple());
		$this->assertTrue($manyToMany->isMultiple());
		$this->assertFalse($hasOne->isMultiple());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isValid
	 */
	public function testIsValid() {
		$this->assertTrue(Relationship::isValid(Relationship::HAS_ONE));
		$this->assertTrue(Relationship::isValid(Relationship::HAS_MANY));
		$this->assertTrue(Relationship::isValid(Relationship::MANY_TO_MANY));
		$this->assertTrue(Relationship::isValid(Relationship::BELONGS_TO));
		$this->assertTrue(Relationship::isValid(Relationship::BELONGS_TO_MANY));

		$this->assertFalse(Relationship::isValid('invalid-type'));
		$this->assertFalse(Relationship::isValid('has_one'));
		$this->assertFalse(Relationship::isValid('HAS_ONE'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::all
	 */
	public function testAll() {
		$all = Relationship::all();

		$this->assertIsArray($all);
		$this->assertCount(5, $all);

		foreach ($all as $relationship) {
			$this->assertInstanceOf(Relationship::class, $relationship);
		}

		// Verify all types are present
		$values = array_map(fn($r) => $r->getValue(), $all);
		$this->assertContains(Relationship::HAS_ONE, $values);
		$this->assertContains(Relationship::HAS_MANY, $values);
		$this->assertContains(Relationship::MANY_TO_MANY, $values);
		$this->assertContains(Relationship::BELONGS_TO, $values);
		$this->assertContains(Relationship::BELONGS_TO_MANY, $values);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::all
	 */
	public function testAllReturnsCachedInstances() {
		$hasOne = Relationship::HAS_ONE();
		$all = Relationship::all();

		// Find HAS_ONE in the array
		$foundHasOne = null;
		foreach ($all as $relationship) {
			if ($relationship->getValue() === Relationship::HAS_ONE) {
				$foundHasOne = $relationship;
				break;
			}
		}

		$this->assertSame($hasOne, $foundHasOne);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__toString
	 * @covers ::HAS_ONE
	 */
	public function testToString() {
		$relationship = Relationship::HAS_ONE();

		$this->assertSame(Relationship::HAS_ONE, (string) $relationship);
		$this->assertSame(Relationship::HAS_ONE, $relationship->__toString());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__call
	 */
	public function testInvalidIsMethodThrowsException() {
		$relationship = Relationship::HAS_ONE();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Method isInvalidType does not exist on Relationship.');

		$relationship->isInvalidType();
	}
}
