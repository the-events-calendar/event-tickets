<?php
/**
 * The CRUD model for the Post And Post.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Contracts\ModelFromQueryBuilderObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Post_And_Post_DTO;
use TEC\Tickets\Flexible_Tickets\Repositories\Posts_And_Posts;

/**
 * Class Post_And_Post.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int    $id         The post and post ID.
 * @property int    $post_id_1  The first post ID.
 * @property int    $post_id_2  The second post ID.
 * @property string $type       The type of the relationship.
 */
class Post_And_Post extends Model implements ModelCrud, ModelFromQueryBuilderObject {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'        => 'int',
		'post_id_1' => 'int',
		'post_id_2' => 'int',
		'type'      => 'string',
	];

	/**
	 * Finds a model by its ID.
	 *
	 * @since TBD
	 *
	 * @param $id
	 *
	 * @return Post_And_Post|null The model, or null if not found.
	 */
	public static function find( $id ): ?Post_And_Post {
		return tribe( Posts_And_Posts::class )->get_by_id( $id );
	}

	/**
	 * Creates and saves to database a new model instance.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attributes The attributes to set on the model.
	 *
	 * @return Post_And_Post The created model.
	 */
	public static function create( array $attributes ): Post_And_Post {
		$model = new static( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since TBD
	 *
	 * @return $this The saved model, its ID set.
	 */
	public function save(): Post_And_Post {
		$this->id = tribe( Posts_And_Posts::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the model was deleted or not.
	 */
	public function delete(): bool {
		return tribe( Posts_And_Posts::class )->delete( $this );
	}


	/**
	 * Returns a query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder for the model.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Posts_And_Posts::class )->prepareQuery();
	}

	/**
	 * Creates a model from a DTO object.
	 *
	 * @since TBD
	 *
	 * @param object $object The query data object.
	 *
	 * @return Post_And_Post The model built from the data.
	 */
	public static function fromQueryBuilderObject( $object ): Post_And_Post {
		return Post_And_Post_DTO::fromObject( $object )->toModel();
	}
}