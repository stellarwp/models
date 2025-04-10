<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\ModelQueryBuilder;

/**
 * @since 1.0.0
 */
interface ModelReadOnly extends ModelBuildsFromQueryData {
	/**
	 * @since 1.0.0
	 *
	 * @param int $id
	 *
	 * @return Model
	 */
	public static function find( $id );

	/**
	 * @since 1.0.0
	 *
	 * @return ModelQueryBuilder
	 */
	public static function query();
}
