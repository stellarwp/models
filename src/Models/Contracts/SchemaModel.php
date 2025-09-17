<?php

namespace StellarWP\Models\Contracts;

interface SchemaModel extends Model {
	public function get_primary_value();

	public function set_primary_value( $value ): void;

	public function getTableInterface();

	public function __call( string $name, array $arguments );

	public function getRelationships(): array;

	public function deleteRelationshipData( string $key ): void;

	public function addToRelationship( string $key, int $id ): void;

	public function removeFromRelationship( string $key, int $id ): void;

	public function save(): int;

	public function delete(): bool;
}
