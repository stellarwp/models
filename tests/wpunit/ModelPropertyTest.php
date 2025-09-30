<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\Exceptions\ReadOnlyPropertyException;
use StellarWP\Models\ModelProperty;
use StellarWP\Models\ModelPropertyDefinition;
use StellarWP\Models\Tests\ModelsTestCase;

/**
 * @coversDefaultClass \StellarWP\Models\ModelProperty
 */
class ModelPropertyTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::getKey
	 * @covers ::getDefinition
	 * @covers ::getValue
	 * @covers ::isSet
	 */
	public function testConstructor() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition, 'John');

		$this->assertSame('name', $property->getKey());
		$this->assertSame($definition, $property->getDefinition());
		$this->assertSame('John', $property->getValue());
		$this->assertTrue($property->isSet());

		// Test with no initial value
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('age', $definition);

		$this->assertSame('age', $property->getKey());
		$this->assertNull($property->getValue());
		$this->assertFalse($property->isSet());

		// Test with definition default value
		$definition = new ModelPropertyDefinition();
		$definition->default('default-value');
		$property = new ModelProperty('withDefault', $definition);

		$this->assertSame('withDefault', $property->getKey());
		$this->assertSame('default-value', $property->getValue());
		$this->assertTrue($property->isSet());

		// Test that constructor value takes precedence over definition default
		$definition = new ModelPropertyDefinition();
		$definition->default('definition-default');
		$property = new ModelProperty('precedence', $definition, 'constructor-value');

		$this->assertSame('precedence', $property->getKey());
		$this->assertSame('constructor-value', $property->getValue());
		$this->assertTrue($property->isSet());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 */
	public function testConstructorShouldThrowExceptionForInvalidValue() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Default value is not valid for the property.');

		$definition = new ModelPropertyDefinition();
		$definition->type('int');

		new ModelProperty('age', $definition, 'not-an-int');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isDirty
	 * @covers ::isClean
	 * @covers ::setValue
	 */
	public function testIsDirtyAndIsClean() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition, 'John');

		// Initially clean
		$this->assertFalse($property->isDirty());
		$this->assertTrue($property->isClean());

		// Set to same value - should still be clean
		$property->setValue('John');
		$this->assertFalse($property->isDirty());
		$this->assertTrue($property->isClean());

		// Set to different value - should be dirty
		$property->setValue('Jane');
		$this->assertTrue($property->isDirty());
		$this->assertFalse($property->isClean());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueShouldThrowExceptionForInvalidValue() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Value is not valid for the property.');

		$definition = new ModelPropertyDefinition();
		$definition->type('int');
		$property = new ModelProperty('age', $definition, 30);

		$property->setValue('not-an-int');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getOriginalValue
	 * @covers ::setValue
	 */
	public function testGetOriginalValue() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition, 'John');

		// Original value should be set from constructor
		$this->assertSame('John', $property->getOriginalValue());

		// Set to different value
		$property->setValue('Jane');

		// Original value should not change
		$this->assertSame('John', $property->getOriginalValue());
		// Current value should be updated
		$this->assertSame('Jane', $property->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::commitChanges
	 * @covers ::setValue
	 */
	public function testCommitChanges() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition, 'John');

		// Change the value
		$property->setValue('Jane');
		$this->assertTrue($property->isDirty());

		// Commit changes
		$property->commitChanges();

		// Should no longer be dirty
		$this->assertFalse($property->isDirty());

		// Original value should be updated
		$this->assertSame('Jane', $property->getOriginalValue());
		$this->assertSame('Jane', $property->getValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::revertChanges
	 * @covers ::setValue
	 */
	public function testRevertChanges() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition, 'John');

		// Change the value
		$property->setValue('Jane');
		$this->assertTrue($property->isDirty());

		// Revert changes
		$property->revertChanges();

		// Should no longer be dirty
		$this->assertFalse($property->isDirty());

		// Value should be reverted
		$this->assertSame('John', $property->getValue());
		$this->assertSame('John', $property->getOriginalValue());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isSet
	 */
	public function testIsSet() {
		$definition = new ModelPropertyDefinition();

		// Test with initial value
		$property = new ModelProperty('name', $definition, 'John');
		$this->assertTrue($property->isSet());

		// Test with no initial value
		$property = new ModelProperty('name', $definition);
		$this->assertFalse($property->isSet());

		// Test with default value
		$definition = (new ModelPropertyDefinition())->default('default-value');
		$property = new ModelProperty('name', $definition);
		$this->assertTrue($property->isSet());

		// Test with null value
		$definition = (new ModelPropertyDefinition())->nullable();
		$property = new ModelProperty('name', $definition);
		$this->assertFalse($property->isSet());
		$property->setValue(null);
		$this->assertTrue($property->isSet());

		// Test that a null initial value is also considered set
		$property = new ModelProperty('name', $definition, null);
		$this->assertTrue($property->isSet());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::unset
	 * @covers ::isSet
	 */
	public function testUnset() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition, 'John');

		// Initially set
		$this->assertTrue($property->isSet());

		// Unset
		$property->unset();

		// Should be unset
		$this->assertFalse($property->isSet());
		$this->assertNull($property->getValue());

		// Should be dirty since original had a value
		$this->assertTrue($property->isDirty());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::unset
	 * @covers ::isSet
	 */
	public function testUnsetWithNoOriginalValue() {
		$definition = new ModelPropertyDefinition();
		$property = new ModelProperty('name', $definition);

		// Initially not set
		$this->assertFalse($property->isSet());

		// Set a value
		$property->setValue('John');
		$this->assertTrue($property->isSet());
		$this->assertTrue($property->isDirty());

		// Unset
		$property->unset();

		// Should be unset
		$this->assertFalse($property->isSet());
		$this->assertNull($property->getValue());

		// Should no longer be dirty because original value was not set
		$this->assertFalse($property->isDirty());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::setValue
	 */
	public function testReadonlyPropertyCanBeSetInConstructor() {
		$definition = new ModelPropertyDefinition();
		$definition->type('string')->readonly();

		// Should be able to set readonly property in constructor
		$property = new ModelProperty('id', $definition, 'initial-value');

		$this->assertSame('initial-value', $property->getValue());
		$this->assertTrue($property->isSet());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testReadonlyPropertyCannotBeModified() {
		$definition = new ModelPropertyDefinition();
		$definition->type('string')->readonly();

		$property = new ModelProperty('id', $definition, 'initial-value');

		$this->expectException(ReadOnlyPropertyException::class);
		$this->expectExceptionMessage('Cannot modify readonly property "id".');

		$property->setValue('new-value');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::unset
	 */
	public function testReadonlyPropertyCannotBeUnset() {
		$definition = new ModelPropertyDefinition();
		$definition->type('string')->readonly();

		$property = new ModelProperty('id', $definition, 'initial-value');

		$this->expectException(ReadOnlyPropertyException::class);
		$this->expectExceptionMessage('Cannot unset readonly property "id".');

		$property->unset();
	}
}
