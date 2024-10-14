<?php
/**
 * Order Modifiers repository.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Repositories;
 */

namespace TEC\Tickets\Order_Modifiers\Repositories;

use RuntimeException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Exceptions\Not_Found_Exception;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers as Table;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships as Relationship_Table;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
use TEC\Tickets\Order_Modifiers\Traits\Valid_Types;

/**
 * Class Order_Modifiers.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Repositories;
 */
class Order_Modifiers extends Repository implements Insertable, Updatable, Deletable {

	use Valid_Types;

	/**
	 * The modifier type for queries.
	 *
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * Order_Modifiers constructor.
	 *
	 * @since TBD
	 *
	 * @param string $modifier_type The modifier type for queries.
	 *
	 * @throws RuntimeException If the modifier type is invalid.
	 */
	public function __construct( $modifier_type ) {
		$this->validate_type( $modifier_type );
		$this->modifier_type = $modifier_type;
	}

	/**
	 * Inserts a model record.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to insert.
	 *
	 * @return bool
	 * @throws RuntimeException If the model type is invalid.
	 */
	public function delete( Model $model ): bool {
		$this->validate_model_type( $model );

		return (bool) DB::delete(
			Table::table_name(),
			[ 'id' => $model->id ],
			[ '%d' ]
		);
	}

