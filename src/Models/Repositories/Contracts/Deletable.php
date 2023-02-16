<?php

namespace StellarWP\Models\Repositories\Contracts;

use StellarWP\Models\Model;

interface Deletable {
	/**
	 * Inserts a model record.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $model
	 *
	 * @return bool
	 */
	public function delete( Model $model ) : bool;
}
