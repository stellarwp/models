<?php

namespace StellarWP\Models;

use JsonSerializable;
use RuntimeException;
use StellarWP\Models\Contracts\Arrayable;
use StellarWP\Models\Contracts\Model as ModelInterface;
use StellarWP\Models\ValueObjects\Relationship;

abstract class Model implements ModelInterface, Arrayable, JsonSerializable {
	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * The model attribute's original state.
	 *
	 * @var array
	 */
	protected $original = [];

	/**
	 * The model properties assigned to their types
	 *
	 * @var array
	 */
	protected $properties = [];

	/**
	 * The model relationships assigned to their relationship types
	 *
	 * @var array
	 */
	protected $relationships = [];

	/**
	 * Relationships that have already been loaded and don't need to be loaded again.
	 *
	 * @var Model[]
	 */
	private $cachedRelations = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes
	 */
	public function __construct( array $attributes = [] ) {
		$this->fill( array_merge( $this->getPropertyDefaults(), $attributes ) );

		$this->syncOriginal();
	}

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes
	 *
	 * @return ModelInterface
	 */
	public function fill( array $attributes ) : ModelInterface {
		foreach ( $attributes as $key => $value ) {
			$this->setAttribute( $key, $value );
		}
		$this->attributes = $attributes;

		return $this;
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 *
	 * @throws RuntimeException
	 */
	public function getAttribute( string $key ) {
		$this->validatePropertyExists( $key );

		return $this->attributes[ $key ] ?? null;
	}

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function getDirty() : array {
		$dirty = [];

		foreach ( $this->attributes as $key => $value ) {
			if ( ! array_key_exists( $key, $this->original ) || $value !== $this->original[ $key ] ) {
				$dirty[ $key ] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Get the model's original attribute values.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key
	 *
	 * @return mixed|array
	 */
	public function getOriginal( string $key = null ) {
		return $key ? $this->original[ $key ] : $this->original;
	}

	/**
	 * Get the default for a property if one is provided, otherwise default to null
	 *
	 * @since 1.0.0
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	protected function getPropertyDefault( $key ) {
		return is_array( $this->properties[ $key ] ) && isset( $this->properties[ $key ][1] )
			? $this->properties[ $key ][1]
			: null;
	}

	/**
	 * Returns the defaults for all the properties. If a default is omitted it defaults to null.
	 *
	 * @since 1.0.0
	 */
	protected function getPropertyDefaults() : array {
		$defaults = [];
		foreach ( array_keys( $this->properties ) as $property ) {
			$defaults[ $property ] = $this->getPropertyDefault( $property );
		}

		return $defaults;
	}

	/**
	 * Get the property type
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function getPropertyType( string $key ) : string {
		$type = is_array( $this->properties[ $key ] ) ? $this->properties[ $key ][0] : $this->properties[ $key ];

		return strtolower( trim( $type ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @param $key
	 *
	 * @return Model|Model[]
	 */
	protected function getRelationship( $key ) {
		if ( ! is_callable( [ $this, $key ] ) ) {
			$exception = Config::getInvalidArgumentException();
			throw new $exception( "$key() does not exist." );
		}

		if ( $this->hasCachedRelationship( $key ) ) {
			return $this->cachedRelations[ $key ];
		}

		$relationship = $this->relationships[ $key ];

		switch ( $relationship ) {
			case Relationship::BELONGS_TO:
			case Relationship::HAS_ONE:
				return $this->cachedRelations[ $key ] = $this->$key()->get();
			case Relationship::HAS_MANY:
			case Relationship::BELONGS_TO_MANY:
			case Relationship::MANY_TO_MANY:
				return $this->cachedRelations[ $key ] = $this->$key()->getAll();
		}

		return null;
	}

	/**
	 * Checks whether a relationship has already been loaded.
	 *
	 * @since 1.0.0
	 */
	protected function hasCachedRelationship( string $key ) : bool {
		return array_key_exists( $key, $this->cachedRelations );
	}

	/**
	 * Determines if the model has the given property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasProperty( string $key ) : bool {
		return array_key_exists( $key, $this->properties );
	}

	/**
	 * Determine if a given attribute is clean.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute
	 *
	 * @return bool
	 */
	public function isClean( string $attribute = null ) : bool {
		return ! $this->isDirty( $attribute );
	}

	/**
	 * Determine if a given attribute is dirty.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute
	 *
	 * @return bool
	 */
	public function isDirty( string $attribute = null ) : bool {
		if ( ! $attribute ) {
			return (bool) $this->getDirty();
		}

		return array_key_exists( $attribute, $this->getDirty() );
	}

	/**
	 * Validate an attribute to a PHP type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function isPropertyTypeValid( string $key, $value ) : bool {
		if ( is_null( $value ) ) {
			return true;
		}

		$type = $this->getPropertyType( $key );

		switch ( $type ) {
			case 'int':
				return is_int( $value );
			case 'string':
				return is_string( $value );
			case 'bool':
				return is_bool( $value );
			case 'array':
				return is_array( $value );
			default:
				return $value instanceof $type;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function jsonSerialize() {
		return get_object_vars( $this );
	}

	/**
	 * Get the property keys.
	 *
	 * @since 1.0.0
	 *
	 * @return int[]|string[]
	 */
	public static function propertyKeys() : array {
		return array_keys( ( new static() )->properties );
	}

	/**
	 * Set an attribute on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return ModelInterface
	 */
	public function setAttribute( string $key, $value ) : ModelInterface {
		$this->validatePropertyExists( $key );
		$this->validatePropertyType( $key, $value );

		$this->attributes[ $key ] = $value;

		return $this;
	}

	/**
	 * Sync the original attributes with the current.
	 *
	 * @since 1.0.0
	 *
	 * @return ModelInterface
	 */
	public function syncOriginal() : ModelInterface {
		$this->original = $this->attributes;

		return $this;
	}

	/**
	 * @since 1.0.0
	 */
	public function toArray() : array {
		return $this->attributes;
	}

	/**
	 * Validates that the given property exists
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function validatePropertyExists( string $key ) {
		if ( ! $this->hasProperty( $key ) ) {
			$exception = Config::getInvalidArgumentException();
			throw new $exception( "Invalid property. '$key' does not exist." );
		}
	}

	/**
	 * Validates that the given value is a valid type for the given property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	protected function validatePropertyType( string $key, $value ) {
		if ( ! $this->isPropertyTypeValid( $key, $value ) ) {
			$type = $this->getPropertyType( $key );

			$exception = Config::getInvalidArgumentException();
			throw new $exception( "Invalid attribute assignment. '$key' should be of type: '$type'" );
		}
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( array_key_exists( $key, $this->relationships ) ) {
			return $this->getRelationship( $key );
		}

		return $this->getAttribute( $key );
	}

	/**
	 * Determine if an attribute exists on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function __isset( string $key ) {
		return isset( $this->attributes[ $key ] );
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function __set( string $key, $value ) {
		$this->setAttribute( $key, $value );
	}
}
