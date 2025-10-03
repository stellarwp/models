<?php
/**
 * Lazy post model.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models
 */

declare(strict_types=1);

namespace StellarWP\Models;

use StellarWP\Models\Contracts\ModelPersistable;

/**
 * Lazy post model.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models
 */
class LazyPostModel extends LazyModel {
	/**
	 * Gets the class of the model.
	 *
	 * @since 2.0.0
	 *
	 * @return class-string<ModelPersistable>
	 */
	public function getModelClass(): string {
		return PostModel::class;
	}
}
