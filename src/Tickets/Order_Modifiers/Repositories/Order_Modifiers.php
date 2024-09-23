<?php
/**
 * Order Modifiers repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Repositories;
 */

namespace TEC\Tickets\Order_Modifiers\Repositories;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers as Table;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;

/**
 * Class Order_Modifiers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Repositories;
 */
class Order_Modifiers extends Repository implements Insertable, Updatable, Deletable {

	/**
	 * {@inheritDoc}
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert( Model $model ): Order_Modifier {
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

		// Return the correct Order_Modifier model.
		return $model;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( Model $model ): Order_Modifier {
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

		// Return the updated Order_Modifier model.
		return $model;
	}

	/**
	 * Finds an Order Modifier by its ID.
	 *
	 * @since TBD
	 *
	 * @param int    $id The ID of the Order Modifier to find.
	 * @param string $type The type of Order Modifier to find.
	 *
	 * @return Order_Modifier|null The Order Modifier model instance, or null if not found.
	 */
	public function find_by_id( int $id, $type ): ?Order_Modifier {
		return $this->prepareQuery()
					->where( 'id', $id )
					->where( 'modifier_type', $type )
					->get();
	}

	/**
	 * Search for Order Modifiers based on the given criteria.
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter the query.
	 *
	 * @type string $modifier_type The type of the modifier ('coupon' or 'fee').
	 * @type string $search_term The term to search for (e.g., in display_name or slug).
	 * @type string $orderby Column to order by. Default 'display_name'.
	 * @type string $order Sorting order. Either 'asc' or 'desc'. Default 'asc'.
	 * }
	 *
	 * @return array An array of Order_Modifiers or an empty array if none found.
	 */
	public function search_modifiers( array $args = [] ): array {
		// Define default arguments.
		$defaults = [
			'modifier_type' => '',
			'search_term'   => '',
			'orderby'       => 'display_name',
			'order'         => 'asc',
		];

		// Merge passed arguments with defaults.
		$args = array_merge( $defaults, $args );

		// Start building the query.
		$query = $this->prepareQuery();

		// Filter by modifier type if provided.
		if ( ! empty( $args['modifier_type'] ) ) {
			$query = $query->where( 'modifier_type', $args['modifier_type'] );
		}

		// Add search functionality (search in display_name or slug).
		if ( ! empty( $args['search_term'] ) ) {
			$query = $query->whereLike( 'display_name', $args['search_term'] );
		}

		// Add ordering.
		if ( ! empty( $args['orderby'] ) && in_array(
			$args['orderby'],
			[
				'display_name',
				'slug',
				'fee_amount_cents',
				'used',
				'remaining',
				'status',
			]
		)
		) {
			$query = $query->orderBy( $args['orderby'], $args['order'] );
		}

		// Return the results of the query.
		return $query->getAll() ?? [];
	}

	/**
	 * Finds an Order Modifier by its slug and modifier_type.
	 *
	 * @since TBD
	 *
	 * @param string $slug The slug of the Order Modifier to find.
	 * @param string $modifier_type The type of the modifier ('coupon' or 'fee').
	 *
	 * @return Order_Modifier|null The Order Modifier model instance, or null if not found.
	 */
	public function find_by_slug( string $slug, string $modifier_type ): ?Order_Modifier {
		return $this->prepareQuery()
					->where( 'slug', $slug )
					->where( 'modifier_type', $modifier_type )
					->where( 'status', 'active' )
					->get();
	}

	/**
	 * Finds all active Order Modifiers.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier[]|null Array of active Order Modifier model instances, or null if not found.
	 */
	public function find_active(): ?array {
		return $this->prepareQuery()
					->where( 'status', 'active' )
					->get();
	}

	/**
	 * Finds Order Modifiers by modifier_type.
	 *
	 * @since TBD
	 *
	 * @param string $modifier_type The type of modifier to find (e.g., 'coupon' or 'fee').
	 *
	 * @return Order_Modifier[]|null Array of Order Modifier model instances, or null if not found.
	 */
	public function find_by_modifier_type( string $modifier_type ): ?array {
		return $this->prepareQuery()
					->where( 'modifier_type', $modifier_type )
					->get();
	}

	/**
	 * {@inheritDoc}
	 */
	public function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Order_Modifier::class );
		return $builder->from( Table::table_name( false ) );
	}
}
