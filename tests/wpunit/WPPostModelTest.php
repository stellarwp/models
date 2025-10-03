<?php

namespace StellarWP\Models\Tests\Unit;

use RuntimeException;
use StellarWP\Models\WPPostModel;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * @coversDefaultClass \StellarWP\Models\WPPostModel
 */
class WPPostModelTest extends WPTestCase {
	/**
	 * @since 2.0.0
	 *
	 * @covers ::find
	 */
	public function testFindReturnsPostModel() {
		$postId = self::factory()->post->create([
			'post_title' => 'Test Post',
			'post_content' => 'Test content',
			'post_status' => 'publish',
			'post_type' => 'post',
		]);

		$model = WPPostModel::find($postId);

		$this->assertInstanceOf(WPPostModel::class, $model);
		$this->assertSame($postId, $model->getAttribute('ID'));
		$this->assertSame('Test Post', $model->getAttribute('post_title'));
		$this->assertSame('Test content', $model->getAttribute('post_content'));
		$this->assertSame('publish', $model->getAttribute('post_status'));
		$this->assertSame('post', $model->getAttribute('post_type'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::find
	 */
	public function testFindReturnsNullForNonExistentPost() {
		$model = WPPostModel::find(999999);

		$this->assertNull($model);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::create
	 * @covers ::save
	 */
	public function testCreateAndSavePost() {
		$model = WPPostModel::create([
			'post_title' => 'Created Post',
			'post_content' => 'Created content',
			'post_status' => 'draft',
			'post_type' => 'page',
		]);

		$this->assertInstanceOf(WPPostModel::class, $model);
		$this->assertGreaterThan(0, $model->getAttribute('ID'));
		$this->assertSame('Created Post', $model->getAttribute('post_title'));
		$this->assertSame('Created content', $model->getAttribute('post_content'));
		$this->assertSame('draft', $model->getAttribute('post_status'));
		$this->assertSame('page', $model->getAttribute('post_type'));

		$post = get_post($model->getAttribute('ID'));
		$this->assertNotNull($post);
		$this->assertSame('Created Post', $post->post_title);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::save
	 */
	public function testSaveUpdatesExistingPost() {
		$postId = self::factory()->post->create([
			'post_title' => 'Original Title',
			'post_content' => 'Original content',
			'post_status' => 'publish',
		]);

		$model = WPPostModel::find($postId);
		$model->setAttribute('post_title', 'Updated Title');
		$model->setAttribute('post_content', 'Updated content');
		$model->save();

		$post = get_post($postId);
		$this->assertSame('Updated Title', $post->post_title);
		$this->assertSame('Updated content', $post->post_content);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::delete
	 */
	public function testDeleteRemovesPost() {
		$postId = self::factory()->post->create([
			'post_title' => 'To Delete',
			'post_content' => 'Will be deleted',
			'post_status' => 'publish',
		]);

		$model = WPPostModel::find($postId);
		$result = $model->delete();

		$this->assertTrue($result);

		$post = get_post($postId);
		$this->assertEquals('trash', $post->post_status);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::query
	 */
	public function testQueryReturnsModelQueryBuilder() {
		$builder = WPPostModel::query();

		$this->assertInstanceOf(\StellarWP\Models\ModelQueryBuilder::class, $builder);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::properties
	 */
	public function testIDPropertyIsRequired() {
		$reflection = new \ReflectionClass(WPPostModel::class);
		$method = $reflection->getMethod('properties');
		$method->setAccessible(true);

		$properties = $method->invoke(null);
		$idProperty = $properties['ID'];

		$this->assertTrue($idProperty->isRequired());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::create
	 * @covers ::save
	 */
	public function testCreateSetsDefaultValues() {
		$model = WPPostModel::create([
			'post_title' => 'Minimal Post',
		]);

		$this->assertSame('publish', $model->getAttribute('post_status'));
		$this->assertSame('post', $model->getAttribute('post_type'));
		$this->assertSame('open', $model->getAttribute('comment_status'));
		$this->assertSame(0, $model->getAttribute('post_parent'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::find
	 */
	public function testFindHandlesDifferentPostTypes() {
		$pageId = self::factory()->post->create([
			'post_title' => 'Test Page',
			'post_content' => 'Page content',
			'post_status' => 'publish',
			'post_type' => 'page',
		]);

		$model = WPPostModel::find($pageId);

		$this->assertInstanceOf(WPPostModel::class, $model);
		$this->assertSame('page', $model->getAttribute('post_type'));
		$this->assertSame('Test Page', $model->getAttribute('post_title'));
		$this->assertSame('Page content', $model->getAttribute('post_content'));
		$this->assertSame('publish', $model->getAttribute('post_status'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::save
	 */
	public function testSavePreservesIDAfterUpdate() {
		$model = WPPostModel::create([
			'post_title' => 'Original',
		]);

		$originalId = $model->getAttribute('ID');

		$model->setAttribute('post_title', 'Updated');
		$model->save();

		$post = get_post($originalId);
		$this->assertNotNull($post);
		$this->assertSame($originalId, $post->ID);
		$this->assertSame($originalId, $model->getAttribute('ID'));
		$this->assertSame('Updated', $post->post_title);
		$this->assertSame('publish', $post->post_status);
		$this->assertSame('post', $post->post_type);
	}
}
