<?php
/**
 * Posts and Ticket Groups relationships Repository.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Repositories;

use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Deletable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Insertable;
use TEC\Common\StellarWP\Models\Repositories\Contracts\Updatable;
use TEC\Common\StellarWP\Models\Repositories\Repository;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups as Table;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Ticket_Group;

/**
 * Class Posts_And_Ticket_Groups.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Posts_And_Ticket_Groups extends Repository implements Insertable, Updatable, Deletable {

	/**
	 * {@inheritDoc}
	 */
	public function delete( Model $model ): bool {
		return (bool) DB::delete( Table::table_name(), [ 'id' => $model->id ], [ '%d' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert( Model $model ): Post_And_Ticket_Group {
		DB::insert( Table::table_name(), [
			'post_id'  => $model->post_id,
			'group_id' => $model->group_id,
			'type'     => $model->type,
		], [
			'%s',
			'%s',
			'%s',
		] );

		$model->id = DB::last_insert_id();

		return $model;
	}

	/**
	 * {@inheritDoc}
	 */
	function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Post_And_Ticket_Group::class );

		return $builder->from( Table::table_name( false ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( Model $model ): Post_And_Ticket_Group {
		DB::update( Table::table_name(), [
			'post_id'  => $model->post_id,
			'group_id' => $model->group_id,
			'type'     => $model->type,
		], [ 'id' => $model->id ], [
			'%s',
			'%s',
			'%s',
		], [ '%d' ] );

		return $model;
	}

	/**
	 * Find a Post_And_Ticket_Group by its ID.
	 *
	 * @param int $id
	 *
	 * @return Post_And_Ticket_Group|null
	 */
	public function find_by_id( int $id ): ?Post_And_Ticket_Group {
		return $this->prepareQuery()->where( 'id', $id )->get();
	}
}