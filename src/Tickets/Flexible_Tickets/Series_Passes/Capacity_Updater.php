<?php
/**
 * Handles the update of a Series Pass capacity mode and value.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Exception;
use TEC\Tickets\Flexible_Tickets\Exceptions\Custom_Tables_Exception;
use TEC\Tickets\Flexible_Tickets\Exceptions\Invalid_Data_Exception;
use TEC\Tickets\Flexible_Tickets\Models\Capacity;
use TEC\Tickets\Flexible_Tickets\Models\Capacity_Relationship;
use TEC\Tickets\Flexible_Tickets\Repositories\Capacities;
use TEC\Tickets\Flexible_Tickets\Repositories\Capacities_Relationships;
use Tribe__Tickets__Global_Stock as Global_Stock;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities as Capacities_Table;

/**
 * Class Capacity_Updater.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Capacity_Updater {
	/**
	 * The capacities repository.
	 *
	 * @since TBD
	 *
	 * @var Capacities
	 */
	private Capacities $capacities;
	/**
	 * The capacities relationships repository.
	 *
	 * @since TBD
	 *
	 * @var Capacities_Relationships
	 */
	private Capacities_Relationships $capacities_relationships;

	/**
	 * Capacity_Updater constructor.
	 *
	 * since TBD
	 *
	 * @param Capacities               $capacities               The capacities repository.
	 * @param Capacities_Relationships $capacities_relationships The capacities relationships repository.
	 */
	public function __construct( Capacities $capacities, Capacities_Relationships $capacities_relationships ) {
		$this->capacities               = $capacities;
		$this->capacities_relationships = $capacities_relationships;
	}

	/**
	 * Updates the capacity mode and value for a Series Pass.
	 *
	 * @since TBD
	 *
	 * @param int    $series_id The series post ID.
	 * @param int    $ticket_id The ticket post ID.
	 * @param string $new_mode  The new capacity mode.
	 * @param array  $data      The data to update the capacity with.
	 *
	 * @return Capacity The updated capacity.
	 *
	 * @throws Exception If the required data is missing or the capacity update fails.
	 */
	public function update( int $series_id, int $ticket_id, string $new_mode, array $data ): Capacity {
		$capacities               = $this->capacities;
		$capacities_relationships = $this->capacities_relationships;


		$capacity_relationship = $capacities_relationships->find_by_object_id( $ticket_id );

		if ( $capacity_relationship === null ) {
			throw new Custom_Tables_Exception(
				'The capacity relationship for the Series Pass is missing.',
				Custom_Tables_Exception::CAPACITY_RELATIONSHIP_MISSING
			);
		}

		$capacity = $capacities->find_by_id( $capacity_relationship->capacity_id );

		if ( $capacity === null ) {
			throw new Custom_Tables_Exception(
				'The capacity for the Series Pass is missing.',
				Custom_Tables_Exception::CAPACITY_MISSING
			);
		}

		$global_capacity_relationship = $capacities_relationships->find_by_object_id( $series_id );

		$current_mode_is_local  = in_array( $capacity->mode, [
			Capacities_Table::MODE_UNLIMITED,
			Global_Stock::OWN_STOCK_MODE
		], true );
		$new_mode_is_local      = in_array( $new_mode, [
			Capacities_Table::MODE_UNLIMITED,
			Global_Stock::OWN_STOCK_MODE
		], true );
		$new_mode_is_unlimited  = $new_mode === Capacities_Table::MODE_UNLIMITED;
		$new_mode_is_capped     = $new_mode === Global_Stock::CAPPED_STOCK_MODE;
		$current_mode_is_global = $capacity->mode === Global_Stock::GLOBAL_STOCK_MODE;
		$current_mode_is_capped = $capacity->mode === Global_Stock::CAPPED_STOCK_MODE;
		$new_mode_is_global     = $new_mode === Global_Stock::GLOBAL_STOCK_MODE;

		// If the new mode is not unlimited, we need a capacity value.
		if ( ( ! $new_mode_is_unlimited ) && ! isset( $data['capacity'] ) ) {
			throw new Invalid_Data_Exception( 'The capacity is missing.', Invalid_Data_Exception::CAPACITY_VALUE_MISSING );
		}

		// If the new mode is global or capped, we need a global capacity value.
		if ( ! isset( $data['event_capacity'] )
		     && in_array( $new_mode, [
				Global_Stock::GLOBAL_STOCK_MODE,
				Global_Stock::CAPPED_STOCK_MODE
			], true ) ) {
			throw new Invalid_Data_Exception( 'The global capacity is missing.', Invalid_Data_Exception::EVENT_CAPACITY_VALUE_MISSING );
		}

		if (
			( $current_mode_is_local && $new_mode_is_local )
			|| ( $current_mode_is_capped && $new_mode_is_capped )
		) {
			$capacity->mode          = $new_mode;
			$capacity->max_value     = $new_mode_is_unlimited ?
				Capacities_Table::VALUE_UNLIMITED : (int) $data['capacity'];
			$capacity->current_value = $new_mode_is_unlimited ?
				Capacities_Table::VALUE_UNLIMITED
				: min( $data['capacity'], $capacity->current_value );
			$capacity->save();

			return $capacity;
		}

		if ( ! $new_mode_is_local ) {
			// Ensure the global capacity exists.
			if ( $global_capacity_relationship === null ) {
				$global_capacity              = Capacity::create_global( $data['event_capacity'] );
				$global_capacity_relationship = Capacity_Relationship::create( [
					'object_id'          => $series_id,
					'capacity_id'        => $global_capacity->id,
					'parent_capacity_id' => 0,
				] );
			} else {
				$global_capacity = $capacities->find_by_id( $global_capacity_relationship->capacity_id );
			}

			// Update the global capacity.
			$global_capacity->max_value     = $data['event_capacity'];
			$global_capacity->current_value = min( $data['event_capacity'], $global_capacity->current_value );
			$global_capacity->save();

			if ( $current_mode_is_global && $new_mode_is_capped ) {
				// Global to capped.
				$capacity                                  = Capacity::create_capped( $data['capacity'] );
				$capacity_relationship->capacity_id        = $capacity->id;
				$capacity_relationship->parent_capacity_id = $global_capacity->id;
				$capacity_relationship->save();

				return $capacity;
			}

			if ( $current_mode_is_capped && $new_mode_is_global ) {
				$capacity->delete();
				$capacity_relationship->parent_capacity_id = 0;
				$capacity_relationship->capacity_id        = $global_capacity->id;
				$capacity_relationship->save();

				return $capacity;
			}
		}

		throw new Invalid_Data_Exception( 'The capacity mode is invalid.', Invalid_Data_Exception::CAPACITY_MODE_INVALID );
	}
}