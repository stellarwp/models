<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\ModelQueryBuilder;

/**
 * @since 2.0.0
 */
interface ModelBuildsFromData {
	/**
	 * @since 2.0.0
	 *
	 * @param array<string,mixed>|object $data
	 *
	 * @return Model
	 */
	public static function fromData( $data );
}
