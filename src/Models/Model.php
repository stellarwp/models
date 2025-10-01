<?php

namespace StellarWP\Models;

use InvalidArgumentException;
use JsonSerializable;
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
	 * @var array<string,string|array{0:string,1:mixed}>
	 */
	protected static array $properties = [];

	/**
	 * The model relationships assigned to their relationship types.
	 *
	 * @var array<string,string>
	 */
	protected static array $relationships = [];

	/**
	 * Relationships that have already been loaded and don't need to be loaded again.
	 *
	 * @var array<string,Model|list<Model>|null>
	 */
	private array $cachedRelations = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 */
	final public function __construct( array $attributes = [] ) {
		$this->propertyCollection = ModelPropertyCollection::fromPropertyDefinitions( static::getPropertyDefinitions(), $attributes );
		$this->afterConstruct();
	}

	/**
	 * This method is meant to be overridden by the model to perform actions after the model is constructed.
	 *
	 * @since 2.0.0
	 */
	protected function afterConstruct(): void {
		return;
	}

	/**
	 * Casts the value for the type, used when constructing a model from query data. If the model needs to support
	 * additional types, especially class types, this method can be overridden.
	 *
	 * Note: Type casting is performed at runtime based on property definitions. PHPStan cannot statically verify
	 * the resulting types, so we suppress type-checking errors for the cast operations.
	 *
	 * @since 2.0.0 changed to static
	 *
	 * @param ModelPropertyDefinition $definition The property definition.
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
			throw new InvalidArgumentException( "Property '$property' has multiple types: " . implode( ', ', $type ) . ". To support additional types, implement a custom castValueForProperty() method." );
		}

		// Runtime type casting based on property definition - PHPStan cannot verify this statically
		switch ( $type[0] ) {
			case 'int':
				return (int) $value; // @phpstan-ignore-line
			case 'string':
				return (string) $value; // @phpstan-ignore-line
			case 'bool':
				return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			case 'array':
				return (array) $value;
			case 'float':
				return (float) filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION );
			default:
				Config::throwInvalidArgumentException( "Unexpected type: '{$type[0]}'. To support additional types, overload this method or use Definition casting." );
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
	 * Revert the changes to a specific property.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Property name.
	 */
	public function revertChange( string $key ): void {
		$this->propertyCollection->getOrFail( $key )->revertChanges();
	}

	/**
	 * Discard the changes to the properties.
	 *
	 * @since 2.0.0
	 */
	public function revertChanges(): void {
		$this->propertyCollection->revertChangedProperties();
	}

	/**
	 * A more robust, alternative way to define properties for the model than static::$properties.
	 *
	 * @return array<string,ModelPropertyDefinition>
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
		$property = $this->propertyCollection->getOrFail( $key );

		return $property->isSet() ? $property->getValue() : $default;
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
			throw new InvalidArgumentException( 'Property ' . $key . ' does not exist.' );
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
		/** @var array<string,ModelPropertyDefinition>|null $cachedDefinitions */
		static $cachedDefinitions = null;

		if ( $cachedDefinitions === null ) {
			$definitions = array_merge( static::$properties, static::properties() );
			/** @var array<string,ModelPropertyDefinition> $processedDefinitions */
			$processedDefinitions = [];

			foreach ( $definitions as $key => $definition ) {
				if ( ! is_string( $key ) ) {
					throw new InvalidArgumentException( 'Property key must be a string.' );
				}

				if ( ! $definition instanceof ModelPropertyDefinition ) {
					$definition = ModelPropertyDefinition::fromShorthand( $definition );
				}

				$processedDefinitions[ $key ] = $definition->lock();
			}

			$cachedDefinitions = $processedDefinitions;
		}

		return $cachedDefinitions;
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
	public function getOriginal( ?string $key = null ) {
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
		return $this->propertyCollection->isSet( $key );
	}

	/**
	 * Checks if a method exists that returns a ModelQueryBuilder instance.
	 *
	 * @since 2.0.0
	 *
	 * @param string $method Method name.
	 *
	 * @return bool
	 */
	protected function hasRelationshipMethod( string $method ): bool {
		if ( ! method_exists( $this, $method ) ) {
			return false;
		}

		try {
			$reflectionMethod = new \ReflectionMethod( $this, $method );
			$returnType = $reflectionMethod->getReturnType();

			if ( ! $returnType instanceof \ReflectionNamedType ) {
				return false;
			}

			$typeName = $returnType->getName();

			return $typeName === ModelQueryBuilder::class || is_subclass_of( $typeName, ModelQueryBuilder::class );
		} catch ( \ReflectionException $e ) {
			return false;
		}
	}

	/**
	 * Returns a relationship.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Relationship name.
	 *
	 * @return Model|list<Model>|null
	 */
	protected function getRelationship( string $key ) {
		if ( ! $this->hasRelationshipMethod( $key ) ) {
			$exception = Config::getInvalidArgumentException();
			throw new $exception( "$key() does not exist." );
		}

		if ( $this->hasCachedRelationship( $key ) ) {
			return $this->cachedRelations[ $key ];
		}

		/** @var ModelQueryBuilder<Model> $queryBuilder */
		$queryBuilder = $this->$key();

		switch ( static::$relationships[ $key ] ) {
			case Relationship::BELONGS_TO:
			case Relationship::HAS_ONE:
				$result = $queryBuilder->get();
				/** @var Model|null $result */
				$this->cachedRelations[ $key ] = $result;
				return $result;
			case Relationship::HAS_MANY:
			case Relationship::BELONGS_TO_MANY:
			case Relationship::MANY_TO_MANY:
				$result = $queryBuilder->getAll();
				/** @var list<Model>|null $result */
				$this->cachedRelations[ $key ] = $result;
				return $result;
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
	 * Purges the entire relationship cache.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	protected function purgeRelationshipCache(): void {
		$this->cachedRelations = [];
	}

	/**
	 * Purges a specific relationship from the cache.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Relationship name.
	 *
	 * @return void
	 */
	protected function purgeRelationship( string $key ): void {
		if ( ! isset( static::$relationships[ $key ] ) ) {
			Config::throwInvalidArgumentException( "Relationship '$key' is not defined on this model." );
		}

		unset( $this->cachedRelations[ $key ] );
	}

	/**
	 * Updates the cached value for a given relationship.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Relationship name.
	 * @param Model|list<Model>|null $value The relationship value to cache.
	 *
	 * @return void
	 */
	protected function setCachedRelationship( string $key, $value ): void {
		if ( ! isset( static::$relationships[ $key ] ) ) {
			Config::throwInvalidArgumentException( "Relationship '$key' is not defined on this model." );
		}

		// Validate the value is a Model, array of Models, or null
		if ( $value !== null && ! $value instanceof Model && ! $this->isModelArray( $value ) ) {
			Config::throwInvalidArgumentException( "Relationship value must be a Model instance, an array of Model instances, or null." );
		}

		$this->cachedRelations[ $key ] = $value;
	}

	/**
	 * Checks if a value is an array of Model instances.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool
	 */
	private function isModelArray( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $item ) {
			if ( ! $item instanceof Model ) {
				return false;
			}
		}

		return true;
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
	public function isClean( ?string $attribute = null ) : bool {
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
	public function isDirty( ?string $attribute = null ) : bool {
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
	 * @param array<string,mixed>|object $data
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

		foreach (static::$properties as $key => $_) {
			if ( ! array_key_exists( $key, $data ) ) {
				// Skip missing properties when BUILD_MODE_IGNORE_MISSING is set
				if ( $mode & self::BUILD_MODE_IGNORE_MISSING ) {
					continue;
				}
				Config::throwInvalidArgumentException( "Property '$key' does not exist." );
			}

			$initialValues[ $key ] = static::castValueForProperty( static::getPropertyDefinition( $key ), $data[ $key ], $key );
		}

		return new static( $initialValues );
	}

	/**
	 * Returns the property keys.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string>
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
		return $this->propertyCollection->isSet( $key );
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

	/**
	 * Unset a property.
	 *
	 * @since 2.0.0
	 */
	public function __unset( string $key ) {
		$this->propertyCollection->unsetProperty( $key );
	}
}
