<?php
/**
 * Order Modifiers repository.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Repositories;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Repositories;

use RuntimeException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\DB\QueryBuilder\QueryBuilder;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships as Relationship_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers as Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Meta_Keys;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Status;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Tickets\Exceptions\Not_Found_Exception;

/**
 * Class Order_Modifiers.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Repositories;
 */
class Order_Modifiers extends Repository implements Insertable, Updatable, Deletable {

	use Meta_Keys;
	use Status;
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
	 * @since 5.18.0
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
	 * Deletes a model record.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model to delete.
	 *
	 * @return bool
	 * @throws RuntimeException If the model type is invalid.
	 */
	public function delete( Model $model ): bool {
		$this->validate_model_type( $model );

		return (bool) DB::delete(
			$this->get_table_name(),
			[ 'id' => $model->id ],
			[ '%d' ]
		);
	}

	/**
	 * Inserts a model record.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model to insert.
	 *
	 * @return Model
	 * @throws RuntimeException If the model type is invalid.
	 */
	public function insert( Model $model ): Model {
		$this->validate_model_type( $model );

		DB::insert(
			$this->get_table_name(),
			[
				'modifier_type' => $model->modifier_type,
				'sub_type'      => $model->sub_type,
				'raw_amount'    => $model->raw_amount,
				'slug'          => $model->slug,
				'display_name'  => $model->display_name,
				'status'        => $model->status,
				'created_at'    => current_time( 'mysql' ),
				'start_time'    => $model->start_time,
				'end_time'      => $model->end_time,
			],
			[
				'%s',
				'%s',
				'%f',
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
	 * Updates a model record.
	 *
	 * @since 5.18.0
	 *
	 * @param Model $model The model to update.
	 *
	 * @return Model
	 * @throws RuntimeException If the model type is invalid.
	 */
	public function update( Model $model ): Model {
		$this->validate_model_type( $model );

		DB::update(
			$this->get_table_name(),
			[
				'modifier_type' => $model->modifier_type,
				'sub_type'      => $model->sub_type,
				'raw_amount'    => $model->raw_amount,
				'slug'          => $model->slug,
				'display_name'  => $model->display_name,
				'status'        => $model->status,
				'start_time'    => $model->start_time,
				'end_time'      => $model->end_time,
			],
			[ 'id' => $model->id ],
			[
				'%s',
				'%s',
				'%f',
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
	 * @since 5.18.0
	 *
	 * @param int $id The ID of the Order Modifier to find.
	 *
	 * @return Order_Modifier The Order Modifier model instance.
	 * @throws Not_Found_Exception If the Order Modifier is not found.
	 * @throws RuntimeException If we didn't get an Order_Modifier object.
	 */
	public function find_by_id( int $id ): Order_Modifier {
		$result = $this->get_query_builder_with_from()
			->where( 'id', $id )
			->get();

		return $this->normalize_return_result( $result );
	}

	/**
	 * Get the count of Order Modifiers based on the given criteria.
	 *
	 * @since 5.18.1
	 *
	 * @param array $args Arguments for the query.
	 *
	 * @return int Number of Order Modifiers found.
	 */
	public function get_search_count( array $args = [] ): int {
		// Merge passed arguments with defaults.
		$args       = wp_parse_args( $args, $this->get_default_query_params() );
		$valid_args = $this->get_valid_params( $args );

		// Start building the query.
		$query = $this->get_query_builder_with_from();

		// Add search functionality (search in display_name or slug).
		if ( ! empty( $valid_args['search_term'] ) ) {
			$query->whereLike( 'display_name', $valid_args['search_term'] );
		}

		// Set the modifier type.
		$query = $query->where( 'modifier_type', $this->modifier_type );

		return $query->count() ?? 0;
	}

	/**
	 * Finds all active Order Modifiers of the current type.
	 *
	 * @since 5.18.0
	 *
	 * @return Order_Modifier[] Array of active Order Modifier model instances, or null if not found.
	 */
	public function find_active(): array {
		$result = $this->get_query_builder_with_from()
			->where( 'modifier_type', $this->modifier_type )
			->where( 'status', 'active' )
			->getAll();

		return $result ?? [];
	}

	/**
	 * Finds an Order Modifier of the current type by its slug.
	 *
	 * @since 5.18.0
	 *
	 * @param string $slug The slug of the Order Modifier to find.
	 *
	 * @return Order_Modifier The Order Modifier model instance.
	 * @throws Not_Found_Exception If the Order Modifier is not found.
	 */
	public function find_by_slug( string $slug ): Order_Modifier {
		$result = $this->get_query_builder_with_from()
			->where( 'modifier_type', $this->modifier_type )
			->where( 'slug', $slug )
			->where( 'status', 'active' )
			->get();

		return $this->normalize_return_result( $result );
	}

	/**
	 * Finds Order Modifiers by modifier_type.
	 *
	 * @since 5.18.0
	 *
	 * @return Order_Modifier[] Array of Order Modifier model instances.
	 */
	public function get_all(): array {
		$results = $this->get_query_builder_with_from()
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
	 * @since 5.18.0
	 *
	 * @param array  $post_ids      The array of post IDs to look up in the relationship table.
	 * @param string $modifier_type The type of modifier to filter by.
	 * @param string $status        The status of the modifiers to filter by. Defaults to 'active'.
	 *
	 * @return array The data from the related order modifiers.
	 */
	public function find_relationship_by_post_ids( array $post_ids, string $modifier_type, string $status = 'active' ): array {
		$this->validate_type( $modifier_type );

		$builder = $this->prepareQuery();

		// Table aliases for the query.
		$modifiers     = 'm';
		$relationships = 'r';

		$results = $builder
			->from( $this->get_table_name( false ), $modifiers )
			->select( "{$modifiers}.*" )
			->innerJoin(
				Relationship_Table::base_table_name(),
				"{$modifiers}.id",
				"{$relationships}.modifier_id",
				$relationships
			)
			->whereIn( "{$relationships}.post_id", $post_ids )
			->where( "{$modifiers}.modifier_type", $modifier_type )
			->where( "{$modifiers}.status", $status )
			->getAll();

		return $results ?? [];
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
	 * @since 5.18.0
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

		$tribe_cache = tribe( 'cache' );

		// Try to get the results from the cache.
		$cached_results = $tribe_cache[ $cache_key ] ?? false;
		if ( $cached_results && is_array( $cached_results ) ) {
			return $cached_results;
		}

		// Aliases for the tables.
		$modifiers = 'o';
		$meta      = 'm';

		// Initialize the SQL query with the base WHERE clause for modifier_type.
		$builder = new QueryBuilder();
		$builder
			->select( "{$modifiers}.*", "{$meta}.meta_value" )
			->from( $this->get_table_name( false ), $modifiers )
			->leftJoin(
				$this->get_meta_table_name( false ),
				"{$modifiers}.id",
				"{$meta}.order_modifier_id",
				$meta
			)
			->where( "{$modifiers}.modifier_type", $this->modifier_type )
			->where( "{$modifiers}.status", 'active' );

		// Handle the meta_key condition: Use IFNULL if a default_meta_key is provided, otherwise check for meta_key directly.
		if ( $default_meta_key ) {
			$builder->whereRaw(
				"IFNULL({$meta}.meta_key, %s) = %s",
				$default_meta_key,
				$meta_key
			);
		} else {
			$builder->where( "{$meta}.meta_key", $meta_key );
		}

		// Handle the meta_value condition: Use IFNULL if a default_meta_value is provided, otherwise check directly.
		if ( $default_meta_value ) {
			$meta_params_string = implode( ',', array_fill( 0, count( $meta_values ), '%s' ) );
			$builder->whereRaw(
				"IFNULL({$meta}.meta_value, %s) IN ({$meta_params_string})",
				$default_meta_value,
				...$meta_values
			);
		} else {
			$builder->whereIn( "{$meta}.meta_value", $meta_values );
		}

		// Prepare and execute the query.
		$results = $builder->getAll() ?? [];

		// Cache the results for future use.
		$tribe_cache[ $cache_key ] = $results;

		return $results;
	}

	/**
	 * Finds Order Modifiers by applied_to value.
	 *
	 * @since 5.18.1
	 *
	 * @param string[] $applied_to The value(s) to filter the query by.
	 * @param array    $params     {
	 *     Optional. Arguments to filter the query. See get_default_query_params() method for the full list of parameters.
	 *
	 *     @type string   $search_term The term to search for (e.g., in display_name or slug).
	 *     @type string   $orderby     Column to order by. Default 'display_name'.
	 *     @type string   $order       Sorting order. Either 'asc' or 'desc'. Default 'asc'.
	 *     @type int      $limit       The number of results to return. Default 10. Using -1 disables the limit.
	 *     @type int      $page        The page number to retrieve. Default 1.
	 *     @type string[] $status      The status of the modifiers to filter by. Default 'active'.
	 * }
	 *
	 * @return array
	 */
	public function get_modifier_by_applied_to( array $applied_to, array $params = [] ): array {
		$cache_key = 'cached_modifiers_by_applied_to_' . md5(
			wp_json_encode(
				[
					$this->modifier_type,
					$params,
					$applied_to,
				]
			)
		);

		$tribe_cache = tribe( 'cache' );

		// Try to get the results from the cache.
		$cached_results = $tribe_cache[ $cache_key ] ?? false;
		if ( $cached_results && is_array( $cached_results ) ) {
			return $cached_results;
		}

		$results = $this->get_modifiers( $params, true, fn( $builder, $modifiers, $meta ) => $builder->whereIn( "{$meta}.meta_value", $applied_to ) );

		// Cache the results for future use.
		$tribe_cache[ $cache_key ] = $results;

		return $results;
	}

	/**
	 * Finds Order Modifiers with applied_to meta value.
	 *
	 * @since 5.18.1
	 *
	 * @param array         $params     {
	 *     Optional. Arguments to filter the query. See get_default_query_params() method for the full list of parameters.
	 *
	 *     @type string   $search_term The term to search for (e.g., in display_name or slug).
	 *     @type string   $orderby     Column to order by. Default 'display_name'.
	 *     @type string   $order       Sorting order. Either 'asc' or 'desc'. Default 'asc'.
	 *     @type int      $limit       The number of results to return. Default 10. Using -1 disables the limit.
	 *     @type int      $page        The page number to retrieve. Default 1.
	 *     @type string[] $status      The status of the modifiers to filter by. Default 'active'.
	 * }
	 * @param bool          $with_applied_to_meta Whether to include the applied_to meta in the query.
	 * @param callable|null $closure   Optional. A closure to modify the query builder.
	 *
	 * @return array
	 */
	public function get_modifiers( array $params = [], bool $with_applied_to_meta = true, ?callable $closure = null ): array {
		// Generate a cache key based on the arguments.
		$cache_key = 'cached_modifiers_' . md5(
			wp_json_encode(
				[
					$this->modifier_type,
					$params,
					$with_applied_to_meta,
				]
			)
		);

		$tribe_cache = tribe( 'cache' );

		// Try to get the results from the cache.
		$cached_results = $tribe_cache[ $cache_key ] ?? false;
		if ( $cached_results && is_array( $cached_results ) && null === $closure ) {
			return $cached_results;
		}

		// Set up default query parameters.
		$params = wp_parse_args( $params, $this->get_default_query_params() );

		// Validate the parameters before using them in the query.
		$valid_params = $this->get_valid_params( $params );

		// Table aliases for the query.
		$modifiers = 'o';
		$meta      = 'm';

		// Initialize the query builder and construct the query.
		$builder = new QueryBuilder();
		$builder
			->from( $this->get_table_name( false ), $modifiers )
			->select( "{$modifiers}.*" )
			->where( "{$modifiers}.modifier_type", $this->modifier_type );

		if ( $with_applied_to_meta ) {
			$builder
				->select( "{$meta}.meta_value" )
				->innerJoin(
					$this->get_meta_table_name( false ),
					"{$modifiers}.id",
					"{$meta}.order_modifier_id",
					$meta
				)
				->where(
					"{$meta}.meta_key",
					$this->get_applied_to_key( $this->modifier_type )
				);
		}

		// Add the status params to the pieces.
		if ( array_key_exists( 'status', $valid_params ) ) {
			$builder->whereIn( "{$modifiers}.status", $valid_params['status'] );
		}

		// Add the order param to the pieces.
		if ( array_key_exists( 'order', $valid_params ) ) {
			$orderby = array_key_exists( 'orderby', $valid_params ) ? $valid_params['orderby'] : 'id';
			$builder->orderBy( "{$modifiers}.{$orderby}", $valid_params['order'] );
		}

		// Add the limit param to the pieces.
		if ( array_key_exists( 'limit', $valid_params ) ) {
			$builder->limit( $valid_params['limit'] );
		}

		// Add the search term to the pieces.
		if ( ! empty( $valid_params['search_term'] ) ) {
			$builder->whereLike( "{$modifiers}.display_name", $valid_params['search_term'] );
		}

		// Add the query offset.
		if ( ! empty( $valid_params['offset'] ) ) {
			$builder->offset( $valid_params['offset'] );
		}

		// Allow external modifications.
		if ( is_callable( $closure ) ) {
			$builder = $closure( $builder, $modifiers, $meta );
		}

		$results = $builder->getAll() ?? [];

		// Cache the results for future use.
		$tribe_cache[ $cache_key ] = $results;

		return $results;
	}

	/**
	 * Prepare a query builder for the repository.
	 *
	 * @since 5.18.0
	 *
	 * @return ModelQueryBuilder The query builder object.
	 */
	public function prepareQuery(): ModelQueryBuilder {
		// Determine the model class based on the modifier type.
		$this->validate_type( $this->modifier_type );
		$class = $this->get_valid_types()[ $this->modifier_type ];

		return new ModelQueryBuilder( $class );
	}

	/**
	 * Wrapper for getting the query builder with table name as the FROM clause.
	 *
	 * This will call ->from() on the query builder with the table name before
	 * the object is returned.
	 *
	 * @since 5.18.1
	 *
	 * @return ModelQueryBuilder The query builder object.
	 */
	protected function get_query_builder_with_from(): ModelQueryBuilder {
		$builder = $this->prepareQuery();

		return $builder->from( $this->get_table_name( false ) );
	}

	/**
	 * Wrapper for getting the table name.
	 *
	 * This allows for easy interpolation in strings.
	 *
	 * @since 5.18.1
	 *
	 * @param bool $with_prefix Whether to include the table prefix. Default is true.
	 *
	 * @return string The table name.
	 */
	protected function get_table_name( bool $with_prefix = true ): string {
		return Table::table_name( $with_prefix );
	}

	/**
	 * Wrapper for getting the meta table name.
	 *
	 * This allows for easy interpolation in strings.
	 *
	 * @since 5.18.1
	 *
	 * @param bool $with_prefix Whether to include the table prefix. Default is true.
	 *
	 * @return string The meta table name.
	 */
	protected function get_meta_table_name( bool $with_prefix = true ): string {
		return Order_Modifiers_Meta::table_name( $with_prefix );
	}

	/**
	 * Get the default query parameters.
	 *
	 * @since 5.18.1
	 *
	 * @return array The default query parameters.
	 */
	protected function get_default_query_params(): array {
		return [
			'order'       => 'ASC',
			'orderby'     => 'id',
			'search_term' => '',
			'status'      => [ 'any' ],
		];
	}

	/**
	 * Get the valid parameters for the query.
	 *
	 * This will remove any parameters that we don't handle from the query and do basic
	 * validation of the value of each query parameter.
	 *
	 * @since 5.18.1
	 *
	 * @param array $params The parameters to validate.
	 *
	 * @return array The valid parameters.
	 */
	protected function get_valid_params( array $params ): array {
		$valid_params = [];
		foreach ( $params as $key => $value ) {
			switch ( $key ) {
				case 'limit':
					// -1 means we should not limit the results, so skip adding to the params.
					if ( -1 === $value ) {
						break;
					}
					// NO break; Deliberately fall through to the next case.

				case 'offset':
				case 'page':
					$valid_params[ $key ] = absint( $value );
					break;

				case 'order':
					$value                = strtoupper( $value );
					$valid_params[ $key ] = 'ASC' === $value ? 'ASC' : 'DESC';
					break;

				case 'orderby':
					$valid_orderby = [
						'display_name' => 1,
						'slug'         => 1,
						'raw_amount'   => 1,
						'used'         => 1,
						'remaining'    => 1,
						'status'       => 1,
					];
					if ( array_key_exists( $value, $valid_orderby ) ) {
						$valid_params[ $key ] = $value;
					}
					break;

				case 'search_term':
					$valid_params[ $key ] = ! empty( $value ) && is_string( $value ) ? DB::esc_like( $value ) : '';
					break;

				case 'status':
					// If 'any' is passed, skip the status.
					if ( 'any' === $value || ( is_array( $value ) && in_array( 'any', $value, true ) ) ) {
						break;
					}

					$valid_params[ $key ] = array_filter(
						(array) $value,
						fn( $status ) => $this->is_valid_status( $status )
					);
					break;

				// Default is to skip adding the parameter.
				default:
					break;
			}
		}

		// If the page parameter is passed, set the offset based on the page and limit.
		if ( array_key_exists( 'page', $valid_params ) && array_key_exists( 'limit', $valid_params ) ) {
			$valid_params['offset'] = ( $valid_params['page'] - 1 ) * $valid_params['limit'];
		}

		return $valid_params;
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
	 * @since 5.18.0
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
