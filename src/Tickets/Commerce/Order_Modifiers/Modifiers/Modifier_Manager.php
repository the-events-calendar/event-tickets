<?php
/**
 * Modifier Manager for handling operations and rendering related to Order Modifiers.
 *
 * This class serves as a context that interacts with different modifier strategies (such as Coupons or Booking Fees).
 * It handles the saving (insert/update) of modifiers and delegates rendering tasks to the appropriate strategy.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use RuntimeException;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Fee_Table;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Values\Percent_Value;

/**
 * Context class that interacts with the strategy.
 *
 * The Modifier_Manager class manages the insertion, updating, and validation
 * of order modifiers (such as Coupons and Booking Fees) by delegating
 * these operations to the strategy provided (e.g., Coupon strategy).
 *
 * @since 5.18.0
 */
class Modifier_Manager {

	/**
	 * The modifier strategy being used (e.g., Coupon, Booking_Fee).
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param array $data The data to save the modifier.
	 *
	 * @return Model The result of the insert or update operation, or an empty array if validation fails or no changes
	 *     were made.
	 */
	public function save_modifier( array $data ): Model {
		$data['modifier_type'] = $this->strategy->get_modifier_type();

		$this->strategy->validate_data( $data );

		// Check if it's an update or an insert.
		if ( isset( $data['id'] ) && is_numeric( $data['id'] ) && (int) $data['id'] > 0 ) {
			return $this->strategy->update_modifier( $data );
		}

		return $this->strategy->insert_modifier( $data );
	}

	/**
	 * Renders the table for the current modifier strategy.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param array $items The items in the cart (tickets).
	 *
	 * @return Value The total amount after fees are applied.
	 */
	public function calculate_total_fees( array $items ): Value {
		return Legacy_Value_Factory::to_legacy_value(
			( new Precision_Value( 0.0 ) )->sum( ...$this->combine_total_fees( $items ) )
		);
	}

	/**
	 * Get the combined fees for an array of items.
	 *
	 * @since 5.21.0
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return Precision_Value[] The combined fees for the items.
	 */
	public function combine_total_fees( array $items ): array {
		return array_map(
			static fn( $item ) => $item['fee_amount'],
			$items
		);
	}

	/**
	 * Apply percentage and flat fees to a single item.
	 *
	 * This method applies all relevant percentage and flat fees to the provided base price.
	 *
	 * @since 5.18.0
	 *
	 * @param Value $base_price The base price of the item.
	 * @param array $item       The fee data for a single item (percentage or flat).
	 *
	 * @return Precision_Value The total price after fees are applied.
	 */
	public function apply_fees_to_item( Value $base_price, array $item ): Precision_Value {
		$raw_base_price = $base_price->get_integer();
		$zero_value     = new Precision_Value( 0.0 );

		// Early bail if base price is zero or negative, return zero value.
		if ( $raw_base_price <= 0 ) {
			return $zero_value;
		}

		$raw_amount = $item['raw_amount'] ?? 0;
		$sub_type   = $item['sub_type'] ?? '';

		// Apply the fee based on the sub-type.
		switch ( $sub_type ) {
			case 'percent':
				$fee       = new Percent_Value( $raw_amount );
				$fee_value = $base_price->get_float() * $fee->get_as_decimal();

				return new Precision_Value( $fee_value );

			case 'flat':
				return new Precision_Value( $raw_amount );

			default:
				return $zero_value;
		}
	}

	/**
	 * Get the table class for the current strategy.
	 *
	 * This method returns the appropriate table class based on the current strategy.
	 *
	 * @since 5.18.1
	 *
	 * @return Fee_Table|Coupon_Table The table class for the current strategy.
	 *
	 * @throws RuntimeException If the modifier type is invalid.
	 */
	public function get_table_class() {
		$type = $this->strategy->get_modifier_type();
		switch ( $type ) {
			case 'fee':
				return tribe( Fee_Table::class );

			case 'coupon':
				return tribe( Coupon_Table::class );
		}

		throw new RuntimeException( 'Invalid modifier type.' );
	}
}
