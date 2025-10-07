<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\ModelPropertyDefinition;
use StellarWP\Models\Tests\ModelsTestCase;

/**
 * @coversDefaultClass \StellarWP\Models\ModelPropertyDefinition
 */
class ModelPropertyDefinitionTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::default
	 * @covers ::hasDefault
	 * @covers ::getDefault
	 */
	public function testDefaultAndHasDefault() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->hasDefault());

		$definition->default('test');
		$this->assertTrue($definition->hasDefault());
		$this->assertSame('test', $definition->getDefault());

		// Test with closure
		$definition = new ModelPropertyDefinition();
		$definition->default(function() {
			return 'closure-value';
		});

		$this->assertTrue($definition->hasDefault());
		$this->assertSame('closure-value', $definition->getDefault());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::type
	 * @covers ::getType
	 * @covers ::supportsType
	 */
	public function testTypeAndGetType() {
		$definition = new ModelPropertyDefinition();

		// Default type should be string
		$this->assertSame(['string'], $definition->getType());

		// Set single type
		$definition->type('int');
		$this->assertSame(['int'], $definition->getType());

		// Set multiple types
		$definition = new ModelPropertyDefinition();
		$definition->type('int', 'string', 'bool');
		$this->assertSame(['int', 'string', 'bool'], $definition->getType());

		// Test supportsType
		$this->assertTrue($definition->supportsType('int'));
		$this->assertTrue($definition->supportsType('string'));
		$this->assertTrue($definition->supportsType('bool'));
		$this->assertFalse($definition->supportsType('array'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::nullable
	 * @covers ::isNullable
	 */
	public function testNullableAndIsNullable() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->isNullable());

		$definition->nullable();
		$this->assertTrue($definition->isNullable());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::required
	 * @covers ::isRequired
	 */
	public function testRequiredAndIsRequired() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->isRequired());

		$definition->required();
		$this->assertTrue($definition->isRequired());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::requiredOnSave
	 * @covers ::isRequiredOnSave
	 */
	public function testRequiredOnSaveAndIsRequiredOnSave() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->isRequiredOnSave());

		$definition->requiredOnSave();
		$this->assertTrue($definition->isRequiredOnSave());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::readonly
	 * @covers ::isReadonly
	 */
	public function testReadonlyAndIsReadonly() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->isReadonly());

		$definition->readonly();
		$this->assertTrue($definition->isReadonly());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::lock
	 * @covers ::isLocked
	 * @covers ::checkLock
	 */
	public function testLockAndIsLocked() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->isLocked());

		$definition->lock();
		$this->assertTrue($definition->isLocked());

		// Once locked, attempting to modify should throw exception
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Property is locked');

		$definition->default('something');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isValidValue
	 * @covers ::supportsType
	 */
	public function testIsValidValue() {
		$definition = new ModelPropertyDefinition();
		$definition->type('string');

		$this->assertTrue($definition->isValidValue('test'));
		$this->assertFalse($definition->isValidValue(123));

		// Test with nullable
		$definition->nullable();
		$this->assertTrue($definition->isValidValue(null));

		// Test with multiple types
		$definition = new ModelPropertyDefinition();
		$definition->type('int', 'string');

		$this->assertTrue($definition->isValidValue('test'));
		$this->assertTrue($definition->isValidValue(123));
		$this->assertFalse($definition->isValidValue(true));

		// Test with object type
		$definition = new ModelPropertyDefinition();
		$definition->type('object');

		$this->assertTrue($definition->isValidValue(new \stdClass()));
		$this->assertFalse($definition->isValidValue('test'));

		// Test with specific class
		$definition = new ModelPropertyDefinition();
		$definition->type(\stdClass::class);

		$this->assertTrue($definition->isValidValue(new \stdClass()));
		$this->assertFalse($definition->isValidValue(new \Exception()));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::castWith
	 * @covers ::cast
	 * @covers ::canCast
	 */
	public function testCastWithAndCast() {
		$definition = new ModelPropertyDefinition();

		$this->assertFalse($definition->canCast());

		$definition->castWith(function($value, $property) {
			return (int) $value;
		});

		$this->assertTrue($definition->canCast());
		$this->assertSame(123, $definition->cast('123'));

		// Test exception when no cast method set
		$definition = new ModelPropertyDefinition();

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('No cast method set');

		$definition->cast('test');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromShorthand
	 */
	public function testFromShorthand() {
		// Test with string
		$definition = ModelPropertyDefinition::fromShorthand('int');

		$this->assertSame(['int'], $definition->getType());
		$this->assertTrue($definition->isNullable());
		$this->assertFalse($definition->hasDefault());

		// Test with array
		$definition = ModelPropertyDefinition::fromShorthand(['string', 'default-value']);

		$this->assertSame(['string'], $definition->getType());
		$this->assertTrue($definition->isNullable());
		$this->assertTrue($definition->hasDefault());
		$this->assertSame('default-value', $definition->getDefault());

		// Test invalid shorthand
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid shorthand property definition');

		ModelPropertyDefinition::fromShorthand(['string', 'too', 'many', 'items']);
	}
}
