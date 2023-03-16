<?php

namespace StellarWP\Models\Contracts;

use RuntimeException;

interface Model {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes
	 */
	public function __construct( array $attributes = [] );

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 *
	 * @return Model
	 */
	public function fill( array $attributes ) : Model;

	/**
	 * Get an attribute from the model.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 *
	 * @throws RuntimeException
	 */
	public function getAttribute( string $key );

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function getDirty() : array;

	/**
	 * Get the model's original attribute values.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key
	 *
	 * @return mixed|array
	 */
	public function getOriginal( string $key = null );

	/**
	 * Determines if the model has the given property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasProperty( string $key ) : bool;

	/**
	 * Determine if a given attribute is clean.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute
	 *
	 * @return bool
	 */
	public function isClean( string $attribute = null ) : bool;

	/**
	 * Determine if a given attribute is dirty.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $attribute
	 *
	 * @return bool
	 */
	public function isDirty( string $attribute = null ) : bool;

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
	public function isPropertyTypeValid( string $key, $value ) : bool;

	/**
	 * Get the property keys.
	 *
	 * @since 1.0.0
	 *
	 * @return int[]|string[]
	 */
	public static function propertyKeys() : array;

	/**
	 * Set an attribute on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return Model
	 */
	public function setAttribute( string $key, $value ) : Model;

	/**
	 * Sync the original attributes with the current.
	 *
	 * @since 1.0.0
	 *
	 * @return Model
	 */
	public function syncOriginal() : Model;

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function __get( string $key );

	/**
	 * Determine if an attribute exists on the model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function __isset( string $key );

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
	public function __set( string $key, $value );
}
