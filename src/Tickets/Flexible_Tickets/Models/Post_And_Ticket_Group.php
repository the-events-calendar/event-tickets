<?php
/**
 * The Post and Ticket Group relationship model.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelPersistable;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Repositories\Posts_And_Ticket_Groups;
use TEC\Common\StellarWP\Models\Contracts\Model as ModelInterface;
use TEC\Common\StellarWP\Models\ModelProperty;

/**
 * Class Post_And_Ticket_Group.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int    $id        The relationship ID.
 * @property int    $post_id   The Post, or Provisional Post, ID part of the relationship.
 * @property int    $group_id  The Ticket Group ID part of the relationship.
 * @property string $type      The type of the relationship.
 */
class Post_And_Ticket_Group extends Model implements ModelPersistable {
	/**
	 * @inheritDoc
	 */
	protected static array $properties = [
		'id'       => 'int',
		'post_id'  => 'int',
		'group_id' => 'int',
		'type'     => 'string',
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
	 * Finds a model instance by ID.
	 *
	 * @since 5.8.0
	 *
	 * @param int $id The model ID.
	 *
	 * @return self|null The model instance or null if not found.
	 */
	public static function find( $id ): ?self {
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
}
