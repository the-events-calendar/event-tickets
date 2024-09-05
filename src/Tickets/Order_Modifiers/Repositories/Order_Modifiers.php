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
				'post_id'          => $model->post_id,
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
				'%d',
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
				'post_id'          => $model->post_id,
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
				'%d',
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
	 * @param int $id The ID of the Order Modifier to find.
	 *
	 * @return Order_Modifier|null The Order Modifier model instance, or null if not found.
	 */
	public function find_by_id( int $id ): ?Order_Modifier {
		return $this->prepareQuery()->where( 'id', $id )->get();
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
	 * Finds Order Modifiers by post_id and status.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID to find the modifiers for.
	 * @param string $status The status to filter by (e.g., 'active').
	 *
	 * @return Order_Modifier[]|null Array of Order Modifier model instances, or null if not found.
	 */
	public function find_by_post_id_and_status( int $post_id, string $status ): ?array {
		return $this->prepareQuery()
					->where( 'post_id', $post_id )
					->where( 'status', $status )
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
	 * Finds Order Modifiers by post_id, status, and sub_type.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID to find the modifiers for.
	 * @param string $status The status to filter by.
	 * @param string $sub_type The sub-type of the modifier (e.g., 'percentage' or 'flat').
	 *
	 * @return Order_Modifier[]|null Array of Order Modifier model instances, or null if not found.
	 */
	public function find_by_post_id_status_and_sub_type( int $post_id, string $status, string $sub_type ): ?array {
		return $this->prepareQuery()
					->where( 'post_id', $post_id )
					->where( 'status', $status )
					->where( 'sub_type', $sub_type )
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
