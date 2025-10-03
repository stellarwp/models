<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\ModelQueryBuilder;

/**
 * @since 2.0.0 renamed from ModelCrud
 * @since 1.0.0
 */
interface ModelPersistable extends Model {
	/**
	 * @since 1.0.0
	 *
	 * @param int|string $id
	 *
	 * @return ?ModelPersistable
	 */
	public static function find( $id ): ?ModelPersistable;

	/**
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $attributes
	 *
	 * @return ModelPersistable
	 */
	public static function create( array $attributes ): ModelPersistable;

	/**
	 * @since 1.0.0
	 *
	 * @return ModelPersistable
	 */
	public function save(): ModelPersistable;

	/**
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete() : bool;

	/**
	 * @since 1.0.0
	 *
	 * @return ModelQueryBuilder<static>
	 */
	public static function query(): ModelQueryBuilder;
}
