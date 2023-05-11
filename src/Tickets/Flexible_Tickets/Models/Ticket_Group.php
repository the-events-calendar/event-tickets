<?php
/**
 * The ticket group model.
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
use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Ticket_Group_DTO;
use TEC\Tickets\Flexible_Tickets\Repositories\Ticket_Groups;

/**
 * Class Ticket_Group.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int    $id            The Ticket Group ID.
 * @property string $slug          The Ticket Group slug.
 * @property string $data          The Ticket Group data in JSON format.
 */
class Ticket_Group extends Model implements ModelCrud, ModelFromQueryBuilderObject {

	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'   => 'int',
		'slug' => 'string',
		'data' => 'string',
	];

	/**
	 * Finds a model by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The model ID.
	 *
	 * @return Ticket_Group|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		return tribe( Ticket_Groups::class )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return Ticket_Group The model instance.
	 */
	public static function create( array $attributes ): self {
		$model = new self( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since TBD
	 *
	 * @return Ticket_Group The model instance.
	 */
	public function save(): self {
		if ( $this->id ) {
			return tribe( Ticket_Groups::class )->update( $this );
		}

		$this->id = tribe( Ticket_Groups::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return tribe( Ticket_Groups::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Ticket_Groups::class )->query();
	}

	/**
	 * Builds a new model from a query builder object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the model from.
	 *
	 * @return Ticket_Group The model instance.
	 */
	public static function fromQueryBuilderObject( $object ): self {
		return Ticket_Group_DTO::fromObject( $object )->toModel();
	}
}