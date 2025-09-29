<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\ModelFactory;

/**
 * @since 1.0.0
 */
interface ModelHasFactory {
	/**
	 * @since 1.0.0
	 *
	 * @return ModelFactory<static>
	 */
	public static function factory();
}
