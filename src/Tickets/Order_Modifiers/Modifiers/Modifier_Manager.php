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

use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Utils\Value;

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
	 * @return Model The result of the insert or update operation, or an empty array if validation fails or no changes
	 *     were made.
	 */
	public function save_modifier( array $data ): Model {
		$data['modifier_type'] = $this->strategy->get_modifier_type();

		// Validate data before proceeding.
		if ( ! $this->strategy->validate_data( $data ) ) {
			// Optionally log the validation failure.
			// @todo redscar - decide how to handle this.
			error_log( 'Validation failed for ' . $this->strategy->get_modifier_type() );
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

	/**
	 * Deletes all relationships associated with a given modifier ID.
	 *
	 * This method allows the manager to clear relationships based on the modifier ID.
	 *
	 * @since TBD
	 *
	 * @param int $modifier_id The ID of the modifier for which relationships should be deleted.
	 *
	 * @return void
	 */
	public function delete_relationships_by_modifier( int $modifier_id ): void {
		$this->strategy->delete_relationship_by_modifier( $modifier_id );
	}

	/**
	 * Deletes all relationships associated with a given post ID.
	 *
	 * This method allows the manager to clear relationships based on the post ID.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post for which relationships should be deleted.
	 *
	 * @return void
	 */
	public function delete_relationships_by_post( int $post_id ): void {
		$this->strategy->delete_relationship_by_post( $post_id );
	}

	/**
	 * Calculates the total fees for the provided tickets.
	 *
	 * This method loops through the items (tickets) in the cart and calculates
	 * the total fees (both percentage and flat) based on the associated modifiers.
	 *
	 * @since TBD
	 *
	 * @param Value $base_price The base price of the item.
	 * @param array $items The items in the cart (tickets).
	 *
	 * @return Value The total amount after fees are applied.
	 */
	public function calculate_total_fees( Value $base_price, array $items ): Value {
		$total_fees = Value::create( 0 );

		foreach ( $items as $item ) {
			$total_fees = Value::create()->total( [ $total_fees, $this->apply_fees_to_item( $base_price, $item ) ] );
		}

		return $total_fees;
	}

	/**
	 * Apply percentage and flat fees to a single item.
	 *
	 * This method applies all relevant percentage and flat fees to the provided base price.
	 *
	 * @since TBD
	 *
	 * @param Value $base_price The base price of the item.
	 * @param array $item       The fee data for a single item (percentage or flat).
	 *
	 * @return Value The total price after fees are applied.
	 */
	public function apply_fees_to_item( Value $base_price, array $item ): Value {
		$base_price_in_cents = $base_price->get_integer();

		// Early bail if base price is zero or negative, return zero value.
		if ( $base_price_in_cents <= 0 ) {
			return Value::create( 0 );
		}

		// Apply percentage fee if applicable and bail early.
		if ( isset( $item['sub_type'] ) && $item['sub_type'] === 'percent' ) {
			$percentage_fee = $item['fee_amount_cents'] / $base_price_in_cents;

			return Value::create( $this->strategy->convert_from_cents( $percentage_fee ) );
		}

		// Apply flat fee if applicable and bail early.
		if ( isset( $item['sub_type'] ) && $item['sub_type'] === 'flat' ) {
			return Value::create( $item['fee_amount_cents'] );
		}

		// Return zero if no valid fee type is found.
		return Value::create( 0 );
	}
}
