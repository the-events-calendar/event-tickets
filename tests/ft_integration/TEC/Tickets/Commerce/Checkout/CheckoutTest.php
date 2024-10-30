<?php
namespace TEC\Tickets\Commerce\Checkout;

use Closure;
use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class CheckoutTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use With_Uopz;
	use Series_Pass_Factory;

	/**
	 * Setup test preconditions.
	 *
	 * @before
	 */
	public function setup_preconditions(): void {
		// we should create and use a user to avoid 0 as user ID.
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
		$this->set_fn_return( 'wp_create_nonce', '123123' );
	}

	/**
	 * This is a helper method to replace IDs in a snapshot with placeholders.
	 *
	 * @param string $snapshot The snapshot to replace the IDs in.
	 * @param array $ids The IDs to replace.
	 *
	 * @return string
	 */
	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			$ids,
			array_fill( 0, count( $ids ), '{{ID}}' ),
			$snapshot
		);
	}

	/**
	 * Generates data for the checkout shortcode.
	 */
	public function checkout_data_provider(): Generator {
		yield 'single ticket from an event' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event 1',
						'start_date' => '2222-02-10 17:30:00',
						'duration'   => 5 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 1 );

				$html = tribe( Checkout_Shortcode::class )->get_html();
				$cart->clear_cart();

				return [ $html, [ $event_id, $ticket_id ] ];
			},
		];

		yield 'multiple ticket from an event' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event 1',
						'start_date' => '2222-02-10 17:30:00',
						'duration'   => 5 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id_a = $this->create_tc_ticket( $event_id, 10 );
				$ticket_id_b = $this->create_tc_ticket( $event_id, 20 );

				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id_a, 2 );
				$cart->add_ticket( $ticket_id_b, 1 );

				$html = tribe( Checkout_Shortcode::class )->get_html();
				$cart->clear_cart();

				return [ $html, [ $event_id, $ticket_id_a, $ticket_id_b ] ];
			},
		];

		yield 'ticket with series pass from an event' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					]
				);

				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'series'     => $series_id,
					]
				)->create()->ID;

				$ticket_id      = $this->create_tc_ticket( $event_id, 10 );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 20 )->ID;

				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 1 );
				$cart->add_ticket( $series_pass_id, 1 );

				$html = tribe( Checkout_Shortcode::class )->get_html();
				$cart->clear_cart();

				return [ $html, [ $event_id, $series_id, $ticket_id, $series_pass_id ] ];
			},
		];

		yield 'multiple ticket with multiple series pass from an event' => [
			function (): array {
				$series_id = static::factory()->post->create(
					[
						'post_type'  => Series_Post_Type::POSTTYPE,
						'post_title' => 'Test series',
					]
				);

				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test event',
						'status'     => 'publish',
						'start_date' => '2021-01-01 10:00:00',
						'end_date'   => '2021-01-01 12:00:00',
						'series'     => $series_id,
					]
				)->create()->ID;

				$ticket_id      = $this->create_tc_ticket( $event_id, 5 );
				$ticket_id_b    = $this->create_tc_ticket( $event_id, 10 );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 20 )->ID;

				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 3 );
				$cart->add_ticket( $ticket_id_b, 2 );
				$cart->add_ticket( $series_pass_id, 2 );

				$html = tribe( Checkout_Shortcode::class )->get_html();
				$cart->clear_cart();

				return [ $html, [ $event_id, $series_id, $ticket_id, $ticket_id_b, $series_pass_id ] ];
			},
		];
	}

	/**
	 * @test
	 *
	 * @dataProvider checkout_data_provider
	 * @covers \TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode::get_html
	 */
	public function test_ticketscommerce_checkout_template( Closure $fixture ): void {
		// Enqueue the assets now to avoid the snapshot containing the script tags.
		Checkout_Shortcode::enqueue_assets();

		[ $html, $tolerables ] = $fixture();

		$html = $this->placehold_post_ids( $html, $tolerables );
		// Replace TEC common path with ET common path to stabilize the snapshots.
		$html = str_replace( 'wp-content/plugins/the-events-calendar/common', 'wp-content/plugins/event-tickets/common', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
