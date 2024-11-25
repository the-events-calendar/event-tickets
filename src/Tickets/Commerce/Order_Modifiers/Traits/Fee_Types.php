<?php
/**
 * Fee Types trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

use Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers;

/**
 * Trait Fee_Types
 *
 * @since TBD
 */
trait Fee_Types {

	/**
	 * The repository for interacting with the order modifiers table.
	 *
	 * @since TBD
	 *
	 * @var Order_Modifiers
	 */
	protected Order_Modifiers $modifiers_repository;

	/**
	 * Get the automatic fees.
	 *
	 * @since TBD
	 *
	 * @param object[] $all_fees The fees.
	 *
	 * @return array The automatic fees.
	 */
	protected function get_automatic_fees( array $all_fees ) {
		return array_filter(
			$all_fees,
			fn( $fee ) => empty( $fee->meta_value ) || $fee->meta_value === 'all'
		);
	}

	/**
	 * Get the selectable fees.
	 *
	 * @since TBD
	 *
	 * @param object[] $all_fees The fees.
	 *
	 * @return array The selectable fees.
	 */
	protected function get_selectable_fees( array $all_fees ) {
		return array_filter(
			$all_fees,
			fn( $fee ) => ! empty( $fee->meta_value ) && $fee->meta_value === 'per'
		);
	}

	/**
	 * Get all fees.
	 *
	 * @since TBD
	 *
	 * @return array The fees.
	 */
	protected function get_all_fees(): array {
		$available_fees = $this->modifiers_repository->find_by_modifier_type_and_meta(
			'fee_applied_to',
			[ 'per', 'all' ],
			'fee_applied_to',
			'all'
		);

		// If no fees were found, return an empty array.
		if ( null === $available_fees ) {
			return [];
		}

		// Convert the return value to an array keyed by the fee ID.
		return array_combine(
			wp_list_pluck( $available_fees, 'id' ),
			$available_fees
		);
	}

	/**
	 * Get the selected fees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int[] The selected fees.
	 */
	protected function get_selected_fees( int $ticket_id ): array {
		$ticket_fees   = [];
		$relationships = $this->relationships->find_by_post_id( $ticket_id );

		// If no relationships were found, return an empty array.
		if ( null === $relationships ) {
			return [];
		}

		// Convert the Fee relationships to Fee objects.
		foreach ( $relationships as $relationship ) {
			$ticket_fees[] = (int) $relationship->modifier_id;
		}

		return $ticket_fees;
	}

	/**
	 * Update the fees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ticket ID.
	 * @param int[] $fees      The fees to update.
	 *
	 * @return void
	 *
	 * @throws Exception If the fees are not selectable.
	 */
	protected function update_fees_for_ticket( $ticket_id, $fees ) {
		// Validate that the fees are actually selectable.
		$all_fees        = $this->get_all_fees();
		$selectable_fees = wp_list_pluck( $this->get_selectable_fees( $all_fees ), 'id', 'id' );
		$invalid_fees    = [];
		foreach ( $fees as $fee ) {
			if ( ! array_key_exists( $fee, $selectable_fees ) ) {
				$invalid_fees[] = $fee;
			}
		}

		if ( ! empty( $invalid_fees ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: The invalid fees. */
					__( 'The following fees are not selectable: %s', 'event-tickets' ),
					implode( ', ', $invalid_fees )
				),
				400
			);
		}

		// Ensure that the fees are integers.
		$fee_ids = array_map( 'absint', $fees );

		$this->manager->delete_relationships_by_post( $ticket_id );
		$this->manager->sync_modifier_relationships( $fee_ids, [ $ticket_id ] );
	}
}
