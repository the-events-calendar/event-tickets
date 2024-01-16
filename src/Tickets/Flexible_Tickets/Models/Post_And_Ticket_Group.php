<?php
/**
 * The Post and Ticket Group relationship model.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Contracts\ModelFromQueryBuilderObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Post_And_Ticket_Group_DTO;
use TEC\Tickets\Flexible_Tickets\Repositories\Posts_And_Ticket_Groups;

/**
 * Class Post_And_Ticket_Group.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int    $id        The relationship ID.
 * @property int    $post_id   The Post, or Provisional Post, ID part of the relationship.
 * @property int    $group_id  The Ticket Group ID part of the relationship.
 * @property string $type      The type of the relationship.
 */
class Post_And_Ticket_Group extends Model implements ModelCrud, ModelFromQueryBuilderObject {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'       => 'int',
		'post_id'  => 'int',
		'group_id' => 'int',
		'type'     => 'string',
	];

	public static function find( $id ) {
		return tribe( Posts_And_Ticket_Groups::class )->find_by_id( $id );
	}

	/**
	 * Creates a new model instance and saves it to the database.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return static
	 */
	public static function create( array $attributes ): self {
		$model = new self( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since 5.8.0
	 *
	 * @return static
	 */
	public function save(): self {
		if ( $this->id ) {
			return tribe( Posts_And_Ticket_Groups::class )->update( $this );
		}

		$this->id = tribe( Posts_And_Ticket_Groups::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since 5.8.0
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return tribe( Posts_And_Ticket_Groups::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 5.8.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Posts_And_Ticket_Groups::class )->query();
	}

	/**
	 * Builds a new model from a query builder object.
	 *
	 * @since 5.8.0
	 *
	 * @param object $object The query builder object.
	 *
	 * @return static The model instance.
	 */
	public static function fromQueryBuilderObject( $object ) {
		return Post_And_Ticket_Group_DTO::fromObject( $object )->toModel();
	}
}
