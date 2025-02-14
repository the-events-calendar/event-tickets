<?php
/**
 * Repository for managing relationships between Order Modifiers and wp_posts.
 *
 * This class provides methods to insert, update, delete, and query the relationships between
 * order modifiers (such as fees or coupons) and WordPress posts (like venues or organizers).
 * It interacts with the custom `Order_Modifier_Relationships` table and uses WordPress database functions
 * for CRUD operations.
 *
 * @since 5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Repositories
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Repositories;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships as Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships as Relationship_Model;

/**
 * Class Order_Modifier_Relationships
 *
 * Repository for managing the relationship between Order Modifiers and wp_posts.
 *
 * @since 5.18.0
 */
class Order_Modifier_Relationship extends Repository implements Insertable, Updatable, Deletable {

	/**
	 * Inserts a new relationship record.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model instance to insert.
	 *
	 * @return Relationship_Model The inserted model instance.
	 */
	public function insert( Model $model ): Relationship_Model {
		DB::insert(
			Table::table_name(),
			[
				'modifier_id' => $model->modifier_id,
				'post_id'     => $model->post_id,
				'post_type'   => $model->post_type,
			],
			[
				'%d',
				'%d',
				'%s',
			]
		);

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * Updates an existing relationship record.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model instance to update.
	 *
	 * @return Order_Modifier_Relationship The updated model instance.
	 */
	public function update( Model $model ): Relationship_Model {
		DB::update(
			Table::table_name(),
			[
				'modifier_id' => $model->modifier_id,
				'post_id'     => $model->post_id,
				'post_type'   => $model->post_type,
			],
			[ 'id' => $model->id ],
			[
				'%d',
				'%d',
				'%s',
			],
			[ '%d' ]
		);

		return $model;
	}

	/**
	 * Deletes a relationship record.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model instance to delete.
	 *
	 * @return bool Whether the record was successfully deleted.
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete(
			Table::table_name(),
			[
				'modifier_id' => $model->modifier_id,
				'post_id'     => $model->post_id,
			],
			[
				'%d',
				'%d',
			]
		);
	}

	/**
	 * Clears all relationships associated with a given modifier id.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the Order Modifier.
	 *
	 * @return bool True if the relationships were successfully deleted, false otherwise.
	 */
	public function clear_relationships_by_modifier_id( int $modifier_id ): bool {
		return (bool) DB::delete(
			Table::table_name(),
			[
				'modifier_id' => $modifier_id,
			],
			[ '%d' ]
		);
	}

	/**
	 * Clears all relationships associated with a given post.
	 *
	 * This method deletes all records in the relationships table for the provided post
	 * based on the `post_id`.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model representing the post for which relationships should be cleared.
	 *
	 * @return bool True if the relationships were successfully deleted, false otherwise.
	 */
	public function clear_relationships_by_post_id( Model $model ): bool {
		return (bool) DB::delete(
			Table::table_name(),
			[
				'post_id' => $model->post_id,
			],
			[ '%d' ]
		);
	}

	/**
	 * Finds a relationship by `modifier_id`, and returns the full modifier and post data.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the Order Modifier.
	 *
	 * @return array|null The data from the Order Modifier and wp_posts tables.
	 */
	public function find_by_modifier_id( int $modifier_id ): array {
		$query = $this->build_base_query()
			->where( 'r.modifier_id', $modifier_id )
			->getAll();

		return $query ?? [];
	}

	/**
	 * Finds all posts related to a specific `modifier_id` and `post_type`.
	 *
	 * @since 5.18.0
	 *
	 * @param int    $modifier_id The ID of the Order Modifier.
	 * @param string $post_type   The post type.
	 *
	 * @return ?Relationship_Model The data from the wp_posts and modifier relationship.
	 */
	public function find_by_modifier_and_post_type( int $modifier_id, string $post_type ): ?Relationship_Model {
		return $this->build_base_query()
			->where( 'r.modifier_id', $modifier_id )
			->where( 'p.post_type', $post_type )
			->get();
	}

	/**
	 * Finds a post and its related modifiers.
	 *
	 * @since 5.18.0
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array|null The data from the Order Modifier and wp_posts tables.
	 */
	public function find_by_post_id( int $post_id ): ?array {
		return $this->build_base_query()
			->where( 'p.ID', $post_id )
			->getAll();
	}

	/**
	 * Builds the base query with joins for Order Modifiers and wp_posts.
	 *
	 * This method centralizes the JOIN logic so that it can be reused in other query-building methods.
	 *
	 * @since 5.18.0
	 *
	 * @return ModelQueryBuilder The query builder with the necessary joins.
	 */
	protected function build_base_query(): ModelQueryBuilder {
		return $this->prepareQuery()
			->select(
				'r.id',
				[ 'm.id', 'modifier_id' ],
				[ 'p.ID', 'post_id' ],
				'p.post_type',
				'p.post_title'
			)
			->innerJoin( Order_Modifiers::base_table_name(), 'r.modifier_id', 'm.id', 'm' )
			->innerJoin( 'posts', 'r.post_id', 'p.ID', 'p' );
	}

	/**
	 * Prepares a query for the repository.
	 *
	 * @since 5.18.0
	 *
	 * @return ModelQueryBuilder
	 */
	public function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Relationship_Model::class );

		return $builder->from( Table::table_name( false ), 'r' );
	}
}
