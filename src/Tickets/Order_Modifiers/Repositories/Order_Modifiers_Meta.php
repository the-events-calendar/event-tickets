<?php
/**
 * Order Modifiers Meta repository.
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
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta as Table;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier_Meta;
/**
 * Class Order_Modifiers_Meta.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Repositories;
 */
class Order_Modifiers_Meta extends Repository implements Insertable, Updatable, Deletable {

	/**
	 * {@inheritDoc}
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert( Model $model ): Order_Modifier_Meta {
		DB::insert(
			Table::table_name(),
			[
				'order_modifier_id' => $model->order_modifier_id,
				'meta_key'          => $model->meta_key,
				'meta_value'        => $model->meta_value,
				'priority'          => $model->priority,
				'created_at'        => current_time( 'mysql' ),
			],
			[
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
			]
		);

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * {@inheritDoc}
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Order_Modifier_Meta::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( Model $model ): Model {
		DB::update(
			Table::table_name(),
			[
				'meta_key'   => $model->meta_key,
				'meta_value' => $model->meta_value,
				'priority'   => $model->priority,
			],
			[ 'id' => $model->id ],
			[
				'%s',
				'%s',
				'%d',
			],
			[ '%d' ]
		);

		return $model;
	}

	/**
	 * Finds metadata by `order_modifier_id`.
	 *
	 * @since TBD
	 *
	 * @param int $order_modifier_id The ID of the Order Modifier to find metadata for.
	 *
	 * @return Order_Modifier_Meta[]|null The Order Modifier Meta models, or null if not found.
	 */
	public function find_by_order_modifier_id( int $order_modifier_id ): ?array {
		return $this->prepareQuery()
					->where( 'order_modifier_id', $order_modifier_id )
					->get();
	}

	/**
	 * Finds metadata by `order_modifier_id` and `meta_key`.
	 *
	 * @since TBD
	 *
	 * @param int    $order_modifier_id The ID of the Order Modifier.
	 * @param string $meta_key The meta key to search by.
	 *
	 * @return Order_Modifier_Meta|null The Order Modifier Meta model instance, or null if not found.
	 */
	public function find_by_order_modifier_id_and_meta_key( int $order_modifier_id, string $meta_key ): ?Order_Modifier_Meta {
		return $this->prepareQuery()
					->where( 'order_modifier_id', $order_modifier_id )
					->where( 'meta_key', $meta_key )
					->get();
	}
}
