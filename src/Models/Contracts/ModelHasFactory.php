<?php

namespace StellarWP\Models\Contracts;

use StellarWP\Models\Factories\ModelFactory;

/**
 * @since 1.0.0
 */
interface ModelHasFactory {
	/**
	 * @since 1.0.0
	 *
	 * @return ModelFactory
	 */
	public static function factory();
}
