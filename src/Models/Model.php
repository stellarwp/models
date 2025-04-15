<?php

namespace StellarWP\Models;

use JsonSerializable;
use RuntimeException;
use StellarWP\Models\Contracts\Arrayable;
use StellarWP\Models\Contracts\Model as ModelInterface;
use StellarWP\Models\ValueObjects\Relationship;

abstract class Model implements ModelInterface, Arrayable, JsonSerializable {
	public const BUILD_MODE_STRICT = 0;
	public const BUILD_MODE_IGNORE_MISSING = 1;
	public const BUILD_MODE_IGNORE_EXTRA = 2;

	/**
	 * The model's properties.
	 *
	 * @var ModelPropertyCollection
	 */
	protected ModelPropertyCollection $propertyCollection;

	/**
	 * The model properties assigned to their types.
	 *
	 * @var array<string,string|array>
	 */
	protected static $properties = [];

	/**
	 * The model relationships assigned to their relationship types.
	 *
	 * @var array<string,string>
	 */
	protected static $relationships = [];

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
	 * @param array<string,mixed> $attributes Attributes.
	 */
	public function __construct( array $attributes = [] ) {
		$this->propertyCollection = ModelPropertyCollection::fromPropertyDefinitions( self::getPropertyDefinitions(), $attributes );
	}

