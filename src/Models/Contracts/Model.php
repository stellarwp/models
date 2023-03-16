<?php

namespace StellarWP\Models\Contracts;

use RuntimeException;

interface Model {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 */
	public function __construct( array $attributes = [] );

	/**
	 * Fills the model with an array of attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 *
	 * @return self
	 */
	public function fill( array $attributes ) : self;

	/**
	 * Returns an attribute from the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return mixed
	 *
	 * @throws RuntimeException
	 */
	public function getAttribute( string $key );

	/**
	 * Returns the attributes that have been changed since last sync.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function getDirty() : array;

	/**
	 * Returns the model's original attribute values.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key Attribute name.
	 *
	 * @return mixed|array
	 */
	public function getOriginal( string $key = null );

	/**
	 * Determines if the model has the given property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Property name.
	 *
	 * @return bool
	 */
	public function hasProperty( string $key ) : bool;

	/**
	 * Determines if a given attribute is clean.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute Attribute name.
	 *
	 * @return bool
	 */
	public function isClean( string $attribute = null ) : bool;

	/**
	 * Determines if a given attribute is dirty.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute Attribute name.
	 *
	 * @return bool
	 */
	public function isDirty( string $attribute = null ) : bool;

	/**
	 * Validates an attribute to a PHP type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return bool
	 */
	public function isPropertyTypeValid( string $key, $value ) : bool;

	/**
	 * Returns the property keys.
	 *
	 * @since 1.0.0
	 *
	 * @return int[]|string[]
	 */
	public static function propertyKeys() : array;

	/**
	 * Sets an attribute on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return $this
	 */
	public function setAttribute( string $key, $value ) : self;

	/**
	 * Syncs the original attributes with the current.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function syncOriginal() : self;

	/**
	 * Dynamically retrieves attributes on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return mixed
	 */
	public function __get( string $key );

	/**
	 * Determines if an attribute exists on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Attribute name.
	 *
	 * @return bool
	 */
	public function __isset( string $key );

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
	public function __set( string $key, $value );
}
