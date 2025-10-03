<?php
/**
 * Post model.
 *
 * @package StellarWP\Models
 */

declare(strict_types=1);

namespace StellarWP\Models;

use StellarWP\Models\Model;
use StellarWP\Models\Contracts\ModelPersistable;
use StellarWP\Models\ModelPropertyDefinition;
use StellarWP\Models\ModelQueryBuilder;
use RuntimeException;

/**
 * Post model.
 *
 * @package StellarWP\Models
 */
class PostModel extends Model implements ModelPersistable {
	/**
	 * A more robust, alternative way to define properties for the model than static::$properties.
	 *
	 * @return array<string,ModelPropertyDefinition>
	 */
	protected static function properties(): array {
		return [
			'ID' => (new ModelPropertyDefinition())
				->type('int')
				->readonly()
				->required(),
			'post_author' => (new ModelPropertyDefinition())
				->type('string')
				->default('0'),
			'post_date' => (new ModelPropertyDefinition())
				->type('string')
				->default('0000-00-00 00:00:00'),
			'post_date_gmt' => (new ModelPropertyDefinition())
				->type('string')
				->default('0000-00-00 00:00:00'),
			'post_content' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'post_title' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'post_excerpt' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'post_status' => (new ModelPropertyDefinition())
				->type('string')
				->default('publish'),
			'comment_status' => (new ModelPropertyDefinition())
				->type('string')
				->default('open'),
			'ping_status' => (new ModelPropertyDefinition())
				->type('string')
				->default('open'),
			'post_password' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'post_name' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'to_ping' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'pinged' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'post_modified' => (new ModelPropertyDefinition())
				->type('string')
				->default('0000-00-00 00:00:00'),
			'post_modified_gmt' => (new ModelPropertyDefinition())
				->type('string')
				->default('0000-00-00 00:00:00'),
			'post_content_filtered' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'post_parent' => (new ModelPropertyDefinition())
				->type('int')
				->default(0),
			'guid' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'menu_order' => (new ModelPropertyDefinition())
				->type('int')
				->default(0),
			'post_type' => (new ModelPropertyDefinition())
				->type('string')
				->default('post'),
			'post_mime_type' => (new ModelPropertyDefinition())
				->type('string')
				->default(''),
			'comment_count' => (new ModelPropertyDefinition())
				->type('int')
				->default(0),
		];
	}

	/**
	 * Finds a post by ID.
	 *
	 * @since 2.0.0
	 *
	 * @param int $id The ID of the post.
	 *
	 * @return ?self
	 */
	public static function find( $id ): ?self {
		return static::fromData( get_post( $id ) );
	}

	/**
	 * Creates a post and saves it to the database.
	 *
	 * @since 2.0.0
	 *
	 * @param array $attributes The attributes of the post.
	 *
	 * @return self
	 */
	public static function create( array $attributes ): self {
		$model = static::fromData( $attributes );

		return $model->save();
	}

	/**
	 * Saves the post to the database.
	 *
	 * @since 2.0.0
	 *
	 * @return self
	 */
	public function save(): self {
		$id = wp_insert_post( $this->toArray(), true );

		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() );
		}

		$this->setAttribute( 'ID', $id );

		return $this;
	}

	/**
	 * Deletes the post from the database.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function delete(): bool {
		return (bool) wp_delete_post( $this->getAttribute( 'ID' ) );
	}

	/**
	 * Queries the posts.
	 *
	 * @since 2.0.0
	 *
	 * @return ModelQueryBuilder<self>
	 */
	public static function query(): ModelQueryBuilder {
		return new ModelQueryBuilder( static::class );
	}
}
