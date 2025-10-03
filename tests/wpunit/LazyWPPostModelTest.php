<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\LazyWPPostModel;
use StellarWP\Models\WPPostModel;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * @coversDefaultClass \StellarWP\Models\LazyWPPostModel
 */
class LazyWPPostModelTest extends WPTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 * @covers ::get_id
	 */
	public function testConstructorStoresPostId() {
		$lazyPost = new LazyWPPostModel(42);

		$this->assertSame(42, $lazyPost->get_id());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getModelClass
	 */
	public function testGetModelClassReturnsWPPostModel() {
		$lazyPost = new LazyWPPostModel(1);

		$this->assertSame(WPPostModel::class, $lazyPost->getModelClass());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::resolve
	 */
	public function testResolveReturnsWPPostModel() {
		$postId = self::factory()->post->create([
			'post_title' => 'Test Post',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);


		$lazyPost = new LazyWPPostModel($postId);
		$resolved = $lazyPost->resolve();

		$this->assertInstanceOf(WPPostModel::class, $resolved);
		$this->assertSame($postId, $resolved->getAttribute('ID'));
		$this->assertSame('Test Post', $resolved->getAttribute('post_title'));
		$this->assertSame('Test content', $resolved->getAttribute('post_content'));
		$this->assertSame('publish', $resolved->getAttribute('post_status'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::resolve
	 */
	public function testResolveReturnsNullForNonExistentPost() {
		$lazyPost = new LazyWPPostModel(999999);
		$resolved = $lazyPost->resolve();

		$this->assertNull($resolved);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::resolve
	 */
	public function testMultipleResolutionsReturnSameData() {
		$postId = self::factory()->post->create([
			'post_title' => 'Test Post',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);

		$lazyPost = new LazyWPPostModel($postId);

		$resolved1 = $lazyPost->resolve();
		$resolved2 = $lazyPost->resolve();

		$this->assertInstanceOf(WPPostModel::class, $resolved1);
		$this->assertInstanceOf(WPPostModel::class, $resolved2);
		$this->assertEquals($resolved1->toArray(), $resolved2->toArray());
		$this->assertSame('Test Post', $resolved1->getAttribute('post_title'));
		$this->assertSame('Test content', $resolved1->getAttribute('post_content'));
		$this->assertSame('publish', $resolved1->getAttribute('post_status'));
	}
}
