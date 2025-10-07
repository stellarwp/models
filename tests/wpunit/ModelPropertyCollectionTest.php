<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\ModelProperty;
use StellarWP\Models\ModelPropertyCollection;
use StellarWP\Models\ModelPropertyDefinition;
use StellarWP\Models\Tests\ModelsTestCase;

/**
 * @coversDefaultClass \StellarWP\Models\ModelPropertyCollection
 */
class ModelPropertyCollectionTest extends ModelsTestCase {
	/**
	 * Create a collection with test properties
	 *
	 * @since 2.0.0
	 */
	private function createTestCollection(): ModelPropertyCollection {
		$property1 = new ModelProperty('name', (new ModelPropertyDefinition())->type('string'), 'John');
		$property2 = new ModelProperty('age', (new ModelPropertyDefinition())->type('int'), 30);
		$property3 = new ModelProperty('active', (new ModelPropertyDefinition())->type('bool'), true);

		return new ModelPropertyCollection([
			'name' => $property1,
			'age' => $property2,
			'active' => $property3,
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 */
	public function testConstructorShouldThrowExceptionForNonStringKeys() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Property key must be a string.');

		$property = new ModelProperty('name', new ModelPropertyDefinition(), 'John');

		new ModelPropertyCollection([
			0 => $property,
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 */
	public function testConstructorShouldThrowExceptionForNonModelPropertyValues() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Property must be an instance of ModelProperty.');

		new ModelPropertyCollection([
			'name' => 'John',
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::count
	 */
	public function testCount() {
		$collection = $this->createTestCollection();

		$this->assertSame(3, $collection->count());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getIterator
	 */
	public function testGetIterator() {
		$collection = $this->createTestCollection();

		$this->assertInstanceOf(\Traversable::class, $collection->getIterator());

		$properties = [];
		foreach ($collection as $key => $property) {
			$properties[$key] = $property;
		}

		$this->assertCount(3, $properties);
		$this->assertArrayHasKey('name', $properties);
		$this->assertArrayHasKey('age', $properties);
		$this->assertArrayHasKey('active', $properties);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::get
	 * @covers ::has
	 */
	public function testGetAndHas() {
		$collection = $this->createTestCollection();

		$this->assertTrue($collection->has('name'));
		$this->assertFalse($collection->has('nonexistent'));

		$this->assertInstanceOf(ModelProperty::class, $collection->get('name'));
		$this->assertNull($collection->get('nonexistent'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getOrFail
	 */
	public function testGetOrFail() {
		$collection = $this->createTestCollection();

		$this->assertInstanceOf(ModelProperty::class, $collection->getOrFail('name'));

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Property nonexistent does not exist.');

		$collection->getOrFail('nonexistent');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValues
	 */
	public function testGetValues() {
		$collection = $this->createTestCollection();

		$values = $collection->getValues();

		$this->assertIsArray($values);
		$this->assertSame('John', $values['name']);
		$this->assertSame(30, $values['age']);
		$this->assertSame(true, $values['active']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getOriginalValues
	 * @covers ::setValues
	 */
	public function testGetOriginalValuesAndSetValues() {
		$collection = $this->createTestCollection();

		// Change values
		$collection->setValues([
			'name' => 'Jane',
			'age' => 25,
		]);

		// Check new values
		$values = $collection->getValues();
		$this->assertSame('Jane', $values['name']);
		$this->assertSame(25, $values['age']);

		// Check original values
		$originalValues = $collection->getOriginalValues();
		$this->assertSame('John', $originalValues['name']);
		$this->assertSame(30, $originalValues['age']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValues
	 */
	public function testSetValuesWithNonExistentProperty() {
		$collection = $this->createTestCollection();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Property nonexistent does not exist.');

		$collection->setValues([
			'nonexistent' => 'value',
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isDirty
	 */
	public function testIsDirty() {
		$collection = $this->createTestCollection();

		// Initially not dirty
		$this->assertFalse($collection->isDirty());

		// Change value
		$collection->setValues([
			'name' => 'Jane',
		]);

		// Should be dirty now
		$this->assertTrue($collection->isDirty());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getDirtyProperties
	 * @covers ::getDirtyValues
	 */
	public function testGetDirtyPropertiesAndValues() {
		$collection = $this->createTestCollection();

		// Make a property dirty first so we can test getDirtyProperties()
		$collection->setValues([
			'name' => 'Jane',
		]);

		// Now there should be dirty properties
		$dirtyProps = $collection->getDirtyProperties();
		$this->assertInstanceOf(ModelPropertyCollection::class, $dirtyProps);
		$this->assertTrue($dirtyProps->has('name'));

		// Check dirty values
		$dirtyValues = $collection->getDirtyValues();
		$this->assertArrayHasKey('name', $dirtyValues);
		$this->assertSame('Jane', $dirtyValues['name']);

		// Set it back to original value so it's no longer dirty
		$collection->setValues([
			'name' => 'John',
		]);

		// Now there should be no dirty values
		$this->assertFalse($collection->isDirty());
		$this->assertEmpty($collection->getDirtyValues());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::commitChangedProperties
	 */
	public function testCommitChangedProperties() {
		$collection = $this->createTestCollection();

		// Change value
		$collection->setValues([
			'name' => 'Jane',
		]);

		// Verify it's dirty before commit
		$this->assertTrue($collection->isDirty());

		// Commit changes
		$collection->commitChangedProperties();

		// Should no longer be dirty
		$this->assertFalse($collection->isDirty());

		// Original value should match current value
		$values = $collection->getValues();
		$originalValues = $collection->getOriginalValues();
		$this->assertSame($values['name'], $originalValues['name']);
		$this->assertSame('Jane', $originalValues['name']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::revertChangedProperties
	 */
	public function testRevertChangedProperties() {
		$collection = $this->createTestCollection();

		// Change values
		$collection->setValues([
			'name' => 'Jane',
			'age' => 25,
		]);

		// Should be dirty
		$this->assertTrue($collection->isDirty());

		// Revert changes
		$collection->revertChangedProperties();

		// Should no longer be dirty
		$this->assertFalse($collection->isDirty());

		// Values should be back to original
		$values = $collection->getValues();
		$this->assertSame('John', $values['name']);
		$this->assertSame(30, $values['age']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::revertProperty
	 */
	public function testRevertProperty() {
		$collection = $this->createTestCollection();

		// Change multiple values
		$collection->setValues([
			'name' => 'Jane',
			'age' => 25,
		]);

		// Revert just one property
		$collection->revertProperty('name');

		// Values check
		$values = $collection->getValues();
		$this->assertSame('John', $values['name']); // Reverted
		$this->assertSame(25, $values['age']); // Still changed
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isSet
	 * @covers ::unsetProperty
	 */
	public function testIsSetAndUnsetProperty() {
		$collection = $this->createTestCollection();

		$this->assertTrue($collection->isSet('name'));

		$collection->unsetProperty('name');

		$this->assertFalse($collection->isSet('name'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::filter
	 */
	public function testFilter() {
		$collection = $this->createTestCollection();

		// Filter for properties that exist (this ensures we don't get an empty collection)
		$filtered = $collection->filter(function(ModelProperty $property) {
			return true; // Return all properties
		});

		$this->assertInstanceOf(ModelPropertyCollection::class, $filtered);
		$this->assertSame(3, $filtered->count());

		// Now filter for just one property
		$nameOnly = $collection->filter(function(ModelProperty $property) {
			return $property->getKey() === 'name';
		});

		$this->assertInstanceOf(ModelPropertyCollection::class, $nameOnly);
		$this->assertSame(1, $nameOnly->count());
		$this->assertTrue($nameOnly->has('name'));
		$this->assertFalse($nameOnly->has('age'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::map
	 */
	public function testMap() {
		$collection = $this->createTestCollection();

		$result = $collection->map(function($property) {
			return $property->getValue() . '_mapped';
		});

		$this->assertIsArray($result);
		$this->assertSame('John_mapped', $result['name']);
		$this->assertSame('30_mapped', $result['age']);
		$this->assertSame('1_mapped', $result['active']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::reduce
	 */
	public function testReduce() {
		$collection = $this->createTestCollection();

		$result = $collection->reduce(function($carry, $property) {
			return $carry . $property->getValue();
		}, '');

		$this->assertSame('John301', $result);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::tap
	 */
	public function testTap() {
		$collection = $this->createTestCollection();

		$values = [];
		$collection->tap(function($property) use (&$values) {
			$values[$property->getKey()] = $property->getValue();
		});

		$this->assertSame('John', $values['name']);
		$this->assertSame(30, $values['age']);
		$this->assertSame(true, $values['active']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getRequiredProperties
	 * @covers ::getRequiredOnSaveProperties
	 */
	public function testGetRequiredAndRequiredOnSaveProperties() {
		// Create a collection with required and requiredOnSave properties
		$requiredDefinition = (new ModelPropertyDefinition())->type('string')->required();
		$requiredOnSaveDefinition = (new ModelPropertyDefinition())->type('int')->requiredOnSave();
		$normalDefinition = (new ModelPropertyDefinition())->type('bool');

		$property1 = new ModelProperty('required', $requiredDefinition, 'value');
		$property2 = new ModelProperty('requiredOnSave', $requiredOnSaveDefinition, 10);
		$property3 = new ModelProperty('normal', $normalDefinition, true);

		$collection = new ModelPropertyCollection([
			'required' => $property1,
			'requiredOnSave' => $property2,
			'normal' => $property3,
		]);

		// Test required properties
		$requiredProperties = $collection->getRequiredProperties();
		$this->assertCount(1, $requiredProperties);
		$this->assertTrue($requiredProperties->has('required'));
		$this->assertFalse($requiredProperties->has('requiredOnSave'));

		// Test requiredOnSave properties
		$requiredOnSaveProperties = $collection->getRequiredOnSaveProperties();
		$this->assertCount(1, $requiredOnSaveProperties);
		$this->assertTrue($requiredOnSaveProperties->has('requiredOnSave'));
		$this->assertFalse($requiredOnSaveProperties->has('required'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromPropertyDefinitions
	 */
	public function testFromPropertyDefinitions() {
		$definitions = [
			'name' => (new ModelPropertyDefinition())->type('string'),
			'age' => (new ModelPropertyDefinition())->type('int'),
		];

		// Create collection with initial values
		$collection = ModelPropertyCollection::fromPropertyDefinitions($definitions, [
			'name' => 'John',
			'age' => 30,
		]);

		$this->assertInstanceOf(ModelPropertyCollection::class, $collection);
		$this->assertCount(2, $collection);

		$values = $collection->getValues();
		$this->assertSame('John', $values['name']);
		$this->assertSame(30, $values['age']);

		// Test without initial values
		$collection = ModelPropertyCollection::fromPropertyDefinitions($definitions);
		$values = $collection->getValues();

		// Should use defaults from definitions (none in this case, so null)
		$this->assertNull($values['name']);
		$this->assertNull($values['age']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromPropertyDefinitions
	 */
	public function testFromPropertyDefinitionsWithInvalidKey() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Property key must be a string.');

		ModelPropertyCollection::fromPropertyDefinitions([
			0 => new ModelPropertyDefinition(),
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromPropertyDefinitions
	 */
	public function testFromPropertyDefinitionsWithInvalidDefinition() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Property definition must be an instance of ModelPropertyDefinition.');

		ModelPropertyCollection::fromPropertyDefinitions([
			'name' => 'not a definition',
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 */
	public function testConstructorWithEmptyProperties() {
		// Should now be allowed to create an empty collection
		$collection = new ModelPropertyCollection([]);

		$this->assertInstanceOf(ModelPropertyCollection::class, $collection);
		$this->assertSame(0, $collection->count());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isEmpty
	 */
	public function testIsEmpty() {
		// Empty collection
		$emptyCollection = new ModelPropertyCollection([]);
		$this->assertTrue($emptyCollection->isEmpty());

		// Non-empty collection
		$collection = $this->createTestCollection();
		$this->assertFalse($collection->isEmpty());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::filter
	 */
	public function testFilterToEmptyCollection() {
		$collection = $this->createTestCollection();

		// Filter to nothing
		$filtered = $collection->filter(function(ModelProperty $property) {
			return false; // Return no properties
		});

		$this->assertInstanceOf(ModelPropertyCollection::class, $filtered);
		$this->assertTrue($filtered->isEmpty());
		$this->assertSame(0, $filtered->count());
	}
}
