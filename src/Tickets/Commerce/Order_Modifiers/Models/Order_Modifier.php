<?php
/**
 * The Order Modifier model.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Models;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use RuntimeException;
use TEC\Common\StellarWP\Models\Contracts\Model as ModelInterface;
use TEC\Common\StellarWP\Models\Contracts\ModelPersistable;
use TEC\Common\StellarWP\Models\ModelPropertyDefinition;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Commerce\Order_Modifiers\Factory;
use TEC\Tickets\Commerce\Values\Float_Value;
use TEC\Tickets\Commerce\Values\Percent_Value;
use TEC\Tickets\Commerce\Values\Value_Interface;

/**
 * Class Order_Modifier.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Models;
 *
 * @property int    $id              The Order Modifier ID.
 * @property string $modifier_type   The type of modifier (coupon, fee).
 * @property string $sub_type        The sub-type of modifier (percentage, flat).
 * @property float  $raw_amount      Raw fee amount.
 * @property string $slug            The Order Modifier slug (coupon code).
 * @property string $display_name    User-friendly name.
 * @property string $status          The status (active, draft, inactive).
 * @property string $created_at      Creation timestamp.
 * @property string $start_time      When the modifier becomes active.
 * @property string $end_time        When the modifier expires.
 */
class Order_Modifier extends Model implements ModelPersistable {

	/**
	 * The model properties assigned to their types.
	 *
	 * @var array<string,string>
	 */
	protected static array $properties = [
		'id'            => 'int',
		'modifier_type' => 'string',
		'slug'          => 'string',
		'display_name'  => 'string',
		'status'        => 'string',
		'created_at'    => 'string',
		'start_time'    => 'string',
		'end_time'      => 'string',
	];

	/**
	 * The order modifier type (e.g., 'coupon', 'fee').
	 *
	 * This will be defined in child classes.
	 *
	 * @since 5.18.0
	 * @var string
	 */
	protected static string $order_modifier_type;

	/**
	 * Returns the properties definition for this model.
	 *
	 * @since 5.18.0
	 * @since 5.27.0 Upgraded properties definition to use ModelPropertyDefinition.
	 *
	 * @return array<string, ModelPropertyDefinition>
	 */
	protected static function properties(): array {
		return [
			'sub_type'   => ( new ModelPropertyDefinition() )->type( 'string' )->castWith(
				function ( $value ): string {
					$value = strtolower( (string) $value );
					if ( ! in_array( $value, [ 'flat', 'percentage' ], true ) ) {
						throw new RuntimeException( 'Invalid modifier sub_type: ' . $value );
					}

					return $value;
				}
			),
			'raw_amount' => ( new ModelPropertyDefinition() )->type( 'float', Float_Value::class, Percent_Value::class )->castWith(
				function ( $value ) {
					return (float) $value;
				}
			),
		];
	}

	/**
	 * Finds a model by its ID.
	 *
	 * @since 5.18.0
	 *
	 * @param int $id The model ID.
	 *
	 * @return Order_Modifier|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		if ( empty( static::$order_modifier_type ) ) {
			return null;
		}

		return Factory::get_repository_for_type( static::$order_modifier_type )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since 5.18.0
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return static
	 */
	public static function create( array $attributes ): self {
		// Maybe override the modifier type based on the final class.
		if ( property_exists( static::class, 'order_modifier_type' ) ) {
			$attributes['modifier_type'] = static::$order_modifier_type;
		}

		$model = new static( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since 5.18.0
	 *
	 * @return static
	 */
	public function save(): self {
		$repository = Factory::get_repository_for_type( static::$order_modifier_type );
		if ( $this->id ) {
			$repository->update( $this );

			return $this;
		}

		$this->id = $repository->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since 5.18.0
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return Factory::get_repository_for_type( static::$order_modifier_type )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 5.18.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return Factory::get_repository_for_type( static::$order_modifier_type )->prepareQuery();
	}

	/**
	 * Converts the Order_Modifier object to an array.
	 *
	 * @since 5.18.0
	 *
	 * @deprecated 5.27.0 Use toArray() instead.
	 *
	 * @return array The object properties as an array.
	 */
	public function to_array(): array {
		_deprecated_function( __METHOD__, '5.27.0', 'toArray()' );
		return $this->toArray();
	}

	/**
	 * Sets an attribute on the model.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return ModelInterface
	 */
	public function setAttribute( string $key, $value ): ModelInterface {
		$this->run_validation_method( $key, $value );

		// Ensure specific attributes are stored as value objects.
		switch ( $key ) {
			case 'raw_amount':
				if ( ! $value instanceof Value_Interface ) {
					$value = Float_Value::from_number( $value );
				}
				break;
			default:
				// No specific action needed.
				break;
		}

		parent::setAttribute( $key, $value );

		return $this;
	}

	/**
	 * Returns an attribute from the model.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key     Attribute name.
	 * @param mixed  $default Default value. Default is null.
	 *
	 * @return mixed The attribute value.
	 * @throws RuntimeException When the attribute does not exist.
	 */
	public function getAttribute( string $key, $default = null ) { // phpcs:ignore Universal.NamingConventions
		if ( ! $this->hasAttribute( $key ) ) {
			return $default;
		}

		$value = parent::getAttribute( $key, $default );

		if ( 'raw_amount' === $key ) {
			if ( is_float( $value ) ) {
				$sub_type = parent::getAttribute( 'sub_type' );
				$value    = 'flat' === $sub_type ? Float_Value::from_number( $value ) : new Percent_Value( $value );
			}
		}

		// Return the value directly if it's not a value object.
		if ( ! $value instanceof Value_Interface ) {
			return $value;
		}

		// When retrieving a value object, return the raw value.
		return $value instanceof Percent_Value
			? $value->get_as_percent()
			: $value->get();
	}

	/**
	 * Validates a property value based on a dedicated method.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return void
	 */
	protected function run_validation_method( string $key, $value ) {
		$validation_method = "validate_{$key}";
		if ( method_exists( $this, $validation_method ) ) {
			$this->$validation_method( $value );
		}
	}
}