	/**
	 * Inserts a model record.
	 *
	 * @since TBD
	 *
	 * @param Model $model
	 *
	 * @return Model
	 * @throws RuntimeException If the model type is invalid.
	 */
	public function insert( Model $model ): Model {
		$this->validate_model_type( $model );

		DB::insert(
			Table::table_name(),
			[
				'modifier_type'    => $model->modifier_type,
				'sub_type'         => $model->sub_type,
				'fee_amount_cents' => $model->fee_amount_cents,
				'slug'             => $model->slug,
				'display_name'     => $model->display_name,
				'status'           => $model->status,
				'created_at'       => current_time( 'mysql' ),
				'start_time'       => $model->start_time,
				'end_time'         => $model->end_time,
			],
			[
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * Inserts a model record.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to insert.
	 *
	 * @return Model
	 * @throws RuntimeException If the model type is invalid.
	 */
	public function update( Model $model ): Model {
		$this->validate_model_type( $model );

		DB::update(
			Table::table_name(),
			[
				'modifier_type'    => $model->modifier_type,
				'sub_type'         => $model->sub_type,
				'fee_amount_cents' => $model->fee_amount_cents,
				'slug'             => $model->slug,
				'display_name'     => $model->display_name,
				'status'           => $model->status,
				'start_time'       => $model->start_time,
				'end_time'         => $model->end_time,
			],
			[ 'id' => $model->id ],
			[
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			],
			[ '%d' ]
		);

		return $model;
	}

	/**
	 * Finds an Order Modifier by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The ID of the Order Modifier to find.
	 *
	 * @return Order_Modifier The Order Modifier model instance, or null if not found.
	 * @throws Not_Found_Exception If the Order Modifier is not found.
	 * @throws RuntimeException If we didn't get an Order_Modifier object.
	 */
	public function find_by_id( int $id ): Order_Modifier {
		$result = $this->prepareQuery()
			->where( 'id', $id )
			->where( 'modifier_type', $this->modifier_type )
			->get();

		return $this->normalize_return_result( $result );
	}

	/**
	 * Search for Order Modifiers based on the given criteria.
	 *
	 * @param array $args          {
	 *                             Optional. Arguments to filter the query.
	 *
	 *     @type string $search_term The term to search for (e.g., in display_name or slug).
	 *     @type string $orderby     Column to order by. Default 'display_name'.
	 *     @type string $order       Sorting order. Either 'asc' or 'desc'. Default 'asc'.
	 * }
	 *
	 * @return Order_Modifier[] An array of Order_Modifiers or an empty array if none found.
	 */
	public function search_modifiers( array $args = [] ): array {
		// Define default arguments.
		$defaults = [
			'search_term' => '',
			'orderby'     => 'display_name',
			'order'       => 'asc',
		];

		// Merge passed arguments with defaults.
		$args = array_merge( $defaults, $args );

		// Start building the query.
		$query = $this->prepareQuery();

		// Add search functionality (search in display_name or slug).
		if ( ! empty( $args['search_term'] ) ) {
			$query = $query->whereLike( 'display_name', $args['search_term'] );
		}

		// Add ordering.
		$valid_orderby = [
			'display_name'     => 1,
			'slug'             => 1,
			'fee_amount_cents' => 1,
			'used'             => 1,
			'remaining'        => 1,
			'status'           => 1,
		];

		if ( ! empty( $args['orderby'] ) && array_key_exists( $args['orderby'], $valid_orderby ) ) {
			$query = $query->orderBy( $args['orderby'], $args['order'] );
		}

		// Set the modifier type.
		$query = $query->where( 'modifier_type', $this->modifier_type );

		return $query->getAll() ?? [];
	}

	/**
	 * Finds all active Order Modifiers.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier[] Array of active Order Modifier model instances, or null if not found.
	 */
	public function find_active(): array {
		$result = $this->prepareQuery()
			->where( 'status', 'active' )
			->getAll();

		return $result ?? [];
	}

	/**
	 * Finds an Order Modifier by its slug and modifier_type.
	 *
	 * @since TBD
	 *
	 * @param string $slug The slug of the Order Modifier to find.
	 *
	 * @return Order_Modifier The Order Modifier model instance, or null if not found.
	 * @throws Not_Found_Exception If the Order Modifier is not found.
	 */
	public function find_by_slug( string $slug ): Order_Modifier {
		$result = $this->prepareQuery()
			->where( 'slug', $slug )
			->where( 'status', 'active' )
			->get();

		return $this->normalize_return_result( $result );
	}

	/**
	 * Finds Order Modifiers by modifier_type.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier[] Array of Order Modifier model instances.
	 */
	public function get_all(): array {
		$results = $this->prepareQuery()
			->where( 'modifier_type', $this->modifier_type )
			->getAll();

		return $results ?? [];
	}

	/**
	 * Finds order modifiers by post IDs, modifier type, and status based on the relationship table.
	 *
	 * This method retrieves order modifiers that are related to the provided post IDs,
	 * match the specified modifier type, and have the specified status. It joins the Order Modifier
	 * and Relationship tables to fetch the related modifiers.
	 *
	 * @since TBD
	 *
	 * @param array  $post_ids      The array of post IDs to look up in the relationship table.
	 * @param string $modifier_type The type of modifier to filter by.
	 * @param string $status        The status of the modifiers to filter by. Defaults to 'active'.
	 *
	 * @return array The data from the related order modifiers.
	 */
	public function find_relationship_by_post_ids( array $post_ids, string $modifier_type, string $status = 'active' ): array {
		$order_modifiers_table = Relationship_Table::base_table_name();
		$builder               = new ModelQueryBuilder( Order_Modifier::class );

		return $builder->from( Table::table_name( false ) . ' as m' )
			->innerJoin( "{$order_modifiers_table} as r", 'm.id', 'r.modifier_id' )
			->whereIn( 'r.post_id', $post_ids )
			->where( 'modifier_type', $modifier_type )
			->where( 'status', $status )
			->getAll();
	}

	/**
	 * Finds Order Modifiers by modifier_type and specific meta key-value pair with optional defaults.
	 *
	 * This method uses $wpdb to filter modifiers based on their `modifier_type`, and a dynamic meta key-value pair.
	 * If no meta key-value pair exists, it defaults to configurable values. If no defaults are provided, `IFNULL()` is
	 * not used.
	 *
	 * Caches the results for identical query arguments to improve performance.
	 *
	 * @since TBD
	 *
	 * @param string      $meta_key           The meta key to filter by (e.g., 'fee_applied_to').
	 * @param array       $meta_values        The meta values to filter by (e.g., ['per', 'all']).
	 * @param string|null $default_meta_key   The default meta key if none exists (null to skip `IFNULL()`).
	 * @param string|null $default_meta_value The default meta value if none exists (null to skip `IFNULL()`).
	 *
	 * @return array|null Array of Order Modifier model instances, or null if not found.
	 */
	public function find_by_modifier_type_and_meta(
		string $meta_key,
		array $meta_values,
		?string $default_meta_key = null,
		?string $default_meta_value = null
	): ?array {
		global $wpdb;

		// Generate a cache key based on the arguments.
		$cache_key = 'modifier_type_meta_' . md5(
				wp_json_encode(
					[
						$this->modifier_type,
						$meta_key,
						$meta_values,
						$default_meta_key,
						$default_meta_value,
					]
				)
			);

		// Try to get the results from the cache.
		$cached_results = wp_cache_get( $cache_key, 'order_modifiers' );
		if ( false !== $cached_results ) {
			return $cached_results;
		}

		// Get the table names dynamically.
		$order_modifiers_table      = Table::table_name();
		$order_modifiers_meta_table = Order_Modifiers_Meta::table_name();

		// Initialize the SQL query with the base WHERE clause for modifier_type.
		$sql = "
        SELECT o.*,m.meta_value
        FROM {$order_modifiers_table} o
        LEFT JOIN {$order_modifiers_meta_table} m
        ON o.id = m.order_modifier_id
        WHERE o.modifier_type = %s
    ";

		$params = [ $this->modifier_type ];

		// Handle the meta_key condition: Use IFNULL if a default_meta_key is provided, otherwise check for meta_key directly.
		if ( $default_meta_key ) {
			$sql      .= ' AND (IFNULL(m.meta_key, %s) = %s)';
			$params[] = $default_meta_key;
			$params[] = $meta_key;
		} else {
			$sql      .= ' AND m.meta_key = %s';
			$params[] = $meta_key;
		}

		// Handle the meta_value condition: Use IFNULL if a default_meta_value is provided, otherwise check directly.
		if ( $default_meta_value ) {
			$sql      .= ' AND (IFNULL(m.meta_value, %s) IN (' . implode( ',', array_fill( 0, count( $meta_values ), '%s' ) ) . '))';
			$params[] = $default_meta_value;
		} else {
			$sql .= ' AND m.meta_value IN (' . implode( ',', array_fill( 0, count( $meta_values ), '%s' ) ) . ')';
		}

		$params = array_merge( $params, $meta_values );

		// Prepare and execute the query.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, ...$params );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $query );

		// Cache the results for future use.
		wp_cache_set( $cache_key, $results, 'order_modifiers', HOUR_IN_SECONDS );

		return $results;
	}

	/**
	 * Prepare a query builder for the repository.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder
	 */
	public function prepareQuery(): ModelQueryBuilder {
		// Determine the model class based on the modifier type.
		$this->validate_type( $this->modifier_type );
		$class = $this->get_valid_types()[ $this->modifier_type ];

		$builder = new ModelQueryBuilder( $class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * Normalize the return result to ensure we have an Order_Modifier object.
	 *
	 * @param mixed $result The result to normalize.
	 *
	 * @return Order_Modifier The normalized Order Modifier.
	 * @throws Not_Found_Exception If the result is null.
	 * @throws RuntimeException If the result is not an Order Modifier.
	 */
	protected function normalize_return_result( $result ): Order_Modifier {
		if ( null === $result ) {
			throw new Not_Found_Exception( 'Order Modifier not found.' );
		}

		// We should always get an Order Modifier instance here, this is a sanity check.
		if ( ! $result instanceof Order_Modifier ) {
			throw new RuntimeException( 'Order Modifier not found.' );
		}

		return $result;
	}

	/**
	 * Validate the model type.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to validate.
	 *
	 * @return void
	 * @throws RuntimeException If the model type is invalid.
	 */
	protected function validate_model_type( Model $model ): void {
		if ( ! $model instanceof Order_Modifier ) {
			throw new RuntimeException( 'Invalid model type.' );
		}

		if ( $model->modifier_type !== $this->modifier_type ) {
			throw new RuntimeException( 'Invalid model type.' );
		}
	}
}
