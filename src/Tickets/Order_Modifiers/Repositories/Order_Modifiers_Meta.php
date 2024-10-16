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
	 * @since TBD
	 *
	 * @param Model $model The model.
	 *
	 * @return false|int
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * Inserts a model record.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model.
	 *
	 * @return Order_Modifier_Meta
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
	 * Prepare a query builder for the repository.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder
	 */
	public function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Order_Modifier_Meta::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * Inserts a model record.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model.
	 *
	 * @return Order_Modifier_Meta
	 */
	public function update( Model $model ): Model {
		DB::update(
			Table::table_name(),
			[
				'meta_value' => $model->meta_value,
				'priority'   => $model->priority,
			],
			[
				'order_modifier_id' => $model->order_modifier_id,
				'meta_key'          => $model->meta_key,
			],
			[
				'%s',
				'%d',
			],
			[ '%d', '%s' ]
		);

		return $model;
	}

	/**
	 * Insert or update meta data for a given Order Modifier.
	 *
	 * This method checks if metadata already exists for a specific
	 * `order_modifier_id` and `meta_key`. If it exists, it updates the record;
	 * otherwise, it inserts a new record.
	 *
	 * @since TBD
	 *
	 * @param Order_Modifier_Meta $meta_data The metadata object containing the
	 *                                       order_modifier_id, meta_key, and meta_value.
	 *
	 * @return mixed The result of the insert or update operation, depending on
	 *               what was performed (insert or update).
	 */
	public function upsert_meta( Order_Modifier_Meta $meta_data ): mixed {
		// Check if a record with this order_modifier_id and meta_key already exists.
		$existing_meta = $this->find_by_order_modifier_id_and_meta_key(
			$meta_data->order_modifier_id,
			$meta_data->meta_key
		);

		if ( $existing_meta ) {
			// If the record exists, update it with the new data.
			return $this->update( $meta_data );
		}

		// If no existing record is found, insert a new one.
		return $this->insert( $meta_data );
	}

	/**
	 * Finds metadata by `order_modifier_id`.
	 *
	 * @since TBD
	 *
	 * @param int $order_modifier_id The ID of the Order Modifier to find metadata for.
	 *
	 * @return ModelQueryBuilder The Order Modifier Meta models.
	 */
	public function find_by_order_modifier_id( int $order_modifier_id ): ModelQueryBuilder {
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
	 * @return Order_Modifier_Meta| null The Order Modifier Meta models.
	 */
	public function find_by_order_modifier_id_and_meta_key( int $order_modifier_id, string $meta_key ): ?Order_Modifier_Meta {
		return $this->prepareQuery()
					->where( 'order_modifier_id', $order_modifier_id )
					->where( 'meta_key', $meta_key )
					->limit( 1 )
					->get();
	}
}