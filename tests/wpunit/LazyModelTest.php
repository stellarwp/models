<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\Contracts\LazyModel as LazyModelInterface;
use StellarWP\Models\Contracts\ModelPersistable;
use StellarWP\Models\LazyModel;
use StellarWP\Models\ModelQueryBuilder;
use StellarWP\Models\Tests\ModelsTestCase;

/**
 * @coversDefaultClass \StellarWP\Models\LazyModel
 */
class LazyModelTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::get_id
	 */
	public function testConstructorStoresId() {
		$lazyModel = new class(123) extends LazyModel {
			public function getModelClass(): string {
				return 'test';
			}
		};

		$this->assertSame(123, $lazyModel->get_id());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::get_id
	 */
	public function testConstructorStoresStringId() {
		$lazyModel = new class('abc-123') extends LazyModel {
			public function getModelClass(): string {
				return 'test';
			}
		};

		$this->assertSame('abc-123', $lazyModel->get_id());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::resolve
	 */
	public function testResolveCallsFindOnModelClass() {
		$modelClass = new class extends \StellarWP\Models\Model implements ModelPersistable {
			public static $findResult;

			public static function find($id): ?ModelPersistable {
				return self::$findResult;
			}

			public static function create(array $attributes): ModelPersistable {
				return new self();
			}

			public function save(): ModelPersistable {
				return $this;
			}

			public function delete(): bool {
				return true;
			}

			public static function query(): ModelQueryBuilder {
				return new ModelQueryBuilder(static::class);
			}
		};

		$mockModel = new $modelClass();

		$modelClass::$findResult = $mockModel;
		$GLOBALS['modelClassName'] = get_class($modelClass);

		$lazyModel = new class(456) extends LazyModel {
			public function getModelClass(): string {
				return $GLOBALS['modelClassName'];
			}
		};

		$result = $lazyModel->resolve();

		$this->assertSame($mockModel, $result);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::resolve
	 */
	public function testResolveReturnsNullWhenModelNotFound() {
		$modelClass = new class extends \StellarWP\Models\Model implements ModelPersistable {
			public static function find($id): ?ModelPersistable {
				return null;
			}

			public static function query(): ModelQueryBuilder {
				return new ModelQueryBuilder(static::class);
			}

			public static function create(array $attributes): ModelPersistable {
				return new self();
			}

			public function save(): ModelPersistable {
				return $this;
			}

			public function delete(): bool {
				return true;
			}
		};

		$GLOBALS['modelClassName'] = get_class($modelClass);

		$lazyModel = new class(999) extends LazyModel {
			public function getModelClass(): string {
				return $GLOBALS['modelClassName'];
			}
		};

		$result = $lazyModel->resolve();

		$this->assertNull($result);
	}

	/**
	 * @since 2.0.0
	 */
	public function testImplementsLazyModelInterface() {
		$lazyModel = new class(1) extends LazyModel {
			public function getModelClass(): string {
				return 'test';
			}
		};

		$this->assertInstanceOf(LazyModelInterface::class, $lazyModel);
	}
}
