<?php
/**
 * The Capacities model repository.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Repositories;

use Exception;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities as Table;
use TEC\Tickets\Flexible_Tickets\Models\Capacity;

/**
 * Class Capacities.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Capacities extends Repository implements Insertable, Updatable, Deletable {
	public static function upsert_global_capaacity() {
	}

	/**
	 * Returns a query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder for the model.
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Capacity::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * Deletes a capacity from the repository.
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
	 * Inserts a capacity into the database.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to insert.
	 *
	 * @return Capacity The inserted model, its ID set.
	 *
	 * @throws Exception If the model insertion fails.
	 */
	public function insert( Model $model ): Capacity {
		DB::insert( Table::table_name(), [
			'max_value'     => $model->max_value,
			'current_value' => $model->current_value,
			'mode'          => $model->mode,
			'name'          => $model->name,
			'description'   => $model->description,
		], [
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
		] );

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * Updates a capacity in the database.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to update.
	 *
	 * @return Model The updated model.
	 *
	 * @throws Exception If the model update fails.
	 */
	public function update( Model $model ): Model {
		DB::update( Table::table_name(), [
			'max_value'     => $model->max_value,
			'current_value' => $model->current_value,
			'mode'          => $model->mode,
			'name'          => $model->name,
			'description'   => $model->description,
		], [ 'id' => $model->id ], [
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
		], [ '%d' ] );

		return $model;
	}

	/**
	 * Finds a capacity by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The ID of the capacity to find.
	 *
	 * @return Capacity|null The capacity, or null if not found.
	 */
	public function find_by_id( int $id ): ?Capacity {
		return $this->prepareQuery()->where( 'id', $id )->get();
	}
}