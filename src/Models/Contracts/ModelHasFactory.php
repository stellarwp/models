<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\ModelFactory;
use StellarWP\Models\Contracts\ModelCrud;

/**
 * @since 1.0.0
 */
interface ModelHasFactory extends ModelCrud {
	/**
	 * @since 1.0.0
	 *
	 * @return ModelFactory<static>
	 */
	public static function factory();
}
