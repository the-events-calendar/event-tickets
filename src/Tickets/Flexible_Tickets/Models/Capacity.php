<?php
/**
 * The CRUD model for the Capacity.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use Exception;
use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Contracts\ModelFromQueryBuilderObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities as Table;
use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Capacity_DTO;
use TEC\Tickets\Flexible_Tickets\Repositories\Capacities;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Capacity.
 *
 * @since     TBD
 *
 * @package   TEC\Tickets\Flexible_Tickets\Models;
 *
 * @property int    $id            The capacity ID.
 * @property int    $max_value     The maximum capacity value.
 * @property int    $current_value The current capacity value.
 * @property string $mode          The capacity mode.
 * @proeperty string $name          The capacity name.
 */
class Capacity extends Model implements ModelCrud, ModelFromQueryBuilderObject {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'            => 'int',
		'max_value'     => 'int',
		'current_value' => 'int',
		'mode'          => 'string',
		'name'          => 'string',
		'description'   => 'string',
	];

	/**
	 * Finds a capacity by its ID.
	 *
	 * @since TBD
	 *
	 * @param $id
	 *
	 * @return \TEC\Common\StellarWP\Models\Contracts\Model|void
	 */
	public static function find( $id ) {
		return tribe( Capacities::class )->find_by_id( $id );
	}

	/**
	 * Creates and saves to database a new Capacity instance.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attributes The attributes to set on the model.
	 *
	 * @return Capacity
	 */
	public static function create( array $attributes ): Capacity {
		$model = new static( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since TBD
	 *
	 * @return Capacity The saved model.
	 *
	 * @throws Exception If the model is not valid.
	 */
	public function save(): Capacity {
		$this->id = tribe( Capacities::class )->insert( $this )->id;

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
		return tribe( Capacities::class )->delete( $this );
	}

	/**
	 * Returns a query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder for the model.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Capacities::class )->query();
	}

	/**
	 * Creates and saves to database a new unlimited Capacity instance.
	 *
	 * @since TBD
	 *
	 * @return Capacity The created unlimited Capacity model.
	 */
	public static function create_unlimited(): Capacity {
		$capacity = new static( [
			'max_value'     => Table::VALUE_UNLIMITED,
			'current_value' => Table::VALUE_UNLIMITED,
			'mode'          => Table::MODE_UNLIMITED,
			'name'          => '',
			'description'   => '',
		] );

		$capacity->save();

		return $capacity;
	}

	/**
	 * Creates and saves to database a new global Capacity instance.
	 *
	 * @since TBD
	 *
	 * @param int $capacity The capacity value.
	 *
	 * @return Capacity The created global Capacity model.
	 */
	public static function create_global( int $capacity ): Capacity {
		$model = new static( [
			'max_value'     => $capacity,
			'current_value' => $capacity,
			'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			'name'          => '',
			'description'   => '',
		] );

		$model->save();

		return $model;
	}

	/**
	 * Creates and saves to database a new capped Capacity instance.
	 *
	 * @since TBD
	 *
	 * @param int $capacity The capacity value.
	 *
	 * @return Capacity The created capped Capacity model.
	 */
	public static function create_capped( int $capacity ): Capacity {
		$model = new static( [
			'max_value'     => $capacity,
			'current_value' => $capacity,
			'mode'          => Global_Stock::CAPPED_STOCK_MODE,
			'name'          => '',
			'description'   => '',
		] );

		$model->save();

		return $model;
	}

	/**
	 * Creates and saves to database a new own Capacity instance.
	 *
	 * @since TBD
	 *
	 * @param int $capacity The capacity value.
	 *
	 * @return Capacity The created own Capacity model.
	 */
	public static function create_own( int $capacity ): Capacity {
		$model = new static( [
			'max_value'     => $capacity,
			'current_value' => $capacity,
			'mode'          => Global_Stock::OWN_STOCK_MODE,
			'name'          => '',
			'description'   => '',
		] );

		$model->save();

		return $model;
	}

	/**
	 * Creates a model from a DTO object.
	 *
	 * @since TBD
	 *
	 * @param object $object The query data object.
	 *
	 * @return Capacity The model built from the data.
	 */
	public static function fromQueryBuilderObject( $object ): Capacity {
		return Capacity_DTO::fromObject( $object )->toModel();
	}

	public function update_to( string $mode, array $data = [] ): Capacity {
		// Going from a own capacity type to a global one requires making sure the global capacity exists.
		$mode_is_global       = in_array( $mode, [
			Global_Stock::GLOBAL_STOCK_MODE,
			Global_Stock::CAPPED_STOCK_MODE
		], true );
		$current_mod_is_local = in_array( $this->mode, [ Global_Stock::OWN_STOCK_MODE, Table::MODE_UNLIMITED ], true );
		$from_own_to_global   = $current_mod_is_local && $mode_is_global;
	}
}