<?php
namespace TEC\Tickets\Commerce\Checkout;

use Closure;
Use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class CheckoutTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;


	public function checkout_data_provider(): Generator {

		yield 'single ticket from an event' => [
			function(): array {
				$event = tribe_events()->set_args( [
					'title'      => 'Event 1',
					'start_date' => '2222-02-10 17:30:00',
					'duration'   => 5 * HOUR_IN_SECONDS,
				] )->create();

				$ticket_id = $this->create_tc_ticket( $event->ID, 10 );

				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 1 );

				$html = tribe( Checkout_Shortcode::class )->get_html();

				$cart->clear_cart();
				return [ $html, $event->ID, $ticket_id ];
			}
		];
	}

	/**
	 * @test
	 *
	 * @dataProvider checkout_data_provider
	 * it should render the checkout shortcode
	 */
	public function test_ticketscommerce_checkout_template( Closure $fixture ): void {
		[ $html, $event_id, $ticket_id ] = $fixture();
		$this->assertMatchesHtmlSnapshot( $html );
	}

}