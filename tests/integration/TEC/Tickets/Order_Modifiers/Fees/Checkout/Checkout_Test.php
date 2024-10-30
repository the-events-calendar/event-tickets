<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Integration\Order_Modifiers\Fees\Checkout;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Unmanaged_Cart;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Order_Modifiers\Models\Fee;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers_Meta as Repository;
use TEC\Tickets\Order_Modifiers\Values\Float_Value;
use Tribe\Tickets\Test\Commerce\Ticket_Maker;

class Checkout_Test extends WPTestCase {

	use Ticket_Maker;

	/** @var int */
	protected int $ticket_id;

	/** @var int */
	protected int $base_price = 15;

	/** @var ?Unmanaged_Cart */
	protected ?Unmanaged_Cart $cart;

	/** @var ?Repository */
	protected ?Repository $repository;

	/**
	 * @before
	 */
	public function set_up() {
		$post_id         = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->ticket_id = $this->create_ticket( Commerce::class, $post_id, $this->base_price );
		$this->cart      = $this->cart ?? new Unmanaged_Cart();
		$this->cart->clear();

		$this->repository = $this->repository ?? new Repository();
	}

	/**
	 * @test
	 */
	public function ticket_without_fees_in_checkout() {
		$this->markTestSkipped( 'This test does not fully function yet.' );

		// Create the fee and set the application.
		$fee = $this->create_fee( [ 'display_name' => __METHOD__ ] );
		$this->set_fee_application( $fee, 'per' );

		// Step 2: Create a cart with the tickets.
		$quantity = 10;
		$cart     = $this->get_cart_with_tickets( $quantity );

		// Step 3: Get the cart total and subtotal.
		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		// Clear the cart for the next test.
		$cart->clear_cart();

		// Step 4: Verify that the cart total and subtotal are correct.
		$this->assertEquals( $this->base_price * $quantity, $cart_subtotal );
		$this->assertEquals( $this->base_price * $quantity, $cart_total );
	}

	/**
	 * @test
	 */
	public function ticket_with_fees_in_checkout() {
		// Create the fee and set the application.
		$fee = $this->create_fee( [ 'display_name' => __METHOD__ ] );
		$this->set_fee_application( $fee, 'all' );

		// Step 2: Create a cart with the tickets.
		$quantity = 2;
		$cart     = $this->get_cart_with_tickets( $quantity );

		// Make sure we have the number of items we expect in the cart.
		$items = $cart->get_items_in_cart();
		$count = 0;
		foreach ( $items as $item ) {
			$count += $item['quantity'];
		}

		$this->assertEquals( $quantity, $count );

		// Step 3: Get the cart total and subtotal.
		$cart_total    = $cart->get_cart_total();
		$cart_subtotal = $cart->get_cart_subtotal();

		// Step 4: Verify that the cart total and subtotal are correct.
		$this->assertEquals( $this->base_price * $quantity, $cart_subtotal );
		$this->assertEquals( ( $this->base_price + 5 ) * $quantity, $cart_total );
	}

	/**
	 * Get a cart with the tickets.
	 *
	 * @param ?int $quantity The quantity of tickets to add to the cart.
	 *
	 * @return Cart
	 */
	protected function get_cart_with_tickets( ?int $quantity = null ) {
		$cart     = new Cart();
		$quantity = $quantity ?? 1;
		$cart->add_ticket( $this->ticket_id, $quantity );

		return $cart;
	}

	/**
	 * Create a fee with the provided arguments.
	 *
	 * @param array $args The arguments to use when creating the fee.
	 *
	 * @return Fee The created fee.
	 */
	protected function create_fee( array $args = [] ): Fee {
		$args = array_merge(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => Float_Value::from_number( 5 ),
				'slug'         => 'test-fee',
				'display_name' => 'test fee',
				'status'       => 'active',
				'start_time'   => null,
				'end_time'     => null,
			],
			$args
		);

		return Fee::create( $args );
	}

	/**
	 * Set the fee application to the provided value.
	 *
	 * @param Fee   $fee        The fee to set the application for.
	 * @param mixed $applied_to The value to set the fee application to.
	 */
	protected function set_fee_application( Fee $fee, $applied_to ) {
		$this->repository->upsert_meta(
			new Order_Modifier_Meta(
				[
					'order_modifier_id' => $fee->id,
					'meta_key'          => 'fee_applied_to',
					'meta_value'        => $applied_to,
					'priority'          => 0,
				]
			)
		);
	}
}
