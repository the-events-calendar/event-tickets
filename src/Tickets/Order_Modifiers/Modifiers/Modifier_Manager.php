<?php
/**
 * Modifier Manager for handling operations and rendering related to Order Modifiers.
 *
 * This class serves as a context that interacts with different modifier strategies (such as Coupons or Booking Fees).
 * It handles the saving (insert/update) of modifiers and delegates rendering tasks to the appropriate strategy.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Order_Modifiers\Modifiers;

/**
 * Context class that interacts with the strategy.
 *
 * The Modifier_Manager class manages the insertion, updating, and validation
 * of order modifiers (such as Coupons and Booking Fees) by delegating
 * these operations to the strategy provided (e.g., Coupon strategy).
 *
 * @since TBD
 */
class Modifier_Manager {

	/**
	 * The modifier strategy being used (e.g., Coupon, Booking_Fee).
	 *
	 * @since TBD
	 *
	 * @var Modifier_Strategy_Interface
	 */
	protected Modifier_Strategy_Interface $strategy;

	/**
	 * Constructor to initialize the strategy.
	 *
	 * Sets the modifier strategy that will handle the insertion, updating,
	 * and validation of order modifiers.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Strategy_Interface $strategy The modifier strategy to use.
	 */
	public function __construct( Modifier_Strategy_Interface $strategy ) {
		$this->strategy = $strategy;
	}

	/**
	 * Saves a modifier (insert or update) based on the provided data.
	 *
	 * Validates the data before proceeding. If an ID is present in the data, the
	 * modifier will be updated; otherwise, a new modifier will be inserted.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to save the modifier.
	 *
	 * @return mixed The result of the insert or update operation, or an empty array if validation fails or no changes were made.
	 */
	public function save_modifier( array $data ): mixed {
		$data['modifier_type'] = $this->strategy->get_modifier_type();

		// Validate data before proceeding.
		if ( ! $this->strategy->validate_data( $data ) ) {
			// Optionally log the validation failure.
			// @todo redscar - decide how to handle this.
			error_log( 'Validation failed for ' . $this->strategy->get_modifier_type() );
			return [];
		}

		// Check if it's an update or an insert.
		if ( isset( $data['id'] ) && is_numeric( $data['id'] ) && (int) $data['id'] > 0 ) {
			return $this->strategy->update_modifier( $data );
		}

		return $this->strategy->insert_modifier( $data );
	}

	/**
	 * Renders the table for the current modifier strategy.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the table.
	 *
	 * @return void
	 */
	public function render_table( array $context ): void {
		$this->strategy->render_table( $context );
	}

	/**
	 * Renders the edit screen for the current modifier strategy.
	 *
	 * @since TBD
	 *
	 * @param array $context The context data for rendering the edit screen.
	 */
	public function render_edit_screen( array $context ) {
		$this->strategy->render_edit( $context );
	}

	/**
	 * Syncs the relationships between modifiers and posts.
	 *
	 * This method handles the synchronization of relationships between the provided
	 * modifier IDs and the new post IDs, delegating the actual update process to the strategy.
	 * It ensures that the correct relationships are either inserted or deleted based on the input.
	 *
	 * @since TBD
	 *
	 * @param array $modifier_ids The array of modifier IDs to sync.
	 * @param array $new_post_ids The array of new post IDs to associate with the modifiers.
	 *
	 * @return void
	 */
	public function sync_modifier_relationships( array $modifier_ids, array $new_post_ids ): void {
		$this->strategy->handle_relationship_update( $modifier_ids, $new_post_ids );
	}
}
