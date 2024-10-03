<?php

namespace TEC\Tickets\Order_Modifiers\Checkout;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Order_Modifiers\Controller;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers;

/**
 * Class Fees
 *
 * Handles fees logic in the checkout process.
 *
 * @since TBD
 */
class Fees {

	protected string $modifier_type = 'fee';

	protected $modifier_strategy;

	protected Modifier_Manager $manager;

	protected Order_Modifiers $order_modifiers_repository;

	protected Order_Modifier_Relationship $order_modifiers_relationship_repository;

	/**
	 * Subtotal used for fee calculations.
	 *
	 * @since TBD
	 * @var float
	 */
	protected float $subtotal = 0.0;

	public function __construct() {
		$this->modifier_strategy                       = tribe( Controller::class )->get_modifier( $this->modifier_type );
		$this->manager                                 = new Modifier_Manager( $this->modifier_strategy );
		$this->order_modifiers_repository              = new Order_Modifiers();
		$this->order_modifiers_relationship_repository = new Order_Modifier_Relationship();
	}

	/**
	 * Registers the necessary hooks for adding fees to the checkout process.
	 *
	 * @since TBD
	 */
	public function register(): void {
		// Hook for calculating total values, setting subtotal, and modifying the total value.
		add_filter( 'tec_tickets_commerce_checkout_shortcode_total_value', [ $this, 'calculate_fees' ], 10, 3 );

		// Hook for displaying fees in the checkout.
		add_action( 'tec_tickets_commerce_checkout_cart_before_footer_quantity', [ $this, 'display_fee_section' ], 30, 3 );
		add_action( 'tec_tickets_commerce_create_from_cart_items', [ $this, 'add_fees_to_cart' ], 3, 3 );
		add_action( 'tec_commerce_get_unit_data_fee', [ $this, 'add_fee_unit_data' ], 2, 3 );
	}

	/**
	 * Calculates the fees and modifies the total value in the checkout process.
	 *
	 * @since TBD
	 *
	 * @param array $values The existing values being passed through the filter.
	 * @param array $items The items in the cart.
	 * @param Value $subtotal The subtotal of the cart items.
	 *
	 * @return array The updated total values, including the fees.
	 */
	public function calculate_fees( $values, $items, $subtotal ): array {
		// Store the subtotal as a class property for later use.
		$this->subtotal = Value::create()->total( $subtotal )->get_float();

		// Fetch the combined fees for the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		// Calculate the total fees based on the subtotal.
		$total_fees = $this->manager->calculate_total_fees( $this->subtotal, $combined_fees );

		// Add the calculated fees to the total value.
		$values[] = Value::create( $total_fees );

		return $values;
	}

	/**
	 * Displays the fee section in the checkout.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post         $post The current post object.
	 * @param array            $items The items in the cart.
	 * @param \Tribe__Template $template The template object for rendering.
	 */
	public function display_fee_section( $post, $items, $template ): void {
		// Fetch the combined fees for the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		// Use the stored subtotal for fee calculations.
		$total_fees = $this->manager->calculate_total_fees( $this->subtotal, $combined_fees );

		// Pass the fees to the template for display.
		$template->template(
			'checkout/order-modifiers/fees',
			[
				'active_fees' => $combined_fees,
				'total_fees'  => $total_fees,
			]
		);
	}

	/**
	 * Retrieves and combines the fees for the given cart items.
	 *
	 * @since TBD
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array The combined fees.
	 */
	protected function get_combined_fees_for_items( array $items ): array {
		$ticket_ids = array_map(
			function ( $item ) {
				return $item['ticket_id'];
			},
			$items
		);

		// Fetch related ticket fees and automatic fees.
		$related_ticket_fees = $this->order_modifiers_repository->find_relationship_by_post_ids( $ticket_ids, $this->modifier_type );
		$automatic_fees      = $this->order_modifiers_repository->find_by_modifier_type_and_meta(
			$this->modifier_type,
			'fee_applied_to',
			[ 'per', 'all' ],
			'fee_applied_to',
			'all'
		);

		// Combine the fees and remove duplicates.
		return $this->extract_and_combine_fees( $related_ticket_fees, $automatic_fees );
	}

	/**
	 * Extracts and combines fees from related ticket fees and automatic fees, removing duplicates.
	 *
	 * @since TBD
	 *
	 * @param array $related_ticket_fees The related ticket fees.
	 * @param array $automatic_fees The automatic fees.
	 *
	 * @return array The combined array of fees.
	 */
	protected function extract_and_combine_fees( array $related_ticket_fees, array $automatic_fees ): array {
		$all_fees = array_merge( $related_ticket_fees, $automatic_fees );

		$unique_fees = [];
		foreach ( $all_fees as $fee ) {
			$id = $fee->id;

			if ( ! isset( $unique_fees[ $id ] ) ) {
				$unique_fees[ $id ] = [
					'id'               => $id,
					'fee_amount_cents' => $fee->fee_amount_cents,
					'display_name'     => $fee->display_name,
					'sub_type'         => $fee->sub_type,
				];

				// Use the stored subtotal in the fee calculation.
				$unique_fees[ $id ]['fee_amount'] = $this->manager->apply_fees_to_item( $this->subtotal, $unique_fees[ $id ] );
			}
		}

		return array_values( $unique_fees );
	}

	public function add_fees_to_cart( $items, $gateway, $purchaser ) {
		/**
		 * Loop over items in $items
		 * If type = `ticket` then see if there are anyway fees associated with it
		 * Add fee to items array
		 */
		$items[] = [
			'price'     => 1,
			'sub_total' => 1,
			'type'      => 'fee',

		];

		return $items;
	}

	public function add_fee_unit_data( $item, $order ) {
		return [
			'name'        => 'Get name from id?',
			'unit_amount' => [
				'value'         => '1',
				'currency_code' => 'USD',
			],
			'quantity'    => '1',
			'item_total'  => [
				'value'         => '1',
				'currency_code' => 'USD',
			],
			'sku'         => 'ABC123',
		];
	}
}
