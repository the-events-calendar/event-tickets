<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Attendees as Attendees;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe__Tickets__Tickets as Tickets;

class AttendeesTest extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use Attendee_Maker;
	use RSVP_Ticket_Maker;
	use Series_Pass_Factory;
	use With_Tickets_Commerce;

	private function get_attendee_data( array $attendees ): array {
		return array_reduce( $attendees, function ( array $carry, array $attendee ): array {
			foreach (
				[
					'ID',
					'ticket_id',
					'purchaser_name',
					'purchaser_email',
					'ticket_name',
					'holder_name',
					'security_code'
				] as $key
			) {
				if ( ! isset( $attendee[ $key ] ) ) {
					continue;
				}

				$value = $attendee[ $key ];

				if ( empty( $value ) ) {
					continue;
				}

				$carry[ esc_html( $value ) ] = strtoupper( $key );
				$carry[ $value ] = $key = strtoupper( $key );
			}

			return $carry;
		}, [] );
	}

	public function display_provider(): Generator {
		yield 'single event without attendees' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			}
		];

		yield 'single event with Single Ticket attendees' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );
				$order = $this->create_order( [ $ticket_id => 3 ] );

				return [ $event_id, [ $event_id, $ticket_id, $order->ID ] ];
			}
		];

		yield 'single event with RSVP attendees' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_rsvp_ticket( $event_id );

				$this->create_many_attendees_for_ticket( 3, $ticket_id, $event_id );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			}
		];

		yield 'single event with Single Tickets and RSVP Attendees' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );
				$order = $this->create_order( [ $ticket_id => 3 ] );
				$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
				$this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $event_id );

				return [ $event_id, [ $event_id, $ticket_id, $rsvp_ticket_id, $order->ID ] ];
			}
		];

		yield 'single event in series with Single Tickets, RSVPs and Series Passes' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
					'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				] )->create()->ID;

				$ticket_1_id = $this->create_tc_ticket( $event_id );
				$ticket_2_id = $this->create_tc_ticket( $event_id );

				// Sort the tickets "manually".
				wp_update_post( [ 'ID' => $ticket_1_id, 'menu_order' => 1 ] );
				wp_update_post( [ 'ID' => $ticket_2_id, 'menu_order' => 0 ] );

				$order_1 = $this->create_order( [ $ticket_1_id => 3 ] );
				$order_2 = $this->create_order( [ $ticket_2_id => 2 ] );

				$rsvp_1_ticket_id = $this->create_rsvp_ticket( $event_id );
				$rsvp_2_ticket_id = $this->create_rsvp_ticket( $event_id );

				// Sort the tickets "manually".
				wp_update_post( [ 'ID' => $rsvp_1_ticket_id, 'menu_order' => 3 ] );
				wp_update_post( [ 'ID' => $rsvp_2_ticket_id, 'menu_order' => 2 ] );

				$this->create_many_attendees_for_ticket( 3, $rsvp_1_ticket_id, $event_id );
				$this->create_many_attendees_for_ticket( 3, $rsvp_2_ticket_id, $event_id );

				$series_id = tec_series()->where( 'event_post_id', $event_id )->first_id();
				$series_pass_1_id = $this->create_tc_series_pass( $series_id )->ID;
				$series_pass_2_id = $this->create_tc_series_pass( $series_id )->ID;

				// Sort the tickets "manually".
				wp_update_post( [ 'ID' => $series_pass_1_id, 'menu_order' => 5 ] );
				wp_update_post( [ 'ID' => $series_pass_2_id, 'menu_order' => 4 ] );

				$order_3 = $this->create_order( [ $series_pass_1_id => 3 ] );
				$order_4 = $this->create_order( [ $series_pass_2_id => 3 ] );

				return [
					$event_id,
					[
						$event_id,
						...Occurrence::where( 'post_id', '=', $event_id )->pluck( 'provisional_id' ),
						$ticket_1_id,
						$ticket_2_id,
						$rsvp_1_ticket_id,
						$rsvp_2_ticket_id,
						$series_pass_1_id,
						$series_pass_2_id,
						$series_id,
						$order_1->ID,
						$order_2->ID,
						$order_3->ID,
						$order_4->ID,
					]
				];
			}
		];
	}

	/**
	 * @dataProvider display_provider
	 */
	public function test_display( Closure $fixture ): void {
		// The global hook suffix is used to set the table static cache, randomize it to avoid collisions with other tests.
		$GLOBALS['hook_suffix'] = uniqid( 'tribe_events_page_tickets-attendees', true );
		// Ensure we're using a user that can check-in Attendees and manage the posts.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Filter the insertion of the Attendees post to import an order by `post_title` and stabilize the snapshot.
		add_filter( 'wp_insert_post_data', function ( $data ) {
			static $k = 1;
			if ( str_ends_with( $data['post_type'], '_attendee' ) || str_ends_with( $data['post_type'], '_attendees' ) ) {
				$data['post_title'] = 'Test Attendee ' . str_pad( $k ++, 3, '0', STR_PAD_LEFT );
			}

			return $data;
		}, PHP_INT_MAX );
		[ $post_id, $post_ids ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$_GET['event_id'] = $post_id;
		$_GET['search'] = '';

		tribe_cache()->reset();
		ob_start();
		/*
		Columns headers are cached in the `get_column_headers` function
		by screen id. To avoid the cache, we need to set the screen id
		to something different from the default one.
		*/
		$attendees = tribe( Attendees::class );
		$attendees->screen_setup();
		$attendees->render();
		$html = ob_get_clean();

		// Stabilize snapshots.
		$attendee_data = $this->get_attendee_data( $attendees->attendees_table->items );
		$replace = array_combine( $post_ids, array_fill( 0, count( $post_ids ), 'POST_ID' ) ) + $attendee_data;
		uksort( $replace, function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		} );
		$html = str_replace( array_keys( $replace ), (array) $replace, $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
