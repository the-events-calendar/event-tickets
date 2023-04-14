<?php
/**
 * The repository for the Post And Post models.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Repositories;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts as Table;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Post;

/**
 * Class Post_And_Posts.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Posts_And_Posts extends Repository implements Insertable, Updatable, Deletable {
	/**
	 * Returns a query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder for the model.
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Post_And_Post::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * Deletes a post and post relationship from the repository.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to delete.
	 *
	 * @return bool Whether the model was deleted or not.
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * Inserts a post and post relationship into the database.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to insert.
	 *
	 * @return Post_And_Post The inserted model.
	 */
	public function insert( Model $model ): Post_And_Post {
		DB::insert( Table::table_name(), [
			'post_id_1' => $model->post_id_1,
			'post_id_2' => $model->post_id_2,
			'type'      => $model->type,
		], [
			'%d',
			'%d',
			'%s',
		] );

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * Updates a post and post relationship in the database.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to update.
	 *
	 * @return Post_And_Post The updated model.
	 */
	public function update( Model $model ): Post_And_Post {
		DB::update( Table::table_name(), [
			'post_id_1' => $model->post_id_1,
			'post_id_2' => $model->post_id_2,
			'type'      => $model->type,
		], [ 'id' => $model->id ], [
			'%d',
			'%d',
			'%s',
		], [ '%d' ] );

		return $model;
	}

	/**
	 * Returns a post and post relationship by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The ID of the post and post relationship.
	 *
	 * @return Post_And_Post|null The post and post relationship.
	 */
	public function get_by_id( int $id ): ?Post_And_Post {
		return $this->prepareQuery()->where( 'id', $id )->get();
	}
}