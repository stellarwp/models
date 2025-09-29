<?php

namespace StellarWP\Models;

use InvalidArgumentException;
use StellarWP\DB\DB;
use StellarWP\DB\QueryBuilder\QueryBuilder;
use StellarWP\DB\QueryBuilder\Clauses\RawSQL;
use StellarWP\Models\Contracts\Model;
use StellarWP\Models\Contracts\ModelBuildsFromData;
/**
 * @since 1.2.2  improve model generic
 * @since 1.0.0
 *
 * @template M of ModelBuildsFromData
 */
class ModelQueryBuilder extends QueryBuilder {
	public const MODEL = 'model';

	/**
	 * @var class-string<M>
	 */
	protected $model;

	/**
	 * @param class-string<M> $modelClass
	 */
	public function __construct( string $modelClass ) {
		if ( ! is_subclass_of( $modelClass, ModelBuildsFromData::class ) ) {
			throw new InvalidArgumentException( "$modelClass must implement " . ModelBuildsFromData::class );
		}

		$this->model = $modelClass;
	}

	/**
	 * Returns the number of rows returned by a query
	 *
	 * @since 1.0.0
	 *
	 * @param null|string $column
	 */
	public function count( ?string $column = null ) : int {
		$column = ( ! $column || $column === '*' ) ? '1' : trim( $column );

		if ( '1' === $column ) {
			$this->selects = [];
		}
		$this->selects[] = new RawSQL( 'SELECT COUNT(%1s) AS count', $column );

		return +parent::get()->count;
	}

	/**
	 * Get row
	 *
	 * @since 1.0.0
	 *
	 * @param string $output
	 *
	 * @return M|null
	 */
	public function get( $output = self::MODEL ): ?Model {
		if ( $output !== self::MODEL ) {
			return parent::get( $output );
		}

		$row = DB::get_row( $this->getSQL() );

		if ( ! $row ) {
			return null;
		}

		return $this->model::fromData( $row );
	}

	/**
	 * Get results
	 *
	 * @since 1.0.0
	 *
	 * @return M[]|null
	 */
	public function getAll( $output = self::MODEL ) : ?array {
		if ( $output !== self::MODEL ) {
			return parent::getAll( $output );
		}

		$results = DB::get_results( $this->getSQL() );

		if ( ! $results ) {
			return null;
		}

		return array_map( [ $this->model, 'fromData' ], $results );
	}
}
