<?php

namespace StellarWP\Models\Tests\Unit;

use StellarWP\Models\ModelRelationship;
use StellarWP\Models\ModelRelationshipCollection;
use StellarWP\Models\ModelRelationshipDefinition;
use StellarWP\Models\Tests\ModelsTestCase;
use StellarWP\Models\ValueObjects\Relationship;

/**
 * @coversDefaultClass \StellarWP\Models\ModelRelationshipCollection
 */
class ModelRelationshipCollectionTest extends ModelsTestCase {
	/**
	 * Create a collection with test relationships
	 *
	 * @since 2.0.0
	 */
	private function createTestCollection(): ModelRelationshipCollection {
		$definition1 = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$definition2 = new ModelRelationshipDefinition('author', Relationship::BELONGS_TO);
		$definition3 = new ModelRelationshipDefinition('tags', Relationship::MANY_TO_MANY);

		$relationship1 = new ModelRelationship('posts', $definition1);
		$relationship2 = new ModelRelationship('author', $definition2);
		$relationship3 = new ModelRelationship('tags', $definition3);

		return new ModelRelationshipCollection([
			'posts' => $relationship1,
			'author' => $relationship2,
			'tags' => $relationship3,
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 */
	public function testConstructorShouldThrowExceptionForNonStringKeys() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Relationship key must be a string.');

		$definition = new ModelRelationshipDefinition('posts', Relationship::HAS_MANY);
		$relationship = new ModelRelationship('posts', $definition);

		new ModelRelationshipCollection([
			0 => $relationship,
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::__construct
	 */
	public function testConstructorShouldThrowExceptionForNonModelRelationshipValues() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Relationship must be an instance of ModelRelationship.');

		new ModelRelationshipCollection([
			'posts' => 'invalid',
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::count
	 */
	public function testCount() {
		$collection = $this->createTestCollection();

		$this->assertSame(3, $collection->count());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getIterator
	 */
	public function testGetIterator() {
		$collection = $this->createTestCollection();

		$this->assertInstanceOf(\Traversable::class, $collection->getIterator());

		$keys = [];
		foreach ($collection as $key => $relationship) {
			$keys[] = $key;
			$this->assertInstanceOf(ModelRelationship::class, $relationship);
		}

		$this->assertSame(['posts', 'author', 'tags'], $keys);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::has
	 */
	public function testHas() {
		$collection = $this->createTestCollection();

		$this->assertTrue($collection->has('posts'));
		$this->assertTrue($collection->has('author'));
		$this->assertFalse($collection->has('nonexistent'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::get
	 */
	public function testGet() {
		$collection = $this->createTestCollection();

		$relationship = $collection->get('posts');
		$this->assertInstanceOf(ModelRelationship::class, $relationship);
		$this->assertSame('posts', $relationship->getKey());

		$this->assertNull($collection->get('nonexistent'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getOrFail
	 */
	public function testGetOrFail() {
		$collection = $this->createTestCollection();

		$relationship = $collection->getOrFail('posts');
		$this->assertInstanceOf(ModelRelationship::class, $relationship);
		$this->assertSame('posts', $relationship->getKey());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getOrFail
	 */
	public function testGetOrFailThrowsException() {
		$collection = $this->createTestCollection();

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Relationship nonexistent does not exist.');

		$collection->getOrFail('nonexistent');
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::getAll
	 */
	public function testGetAll() {
		$collection = $this->createTestCollection();
		$all = $collection->getAll();

		$this->assertIsArray($all);
		$this->assertCount(3, $all);
		$this->assertArrayHasKey('posts', $all);
		$this->assertArrayHasKey('author', $all);
		$this->assertArrayHasKey('tags', $all);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromRelationshipDefinitions
	 */
	public function testFromRelationshipDefinitions() {
		$definitions = [
			'posts' => new ModelRelationshipDefinition('posts', Relationship::HAS_MANY),
			'author' => new ModelRelationshipDefinition('author', Relationship::BELONGS_TO),
		];

		$collection = ModelRelationshipCollection::fromRelationshipDefinitions($definitions);

		$this->assertCount(2, $collection);
		$this->assertTrue($collection->has('posts'));
		$this->assertTrue($collection->has('author'));

		// Verify relationships are locked
		$this->assertTrue($collection->get('posts')->getDefinition()->isLocked());
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromRelationshipDefinitions
	 */
	public function testFromRelationshipDefinitionsThrowsExceptionForNonStringKey() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Relationship key must be a string.');

		ModelRelationshipCollection::fromRelationshipDefinitions([
			new ModelRelationshipDefinition('posts', Relationship::HAS_MANY),
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::fromRelationshipDefinitions
	 */
	public function testFromRelationshipDefinitionsThrowsExceptionForInvalidDefinition() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Relationship definition must be an instance of ModelRelationshipDefinition.');

		ModelRelationshipCollection::fromRelationshipDefinitions([
			'posts' => 'invalid',
		]);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::purgeAll
	 * @covers ::isLoaded
	 */
	public function testPurgeAll() {
		$collection = $this->createTestCollection();

		// Set some values
		$collection->get('posts')->setValue([]);
		$collection->get('author')->setValue(null);

		$this->assertTrue($collection->isLoaded('posts'));
		$this->assertTrue($collection->isLoaded('author'));

		$collection->purgeAll();

		$this->assertFalse($collection->isLoaded('posts'));
		$this->assertFalse($collection->isLoaded('author'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::purge
	 * @covers ::isLoaded
	 */
	public function testPurge() {
		$collection = $this->createTestCollection();

		$collection->get('posts')->setValue([]);
		$this->assertTrue($collection->isLoaded('posts'));

		$collection->purge('posts');
		$this->assertFalse($collection->isLoaded('posts'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::isLoaded
	 */
	public function testIsLoaded() {
		$collection = $this->createTestCollection();

		$this->assertFalse($collection->isLoaded('posts'));

		$collection->get('posts')->setValue([]);

		$this->assertTrue($collection->isLoaded('posts'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::filter
	 */
	public function testFilter() {
		$collection = $this->createTestCollection();

		$filtered = $collection->filter(function($relationship) {
			return $relationship->getDefinition()->isMultiple();
		});

		$this->assertCount(2, $filtered);
		$this->assertTrue($filtered->has('posts'));
		$this->assertTrue($filtered->has('tags'));
		$this->assertFalse($filtered->has('author'));
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::map
	 */
	public function testMap() {
		$collection = $this->createTestCollection();

		$keys = $collection->map(fn($relationship) => $relationship->getKey());

		$this->assertSame([
			'posts' => 'posts',
			'author' => 'author',
			'tags' => 'tags',
		], $keys);
	}

	/**
	 * @since 2.0.0
	 *
	 * @covers ::tap
	 */
	public function testTap() {
		$collection = $this->createTestCollection();

		$called = 0;
		$result = $collection->tap(function($relationship) use (&$called) {
			$called++;
		});

		$this->assertSame(3, $called);
		$this->assertSame($collection, $result);
	}
}
