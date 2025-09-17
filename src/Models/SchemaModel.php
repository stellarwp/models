<?php
/**
 * The schema model.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models;
 */

namespace StellarWP\Models;

use InvalidArgumentException;
use BadMethodCallException;
use StellarWP\Models\Contracts\SchemaModel as SchemaModelInterface;
use StellarWP\Models\ValueObjects\Relationship;
use StellarWP\DB\DB;

/**
 * The schema model.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models;
 */
abstract class SchemaModel extends Model implements SchemaModelInterface {
	/**
	 * The relationship data of the model.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private array $relationship_data = [];

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 */
	public function __construct( array $attributes = [] ) {
		if ( ! empty( static::getPropertyDefinitions() ) ) {
			throw new InvalidArgumentException( 'Schema models do not accept property definitions. Define a schema interface to link with instead.' );
		}

		$this->propertyCollection = ModelPropertyCollection::fromPropertyDefinitions( $this->getPropertyDefinitionsFromSchema(), $attributes );
	}

	abstract public function getTableInterface();

	/**
	 * Magic method to get the relationships of the model.
	 *
	 * @since TBD
	 *
	 * @param string $name The name of the method.
	 * @param array  $arguments The arguments of the method.
	 *
	 * @return array|void The relationships of the model.
	 *
	 * @throws BadMethodCallException If the method does not exist on the model.
	 * @throws BadMethodCallException If the relationship does not exist on the model.
	 * @throws BadMethodCallException If the relationship is not a many to many relationship.
	 */
	public function __call( string $name, array $arguments ) {
		if ( ! str_starts_with( $name, 'get_' ) && ! str_starts_with( $name, 'set_' ) ) {
			throw new BadMethodCallException( "Method {$name} does not exist on the model." );
		}

		$property      = str_replace( [ 'get_', 'set_' ], '', $name );
		$relationships = $this->getRelationships();

		if ( $this->hasProperty( $property ) && ! isset( $relationships[ $property ] ) ) {
			throw new BadMethodCallException( "`{$property}` is not a property or a relationship on the model." );
		}

		$is_getter = str_starts_with( $name, 'get_' );

		if ( $is_getter ) {
			if ( isset( $relationships[ $property ] ) ) {
				return $this->getRelationship( $property );
			}

			return $this->getAttribute( $property );
		}

		$args = $arguments['0'] ?? null;
		$args = (array) $args;
		if ( isset( $relationships[ $property ] ) ) {
			$args ? $this->setRelationship( $property, $args ) : $this->deleteRelationshipData( $property );
			return;
		}

		$this->setAttribute( $property, $args );
	}

	/**
	 * Gets the relationships of the model.
	 *
	 * @since 2.0.0
	 *
	 * @return array The relationships of the model.
	 */
	public function getRelationships(): array {
		return static::$relationships;
	}

	/**
	 * Deletes the relationship data for a given key.
	 *
	 * @since TBD
	 *
	 * @param string $key The key of the relationship.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public function deleteRelationshipData( string $key ): void {
		if ( ! isset( $this->getRelationships()[ $key ] ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		if ( $this->getRelationships()[ $key ]['type'] === Relationship::MANY_TO_MANY ) {
			$this->getRelationships()[ $key ]['through']::delete( $this->get_primary_value(), $this->getRelationships()[ $key ]['columns']['this'] );
		}
	}

	/**
	 * Adds an ID to a relationship.
	 *
	 * @since TBD
	 *
	 * @param string $key The key of the relationship.
	 * @param int    $id  The ID to add.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public function addToRelationship( string $key, int $id ): void {
		if ( ! isset( $this->getRelationships()[ $key ] ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		if ( ! isset( $this->relationship_data[ $key ] ) ) {
			$this->relationship_data[ $key ] = [];
		}

		if ( ! isset( $this->relationship_data[ $key ]['insert'] ) ) {
			$this->relationship_data[ $key ]['insert'] = [];
		}

		$this->relationship_data[ $key ]['insert'][] = $id;

		if ( ! empty( $this->relationship_data[ $key ]['delete'] ) ) {
			$this->relationship_data[ $key ]['delete'] = array_diff( $this->relationship_data[ $key ]['delete'], [ $id ] );
		}
	}

	/**
	 * Removes an ID from a relationship.
	 *
	 * @since TBD
	 *
	 * @param string $key The key of the relationship.
	 * @param int    $id  The ID to remove.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public function removeFromRelationship( string $key, int $id ): void {
		if ( ! isset( $this->getRelationships()[ $key ] ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		if ( ! isset( $this->relationship_data[ $key ] ) ) {
			$this->relationship_data[ $key ] = [];
		}

		if ( ! isset( $this->relationship_data[ $key ]['delete'] ) ) {
			$this->relationship_data[ $key ]['delete'] = [];
		}

		$this->relationship_data[ $key ]['delete'][] = $id;

		if ( ! empty( $this->relationship_data[ $key ]['insert'] ) ) {
			$this->relationship_data[ $key ]['insert'] = array_diff( $this->relationship_data[ $key ]['insert'], [ $id ] );
		}
	}

	/**
	 * Get the property definitions from the schema.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string,ModelPropertyDefinition>
	 */
	private function getPropertyDefinitionsFromSchema(): array {
		return [];
	}

