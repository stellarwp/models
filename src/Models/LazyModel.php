<?php
/**
 * Lazy model.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models
 */

declare(strict_types=1);

namespace StellarWP\Models;

use StellarWP\Models\Contracts\ModelPersistable;
use StellarWP\Models\Contracts\LazyModel as LazyModelInterface;

/**
 * Lazy model.
 *
 * @since 2.0.0
 *
 * @package StellarWP\Models
 */
abstract class LazyModel implements LazyModelInterface {
	/**
	 * The ID of the model.
	 *
	 * @since 2.0.0
	 *
	 * @var int|string
	 */
	private $id;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $id The ID of the model.
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * Gets the ID of the model.
	 *
	 * @since 2.0.0
	 *
	 * @return int|string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the ID of the model as a string.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->get_id();
	}

	/**
	 * Resolves the model.
	 *
	 * @since 2.0.0
	 *
	 * @return ?ModelPersistable
	 */
	public function resolve(): ?ModelPersistable {
		return $this->getModelClass()::find( $this->id );
	}
}
