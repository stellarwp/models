<?php
/**
 * Lazy model interface.
 *
 * @package StellarWP\Models
 */

declare(strict_types=1);

namespace StellarWP\Models\Contracts;

/**
 * Lazy model interface.
 *
 * @package StellarWP\Models
 */
interface LazyModel {
	/**
	 * Resolves the model.
	 *
	 * @return ?ModelPersistable
	 */
	public function resolve(): ?ModelPersistable;

	/**
	 * Gets the ID of the model.
	 *
	 * @return int|string
	 */
	public function get_id();

	/**
	 * Gets the class of the model.
	 *
	 * @return class-string<ModelPersistable>
	 */
	public function getModelClass(): string;
}
