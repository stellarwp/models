<?php

declare(strict_types=1);

namespace StellarWP\Models;

use Closure;

class ModelPropertyDefinition {
	/**
	 * The default value of the property.
	 *
	 * @var mixed|Closure
	 */
	private $default;

	/**
	 * The method to cast the property value.
	 *
	 * @var Closure|null A closure that accepts the value and property instance as parameters and returns the cast value.
	 */
	private Closure $castMethod;

	/**
	 * Whether the definition is locked. Once locked, the definition cannot be changed.
	 */
	private bool $locked = false;

	/**
	 * Whether the property is nullable.
	 */
	private bool $nullable = false;

	/**
	 * Whether the property is required.
	 */
	private bool $required = false;

	/**
	 * Whether the property is required on save.
	 */
	private bool $requiredOnSave = false;

	/**
	 * The type of the property.
	 *
	 * @var string[]
	 */
	private array $type = ['string'];

	/**
	 * Set the default value of the property.
	 *
	 * @param mixed|Closure $default The default value of the property.
	 */
	public function default( $default ): self {
		$this->checkLock();

		$this->default = $default;

		return $this;
	}

	/**
	 * Whether the property can cast the value.
	 */
	public function canCast(): bool {
		return isset( $this->castMethod );
	}

	/**
	 * Cast the property value.
	 *
	 * @param mixed $value The value to cast.
	 */
	public function cast( $value ) {
		$this->checkLock();

		if ( ! $this->canCast() ) {
			throw new \RuntimeException( 'No cast method set' );
		}

		$castMethod = $this->castMethod;

		return $castMethod( $value, $this );
	}

	/**
	 * Provides a method to cast the property value.
	 */
	public function castWith( callable $castMethod ): self {
		$this->checkLock();

		$this->castMethod = Closure::fromCallable( $castMethod );

		return $this;
	}

	/**
	 * Check if the property is locked and throw an exception if it is.
	 */
	private function checkLock(): void {
		if ( $this->locked ) {
			throw new \RuntimeException( 'Property is locked' );
		}
	}

	/**
	 * Create a property definition from a shorthand string or array.
	 * @param string|array{0:string,1:bool} $definition
	 */
	public static function fromShorthand( $definition ): self {
		$property = new self();

		if ( is_string( $definition ) ) {
			$property->type( $definition );
		} else if ( is_array( $definition ) && 2 === count( $definition ) ) {
			$property->type( $definition[0] );
			$property->default( $definition[1] );
		} else {
			throw new \InvalidArgumentException( 'Invalid shorthand property definition' );
		}

		// Nullable for backwards compatibility
		$property->nullable();

		return $property;
	}

	/**
	 * Get the default value of the property.
	 *
	 * @return mixed
	 */
	public function getDefault() {
		if ( $this->default instanceof Closure ) {
			$default = $this->default;

			return $default();
		}

		return $this->default;
	}

	/**
	 * Get the type of the property.
	 *
	 * @return string[]
	 */
	public function getType(): array {
		return $this->type;
	}

	/**
	 * Whether the property has a default value.
	 */
	public function hasDefault(): bool {
		return isset( $this->default );
	}

	/**
	 * Whether the property is locked.
	 *
	 * @return bool
	 */
	public function isLocked(): bool {
		return $this->locked;
	}

	/**
	 * Whether the property is nullable.
	 */
	public function isNullable(): bool {
		return $this->nullable;
	}

	/**
	 * Whether the property is required.
	 */
	public function isRequired(): bool {
		return $this->required;
	}

	/**
	 * Whether the property is required on save.
	 */
	public function isRequiredOnSave(): bool {
		return $this->requiredOnSave;
	}

	/**
	 * Whether the property is valid for the given value.
	 */
	public function isValidValue( $value ): bool {
		$valueType = gettype( $value );

		switch ( $valueType ) {
			case 'NULL':
				return $this->nullable;
			case 'integer':
				return $this->supportsType( 'int' );
			case 'string':
				return $this->supportsType( 'string' );
			case 'boolean':
				return $this->supportsType( 'bool' );
			case 'array':
				return $this->supportsType( 'array' );
			case 'double':
				return $this->supportsType( 'float' );
			case 'object':
				if ( $this->supportsType( 'object' ) ) {
					return true;
				} else {
					$class = get_class( $value );
					return $this->supportsType( $class );
				}
			default:
				return false;
		}
	}

	/**
	 * Locks the property so it cannot be changed.
	 * Note that once locked the property cannot be unlocked.
	 */
	public function lock(): self {
		$this->locked = true;

		return $this;
	}

	public function nullable(): self {
		$this->checkLock();

		$this->nullable = true;

		return $this;
	}

	/**
	 * Makes the property required.
	 */
	public function required(): self {
		$this->checkLock();

		$this->required = true;

		return $this;
	}

	/**
	 * Makes the property required on save.
	 */
	public function requiredOnSave(): self {
		$this->checkLock();

		$this->requiredOnSave = true;

		return $this;
	}

	/**
	 * Whether the property supports the given type.
	 */
	public function supportsType( string $type ): bool {
		return in_array( $type, $this->type, true );
	}

	/**
	 * Set the type of the property.
	 *
	 * @param string[] $types The types of the property, multiple types are considered a union type.
	 */
	public function type( string ...$types ): self {
		$this->checkLock();

		$this->type = $types;

		return $this;
	}
}
