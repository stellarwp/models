<?php

namespace StellarWP\Models\Repositories;

use StellarWP\Models\Contracts\ModelBuildsFromData;
use StellarWP\Models\ModelQueryBuilder;

/**
 * @template M of ModelBuildsFromData
 */
abstract class Repository {
	/**
	 * Prepare a query builder for the repository.
	 *
	 * @since 1.0.0
	 *
	 * @return ModelQueryBuilder<M>
	 */
	abstract function prepareQuery() : ModelQueryBuilder;
}
