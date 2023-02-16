<?php

namespace StellarWP\Models\Repositories\Contracts;

use StellarWP\Models\Contracts\Model;

interface Updatable {
	/**
	 * Inserts a model record.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $model
	 *
	 * @return Model
	 */
	public function update( Model $model ) : Model;
}
