<?php

declare(strict_types=1);

namespace StellarWP\Models;

/**
 * A collection of properties for a model.
 *
 * Some philosophical notes:
 * 		* The collection is immutable. Once created, the collection cannot be changed.
 */
class ModelPropertyCollection implements \Countable, \IteratorAggregate {
	/**
	 * The properties.
	 *
	 * @var ModelProperty[]
	 */
	private array $properties = [];

	/**
	 * Constructor.
	 *
	 * @param array<string,ModelPropertyDefinition|array<string,mixed>> $properties
	 */
	public function __construct( array $properties = [] ) {
		foreach ( $properties as $key => $definition ) {
			if ( ! is_string( $key ) ) {
				throw new \InvalidArgumentException( 'Property key must be a string.' );
			}

			if ( $definition instanceof ModelPropertyDefinition ) {
				$this->properties[$key] = new ModelProperty( $key, $definition );
			} else {
				$this->properties[$key] = new ModelProperty( $key, ModelPropertyDefinition::fromShorthand( $definition ) );
			}
		}
	}

	/**
	 * Count the number of properties.
	 */
	public function count(): int {
		return count($this->properties);
	}

	/**
	 * Get an iterator for the properties.
	 */
	public function getIterator(): \Traversable {
		return new \ArrayIterator($this->properties);
	}

	/**
	 * Get a property by key.
	 */
	public function getProperty( string $key ): ?ModelProperty {
		return $this->properties[$key] ?? null;
	}

	public function getRequiredProperties(): array {
		return array_filter( $this->properties, function( ModelProperty $property ) {
			return $property->getDefinition()->isRequired();
		} );
	}

	public function getRequiredOnSaveProperties(): array {
		return array_filter( $this->properties, function( ModelProperty $property ) {
			return $property->getDefinition()->isRequiredOnSave();
		} );
	}

	/**
	 * Check if the collection has a property with the given key.
	 */
	public function hasProperty( string $key ): bool {
		return isset( $this->properties[$key] );
	}
}
