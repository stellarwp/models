<?php

declare(strict_types=1);

namespace StellarWP\Models;

use InvalidArgumentException;

class ModelProperty {
	private const NO_INITIAL_VALUE = '__NO_STELLARWP_MODELS_INITIAL_VALUE__';

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

	/**
	 * @since 2.0.0
	 *
	 * @param mixed $initialValue The optional, initial value of the property, which takes precedence over the definition's default value.
	 */
	public function __construct( string $key, ModelPropertyDefinition $definition, $initialValue = self::NO_INITIAL_VALUE ) {
		$this->key = $key;
		$this->definition = $definition->lock();

		if ( $initialValue === self::NO_INITIAL_VALUE && $this->definition->hasDefault() ) {
			$initialValue = $this->definition->getDefault();
		}

		if ( $initialValue !== self::NO_INITIAL_VALUE ) {
			if ( ! $this->definition->isValidValue( $initialValue ) ) {
				throw new \InvalidArgumentException( 'Default value is not valid for the property.' );
			}

			$this->value = $initialValue;
			$this->originalValue = $this->value;
		}
	}

	/**
	 * Get the definition of the property.
	 *
	 * @since 2.0.0
	 */
	public function getDefinition(): ModelPropertyDefinition {
		return $this->definition;
	}

	/**
	 * Get the key of the property.
	 *
	 * @since 2.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * Get the original value of the property.
	 *
	 * @since 2.0.0
	 */
	public function getOriginalValue() {
		return $this->originalValue;
	}

	/**
	 * Get the value of the property.
	 *
	 * @since 2.0.0
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns whether the property has not changed.
	 *
	 * @since 2.0.0
	 */
	public function isClean(): bool {
		return !$this->isDirty;
	}

	/**
	 * Returns whether the property has changed.
	 *
	 * @since 2.0.0
	 */
	public function isDirty(): bool {
		return $this->isDirty;
	}

	/**
	 * Returns whether the property value has been set.
	 *
	 * @since 2.0.0
	 */
	public function isSet(): bool {
		return isset( $this->value );
	}

	/**
	 * Reverts the changes to the property — restoring the original value and clearing the dirty flag.
	 *
	 * @since 2.0.0
	 */
	public function revertChanges(): void {
		if ( isset( $this->originalValue ) ) {
			$this->value = $this->originalValue;
		} else {
			unset( $this->value );
		}

		$this->isDirty = false;
	}

	/**
	 * Commits the changes to the property — syncing the original value with the current and resetting the dirty flag.
	 *
	 * @since 2.0.0
	 */
	public function commitChanges(): void {
		$this->originalValue = $this->value;
		$this->isDirty = false;
	}

	/**
	 * Sets the value of the property.
	 *
	 * @since 2.0.0
	 */
	public function setValue( $value ): self {
		if ( ! $this->definition->isValidValue( $value ) ) {
			throw new \InvalidArgumentException( 'Value is not valid for the property.' );
		}

		$this->value = $value;
		$this->isDirty = false;

		return $this;
	}
}
