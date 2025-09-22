<?php
/**
 * The schema model contract.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models\Contracts;
 */

declare( strict_types=1 );

namespace StellarWP\Models\Contracts;

use StellarWP\Schema\Tables\Contracts\Table_Interface;

interface SchemaModel extends Model {
	/**
	 * Gets the primary value of the model.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed
	 */
	public function getPrimaryValue();

	/**
	 * Gets the primary column of the model.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function getPrimaryColumn(): string;

	/**
	 * Gets the table interface of the model.
	 *
	 * @since 2.0.0
	 *
	 * @return Table_Interface
	 */
	public function getTableInterface(): Table_Interface;

	/**
	 * Magic method to get the relationships of the model.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name The name of the method.
	 * @param array  $arguments The arguments of the method.
	 *
	 * @return array|void The relationships of the model.
	 */
	public function __call( string $name, array $arguments );

	/**
	 * Gets the relationships of the model.
	 *
	 * @since 2.0.0
	 *
	 * @return array The relationships of the model.
	 */
	public function getRelationships(): array;

	/**
	 * Deletes the relationship data for a given key.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key The key of the relationship.
	 */
	public function deleteRelationshipData( string $key ): void;

	/**
	 * Adds an ID to a relationship.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key The key of the relationship.
	 * @param int    $id  The ID to add.
	 */
	public function addToRelationship( string $key, int $id ): void;

	/**
	 * Removes an ID from a relationship.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key The key of the relationship.
	 * @param int    $id  The ID to remove.
	 */
	public function removeFromRelationship( string $key, int $id ): void;

	/**
	 * Saves the model.
	 *
	 * @since 2.0.0
	 *
	 * @return int The ID of the saved model.
	 */
	public function save(): int;

	/**
	 * Deletes the model.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool;
}