	/**
	 * Sets a relationship.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Relationship name.
	 * @param mixed $value Relationship value.
	 */
	protected function setRelationship( string $key, $value ): void {
		$this->cachedRelations[ $key ] = $value;
	}

	/**
	 * Returns a relationship.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Relationship name.
	 *
	 * @return Model|Model[]
	 */
	protected function getRelationship( string $key ) {
		$relationships = $this->getRelationships();
		if ( ! isset( $relationships[ $key ] ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		if ( $this->hasCachedRelationship( $key ) ) {
			return $this->cachedRelations[ $key ];
		}

		$relationship = $relationships[ $key ];

		$relationship_type = $relationship['type'];

		switch ( $relationship_type ) {
			case Relationship::BELONGS_TO:
			case Relationship::HAS_ONE:
				if ( 'post' === $relationship['entity'] ) {
					return $this->cachedRelations[ $key ] = get_post( $this->getAttribute( $key ) );
				}

				throw new InvalidArgumentException( "Relationship {$key} is not a post relationship." );
			case Relationship::HAS_MANY:
			case Relationship::BELONGS_TO_MANY:
			case Relationship::MANY_TO_MANY:
				return $this->cachedRelations[ $key ] = iterator_to_array(
					$relationship['through']::fetch_all_where(
						DB::prepare(
							'WHERE %i = %d',
							$relationship['columns']['this'],
							$this->get_primary_value()
						),
						100,
						ARRAY_A,
						$relationship['columns']['other'] . ' ASC'
					)
				);
		}

		return null;
	}

	/**
	 * Saves the relationship data.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function saveRelationshipData(): void {
		foreach ( $this->getRelationships() as $key => $relationship ) {
			if ( Relationship::MANY_TO_MANY !== $relationship['type'] ) {
				continue;
			}

			if ( ! empty( $this->relationship_data[ $key ]['insert'] ) ) {
				$insert_data = [];
				foreach ( $this->relationship_data[ $key ]['insert'] as $insert_id ) {
					$insert_data[] = [
						$this->getRelationships()[ $key ]['columns']['this']  => $this->get_primary_value(),
						$this->getRelationships()[ $key ]['columns']['other'] => $insert_id,
					];
				}

				// First delete them to avoid duplicates.
				$relationship['through']::delete_many(
					$this->relationship_data[ $key ]['insert'],
					$this->getRelationships()[ $key ]['columns']['other'],
					DB::prepare( ' AND %i = %d', $this->getRelationships()[ $key ]['columns']['this'], $this->get_primary_value() )
				);

				$relationship['through']::insert_many( $insert_data );
			}

			if ( ! empty( $this->relationship_data[ $key ]['delete'] ) ) {
				$relationship['through']::delete_many(
					$this->relationship_data[ $key ]['delete'],
					$this->get_relationships()[ $key ]['columns']['other'],
					DB::prepare( ' AND %i = %d', $this->get_relationships()[ $key ]['columns']['this'], $this->get_primary_value() )
				);
			}
		}
	}

	/**
	 * Deletes all the relationship data.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function deleteAllRelationshipData(): void {
		if ( empty( $this->getRelationships() ) ) {
			return;
		}

		foreach ( array_keys( $this->getRelationships() ) as $key ) {
			$this->deleteRelationshipData( $key );
		}
	}

	/**
	 * Sets a relationship for the model.
	 *
	 * @since TBD
	 *
	 * @param string  $key                 The key of the relationship.
	 * @param string  $type                The type of the relationship.
	 * @param ?string $through             A table interface that provides the relationship.
	 * @param string  $relationship_entity The entity of the relationship.
	 */
	protected function defineRelationship( string $key, string $type, ?string $through = null, string $relationship_entity = 'post' ): void {
		static::$relationships[ $key ] = [
			'type'    => $type,
			'through' => $through,
			'entity'  => $relationship_entity,
		];
	}

	/**
	 * Sets the relationship columns for the model.
	 *
	 * @since TBD
	 *
	 * @param string $key                 The key of the relationship.
	 * @param string $this_entity_column  The column of the relationship.
	 * @param string $other_entity_column The other entity column.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	protected function defineRelationshipColumns( string $key, string $this_entity_column, string $other_entity_column ): void {
		if ( ! isset( $this->getRelationships()[ $key ] ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		static::$relationships[ $key ]['columns'] = [
			'this'  => $this_entity_column,
			'other' => $other_entity_column,
		];
	}
}
