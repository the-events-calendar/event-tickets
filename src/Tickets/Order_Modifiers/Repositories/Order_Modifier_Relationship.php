<?php
/**
 * Repository for managing relationships between Order Modifiers and wp_posts.
 *
 * This class provides methods to insert, update, delete, and query the relationships between
 * order modifiers (such as fees or coupons) and WordPress posts (like venues or organizers).
 * It interacts with the custom `Order_Modifier_Relationships` table and uses WordPress database functions
 * for CRUD operations.
 *
 * @package TEC\Tickets\Order_Modifiers\Repositories
 * @since TBD
 */

namespace TEC\Tickets\Order_Modifiers\Repositories;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships as Table;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier_Relationships as Order_Modifier_Model;

/**
 * Class Order_Modifier_Relationships
 *
 * Repository for managing the relationship between Order Modifiers and wp_posts.
 *
 * @since TBD
 */
class Order_Modifier_Relationship extends Repository implements Insertable, Updatable, Deletable {

	/**
	 * Inserts a new relationship record.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model instance to insert.
	 *
	 * @return Order_Modifier_Relationship The inserted model instance.
	 */
	public function insert( Model $model ): Order_Modifier_Model {
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

		$model->object_id = DB::last_insert_id();

		return $model;
	}

	/**
	 * Updates an existing relationship record.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model instance to update.
	 *
	 * @return Order_Modifier_Relationship The updated model instance.
	 */
	public function update( Model $model ): Order_Modifier_Model {
		DB::update(
			Table::table_name(),
			[
				'modifier_id' => $model->modifier_id,
				'post_id'     => $model->post_id,
				'post_type'   => $model->post_type,
			],
			[ 'object_id' => $model->object_id ],
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
	 * @since TBD
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
	 * Clears all relationships associated with a given modifier.
	 *
	 * This method deletes all records in the relationships table for the provided modifier
	 * based on the `modifier_id`.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model representing the modifier for which relationships should be cleared.
	 *
	 * @return bool True if the relationships were successfully deleted, false otherwise.
	 */
	public function clear_relationships_by_modifier_id( Model $model ): bool {
		return (bool) DB::delete(
			Table::table_name(),
			[
				'modifier_id' => $model->modifier_id,
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param int    $modifier_id The ID of the Order Modifier.
	 * @param string $post_type The post type.
	 *
	 * @return array|null The data from the wp_posts and modifier relationship.
	 */
	public function find_by_modifier_and_post_type( int $modifier_id, string $post_type ): ?Order_Modifier_Model {
		return $this->build_base_query()
					->where( 'r.modifier_id', $modifier_id )
					->where( 'p.post_type', $post_type )
					->get();
	}

	/**
	 * Finds a post and its related modifiers.
	 *
	 * @since TBD
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
	 * Finds posts and their related modifiers based on an array of post IDs.
	 *
	 * This method retrieves data from the Order Modifier and wp_posts tables for
	 * the given array of post IDs. It returns an array of results or null if no
	 * matching posts are found.
	 *
	 * @since TBD
	 *
	 * @param array $post_ids The array of post IDs to find.
	 *
	 * @return array|null The data from the Order Modifier and wp_posts tables, or null if no matches are found.
	 */
	public function find_by_post_ids( array $post_ids ): ?array {
		return $this->build_base_query()
					->select('m.display_name')
					->whereIn( 'p.ID', $post_ids )
					->getAll();
	}

	/**
	 * Builds the base query with joins for Order Modifiers and wp_posts.
	 *
	 * This method centralizes the JOIN logic so that it can be reused in other query-building methods.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder with the necessary joins.
	 */
	protected function build_base_query(): ModelQueryBuilder {
		global $wpdb;

		// Get dynamic table names from $wpdb.
		$posts_table           = 'posts';
		$order_modifiers_table = Order_Modifiers::base_table_name();

		return $this->prepareQuery()
					->select( 'r.object_id,m.id as modifier_id', 'p.ID as post_id', 'p.post_type', 'p.post_title' )
					->innerJoin( "$order_modifiers_table as m", 'r.modifier_id', 'm.id' )
					->innerJoin( "$posts_table as p", 'r.post_id', 'p.ID' );
	}

	/**
	 * Prepares a query for the repository.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder
	 */
	public function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Order_Modifier_Model::class );
		return $builder->from( Table::table_name( false ) . ' as r' );
	}
}
