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
use StellarWP\Schema\Tables\Contracts\Table_Interface;
use StellarWP\Schema\Tables\Contracts\Table_Schema_Interface;
use RuntimeException;

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
	private array $relationshipData = [];

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 */
	public function __construct( array $attributes = [] ) {
		$this->propertyCollection = ModelPropertyCollection::fromPropertyDefinitions( $this->getPropertyDefinitionsFromSchema(), $attributes );
	}

	abstract public function getTableInterface(): Table_Interface;

	/**
	 * Gets the primary value of the model.
	 *
	 * @since TBD
	 *
	 * @return mixed
	 */
	public function getPrimaryValue() {
		return $this->getAttribute( $this->getPrimaryColumn() );
	}

	/**
	 * Gets the primary column of the model.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function getPrimaryColumn(): string {
		return $this->getTableInterface()::uid_column();
	}

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

		if ( ! $this->hasProperty( $property ) && ! isset( $relationships[ $property ] ) ) {
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
			$this->getRelationships()[ $key ]['through']::delete( $this->getPrimaryValue(), $this->getRelationships()[ $key ]['columns']['this'] );
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

		if ( ! isset( $this->relationshipData[ $key ] ) ) {
			$this->relationshipData[ $key ] = [];
		}

		if ( ! isset( $this->relationshipData[ $key ]['insert'] ) ) {
			$this->relationshipData[ $key ]['insert'] = [];
		}

		$this->relationshipData[ $key ]['insert'][] = $id;

		if ( ! empty( $this->relationshipData[ $key ]['delete'] ) ) {
			$this->relationshipData[ $key ]['delete'] = array_diff( $this->relationshipData[ $key ]['delete'], [ $id ] );
		}

		$this->relationshipData[ $key ]['current'] = array_unique( array_merge( $this->relationshipData[ $key ]['current'] ?? [], [ $id ] ) );
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

		if ( ! isset( $this->relationshipData[ $key ] ) ) {
			$this->relationshipData[ $key ] = [];
		}

		if ( ! isset( $this->relationshipData[ $key ]['delete'] ) ) {
			$this->relationshipData[ $key ]['delete'] = [];
		}

		$this->relationshipData[ $key ]['delete'][] = $id;

		if ( ! empty( $this->relationshipData[ $key ]['insert'] ) ) {
			$this->relationshipData[ $key ]['insert'] = array_diff( $this->relationshipData[ $key ]['insert'], [ $id ] );
		}

		$this->relationshipData[ $key ]['current'] = array_diff( $this->relationshipData[ $key ]['current'] ?? [], [ $id ] );
	}

	/**
	 * Get the property definitions from the schema.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string,ModelPropertyDefinition>
	 */
	private function getPropertyDefinitionsFromSchema(): array {
		$table_interface = $this->getTableInterface();
		/** @var Table_Schema_Interface $table_schema */
		$table_schema = $table_interface::get_current_schema();

		$property_definitions = [];

		foreach ( $table_schema->get_columns() as $column ) {
			$definition = ( new ModelPropertyDefinition() )->type( $column->get_php_type() );
			if ( $column->get_nullable() ) {
				$definition->nullable();
			}

			if ( $column->get_default() ) {
				$definition->default( $column->get_default() );
			}

			if ( is_callable( [ $this->getTableInterface(), 'cast_value_based_on_type' ] ) ) {
				$definition->castWith( fn( $value ) => $this->getTableInterface()::cast_value_based_on_type( $column->get_php_type(), $value ) );
			}

			$property_definitions[ $column->get_name() ] = $definition;
		}

		static::$properties = $property_definitions;

		return $property_definitions;
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
		$old_value = $this->relationshipData[ $key ]['current'] ?? null;
		$this->relationshipData[ $key ]['current'] = $value;

		if ( $old_value ) {
			if ( is_array( $old_value ) ) {
				foreach ( $old_value as $i ) {
					$this->removeFromRelationship( $key, $i );
				}
			} else {
				$this->removeFromRelationship( $key, $old_value );
			}
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $i ) {
				if ( ! is_int( $i ) ) {
					throw new InvalidArgumentException( "Relationship {$key} must be an integer." );
				}

				$this->addToRelationship( $key, $i );
			}
		} else {
			if ( ! is_int( $value ) ) {
				throw new InvalidArgumentException( "Relationship {$key} must be an integer." );
			}

			$this->addToRelationship( $key, $value );
		}
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

		if ( isset( $this->relationshipData[ $key ]['current'] ) ) {
			return $this->relationshipData[ $key ]['current'];
		}

		$relationship = $relationships[ $key ];

		$relationship_type = $relationship['type'];

		switch ( $relationship_type ) {
			case Relationship::BELONGS_TO:
			case Relationship::HAS_ONE:
				if ( 'post' === $relationship['entity'] ) {
					return $this->relationshipData[ $key ]['current'] = get_post( $this->getAttribute( $key ) );
				}

				throw new InvalidArgumentException( "Relationship {$key} is not a post relationship." );
			case Relationship::HAS_MANY:
			case Relationship::BELONGS_TO_MANY:
			case Relationship::MANY_TO_MANY:
				return $this->relationshipData[ $key ]['current'] = wp_list_pluck(
					$relationship['through']::get_all_by(
						$relationship['columns']['this'],
						$this->getPrimaryValue()
					),
					$relationship['columns']['other']
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

			if ( ! empty( $this->relationshipData[ $key ]['insert'] ) ) {
				$insert_data = [];
				foreach ( $this->relationshipData[ $key ]['insert'] as $insert_id ) {
					$insert_data[] = [
						$this->getRelationships()[ $key ]['columns']['this']  => $this->getPrimaryValue(),
						$this->getRelationships()[ $key ]['columns']['other'] => $insert_id,
					];
				}

				// First delete them to avoid duplicates.
				$relationship['through']::delete_many(
					$this->relationshipData[ $key ]['insert'],
					$this->getRelationships()[ $key ]['columns']['other'],
					DB::prepare( ' AND %i = %d', $this->getRelationships()[ $key ]['columns']['this'], $this->getPrimaryValue() )
				);

				$relationship['through']::insert_many( $insert_data );
			}

			if ( ! empty( $this->relationshipData[ $key ]['delete'] ) ) {
				$relationship['through']::delete_many(
					$this->relationshipData[ $key ]['delete'],
					$this->get_relationships()[ $key ]['columns']['other'],
					DB::prepare( ' AND %i = %d', $this->get_relationships()[ $key ]['columns']['this'], $this->getPrimaryValue() )
				);
			}
		}
	}

	/**
	 * Saves the model.
	 *
	 * @since TBD
	 *
	 * @return int The id of the saved model.
	 *
	 * @throws RuntimeException If the model fails to save.
	 */
	public function save(): int {
		if ( ! $this->isDirty() ) {
			$this->saveRelationshipData();
			return $this->getPrimaryValue();
		}

		$table_interface = $this->getTableInterface();
		$result          = $table_interface::upsert( $this->toArray() );

		if ( ! $result ) {
			throw new RuntimeException( __( 'Failed to save the model.', 'tribe-common' ) );
		}

		$id = $this->getPrimaryValue();

		if ( ! $id ) {
			$id = DB::last_insert_id();
			$this->setAttribute( $this->getPrimaryColumn(), $id );
		}

		$this->commitChanges();

		$this->saveRelationshipData();

		return $id;
	}

	/**
	 * Deletes the model.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the model was deleted.
	 *
	 * @throws RuntimeException If the model ID required to delete the model is not set.
	 */
	public function delete(): bool {
		$uid = $this->getPrimaryValue();

		if ( ! $uid ) {
			throw new RuntimeException( __( 'Model ID is required to delete the model.', 'tribe-common' ) );
		}

		$this->deleteAllRelationshipData();

		return $this->getTableInterface()::delete( $uid );
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

		$model = new static();

		foreach (static::propertyKeys() as $key) {
			$property_definition = static::getPropertyDefinition( $key );
			if ( $key !== $model->getPrimaryColumn() && ! array_key_exists( $key, $data ) && ! $property_definition->hasDefault() ) {
				Config::throwInvalidArgumentException( "Property '$key' does not exist." );
			}

			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			// Remember not to use $type, as it may be an array that includes the default value. Safer to use getPropertyType().
			$model->setAttribute( $key, static::castValueForProperty( static::getPropertyDefinition( $key ), $data[ $key ], $key ) );
		}

		foreach ( $model->getRelationships() as $key => $relationship ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$model->setRelationship( $key, $data[ $key ] );
		}

		if ( $model->getPrimaryValue() ) {
			$model->commitChanges();
		}

		return $model;
	}
}
