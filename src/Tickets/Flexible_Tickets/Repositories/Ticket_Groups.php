<?php
/**
 * Ticket Groups repository.
 *
 * @since 5.8.0
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
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups as Table;
use TEC\Tickets\Flexible_Tickets\Models\Ticket_Group;

/**
 * Class Ticket_Groups.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Ticket_Groups extends Repository implements Insertable, Updatable, Deletable {

	/**
	 * {@inheritDoc}
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.24.1 Add `name`, `capacity`, and `cost` columns for Ticket Presets use.
	 */
	public function insert( Model $model ): Ticket_Group {
		DB::insert( Table::table_name(), [
			'slug'     => $model->slug,
			'data'     => $model->data,
			'name'     => $model->name,
			'capacity' => $model->capacity,
			'cost'     => $model->cost, // pass as string.
		], [
			'%s',
			'%s',
			'%s',
			'%d',
			'%s', // Use %s for cost to avoid float conversion.
		] );

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * {@inheritDoc}
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Ticket_Group::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.24.1 Add `name`, `capacity`, and `cost` columns for Ticket Presets use.
	 */
	public function update( Model $model ): Model {
		DB::update(
			Table::table_name(),
			[
				'slug'     => $model->slug,
				'data'     => $model->data,
				'name'     => $model->name,
				'capacity' => $model->capacity,
				'cost'     => $model->cost, // pass as string.
			],
			[
				'id' => $model->id,
			],
			[
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			],
			[ '%d' ]
		);

		return $model;
	}

	/**
	 * Finds a Ticket Group by its ID.
	 *
	 * @since 5.8.0
	 *
	 * @param int $id The ID of the Ticket Group to find.
	 *
	 * @return Ticket_Group|null The Ticket Group model instance, or null if not found.
	 */
	public function find_by_id( int $id ): ?Ticket_Group {
		return $this->prepareQuery()->where( 'id', $id )->get();
	}
}
