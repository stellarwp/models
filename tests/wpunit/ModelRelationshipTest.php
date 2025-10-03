<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\Contracts\LazyModel;
use StellarWP\Models\Model;
use StellarWP\Models\ModelRelationship;
use StellarWP\Models\ModelRelationshipDefinition;
use StellarWP\Models\ModelPropertyDefinition;
use StellarWP\Models\Tests\ModelsTestCase;
use StellarWP\Models\ValueObjects\Relationship;
use StellarWP\Models\WPPostModel;
use StellarWP\Models\LazyWPPostModel;

/**
 * @coversDefaultClass \StellarWP\Models\ModelRelationship
 */
class ModelRelationshipTest extends ModelsTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::getKey
	 * @covers ::getDefinition
	 * @covers ::isLoaded
	 */
	public function testConstructor() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$this->assertSame('posts', $relationship->getKey());
		$this->assertSame($definition, $relationship->getDefinition());
		$this->assertFalse($relationship->isLoaded());
		$this->assertTrue($relationship->getDefinition()->isLocked());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 * @covers ::getValue
	 * @covers ::isLoaded
	 */
	public function testSetValueAndGetValue() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);

		// Should not be loaded initially
		$this->assertFalse($relationship->isLoaded());

		// Set value
		$relationship->setValue($mockModel);

		$this->assertTrue($relationship->isLoaded());

		// getValue without loader should return cached value
		$loader = fn() => $this->fail('Loader should not be called when value is already loaded');
		$this->assertSame($mockModel, $relationship->getValue($loader));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueThrowsExceptionForInvalidSingleValue() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Single relationship value must be a Model instance or null.');

		$relationship->setValue('invalid');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueThrowsExceptionForInvalidMultipleValue() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Multiple relationship value must be an array or null.');

		$relationship->setValue('invalid');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 */
	public function testSetValueThrowsExceptionForInvalidArrayItem() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Multiple relationship value must be an array of Model instances.');

		$relationship->setValue(['not', 'models']);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 * @covers ::getValue
	 */
	public function testSetValueWithMultipleModels() {
		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		$mockModel1 = $this->createMock(Model::class);
		$mockModel2 = $this->createMock(Model::class);
		$models = [$mockModel1, $mockModel2];

		$relationship->setValue($models);

		$loader = fn() => $this->fail('Loader should not be called');
		$this->assertSame($models, $relationship->getValue($loader));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 */
	public function testGetValueLoadsWhenNotCached() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$definition->disableCaching();
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);
		$loaderCalled = false;
		$loader = function() use ($mockModel, &$loaderCalled) {
			$loaderCalled = true;
			return $mockModel;
		};

		$result = $relationship->getValue($loader);

		$this->assertTrue($loaderCalled);
		$this->assertSame($mockModel, $result);
		$this->assertFalse($relationship->isLoaded(), 'Should not cache when caching is disabled');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 */
	public function testGetValueLoadsAndCachesWhenNotLoaded() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);
		$loaderCallCount = 0;
		$loader = function() use ($mockModel, &$loaderCallCount) {
			$loaderCallCount++;
			return $mockModel;
		};

		// First call should load
		$result1 = $relationship->getValue($loader);
		$this->assertSame(1, $loaderCallCount);
		$this->assertSame($mockModel, $result1);
		$this->assertTrue($relationship->isLoaded());

		// Second call should use cache
		$result2 = $relationship->getValue($loader);
		$this->assertSame(1, $loaderCallCount, 'Loader should only be called once');
		$this->assertSame($mockModel, $result2);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::purge
	 * @covers ::isLoaded
	 * @covers ::getValue
	 */
	public function testPurge() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$mockModel = $this->createMock(Model::class);
		$relationship->setValue($mockModel);

		$this->assertTrue($relationship->isLoaded());

		$relationship->purge();

		$this->assertFalse($relationship->isLoaded());

		// After purge, getValue should load again
		$loaderCalled = false;
		$loader = function() use ($mockModel, &$loaderCalled) {
			$loaderCalled = true;
			return $mockModel;
		};

		$relationship->getValue($loader);
		$this->assertTrue($loaderCalled);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::setValue
	 * @covers ::getValue
	 */
	public function testSetValueWithNull() {
		$definition = new ModelRelationshipDefinition('post', Relationship::HAS_ONE);
		$relationship = new ModelRelationship('post', $definition);

		$relationship->setValue(null);

		$this->assertTrue($relationship->isLoaded());
		$loader = fn() => $this->fail('Loader should not be called');
		$this->assertNull($relationship->getValue($loader));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 * @covers ::resolveValue
	 */
	public function testGetValueResolvesLazyModelToActualModel() {
		$post = WPPostModel::create([
			'post_title' => 'Test Post',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);

		$GLOBALS['models_post_id'] = $post->getAttribute('ID');
		$model = new class extends Model {
			protected static function relationships(): array {
				return [
					'post' => new ModelRelationshipDefinition('post', Relationship::HAS_ONE),
				];
			}

			protected static function properties(): array {
				return [
					'id' => new ModelPropertyDefinition('id', 'int'),
				];
			}

			protected function fetchRelationship( string $key ) {
				return new LazyWPPostModel($GLOBALS['models_post_id']);
			}
		};

		$this->assertInstanceOf( WPPostModel::class, $model->post );
		$this->assertSame($post->getAttribute('ID'), $model->post->getAttribute('ID'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 * @covers ::resolveValue
	 */
	public function testGetValueResolvesArrayOfLazyModels() {
		$post_1 = WPPostModel::create([
			'post_title' => 'Test Post 1',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);

		$post_2 = WPPostModel::create([
			'post_title' => 'Test Post 2',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);

		$GLOBALS['models_post_id_1'] = $post_1->getAttribute('ID');
		$GLOBALS['models_post_id_2'] = $post_2->getAttribute('ID');

		$model = new class extends Model {
			protected static function relationships(): array {
				return [
					'posts' => new ModelRelationshipDefinition('posts', Relationship::HAS_MANY),
				];
			}

			protected static function properties(): array {
				return [
					'id' => new ModelPropertyDefinition('id', 'int'),
				];
			}

			protected function fetchRelationship( string $key ) {
				return [
					new LazyWPPostModel($GLOBALS['models_post_id_1']),
					new LazyWPPostModel($GLOBALS['models_post_id_2']),
				];
			}
		};

		$results = $model->posts;
		$this->assertInstanceOf( WPPostModel::class, $results[0] );
		$this->assertInstanceOf( WPPostModel::class, $results[1] );
		$this->assertSame($post_1->getAttribute('ID'), $results[0]->getAttribute('ID'));
		$this->assertSame($post_2->getAttribute('ID'), $results[1]->getAttribute('ID'));
	}

		/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 * @covers ::resolveValue
	 */
	public function testGetValueResolvesLazyModelToNull() {
		$model = new class extends Model {
			protected static function relationships(): array {
				return [
					'post' => new ModelRelationshipDefinition('post', Relationship::HAS_ONE),
				];
			}

			protected static function properties(): array {
				return [
					'id' => new ModelPropertyDefinition('id', 'int'),
				];
			}

			protected function fetchRelationship( string $key ) {
				return new LazyWPPostModel( null );
			}
		};

		$this->assertNull( $model->post );
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 * @covers ::resolveValue
	 */
	public function testGetValueResolvesArrayOfLazyModelsToNull() {
		$model = new class extends Model {
			protected static function relationships(): array {
				return [
					'posts' => new ModelRelationshipDefinition('posts', Relationship::HAS_MANY),
				];
			}

			protected static function properties(): array {
				return [
					'id' => new ModelPropertyDefinition('id', 'int'),
				];
			}

			protected function fetchRelationship( string $key ) {
				return [
					new LazyWPPostModel( null ),
					new LazyWPPostModel( null ),
				];
			}
		};

		$results = $model->posts;
		$this->assertEmpty( $results );
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getValue
	 * @covers ::resolveValue
	 */
	public function testGetRawValueReturnsLazyModelWithoutResolving() {
		$post = WPPostModel::create([
			'post_title' => 'Test Post',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);

		$GLOBALS['models_post_id'] = $post->getAttribute('ID');
		$model = new class extends Model {
			protected static function relationships(): array {
				return [
					'post' => new ModelRelationshipDefinition('post', Relationship::HAS_ONE),
				];
			}

			protected static function properties(): array {
				return [
					'id' => new ModelPropertyDefinition('id', 'int'),
				];
			}

			protected function fetchRelationship( string $key ) {
				return new LazyWPPostModel($GLOBALS['models_post_id']);
			}
		};

		$this->assertInstanceOf( WPPostModel::class, $model->post );
		$this->assertSame($post->getAttribute('ID'), $model->post->getAttribute('ID'));
	}
}
