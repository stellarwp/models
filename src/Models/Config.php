<?php

namespace StellarWP\Models;

use InvalidArgumentException;
use RuntimeException;

class Config {
	/**
	 * @var ?string
	 */
	protected static $hookPrefix;

	/**
	 * @var string
	 */
	protected static $invalidArgumentException = InvalidArgumentException::class;

	/**
	 * Gets the hook prefix.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 *
	 * @throws RuntimeException If the hook prefix is not set.
	 */
	public static function getHookPrefix(): string {
		if ( ! static::$hookPrefix ) {
			throw new RuntimeException(
				sprintf(
					'You must provide a hook prefix via %1$s before using the stellarwp/models library.',
					__CLASS__ . '::setHookPrefix()'
				)
			);
		}

		return static::$hookPrefix;
	}

	/**
	 * Gets the InvalidArgumentException class.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function getInvalidArgumentException(): string {
		return static::$invalidArgumentException;
	}

	/**
	 * Resets the class back to default.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset() {
		static::$hookPrefix = null;
		static::$invalidArgumentException = InvalidArgumentException::class;
	}

	/**
	 * Sets the hook prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_prefix
	 *
	 * @return void
	 *
	 * @throws RuntimeException         If the hook prefix has already been set.
	 * @throws InvalidArgumentException If the sanitized hook prefix is not the same as the original hook prefix.
	 */
	public static function setHookPrefix( string $hook_prefix ) {
		if ( ! empty( static::$hookPrefix ) ) {
			throw new RuntimeException(
				sprintf(
					'The %1$s has already been called and set to %2$s.',
					__CLASS__ . '::setHookPrefix()',
					static::$hookPrefix
				)
			);
		}

		$sanitized_prefix = preg_replace( '/[^a-z0-9_-]/', '', $hook_prefix );

		if ( $sanitized_prefix !== $hook_prefix ) {
			throw new InvalidArgumentException( 'Hook prefix must only contain lowercase letters, numbers, "_", or "-".' );
		}

		static::$hookPrefix = $hook_prefix;
	}

	/**
	 * Allow for overriding the InvalidArgumentException class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If the provided InvalidArgumentException class is not or does not extend InvalidArgumentException.
	 */
	public static function setInvalidArgumentException( string $class ) {
		if ( ! is_a( $class, InvalidArgumentException::class, true ) ) {
			throw new InvalidArgumentException( 'The provided InvalidArgumentException class must be or must extend ' . InvalidArgumentException::class . '.' );
		}

		static::$invalidArgumentException = $class;
	}

	/**
	 * Convenience method for throwing the InvalidArgumentException.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException Always, thats what it does.
	 */
	public static function throwInvalidArgumentException( string $message ): void {
		throw new static::$invalidArgumentException( $message );
	}
}
