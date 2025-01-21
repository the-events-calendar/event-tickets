<?php

namespace TEC\Tickets\Commerce\Shortcodes;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Success;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Module;

class Success_Shortcode_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;
	use Order_Maker;
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable post as ticket type.
		add_filter(
			'tribe_tickets_post_types',
			function () {
				return [ 'post' ];
			} 
		);

		// Enable Tickets Commerce as the default provider.
		add_filter(
			'tribe_tickets_get_modules',
			function ( $modules ) {
				$modules[ Module::class ] = Module::class;
				return $modules;
			} 
		);

		tribe_update_option( 'tickets-commerce-currency-code', 'USD' );
		tribe_update_option( 'tickets-commerce-currency-decimal-separator', '.' );
		tribe_update_option( 'tickets-commerce-currency-thousands-separator', ',' );
		tribe_update_option( 'tickets-commerce-currency-number-of-decimals', '2' );
		tribe_update_option( 'tickets-commerce-currency-position', 'prefix' );
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * @test
	 */
	public function test_get_html() {
		$fake_gateway_order_id = '2MJ687450D400282D';
		$this->create_order_with_ticket_and_attendee( $fake_gateway_order_id );

		// Set the order ID query arg to the fake gateway order ID.
		$_GET[ Success::$order_id_query_arg ] = $fake_gateway_order_id;

		// Create an instance of the Success_Shortcode class
		$shortcode = new Success_Shortcode();

		// Call the get_html method
		$html = $shortcode->get_html();

		// Add more assertions for the expected HTML content if needed
		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * Helper method to create an order for a post.
	 *
	 * @since 5.9.1
	 *
	 * @param string $gateway_order_id
	 *
	 * @return void
	 */
	public function create_order_with_ticket_and_attendee( $gateway_order_id ) {
		// Create a post and a ticket for it.
		$post_id                    = $this->factory()->post->create();
		$tickets_commerce_ticket_id = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'ticket_name'        => 'Test TC ticket',
				'ticket_description' => 'Test TC ticket description',
			] 
		);

		// Create an order for one ticket.
		$order = $this->create_order(
			[ $tickets_commerce_ticket_id => 1 ],
			[
				'purchaser_email' => 'purchaser_email@test.com',
			] 
		);

		// Update order info for normalization.
		update_post_meta( $order->ID, Order::$gateway_order_id_meta_key, $gateway_order_id );
		$fake_date = '1974-11-20 21:57:56';
		wp_update_post(
			[
				'ID'                => $order->ID,
				'post_date'         => $fake_date,
				'post_date_gmt'     => $fake_date,
				'post_modified'     => $fake_date,
				'post_modified_gmt' => $fake_date,
			] 
		);

		$attendee_id = $this->create_attendee_for_ticket(
			$tickets_commerce_ticket_id,
			$post_id,
			[
				'order_id' => $order->ID,
			] 
		);
	}
}
