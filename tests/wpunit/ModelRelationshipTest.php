<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\Model;
use StellarWP\Models\ModelRelationship;
use StellarWP\Models\ModelRelationshipDefinition;
use StellarWP\Models\Tests\ModelsTestCase;
use StellarWP\Models\ValueObjects\Relationship;

/**
 * @coversDefaultClass \StellarWP\Models\ModelRelationship
 */
class ModelRelationshipTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::getKey
	 * @covers ::getDefinition
	 * @covers ::isLoaded
	 */
	public function testConstructor() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$this->assertSame('posts', $relationship->getKey());
		$this->assertSame($definition, $relationship->getDefinition());
		$this->assertFalse($relationship->isLoaded());
		$this->assertTrue($relationship->getDefinition()->isLocked());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 * @covers ::getValue
	 * @covers ::isLoaded
	 */
	public function testSetValueAndGetValue() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);

		// Should not be loaded initially
		$this->assertFalse($relationship->isLoaded());

		// Set value
		$relationship->setValue($mockModel);

		$this->assertTrue($relationship->isLoaded());

		// getValue without loader should return cached value
		$loader = fn() => $this->fail('Loader should not be called when value is already loaded');
		$this->assertSame($mockModel, $relationship->getValue($loader));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueThrowsExceptionForInvalidSingleValue() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Single relationship value must be a Model instance or null.');

		$relationship->setValue('invalid');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueThrowsExceptionForInvalidMultipleValue() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Multiple relationship value must be an array or null.');

		$relationship->setValue('invalid');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueThrowsExceptionForInvalidArrayItem() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Multiple relationship value must be an array of Model instances.');

		$relationship->setValue(['not', 'models']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 * @covers ::getValue
	 */
	public function testSetValueWithMultipleModels() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$mockModel1 = $this->createMock(Model::class);
		$mockModel2 = $this->createMock(Model::class);
		$models = [$mockModel1, $mockModel2];

		$relationship->setValue($models);

		$loader = fn() => $this->fail('Loader should not be called');
		$this->assertSame($models, $relationship->getValue($loader));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 */
	public function testGetValueLoadsWhenNotCached() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$definition->disableCaching();
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);
		$loaderCalled = false;
		$loader = function() use ($mockModel, &$loaderCalled) {
			$loaderCalled = true;
			return $mockModel;
		};

		$result = $relationship->getValue($loader);

		$this->assertTrue($loaderCalled);
		$this->assertSame($mockModel, $result);
		$this->assertFalse($relationship->isLoaded(), 'Should not cache when caching is disabled');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 */
	public function testGetValueLoadsAndCachesWhenNotLoaded() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);
		$loaderCallCount = 0;
		$loader = function() use ($mockModel, &$loaderCallCount) {
			$loaderCallCount++;
			return $mockModel;
		};

		// First call should load
		$result1 = $relationship->getValue($loader);
		$this->assertSame(1, $loaderCallCount);
		$this->assertSame($mockModel, $result1);
		$this->assertTrue($relationship->isLoaded());

		// Second call should use cache
		$result2 = $relationship->getValue($loader);
		$this->assertSame(1, $loaderCallCount, 'Loader should only be called once');
		$this->assertSame($mockModel, $result2);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::purge
	 * @covers ::isLoaded
	 * @covers ::getValue
	 */
	public function testPurge() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);
		$relationship->setValue($mockModel);

		$this->assertTrue($relationship->isLoaded());

		$relationship->purge();

		$this->assertFalse($relationship->isLoaded());

		// After purge, getValue should load again
		$loaderCalled = false;
		$loader = function() use ($mockModel, &$loaderCalled) {
			$loaderCalled = true;
			return $mockModel;
		};

		$relationship->getValue($loader);
		$this->assertTrue($loaderCalled);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 * @covers ::getValue
	 */
	public function testSetValueWithNull() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$relationship->setValue(null);

		$this->assertTrue($relationship->isLoaded());
		$loader = fn() => $this->fail('Loader should not be called');
		$this->assertNull($relationship->getValue($loader));
	}
}
