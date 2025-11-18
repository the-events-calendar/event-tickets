<?php
/**
 * The ticket group model.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelPersistable;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Repositories\Ticket_Groups;
use TEC\Common\StellarWP\Models\Contracts\Model as ModelInterface;
use TEC\Common\StellarWP\Models\ModelProperty;

/**
 * Class Ticket_Group.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int    $id            The Ticket Group ID.
 * @property string $slug          The Ticket Group slug.
 * @property string $data          The Ticket Group data in JSON format.
 */
class Ticket_Group extends Model implements ModelPersistable {

	/**
	 * @inheritDoc
	 *
	 * @since 5.24.1 Add `name`, `capacity`, and `cost` properties.
	 */
	protected static array $properties = [
		'id'       => 'int',
		'slug'     => 'string',
		'data'     => 'string',
		'name'     => 'string',
		'capacity' => 'int',
		'cost'     => 'string',
	];

	/**
	 * Validate the model data after construction.
	 *
	 * @since 5.27.0
	 *
	 * @return void
	 */
	protected function afterConstruct(): void {
		$this->propertyCollection->tap(
			function ( ModelProperty $property ) {
				if ( null !== $property->getValue() && method_exists( $this, "validate_{$property->getKey()}" ) ) {
					$this->{"validate_{$property->getKey()}"}( $property->getValue() );
				}
			}
		);
	}

	/**
	 * Set an attribute on the model.
	 *
	 * @since 5.27.0
	 *
	 * @param string $key   The attribute key.
	 * @param mixed  $value The attribute value.
	 *
	 * @return ModelInterface
	 */
	public function setAttribute( string $key, $value ): ModelInterface {
		parent::setAttribute( $key, $value );

		if ( null !== $value && method_exists( $this, "validate_{$key}" ) ) {
			$this->{"validate_{$key}"}( $value );
		}

		return $this;
	}

	/**
	 * Finds a model by its ID.
	 *
	 * @since 5.8.0
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
	 * @since 5.8.0
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
	 * @since 5.8.0
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
	 * @since 5.8.0
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return tribe( Ticket_Groups::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 5.8.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Ticket_Groups::class )->query();
	}
}