	/**
	 * Casts the value for the type, used when constructing a model from query data. If the model needs to support
	 * additional types, especially class types, this method can be overridden.
	 *
	 * @since 2.0.0 changed to static
	 *
	 * @param string $type
	 * @param mixed  $value The query data value to cast, probably a string.
	 * @param string $property The property being casted.
	 *
	 * @return mixed
	 */
	protected static function castValueForProperty( ModelPropertyDefinition $definition, $value, string $property ) {
		if ( $definition->isValidValue( $value ) || $value === null ) {
			return $value;
		}

		if ( $definition->canCast() ) {
			return $definition->cast( $value );
		}

		$type = $definition->getType();
		if ( count( $type ) !== 1 ) {
			throw new \InvalidArgumentException( "Property '$property' has multiple types: " . implode( ', ', $type ) . ". To support additional types, implement a custom castValueForProperty() method." );
		}

		switch ( $type[0] ) {
			case 'int':
				return (int) $value;
			case 'string':
				return (string) $value;
			case 'bool':
				return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			case 'array':
				return (array) $value;
			case 'float':
				return (float) filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION );
			default:
				Config::throwInvalidArgumentException( "Unexpected type: '$type'. To support additional types, overload this method or use Definition casting." );
		}
	}

	/**
	 * Commit the changes to the properties.
	 *
	 * @since 2.0.0
	 */
	public function commitChanges(): void {
		$this->propertyCollection->commitChangedProperties();
	}

	/**
	 * Discard the changes to the properties.
	 *
	 * @since 2.0.0
	 */
	public function discardChanges(): void {
		$this->propertyCollection->revertChangedProperties();
	}

	/**
	 * A more robust, alternative way to define properties for the model than static::$properties.
	 *
	 * @return array<string,ModelPropertyDefinition|array<string,mixed>>
	 */
	protected static function properties(): array {
		return [];
	}

	/**
	 * Fills the model with an array of attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 *
	 * @return ModelInterface
	 */
	public function fill( array $attributes ) : ModelInterface {
		$this->propertyCollection->setValues( $attributes );

		return $this;
	}

	/**
	 * Returns an attribute from the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key     Attribute name.
	 * @param mixed  $default Default value. Default is null.
	 *
	 * @return mixed
	 */
	public function getAttribute( string $key, $default = null ) {
		return $this->propertyCollection->has( $key ) ? $this->propertyCollection->get( $key )->getValue() : $default;
	}

	/**
	 * Returns the attributes that have been changed since last sync.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function getDirty() : array {
		return $this->propertyCollection->getDirtyValues();
	}

	public static function getPropertyDefinition( string $key ): ModelPropertyDefinition {
		$definitions = static::getPropertyDefinitions();

		if ( ! isset( $definitions[ $key ] ) ) {
			throw new \InvalidArgumentException( 'Property ' . $key . ' does not exist.' );
		}

		return $definitions[ $key ];
	}

	/**
	 * Returns the parsed property definitions for the model.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string,ModelPropertyDefinition>
	 */
	public static function getPropertyDefinitions(): array {
		static $definitions = null;

		if ( $definitions === null ) {
			$definitions = array_merge( static::$properties, static::properties() );

			foreach ( $definitions as $key => $definition ) {
				if ( ! is_string( $key ) ) {
					throw new \InvalidArgumentException( 'Property key must be a string.' );
				}

				if ( ! $definition instanceof ModelPropertyDefinition ) {
					$definition = ModelPropertyDefinition::fromShorthand( $definition );
				}

				$definitions[ $key ] = $definition->lock();
			}
		}

		return $definitions;
	}

	/**
	 * Returns the model's original attribute values.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key Attribute name.
	 *
	 * @return mixed|array
	 */
	public function getOriginal( string $key = null ) {
		return $key ? $this->propertyCollection->getOrFail( $key )->getOriginalValue() : $this->propertyCollection->getOriginalValues();
	}

	/**
	 * Whether the property is set or not. This is different from isset() because this considers a `null` value as
	 * being set. Defaults are considered set as well.
	 *
	 * @since 1.2.2
	 *
	 * @return boolean
	 */
	public function isSet( string $key ): bool {
		return $this->propertyCollection->has( $key );
	}

	/**
	 * Returns a relationship.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Relationship name.
	 *
	 * @return Model|Model[]
	 */
	protected function getRelationship( string $key ) {
		if ( ! is_callable( [ $this, $key ] ) ) {
			$exception = Config::getInvalidArgumentException();
			throw new $exception( "$key() does not exist." );
		}

		if ( $this->hasCachedRelationship( $key ) ) {
			return $this->cachedRelations[ $key ];
		}

		$relationship = static::$relationships[ $key ];

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
	 * Returns true if an attribute exists. Otherwise, false.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return bool
	 */
	protected function hasAttribute( string $key ) : bool {
		return $this->propertyCollection->has( $key );
	}

	/**
	 * Checks whether a relationship has already been loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Relationship name.
	 *
	 * @return bool
	 */
	protected function hasCachedRelationship( string $key ) : bool {
		return array_key_exists( $key, $this->cachedRelations );
	}

	/**
	 * Determines if the model has the given property.
	 *
	 * @since 2.0.0 changed to static
	 * @since 1.0.0
	 *
	 * @param string $key Property name.
	 *
	 * @return bool
	 */
	public static function hasProperty( string $key ) : bool {
		return isset( static::getPropertyDefinitions()[ $key ] );
	}

	/**
	 * Determine if a given attribute is clean.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute Attribute name.
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
	 * @param string|null $attribute Attribute name.
	 *
	 * @return bool
	 */
	public function isDirty( string $attribute = null ) : bool {
		if ( ! $attribute ) {
			return $this->propertyCollection->isDirty();
		}

		return $this->propertyCollection->getOrFail( $attribute )->isDirty();
	}

	/**
	 * Validates an attribute to a PHP type.
	 *
	 * @since 2.0.0
	 * @since 1.0.0
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return bool
	 */
	public static function isPropertyTypeValid( string $key, $value ) : bool {
		return static::getPropertyDefinition( $key )->isValidValue( $value );
	}

	/**
	 * Returns the object vars.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return get_object_vars( $this );
	}

	/**
	 * Constructs a model instance from database query data.
	 *
	 * @param object|array $queryData
	 * @param int $mode The level of strictness to take when constructing the object, by default it will ignore extra keys but error on missing keys.
	 * @return static
	 */
	public static function fromData($data, $mode = self::BUILD_MODE_IGNORE_EXTRA) {
		if ( ! is_object( $data ) && ! is_array( $data ) ) {
			Config::throwInvalidArgumentException( 'Query data must be an object or array' );
		}

		$data = (array) $data;

		// If we're not ignoring extra keys, check for them and throw an exception if any are found.
		if ( ! ($mode & self::BUILD_MODE_IGNORE_EXTRA) ) {
			$extraKeys = array_diff_key( (array) $data, static::$properties );
			if ( ! empty( $extraKeys ) ) {
				Config::throwInvalidArgumentException( 'Query data contains extra keys: ' . implode( ', ', array_keys( $extraKeys ) ) );
			}
		}

		if ( ! ($mode & self::BUILD_MODE_IGNORE_MISSING) ) {
			$missingKeys = array_diff_key( static::$properties, (array) $data );
			if ( ! empty( $missingKeys ) ) {
				Config::throwInvalidArgumentException( 'Query data is missing keys: ' . implode( ', ', array_keys( $missingKeys ) ) );
			}
		}

		$initialValues = [];

		foreach (static::$properties as $key => $type) {
			if ( ! array_key_exists( $key, $data ) ) {
				Config::throwInvalidArgumentException( "Property '$key' does not exist." );
			}

			// Remember not to use $type, as it may be an array that includes the default value. Safer to use getPropertyType().
			$initialValues[ $key ] = static::castValueForProperty( static::getPropertyDefinition( $key ), $data[ $key ], $key );
		}

		return new static( $initialValues );
	}

	/**
	 * Returns the property keys.
	 *
	 * @since 1.0.0
	 *
	 * @return int[]|string[]
	 */
	public static function propertyKeys() : array {
		return array_keys( static::$properties );
	}

	/**
	 * Sets an attribute on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return ModelInterface
	 */
	public function setAttribute( string $key, $value ) : ModelInterface {
		$this->propertyCollection->getOrFail( $key )->setValue( $value );

		return $this;
	}

	/**
	 * Sets multiple attributes on the model.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string,mixed> $attributes Attributes to set.
	 *
	 * @return ModelInterface
	 */
	public function setAttributes( array $attributes ) : ModelInterface {
		foreach ( $attributes as $key => $value ) {
			$this->setAttribute( $key, $value );
		}

		return $this;
	}

	/**
	 * Syncs the original attributes with the current.
	 *
	 * This is considered an alias of `commitChanges()` and is here for backwards compatibility.
	 *
	 * @since 1.0.0
	 *
	 * @return ModelInterface
	 */
	public function syncOriginal() : ModelInterface {
		$this->commitChanges();

		return $this;
	}

	/**
	 * Returns attributes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function toArray() : array {
		return $this->propertyCollection->getValues();
	}

	/**
	 * Dynamically retrieves attributes on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( array_key_exists( $key, static::$relationships ) ) {
			return $this->getRelationship( $key );
		}

		return $this->getAttribute( $key );
	}

	/**
	 * Determines if an attribute exists on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return bool
	 */
	public function __isset( string $key ) {
		return $this->propertyCollection->has( $key );
	}

	/**
	 * Dynamically sets attributes on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return void
	 */
	public function __set( string $key, $value ) {
		$this->setAttribute( $key, $value );
	}
}
