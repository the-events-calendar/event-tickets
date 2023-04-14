<?php
/**
 * The CRUD model for the Capacity Relationship.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Contracts\ModelFromQueryBuilderObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Capacity_Relationship_DTO;
use TEC\Tickets\Flexible_Tickets\Repositories\Capacities_Relationships;

/**
 * Class Capacity_Relationship.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int $id                 The capacity relationship ID.
 * @property int $capacity_id        The capacity ID.
 * @property int $parent_capacity_id The parent capacity ID.
 * @property int $object_id          The object ID.
 */
class Capacity_Relationship extends Model implements ModelCrud, ModelFromQueryBuilderObject {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'                 => 'int',
		'capacity_id'        => 'int',
		'parent_capacity_id' => 'int',
		'object_id'          => 'int',
	];

	/**
	 * Finds a capacity relationship by its ID.
	 *
	 * @since TBD
	 *
	 * @param $id
	 *
	 * @return Capacity_Relationship|null
	 */
	public static function find( $id ): ?Capacity_Relationship {
		return tribe( Capacities_Relationships::class )->find_by_id( $id );
	}

	/**
	 * Creates and saves to database a new Capacity Relationship instance.
	 *
	 * @since TBD
	 *
	 * @param array $attributes
	 *
	 * @return Capacity_Relationship The created capacity relationship.
	 */
	public static function create( array $attributes ) {
		$capacity_relationship = new Capacity_Relationship( $attributes );
		$capacity_relationship->save();

		return $capacity_relationship;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since TBD
	 *
	 * @return Capacity_Relationship The saved model.
	 */
	public function save(): Capacity_Relationship {
		$this->id = tribe( Capacities_Relationships::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the model was deleted or not.
	 */
	public function delete(): bool {
		return tribe( Capacities_Relationships::class )->delete( $this );
	}

	/**
	 * Returns a query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Capacities_Relationships::class )->query();
	}

	/**
	 * Creates a model from a DTO object.
	 *
	 * @since TBD
	 *
	 * @param object $object The query data object.
	 *
	 * @return Capacity_Relationship The model built from the data.
	 */
	public static function fromQueryBuilderObject( $object ): Capacity_Relationship {
		return Capacity_Relationship_DTO::fromObject( $object )->toModel();
	}
}