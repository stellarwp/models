<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\ModelQueryBuilder;

/**
 * @since 2.0.0
 */
interface ModelBuildsFromQueryData {
	/**
	 * @since 2.0.0
	 *
	 * @param array|object $queryData
	 *
	 * @return Model
	 */
	public static function fromQueryData( $queryData );
}
