<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\ModelRelationshipDefinition;
use StellarWP\Models\Tests\ModelsTestCase;
use StellarWP\Models\ValueObjects\Relationship;

/**
 * @coversDefaultClass \StellarWP\Models\ModelRelationshipDefinition
 */
class ModelRelationshipDefinitionTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::getKey
	 * @covers ::getType
	 */
	public function testConstructorSetsKeyAndType() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);

		$this->assertSame('posts', $definition->getKey());
		$this->assertSame(Relationship::HAS_MANY, $definition->getType()->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::hasOne
	 * @covers ::getType
	 */
	public function testHasOne() {
		$definition = new ModelRelationshipDefinition('profile');
		$definition->hasOne();

		$this->assertSame(Relationship::HAS_ONE, $definition->getType()->getValue());
		$this->assertTrue($definition->isSingle());
		$this->assertFalse($definition->isMultiple());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::hasMany
	 * @covers ::isMultiple
	 */
	public function testHasMany() {
		$definition = new ModelRelationshipDefinition('posts');
		$definition->hasMany();

		$this->assertSame(Relationship::HAS_MANY, $definition->getType()->getValue());
		$this->assertTrue($definition->isMultiple());
		$this->assertFalse($definition->isSingle());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::belongsTo
	 * @covers ::isSingle
	 */
	public function testBelongsTo() {
		$definition = new ModelRelationshipDefinition('author');
		$definition->belongsTo();

		$this->assertSame(Relationship::BELONGS_TO, $definition->getType()->getValue());
		$this->assertTrue($definition->isSingle());
		$this->assertFalse($definition->isMultiple());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::belongsToMany
	 * @covers ::isMultiple
	 */
	public function testBelongsToMany() {
		$definition = new ModelRelationshipDefinition('tags');
		$definition->belongsToMany();

		$this->assertSame(Relationship::BELONGS_TO_MANY, $definition->getType()->getValue());
		$this->assertTrue($definition->isMultiple());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::manyToMany
	 * @covers ::isMultiple
	 */
	public function testManyToMany() {
		$definition = new ModelRelationshipDefinition('categories');
		$definition->manyToMany();

		$this->assertSame(Relationship::MANY_TO_MANY, $definition->getType()->getValue());
		$this->assertTrue($definition->isMultiple());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::enableCaching
	 * @covers ::disableCaching
	 * @covers ::cachingIsEnabled
	 */
	public function testCaching() {
		$definition = new ModelRelationshipDefinition('posts');

		// Default should be cached
		$this->assertTrue($definition->hasCachingEnabled());

		// Disable caching
		$definition->disableCaching();
		$this->assertFalse($definition->hasCachingEnabled());

		// Re-enable caching
		$definition->enableCaching();
		$this->assertTrue($definition->hasCachingEnabled());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromShorthand
	 * @covers ::getKey
	 * @covers ::getType
	 */
	public function testFromShorthand() {
		$definition = ModelRelationshipDefinition::fromShorthand('posts', Relationship::HAS_MANY);

		$this->assertSame('posts', $definition->getKey());
		$this->assertSame(Relationship::HAS_MANY, $definition->getType()->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromShorthand
	 */
	public function testFromShorthandThrowsExceptionForInvalidType() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid relationship type: invalid-type');

		ModelRelationshipDefinition::fromShorthand('posts', 'invalid-type');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::lock
	 * @covers ::isLocked
	 */
	public function testLock() {
		$definition = new ModelRelationshipDefinition('posts');

		$this->assertFalse($definition->isLocked());

		$definition->lock();
		$this->assertTrue($definition->isLocked());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::lock
	 * @covers ::hasMany
	 */
	public function testCannotModifyAfterLock() {
		$definition = new ModelRelationshipDefinition('posts');
		$definition->lock();

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Relationship is locked');

		$definition->hasMany();
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::hasOne
	 * @covers ::hasMany
	 * @covers ::belongsTo
	 */
	public function testFluentInterface() {
		$definition = new ModelRelationshipDefinition('posts');
		$result = $definition->hasMany();

		$this->assertSame($definition, $result);

		// Test chaining
		$definition = new ModelRelationshipDefinition('posts');
		$definition->hasMany()->disableCaching();

		$this->assertSame(Relationship::HAS_MANY, $definition->getType()->getValue());
		$this->assertFalse($definition->hasCachingEnabled());
	}
}
