<?php

declare(strict_types=1);

namespace StellarWP\Models;

class ModelProperty {
	/**
	 * The property definition.
	 */
	private ModelPropertyDefinition $definition;

	/**
	 * Whether the property is dirty.
	 */
	private bool $isDirty = false;

	/**
	 * The key of the property.
	 */
	private string $key;

	/**
	 * The original value of the property.
	 */
	private $originalValue;

	/**
	 * The property value.
	 */
	private $value;

	public function __construct( string $key, ModelPropertyDefinition $definition ) {
		$this->key = $key;
		$this->definition = $definition->lock();

		if ( $this->definition->hasDefault() ) {
			if ( ! $this->isValidValue( $this->definition->getDefault() ) ) {
				throw new \InvalidArgumentException( 'Default value is not valid for the property.' );
			}

			$this->value = $this->definition->getDefault();
			$this->originalValue = $this->value;
		}
	}

	/**
	 * Get the definition of the property.
	 */
	public function getDefinition(): ModelPropertyDefinition {
		return $this->definition;
	}

	/**
	 * Get the key of the property.
	 */
	public function getKey(): string {
		return $this->key;
	}

	public function getOriginalValue() {
		return $this->originalValue;
	}

	public function getValue() {
		return $this->value;
	}

	public function isClean(): bool {
		return !$this->isDirty;
	}

	public function isDirty(): bool {
		return $this->isDirty;
	}

	/**
	 * Checks whether a given value is valid for the property.
	 */
	public function isValidValue( $value ): bool {
		$valueType = gettype( $value );

		switch ( $valueType ) {
			case 'NULL':
				return $this->definition->isNullable();
			case 'integer':
				return $this->definition->supportsType( 'int' );
			case 'string':
				return $this->definition->supportsType( 'string' );
			case 'boolean':
				return $this->definition->supportsType( 'bool' );
			case 'array':
				return $this->definition->supportsType( 'array' );
			case 'double':
				return $this->definition->supportsType( 'float' );
			case 'object':
				if ( $this->definition->supportsType( 'object' ) ) {
					return true;
				} else {
					$class = get_class( $value );
					return $this->definition->supportsType( $class );
				}

			default:
				return false;
		}
	}

	public function reset(): void {
		$this->value = $this->originalValue;
		$this->isDirty = false;
	}

	public function value( $value ) {
		$this->value = $value;
		$this->isDirty = false;

		return $this;
	}
}
