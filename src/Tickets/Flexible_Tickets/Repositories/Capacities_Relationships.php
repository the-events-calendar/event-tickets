<?php
/**
 * The repository for the Capacities_Relationships model.
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
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships as Table;
use TEC\Tickets\Flexible_Tickets\Models\Capacity_Relationship;

/**
 * Class Capacities_Relationships.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Capacities_Relationships extends Repository implements Insertable, Updatable, Deletable {
	/**
	 * Finds a capacity relationship by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The ID of the capacity to find.
	 *
	 * @return Capacity_Relationship|null The capacity, or null if not found.
	 */
	public function find_by_id( int $id ): ?Capacity_Relationship {
		return $this->prepareQuery()->where( 'id', $id )->get();
	}

	/**
	 * Finds a capacity relationship by its `object_id` field.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The ID of the object to find.
	 *
	 * @return Capacity_Relationship|null The capacity relationship, or null if not found.
	 */
	public function find_by_object_id( int $object_id ): ?Capacity_Relationship {
		return $this->prepareQuery()->where( 'object_id', $object_id )->get();
	}

	/**
	 * Returns a query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder for the model.
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Capacity_Relationship::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * Deletes a capacity relationship from the repository.
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
	 * Inserts a capacity relationship into the database.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to insert.
	 *
	 * @return Model The inserted model.
	 */
	public function insert( Model $model ): Model {
		DB::insert(
			Table::table_name(),
			[
				'capacity_id'        => $model->capacity_id,
				'parent_capacity_id' => $model->parent_capacity_id,
				'object_id'          => $model->object_id,
			],
			[ '%d', '%d', '%s' ]
		);

		$model->id = DB::insert_id();

		return $model;
	}

	/**
	 * Updates a capacity relationship in the database.
	 *
	 * @since TBD
	 *
	 * @param Model $model The model to update.
	 *
	 * @return Model The updated model.
	 */
	public function update( Model $model ): Model {
		DB::update(
			Table::table_name(),
			[
				'capacity_id'        => $model->capacity_id,
				'parent_capacity_id' => $model->parent_capacity_id,
				'object_id'          => $model->object_id,
			],
			[ 'id' => $model->id ],
			[ '%d', '%d', '%s' ],
			[ '%d' ]
		);

		return $model;
	}
}