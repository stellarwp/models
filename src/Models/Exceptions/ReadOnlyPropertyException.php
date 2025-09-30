<?php

declare(strict_types=1);

namespace StellarWP\Models\Exceptions;

use RuntimeException;

/**
 * Exception thrown when attempting to modify a readonly property.
 *
 * @since 2.0.0
 */
class ReadOnlyPropertyException extends RuntimeException {
}
